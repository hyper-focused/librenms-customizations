# Real Device SNMP Data

This document contains actual SNMP data collected from real Foundry/Brocade switches.

## Data Collection

All data collected via SNMP v2c queries.

---

## FCX648 (Stacked System)

### System Information
```
sysDescr (.1.3.6.1.2.1.1.1.0):
"Brocade Communications Systems, Inc. Stacking System FCX648, IronWare Version 08.0.30uT7f1 Compiled on Apr 23 2020 at 12:11:06 labeled as FCXS08030u"

sysObjectID (.1.3.6.1.2.1.1.2.0):
.1.3.6.1.4.1.1991.1.3.48.2.1
```

### Analysis
- **Vendor String**: "Brocade Communications Systems, Inc." (Brocade branding)
- **Configuration**: "Stacking System" (indicates stack capable/configured)
- **Model**: FCX648
- **OS**: IronWare
- **Version**: 08.0.30uT7f1
- **Build Date**: Apr 23 2020
- **MIB Label**: FCXS08030u (matches Switch MIB file provided)
- **Enterprise OID**: 1991 (Foundry)
- **Product OID Pattern**: 1991.1.3.48.2.1
  - Base: .1.3.6.1.4.1.1991.1.3
  - Family: 48
  - Series: 2 (FCX?)
  - Variant: 1

### Detection Patterns
```regex
sysDescr patterns:
- "Brocade Communications Systems"
- "Stacking System"
- "FCX648"
- "IronWare Version"

sysObjectID pattern:
- .1.3.6.1.4.1.1991.1.3.48.2.1
```

---

## ICX6450-48 (Stacked System)

### System Information
```
sysDescr (.1.3.6.1.2.1.1.1.0):
"Brocade Communications Systems, Inc. Stacking System ICX6450-48, IronWare Version 08.0.30uT311 Compiled on Apr 23 2020 at 10:57:26 labeled as ICX64S08030u"

sysObjectID (.1.3.6.1.2.1.1.2.0):
.1.3.6.1.4.1.1991.1.3.48.5.1
```

### Analysis
- **Vendor String**: "Brocade Communications Systems, Inc."
- **Configuration**: "Stacking System"
- **Model**: ICX6450-48
- **OS**: IronWare
- **Version**: 08.0.30uT311
- **Build Date**: Apr 23 2020
- **MIB Label**: ICX64S08030u (matches Switch MIB file provided)
- **Enterprise OID**: 1991 (Foundry) - NOT 1588!
- **Product OID Pattern**: 1991.1.3.48.5.1
  - Base: .1.3.6.1.4.1.1991.1.3
  - Family: 48
  - Series: 5 (ICX6450?)
  - Variant: 1

### Detection Patterns
```regex
sysDescr patterns:
- "Brocade Communications Systems"
- "Stacking System"
- "ICX6450-48"
- "IronWare Version"

sysObjectID pattern:
- .1.3.6.1.4.1.1991.1.3.48.5.1
```

---

## Key Findings

### 1. Enterprise OID Usage
**IMPORTANT**: Both FCX648 and ICX6450-48 use enterprise OID **1991** (Foundry), NOT 1588 (Brocade).

This contradicts some documentation that suggests ICX uses 1588. It appears:
- Firmware 08.0.30u uses Foundry OID (1991) for both FCX and ICX
- Newer firmware may use Brocade OID (1588) for ICX
- Need to check for BOTH OIDs in detection logic

### 2. OID Structure Pattern
```
.1.3.6.1.4.1.1991.1.3.48.X.Y
                        └─ Series identifier
                           └─ Variant/submodel
```

**Observed mappings**:
- `48.2.1` = FCX648
- `48.5.1` = ICX6450-48

**Hypothesis for series identifier**:
- `48.1.x` = FCX624?
- `48.2.x` = FCX648
- `48.3.x` = ICX6430?
- `48.4.x` = ICX6610?
- `48.5.x` = ICX6450
- `48.6.x` = ICX6650?
- Other values for ICX7xxx series?

### 3. sysDescr Format
```
"Brocade Communications Systems, Inc. Stacking System [MODEL], IronWare Version [VERSION] Compiled on [DATE] at [TIME] labeled as [MIB]"
```

**Components**:
- Vendor: "Brocade Communications Systems, Inc."
- Stack indicator: "Stacking System"
- Model: "FCX648" or "ICX6450-48"
- OS: "IronWare Version"
- Version: "08.0.30uT7f1" or "08.0.30uT311"
- Build info: Date, time, MIB label

