# Code Review Report - LibreNMS IronWare Stack Discovery

**Date**: January 17, 2026  
**Reviewer**: AI Assistant  
**Status**: Critical Issues Found

---

## Executive Summary

This review identified **5 critical bugs**, **3 compliance issues**, and **multiple optimization opportunities** in the LibreNMS IronWare stack discovery implementation. The most critical finding is that the stack MIB OIDs documented in the code **do not work on actual stacked switches** (verified with ICX6450 2-stack and FCX648 6-stack).

---

## Critical Bugs

### 1. **Duplicate Code Block (Syntax Error)**
**File**: `LibreNMS/OS/BrocadeStack.php`  
**Lines**: 144-147  
**Severity**: CRITICAL - Will cause fatal error

```php
if (!$isStackCapable) {
    // ... cleanup code ...
    return;
}

    // Not stack-capable, clean up old data  // DUPLICATE!
    IronwareStackTopology::where('device_id', $device->device_id)->delete();
    return;
}  // Extra closing brace!
```

**Fix**: Remove lines 144-147

---

### 2. **JavaScript Code in PHP File**
**File**: `LibreNMS/OS/BrocadeStack.php`  
**Lines**: 196-216  
**Severity**: CRITICAL - Invalid PHP syntax

The file contains JavaScript `fetch()` calls embedded in PHP code. This will cause parse errors.

**Fix**: Remove all JavaScript fetch() calls or convert to proper PHP logging

---

### 3. **Wrong OID for Stack Config State**
**File**: `LibreNMS/OS/BrocadeStack.php`  
**Line**: 81  
**Severity**: HIGH

The code queries `snStackingGlobalConfigState.0` but according to SNMP_REFERENCE.md:
- `snStackingGlobalMacAddress` = `.1.3.6.1.4.1.1991.1.1.3.31.1.1.0`
- `snStackingGlobalTopology` = `.1.3.6.1.4.1.1991.1.1.3.31.1.2.0`

There's no documented `snStackingGlobalConfigState` OID. The code should check if this OID exists first.

---

### 4. **Missing OID Usage**
**File**: `LibreNMS/OS/BrocadeStack.php`  
**Severity**: MEDIUM

The SNMP data shows these OIDs exist and return values:
- `snStackMemberCount` = `.1.3.6.1.4.1.1991.1.1.2.1.1.0` (returns 1)
- `snStackPortCount` = `.1.3.6.1.4.1.1991.1.1.2.1.3.0` (returns 1)

These are NOT being used in the code but could provide valuable information.

---

### 5. **Incorrect OID Base for Stack Tables**
**File**: `LibreNMS/OS/BrocadeStack.php`  
**Severity**: HIGH

The code uses:
- `snStackingOperUnitTable` = `.1.3.6.1.4.1.1991.1.1.3.31.3.1`

But the SNMP data shows attempts to query:
- `snStackMemberTable` = `.1.3.6.1.4.1.1991.1.1.2.1.2` (different base!)

The actual stack member table might be at `.1.3.6.1.4.1.1991.1.1.2.1.2` not `.1.3.6.1.4.1.1991.1.1.3.31.3.1`

---

## Compliance Issues

### 1. **OS Name Not Standard**
**File**: `resources/definitions/os_detection/brocade-stack.yaml`  
**Issue**: Using `brocade-stack` as OS name

LibreNMS already has `ironware` OS. According to LIBRENMS_COMPLIANCE_ANALYSIS.md, we should enhance the existing `ironware` OS rather than creating a new one.

**Recommendation**: 
- Option A: Enhance existing `ironware` OS
- Option B: If new OS is needed, use `ironware-stack` to maintain consistency

---

### 2. **Custom Base Class Not in LibreNMS**
**File**: `LibreNMS/OS/Shared/Foundry.php`  
**Issue**: This class doesn't exist in LibreNMS

The code extends `LibreNMS\OS\Shared\Foundry` which is custom to this project. For upstream contribution, we need to either:
- Remove the dependency and extend `OS` directly
- Implement CPU discovery inline
- Propose the base class as part of the contribution

---

### 3. **Missing Test Files**
**Issue**: No snmprec or JSON test data files

LibreNMS requires:
- `tests/snmpsim/<os>_<model>.snmprec` files
- `tests/data/<os>_<model>.json` database dumps

These are missing despite having test data in `tests/snmpsim/` directory.

---

## SNMP Data Analysis

### Key Findings from Real Device Data

1. **Stack MIBs Don't Work on Stacked Switches**
   - ICX6450 (2-stack): All stack member queries return "No Such Instance"
   - FCX648 (6-stack): Same behavior
   - `snStackMemberCount` returns 1 (not 2 or 6!)
   - This suggests the MIBs are either:
     - Not implemented in firmware 08.0.30u
     - Require different access permissions
     - Use completely different OIDs

2. **OID Structure Mismatch**
   - Code expects: `.1.3.6.1.4.1.1991.1.1.3.31.x.x` (stacking MIB)
   - Data shows: `.1.3.6.1.4.1.1991.1.1.2.1.x` (different MIB branch)
   - The `snStackMemberTable` is at `.1.3.6.1.4.1.1991.1.1.2.1.2` not in the stacking MIB

3. **Chassis MIBs Also Fail**
   - `snChasUnitTable` queries return "No Such Instance"
   - Serial numbers can't be retrieved via standard OIDs
   - Need alternative methods to get hardware info

---

## Optimization Suggestions

### 1. **Reduce SNMP Queries**
Currently making multiple separate queries. Use `snmpwalk` with table OIDs to get all data in one query.

### 2. **Cache Stack State**
Stack configuration rarely changes. Cache the result and only re-query on device rediscovery.

### 3. **Better Error Handling**
Add try-catch blocks around SNMP queries to handle timeouts gracefully.

### 4. **Use YAML for Discovery**
Move more discovery logic to YAML files per LibreNMS conventions. The `os_discovery/brocade-stack.yaml` already has good structure.

### 5. **Component System Integration**
The code uses LibreNMS Component system which is good, but ensure it follows the standard pattern used by other OS classes.

---

## Recommended Fixes Priority

1. **IMMEDIATE**: Fix duplicate code block (Bug #1)
2. **IMMEDIATE**: Remove JavaScript from PHP (Bug #2)
3. **HIGH**: Investigate correct stack OIDs based on real device data
4. **HIGH**: Add fallback methods when standard MIBs fail
5. **MEDIUM**: Use snStackMemberCount OID if available
6. **MEDIUM**: Align with LibreNMS OS naming conventions
7. **LOW**: Add test files
8. **LOW**: Optimize SNMP queries

---

## Next Steps

1. Run `debug_stack_detection.php` on both switches to identify working OIDs
2. Fix all critical bugs
3. Update OID references based on actual working OIDs
4. Test with real devices after fixes
5. Create proper test files
6. Align with LibreNMS conventions for upstream contribution

---

## Additional Observations

1. The project has excellent documentation
2. Good separation of concerns (Models, OS class, YAML definitions)
3. Comprehensive hardware mapping in `rewriteHardware()`
4. Well-structured database schema
5. Good use of Eloquent relationships

The main issues are:
- Bugs in the code
- Mismatch between documented OIDs and actual device behavior
- Need to align with LibreNMS conventions for contribution
