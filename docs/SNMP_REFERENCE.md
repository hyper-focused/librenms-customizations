# SNMP Reference for Foundry FCX Switches

This document provides a comprehensive reference for SNMP OIDs and MIBs used for discovering and monitoring Foundry FCX switches in stacked configurations.

## Enterprise Information

- **Enterprise Name**: Foundry Networks (now Ruckus/CommScope)
- **Enterprise Number**: 1991
- **Base OID**: `.1.3.6.1.4.1.1991`

## Required MIB Files

### Standard MIBs
- **SNMPv2-MIB**: Standard SNMP MIB
- **IF-MIB**: Interface MIB (RFC 2863)
- **ENTITY-MIB**: Physical entity MIB (RFC 4133)
- **BRIDGE-MIB**: Bridge MIB for switching

### Foundry-Specific MIBs
- **FOUNDRY-SN-ROOT-MIB**: Root MIB definitions
- **FOUNDRY-SN-AGENT-MIB**: Agent and system information
- **FOUNDRY-SN-SWITCH-GROUP-MIB**: Switch-specific OIDs
- **FOUNDRY-SN-STACKING-MIB**: Stack-specific OIDs (if available)
- **FOUNDRY-SN-IP-MIB**: IP configuration
- **FOUNDRY-SN-MAC-ADDRESS-MIB**: MAC address tables

## System Identification OIDs

### Basic System Information

```
sysDescr.0                  .1.3.6.1.2.1.1.1.0
sysObjectID.0               .1.3.6.1.2.1.1.2.0
sysUpTime.0                 .1.3.6.1.2.1.1.3.0
sysContact.0                .1.3.6.1.2.1.1.4.0
sysName.0                   .1.3.6.1.2.1.1.5.0
sysLocation.0               .1.3.6.1.2.1.1.6.0
```

### Expected Values for FCX Switches

**sysDescr Example**:
```
"Foundry Networks, Inc. FCX648, IronWare Version 08.0.30 ..."
```

**sysObjectID Pattern**:
```
.1.3.6.1.4.1.1991.1.3.x.x
                    └─ Product family identifier
```

Common sysObjectID values:
- FCX624: `.1.3.6.1.4.1.1991.1.3.51.x`
- FCX648: `.1.3.6.1.4.1.1991.1.3.52.x`

## Foundry Agent MIB OIDs

### Software Version Information

```
snAgentBrdIndex             .1.3.6.1.4.1.1991.1.1.1.1.1.0
snAgentBrdMainBrdDescription .1.3.6.1.4.1.1991.1.1.1.1.2.0
snAgentBrdMainBrdId         .1.3.6.1.4.1.1991.1.1.1.1.3.0

snAgSoftwareVersion         .1.3.6.1.4.1.1991.1.1.2.1.1.0
snAgBootVersion             .1.3.6.1.4.1.1991.1.1.2.1.2.0
```

### System Serial Numbers

```
snChasUnitIndex             .1.3.6.1.4.1.1991.1.1.1.4.1.1.1
snChasUnitSerNum            .1.3.6.1.4.1.1991.1.1.1.4.1.1.2
snChasUnitDescription       .1.3.6.1.4.1.1991.1.1.1.4.1.1.3
```

## Stack-Specific OIDs

### Stack Configuration Table

**Base OID**: `.1.3.6.1.4.1.1991.1.1.3.31.2.1`

```
snStackingConfigUnitTable
├── snStackingConfigUnitIndex       .1
├── snStackingConfigUnitPriority    .2
├── snStackingConfigUnitConfigStackPort .3
├── snStackingConfigUnitRowStatus   .4
└── snStackingConfigUnitType        .5
```

### Stack Operational Table

**Base OID**: `.1.3.6.1.4.1.1991.1.1.3.31.3.1`

```
snStackingOperUnitTable
├── snStackingOperUnitIndex         .1
├── snStackingOperUnitRole          .2
│   ├── 1 = standalone
│   ├── 2 = member
│   └── 3 = master
├── snStackingOperUnitState         .3
│   ├── 1 = local
│   ├── 2 = remote
│   ├── 3 = reserved
│   ├── 4 = empty
│   └── 5 = unknown
├── snStackingOperUnitMac           .4
├── snStackingOperUnitPriority      .5
├── snStackingOperUnitDescription   .6
├── snStackingOperUnitStackPort1    .7
├── snStackingOperUnitStackPort2    .8
└── snStackingOperUnitImgVer        .9
```

