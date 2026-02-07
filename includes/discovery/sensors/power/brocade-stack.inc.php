<?php
/**
 * Brocade Stack PoE Power Sensor Discovery
 *
 * Discovers PoE power sensors for Brocade/Ruckus stackable switches.
 * Located at includes/discovery/sensors/power/ so LibreNMS auto-loads it
 * during the power sensor discovery phase.
 *
 * Unit-level sensors: PoE Capacity + Consumption per stack unit (device overview)
 * Port-level sensors: PoE Limit + Consumption per port (linked to port pages)
 *
 * Uses FOUNDRY-POE-MIB (.1.3.6.1.4.1.1991.1.1.2.14)
 * YAML cannot handle these sensors due to index resolution issues with
 * numerical OIDs when MIBs are loaded, and complex port linking requirements.
 */

// Quick PoE capability check — avoid unnecessary SNMP walks on non-PoE devices
$poeCapabilityCheck = \SnmpQuery::numeric()->get(['.1.3.6.1.4.1.1991.1.1.2.14.4.1.1.2.1'])->value();
if (! is_numeric($poeCapabilityCheck) || $poeCapabilityCheck <= 0) {
    return;
}

// ============================================================================
// PoE Unit Sensors - Device Overview (PoE Power Budget)
// ============================================================================
// snAgentPoeUnitEntry (.1.3.6.1.4.1.1991.1.1.2.14.4.1.1)
//   .2 = snAgentPoeUnitMaxPower (total capacity in milliwatts)
//   .3 = snAgentPoeUnitConsumedPower (remaining/available capacity in milliwatts)
//       Note: Despite the MIB name, .3 returns AVAILABLE power, not consumed.
//       Actual consumption = .2 (capacity) - .3 (available)

$maxPower = \SnmpQuery::numeric()->walk('.1.3.6.1.4.1.1991.1.1.2.14.4.1.1.2')->values();
$availablePower = \SnmpQuery::numeric()->walk('.1.3.6.1.4.1.1991.1.1.2.14.4.1.1.3')->values();

if (! empty($maxPower)) {
    foreach ($maxPower as $oid => $capacity) {
        $index = (int) substr($oid, strrpos($oid, '.') + 1);
        $unitNum = $index;

        // Unit PoE Capacity (total budget)
        if (is_numeric($capacity) && $capacity > 0) {
            discover_sensor(
                null,
                'power',
                $device,
                '.1.3.6.1.4.1.1991.1.1.2.14.4.1.1.2.' . $index,
                "poe-unit-capacity-{$index}",
                'brocade-poe',
                "Unit {$unitNum} PoE Capacity",
                1000,   // divisor: mW to W
                1,      // multiplier
                0,      // low_limit
                null,   // low_warn_limit
                null,   // warn_limit
                null,   // high_limit
                $capacity / 1000, // current value in W
                'snmp',
                null,   // entPhysicalIndex (no port link)
                null,   // entPhysicalIndex_measured
                null,   // user_func
                'PoE Power Budget'
            );
        }

        // Unit PoE Available (remaining budget — used to calculate consumption)
        $availOid = '.1.3.6.1.4.1.1991.1.1.2.14.4.1.1.3.' . $index;
        $available = $availablePower[$availOid] ?? null;
        if ($available !== null && is_numeric($available)) {
            discover_sensor(
                null,
                'power',
                $device,
                $availOid,
                "poe-unit-available-{$index}",
                'brocade-poe',
                "Unit {$unitNum} PoE Available",
                1000,   // divisor: mW to W
                1,      // multiplier
                0,      // low_limit
                null,   // low_warn_limit
                null,   // warn_limit
                null,   // high_limit
                $available / 1000, // current value in W
                'snmp',
                null,   // entPhysicalIndex (no port link)
                null,   // entPhysicalIndex_measured
                null,   // user_func
                'PoE Power Budget'
            );
        }
    }
}

