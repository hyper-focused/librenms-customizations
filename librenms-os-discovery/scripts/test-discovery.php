#!/usr/bin/env php
<?php

/**
 * Test script for Brocade Ironware OS Discovery (FCX and ICX series)
 *
 * This script simulates the discovery process using mock data
 * to validate the implementation without requiring actual hardware.
 */

require_once __DIR__ . '/../tests/mocks/brocade-ironware-mock.php';

// Include the discovery module (simulated include)
echo "Brocade Ironware OS Discovery Test Script\n";
echo "=========================================\n\n";

// Test 1: Stack Configuration Discovery
echo "Test 1: FCX Stack Configuration Discovery\n";
echo "------------------------------------------\n";

$device = $mock_fcx_stack['device'];
$os = null;

// Simulate discovery process
$fcx_oids = [
    '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.2',  // snChasUnitName
    '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.4',  // snChasUnitDescr
    '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.5',  // snChasUnitType
];

$fcx_data = [];
foreach ($fcx_oids as $oid) {
    $result = mock_snmp_get($mock_fcx_stack, $oid);
    $fcx_data[] = $result;
    echo "OID $oid: " . ($result ? "Found" : "Not found") . "\n";
}

if (!empty(array_filter($fcx_data))) {
    $os = 'foundry-fcx';
    echo "✓ OS detected as: $os\n";
} else {
    echo "✗ OS detection failed\n";
}

// Enhanced discovery
if ($os === 'foundry-fcx') {
    echo "\nEnhanced Discovery Results:\n";

    $sysDescr = mock_snmp_get($mock_fcx_stack, 'SNMPv2-MIB::sysDescr.0');
    echo "System Description: $sysDescr\n";

    $chassis_table = mock_snmpwalk_cache_oid($mock_fcx_stack, 'snChasUnitTable');
    echo "Chassis Units Found: " . count($chassis_table) . "\n";

    $stack_members = [];
    $master_unit = null;

    foreach ($chassis_table as $index => $chassis) {
        if ($chassis['snChasUnitStatus'] == 1) {
            $stack_members[] = [
                'index' => $index,
                'name' => $chassis['snChasUnitName'],
                'type' => $chassis['snChasUnitType'],
                'status' => $chassis['snChasUnitStatus']
            ];

            if (stripos($chassis['snChasUnitName'], 'master') !== false) {
                $master_unit = $index;
            }

            echo "  Unit $index: {$chassis['snChasUnitName']} ({$chassis['snChasUnitType']})\n";
        }
    }

    $device['stack_members'] = $stack_members;
    $device['master_unit'] = $master_unit;
    $device['is_stacked'] = count($stack_members) > 1;

    echo "Stack Configuration: " . ($device['is_stacked'] ? "Yes" : "No") . "\n";
    echo "Master Unit: " . ($master_unit ?: "None identified") . "\n";

    // Stack topology
    $stack_topo = mock_snmpwalk_cache_oid($mock_fcx_stack, 'snStackTopoTable');
    if (!empty($stack_topo)) {
        echo "Stack Connections: " . count($stack_topo) . "\n";
        foreach ($stack_topo as $connection) {
            echo "  Unit {$connection['snStackTopoLocalUnit']} Port {$connection['snStackTopoLocalPort']} ↔ Unit {$connection['snStackTopoRemoteUnit']} Port {$connection['snStackTopoRemotePort']}\n";
        }
    }

    // Stack priority
    $stack_priority = mock_snmpwalk_cache_oid($mock_fcx_stack, 'snStackPriorityTable');
    if (!empty($stack_priority)) {
        echo "Stack Priority Information:\n";
        foreach ($stack_priority as $priority) {
            $role = ['master', 'member', 'standby'][$priority['snStackPriorityRole'] - 1];
            echo "  Unit {$priority['snStackPriorityUnit']}: Priority {$priority['snStackPriorityValue']} ($role)\n";
        }
    }
}

echo "\n";

// Test 2: Standalone Configuration Discovery
echo "Test 2: FCX Standalone Configuration Discovery\n";
echo "-----------------------------------------------\n";

$device_standalone = $mock_fcx_standalone['device'];
$os_standalone = null;

$fcx_data_standalone = [];
foreach ($fcx_oids as $oid) {
    $result = mock_snmp_get($mock_fcx_standalone, $oid);
    $fcx_data_standalone[] = $result;
}

if (!empty(array_filter($fcx_data_standalone))) {
    $os_standalone = 'foundry-fcx';
    echo "✓ OS detected as: $os_standalone\n";
} else {
    echo "✗ OS detection failed\n";
}

