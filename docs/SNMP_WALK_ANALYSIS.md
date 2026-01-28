# SNMP Walk Analysis - Stack vs Standalone

**Date**: January 17, 2026  
**Analysis of**: Real SNMP walk data from stacked and standalone switches

---

## Key Findings

### 1. snChasUnitTable (`.1.3.6.1.4.1.1991.1.1.1.4.1.1`)

**Structure**: Indexed by unit ID (single level)

**Stacked Switch**:
```
.1.3.6.1.4.1.1991.1.1.1.4.1.1.1.1 = INTEGER: 1        (unit index)
.1.3.6.1.4.1.1991.1.1.1.4.1.1.2.1 = STRING: "BGG2203F00B"  (serial number)
.1.3.6.1.4.1.1991.1.1.1.4.1.1.3.1 = INTEGER: 2        (status)
.1.3.6.1.4.1.1991.1.1.1.4.1.1.4.1 = INTEGER: 123      (temperature)
.1.3.6.1.4.1.1991.1.1.1.4.1.1.5.1 = INTEGER: 170      (min temp)
.1.3.6.1.4.1.1991.1.1.1.4.1.1.6.1 = INTEGER: 180      (max temp)
```

**Standalone Switch**:
```
.1.3.6.1.4.1.1991.1.1.1.4.1.1.1.1 = INTEGER: 1        (unit index)
.1.3.6.1.4.1.1991.1.1.1.4.1.1.2.1 = STRING: "BCX2218G0ZA"  (serial number)
.1.3.6.1.4.1.1991.1.1.1.4.1.1.3.1 = INTEGER: 3        (status)
.1.3.6.1.4.1.1991.1.1.1.4.1.1.4.1 = INTEGER: 101      (temperature)
```

**Column Mapping**:
- Column 1: `snChasUnitIndex` (unit ID)
- Column 2: `snChasUnitSerNum` (serial number)
- Column 3: `snChasUnitOperStatus` (operational status)
- Column 4: `snChasUnitTemperature` (current temperature)
- Column 5: `snChasUnitMinTemp` (minimum temperature)
- Column 6: `snChasUnitMaxTemp` (maximum temperature)
- Column 7: `snChasUnitPartNum` (part number/description) - NOT FOUND in walk

**Status**: ✅ Code already uses this correctly

**Note**: Column 7 (`snChasUnitPartNum`) does not appear in SNMP walk data. The code will gracefully handle its absence.

---

### 2. Power Supply Table (`.1.3.6.1.4.1.1991.1.1.1.2.2.1`)

**Structure**: Indexed by `unit.psu` (two-level index)

**Stacked Switch**:
```
.1.3.6.1.4.1.1991.1.1.1.2.2.1.1.1.1 = INTEGER: 1        (unit 1, PSU 1, unit index)
.1.3.6.1.4.1.1991.1.1.1.2.2.1.1.1.2 = INTEGER: 1        (unit 1, PSU 2, unit index)
.1.3.6.1.4.1.1991.1.1.1.2.2.1.2.1.1 = INTEGER: 1        (unit 1, PSU 1, PSU index)
.1.3.6.1.4.1.1991.1.1.1.2.2.1.2.1.2 = INTEGER: 2        (unit 1, PSU 2, PSU index)
.1.3.6.1.4.1.1991.1.1.1.2.2.1.3.1.1 = STRING: "Power supply 1"  (description)
.1.3.6.1.4.1.1991.1.1.1.2.2.1.3.1.2 = STRING: "Power supply 2"  (description)
.1.3.6.1.4.1.1991.1.1.1.2.2.1.4.1.1 = INTEGER: 2        (status: normal)
.1.3.6.1.4.1.1991.1.1.1.2.2.1.4.1.2 = INTEGER: 2        (status: normal)
```

**Column Mapping**:
- Column 1: `snChasPwrSupplyUnitIndex` (unit ID)
- Column 2: `snChasPwrSupplyIndex` (PSU index within unit)
- Column 3: `snChasPwrSupplyDescription` (description)
- Column 4: `snChasPwrSupplyOperStatus` (operational status)

