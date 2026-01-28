# Platform Differences: FastIron (FCX) vs ICX

Reference for the unified FastIron + ICX module. Describes differences between FCX and ICX platforms (both handled by the same OS and codebase).

## Overview

Both platforms share common heritage and SNMP MIB structures, but have evolved separately with distinct characteristics.

## Platform History

```
Foundry Networks (1996-2008)
    ├─ FCX Series → IronWare OS
    └─ Acquired by Brocade (2008)
        └─ ICX Series → FastIron OS (rebranded IronWare)
            └─ Acquired by Ruckus/CommScope (2016)
                └─ ICX Series continues under Ruckus branding
```

## Hardware Platforms

### Foundry FCX Series (Legacy)

| Model | Ports | PoE | Stack | Status |
|-------|-------|-----|-------|--------|
| FCX624 | 24 | Optional | Yes (8 units) | End of Life |
| FCX648 | 48 | Optional | Yes (8 units) | End of Life |

**Characteristics**:
- Fixed configuration switches
- 1G access ports
- 10G uplink options
- IronWare OS
- End of support (legacy deployments only)

### Brocade/Ruckus ICX Series (Current)

#### ICX 6450 Series (Entry Campus)
| Model | Ports | PoE | Stack | Notes |
|-------|-------|-----|-------|-------|
| ICX6450-24 | 24 | No | 8 units | Basic L3 |
| ICX6450-24-HPOE | 24 | Yes | 8 units | High PoE |
| ICX6450-48 | 48 | No | 8 units | Basic L3 |
| ICX6450-48-HPOE | 48 | Yes | 8 units | High PoE |

#### ICX 7150 Series (Mainstream Campus)
| Model | Ports | PoE | Stack | Notes |
|-------|-------|-----|-------|-------|
| ICX7150-24 | 24 | No | 12 units | Full L3 |
| ICX7150-24P | 24 | Yes | 12 units | PoE+ |
| ICX7150-48 | 48 | No | 12 units | Full L3 |
| ICX7150-48P | 48 | Yes | 12 units | PoE+ |
| ICX7150-48ZP | 48 | Yes | 12 units | PoE++, 90W |

#### ICX 7250 Series (Advanced Campus)
| Model | Ports | PoE | Stack | Notes |
|-------|-------|-----|-------|-------|
| ICX7250-24 | 24 | Optional | 12 units | 10G uplinks |
| ICX7250-48 | 48 | Optional | 12 units | 10G uplinks |

#### ICX 7450 Series (Aggregation)
| Model | Ports | PoE | Stack | Notes |
|-------|-------|-----|-------|-------|
| ICX7450-24 | 24 | No | 12 units | 40G uplinks |
| ICX7450-48 | 48 | No | 12 units | 40G uplinks |
| ICX7450-48F | 48 | No | 12 units | All fiber |

#### ICX 7650 Series (Data Center)
| Model | Ports | PoE | Stack | Notes |
|-------|-------|-----|-------|-------|
| ICX7650-48F | 48 | No | 12 units | 10G, 40G, 100G |

#### ICX 7750 Series (Modular/High-End)
| Model | Type | Ports | Stack | Notes |
|-------|------|-------|-------|-------|
| ICX7750-26Q | Fixed | 26 | 12 units | 40G/100G |
| ICX7750-48C | Fixed | 48 | 12 units | 10G copper |
| ICX7750-48F | Fixed | 48 | 12 units | 10G fiber |

## Software Differences

### Operating System

| Aspect | Foundry FCX | Brocade/Ruckus ICX |
|--------|-------------|-------------------|
| OS Name | IronWare | FastIron (rebranded IronWare) |
| Version Format | IronWare Version 08.0.30 | FastIron Version 08.0.95 or 09.0.10 |
| Latest Version | 08.0.30 (EOL) | 09.0.x (current) |
| CLI | IronWare CLI | Compatible FastIron CLI |

### Version Numbering

**FCX (IronWare)**:
- Format: `08.0.30a` (Major.Minor.Patch + Letter)
- Typical range: 07.x - 08.0.x
- No longer updated

