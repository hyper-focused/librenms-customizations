<?php
/**
 * Brocade Stack Discovery Include
 *
 * Additional discovery logic for Brocade/Ruckus stack switches
 * Called from sensors discovery module
 *
 * Handles PoE sensor discovery which cannot be done in YAML due to
 * complex port linking and index resolution requirements.
 */

use LibreNMS\Util\SnmpQuery;

echo "Brocade Stack discovery include loaded\n";

// ============================================================================
// PoE Unit Sensors - Device Overview Page
// ============================================================================

echo "DEBUG: Starting PoE unit sensor discovery\n";

// Walk individual columns of the PoE unit table
// Base OID: .1.3.6.1.4.1.1991.1.1.2.14.4.1.1 = snAgentPoeUnitEntry
// Column .2 = snAgentPoeUnitMaxPower (capacity in milliwatts)
// Column .3 = snAgentPoeUnitConsumedPower (consumption in milliwatts)

$maxPower = SnmpQuery::numeric()->walk('.1.3.6.1.4.1.1991.1.1.2.14.4.1.1.2')->values();
$consumedPower = SnmpQuery::numeric()->walk('.1.3.6.1.4.1.1991.1.1.2.14.4.1.1.3')->values();

echo "DEBUG: Unit SNMP walks complete - maxPower: " . count($maxPower) . ", consumed: " . count($consumedPower) . "\n";

if (!empty($maxPower) || !empty($consumedPower)) {
    // Get all unit indices from either dataset
    $allOids = array_unique(array_merge(array_keys($maxPower), array_keys($consumedPower)));

    foreach ($allOids as $oid) {
        // Extract index from OID (last component after final dot)
        $index = (int) substr($oid, strrpos($oid, '.') + 1);
        $unitNum = $index;

        // PoE Unit Capacity (snAgentPoeUnitMaxPower)
        $capacity = $maxPower[$oid] ?? null;
        if ($capacity !== null && is_numeric($capacity) && $capacity > 0) {
            $capacityOid = '.1.3.6.1.4.1.1991.1.1.2.14.4.1.1.2.' . $index;

            discover_sensor(
                $valid['sensor'],
                'power',
                $device,
                $capacityOid,
                "poe-unit-capacity-{$index}",
                'brocade-poe',
                "Unit {$unitNum} PoE Capacity",
                1000,  // divisor: mW to W
                1,     // multiplier
                0,     // low_limit
                null,  // low_warn_limit
                null,  // warn_limit
                null,  // high_limit
                $capacity,
                'snmp', // poller_type
                null,   // entPhysicalIndex
                null,   // entPhysicalIndex_measured
                null,   // user_func
                'PoE Power Budget' // group
            );

            echo "DEBUG: Added unit {$unitNum} capacity sensor: {$capacity}mW\n";
        }

        // PoE Unit Consumption (snAgentPoeUnitConsumedPower)
        $consumed = $consumedPower[$oid] ?? null;
        if ($consumed !== null && is_numeric($consumed)) {
            $consumedOid = '.1.3.6.1.4.1.1991.1.1.2.14.4.1.1.3.' . $index;

            discover_sensor(
                $valid['sensor'],
                'power',
                $device,
                $consumedOid,
                "poe-unit-consumption-{$index}",
                'brocade-poe',
                "Unit {$unitNum} PoE Consumption",
                1000,  // divisor: mW to W
                1,     // multiplier
                0,     // low_limit
                null,  // low_warn_limit
                null,  // warn_limit
                null,  // high_limit
                $consumed,
                'snmp', // poller_type
                null,   // entPhysicalIndex
                null,   // entPhysicalIndex_measured
                null,   // user_func
                'PoE Power Budget' // group
            );

            echo "DEBUG: Added unit {$unitNum} consumption sensor: {$consumed}mW\n";
        }
    }
} else {
    echo "DEBUG: No unit PoE data - non-PoE hardware\n";
}

// ============================================================================
// PoE Port Sensors - Port Pages
// ============================================================================

echo "DEBUG: Starting PoE port sensor discovery\n";

// Walk individual columns of the PoE port table
// Base OID: .1.3.6.1.4.1.1991.1.1.2.14.2.2 = snAgentPoePortTable
// Column .1 = snAgentPoePortIndex (port identifier)
// Column .2 = snAgentPoePortControl (port status)
// Column .3 = snAgentPoePortWattage (allocated limit in milliwatts)
// Column .6 = snAgentPoePortConsumed (current consumption in milliwatts)

