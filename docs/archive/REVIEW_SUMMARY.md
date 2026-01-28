# Code Review Summary - LibreNMS IronWare Stack Discovery

**Date**: January 17, 2026  
**Reviewer**: AI Assistant

---

## Critical Bugs Fixed ✅

1. **Duplicate code block removed** (lines 144-147)
2. **All JavaScript fetch() calls removed** - replaced with Laravel Log::debug()
3. **Added snStackMemberCount OID support** - now checks this OID from real device data

---

## Remaining Issues

### 1. **Stack MIB OIDs Don't Work on Real Stacks** ⚠️
**Status**: CONFIRMED by user data

Both ICX6450 (2-stack) and FCX648 (6-stack) show:
- `snStackMemberCount = 1` (should be 2 and 6 respectively)
- All stack member table queries return "No Such Instance"
- Stack MIBs at `.1.3.6.1.4.1.1991.1.1.3.31.x.x` don't work

**Possible OID locations from SNMP data**:
- `snStackMemberTable` = `.1.3.6.1.4.1.1991.1.1.2.1.2` (different branch!)
- `snStackPortTable` = `.1.3.6.1.4.1.1991.1.1.2.1.4`

**Action Required**: 
- Run `debug_stack_detection.php` on both switches
- Identify which OIDs actually work
- Update code to use working OIDs

---

### 2. **OS Name Convention** ⚠️
**Current**: `brocade-stack`  
**LibreNMS Standard**: `ironware`

LibreNMS already has `ironware` OS that handles these devices. Options:
- **Option A**: Enhance existing `ironware` OS (recommended for upstream)
- **Option B**: Keep `brocade-stack` as custom OS (for local use)

---

### 3. **Custom Foundry Base Class** ⚠️
**Issue**: `LibreNMS\OS\Shared\Foundry` doesn't exist in LibreNMS

**Current State**: 
- LibreNMS has `LibreNMS\OS\Ironware` which extends `Foundry`
- But `Foundry` itself is in `LibreNMS\OS\Shared\Foundry` in LibreNMS

**Action**: Verify if Foundry base class exists in LibreNMS or if we need to:
- Extend `OS` directly
- Copy Foundry implementation
- Propose as new shared class

---

### 4. **Missing Test Files** ❌
**Required by LibreNMS**:
- `tests/snmpsim/brocade-stack_*.snmprec` files
- `tests/data/brocade-stack_*.json` database dumps

**Current**: Test files exist but may need regeneration with proper format

---

## SNMP Data Analysis

### Working OIDs (from device data)
- ✅ `sysDescr` = `.1.3.6.1.2.1.1.1.0`
- ✅ `sysObjectID` = `.1.3.6.1.2.1.1.2.0`
- ✅ `snStackMemberCount` = `.1.3.6.1.4.1.1991.1.1.2.1.1.0` (returns 1, but exists)
- ✅ `snStackPortCount` = `.1.3.6.1.4.1.1991.1.1.2.1.3.0` (returns 1)
- ✅ `ifTable` = `.1.3.6.1.2.1.2.2` (works normally)
- ✅ Foundry Agent Config = `.1.3.6.1.4.1.1991.1.1.3.x.x` (works)

### Non-Working OIDs (from device data)
- ❌ `snStackMemberTable` = `.1.3.6.1.4.1.1991.1.1.2.1.2` (No Such Instance)
- ❌ `snStackPortTable` = `.1.3.6.1.4.1.1991.1.1.2.1.4` (No Such Object)
- ❌ `snStackingOperUnitTable` = `.1.3.6.1.4.1.1991.1.1.3.31.3.1` (not tested, but likely fails)
- ❌ `snChasUnitTable` = `.1.3.6.1.4.1.1991.1.1.1.4.1.1` (No Such Instance)
- ❌ `entPhysicalTable` = `.1.3.6.1.2.1.47.1.1.1` (No Such Object)

---

## Optimization Recommendations

### 1. **Use snmpwalk for Tables**
Instead of multiple `snmpget` calls, use `snmpwalk` to get entire tables at once.

### 2. **Cache Stack Configuration**
Stack configuration rarely changes. Cache results between discovery runs.

### 3. **Better Error Messages**
When OIDs fail, log the exact error message and OID attempted for debugging.

### 4. **YAML-First Approach**
Move more discovery logic to YAML files per LibreNMS conventions. The `os_discovery/brocade-stack.yaml` is well-structured.

### 5. **Component System**
The Component system usage looks correct. Ensure it matches patterns from other OS classes.

---

## Code Quality Observations

### ✅ Good Practices
- Well-documented code
- Good separation of concerns (Models, OS class, YAML)
- Comprehensive hardware mapping
- Proper use of Eloquent relationships
- Good error handling for missing OIDs

### ⚠️ Areas for Improvement
- Too much debug logging (should use log levels appropriately)
- Some methods are too long (consider breaking down)
- Hard-coded OIDs should be constants or config
- Missing PHPDoc for some methods

---

## Next Steps

1. **Immediate**:
   - Run `debug_stack_detection.php` on both switches
   - Collect working OID information
   - Update code with correct OIDs

2. **Short Term**:
   - Fix remaining issues
   - Add proper test files
   - Verify Foundry base class in LibreNMS

3. **Long Term**:
   - Align with LibreNMS conventions
   - Prepare for upstream contribution
   - Document all OID variations by firmware version

---

## Conclusion

The code is well-structured but has a fundamental issue: **the stack MIB OIDs documented don't work on real stacked switches**. This needs to be resolved before the code can function correctly. The debug script will help identify the correct OIDs to use.