// ============================================================================
// PoE Port Sensors - Linked to Port Pages
// ============================================================================
// snAgentPoePortTable (.1.3.6.1.4.1.1991.1.1.2.14.2.2)
//   .1.1 = snAgentPoePortIndex (port identifier)
//   .1.2 = snAgentPoePortControl (1=notCapable, 2=disabled, 3=enabled, 4=legacyEnabled)
//   .1.3 = snAgentPoePortWattage (allocated limit in milliwatts)
//   .1.6 = snAgentPoePortConsumed (current consumption in milliwatts)

$poePortIndex = \SnmpQuery::numeric()->walk('.1.3.6.1.4.1.1991.1.1.2.14.2.2.1.1')->values();
$poeStatus = \SnmpQuery::numeric()->walk('.1.3.6.1.4.1.1991.1.1.2.14.2.2.1.2')->values();
$poeWattage = \SnmpQuery::numeric()->walk('.1.3.6.1.4.1.1991.1.1.2.14.2.2.1.3')->values();
$poeConsumed = \SnmpQuery::numeric()->walk('.1.3.6.1.4.1.1991.1.1.2.14.2.2.1.6')->values();

if (! empty($poePortIndex) && (! empty($poeStatus) || ! empty($poeWattage) || ! empty($poeConsumed))) {
    // Build port map from DB (keyed by ifIndex for fast lookup)
    $portMap = [];
    $ports_db = \App\Models\Port::where('device_id', $device['device_id'])
        ->get(['port_id', 'ifIndex', 'ifDescr', 'ifName']);
    foreach ($ports_db as $port) {
        $portMap[$port->ifIndex] = $port;
    }

    foreach ($poePortIndex as $oid => $portNum) {
        $index = (int) substr($oid, strrpos($oid, '.') + 1);

        // Skip ports not in LibreNMS
        if (! isset($portMap[$index])) {
            continue;
        }

        $port = $portMap[$index];
        $portLabel = $port->ifDescr ?: "Port {$index}";

        // Check PoE status — skip non-capable ports (1 = notCapable)
        $statusOid = '.1.3.6.1.4.1.1991.1.1.2.14.2.2.1.2.' . $index;
        $status = $poeStatus[$statusOid] ?? 1;
        if ($status == 1) {
            continue;
        }

        // Skip ports with no PoE power allocated (PoE not enabled on port)
        $wattageOid = '.1.3.6.1.4.1.1991.1.1.2.14.2.2.1.3.' . $index;
        $wattage = $poeWattage[$wattageOid] ?? null;
        if (! is_numeric($wattage) || $wattage <= 0) {
            continue;
        }

        // Port PoE Allocated Limit
        discover_sensor(
            null,
            'power',
            $device,
            $wattageOid,
            "poe.{$portNum}.limit",
            'brocade-poe',
            "{$portLabel} PoE Limit",
            1000,    // divisor (mW to W)
            1,       // multiplier
            0,       // low_limit
            null, null, null,
            $wattage / 1000, // current value in W
            'snmp',
            $index,  // entPhysicalIndex = port ifIndex
            'ports', // entPhysicalIndex_measured — links to port page
            null,    // user_func
            'PoE Port Power'
        );

        // Port PoE Current Consumption
        $consumedOid = '.1.3.6.1.4.1.1991.1.1.2.14.2.2.1.6.' . $index;
        $consumed = $poeConsumed[$consumedOid] ?? null;
        if ($consumed !== null && is_numeric($consumed)) {
            discover_sensor(
                null,
                'power',
                $device,
                $consumedOid,
                "poe.{$portNum}.consumption",
                'brocade-poe',
                "{$portLabel} PoE Consumption",
                1000,    // divisor (mW to W)
                1,       // multiplier
                0,       // low_limit
                null, null, null,
                $consumed / 1000, // current value in W
                'snmp',
                $index,  // entPhysicalIndex = port ifIndex
                'ports', // entPhysicalIndex_measured — links to port page
                null,    // user_func
                'PoE Port Power'
            );
        }
    }
}