### Stack Global Information

```
snStackingGlobalMacAddress          .1.3.6.1.4.1.1991.1.1.3.31.1.1.0
snStackingGlobalTopology            .1.3.6.1.4.1.1991.1.1.3.31.1.2.0
    1 = ring
    2 = chain
    3 = standalone
```

## Hardware Inventory OIDs

### Chassis Information

```
snChasUnitTable                     .1.3.6.1.4.1.1991.1.1.1.4.1.1
├── snChasUnitIndex                 .1
├── snChasUnitSerNum                .2
├── snChasUnitDescription           .3
└── snChasUnitState                 .4
```

### Power Supply Status

```
snChasPwrSupplyTable                .1.3.6.1.4.1.1991.1.1.1.1.2.1
├── snChasPwrSupplyIndex            .1
├── snChasPwrSupplyDescription      .2
└── snChasPwrSupplyOperStatus       .3
    1 = normal
    2 = failure
```

### Fan Status

```
snChasFanTable                      .1.3.6.1.4.1.1991.1.1.1.1.3.1
├── snChasFanIndex                  .1
├── snChasFanDescription            .2
└── snChasFanOperStatus             .3
    1 = normal
    2 = failure
```

### Temperature Sensors

```
snAgentTempTable                    .1.3.6.1.4.1.1991.1.1.2.13.1.1
├── snAgentTempUnitIndex            .1
├── snAgentTempSlotNum              .2
├── snAgentTempSensorId             .3
├── snAgentTempValue                .4
└── snAgentTempWarningThreshold     .5
```

## Interface and Port Information

### Standard IF-MIB

```
ifTable                             .1.3.6.1.2.1.2.2.1
├── ifIndex                         .1
├── ifDescr                         .2
├── ifType                          .3
├── ifMtu                           .4
├── ifSpeed                         .5
├── ifPhysAddress                   .6
├── ifAdminStatus                   .7
├── ifOperStatus                    .8
└── ...
```

### Extended Interface Information

```
ifXTable                            .1.3.6.1.2.1.31.1.1.1
├── ifName                          .1
├── ifHighSpeed                     .15
├── ifAlias                         .18
└── ...
```

### Stack Port Information

Stack ports typically show up in the ifTable with specific interface types or naming conventions:
- Interface names like "Stack1/1", "Stack1/2", etc.
- May have specific ifType values
- Monitor ifOperStatus for stack link health

## Entity MIB (Physical Inventory)

### Physical Entity Table

```
entPhysicalTable                    .1.3.6.1.2.1.47.1.1.1.1
├── entPhysicalIndex                .1
├── entPhysicalDescr                .2
├── entPhysicalVendorType           .3
├── entPhysicalContainedIn          .4
├── entPhysicalClass                .5
├── entPhysicalParentRelPos         .6
├── entPhysicalName                 .7
├── entPhysicalHardwareRev          .8
├── entPhysicalFirmwareRev          .9
├── entPhysicalSoftwareRev          .10
├── entPhysicalSerialNum            .11
├── entPhysicalMfgName              .12
└── entPhysicalModelName            .13
```

**entPhysicalClass Values**:
- 1 = other
- 2 = unknown
- 3 = chassis
- 4 = backplane
- 5 = container
- 6 = powerSupply
- 7 = fan
- 8 = sensor
- 9 = module
- 10 = port

## Example SNMP Queries

### Check if Device is Foundry

```bash
snmpget -v2c -c public device.example.com .1.3.6.1.2.1.1.2.0
# Should return OID starting with .1.3.6.1.4.1.1991
```

### Get System Description

```bash
snmpget -v2c -c public device.example.com .1.3.6.1.2.1.1.1.0
# Example: "Foundry Networks, Inc. FCX648, IronWare Version 08.0.30..."
```

### Walk Stack Operational Table

