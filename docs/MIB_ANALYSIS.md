# MIB Analysis for IronWare/FastIron Switches

This document tracks analysis of device-specific MIB files for accurate OS detection and stack discovery.

## Available MIB Files

Based on firmware version 08.0.30u, we have device-specific MIBs for:

### Foundry FCX Series
- **FCXR08030u.mib** - FCX Router MIB
- **FCXS08030u.mib** - FCX Switch MIB

### Foundry FSX Series
- **SXLR08030u.mib** - FSX Router MIB (FastIron SX?)
- **SXLS08030u.mib** - FSX Switch MIB

### Brocade/Ruckus ICX 6400 Series
- **ICX64R08030u.mib** - ICX6400 Router MIB
- **ICX64S08030u.mib** - ICX6400 Switch MIB

### Brocade/Ruckus ICX 6650 Series
- **ICXR08030u.mib** - ICX6650 Router MIB
- **ICXS08030u.mib** - ICX6650 Switch MIB

### Brocade/Ruckus ICX 7250 Series
- **SPR08030u.mib** - ICX7250 Router MIB
- **SPS08030u.mib** - ICX7250 Switch MIB

### Brocade/Ruckus ICX 7750 Series
- **SWR08030u.mib** - ICX7750 Router MIB
- **SWS08030u.mib** - ICX7750 Switch MIB

## MIB Naming Convention

Pattern: `[PREFIX][R|S][VERSION].mib`
- **Prefix**: Platform identifier (FCX, ICX, SP, SW, etc.)
- **R**: Router MIB (Layer 3 features)
- **S**: Switch MIB (Layer 2 features)
- **Version**: Firmware version (08030u = 08.0.30u)

## Key Information to Extract

For each MIB file, we need to extract:

### 1. Product Identification
```
-- Look for these in each MIB:
snSwitch OBJECT IDENTIFIER ::= { foundry 1 }
snRouter OBJECT IDENTIFIER ::= { foundry 2 }

-- Product registration section:
-- Example structure:
products OBJECT IDENTIFIER ::= { foundry 1 }
registration OBJECT IDENTIFIER ::= { products 1 }

-- Specific model OIDs:
fcx624     OBJECT IDENTIFIER ::= { registration X }
fcx648     OBJECT IDENTIFIER ::= { registration Y }
icx6450    OBJECT IDENTIFIER ::= { registration Z }
```

### 2. sysObjectID Values

**ACTUAL VALUES FROM REAL DEVICES** (Firmware 08.0.30u):
```
FCX648:     .1.3.6.1.4.1.1991.1.3.48.2.1  ✅ VERIFIED
ICX6450-48: .1.3.6.1.4.1.1991.1.3.48.5.1  ✅ VERIFIED
```

**PATTERN DISCOVERED**: `.1.3.6.1.4.1.1991.1.3.48.X.Y`
- Both FCX and ICX6450 use Foundry OID (1991)
- Family identifier: 48
- Series: 2 (FCX648), 5 (ICX6450-48)
- Variant: 1

**TO BE VERIFIED**:
```
FCX624:    .1.3.6.1.4.1.1991.1.3.48.1.? (hypothesized)
ICX6430:   .1.3.6.1.4.1.1991.1.3.48.3.? (hypothesized)
ICX6610:   .1.3.6.1.4.1.1991.1.3.48.4.? (hypothesized)
ICX6650:   .1.3.6.1.4.1.1991.1.3.48.6.? (hypothesized)
ICX7150:   .1.3.6.1.4.1.1588.2.1.1.1.3.? (may use Brocade OID)
ICX7250:   .1.3.6.1.4.1.1588.2.1.1.1.3.? (may use Brocade OID)
ICX7450:   .1.3.6.1.4.1.1588.2.1.1.1.3.? (may use Brocade OID)
ICX7750:   .1.3.6.1.4.1.1588.2.1.1.1.3.? (may use Brocade OID)
```

**IMPORTANT FINDING**: 
- Firmware 08.0.30u uses Foundry OID (1991) for BOTH FCX and ICX
- Newer firmware may use Brocade OID (1588)
- Detection must check BOTH enterprise OIDs

### 3. Stack-Related OIDs
```
-- Verify these OIDs exist in each platform MIB:
snStackingGlobalObjects ::= { snStacking 1 }
snStackingConfigUnit    ::= { snStacking 2 }
snStackingOperUnit      ::= { snStacking 3 }

-- Key stack OIDs to verify:
snStackingGlobalTopology      OBJECT-TYPE
snStackingGlobalMacAddress    OBJECT-TYPE
snStackingConfigUnitTable     OBJECT-TYPE
snStackingOperUnitTable       OBJECT-TYPE
snStackingOperUnitRole        OBJECT-TYPE
snStackingOperUnitState       OBJECT-TYPE
```

