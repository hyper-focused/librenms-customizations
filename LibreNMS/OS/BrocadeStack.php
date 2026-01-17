<?php

/**
 * BrocadeStack.php
 *
 * Unified OS class for Brocade Stacking Systems (FCX and ICX series)
 * Enhanced stack topology discovery and per-unit inventory
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2026 Enhanced Stack Discovery
 * @author     LibreNMS Community
 */

namespace LibreNMS\OS;

use App\Models\Device;
use App\Models\IronwareStackTopology;
use App\Models\IronwareStackMember;
use LibreNMS\Component;
use LibreNMS\OS\Shared\Foundry;

class BrocadeStack extends Foundry
{
    /**
     * Discover OS information
     * Extends Foundry base class with stack topology discovery
     *
     * @param Device $device
     * @return void
     */
    public function discoverOS(Device $device): void
    {
        parent::discoverOS($device); // YAML discovery + CPU from Foundry base

        $this->rewriteHardware(); // Translate hardware names
        $this->discoverStackTopology(); // Enhanced stack discovery
    }

    /**
     * Discover and map stack topology
     *
     * Enhanced stack discovery for Brocade stacking systems:
     * - Detects ring vs chain topology
     * - Maps all stack members
     * - Tracks per-unit hardware inventory
     * - Identifies master and member roles
     *
     * Verified with:
     * - FCX648 (sysObjectID: .1.3.6.1.4.1.1991.1.3.48.2.1)
     * - ICX6450-48 (sysObjectID: .1.3.6.1.4.1.1991.1.3.48.5.1)
     *
     * @return void
     */
    private function discoverStackTopology(): void
    {
        $device = $this->getDevice();

        // Check if stacking is enabled
        $stackState = \SnmpQuery::get('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalConfigState.0')->value();

        if ($stackState != 1) {
            // Stacking not enabled or not supported, clean up old data
            IronwareStackTopology::where('device_id', $device->device_id)->delete();
            return;
        }

        // Get stack global information
        $topologyValue = \SnmpQuery::get('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalTopology.0')->value();
        $stackMac = \SnmpQuery::get('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalMacAddress.0')->value();

        // Get all stack members from operational table
        $members = \SnmpQuery::walk('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingOperUnitTable')->table();

        if (empty($members)) {
            return; // No stack members found
        }

        // Get hardware details for each unit
        $serials = \SnmpQuery::walk('FOUNDRY-SN-AGENT-MIB::snChasUnitSerNum')->table();
        $descriptions = \SnmpQuery::walk('FOUNDRY-SN-AGENT-MIB::snChasUnitDescription')->table();

        // Map topology value to string
        $topology = match ($topologyValue) {
            1 => 'ring',
            2 => 'chain',
            3 => 'standalone',
            default => 'unknown',
        };

        // Identify master unit
        $masterUnit = $this->findMasterUnit($members);

        // Update or create topology record
        IronwareStackTopology::updateOrCreate(
            ['device_id' => $device->device_id],
            [
                'topology' => $topology,
                'unit_count' => count($members),
                'master_unit' => $masterUnit,
                'stack_mac' => $stackMac,
            ]
        );

        // Process each stack member
        $currentMemberIds = [];
        foreach ($members as $unitId => $member) {
            $stackMember = $this->discoverStackMember(
                $device,
                $unitId,
                $member,
                $serials[$unitId] ?? null,
                $descriptions[$unitId] ?? null
            );

            if ($stackMember) {
                $currentMemberIds[] = $stackMember->id;
            }
        }

        // Remove members that no longer exist (stack reduced/units failed)
        if (!empty($currentMemberIds)) {
            IronwareStackMember::where('device_id', $device->device_id)
                ->whereNotIn('id', $currentMemberIds)
                ->delete();
        }

        // Store stack data in Component system for device overview display
        $this->updateStackComponent($device, $topology, $masterUnit, $members, count($members));
    }

