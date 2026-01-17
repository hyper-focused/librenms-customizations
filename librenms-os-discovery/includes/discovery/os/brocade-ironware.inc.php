<?php

/**
 * LibreNMS OS Discovery Module for Brocade Ironware Switches (FCX and ICX Series)
 *
 * This module provides enhanced OS discovery for Foundry/Brocade FCX and ICX switches,
 * with special handling for stacked configurations.
 *
 * Supported models:
 * - FCX series (FCX648S, FCX624S, etc.)
 * - ICX series (ICX6450, ICX7150, ICX7250, ICX7450, ICX7750, etc.)
 *
 * @package LibreNMS
 * @subpackage Discovery
 * @author LibreNMS Community
 * @license MIT
 */

// Brocade Ironware OS Discovery (FCX and ICX)
// Based on real device testing with FCX648 and ICX6450-48
if (!$os) {
    $sysObjectID = snmp_get($device, 'SNMPv2-MIB::sysObjectID.0', '-Oqv');
    $sysDescr = snmp_get($device, 'SNMPv2-MIB::sysDescr.0', '-Oqv');
    
    // Check for Foundry/Brocade Ironware switches
    // Real devices use .1.3.6.1.4.1.1991.1.3.48.X.Y pattern
    // - FCX648: .1.3.6.1.4.1.1991.1.3.48.2.1
    // - ICX6450-48: .1.3.6.1.4.1.1991.1.3.48.5.1
    if (preg_match('/\.1\.3\.6\.1\.4\.1\.1991\.1\.3\.48\.(\d+)\./', $sysObjectID, $matches)) {
        $series_id = $matches[1];
        
        // Determine OS based on model in sysDescr
        if (stripos($sysDescr, 'FCX') !== false) {
            $os = 'foundry-fcx';
            $device['switch_series'] = 'FCX';
            
            // Extract specific FCX model
            if (preg_match('/FCX\s*(\d+)/i', $sysDescr, $model_matches)) {
                $device['hardware'] = 'FCX' . $model_matches[1];
            }
            
            log_event('OS discovered as Foundry FCX', $device, 'discovery', 1);
        } elseif (preg_match('/ICX\s*(\d{4})/i', $sysDescr, $model_matches)) {
            $icx_model = $model_matches[1];
            
            // Set specific ICX OS based on series
            if (in_array($icx_model, ['6430', '6450', '6610', '6650'])) {
                $os = 'brocade-icx6450';  // ICX6400 series
            } elseif (str_starts_with($icx_model, '715')) {
                $os = 'brocade-icx7150';
            } elseif (str_starts_with($icx_model, '725')) {
                $os = 'brocade-icx7250';
            } elseif (str_starts_with($icx_model, '745')) {
                $os = 'brocade-icx7450';
            } elseif (str_starts_with($icx_model, '765')) {
                $os = 'brocade-icx7650';
            } elseif (str_starts_with($icx_model, '775')) {
                $os = 'brocade-icx7750';
            } else {
                $os = 'brocade-icx';  // Generic ICX fallback
            }
            
            $device['switch_series'] = 'ICX';
            $device['icx_model'] = $icx_model;
            $device['hardware'] = 'ICX' . $icx_model;
            
            log_event("OS discovered as Brocade ICX $icx_model", $device, 'discovery', 1);
        } else {
            // Generic Brocade Ironware fallback
            $os = 'brocade-ironware';
            $device['switch_series'] = 'Ironware';
            log_event('OS discovered as Brocade Ironware', $device, 'discovery', 1);
        }
        
        // Detect if stacking system from sysDescr
        if (stripos($sysDescr, 'Stacking System') !== false) {
            $device['stack_capable'] = true;
        }
        
        // Extract version information
        if (preg_match('/(?:IronWare|FastIron)\s+Version\s+([\d.]+[a-zA-Z0-9]*)/i', $sysDescr, $version_matches)) {
            $device['version'] = $version_matches[1];
        }
    }
}

