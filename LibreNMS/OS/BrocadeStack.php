<?php

/**
 * BrocadeStack.php
 *
 * Unified OS class for FastIron and ICX stackable switches (single module).
 * Covers: FastIron (FCX, FWS, FLS, etc.) and ICX series — shared MIBs and discovery.
 * Enhanced stack topology discovery and per-unit inventory for both platforms.
 *
 * This class provides comprehensive support for Brocade/Ruckus stack switches
 * which provides CPU discovery functionality using FOUNDRY-SN-AGENT-MIB.
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
// Using device_attribs for LibreNMS compliance (no custom tables)
use Illuminate\Support\Facades\Schema;
use LibreNMS\Component;
use LibreNMS\Device\Processor;
use LibreNMS\Interfaces\Discovery\ProcessorDiscovery;
use LibreNMS\OS;
use LibreNMS\Util\Mac;

class BrocadeStack extends OS implements ProcessorDiscovery
{
    /**
     * Discover OS information
     * Performs stack topology discovery and hardware mapping
     *
     * @param Device $device
     * @return void
     */
    public function discoverOS(Device $device): void
    {
        parent::discoverOS($device); // YAML discovery + CPU from integrated discovery

        $this->rewriteHardware(); // Translate hardware names
        $this->discoverStackTopology(); // Enhanced stack discovery
    }

    /**
     * Discover and map stack topology
     *
     * Enhanced stack discovery for FastIron and ICX stacking systems:
     * - Detects ring vs chain topology
     * - Maps all stack members
     * - Tracks per-unit hardware inventory
     * - Identifies master and member roles
     *
     * Verified with: FCX648, ICX6450-48 (enterprise 1991); applies to FWS, FLS, all ICX.
     *
     * @return void
     */
    private function discoverStackTopology(): void
    {
        $device = $this->getDevice();

        // Check if stacking is enabled - handle case where OID doesn't exist
        $stackStateQuery = \SnmpQuery::get('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalConfigState.0');
        $stackState = $stackStateQuery->value();

        // If the OID doesn't exist or stacking is not enabled, check if device is stack-capable
        if ($stackState === null || $stackState != 1) {
            // Check if this is a standalone stack-capable device
            $isStackCapable = $this->isStackCapableDevice($device);

            if (!$isStackCapable) {
                // Not stack-capable, clean up old data
                $this->clearStackAttributes($device);
                return;
            }

            // Device is stack-capable but not stacked - record as standalone
            $this->storeStackTopology($device, [
                'topology' => 'standalone',
                'unit_count' => 1,
                'master_unit' => 1,
                'stack_mac' => null,
                'members' => [
                    1 => [
                        'role' => 'standalone',
                        'state' => 'active',
                        'serial_number' => $this->getUnitSerial($device, 1),
                        'model' => $this->getUnitModel($device, 1),
                        'version' => $this->getUnitVersion($device, 1),
                        'mac_address' => $this->getUnitMac($device, 1),
                        'priority' => 128, // Default priority
                    ]
                ]
            ]);

            // Still try to discover single unit hardware info
            $this->discoverStandaloneUnit($device);
            return;
        }

        // Get stack global information - handle missing OIDs
        $topologyQuery = \SnmpQuery::get('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalTopology.0');
        $topologyValue = $topologyQuery->value() ?? 3; // Default to standalone
        $stackMacQuery = \SnmpQuery::get('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalMacAddress.0');
        $stackMac = $stackMacQuery->value();

        $membersQuery = \SnmpQuery::walk('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingOperUnitTable');
        $members = $membersQuery->table();

        if (empty($members)) {
            // No stack members found via standard MIB, try alternative detection
            $this->discoverStackViaAlternativeMethod($device);
            return;
        }

        // Get hardware details for each unit - handle missing OIDs
        $serialsQuery = \SnmpQuery::walk('FOUNDRY-SN-AGENT-MIB::snChasUnitSerNum');
        $serials = $serialsQuery->table();

        $descriptionsQuery = \SnmpQuery::walk('FOUNDRY-SN-AGENT-MIB::snChasUnitDescription');
        $descriptions = $descriptionsQuery->table();

        // Map topology value to string
        $topology = match ($topologyValue) {
            1 => 'ring',
            2 => 'chain',
            3 => 'standalone',
            default => 'unknown',
        };

        // Identify master unit
        $masterUnit = $this->findMasterUnit($members);

        // Build stack topology data
        $stackData = [
            'topology' => $topology,
            'unit_count' => count($members),
            'master_unit' => $masterUnit,
            'stack_mac' => $stackMac,
            'members' => []
        ];

        // Process each stack member
        foreach ($members as $unitId => $member) {
            // Get serial and description for this unit, handling missing data
            $unitSerial = null;
            $unitDescription = null;

            if (!empty($serials) && isset($serials[$unitId])) {
                $unitSerial = $serials[$unitId];
            }
            if (!empty($descriptions) && isset($descriptions[$unitId])) {
                $unitDescription = $descriptions[$unitId];
            }

            // Build member data
            $stackData['members'][$unitId] = [
                'role' => $this->mapRoleValue($member['snStackingOperUnitRole'] ?? 5),
                'state' => $this->mapStateValue($member['snStackingOperUnitState'] ?? 4),
                'serial_number' => $unitSerial,
                'model' => $unitDescription,
                'version' => $this->getUnitVersion($device, $unitId),
                'mac_address' => $this->getUnitMac($device, $unitId),
                'priority' => $member['snStackingOperUnitPriority'] ?? 128,
            ];
        }

        // Store the complete stack topology data
        $this->storeStackTopology($device, $stackData);

        // Store stack data in Component system for device overview display
        $this->updateStackComponent($device, $topology, $masterUnit, $members, count($members));
    }

    /**
     * Store stack topology data in device_attribs (LibreNMS compliant approach)
     *
     * @param Device $device
     * @param array $stackData
     * @return void
     */
    private function storeStackTopology(Device $device, array $stackData): void
    {
        $device->setAttrib('brocade_stack_topology', $stackData['topology']);
        $device->setAttrib('brocade_stack_unit_count', $stackData['unit_count']);
        $device->setAttrib('brocade_stack_master_unit', $stackData['master_unit']);

        // Only set MAC if we have a valid value (not null)
        if ($stackData['stack_mac'] !== null) {
            $device->setAttrib('brocade_stack_mac', $stackData['stack_mac']);
        }

        $device->setAttrib('brocade_stack_members', json_encode($stackData['members']));
    }

    /**
     * Retrieve stack topology data from device_attribs
     *
     * @param Device $device
     * @return array
     */
    private function getStackTopology(Device $device): array
    {
        return [
            'topology' => $device->getAttrib('brocade_stack_topology') ?? 'unknown',
            'unit_count' => (int) ($device->getAttrib('brocade_stack_unit_count') ?? 0),
            'master_unit' => (int) ($device->getAttrib('brocade_stack_master_unit') ?? null),
            'stack_mac' => $device->getAttrib('brocade_stack_mac'),
            'members' => json_decode($device->getAttrib('brocade_stack_members') ?? '[]', true),
        ];
    }

    /**
     * Clear all stack-related attributes from device_attribs
     *
     * @param Device $device
     * @return void
     */
    private function clearStackAttributes(Device $device): void
    {
        $device->forgetAttrib('brocade_stack_topology');
        $device->forgetAttrib('brocade_stack_unit_count');
        $device->forgetAttrib('brocade_stack_master_unit');
        $device->forgetAttrib('brocade_stack_mac');
        $device->forgetAttrib('brocade_stack_members');
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
     * Build stack member data array (no longer stores in separate table)
     * Used by discoverStackTopology to build complete member data
     *
     * @param Device $device
     * @param int $unitId
     * @param array $member SNMP data for this member
     * @param string|null $serial Serial number
     * @param string|null $description Hardware description
     * @return array Member data array
     */
    private function buildStackMemberData(
        Device $device,
        int $unitId,
        array $member,
        ?string $serial,
        ?string $description
    ): array {
        return [
            'role' => $this->mapStackRole($member['snStackingOperUnitRole'] ?? 0),
            'state' => $this->mapStackState($member['snStackingOperUnitState'] ?? 0),
            'mac_address' => $member['snStackingOperUnitMac'] ?? null,
            'priority' => $member['snStackingOperUnitPriority'] ?? 0,
            'version' => $member['snStackingOperUnitImgVer'] ?? null,
            'serial_number' => $serial,
            'model' => $this->extractModel($description),
        ];
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
     * Try additional stack-related OIDs that might work on stacked switches
     *
     * @param Device $device
     * @return bool True if any stack data was found
     */
    private function tryAdditionalStackOIDs(Device $device): bool
    {
        $stackDataFound = false;

        // Try various alternative stack OIDs
        $alternativeOIDs = [
            // Try direct member count queries
            'snStackMemberCount' => '.1.3.6.1.4.1.1991.1.1.2.1.1.0',
            'snStackPortCount' => '.1.3.6.1.4.1.1991.1.1.2.1.3.0',

            // Try different stack table variations
            'stackTable' => '.1.3.6.1.4.1.1991.1.1.2.1.2',
            'stackPortTable' => '.1.3.6.1.4.1.1991.1.1.2.1.4',

            // Try alternative Foundry enterprise OIDs
            'foundryStackInfo' => '.1.3.6.1.4.1.1991.1.1.2.1.1',
        ];

        foreach ($alternativeOIDs as $name => $oid) {
            $query = \SnmpQuery::get($oid);
            $value = $query->value();

            if ($value !== null) {
                $stackDataFound = true;
                \Log::info("BrocadeStack: Found stack data via alternative OID {$name}: {$value}");
            }
        }

        return $stackDataFound;
    }

    /**
     * Map topology value to string
     *
     * @param int $value Topology from SNMP (1=ring, 2=chain, 3=standalone)
     * @return string Topology as string
     */
    private function mapTopologyValue(int $value): string
    {
        return match ($value) {
            1 => 'ring',
            2 => 'chain',
            3 => 'standalone',
            default => 'unknown',
        };
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

        // Extract FastIron (FCX, FWS, FLS) or ICX model from description
        if (preg_match('/(FCX|FWS|FLS|ICX)\s*\d*[A-Z0-9-]*/i', $description, $matches)) {
            return trim($matches[0]);
        }

        return $description;
    }

    /**
     * Check if device is stack-capable based on sysDescr and sysObjectID
     *
     * @param Device $device
     * @return bool
     */
    private function isStackCapableDevice(Device $device): bool
    {
        // Check sysDescr for "Stacking System" or stack-capable models
        if (stripos($device->sysDescr, 'Stacking System') !== false) {
            return true;
        }

        // Check for known stack-capable model patterns (FastIron + ICX)
        $stackModels = ['FCX', 'FWS', 'FLS', 'ICX'];
        foreach ($stackModels as $model) {
            if (stripos($device->sysDescr, $model) !== false) {
                return true;
            }
        }

        // Check sysObjectID for Foundry enterprise with stack-capable pattern
        if (strpos($device->sysObjectID, '.1.3.6.1.4.1.1991.1.3.') === 0) {
            return true;
        }

        return false;
    }

    /**
     * Discover hardware info for standalone stack-capable device
     *
     * @param Device $device
     * @return void
     */
    private function discoverStandaloneUnit(Device $device): void
    {
        // Try alternative methods to get hardware info when standard stack OIDs don't exist

        // Try to get serial number from alternative OIDs
        $serialNumber = null;

        // Try various serial number OIDs that might work on different firmware versions
        $serialOids = [
            'FOUNDRY-SN-AGENT-MIB::snChasSerNum.0',
            'FOUNDRY-SN-ROOT-MIB::snChasSerNum.0',
            // Add more alternatives as discovered
        ];

        foreach ($serialOids as $oid) {
            $serialQuery = \SnmpQuery::get($oid);
            if ($serialQuery->value() !== null) {
                $serialNumber = $serialQuery->value();
                break;
            }
        }

        // Extract model from sysDescr
        $model = $this->extractModelFromSysDescr($device->sysDescr);

        // Store standalone topology data
        $this->storeStackTopology($device, [
            'topology' => 'standalone',
            'unit_count' => 1,
            'master_unit' => 1,
            'stack_mac' => null,
            'members' => [
                1 => [
                    'role' => 'standalone',
                    'state' => 'active',
                    'serial_number' => $serialNumber,
                    'model' => $model,
                    'version' => $this->extractVersionFromSysDescr($device->sysDescr),
                    'mac_address' => null, // Would need to get from ifPhysAddress or similar
                    'priority' => 128, // Default priority for standalone
                ]
            ]
        ]);
    }

    /**
     * Alternative stack discovery when standard MIBs don't work
     *
     * @param Device $device
     * @return void
     */
    private function discoverStackViaAlternativeMethod(Device $device): void
    {
        // Try configuration table as alternative to operational table
        $configMembersQuery = \SnmpQuery::walk('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingConfigUnitTable');
        $configMembers = $configMembersQuery->table();

        if (!empty($configMembers)) {
            // Fetch topology/mac once and pass in to avoid duplicate SNMP in processConfigTableMembers
            $topologyValue = \SnmpQuery::get('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalTopology.0')->value() ?? 3;
            $stackMac = \SnmpQuery::get('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalMacAddress.0')->value();
            $this->processConfigTableMembers($device, $configMembers, $topologyValue, $stackMac);
            return;
        }

        // Try additional alternative OIDs before giving up
        $foundAnyStackData = $this->tryAdditionalStackOIDs($device);

        if ($foundAnyStackData) {
            return; // Successfully found stack data via alternative methods
        }

        // No stack information available at all
        \Log::warning("Standard Foundry stack MIBs not available on device {$device->hostname}. Treating as standalone stack-capable device.");

        // Fall back to standalone discovery
        $this->storeStackTopology($device, [
            'topology' => 'standalone',
            'unit_count' => 1,
            'master_unit' => 1,
            'stack_mac' => null,
            'members' => [
                1 => [
                    'role' => 'standalone',
                    'state' => 'active',
                    'serial_number' => $this->getUnitSerial($device, 1),
                    'model' => $this->getUnitModel($device, 1),
                    'version' => $this->getUnitVersion($device, 1),
                    'mac_address' => $this->getUnitMac($device, 1),
                    'priority' => 128,
                ]
            ]
        ]);

        $this->discoverStandaloneUnit($device);
    }

    /**
     * Process stack members from configuration table
     *
     * @param Device $device
     * @param array $configMembers
     * @param int|null $topologyValue From snStackingGlobalTopology.0 (caller fetches once)
     * @param string|null $stackMac From snStackingGlobalMacAddress.0 (caller fetches once)
     * @return void
     */
    private function processConfigTableMembers(Device $device, array $configMembers, ?int $topologyValue = null, ?string $stackMac = null): void
    {
        $memberCount = count($configMembers);
        $topologyValue = $topologyValue ?? 3;

        // Store topology data in device_attribs (replacing old database approach)
        $topologyData = [
            'topology' => $this->mapTopologyValue($topologyValue),
            'unit_count' => $memberCount,
            'master_unit' => 1, // Assume first unit is master in config
            'stack_mac' => $stackMac,
            'members' => []
        ];

        // Process each configured member
        foreach ($configMembers as $unitId => $member) {
            $topologyData['members'][$unitId] = [
                'role' => 'member', // Config table doesn't specify roles clearly
                'state' => 'active', // Assume configured units are active
                'serial_number' => null, // Config table may not have serials
                'model' => null, // Config table may not have models
                'version' => null, // Config table may not have versions
                'mac_address' => null,
                'priority' => $member['snStackingConfigUnitPriority'] ?? 128,
            ];
        }

        // Store in device_attribs
        $device->setAttrib('brocade_stack_topology', json_encode($topologyData));

        // Update component for display
        $this->updateStackComponent($device, $this->mapTopologyValue($topologyValue), 1, $configMembers, $memberCount);
    }

    /**
     * Extract model from sysDescr
     *
     * @param string $sysDescr
     * @return string|null
     */
    private function extractModelFromSysDescr(string $sysDescr): ?string
    {
        // Look for FastIron (FCX, FWS, FLS) or ICX model patterns
        if (preg_match('/\b(FCX|FWS|FLS|ICX)\d*[A-Z0-9-]*/i', $sysDescr, $matches)) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Extract version from sysDescr
     *
     * @param string $sysDescr
     * @return string|null
     */
    private function extractVersionFromSysDescr(string $sysDescr): ?string
    {
        // Look for version patterns
        if (preg_match('/(?:IronWare|FastIron)\s+Version\s+([\d.]+[a-zA-Z0-9]*)/i', $sysDescr, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Rewrite hardware names to friendly format
     *
     * The sysObjectID resolves via FOUNDRY-SN-ROOT-MIB to names like:
     *   snFCX648SSwitch, snFCX648SRouter, snFCX648SAdvRouter, snFCX648SHPOERouter
     * The suffix indicates firmware type: Switch=L2, Router=L3, AdvRouter=L3 Advanced.
     * We strip the suffix before lookup so one map entry handles all variants.
     *
     * @return void
     */
    private function rewriteHardware(): void
    {
        $hardware = $this->getDevice()->hardware;
        if (empty($hardware)) {
            return;
        }

        // Strip firmware variant suffix to get the base MIB name
        if (! preg_match('/^(.+?)(?:Adv)?(?:Switch|Router)$/', $hardware, $matches)) {
            return;
        }

        $baseName = $matches[1];

        // Look up the base name in the known hardware map
        $rewriteMap = $this->getHardwareRewriteMap();
        if (isset($rewriteMap[$baseName])) {
            $this->getDevice()->hardware = $rewriteMap[$baseName];

            return;
        }

        // Regex fallback for models not yet in the map
        $this->rewriteHardwareFallback($baseName);
    }

    /**
     * Map of base MIB names (without Switch/Router/AdvRouter suffix) to friendly names
     *
     * @return array<string, string>
     */
    private function getHardwareRewriteMap(): array
    {
        return [
            // FastIron: FCX Series
            'snFCX624S' => 'FCX624S',
            'snFCX624' => 'FCX624',
            'snFCX624SHPOE' => 'FCX624S PoE+',
            'snFCX648S' => 'FCX648S',
            'snFCX648' => 'FCX648',
            'snFCX648SHPOE' => 'FCX648S PoE+',
            'snFastIronStackFCX' => 'FCX Stack',

            // ICX 6430 Series
            'snICX643024' => 'ICX6430-24',
            'snICX643024HPOE' => 'ICX6430-24 PoE+',
            'snICX643048' => 'ICX6430-48',
            'snICX643048HPOE' => 'ICX6430-48 PoE+',
            'snICX6430C12' => 'ICX6430-C12',
            'snFastIronStackICX6430' => 'ICX6430 Stack',

            // ICX 6450 Series
            'snICX645024' => 'ICX6450-24',
            'snICX645024HPOE' => 'ICX6450-24 PoE+',
            'snICX645048' => 'ICX6450-48',
            'snICX645048HPOE' => 'ICX6450-48 PoE+',
            'snICX6450C12PD' => 'ICX6450-C12-PD',
            'snFastIronStackICX6450' => 'ICX6450 Stack',

            // ICX 6610 Series
            'snICX661024' => 'ICX6610-24',
            'snICX661024HPOE' => 'ICX6610-24 PoE+',
            'snICX661024F' => 'ICX6610-24F',
            'snICX661048' => 'ICX6610-48',
            'snICX661048HPOE' => 'ICX6610-48 PoE+',
            'snFastIronStackICX6610' => 'ICX6610 Stack',

            // ICX 6650 Series
            'snICX665064' => 'ICX6650-64',

            // ICX 7150 Series
            'snICX715024' => 'ICX7150-24',
            'snICX715024POE' => 'ICX7150-24 PoE+',
            'snICX715024F' => 'ICX7150-24F',
            'snICX715048' => 'ICX7150-48',
            'snICX715048POE' => 'ICX7150-48 PoE+',
            'snICX7150C12POE' => 'ICX7150-C12 PoE+',
            'snICX7150C08P' => 'ICX7150-C08 PoE+',
            'snFastIronStackICX7150' => 'ICX7150 Stack',

            // ICX 7250 Series
            'snICX725024' => 'ICX7250-24',
            'snICX725024HPOE' => 'ICX7250-24 PoE+',
            'snICX725024G' => 'ICX7250-24G',
            'snICX725048' => 'ICX7250-48',
            'snICX725048HPOE' => 'ICX7250-48 PoE+',
            'snFastIronStackICX7250' => 'ICX7250 Stack',

            // ICX 7450 Series
            'snICX745024' => 'ICX7450-24',
            'snICX745024HPOE' => 'ICX7450-24 PoE+',
            'snICX745048' => 'ICX7450-48',
            'snICX745048HPOE' => 'ICX7450-48 PoE+',
            'snICX745048F' => 'ICX7450-48F',
            'snFastIronStackICX7450' => 'ICX7450 Stack',

            // ICX 7750 Series
            'snICX775026Q' => 'ICX7750-26Q',
            'snICX775048C' => 'ICX7750-48C',
            'snICX775048F' => 'ICX7750-48F',
            'snFastIronStackICX7750' => 'ICX7750 Stack',

            // Mixed Stack
            'snFastIronStackMixedStack' => 'Mixed Stack',
        ];
    }

    /**
     * Regex-based fallback for hardware names not in the known map
     *
     * @param string $baseName MIB base name (Switch/Router suffix already stripped)
     * @return void
     */
    private function rewriteHardwareFallback(string $baseName): void
    {
        // Stack: snFastIronStack{Family}
        if (preg_match('/^snFastIronStack(.+)$/', $baseName, $matches)) {
            $family = ($matches[1] === 'MixedStack') ? 'Mixed' : $matches[1];
            $this->getDevice()->hardware = "{$family} Stack";

            return;
        }

        // Individual model with HPOE/POE suffix
        if (preg_match('/^sn(.+?)(?:HPOE|POE)$/', $baseName, $matches)) {
            $model = $this->formatIcxModelName($matches[1]);
            $this->getDevice()->hardware = "{$model} PoE+";

            return;
        }

        // Compact PoE model (trailing P after port designator, e.g. C08P)
        if (preg_match('/^sn(.+C\d{2})P$/', $baseName, $matches)) {
            $model = $this->formatIcxModelName($matches[1]);
            $this->getDevice()->hardware = "{$model} PoE+";

            return;
        }

        // Plain model, no PoE
        if (preg_match('/^sn(.+)$/', $baseName, $matches)) {
            $this->getDevice()->hardware = $this->formatIcxModelName($matches[1]);
        }
    }

    /**
     * Insert dash in ICX model names between family code and port/variant suffix
     * e.g., ICX643024 → ICX6430-24, ICX7150C12 → ICX7150-C12
     *
     * @param string $rawModel
     * @return string
     */
    private function formatIcxModelName(string $rawModel): string
    {
        if (preg_match('/^(ICX\d{4})(.+)$/', $rawModel, $matches)) {
            return $matches[1] . '-' . $matches[2];
        }

        return $rawModel;
    }

    /**
     * Discover processors for Brocade devices
     * Uses FOUNDRY-SN-AGENT-MIB::snAgentCpuUtilTable for per-slot/module CPU monitoring.
     * Returns an array of Processor objects (required by ProcessorDiscovery interface).
     *
     * @return array<int, Processor>
     */
    public function discoverProcessors(): array
    {
        $processors = [];

        // Get CPU utilization data from snAgentCpuUtilTable (per-module/slot)
        $cpuData = \SnmpQuery::walk('FOUNDRY-SN-AGENT-MIB::snAgentCpuUtilTable')->table();

        if (empty($cpuData)) {
            return $processors;
        }

        foreach ($cpuData as $index => $data) {
            $util5min = $data['snAgentCpuUtilValue'] ?? null;
            if ($util5min === null || ! is_numeric($util5min)) {
                continue;
            }

            // OID for this index (snAgentCpuUtilPercent.INDEX)
            $oid = '.1.3.6.1.4.1.1991.1.1.2.11.1.1.5.' . $index;

            $processor = Processor::discover(
                'FOUNDRY-SN-AGENT-MIB',
                $this->getDeviceId(),
                $oid,
                (string) $index,
                "Slot {$index}",
                1,
                (int) $util5min,
                null,
                null,
                (string) $index
            );

            if ($processor->isValid()) {
                $processors[] = $processor;
            }
        }

        return $processors;
    }

    /**
     * Get unit serial number
     *
     * @param Device $device
     * @param int $unitId
     * @return string|null
     */
    private function getUnitSerial(Device $device, int $unitId): ?string
    {
        $serialQuery = \SnmpQuery::get("FOUNDRY-SN-AGENT-MIB::snChasUnitSerNum.{$unitId}");
        return $serialQuery->value();
    }

    /**
     * Get unit model/description
     *
     * @param Device $device
     * @param int $unitId
     * @return string|null
     */
    private function getUnitModel(Device $device, int $unitId): ?string
    {
        $modelQuery = \SnmpQuery::get("FOUNDRY-SN-AGENT-MIB::snChasUnitDescription.{$unitId}");
        return $modelQuery->value();
    }

    /**
     * Get unit firmware version
     *
     * @param Device $device
     * @param int $unitId
     * @return string|null
     */
    private function getUnitVersion(Device $device, int $unitId): ?string
    {
        // Try to get version from various OIDs
        $versionOids = [
            "FOUNDRY-SN-AGENT-MIB::snAgentBrdSoftwareVer.{$unitId}",
            "FOUNDRY-SN-AGENT-MIB::snAgImgVer.0",  // Global version as fallback
        ];

        foreach ($versionOids as $oid) {
            $versionQuery = \SnmpQuery::get($oid);
            $version = $versionQuery->value();
            if ($version !== null) {
                return $version;
            }
        }

        return null;
    }

    /**
     * Get unit MAC address
     *
     * @param Device $device
     * @param int $unitId
     * @return string|null
     */
    private function getUnitMac(Device $device, int $unitId): ?string
    {
        try {
            $macQuery = \SnmpQuery::get("FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingOperUnitMac.{$unitId}");
            $mac = $macQuery->value();

            if ($mac !== null && is_string($mac) && !empty($mac)) {
                // Convert to standard MAC format if needed
                $parsedMac = Mac::parse($mac);
                return $parsedMac ? $parsedMac->hex() : null;
            }
        } catch (\Exception $e) {
            // If anything fails, return null
            return null;
        }

        return null;
    }
}