    /**
     * Update stack component for device overview display
     * Uses LibreNMS Component system (standard approach)
     *
     * @param Device $device
     * @param string $topology
     * @param int|null $masterUnit
     * @param array $members
     * @param int $unitCount
     * @return void
     */
    private function updateStackComponent(Device $device, string $topology, ?int $masterUnit, array $members, int $unitCount): void
    {
        $component = new Component();

        // Get existing stack component or create new
        $components = $component->getComponents($device->device_id, ['type' => 'stack']);
        
        $componentArray = [];
        if (!empty($components)) {
            $componentArray = $components;
            $stackId = array_key_first($componentArray[$device->device_id] ?? []);
        }

        // Build component data
        $componentData = [
            'type' => 'stack',
            'label' => 'Stack Topology',
            'status' => ($topology === 'ring' || $topology === 'chain') ? 1 : 0,
            'disabled' => 0,
            'ignore' => 0,
        ];

        // Build component preferences (detailed stack info)
        $prefs = [
            'topology' => $topology,
            'unit_count' => $unitCount,
            'master_unit' => $masterUnit,
            'members' => [],
        ];

        // Add member details
        foreach ($members as $unitId => $member) {
            $prefs['members'][$unitId] = [
                'unit_id' => $unitId,
                'role' => $this->mapStackRole($member['snStackingOperUnitRole'] ?? 0),
                'state' => $this->mapStackState($member['snStackingOperUnitState'] ?? 0),
                'priority' => $member['snStackingOperUnitPriority'] ?? 0,
                'mac' => $member['snStackingOperUnitMac'] ?? null,
                'version' => $member['snStackingOperUnitImgVer'] ?? null,
            ];
        }

        if (isset($stackId)) {
            // Update existing component
            $component->setComponentPrefs($stackId, $prefs);
        } else {
            // Create new component
            $stackId = $component->createComponent($device->device_id, 'stack');
            $component->setComponentPrefs($stackId, $prefs);
        }
    }

    /**
     * Discover individual stack member
     *
     * @param Device $device
     * @param int $unitId
     * @param array $member SNMP data for this member
     * @param string|null $serial Serial number
     * @param string|null $description Hardware description
     * @return IronwareStackMember|null
     */
    private function discoverStackMember(
        Device $device,
        int $unitId,
        array $member,
        ?string $serial,
        ?string $description
    ): ?IronwareStackMember {
        $memberData = [
            'role' => $this->mapStackRole($member['snStackingOperUnitRole'] ?? 0),
            'state' => $this->mapStackState($member['snStackingOperUnitState'] ?? 0),
            'mac_address' => $member['snStackingOperUnitMac'] ?? null,
            'priority' => $member['snStackingOperUnitPriority'] ?? 0,
            'version' => $member['snStackingOperUnitImgVer'] ?? null,
            'serial_number' => $serial,
            'model' => $this->extractModel($description),
        ];

        return IronwareStackMember::updateOrCreate(
            [
                'device_id' => $device->device_id,
                'unit_id' => $unitId,
            ],
            $memberData
        );
    }

    /**
     * Find master unit in stack
     *
     * @param array $members Stack member data
     * @return int|null Master unit ID
     */
    private function findMasterUnit(array $members): ?int
    {
        foreach ($members as $unitId => $member) {
            if (($member['snStackingOperUnitRole'] ?? 0) == 3) {
                return $unitId;
            }
        }
        return null;
    }

    /**
     * Map stack role value to string
     *
     * @param int $value Role from SNMP (1=standalone, 2=member, 3=master)
     * @return string Role as string
     */
    private function mapStackRole(int $value): string
    {
        return match ($value) {
            1 => 'standalone',
            2 => 'member',
            3 => 'master',
            default => 'unknown',
        };
    }

    /**
     * Map stack state value to string
     *
     * @param int $value State from SNMP (1=active, 2=remote, 3=reserved, 4=empty)
     * @return string State as string
     */
    private function mapStackState(int $value): string
    {
        return match ($value) {
            1 => 'active',
            2 => 'remote',
            3 => 'reserved',
            4 => 'empty',
            default => 'unknown',
        };
    }

    /**
     * Extract model from hardware description
     *
     * @param string|null $description Hardware description
     * @return string|null Model name
     */
    private function extractModel(?string $description): ?string
    {
        if (!$description) {
            return null;
        }

        // Extract FCX or ICX model from description
        if (preg_match('/(FCX|ICX)\s*\d+[A-Z0-9-]*/i', $description, $matches)) {
            return $matches[0];
        }

        return $description;
    }