### 4. Branding Observations
- Both switches show **Brocade** branding (not Foundry, not Ruckus)
- Firmware from 2020 (post-Brocade acquisition)
- Still using Foundry enterprise OID (1991)
- Using IronWare (not FastIron branding yet)

### 5. Stack Indication
Both sysDescr include "Stacking System" which indicates:
- These are stack-capable switches
- May be currently stacked or capable of stacking
- Need to query stack OIDs to determine actual stack configuration

---

## Detection Strategy Updates

Based on real data, update detection logic:

### OS Detection Algorithm
```
1. Check sysObjectID:
   IF starts with .1.3.6.1.4.1.1991.1.3.48
      THEN IronWare-based switch (FCX or ICX)
   
2. Parse sysDescr for model:
   IF contains "FCX"
      THEN platform = "foundry" / "fcx"
   ELSE IF contains "ICX"
      THEN platform = "icx"
   
3. Extract specific model:
   IF sysObjectID = .1.3.6.1.4.1.1991.1.3.48.2.1
      THEN model = "FCX648"
   IF sysObjectID = .1.3.6.1.4.1.1991.1.3.48.5.1
      THEN model = "ICX6450-48"
   
4. Parse version:
   REGEX: "IronWare Version ([0-9.]+[a-zA-Z0-9]*)"
   EXTRACT: "08.0.30uT7f1" -> version
   
5. Detect stack capability:
   IF sysDescr contains "Stacking System"
      THEN stack_capable = true
```

### Platform Differentiation
```
FCX Series:
- sysObjectID: .1.3.6.1.4.1.1991.1.3.48.[1-2].x
- sysDescr contains: "FCX"
- MIB label: FCXR* or FCXS*

ICX 6400/6450 Series:
- sysObjectID: .1.3.6.1.4.1.1991.1.3.48.[3-6].x
- sysDescr contains: "ICX64" or "ICX6450"
- MIB label: ICX64R* or ICX64S*
```

---

## Questions for Further Investigation

1. **What are the OIDs for other models?**
   - FCX624 (likely 48.1.x?)
   - ICX6430, ICX6610, ICX6650
   - ICX7150, ICX7250, ICX7450, ICX7750

2. **Do newer firmware versions use different OIDs?**
   - Does FastIron 09.x use Brocade OID (1588)?
   - When did the transition happen?

3. **What about standalone vs stacked?**
   - Does sysDescr change from "Stacking System" when standalone?
   - Need to query stack OIDs to confirm

4. **Ruckus branding?**
   - Does Ruckus-branded firmware change sysDescr?
   - Different OIDs for Ruckus era?

---

## Next Steps

1. **Query stack OIDs** from these devices:
   ```bash
   # Get stack topology
   snmpget -v2c -c public device .1.3.6.1.4.1.1991.1.1.3.31.1.2.0
   
   # Get stack members
   snmpwalk -v2c -c public device .1.3.6.1.4.1.1991.1.1.3.31.3.1
   ```

2. **Collect more device data**:
   - Other FCX models (FCX624?)
   - Other ICX models (ICX7150, ICX7250, etc.)
   - Different firmware versions
   - Standalone vs stacked configurations

3. **Update detection logic** with real OID values

4. **Create test data** based on real SNMP responses

---

## Test Cases to Create

### FCX648 Test Data
```json
{
  "platform": "FCX648",
  "firmware": "08.0.30uT7f1",
  "sysDescr": "Brocade Communications Systems, Inc. Stacking System FCX648, IronWare Version 08.0.30uT7f1...",
  "sysObjectID": ".1.3.6.1.4.1.1991.1.3.48.2.1",
  "expected_os": "foundry",
  "expected_model": "FCX648",
  "expected_version": "08.0.30uT7f1"
}
```

### ICX6450-48 Test Data
```json
{
  "platform": "ICX6450-48",
  "firmware": "08.0.30uT311",
  "sysDescr": "Brocade Communications Systems, Inc. Stacking System ICX6450-48, IronWare Version 08.0.30uT311...",
  "sysObjectID": ".1.3.6.1.4.1.1991.1.3.48.5.1",
  "expected_os": "icx",
  "expected_model": "ICX6450-48",
  "expected_version": "08.0.30uT311"
}
```