// Enhanced OS discovery for Brocade Ironware switches (FCX and ICX)
if (in_array($os, ['foundry-fcx', 'brocade-icx', 'brocade-ironware'])) {
    $switch_name = $device['switch_series'] ?? 'Brocade';
    echo "$switch_name Switch detected. Performing enhanced discovery...\n";

    // Get basic system information
    $sysDescr = snmp_get($device, 'SNMPv2-MIB::sysDescr.0', '-Oqv');
    $sysObjectID = snmp_get($device, 'SNMPv2-MIB::sysObjectID.0', '-Oqv');

    // Get chassis information for stack detection
    $chassis_table = snmpwalk_cache_oid($device, 'snChasUnitTable', [], 'FOUNDRY-SN-ROOT');

    if (!empty($chassis_table)) {
        echo "Found chassis table with " . count($chassis_table) . " entries\n";

        // Determine if this is a stacked configuration
        $stack_members = [];
        $master_unit = null;

        foreach ($chassis_table as $index => $chassis) {
            $unit_name = $chassis['snChasUnitName'] ?? '';
            $unit_type = $chassis['snChasUnitType'] ?? '';
            $unit_status = $chassis['snChasUnitStatus'] ?? '';

            // Check if this unit is operational
            if ($unit_status == 1) { // operational
                $stack_members[] = [
                    'index' => $index,
                    'name' => $unit_name,
                    'type' => $unit_type,
                    'status' => $unit_status
                ];

                // Look for master unit indicators
                if (stripos($unit_name, 'master') !== false ||
                    stripos($unit_type, 'master') !== false) {
                    $master_unit = $index;
                }
            }
        }

        // Store stack information in device array
        $device['stack_members'] = $stack_members;
        $device['master_unit'] = $master_unit;
        $device['is_stacked'] = count($stack_members) > 1;

        echo "Stack configuration detected: " . count($stack_members) . " members\n";
        if ($master_unit) {
            echo "Master unit identified: $master_unit\n";
        }
    }

    // Get firmware version information
    $firmware_version = snmp_get($device, '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.6.1', '-Oqv');
    if ($firmware_version) {
        $device['firmware_version'] = $firmware_version;
        echo "Firmware version: $firmware_version\n";
    }

    // Get hardware model information
    $hardware_model = snmp_get($device, '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.5.1', '-Oqv');
    if ($hardware_model) {
        $device['hardware_model'] = $hardware_model;
        echo "Hardware model: $hardware_model\n";
    }

    // Get serial number
    $serial_number = snmp_get($device, '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.11.1', '-Oqv');
    if ($serial_number) {
        $device['serial_number'] = $serial_number;
        echo "Serial number: $serial_number\n";
    }

    // Enhanced stack discovery for complex configurations
    if ($device['is_stacked']) {
        // Get stack topology information
        $stack_topo = snmpwalk_cache_oid($device, 'snStackTopoTable', [], 'FOUNDRY-SN-ROOT');

        if (!empty($stack_topo)) {
            echo "Stack topology information available\n";
            $device['stack_topology'] = $stack_topo;

            // Analyze stack connections
            $stack_connections = [];
            foreach ($stack_topo as $connection) {
                if (isset($connection['snStackTopoLocalPort']) &&
                    isset($connection['snStackTopoRemoteUnit'])) {
                    $stack_connections[] = [
                        'local_port' => $connection['snStackTopoLocalPort'],
                        'remote_unit' => $connection['snStackTopoRemoteUnit'],
                        'remote_port' => $connection['snStackTopoRemotePort'] ?? null
                    ];
                }
            }

            $device['stack_connections'] = $stack_connections;
        }

        // Get stack priority information
        $stack_priority = snmpwalk_cache_oid($device, 'snStackPriorityTable', [], 'FOUNDRY-SN-ROOT');
        if (!empty($stack_priority)) {
            $device['stack_priority'] = $stack_priority;
        }
    }

    // Store enhanced discovery data for use by other modules
    $device['brocade_discovery_complete'] = true;

    // Log successful discovery
    $switch_name = $device['switch_series'] ?? 'Brocade';
    log_event("$switch_name enhanced discovery completed", $device, 'discovery', 1);
}

?>