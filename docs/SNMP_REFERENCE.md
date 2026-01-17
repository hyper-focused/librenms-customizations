# SNMP Reference for IronWare/FastIron Switches

This document provides a comprehensive reference for SNMP OIDs and MIBs used for discovering and monitoring IronWare/FastIron-based switches (Foundry FCX and Brocade/Ruckus ICX series) in stacked configurations.

## Enterprise Information

### Foundry Networks (FCX Series)
- **Enterprise Name**: Foundry Networks (now Ruckus/CommScope)
- **Enterprise Number**: 1991
- **Base OID**: `.1.3.6.1.4.1.1991`
- **Operating System**: IronWare

### Brocade/Ruckus (ICX Series)
- **Enterprise Name**: Brocade Communications (now Ruckus/CommScope)
- **Enterprise Number**: 1588
- **Base OID**: `.1.3.6.1.4.1.1588`
- **Alternative OID**: May also use `.1.3.6.1.4.1.1991` for backward compatibility
- **Operating System**: FastIron

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

### Expected Values for IronWare/FastIron Switches

**sysDescr Examples**:

Foundry FCX:
```
"Foundry Networks, Inc. FCX648, IronWare Version 08.0.30 ..."
```

Brocade ICX:
```
"Brocade ICX7150-48 Switch, IronWare Version 08.0.40 ..."
"Ruckus ICX 7150-48P Switch, FastIron Version 08.0.95 ..."
"ICX7750-48F Router, FastIron Version 09.0.10 ..."
```

**sysObjectID Patterns**:

Foundry (Enterprise 1991):
```
.1.3.6.1.4.1.1991.1.3.x.x
                    └─ Product family identifier
```

Brocade (Enterprise 1588):
```
.1.3.6.1.4.1.1588.2.1.1.1.x.x
                        └─ Product family identifier
```

Common sysObjectID values:
- **FCX624**: `.1.3.6.1.4.1.1991.1.3.51.x`
- **FCX648**: `.1.3.6.1.4.1.1991.1.3.52.x`
- **ICX6450-24**: `.1.3.6.1.4.1.1588.2.1.1.1.3.7`
- **ICX6450-48**: `.1.3.6.1.4.1.1588.2.1.1.1.3.8`
- **ICX7150-24**: `.1.3.6.1.4.1.1588.2.1.1.1.3.30`
- **ICX7150-48**: `.1.3.6.1.4.1.1588.2.1.1.1.3.31`
- **ICX7250-24**: `.1.3.6.1.4.1.1588.2.1.1.1.3.32`
- **ICX7250-48**: `.1.3.6.1.4.1.1588.2.1.1.1.3.33`
- **ICX7450-24**: `.1.3.6.1.4.1.1588.2.1.1.1.3.24`
- **ICX7450-48**: `.1.3.6.1.4.1.1588.2.1.1.1.3.25`
- **ICX7650**: `.1.3.6.1.4.1.1588.2.1.1.1.3.45`
- **ICX7750-26Q**: `.1.3.6.1.4.1.1588.2.1.1.1.3.40`
- **ICX7750-48F**: `.1.3.6.1.4.1.1588.2.1.1.1.3.41`

## Agent MIB OIDs (Common to Both Platforms)

### Software Version Information

Both Foundry and Brocade/Ruckus ICX use the same FOUNDRY-SN-AGENT-MIB OIDs:

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

**Note**: ICX switches use these same OIDs for backward compatibility, even though their enterprise OID is 1588.

## Stack-Specific OIDs (Common Across Platforms)

Both Foundry FCX and Brocade/Ruckus ICX use the same stacking MIB structure from FOUNDRY-SN-SWITCH-GROUP-MIB.

### Stack Configuration Table

**Base OID**: `.1.3.6.1.4.1.1991.1.1.3.31.2.1`
**Applies to**: Foundry FCX, Brocade ICX, Ruckus ICX

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
**Applies to**: Foundry FCX, Brocade ICX, Ruckus ICX

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

### Standalone Switch (FCX and ICX)
- snStackingGlobalTopology = 3 (standalone)
- Single entry in snStackingOperUnitTable
- snStackingOperUnitRole = 1 (standalone)

### Two-Unit Stack (FCX and ICX)
- snStackingGlobalTopology = 1 (ring) or 2 (chain)
- Two entries in snStackingOperUnitTable
- One unit with role = 3 (master)
- One unit with role = 2 (member)

### Full Ring Stack
- **FCX**: Typically up to 8 units
- **ICX 6450/7150**: Up to 12 units
- **ICX 7450/7750**: Up to 12 units
- snStackingGlobalTopology = 1 (ring)
- All units connected in ring topology
- One master, remaining members

### Degraded Stack
- One or more units with state = 4 (empty) or 5 (unknown)
- Indicates failed or removed stack member
- May show chain topology if ring is broken
- Behavior consistent across FCX and ICX platforms

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

#### Foundry FCX Series
1. **Standalone FCX624** - Baseline single switch
2. **Standalone FCX648** - Different model
3. **2-unit FCX stack** - Minimum stack configuration
4. **8-unit FCX stack** - Large stack
5. **Mixed FCX stack** - FCX624 + FCX648
6. **Degraded FCX stack** - Stack with failed member
7. **Different IronWare versions** - Old and new firmware

