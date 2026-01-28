<?php

/**
 * BrocadeStack.php
 *
 * Unified OS class for Brocade Stacking Systems (FCX and ICX series)
 * Enhanced stack topology discovery and per-unit inventory
 *
 * This class extends LibreNMS\OS directly and implements ProcessorDiscovery
 * for CPU monitoring.
 *
 * Stack discovery uses alternative detection methods when standard stack MIBs
 * are unavailable (e.g., firmware 08.0.30u). See docs/LIMITATIONS.md for details.
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
use LibreNMS\OS;
use LibreNMS\Interfaces\Discovery\ProcessorDiscovery;

class BrocadeStack extends OS implements ProcessorDiscovery
{
    /**
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
        $device = $this->getDevice();
        $topology = IronwareStackTopology::where('device_id', $device->device_id)->first();
        $isStacked = $topology && $topology->topology !== 'standalone' && $topology->unit_count > 1;

        $cpuData = \SnmpQuery::walk('FOUNDRY-SN-AGENT-MIB::snAgentCpuUtilTable')->table();

        if (!empty($cpuData)) {
            foreach ($cpuData as $index => $data) {
                // Prefer snAgentCpuUtil100thPercent (column 6), fallback to snAgentCpuUtilPercent (column 5)
                // snAgentCpuUtilValue (column 4) is deprecated per MIB
                $util5min = $data['snAgentCpuUtil100thPercent'] ?? $data['snAgentCpuUtilPercent'] ?? $data['snAgentCpuUtilValue'] ?? null;

                if ($util5min !== null) {
                    // Index format: slot.cpu.interval (e.g., "1.1.0" = unit 1, CPU 1, interval 0)
                    // Extract unit ID from index for stacked systems
                    $unitId = $isStacked ? explode('.', $index)[0] : $index;
                    $label = $isStacked ? "Unit {$unitId} CPU" : "CPU";
                    
                    // Convert 100th percent to percent if needed
                    $utilPercent = isset($data['snAgentCpuUtil100thPercent']) ? $util5min / 100 : $util5min;
                    
                    $this->discoverProcessor(
                        'FOUNDRY-SN-AGENT-MIB',
                        $index,
                        $utilPercent,
                        isset($data['snAgentCpuUtil100thPercent']) ? 'snAgentCpuUtil100thPercent' : (isset($data['snAgentCpuUtilPercent']) ? 'snAgentCpuUtilPercent' : 'snAgentCpuUtilValue'),
                        $label,
                        1,
                        $index,
                        null,
                        true
                    );
                }
            }
        }
    }

    /**
     * Discover OS information
     * Extends OS base class with stack topology discovery
     *
     * @param Device $device
     * @return void
     */
    public function discoverOS(Device $device): void
    {
        parent::discoverOS($device);
        $this->rewriteHardware();
        $this->discoverStackTopology();
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
            return;
        }

        $topologyQuery = \SnmpQuery::get('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalTopology.0');
        $topologyValue = $topologyQuery->value() ?? 3;
        
        $stackMacQuery = \SnmpQuery::get(self::OID_STACK_MAC);
        $stackMac = $stackMacQuery->value();

        if (config('app.debug')) {
            \Log::debug("BrocadeStack: Stack topology and MAC queries", [
                'topology_value' => $topologyValue,
                'topology_exists' => $topologyQuery->value() !== null,
                'stack_mac' => $stackMac,
                'stack_mac_exists' => $stackMacQuery->value() !== null
            ]);
        }

        $membersQuery = \SnmpQuery::walk('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingOperUnitTable');
        $members = $membersQuery->table();

        if (config('app.debug')) {
            \Log::debug("BrocadeStack: Stack members query result", [
                'members_found' => !empty($members),
                'members_count' => count($members),
                'members_keys' => !empty($members) ? array_keys($members) : [],
                'query_error' => $membersQuery->error(),
                'oid' => self::OID_STACK_OPER_TABLE
            ]);
        }

        if (empty($members)) {
            // No stack members found via standard MIB (expected on 08.0.30u)
            // Try alternative detection methods
            $stackDetected = $this->detectStackViaAlternatives($device);
            
            if (!$stackDetected) {
                // Fall back to standalone if no stack detected
                $this->discoverStackViaAlternativeMethod($device);
            }
            return;
        }

        $chasUnitTable = \SnmpQuery::walk('FOUNDRY-SN-AGENT-MIB::snChasUnitTable')->table();
        $unitSerials = [];
        $unitDescriptions = [];
        
        if (!empty($chasUnitTable)) {
            foreach ($chasUnitTable as $unitIndex => $unitData) {
                if (isset($unitData['snChasUnitSerNum'])) {
                    $unitSerials[$unitIndex] = $unitData['snChasUnitSerNum'];
                }
                if (isset($unitData['snChasUnitPartNum'])) {
                    $unitDescriptions[$unitIndex] = $unitData['snChasUnitPartNum'];
                }
            }
        }
        
        if (config('app.debug')) {
            \Log::debug("BrocadeStack: Stack-aware hardware details query", [
                'serials_found' => !empty($unitSerials),
                'descriptions_found' => !empty($unitDescriptions),
                'serials_count' => count($unitSerials),
                'descriptions_count' => count($unitDescriptions),
                'using_stack_oids' => true,
                'table_keys' => array_keys($chasUnitTable)
            ]);
        }

        // Map topology value to string
        $topology = match ($topologyValue) {
            1 => 'ring',
            2 => 'chain',
            3 => 'standalone',
            default => 'unknown',
        };

        $masterUnit = $this->findMasterUnit($members);
        IronwareStackTopology::updateOrCreate(
            ['device_id' => $device->device_id],
            [
                'topology' => $topology,
                'unit_count' => count($members),
                'master_unit' => $masterUnit,
                'stack_mac' => $stackMac,
            ]
        );

        $currentMemberIds = [];
        foreach ($members as $unitId => $member) {
            $unitSerial = $unitSerials[$unitId] ?? null;
            $unitDescription = $unitDescriptions[$unitId] ?? null;
            
            if (!$unitDescription && isset($member['snStackingOperUnitDescription'])) {
                $unitDescription = $member['snStackingOperUnitDescription'];
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

        $componentData = [
            'type' => 'stack',
            'label' => 'Stack Topology',
            'status' => ($topology === 'ring' || $topology === 'chain') ? 1 : 0,
            'disabled' => 0,
            'ignore' => 0,
        ];

        $prefs = [
            'topology' => $topology,
            'unit_count' => $unitCount,
            'master_unit' => $masterUnit,
            'members' => [],
        ];

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
            $component->setComponentPrefs($stackId, $prefs);
        } else {
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
            // sysName ends with "_stack" - likely a stack
            // Try to extract unit count from name pattern
            $unitCount = 1; // Default
            
            // Pattern: "h08-h05_stack" suggests 2 units (h08 and h05)
            if (preg_match('/-.*_stack$/i', $device->sysName)) {
                // Count hyphens before _stack to estimate units
                $parts = explode('-', $device->sysName);
                $unitCount = count($parts) - 1; // Subtract 1 for _stack part
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
     * Try additional stack-related OIDs that might work on stacked switches
     *
     * @param Device $device
     * @return bool True if any stack data was found
     */
    private function tryAdditionalStackOIDs(Device $device): bool
    {
        $stackDataFound = false;
        $alternativeOIDs = [
            'snStackMemberCount' => self::OID_STACK_MEMBER_COUNT,
            'snStackPortCount' => self::OID_STACK_PORT_COUNT,
            'stackTable' => '.1.3.6.1.4.1.1991.1.1.2.1.2',
            'stackPortTable' => '.1.3.6.1.4.1.1991.1.1.2.1.4',
            'brocadeStackInfo' => '.1.3.6.1.4.1.1588.2.1.1.1',
        ];

        foreach ($alternativeOIDs as $name => $oid) {
            $query = \SnmpQuery::get($oid);
            $value = $query->value();

            if ($value !== null) {
                $stackDataFound = true;
                if (config('app.debug')) {
                    \Log::debug("BrocadeStack: Found stack data via alternative OID", [
                        'oid_name' => $name,
                        'oid' => $oid,
                        'value' => $value
                    ]);
                }
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

        if (preg_match('/(FCX|ICX)\s*\d+[A-Z0-9-]*/i', $description, $matches)) {
            return $matches[0];
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
        if (stripos($device->sysDescr, 'Stacking System') !== false) {
            return true;
        }

        $stackModels = ['FCX', 'ICX'];
        foreach ($stackModels as $model) {
            if (stripos($device->sysDescr, $model) !== false) {
                return true;
            }
        }

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
        // For standalone devices, use standard scalar OIDs (not unit-indexed tables)
        $serialNumber = null;
        $standaloneOids = [
            'FOUNDRY-SN-AGENT-MIB::snChasSerNum.0',
            'FOUNDRY-SN-ROOT-MIB::snChasSerNum.0',
        ];

        foreach ($standaloneOids as $oid) {
            $serialQuery = \SnmpQuery::get($oid);
            if ($serialQuery->value() !== null) {
                $serialNumber = $serialQuery->value();
                break;
            }
        }
        
        if (config('app.debug')) {
            \Log::debug("BrocadeStack: Standalone hardware query", [
                'using_standalone_oids' => true,
                'serial_found' => $serialNumber !== null,
                'oids_tried' => $standaloneOids
            ]);
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
                'mac_address' => null,
                'priority' => 128
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
        $configMembersQuery = \SnmpQuery::walk('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingConfigUnitTable');
        $configMembers = $configMembersQuery->table();

        if (config('app.debug')) {
            \Log::debug("BrocadeStack: Configuration table query result", [
                'config_members_found' => !empty($configMembers),
                'config_members_count' => count($configMembers),
                'config_members_keys' => !empty($configMembers) ? array_keys($configMembers) : [],
                'query_error' => $configMembersQuery->error(),
                'oid' => self::OID_STACK_CONFIG_TABLE
            ]);
        }

        if (!empty($configMembers)) {
            $this->processConfigTableMembers($device, $configMembers);
            return;
        }

        $foundAnyStackData = $this->tryAdditionalStackOIDs($device);

        if (config('app.debug') && $foundAnyStackData) {
            \Log::debug("BrocadeStack: Found stack data via alternative OIDs", [
                'device_hostname' => $device->hostname
            ]);
        }

        if ($foundAnyStackData) {
            return; // Successfully found stack data via alternative methods
        }

        // No stack information available at all
        \Log::warning("Standard Foundry stack MIBs not available on device {$device->hostname}. Treating as standalone stack-capable device.");

        if (config('app.debug')) {
            \Log::debug("BrocadeStack: Final fallback to standalone mode", [
                'device_id' => $device->device_id,
                'hostname' => $device->hostname
            ]);
        }

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
     * @return void
     */
    private function processConfigTableMembers(Device $device, array $configMembers): void
    {
        $memberCount = count($configMembers);

        // Get topology from global config if available
        $topologyQuery = \SnmpQuery::get('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalTopology.0');
        $topologyValue = $topologyQuery->value() ?? 3;

        // Create topology record
        IronwareStackTopology::updateOrCreate(
            ['device_id' => $device->device_id],
            [
                'topology' => $this->mapTopologyValue($topologyValue),
                'unit_count' => $memberCount,
                'master_unit' => 1, // Assume first unit is master in config
                'stack_mac' => \SnmpQuery::get(self::OID_STACK_MAC)->value(),
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
        // Look for patterns like "ICX6450-48" or "FCX648"
        if (preg_match('/\b(FCX|ICX)\d+[A-Z0-9-]*/i', $sysDescr, $matches)) {
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