    /**
     * Rewrite hardware names to friendly format
     * Maps internal Foundry names to user-friendly names
     *
     * @return void
     */
    private function rewriteHardware(): void
    {
        $rewrite_brocade_stack_hardware = [
            // FCX Series
            'snFCX624SSwitch' => 'FCX624S',
            'snFCX624Switch' => 'FCX624',
            'snFCX624SHPOESwitch' => 'FCX624S PoE+',
            'snFCX648SSwitch' => 'FCX648S',
            'snFCX648Switch' => 'FCX648',
            'snFCX648SHPOESwitch' => 'FCX648S PoE+',
            'snFastIronStackFCXSwitch' => 'FCX Stack',
            
            // ICX 6430 Series
            'snICX643024Switch' => 'ICX6430-24',
            'snICX643024HPOESwitch' => 'ICX6430-24 PoE+',
            'snICX643048Switch' => 'ICX6430-48',
            'snICX643048HPOESwitch' => 'ICX6430-48 PoE+',
            'snICX6430C12Switch' => 'ICX6430-C12',
            'snFastIronStackICX6430Switch' => 'ICX6430 Stack',
            
            // ICX 6450 Series
            'snICX645024Switch' => 'ICX6450-24',
            'snICX645024HPOESwitch' => 'ICX6450-24 PoE+',
            'snICX645048Switch' => 'ICX6450-48',
            'snICX645048HPOESwitch' => 'ICX6450-48 PoE+',
            'snICX6450C12PDSwitch' => 'ICX6450-C12-PD',
            'snFastIronStackICX6450Switch' => 'ICX6450 Stack',
            
            // ICX 6610 Series
            'snICX661024Switch' => 'ICX6610-24',
            'snICX661024HPOESwitch' => 'ICX6610-24 PoE+',
            'snICX661024FSwitch' => 'ICX6610-24F',
            'snICX661048Switch' => 'ICX6610-48',
            'snICX661048HPOESwitch' => 'ICX6610-48 PoE+',
            'snFastIronStackICX6610Switch' => 'ICX6610 Stack',
            
            // ICX 6650 Series
            'snICX665064Switch' => 'ICX6650-64',
            
            // ICX 7150 Series
            'snICX715024Switch' => 'ICX7150-24',
            'snICX715024POESwitch' => 'ICX7150-24 PoE+',
            'snICX715024FSwitch' => 'ICX7150-24F',
            'snICX715048Switch' => 'ICX7150-48',
            'snICX715048POESwitch' => 'ICX7150-48 PoE+',
            'snICX7150C12POESwitch' => 'ICX7150-C12 PoE+',
            'snICX7150C08PSwitch' => 'ICX7150-C08 PoE+',
            'snFastIronStackICX7150Switch' => 'ICX7150 Stack',
            
            // ICX 7250 Series
            'snICX725024Switch' => 'ICX7250-24',
            'snICX725024HPOESwitch' => 'ICX7250-24 PoE+',
            'snICX725024GSwitch' => 'ICX7250-24G',
            'snICX725048Switch' => 'ICX7250-48',
            'snICX725048HPOESwitch' => 'ICX7250-48 PoE+',
            'snFastIronStackICX7250Switch' => 'ICX7250 Stack',
            
            // ICX 7450 Series
            'snICX745024Switch' => 'ICX7450-24',
            'snICX745024HPOESwitch' => 'ICX7450-24 PoE+',
            'snICX745048Switch' => 'ICX7450-48',
            'snICX745048HPOESwitch' => 'ICX7450-48 PoE+',
            'snICX745048FSwitch' => 'ICX7450-48F',
            'snFastIronStackICX7450Switch' => 'ICX7450 Stack',
            
            // ICX 7750 Series
            'snICX775026QSwitch' => 'ICX7750-26Q',
            'snICX775048CSwitch' => 'ICX7750-48C',
            'snICX775048FSwitch' => 'ICX7750-48F',
            'snFastIronStackICX7750Switch' => 'ICX7750 Stack',
            
            // Mixed Stack
            'snFastIronStackMixedStackSwitch' => 'Mixed Stack',
        ];

        $this->getDevice()->hardware = str_replace(
            array_keys($rewrite_brocade_stack_hardware),
            array_values($rewrite_brocade_stack_hardware),
            $this->getDevice()->hardware
        );
    }
}
