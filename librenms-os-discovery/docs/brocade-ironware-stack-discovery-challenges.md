# Brocade Ironware Stack Discovery Challenges and Solutions

## Overview

Brocade Ironware switches (including FCX and ICX series) present unique challenges when deployed in stacked configurations. This document outlines the common issues encountered during network monitoring discovery and the solutions implemented in this project.

## Supported Platforms

This project supports the following Brocade/Ruckus Ironware platforms:

### FCX Series
- FCX648S
- FCX624S
- Other FCX models

### ICX Series
- ICX 6450 (stackable)
- ICX 7150 (stackable)
- ICX 7250 (stackable)
- ICX 7450 (stackable)
- ICX 7750 (stackable)

All platforms share the same Ironware operating system and use similar MIB structures for stack management.

## Challenges

### 1. Stack Member Identification

**Problem**: Traditional OS discovery methods often fail to properly identify individual stack members, treating the entire stack as a single device or failing to discover members altogether.

**Symptoms**:
- Only the master unit appears in LibreNMS
- Member switches are not discovered
- Inconsistent device naming and identification

**Root Cause**: FCX stacks use a distributed management architecture where the master unit responds to SNMP queries, but member information is stored in chassis tables that require special handling.

### 2. MIB Compatibility Issues

**Problem**: FCX switches support multiple MIB versions (Foundry, Brocade, and generic standards), but LibreNMS may not have the correct MIB files or polling configurations.

**Symptoms**:
- SNMP walks return incomplete data
- Missing sensor information (fans, power supplies)
- Stack topology information unavailable

**Root Cause**: FCX switches transitioned from Foundry Networks to Brocade/Ruckus ownership, resulting in MIB fragmentation and version conflicts.

### 3. Stack Topology Detection

**Problem**: Understanding the physical and logical connections between stack members is crucial for proper monitoring, but this information is often not collected or misinterpreted.

**Symptoms**:
- Unknown stack connections
- Incorrect topology mapping
- Failure to detect stack link failures

**Root Cause**: Stack topology information is stored in specialized MIB tables that require specific polling configurations.

### 4. Firmware Version Consistency

**Problem**: In stacked configurations, firmware versions should be consistent across all members, but discovery may only report the master's version.

**Symptoms**:
- Missing firmware information for member units
- Inability to detect version mismatches
- Firmware upgrade monitoring limitations

**Root Cause**: Firmware version information is stored per-unit in chassis tables rather than globally.

### 5. Dynamic Stack Membership

**Problem**: Stack membership can change due to hardware failures, configuration changes, or maintenance activities, but discovery processes may not adapt.

**Symptoms**:
- Stale stack member information
- Incorrect master/standby identification
- Monitoring gaps during topology changes

**Root Cause**: Discovery is typically performed at fixed intervals without awareness of stack membership changes.

## Solutions Implemented

### Enhanced OS Discovery Module

The `foundry-fcx.inc.php` module implements the following improvements:

1. **Multi-OID Detection**: Uses multiple chassis table OIDs to reliably identify FCX devices
2. **Stack-Aware Discovery**: Detects stack configurations and identifies master/member roles
3. **Comprehensive Data Collection**: Gathers firmware, hardware, and serial number information for all units
4. **Topology Mapping**: Collects and analyzes stack connection information

### Optimized Device Definition

The `foundry-fcx.yaml` definition file includes:

1. **Proper MIB Registration**: Registers all necessary Foundry/Brocade MIBs
2. **Module Configuration**: Enables appropriate discovery and polling modules
3. **Sensor Definitions**: Configures fan and power supply monitoring
4. **Interface Filtering**: Properly handles FCX interface naming conventions

### Testing Framework

Comprehensive testing with mock data validates:

1. **Stack vs Standalone Detection**: Ensures correct identification of deployment types
2. **Member Discovery**: Validates that all stack members are properly enumerated
3. **Data Accuracy**: Confirms that collected information matches expected values

## Implementation Details