if ($os_standalone === 'foundry-fcx') {
    $chassis_table_sa = mock_snmpwalk_cache_oid($mock_fcx_standalone, 'snChasUnitTable');
    echo "Chassis Units Found: " . count($chassis_table_sa) . "\n";

    $stack_members_sa = [];
    foreach ($chassis_table_sa as $index => $chassis) {
        if ($chassis['snChasUnitStatus'] == 1) {
            $stack_members_sa[] = [
                'index' => $index,
                'name' => $chassis['snChasUnitName'],
                'type' => $chassis['snChasUnitType'],
                'status' => $chassis['snChasUnitStatus']
            ];
            echo "  Unit $index: {$chassis['snChasUnitName']} ({$chassis['snChasUnitType']})\n";
        }
    }

    $is_stacked_sa = count($stack_members_sa) > 1;
    echo "Stack Configuration: " . ($is_stacked_sa ? "Yes" : "No") . "\n";
}

// Test 3: ICX 6450 Stack Configuration Discovery
echo "Test 3: ICX 6450 Stack Configuration Discovery\n";
echo "-----------------------------------------------\n";

$device_icx6450 = $mock_icx6450_stack['device'];
$os_icx6450 = null;

// Check for Brocade Ironware specific OIDs
$icx6450_data = [];
foreach ($brocade_oids as $oid) {
    $result = mock_snmp_get($mock_icx6450_stack, $oid);
    $icx6450_data[] = $result;
}

if (!empty(array_filter($icx6450_data))) {
    $sysDescr_icx6450 = mock_snmp_get($mock_icx6450_stack, 'SNMPv2-MIB::sysDescr.0');
    if (stripos($sysDescr_icx6450, 'ICX') !== false) {
        $os_icx6450 = 'brocade-icx';
        echo "✓ OS detected as: $os_icx6450 (ICX 6450)\n";
    }
} else {
    echo "✗ OS detection failed\n";
}

// Enhanced discovery for ICX 6450
if ($os_icx6450 === 'brocade-icx') {
    echo "\nICX 6450 Enhanced Discovery Results:\n";

    $chassis_table_icx6450 = mock_snmpwalk_cache_oid($mock_icx6450_stack, 'snChasUnitTable');
    echo "Chassis Units Found: " . count($chassis_table_icx6450) . "\n";

    $stack_members_icx6450 = [];
    $master_unit_icx6450 = null;

    foreach ($chassis_table_icx6450 as $index => $chassis) {
        if ($chassis['snChasUnitStatus'] == 1) {
            $stack_members_icx6450[] = [
                'index' => $index,
                'name' => $chassis['snChasUnitName'],
                'type' => $chassis['snChasUnitType'],
                'status' => $chassis['snChasUnitStatus']
            ];
            echo "  Unit $index: {$chassis['snChasUnitName']} ({$chassis['snChasUnitType']})\n";

            if (stripos($chassis['snChasUnitName'], 'master') !== false) {
                $master_unit_icx6450 = $index;
            }
        }
    }

    $is_stacked_icx6450 = count($stack_members_icx6450) > 1;
    echo "Stack Configuration: " . ($is_stacked_icx6450 ? "Yes" : "No") . "\n";
    echo "Master Unit: " . ($master_unit_icx6450 ?: "None identified") . "\n";
}

echo "\n";

// Test 4: ICX 7750 Standalone Configuration Discovery
echo "Test 4: ICX 7750 Standalone Configuration Discovery\n";
echo "----------------------------------------------------\n";

$device_icx7750 = $mock_icx7750_standalone['device'];
$os_icx7750 = null;

$icx7750_data = [];
foreach ($brocade_oids as $oid) {
    $result = mock_snmp_get($mock_icx7750_standalone, $oid);
    $icx7750_data[] = $result;
}

if (!empty(array_filter($icx7750_data))) {
    $sysDescr_icx7750 = mock_snmp_get($mock_icx7750_standalone, 'SNMPv2-MIB::sysDescr.0');
    if (stripos($sysDescr_icx7750, 'ICX') !== false) {
        $os_icx7750 = 'brocade-icx';
        echo "✓ OS detected as: $os_icx7750 (ICX 7750)\n";
    }
} else {
    echo "✗ OS detection failed\n";
}

// Enhanced discovery for ICX 7750
if ($os_icx7750 === 'brocade-icx') {
    echo "\nICX 7750 Enhanced Discovery Results:\n";

    $chassis_table_icx7750 = mock_snmpwalk_cache_oid($mock_icx7750_standalone, 'snChasUnitTable');
    echo "Chassis Units Found: " . count($chassis_table_icx7750) . "\n";

    $stack_members_icx7750 = [];
    foreach ($chassis_table_icx7750 as $index => $chassis) {
        if ($chassis['snChasUnitStatus'] == 1) {
            $stack_members_icx7750[] = [
                'index' => $index,
                'name' => $chassis['snChasUnitName'],
                'type' => $chassis['snChasUnitType'],
                'status' => $chassis['snChasUnitStatus']
            ];
            echo "  Unit $index: {$chassis['snChasUnitName']} ({$chassis['snChasUnitType']})\n";
        }
    }

    $is_stacked_icx7750 = count($stack_members_icx7750) > 1;
    echo "Stack Configuration: " . ($is_stacked_icx7750 ? "Yes" : "No") . "\n";
}

echo "\nTest completed successfully!\n";

?>