**ICX (FastIron)**:
- Format: `08.0.95` or `09.0.10` (Major.Minor.Patch)
- Ranges:
  - 08.0.x: Current stable
  - 09.0.x: Latest features
- Regular updates and security patches

## SNMP Differences

### Enterprise OIDs

| Platform | Enterprise Number | Base OID | Usage |
|----------|------------------|----------|-------|
| FCX | 1991 (Foundry) | `.1.3.6.1.4.1.1991` | Primary |
| ICX | 1588 (Brocade) | `.1.3.6.1.4.1.1588` | Primary |
| ICX | 1991 (Foundry) | `.1.3.6.1.4.1.1991` | Compatibility |

**Note**: ICX switches respond to both OIDs for backward compatibility.

### sysObjectID Patterns

**FCX Series**:
```
.1.3.6.1.4.1.1991.1.3.51.x    # FCX624
.1.3.6.1.4.1.1991.1.3.52.x    # FCX648
```

**ICX Series**:
```
.1.3.6.1.4.1.1588.2.1.1.1.3.7    # ICX6450-24
.1.3.6.1.4.1.1588.2.1.1.1.3.8    # ICX6450-48
.1.3.6.1.4.1.1588.2.1.1.1.3.30   # ICX7150-24
.1.3.6.1.4.1.1588.2.1.1.1.3.31   # ICX7150-48
.1.3.6.1.4.1.1588.2.1.1.1.3.32   # ICX7250-24
.1.3.6.1.4.1.1588.2.1.1.1.3.33   # ICX7250-48
.1.3.6.1.4.1.1588.2.1.1.1.3.40-42 # ICX7750 series
```

### sysDescr Patterns

**FCX**:
```
"Foundry Networks, Inc. FCX648, IronWare Version 08.0.30..."
```

**ICX (Brocade branding)**:
```
"Brocade ICX7150-48 Switch, IronWare Version 08.0.40..."
```

**ICX (Ruckus branding)**:
```
"Ruckus ICX 7150-48P Switch, FastIron Version 08.0.95..."
"ICX7750-48F Router, FastIron Version 09.0.10..."
```

### MIB Compatibility

| MIB | FCX Support | ICX Support | Notes |
|-----|-------------|-------------|-------|
| FOUNDRY-SN-ROOT-MIB | Yes | Yes | Backward compatible |
| FOUNDRY-SN-AGENT-MIB | Yes | Yes | Common to both |
| FOUNDRY-SN-SWITCH-GROUP-MIB | Yes | Yes | Stacking MIB |
| BROCADE-REG-MIB | No | Yes | ICX-specific |
| BROCADE-PRODUCTS-MIB | No | Yes | ICX product IDs |
| BROCADE-ENTITY-MIB | No | Yes | Enhanced entity info |
| BROCADE-STACKABLE-MIB | No | Partial | May use Foundry stack MIB |

## Stacking Differences

### Stack Capacity

| Platform | Maximum Units | Bandwidth | Redundancy |
|----------|--------------|-----------|------------|
| FCX624/648 | 8 | 80 Gbps | Dual links |
| ICX6450 | 8 | 40 Gbps | Dual links |
| ICX7150 | 12 | 84 Gbps | Dual links |
| ICX7250 | 12 | 84 Gbps | Dual links |
| ICX7450 | 12 | 480 Gbps | Quad links |
| ICX7650 | 12 | 480 Gbps | Quad links |
| ICX7750 | 12 | 960 Gbps | Multiple options |

### Stack Module Types

**FCX**:
- Proprietary stacking modules
- 2 stack ports per unit
- Auto-negotiation

**ICX**:
- Varies by series:
  - ICX6450/7150: Built-in stack ports
  - ICX7450+: Optional high-speed stack modules
  - ICX7750: Multiple stacking options (40G, 100G)

### Stack Configuration

Both platforms use the same SNMP OIDs for stack management:
- `.1.3.6.1.4.1.1991.1.1.3.31.*` (FOUNDRY-SN-SWITCH-GROUP-MIB)