### Stack Discovery Algorithm

```php
// 1. Detect FCX OS using multiple OIDs
$fcx_data = check_multiple_oids($fcx_oids);

// 2. If FCX detected, perform enhanced discovery
if ($os === 'foundry-fcx') {
    // 3. Get chassis table for all units
    $chassis_table = snmpwalk_cache_oid($device, 'snChasUnitTable');

    // 4. Identify operational units
    foreach ($chassis_table as $unit) {
        if ($unit['status'] === 'operational') {
            $stack_members[] = $unit;
        }
    }

    // 5. Determine stack configuration
    $device['is_stacked'] = count($stack_members) > 1;

    // 6. Identify master unit
    $device['master_unit'] = find_master_unit($stack_members);
}
```

### MIB Usage

The implementation leverages these key MIBs:

- **FOUNDRY-SN-ROOT**: Core chassis and system information
- **FOUNDRY-SN-STACKING-MIB**: Stack topology and priority information
- **FOUNDRY-SN-SWITCH-GROUP-MIB**: Switch-specific monitoring data
- **FOUNDRY-SN-AGENT-MIB**: Agent and management information

## Configuration Recommendations

### SNMP Configuration

Ensure FCX switches are configured with:

```
snmp-server community public ro
snmp-server contact "Network Operations <noc@example.com>"
snmp-server location "Data Center - Rack A"
```

### Stack Configuration Best Practices

1. **Consistent Firmware**: Ensure all stack members run identical firmware versions
2. **Priority Settings**: Configure explicit stack priorities to avoid master election issues
3. **Redundant Links**: Use multiple stack ports for redundancy
4. **Management IP**: Assign management IP to stack (not individual units)

### LibreNMS Integration

1. **MIB Installation**: Copy provided MIB files to `/opt/librenms/mibs/`
2. **Module Files**: Install discovery and definition files in appropriate directories
3. **Cache Update**: Run `php includes/discovery/functions.inc.php` to rebuild MIB cache
4. **Service Restart**: Restart LibreNMS services to load new configurations

## Troubleshooting

### Common Issues

#### Stack Members Not Appearing

**Symptoms**: Only master unit visible in LibreNMS

**Solutions**:
1. Verify SNMP community string access to all units
2. Check stack link status using `show stack connections`
3. Ensure consistent firmware versions
4. Review discovery logs for SNMP timeout errors

#### Incomplete Sensor Data

**Symptoms**: Missing fan or power supply information

**Solutions**:
1. Verify MIB files are installed and compiled
2. Check SNMP polling permissions
3. Review device definition for correct OID mappings
4. Test manual SNMP walks for sensor OIDs

#### Topology Mapping Issues

**Symptoms**: Incorrect or missing stack connection information

**Solutions**:
1. Verify stacking MIB is loaded
2. Check stack port configurations
3. Review stack topology table manually
4. Ensure stack links are operational

### Debug Commands

```bash
# Test SNMP connectivity
snmpwalk -v2c -c public <device_ip> SNMPv2-MIB::sysDescr.0

# Check chassis table
snmpwalk -v2c -c public <device_ip> .1.3.6.1.4.1.1991.1.3.49.1.1.1

# Verify stack topology
snmpwalk -v2c -c public <device_ip> snStackTopoTable

# Check stack priority
snmpwalk -v2c -c public <device_ip> snStackPriorityTable
```

## Future Enhancements

1. **Dynamic Stack Monitoring**: Real-time stack membership change detection
2. **Stack Health Metrics**: Additional KPIs for stack performance monitoring
3. **Automated Firmware Checks**: Version consistency validation
4. **Topology Visualization**: Integration with LibreNMS network maps
5. **HA Stack Support**: Enhanced support for high-availability configurations

## References

- Foundry Networks MIB Reference
- Brocade FCX Configuration Guide
- LibreNMS Device Discovery Documentation
- RFC 1213 (MIB-II) and RFC 2863 (IF-MIB)