<?php

/**
 * Debug script for testing stack detection on real Foundry/Brocade switches
 *
 * This script tests various hypotheses about why stack MIBs don't work on stacked switches.
 *
 * Run with: php debug_stack_detection.php <ip> <community>
 */

require_once 'vendor/autoload.php';

if ($argc < 3) {
    echo "Usage: php debug_stack_detection.php <ip> <community>\n";
    exit(1);
}

$ip = $argv[1];
$community = $argv[2];

echo "=== Testing Stack Detection on {$ip} ===\n\n";

// Hypotheses to test:
$hypotheses = [
    'H1' => 'Basic device identification',
    'H2' => 'Stack config state OID exists',
    'H3' => 'Stack topology OID exists',
    'H4' => 'Stack MAC OID exists',
    'H5' => 'Operational stack table exists',
    'H6' => 'Configuration stack table exists',
    'H7' => 'Alternative stack OIDs work',
    'H8' => 'SNMP community has stack access'
];

echo "Testing Hypotheses:\n";
foreach ($hypotheses as $id => $desc) {
    echo "  {$id}: {$desc}\n";
}
echo "\n";

// Test basic connectivity and device info
echo "H1: Basic device identification\n";
$sysDescr = snmpget($ip, $community, '.1.3.6.1.2.1.1.1.0');
$sysObjectID = snmpget($ip, $community, '.1.3.6.1.2.1.1.2.0');

echo "  sysDescr: " . ($sysDescr ?: 'FAILED') . "\n";
echo "  sysObjectID: " . ($sysObjectID ?: 'FAILED') . "\n";

$isStackCapable = strpos($sysDescr, 'Stacking System') !== false;
echo "  Stack-capable (sysDescr): " . ($isStackCapable ? 'YES' : 'NO') . "\n\n";

// Test stack state OID
echo "H2: Stack config state OID exists\n";
$stackState = snmpget($ip, $community, '.1.3.6.1.4.1.1991.1.1.3.31.1.1.0');
echo "  snStackingGlobalConfigState.0: " . ($stackState ?: 'FAILED') . "\n\n";

// Test stack topology OID
echo "H3: Stack topology OID exists\n";
$topology = snmpget($ip, $community, '.1.3.6.1.4.1.1991.1.1.3.31.1.2.0');
echo "  snStackingGlobalTopology.0: " . ($topology ?: 'FAILED') . "\n\n";

// Test stack MAC OID
echo "H4: Stack MAC OID exists\n";
$stackMac = snmpget($ip, $community, '.1.3.6.1.4.1.1991.1.1.3.31.1.1.0');
echo "  snStackingGlobalMacAddress.0: " . ($stackMac ?: 'FAILED') . "\n\n";

// Test operational stack table
echo "H5: Operational stack table exists\n";
$operTable = snmpwalk($ip, $community, '.1.3.6.1.4.1.1991.1.1.3.31.3.1');
echo "  snStackingOperUnitTable entries: " . count($operTable) . "\n";
if (count($operTable) > 0) {
    echo "  Sample entries:\n";
    $count = 0;
    foreach ($operTable as $oid => $value) {
        if ($count++ >= 5) break;
        echo "    {$oid} = {$value}\n";
    }
}
echo "\n";

// Test configuration stack table
echo "H6: Configuration stack table exists\n";
$configTable = snmpwalk($ip, $community, '.1.3.6.1.4.1.1991.1.1.3.31.2.1');
echo "  snStackingConfigUnitTable entries: " . count($configTable) . "\n";
if (count($configTable) > 0) {
    echo "  Sample entries:\n";
    $count = 0;
    foreach ($configTable as $oid => $value) {
        if ($count++ >= 5) break;
        echo "    {$oid} = {$value}\n";
    }
}
echo "\n";

// Test alternative OIDs
echo "H7: Alternative stack OIDs\n";
$alternativeOIDs = [
    'snStackMemberCount' => '.1.3.6.1.4.1.1991.1.1.2.1.1.0',
    'snStackPortCount' => '.1.3.6.1.4.1.1991.1.1.2.1.3.0',
    'Brocade stack info' => '.1.3.6.1.4.1.1588.2.1.1.1',
];

foreach ($alternativeOIDs as $name => $oid) {
    $result = snmpget($ip, $community, $oid);
    echo "  {$name} ({$oid}): " . ($result ?: 'FAILED') . "\n";
}
echo "\n";

// Test with different community strings (if provided)
echo "H8: SNMP community access test\n";
$communities = [$community, 'public', 'private'];
foreach ($communities as $testCommunity) {
    $testResult = snmpget($ip, $testCommunity, '.1.3.6.1.4.1.1991.1.1.3.31.1.1.0');
    echo "  Community '{$testCommunity}': " . ($testResult ? 'SUCCESS' : 'FAILED') . "\n";
}

echo "\n=== Analysis ===\n";

if ($stackState && $stackState != 'FAILED') {
    echo "✓ Stack config state OID works\n";
} else {
    echo "✗ Stack config state OID fails - Hypothesis H2 REJECTED\n";
}

if ($topology && $topology != 'FAILED') {
    echo "✓ Stack topology OID works\n";
} else {
    echo "✗ Stack topology OID fails - Hypothesis H3 REJECTED\n";
}

if (count($operTable) > 0) {
    echo "✓ Operational stack table works (found " . count($operTable) . " entries)\n";
} else {
    echo "✗ Operational stack table fails - Hypothesis H5 REJECTED\n";
}

if (count($configTable) > 0) {
    echo "✓ Configuration stack table works (found " . count($configTable) . " entries)\n";
} else {
    echo "✗ Configuration stack table fails - Hypothesis H6 REJECTED\n";
}

echo "\n=== Conclusions ===\n";
echo "If all stack OIDs fail on a stacked switch, possible causes:\n";
echo "1. Wrong MIB implementation in firmware\n";
echo "2. SNMP community lacks stack MIB access\n";
echo "3. Stack must be in specific state to expose MIBs\n";
echo "4. OIDs are different than expected\n";
echo "5. Device requires special SNMP configuration\n";

?>