### 4. Hardware Information OIDs
```
-- Chassis and hardware OIDs:
snChasUnitTable               OBJECT-TYPE
snChasUnitIndex               OBJECT-TYPE
snChasUnitSerNum              OBJECT-TYPE
snChasUnitDescription         OBJECT-TYPE

-- Agent/Software info:
snAgSoftwareVersion           OBJECT-TYPE
snAgBootVersion               OBJECT-TYPE
snAgentBrdMainBrdDescription  OBJECT-TYPE
```

### 5. Model-Specific Capabilities
```
-- Port counts, PoE support, stack capabilities
-- Maximum stack size
-- Stack bandwidth
-- Supported features
```

## MIB Extraction Commands

To extract key information from MIBs:

```bash
# Get all OID definitions
snmptranslate -M +./mibs -m +ALL -Tp -IR

# Find product registration
grep -A 5 "registration\|products" *.mib

# Find sysObjectID assignments
grep -E "::=\s*\{\s*(registration|products)" *.mib

# Find stack-related definitions
grep -B 2 -A 10 "snStacking" *.mib

# Get OID numeric values
snmptranslate -M +./mibs -m +ALL -On SNMPv2-SMI::enterprises.foundry
```

## Questions for MIB Analysis

### For each platform, we need to determine:

1. **Exact sysObjectID values** for each model variant
2. **Stack support**: Which models support stacking?
3. **Maximum stack size**: 8 or 12 units?
4. **MIB compatibility**: Do all platforms use FOUNDRY-SN-SWITCH-GROUP-MIB for stacking?
5. **Brocade-specific MIBs**: Do ICX switches have additional Brocade MIBs?
6. **Enterprise OID**: Do ICX MIBs define both 1991 and 1588 OIDs?

## Platform-Specific Analysis

### FCX Series Analysis

**Models**: FCX624, FCX648

**Questions**:
- [ ] What are the exact sysObjectID values?
- [ ] Confirm maximum stack size (8 units)
- [ ] Verify stack MIB structure
- [ ] Check for FCX-specific OIDs

**Expected Findings**:
```
Enterprise: 1991 (Foundry)
Base OID: .1.3.6.1.4.1.1991.1.3.5x
Stack support: Yes, up to 8 units
OS: IronWare
```

### ICX 6400/6450 Series Analysis

**Models**: ICX6400, ICX6430, ICX6450, ICX6610

**Questions**:
- [ ] Exact sysObjectID for each model
- [ ] Maximum stack size
- [ ] Enterprise OID (1588 or 1991 or both?)
- [ ] Differences from FCX stack implementation

**Expected Findings**:
```
Enterprise: 1588 (Brocade) primary, 1991 (Foundry) compatible
Base OID: .1.3.6.1.4.1.1588.2.1.1.1.3.x
Stack support: Yes, up to 8 units (model dependent)
OS: FastIron (or IronWare in older firmware)
```

### ICX 6650 Series Analysis

**Models**: ICX6610, ICX6650

**Questions**:
- [ ] sysObjectID values
- [ ] Stack capabilities
- [ ] Differences from 6450 series

### ICX 7250 Series Analysis

**Models**: ICX7250-24, ICX7250-48, ICX7250-48F

**Questions**:
- [ ] sysObjectID for each variant
- [ ] Maximum stack size (12 units?)
- [ ] Enhanced stack features vs 6450
- [ ] 10G uplink OIDs

**Expected Findings**:
```
Enterprise: 1588 (Brocade)
Stack support: Yes, up to 12 units
Stack bandwidth: 84 Gbps
OS: FastIron
```

### ICX 7750 Series Analysis

**Models**: ICX7750-26Q, ICX7750-48C, ICX7750-48F

**Questions**:
- [ ] sysObjectID for each model
- [ ] Stackable vs modular chassis detection
- [ ] Maximum stack size (12 units)
- [ ] High-speed stack module OIDs

**Expected Findings**:
```
Enterprise: 1588 (Brocade)
Stack support: Yes, up to 12 units (stackable models)
Stack bandwidth: 960 Gbps
OS: FastIron
```

## Next Steps

1. **Extract sysObjectID mappings** from each MIB
2. **Verify stack OID compatibility** across platforms
3. **Document platform differences** in stack implementation
4. **Create detection logic** based on exact OIDs
5. **Build comprehensive test data** with real OID values

## MIB Storage

Store extracted MIB information in:
- `/workspace/mibs/foundry/` - FCX, FSX MIBs
- `/workspace/mibs/brocade/` - ICX MIBs
- Document source and version for each MIB file

## Test Data Generation

For each platform, create test SNMP walk data:
```json
{
  "platform": "ICX7250-48",
  "firmware": "08.0.30u",
  "sysObjectID": ".1.3.6.1.4.1.1588.2.1.1.1.3.XX",
  "sysDescr": "Brocade ICX7250-48 Switch...",
  "stack_oids": {
    "snStackingGlobalTopology.0": "1",
    "snStackingOperUnitRole.1": "3",
    ...
  }
}
```

## Reference Documentation

Based on MIB analysis, update:
- `SNMP_REFERENCE.md` - Add exact OID values
- `PLATFORM_DIFFERENCES.md` - Note MIB differences
- Detection logic in OS discovery modules