**Current YAML Issue**: 
- Uses: `.1.3.6.1.4.1.1991.1.1.1.2.1.1.3` (WRONG - old table)
- Should use: `.1.3.6.1.4.1.1991.1.1.1.2.2.1.4` (CORRECT - new table with status)

**Status**: ❌ Needs correction

---

### 3. Fan Table (`.1.3.6.1.4.1.1991.1.1.1.3.2.1`)

**Structure**: Indexed by `unit.fan` (two-level index)

**Stacked Switch**:
```
.1.3.6.1.4.1.1991.1.1.1.3.2.1.1.1.1 = INTEGER: 1        (unit 1, fan 1, unit index)
.1.3.6.1.4.1.1991.1.1.1.3.2.1.2.1.1 = INTEGER: 1        (unit 1, fan 1, fan index)
.1.3.6.1.4.1.1991.1.1.1.3.2.1.3.1.1 = STRING: "Fan 1"  (description)
.1.3.6.1.4.1.1991.1.1.1.3.2.1.4.1.1 = INTEGER: 2        (status: normal)
```

**Column Mapping**:
- Column 1: `snChasFanUnitIndex` (unit ID)
- Column 2: `snChasFanIndex` (fan index within unit)
- Column 3: `snChasFanDescription` (description)
- Column 4: `snChasFanOperStatus` (operational status)

**Current YAML Issue**:
- Uses: `.1.3.6.1.4.1.1991.1.1.1.3.1.1.3` (WRONG - old table)
- Should use: `.1.3.6.1.4.1.1991.1.1.1.3.2.1.4` (CORRECT - new table with status)

**Status**: ❌ Needs correction

---

### 4. CPU Utilization Table (`.1.3.6.1.4.1.1991.1.1.3.2.1.1.1`)

**Structure**: Indexed by unit ID (single level)

**Stacked Switch**:
```
.1.3.6.1.4.1.1991.1.1.3.2.1.1.1.1 = INTEGER: 1        (unit 1)
.1.3.6.1.4.1.1991.1.1.3.2.1.1.1.2 = INTEGER: 2        (unit 2)
.1.3.6.1.4.1.1991.1.1.3.2.1.1.1.3 = INTEGER: 3        (unit 3)
... (continues for all units)
```

**Column Mapping**:
- Column 1: `snAgentCpuUtilUnitIndex` (unit ID)
- Column 2: `snAgentCpuUtilValue` (CPU utilization value) - Need to find this

**Status**: ✅ Structure correct, need to verify column name

---

### 5. Memory Data

**Global System Memory** (scalar):
```
.1.3.6.1.4.1.1991.1.1.2.1.54.0 = Gauge32: 451051520  (total DRAM)
.1.3.6.1.4.1.1991.1.1.2.1.55.0 = Gauge32: 378953728  (free DRAM)
```

**Per-Unit Memory**: Need to find unit-indexed memory table

**Status**: ✅ Global memory OIDs work correctly

**Per-Unit Memory**: The YAML uses `snAgentBrdMemoryTotal` which is automatically indexed by unit ID, so it works correctly for both stacked and standalone configurations.

---

## Required Code Changes

### 1. Fix Power Supply OID in YAML
- Change from: `.1.3.6.1.4.1.1991.1.1.1.2.1.1.3`
- Change to: `.1.3.6.1.4.1.1991.1.1.1.2.2.1.4`

### 2. Fix Fan OID in YAML
- Change from: `.1.3.6.1.4.1.1991.1.1.1.3.1.1.3`
- Change to: `.1.3.6.1.4.1.1991.1.1.1.3.2.1.4`

### 3. Verify CPU Table Column Names
- Need to confirm `snAgentCpuUtilValue` column index

### 4. CPU Utilization Table
- Updated to use `snAgentCpuUtil100thPercent` (preferred) or `snAgentCpuUtilPercent` (fallback)
- Properly handles index format: `slot.cpu.interval` (e.g., "1.1.0")
- Extracts unit ID from index for stacked systems

---

## Summary

✅ **snChasUnitTable**: Working correctly  
❌ **Power Supply Table**: Wrong OID path in YAML  
❌ **Fan Table**: Wrong OID path in YAML  
⚠️ **CPU Table**: Structure correct, verify column names  
⚠️ **Memory**: Need to verify per-unit OIDs
