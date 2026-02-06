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
        $this->discoverPoePortSensors(); // PoE per-port sensors
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
     * Maps internal Foundry names to user-friendly names
     *
     * @return void
     */
    private function rewriteHardware(): void
    {
        $rewrite_brocade_stack_hardware = [
            // FastIron: FCX Series
            'snFCX624SSwitch' => 'FCX624S',
            'snFCX624Switch' => 'FCX624',
            'snFCX624SHPOESwitch' => 'FCX624S PoE+',
            'snFCX648SSwitch' => 'FCX648S',
            'snFCX648Switch' => 'FCX648',
            'snFCX648SHPOESwitch' => 'FCX648S PoE+',
            'snFastIronStackFCXSwitch' => 'FCX Stack',
            // FastIron: FWS/FLS (add OID keys as discovered from MIBs)
            
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
     * Discover per-port PoE sensors
     *
     * Discovers PoE power consumption and limits for each port.
     * Sensors are linked to individual port pages via port_id.
     * Only runs on PoE-capable hardware (gracefully skips if table doesn't exist).
     *
     * @return void
     */
    private function discoverPoePortSensors(): void
    {
        $device = $this->getDevice();

        // Get all PoE port data from FOUNDRY-POE-MIB
        $poePortData = \SnmpQuery::walk('FOUNDRY-POE-MIB::snAgentPoePortTable')->table();

        if (empty($poePortData)) {
            // No PoE data available (non-PoE hardware or unsupported)
            return;
        }

        // Get all ports from LibreNMS database to map port indices
        $ports = $device->ports()->get();
        $portMap = [];

        foreach ($ports as $port) {
            // Map by ifIndex - Brocade uses ifIndex for port table indices
            $portMap[$port->ifIndex] = $port;
        }

        foreach ($poePortData as $index => $data) {
            // Check if we have a matching port in LibreNMS
            if (!isset($portMap[$index])) {
                continue;
            }

            $port = $portMap[$index];
            $portLabel = $port->ifDescr ?: "Port {$index}";

            // Check port PoE status (OID .2)
            // 1=notCapable, 2=disabled, 3=enabled, 4=legacyEnabled
            $poeStatus = $data['snAgentPoePortStatus'] ?? 1;

            // Skip non-PoE capable ports (status = 1)
            if ($poeStatus == 1) {
                continue;
            }

            // PoE Port Allocated Limit (OID .3, not .4!)
            // Units: milliwatts, convert to watts
            $wattage = $data['snAgentPoePortPower'] ?? null;
            if ($wattage !== null && is_numeric($wattage)) {
                $wattageOid = '.1.3.6.1.4.1.1991.1.1.2.14.2.2.1.3.' . $index;

                discover_sensor(
                    $valid = null,
                    'power',
                    $device,
                    $wattageOid,
                    "poe-limit-{$index}",
                    'brocade-poe',
                    "{$portLabel} PoE Limit",
                    1000, // divisor (mW to W)
                    1, // multiplier
                    0, // low_limit
                    null, // low_warn_limit
                    null, // warn_limit
                    null, // high_limit
                    $wattage, // current value in mW
                    'sensor',
                    $port->port_id, // link to port
                    null, // rrd_type
                    null // group
                );
            }

            // PoE Port Current Consumption (OID .6, not .5!)
            // Units: milliwatts, convert to watts
            $consumed = $data['snAgentPoePortConsumedPower'] ?? null;
            if ($consumed !== null && is_numeric($consumed)) {
                $consumedOid = '.1.3.6.1.4.1.1991.1.1.2.14.2.2.1.6.' . $index;

                discover_sensor(
                    $valid = null,
                    'power',
                    $device,
                    $consumedOid,
                    "poe-consumption-{$index}",
                    'brocade-poe',
                    "{$portLabel} PoE Consumption",
                    1000, // divisor (mW to W)
                    1, // multiplier
                    0, // low_limit
                    null, // low_warn_limit
                    null, // warn_limit
                    null, // high_limit
                    $consumed, // current value in mW
                    'sensor',
                    $port->port_id, // link to port
                    null, // rrd_type
                    null // group
                );
            }
        }
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
