<?php

/**
 * BrocadeStack.php
 *
 * Unified OS class for FastIron and ICX stackable switches (single module).
 * Covers: FastIron (FCX, FWS, FLS, etc.) and ICX series — shared MIBs and discovery.
 * Enhanced stack topology discovery and per-unit inventory for both platforms.
 *
 * This class extends the custom Foundry base class (LibreNMS\OS\Shared\Foundry)
 * which provides CPU discovery functionality. Note: This Foundry class is
 * custom to this project and does not exist in the official LibreNMS repository.
 *
 * CRITICAL ISSUE: Stack MIBs don't work on actual stacked switches!
 * Both ICX6450 (2-stack) and FCX648 (6-stack) show snStackMemberCount=1
 * and "No Such Instance" for all stack member table queries.
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
use Illuminate\Support\Facades\Schema;
use LibreNMS\Component;
use LibreNMS\OS\Shared\Foundry;

class BrocadeStack extends Foundry
{
    /**
<<<<<<< HEAD
     * OID Constants for stack-related queries
     * 
     * OIDs verified against FOUNDRY-SN-STACKING-MIB from LibreNMS repository.
     * Note: These OIDs may not exist on firmware 08.0.30u - see LIMITATIONS.md
     */
    private const OID_STACK_MEMBER_COUNT = '.1.3.6.1.4.1.1991.1.1.2.1.1.0';
    private const OID_STACK_PORT_COUNT = '.1.3.6.1.4.1.1991.1.1.2.1.3.0';
    
    // Stack OIDs from FOUNDRY-SN-STACKING-MIB
    // Base: snStacking = .1.3.6.1.4.1.1991.1.1.3.31
    private const OID_STACK_CONFIG_STATE = '.1.3.6.1.4.1.1991.1.1.3.31.1.1.0';
    private const OID_STACK_TOPOLOGY = '.1.3.6.1.4.1.1991.1.1.3.31.1.5.0';
    private const OID_STACK_MAC = '.1.3.6.1.4.1.1991.1.1.3.31.1.2.0';
    private const OID_STACK_OPER_TABLE = '.1.3.6.1.4.1.1991.1.1.3.31.2.2';
    private const OID_STACK_CONFIG_TABLE = '.1.3.6.1.4.1.1991.1.1.3.31.2.1';

    /**
     * Discover processors for Foundry-based devices
     * Uses stack-aware OIDs when stack is detected, standard OIDs for standalone
     *
     * For stacked configs: snAgentCpuUtilTable is indexed by unit/slot (stack-aware)
     * For standalone: Same table but only contains unit 1 data
     *
     * @return void
     */
    public function discoverProcessors(): void
    {
        // CPU monitoring is now handled via YAML sensors (load type) instead of custom processor discovery
        // This provides better integration with LibreNMS sensor system and avoids duplication
        // The snAgentCpuUtilTable is polled via sensors configuration in brocade-stack.yaml
    }

    /**
=======
>>>>>>> 186097fef2a222df859e331a0411d20c6ccbcf26
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

<<<<<<< HEAD
        \Log::info("BrocadeStack: Starting stack topology discovery for device {$device->hostname} (ID: {$device->device_id})");

        $stackStateQuery = \SnmpQuery::get('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalConfigState.0');
        $stackState = $stackStateQuery->value();
        
        if (config('app.debug')) {
            \Log::debug("BrocadeStack: Stack state query", [
                'device_id' => $device->device_id,
                'hostname' => $device->hostname,
                'stackState' => $stackState,
                'stackState_exists' => $stackState !== null,
                'oid' => self::OID_STACK_CONFIG_STATE
            ]);
        }

        if ($stackState === null || $stackState != 1) {
            $isStackCapable = $this->isStackCapableDevice($device);

            if (!$isStackCapable) {
                IronwareStackTopology::where('device_id', $device->device_id)->delete();
                return;
            }
            
            $stackDetected = $this->detectStackViaAlternatives($device);
            
            if (!$stackDetected) {
                IronwareStackTopology::updateOrCreate(
                    ['device_id' => $device->device_id],
                    [
                        'topology' => 'standalone',
                        'unit_count' => 1,
                        'master_unit' => 1,
                        'stack_mac' => null,
                    ]
                );
                $this->discoverStandaloneUnit($device);
            }
=======
        // Require stack tables; skip stack discovery if migration was not run
        if (! Schema::hasTable('ironware_stack_topology') || ! Schema::hasTable('ironware_stack_members')) {
            \Log::warning('BrocadeStack: ironware_stack_topology / ironware_stack_members tables missing. Run: php artisan migrate --force');
>>>>>>> 186097fef2a222df859e331a0411d20c6ccbcf26
            return;
        }

        // Check if stacking is enabled - handle case where OID doesn't exist
        $stackStateQuery = \SnmpQuery::get('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalConfigState.0');
        $stackState = $stackStateQuery->value();

        // If the OID doesn't exist or stacking is not enabled, check if device is stack-capable
        if ($stackState === null || $stackState != 1) {
            // Check if this is a standalone stack-capable device
            $isStackCapable = $this->isStackCapableDevice($device);

            if (!$isStackCapable) {
                // Not stack-capable, clean up old data
                IronwareStackTopology::where('device_id', $device->device_id)->delete();
                return;
            }

            // Device is stack-capable but not stacked - record as standalone
            IronwareStackTopology::updateOrCreate(
                ['device_id' => $device->device_id],
                [
                    'topology' => 'standalone',
                    'unit_count' => 1,
                    'master_unit' => 1,
                    'stack_mac' => null,
                ]
            );

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
            // Get serial and description for this unit, handling missing data
            $unitSerial = null;
            $unitDescription = null;

            if (!empty($serials) && isset($serials[$unitId])) {
                $unitSerial = $serials[$unitId];
            }
            if (!empty($descriptions) && isset($descriptions[$unitId])) {
                $unitDescription = $descriptions[$unitId];
            }

            $stackMember = $this->discoverStackMember(
                $device,
                $unitId,
                $member,
                $unitSerial,
                $unitDescription
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
<<<<<<< HEAD
     * Detect stack via interface names
     * Stack interfaces are named like "Stack1/1", "Stack1/2", etc.
     *
     * @param Device $device
     * @return array|null Array of detected units with their ports, or null if no stack interfaces found
     */
    private function detectStackViaInterfaces(Device $device): ?array
    {
        $ports = \DB::table('ports')
            ->where('device_id', $device->device_id)
            ->where('ifDescr', 'like', 'Stack%')
            ->get(['ifIndex', 'ifDescr', 'ifOperStatus', 'ifAdminStatus']);

        if ($ports->isEmpty()) {
            return null;
        }

        $units = [];
        foreach ($ports as $port) {
            // Parse "Stack1/1" -> unit 1, port 1
            // Parse "Stack2/1" -> unit 2, port 1
            if (preg_match('/^Stack(\d+)\/(\d+)$/', $port->ifDescr, $matches)) {
                $unitId = (int)$matches[1];
                $portNum = (int)$matches[2];
                
                if (!isset($units[$unitId])) {
                    $units[$unitId] = [
                        'unit_id' => $unitId,
                        'ports' => [],
                        'port_count' => 0,
                        'active_ports' => 0
                    ];
                }
                $units[$unitId]['ports'][] = [
                    'port_num' => $portNum,
                    'ifIndex' => $port->ifIndex,
                    'operStatus' => $port->ifOperStatus,
                    'adminStatus' => $port->ifAdminStatus
                ];
                $units[$unitId]['port_count']++;
                if ($port->ifOperStatus === 'up') {
                    $units[$unitId]['active_ports']++;
                }
            }
        }

        return !empty($units) ? $units : null;
    }

    /**
     * Detect stack using alternative methods when standard MIBs don't work
     * 
     * Tries multiple detection strategies:
     * 1. Interface-based detection (Stack1/1, Stack1/2, etc.)
     * 2. sysName parsing (e.g., "h08-h05_stack")
     * 3. Configuration table (if available)
     * 
     * @param Device $device
     * @return bool True if stack was detected and recorded
     */
    private function detectStackViaAlternatives(Device $device): bool
    {
        // Method 1: Interface-based detection
        $stackInterfaces = $this->detectStackViaInterfaces($device);
        if ($stackInterfaces !== null) {
            $unitCount = count($stackInterfaces);
            
            IronwareStackTopology::updateOrCreate(
                ['device_id' => $device->device_id],
                [
                    'topology' => $unitCount > 1 ? 'ring' : 'standalone',
                    'unit_count' => $unitCount,
                    'master_unit' => 1,
                    'stack_mac' => null,
                ]
            );
            
            foreach ($stackInterfaces as $unitId => $unitData) {
                IronwareStackMember::updateOrCreate(
                    [
                        'device_id' => $device->device_id,
                        'unit_id' => $unitId,
                    ],
                    [
                        'role' => $unitId === 1 ? 'master' : 'member',
                        'state' => 'active',
                        'serial_number' => null,
                        'model' => $this->extractModelFromSysDescr($device->sysDescr),
                        'version' => $this->extractVersionFromSysDescr($device->sysDescr),
                        'mac_address' => null,
                        'priority' => 128,
                    ]
                );
            }
            
            if (config('app.debug')) {
                \Log::debug("BrocadeStack: Detected stack via interfaces", [
                    'device_id' => $device->device_id,
                    'unit_count' => $unitCount
                ]);
            }
            
            return true;
        }
        
        // Method 2: sysName parsing
        if ($this->detectStackViaSysName($device)) {
            return true;
        }
        
        // Method 3: Try configuration table
        $configMembersQuery = \SnmpQuery::walk('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingConfigUnitTable');
        $configMembers = $configMembersQuery->table();
        
        if (!empty($configMembers)) {
            $this->processConfigTableMembers($device, $configMembers);
            return true;
        }
        
        return false;
    }

    /**
=======
>>>>>>> 186097fef2a222df859e331a0411d20c6ccbcf26
     * Try additional stack-related OIDs that might work on stacked switches
     *
     * @param Device $device
     * @return bool True if any stack data was found
     */
    private function tryAdditionalStackOIDs(Device $device): bool
    {
        $stackDataFound = false;

<<<<<<< HEAD
        // Method 1: Try sysName parsing for stack indicators
        \Log::info("BrocadeStack: Attempting sysName-based stack detection for device {$device->hostname}");
        if ($this->detectStackViaSysName($device)) {
            return true;
        }

        // Method 2: Try alternative OIDs
=======
        // Try various alternative stack OIDs
>>>>>>> 186097fef2a222df859e331a0411d20c6ccbcf26
        $alternativeOIDs = [
            // Try direct member count queries
            'snStackMemberCount' => '.1.3.6.1.4.1.1991.1.1.2.1.1.0',
            'snStackPortCount' => '.1.3.6.1.4.1.1991.1.1.2.1.3.0',

            // Try different stack table variations
            'stackTable' => '.1.3.6.1.4.1.1991.1.1.2.1.2',
            'stackPortTable' => '.1.3.6.1.4.1.1991.1.1.2.1.4',

            // Try Brocade-specific enterprise OIDs
            'brocadeStackInfo' => '.1.3.6.1.4.1.1588.2.1.1.1',
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
     * Detect stack via sysName parsing
     * Example: "h08-h05_stack" suggests a stack
     *
     * @param Device $device
     * @return bool True if stack was detected and recorded
     */
    private function detectStackViaSysName(Device $device): bool
    {
        // Check if sysName contains stack indicators
        if (preg_match('/_stack$/i', $device->sysName)) {
            \Log::info("BrocadeStack: Detected stack via sysName pattern: {$device->sysName}");
            // sysName ends with "_stack" - likely a stack
            // Try to extract unit count from name pattern
            $unitCount = 1; // Default

            // Pattern: "h08-h05_stack" suggests 2 units (h08 and h05)
            if (preg_match('/-.*_stack$/i', $device->sysName)) {
                // Count hyphens before _stack to estimate units
                $stackName = preg_replace('/_stack$/i', '', $device->sysName);
                $parts = explode('-', $stackName);
                $unitCount = count($parts);
            }

            IronwareStackTopology::updateOrCreate(
                ['device_id' => $device->device_id],
                [
                    'topology' => $unitCount > 1 ? 'ring' : 'standalone',
                    'unit_count' => $unitCount,
                    'master_unit' => 1,
                    'stack_mac' => null,
                ]
            );

            // Create member records for each unit
            for ($unitId = 1; $unitId <= $unitCount; $unitId++) {
                IronwareStackMember::updateOrCreate(
                    [
                        'device_id' => $device->device_id,
                        'unit_id' => $unitId,
                    ],
                    [
                        'role' => $unitId === 1 ? 'master' : 'member',
                        'state' => 'active',
                        'serial_number' => null,
                        'model' => $this->extractModelFromSysDescr($device->sysDescr),
                        'version' => $this->extractVersionFromSysDescr($device->sysDescr),
                        'mac_address' => null,
                        'priority' => 128,
                    ]
                );
            }

            if (config('app.debug')) {
                \Log::debug("BrocadeStack: Detected stack via sysName", [
                    'device_id' => $device->device_id,
                    'sysName' => $device->sysName,
                    'unit_count' => $unitCount
                ]);
            }

            return true;
        }

        return false;
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

        // Create single unit record
        $memberData = [
            'role' => 'standalone',
            'state' => 'active',
            'serial_number' => $serialNumber,
            'model' => $model,
            'version' => $this->extractVersionFromSysDescr($device->sysDescr),
            'mac_address' => null, // Would need to get from ifPhysAddress or similar
            'priority' => 128, // Default priority for standalone
        ];

        IronwareStackMember::updateOrCreate(
            [
                'device_id' => $device->device_id,
                'unit_id' => 1,
            ],
            $memberData
        );
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
        IronwareStackTopology::updateOrCreate(
            ['device_id' => $device->device_id],
            [
                'topology' => 'standalone',
                'unit_count' => 1,
                'master_unit' => 1,
                'stack_mac' => null,
            ]
        );

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

        // Create topology record (caller already fetched topology/mac)
        IronwareStackTopology::updateOrCreate(
            ['device_id' => $device->device_id],
            [
                'topology' => $this->mapTopologyValue($topologyValue),
                'unit_count' => $memberCount,
                'master_unit' => 1, // Assume first unit is master in config
                'stack_mac' => $stackMac,
            ]
        );

        // Process each configured member
        foreach ($configMembers as $unitId => $member) {
            $memberData = [
                'role' => 'member', // Config table doesn't specify roles clearly
                'state' => 'active', // Assume configured units are active
                'serial_number' => null, // Config table may not have serials
                'model' => null, // Config table may not have models
                'version' => null, // Config table may not have versions
                'mac_address' => null,
                'priority' => $member['snStackingConfigUnitPriority'] ?? 128,
            ];

            IronwareStackMember::updateOrCreate(
                [
                    'device_id' => $device->device_id,
                    'unit_id' => $unitId,
                ],
                $memberData
            );
        }

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
}