However, ICX may provide additional information through BROCADE-specific MIBs.

## Feature Differences

### Layer 3 Routing

| Feature | FCX | ICX 6450 | ICX 7150+ |
|---------|-----|----------|-----------|
| Static Routes | Yes | Yes | Yes |
| RIP | Yes | Yes | Yes |
| OSPF | Yes | Limited | Full |
| BGP | Limited | No | Yes |
| VRRP | Yes | Yes | Yes |
| PIM | Limited | No | Yes |

### Monitoring Capabilities

**FCX**:
- Basic SNMP monitoring
- Limited sFlow support
- Standard RMON

**ICX**:
- Enhanced SNMP monitoring
- Full sFlow support
- Extended RMON
- Additional Brocade MIBs
- Better entity-physical data
- Enhanced diagnostics

### Management

**Both Platforms**:
- CLI (similar syntax)
- SNMP v1/v2c/v3
- SSH/Telnet
- HTTP/HTTPS web interface

**ICX Additional**:
- REST API (newer models)
- Enhanced RADIUS/TACACS+
- Better NETCONF support
- Cloud management options (Ruckus Cloud)

## Detection and Identification Strategy

### Step 1: Determine Platform Family

Check sysObjectID:
- Starts with `.1.3.6.1.4.1.1991.1.3.5x` → FCX Series
- Starts with `.1.3.6.1.4.1.1588.2.1.1.1.3.x` → ICX Series

### Step 2: Extract Model Information

Parse sysDescr for model pattern:
- `FCX\d{3}` → FCX model (e.g., FCX648)
- `ICX\d{4}` → ICX model (e.g., ICX7150)

### Step 3: Identify OS Version

Parse sysDescr for OS version:
- "IronWare Version X.Y.Z" → IronWare
- "FastIron Version X.Y.Z" → FastIron

### Step 4: Platform-Specific Handling

Based on platform:
- **FCX**: Use Foundry MIBs only
- **ICX**: Try Brocade MIBs first, fall back to Foundry MIBs

## Implementation Recommendations

### OS Definitions

Create separate OS definitions:
1. **foundry** - For FCX series
2. **icx** or **brocade-icx** - For ICX series

Rationale:
- Different enterprise OIDs
- Different feature sets
- Different support status
- Clearer differentiation for users

### Discovery Logic

Unified discovery module that:
1. Detects platform (FCX vs ICX)
2. Uses appropriate MIBs
3. Handles platform-specific features
4. Maintains common stack structure

### Database Schema

Use platform-agnostic schema:
- `ironware_stacks` table (not `foundry_stacks`)
- Add `platform` column: 'fcx' or 'icx'
- Allows future expansion

### Testing

Test across platforms:
- All FCX models (if available)
- Representative ICX models from each series
- Various firmware versions
- Mixed stack scenarios (if supported)

## Migration Considerations

### Upgrading from FCX to ICX

Organizations migrating from FCX to ICX:
1. MIB compatibility maintained
2. Stacking behavior similar
3. CLI commands mostly compatible
4. Configuration migration supported

LibreNMS should:
1. Handle both platforms seamlessly
2. Detect platform change on rediscovery
3. Update device classification
4. Preserve historical data

## Future Considerations

### ICX Evolution

Current trends:
- Ruckus branding (replacing Brocade)
- FastIron OS advancement
- Cloud management integration
- API expansion

Implementation should:
- Be flexible for future changes
- Support multiple vendor strings
- Handle version evolution
- Accommodate new models

## Summary

| Aspect | FCX | ICX |
|--------|-----|-----|
| Status | EOL | Current |
| OS | IronWare | FastIron |
| Enterprise OID | 1991 | 1588 (1991 compat) |
| Stack Size | 8 | 8-12 (model dependent) |
| MIB Compatibility | Foundry only | Foundry + Brocade |
| Feature Set | Basic-Intermediate | Intermediate-Advanced |
| Support | None | Active |
| Deployment | Legacy only | Active deployment |

**Recommendation**: Implement support for both platforms with unified architecture but platform-specific optimizations where beneficial.
