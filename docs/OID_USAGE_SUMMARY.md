# OID Usage Summary: Stack vs Standalone

**Date**: January 17, 2026  
**Status**: ✅ Implemented

---

## ✅ Implementation Complete

The code now correctly uses:
- **Stack-aware OIDs** (unit-indexed tables) when a stack is detected
- **Standard scalar OIDs** when device is standalone

---

## Key Changes Made

### 1. Hardware Discovery (Serial Numbers, Descriptions)

#### Stacked Configuration
- **OID**: `snChasUnitTable` (table indexed by `snChasUnitIndex`)
- **Path**: `.1.3.6.1.4.1.1991.1.1.1.4.1.1`
- **Usage**: Walk table to get per-unit data
- **Columns**:
  - `snChasUnitSerNum` (column 2) - Serial number per unit
  - `snChasUnitPartNum` (column 7) - Part number/description per unit
- **Index**: Unit ID (1, 2, 3, etc.)

#### Standalone Configuration
- **OID**: `snChasSerNum.0` (scalar, no index)
- **Path**: `.1.3.6.1.4.1.1991.1.1.1.4.0`
- **Usage**: Direct GET query (scalar value)
- **Returns**: Single serial number for the device

### 2. CPU Discovery

#### Both Configurations
- **OID**: `snAgentCpuUtilTable` (table-based)
- **Behavior**:
  - **Stacked**: Returns multiple entries (one per unit), index = unit ID
  - **Standalone**: Returns single entry, index = 1
- **Code**: Automatically adapts based on number of entries

### 3. Memory Discovery

#### Both Configurations
- **System Memory**: `snAgSystemDRAMTotal` (scalar, master unit for stacks)
- **Per-Unit Memory**: `snAgentBrdMemoryTotal` (table, indexed by unit)
- **Behavior**: Automatically adapts - stacked returns multiple entries, standalone returns one

### 4. Stack Member Information

#### Stacked Configuration Only
- **OID**: `snStackingOperUnitTable` (table indexed by `snStackingOperUnitIndex`)
- **Path**: `.1.3.6.1.4.1.1991.1.1.3.31.2.2.1`
- **Columns**:
  - Column 1: `snStackingOperUnitIndex` (unit ID)
  - Column 2: `snStackingOperUnitRole` (master/member/standalone)
  - Column 3: `snStackingOperUnitMac` (MAC address)
  - Column 4: `snStackingOperUnitPriority` (priority)
  - Column 5: `snStackingOperUnitState` (local/remote/reserved/empty)
  - Column 6: `snStackingOperUnitDescription` (description)
  - Column 13: `snStackingOperUnitImgVer` (version)

---

## Code Flow

### Stack Detection
1. Try `snStackingGlobalConfigState` (if available)
2. Try `snStackingOperUnitTable` (if available)
3. Try alternative detection (interface names, sysName)
4. If no stack detected → Standalone mode

### Hardware Discovery Based on Stack State

**If Stacked**:
```php
// Walk unit-indexed table
$chasUnitTable = \SnmpQuery::walk('FOUNDRY-SN-AGENT-MIB::snChasUnitTable')->table();
// Access by unit ID: $chasUnitTable[$unitId]['snChasUnitSerNum']
```

**If Standalone**:
```php
// Use scalar OID
$serial = \SnmpQuery::get('FOUNDRY-SN-AGENT-MIB::snChasSerNum.0')->value();
```

---

## MIB File Verification

All OIDs verified against downloaded MIB files:
- ✅ `FOUNDRY-SN-AGENT-MIB` - Contains `snChasUnitTable` and `snChasSerNum.0`
- ✅ `FOUNDRY-SN-STACKING-MIB` - Contains `snStackingOperUnitTable`
- ✅ OID paths corrected based on actual MIB definitions

---

## Testing Recommendations

1. **Test with Stacked Device**:
   - Verify `snChasUnitTable` returns multiple entries (one per unit)
   - Verify unit IDs match stack member IDs
   - Verify serial numbers are per-unit

2. **Test with Standalone Device**:
   - Verify `snChasSerNum.0` returns single value
   - Verify no unit-indexed queries are made
   - Verify single unit record is created

3. **Test CPU/Memory**:
   - Verify both configs work with same OIDs
   - Verify stacked returns multiple entries
   - Verify standalone returns single entry

---

## Files Modified

1. `LibreNMS/OS/BrocadeStack.php`:
   - Updated hardware discovery to use `snChasUnitTable` for stacked
   - Updated standalone discovery to use scalar OIDs
   - Updated CPU discovery to detect stack state

2. `resources/definitions/os_discovery/brocade-stack.yaml`:
   - Added comments explaining OID selection logic
   - Documented stack-aware vs standalone behavior

3. `docs/STACK_VS_STANDALONE_OIDS.md`:
   - Comprehensive guide to OID usage
   - Examples for both configurations

4. `docs/OID_USAGE_SUMMARY.md`:
   - This file - quick reference

---

## Summary

✅ **Stacked Config**: Uses unit-indexed table OIDs (`snChasUnitTable`, `snStackingOperUnitTable`)  
✅ **Standalone Config**: Uses scalar OIDs (`snChasSerNum.0`)  
✅ **Automatic Detection**: Code selects appropriate OIDs based on stack detection  
✅ **MIB Verified**: All OIDs verified against actual MIB files  

The implementation now correctly uses stack-aware OIDs when a stack is detected, and standard OIDs for standalone switches, as required.