```bash
snmpwalk -v2c -c public device.example.com .1.3.6.1.4.1.1991.1.1.3.31.3.1
# Returns stack member information
```

### Get Stack Unit Roles

```bash
snmpwalk -v2c -c public device.example.com .1.3.6.1.4.1.1991.1.1.3.31.3.1.2
# Returns: 1=standalone, 2=member, 3=master
```

### Get Serial Numbers for All Stack Members

```bash
snmpwalk -v2c -c public device.example.com .1.3.6.1.4.1.1991.1.1.1.4.1.1.2
# Returns serial number for each unit
```

### Walk Physical Entity Table

```bash
snmpwalk -v2c -c public device.example.com .1.3.6.1.2.1.47.1.1.1
# Returns detailed physical inventory
```

## SNMP Data Collection Strategy

### Discovery Phase

1. **OS Detection**:
   - Get sysObjectID
   - Get sysDescr
   - Match against Foundry patterns

2. **Stack Detection**:
   - Query snStackingOperUnitTable
   - Check snStackingGlobalTopology
   - Count number of units

3. **Hardware Inventory**:
   - Walk snChasUnitTable
   - Walk entPhysicalTable
   - Collect serial numbers, models, versions

4. **Port Discovery**:
   - Walk ifTable
   - Identify stack ports
   - Map ports to units

### Polling Phase

1. **Stack Health**:
   - Monitor snStackingOperUnitState
   - Check stack port status (ifOperStatus)
   - Verify unit count consistency

2. **Hardware Health**:
   - Poll power supply status
   - Poll fan status
   - Poll temperature sensors

3. **Performance Metrics**:
   - Interface statistics
   - Stack port utilization
   - CPU and memory (if available)

## Common Stack Configurations

### Standalone Switch
- snStackingGlobalTopology = 3 (standalone)
- Single entry in snStackingOperUnitTable
- snStackingOperUnitRole = 1 (standalone)

### Two-Unit Stack
- snStackingGlobalTopology = 1 (ring) or 2 (chain)
- Two entries in snStackingOperUnitTable
- One unit with role = 3 (master)
- One unit with role = 2 (member)

### Full Ring Stack (8 units)
- snStackingGlobalTopology = 1 (ring)
- Eight entries in snStackingOperUnitTable
- All units connected in ring topology
- One master, seven members

### Degraded Stack
- One or more units with state = 4 (empty) or 5 (unknown)
- Indicates failed or removed stack member
- May show chain topology if ring is broken

## Troubleshooting

### Missing Stack OIDs

Some older firmware versions may not support stack MIBs. Fallback detection methods:
1. Parse sysDescr for stack indicators
2. Use entPhysicalTable to enumerate chassis
3. Check for multiple entries in snChasUnitTable

### SNMP Timeouts

Stack operations can be slow. Recommendations:
- Use SNMPv2c bulk operations when possible
- Increase timeout values for stack queries
- Query stack information less frequently than standard metrics

### Version Differences

Different IronWare versions may report data differently:
- Collect SNMP walks from multiple versions
- Implement version-specific parsing if needed
- Document known variations

## Testing Scenarios

### Test Data Needed

1. **Standalone FCX624** - Baseline single switch
2. **Standalone FCX648** - Different model
3. **2-unit stack** - Minimum stack configuration
4. **8-unit stack** - Large stack
5. **Mixed model stack** - FCX624 + FCX648
6. **Degraded stack** - Stack with failed member
7. **Different firmware versions** - Old and new IronWare

### SNMP Simulation

For each test scenario, capture:
```bash
snmpwalk -v2c -c public -ObentU device > fcx_scenario_X.snmpwalk
```

This data can be used with snmpsim for offline testing.

## References

- Foundry FastIron Command Reference
- Foundry SNMP Reference Guide
- LibreNMS OS Discovery Documentation
- SNMP RFCs: 3411-3418
- IF-MIB RFC 2863
- ENTITY-MIB RFC 4133

## Notes

- **Security**: Always use SNMPv3 with authentication/encryption in production
- **Community Strings**: Default is often "public" (read-only)
- **Access Control**: Configure SNMP ACLs on switches
- **Performance**: Bulk operations reduce query overhead
- **Reliability**: Implement retry logic for critical queries
