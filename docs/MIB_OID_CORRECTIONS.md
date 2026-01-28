# MIB OID Corrections Based on Actual MIB Files

**Date**: January 17, 2026  
**Source**: FOUNDRY-SN-STACKING-MIB from LibreNMS repository

---

## Critical OID Path Corrections

After downloading and analyzing the actual MIB files from the LibreNMS repository, several OID paths in the code are **INCORRECT**.

---

## Correct OID Structure

Based on the MIB file analysis:

### Base Path
- `snSwitch` = `.1.3.6.1.4.1.1991.1.1.3` (from FOUNDRY-SN-SWITCH-GROUP-MIB)
- `snStacking` = `{ snSwitch 31 }` = `.1.3.6.1.4.1.1991.1.1.3.31`
- `snStackingGlobalObjects` = `{ snStacking 1 }` = `.1.3.6.1.4.1.1991.1.1.3.31.1`
- `snStackingTableObjects` = `{ snStacking 2 }` = `.1.3.6.1.4.1.1991.1.1.3.31.2`

### Global Scalar Objects

| Object | Current Code (WRONG) | Correct OID | MIB Definition |
|--------|---------------------|------------|----------------|
| `snStackingGlobalConfigState` | `.1.3.6.1.4.1.1991.1.1.3.31.1.1.0` | ✅ `.1.3.6.1.4.1.1991.1.1.3.31.1.1` | `{ snStackingGlobalObjects 1 }` |
| `snStackingGlobalMacAddress` | `.1.3.6.1.4.1.1991.1.1.3.31.1.3.0` | ❌ **WRONG** | `{ snStackingGlobalObjects 2 }` = `.1.3.6.1.4.1.1991.1.1.3.31.1.2` |
| `snStackingGlobalTopology` | `.1.3.6.1.4.1.1991.1.1.3.31.1.2.0` | ❌ **WRONG** | `{ snStackingGlobalObjects 5 }` = `.1.3.6.1.4.1.1991.1.1.3.31.1.5` |

### Table Objects

| Object | Current Code (WRONG) | Correct OID | MIB Definition |
|--------|---------------------|------------|----------------|
| `snStackingConfigUnitTable` | `.1.3.6.1.4.1.1991.1.1.3.31.2.1` | ✅ `.1.3.6.1.4.1.1991.1.1.3.31.2.1` | `{ snStackingTableObjects 1 }` |
| `snStackingOperUnitTable` | `.1.3.6.1.4.1.1991.1.1.3.31.3.1` | ❌ **WRONG** | `{ snStackingTableObjects 2 }` = `.1.3.6.1.4.1.1991.1.1.3.31.2.2` |

---

## Detailed OID Breakdown

### snStackingGlobalObjects (`.1.3.6.1.4.1.1991.1.1.3.31.1`)

1. `snStackingGlobalConfigState` = `.1.3.6.1.4.1.1991.1.1.3.31.1.1`
2. `snStackingGlobalMacAddress` = `.1.3.6.1.4.1.1991.1.1.3.31.1.2` ⚠️ **Code uses .1.3**
3. `snStackingGlobalPersistentMacTimerState` = `.1.3.6.1.4.1.1991.1.1.3.31.1.3`
4. `snStackingGlobalPersistentMacTimer` = `.1.3.6.1.4.1.1991.1.1.3.31.1.4`
5. `snStackingGlobalTopology` = `.1.3.6.1.4.1.1991.1.1.3.31.1.5` ⚠️ **Code uses .1.2**

### snStackingTableObjects (`.1.3.6.1.4.1.1991.1.1.3.31.2`)

1. `snStackingConfigUnitTable` = `.1.3.6.1.4.1.1991.1.1.3.31.2.1` ✅ **Correct**
2. `snStackingOperUnitTable` = `.1.3.6.1.4.1.1991.1.1.3.31.2.2` ⚠️ **Code uses .31.3.1**

---

## Operational Unit Table Structure

The `snStackingOperUnitTable` has the following columns (indexed by `snStackingOperUnitIndex`):

1. `snStackingOperUnitIndex` = `.1.3.6.1.4.1.1991.1.1.3.31.2.2.1.1`
2. `snStackingOperUnitRole` = `.1.3.6.1.4.1.1991.1.1.3.31.2.2.1.2`
3. `snStackingOperUnitMac` = `.1.3.6.1.4.1.1991.1.1.3.31.2.2.1.3`
4. `snStackingOperUnitPriority` = `.1.3.6.1.4.1.1991.1.1.3.31.2.2.1.4`
5. `snStackingOperUnitState` = `.1.3.6.1.4.1.1991.1.1.3.31.2.2.1.5`
6. `snStackingOperUnitDescription` = `.1.3.6.1.4.1.1991.1.1.3.31.2.2.1.6`
7. `snStackingOperUnitStackPort1` = `.1.3.6.1.4.1.1991.1.1.3.31.2.2.1.7`
8. `snStackingOperUnitStackPort1State` = `.1.3.6.1.4.1.1991.1.1.3.31.2.2.1.8`
9. `snStackingOperUnitStackPort2` = `.1.3.6.1.4.1.1991.1.1.3.31.2.2.1.9`
10. `snStackingOperUnitStackPort2State` = `.1.3.6.1.4.1.1991.1.1.3.31.2.2.1.10`
11. `snStackingOperUnitNeighbor1` = `.1.3.6.1.4.1.1991.1.1.3.31.2.2.1.11`
12. `snStackingOperUnitNeighbor2` = `.1.3.6.1.4.1.1991.1.1.3.31.2.2.1.12`
13. `snStackingOperUnitImgVer` = `.1.3.6.1.4.1.1991.1.1.3.31.2.2.1.13`
14. `snStackingOperUnitBuildlVer` = `.1.3.6.1.4.1.1991.1.1.3.31.2.2.1.14`

---

## Required Code Fixes

### 1. Fix OID Constants

```php
// WRONG (current):
private const OID_STACK_TOPOLOGY = '.1.3.6.1.4.1.1991.1.1.3.31.1.2.0';
private const OID_STACK_MAC = '.1.3.6.1.4.1.1991.1.1.3.31.1.3.0';
private const OID_STACK_OPER_TABLE = '.1.3.6.1.4.1.1991.1.1.3.31.3.1';

// CORRECT:
private const OID_STACK_TOPOLOGY = '.1.3.6.1.4.1.1991.1.1.3.31.1.5.0';
private const OID_STACK_MAC = '.1.3.6.1.4.1.1991.1.1.3.31.1.2.0';
private const OID_STACK_OPER_TABLE = '.1.3.6.1.4.1.1991.1.1.3.31.2.2';
```

### 2. Update YAML Sensor Definitions

The YAML file also has incorrect OID paths that need to be fixed.

---

## Impact

These incorrect OIDs explain why:
1. Stack MIBs return "No Such Object" - we're querying wrong OIDs!
2. Even if the MIBs existed, we wouldn't find them with wrong paths
3. The alternative detection methods are working, but we should fix the primary method

---

## Next Steps

1. ✅ Download MIB files (completed)
2. ✅ Analyze OID structure (completed)
3. ⏳ Fix OID constants in code
4. ⏳ Update YAML sensor definitions
5. ⏳ Test with corrected OIDs on real devices

---

## References

- MIB Source: https://github.com/librenms/librenms/tree/master/mibs/brocade
- MIB File: `FOUNDRY-SN-STACKING-MIB` (435 lines)
- Last Updated: June 4, 2010