$poePortIndex = SnmpQuery::numeric()->walk('.1.3.6.1.4.1.1991.1.1.2.14.2.2.1.1')->values();
$poeStatus = SnmpQuery::numeric()->walk('.1.3.6.1.4.1.1991.1.1.2.14.2.2.1.2')->values();
$poeWattage = SnmpQuery::numeric()->walk('.1.3.6.1.4.1.1991.1.1.2.14.2.2.1.3')->values();
$poeConsumed = SnmpQuery::numeric()->walk('.1.3.6.1.4.1.1991.1.1.2.14.2.2.1.6')->values();

echo "DEBUG: Port SNMP walks complete - portIndex: " . count($poePortIndex) . ", status: " . count($poeStatus) . ", wattage: " . count($poeWattage) . ", consumed: " . count($poeConsumed) . "\n";

if (!empty($poePortIndex) && (!empty($poeStatus) || !empty($poeWattage) || !empty($poeConsumed))) {
    // Get all ports from LibreNMS database to map port indices
    $ports = $device->ports()->get();
    $portMap = [];

    foreach ($ports as $port) {
        // Map by ifIndex - Brocade uses ifIndex for port table indices
        $portMap[$port->ifIndex] = $port;
    }

    echo "DEBUG: Mapped " . count($portMap) . " ports by ifIndex\n";

    // Iterate over port indices from column .1 (snAgentPoePortIndex)
    $sensorCount = 0;
    foreach ($poePortIndex as $oid => $portNum) {
        // Extract ifIndex from OID
        $index = (int) substr($oid, strrpos($oid, '.') + 1);

        // Check if we have a matching port in LibreNMS
        if (!isset($portMap[$index])) {
            continue;
        }

        $port = $portMap[$index];
        $portLabel = $port->ifDescr ?: "Port {$index}";

        // Build OID keys for this index
        $statusOid = '.1.3.6.1.4.1.1991.1.1.2.14.2.2.1.2.' . $index;
        $wattageOid = '.1.3.6.1.4.1.1991.1.1.2.14.2.2.1.3.' . $index;
        $consumedOid = '.1.3.6.1.4.1.1991.1.1.2.14.2.2.1.6.' . $index;

        // Check port PoE status (snAgentPoePortControl)
        // 1=notCapable, 2=disabled, 3=enabled, 4=legacyEnabled
        $status = $poeStatus[$statusOid] ?? 1;

        // Skip non-PoE capable ports
        if ($status == 1) {
            continue;
        }

        // PoE Port Allocated Limit (snAgentPoePortWattage)
        $wattage = $poeWattage[$wattageOid] ?? null;
        if ($wattage !== null && is_numeric($wattage) && $wattage > 0) {
            discover_sensor(
                $valid['sensor'],
                'power',
                $device,
                $wattageOid,
                "poe.{$portNum}.limit",
                'brocade-poe',
                "{$portLabel} PoE Limit",
                1000, // divisor (mW to W)
                1,    // multiplier
                0,    // low_limit
                null, // low_warn_limit
                null, // warn_limit
                null, // high_limit
                $wattage,
                'snmp',  // poller_type
                $index,  // entPhysicalIndex - port ifIndex for port page linking
                'ports', // entPhysicalIndex_measured - links sensor to port page
                null,    // user_func
                null     // group
            );

            $sensorCount++;
        }

        // PoE Port Current Consumption (snAgentPoePortConsumed)
        $consumed = $poeConsumed[$consumedOid] ?? null;
        if ($consumed !== null && is_numeric($consumed)) {
            discover_sensor(
                $valid['sensor'],
                'power',
                $device,
                $consumedOid,
                "poe.{$portNum}.consumption",
                'brocade-poe',
                "{$portLabel} PoE Consumption",
                1000, // divisor (mW to W)
                1,    // multiplier
                0,    // low_limit
                null, // low_warn_limit
                null, // warn_limit
                null, // high_limit
                $consumed,
                'snmp',  // poller_type
                $index,  // entPhysicalIndex - port ifIndex for port page linking
                'ports', // entPhysicalIndex_measured - links sensor to port page
                null,    // user_func
                null     // group
            );

            $sensorCount++;
        }
    }

    echo "DEBUG: Added {$sensorCount} port PoE sensors\n";
} else {
    echo "DEBUG: No port PoE data - non-PoE hardware or mixed stack\n";
}

echo "DEBUG: Brocade Stack PoE sensor discovery complete\n";