#### Brocade/Ruckus ICX Series
1. **Standalone ICX6450-24** - Campus access switch
2. **Standalone ICX7150-48** - Common campus switch
3. **2-unit ICX7150 stack** - Minimum stack
4. **8-unit ICX7150 stack** - Medium stack
5. **12-unit ICX7450 stack** - Maximum stack
6. **Mixed ICX stack** - ICX7150 + ICX7250 (if supported)
7. **Degraded ICX stack** - Stack with failed member
8. **ICX7750 stack** - High-end model
9. **Different FastIron versions** - Various firmware versions

### SNMP Simulation

For each test scenario, capture:
```bash
snmpwalk -v2c -c public -ObentU device > fcx_scenario_X.snmpwalk
```

This data can be used with snmpsim for offline testing.

## Platform-Specific Differences

### Foundry FCX vs Brocade/Ruckus ICX

#### Similarities
- Both use FOUNDRY-SN-AGENT-MIB for system information
- Both use FOUNDRY-SN-SWITCH-GROUP-MIB for stacking
- Same stack OID structure
- Compatible stacking behavior

#### Differences

**Enterprise OID**:
- FCX: `.1.3.6.1.4.1.1991` (Foundry)
- ICX: `.1.3.6.1.4.1.1588` (Brocade), but may also respond to 1991

**sysDescr Format**:
- FCX: "Foundry Networks, Inc. FCX648, IronWare Version..."
- ICX: "Ruckus ICX 7150-48P Switch, FastIron Version..." or "Brocade..."

**OS Version String**:
- FCX: "IronWare"
- ICX: "FastIron" (newer) or "IronWare" (older firmware)

**Maximum Stack Size**:
- FCX624/648: Typically 8 units
- ICX6450: Up to 8 units
- ICX7150/7250/7450/7750: Up to 12 units

**Additional ICX Features**:
- Enhanced stacking bandwidth
- More detailed entity-physical data
- Additional monitoring OIDs in BROCADE-ENTITY-MIB

### Detection Strategy

1. Check sysObjectID first:
   - Starts with `.1.3.6.1.4.1.1991.1.3.51-52.*` → Foundry FCX
   - Starts with `.1.3.6.1.4.1.1588.2.1.1.1.3.*` → Brocade/Ruckus ICX
   
2. Parse sysDescr:
   - Contains "Foundry" → FCX platform
   - Contains "Brocade" or "Ruckus" → ICX platform
   - Contains "ICX" → ICX platform
   - Contains "FCX" → FCX platform

3. Extract model number:
   - Pattern: FCX\d{3} → Foundry
   - Pattern: ICX\d{4} → Brocade/Ruckus

4. Determine OS version format:
   - "IronWare Version X.Y.Z" → Older platform or FCX
   - "FastIron Version X.Y.Z" → ICX platform

## ICX Series Model Identification

### ICX 6450 Series (Campus Access)
- **Models**: ICX6450-24, ICX6450-48
- **OID Pattern**: `.1.3.6.1.4.1.1588.2.1.1.1.3.7-8`
- **Features**: Basic Layer 3, PoE options
- **Stack Size**: Up to 8 units

### ICX 7150 Series (Stackable Campus)
- **Models**: ICX7150-24, ICX7150-48, ICX7150-24P, ICX7150-48P
- **OID Pattern**: `.1.3.6.1.4.1.1588.2.1.1.1.3.30-31`
- **Features**: Full Layer 3, PoE+, advanced routing
- **Stack Size**: Up to 12 units

### ICX 7250 Series (Advanced Campus)
- **Models**: ICX7250-24, ICX7250-48
- **OID Pattern**: `.1.3.6.1.4.1.1588.2.1.1.1.3.32-33`
- **Features**: 10G uplinks, advanced Layer 3
- **Stack Size**: Up to 12 units

### ICX 7450 Series (Aggregation)
- **Models**: ICX7450-24, ICX7450-48
- **OID Pattern**: `.1.3.6.1.4.1.1588.2.1.1.1.3.24-25`
- **Features**: 10G/40G, advanced routing, high performance
- **Stack Size**: Up to 12 units

### ICX 7650 Series (Data Center)
- **Models**: ICX7650-48F
- **OID Pattern**: `.1.3.6.1.4.1.1588.2.1.1.1.3.45`
- **Features**: 10G/40G/100G, data center optimized
- **Stack Size**: Up to 12 units

### ICX 7750 Series (Modular/High-End)
- **Models**: ICX7750-26Q, ICX7750-48C, ICX7750-48F
- **OID Pattern**: `.1.3.6.1.4.1.1588.2.1.1.1.3.40-42`
- **Features**: Modular chassis or stackable, 40G/100G
- **Stack Size**: Up to 12 units (stackable models)

## References

### Foundry Resources
- Foundry FastIron Command Reference
- Foundry SNMP Reference Guide
- IronWare documentation

### Brocade Resources
- Brocade FastIron Command Reference
- Brocade MIB Reference Guide
- ICX Configuration Guides

### Ruckus Resources
- Ruckus ICX Switch documentation
- Ruckus FastIron Release Notes
- Ruckus SNMP Configuration Guide

### Standards
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
- **Compatibility**: ICX switches maintain backward compatibility with Foundry MIBs
- **Firmware**: Newer ICX firmware uses "FastIron" branding, older versions may show "IronWare"
