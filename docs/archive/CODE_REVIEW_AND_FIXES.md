# Code Review and Fixes - Brocade Stack Discovery

**Date**: January 17, 2026  
**Reviewer**: AI Assistant  
**Status**: Critical Issues Found

---

## Executive Summary

This review identified **critical OID mismatches** between the code implementation and actual SNMP data from real stacked switches. The code uses OIDs from the `snStacking*` MIB (`.1.3.6.1.4.1.1991.1.1.3.31`) which **do not exist** on the tested devices, while the actual data shows OIDs under a different branch (`.1.3.6.1.4.1.1991.1.1.2.1`).

---

## Critical Issues

### 1. **WRONG OID TREE - Stack MIBs Don't Exist** ❌

**Problem**: The code uses OIDs from `FOUNDRY-SN-SWITCH-GROUP-MIB::snStacking*` which don't exist on real devices.

**Code References**:
- `snStackingGlobalConfigState.0` = `.1.3.6.1.4.1.1991.1.1.3.31.1.1.0`
- `snStackingGlobalTopology.0` = `.1.3.6.1.4.1.1991.1.1.3.31.1.2.0`
- `snStackingOperUnitTable` = `.1.3.6.1.4.1.1991.1.1.3.31.3.1`

**Actual SNMP Data Shows**:
- `snStackMemberCount` = `.1.3.6.1.4.1.1991.1.1.2.1.1.0` ✅ (exists, returns 1)
- `snStackMemberTable` = `.1.3.6.1.4.1.1991.1.1.2.1.2` ❌ (No Such Instance)
- `snStackPortCount` = `.1.3.6.1.4.1.1991.1.1.2.1.3.0` ✅ (exists, returns 1)
- `snStackPortTable` = `.1.3.6.1.4.1.1991.1.1.2.1.4` ❌ (No Such Object)

**Impact**: Stack detection **completely fails** on real stacked switches (ICX6450 2-stack, FCX648 6-stack).

**Root Cause**: Documentation references MIB definitions that don't match the actual firmware implementation. The firmware may use a different/older MIB structure.

---

### 2. **Missing LibreNMS Base Class** ⚠️

**Problem**: `BrocadeStack` extends `LibreNMS\OS\Shared\Foundry` which doesn't exist in LibreNMS.

**Status**: Fixed - Created custom base class, but this is non-standard.

**Recommendation**: 
- Option A: Extend `LibreNMS\OS` directly and implement `ProcessorDiscovery` interface
- Option B: Check if LibreNMS has an `Ironware` OS class to extend instead

---

### 3. **Incorrect OID for Stack MAC Address** ❌

**Code**:
```php
$stackMacQuery = \SnmpQuery::get('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalMacAddress.0');
```

**Issue**: The OID `.1.3.6.1.4.1.1991.1.1.3.31.1.1.0` is actually for `snStackingGlobalConfigState`, not MAC address!

**Correct OID** (from documentation): `.1.3.6.1.4.1.1991.1.1.3.31.1.3.0` (if it exists)

---

### 4. **snStackMemberCount Returns 1 on Real Stacks** ⚠️

**Observation**: Both ICX6450 (2-stack) and FCX648 (6-stack) return `snStackMemberCount = 1`.

**Possible Explanations**:
1. Firmware bug - always returns 1
2. OID means something else (e.g., "stack enabled" not "member count")
3. Need different OID for actual count

**Action Required**: Cannot rely on this OID for stack detection.

---

### 5. **Missing Alternative Detection Methods** ⚠️

**Current State**: Code has fallback to standalone detection but no alternative stack detection.

**Recommendations**:
1. Parse interface names for stack ports (e.g., "Stack1/1", "Stack1/2")
2. Use ifTable to identify stack interfaces
3. Check for multiple MAC addresses in ifPhysAddress
4. Parse sysName for stack indicators (e.g., "h08-h05_stack")

---

## Code Quality Issues

### 1. **Excessive Debug Logging** ⚠️

**Location**: `BrocadeStack.php` lines 102-209

**Issue**: Too many debug log statements with hypothesis IDs (H1-H14) that should be removed or made conditional.

**Fix**: Use Laravel's log level configuration or wrap in `if (config('app.debug'))`.

---

### 2. **Inconsistent Error Handling** ⚠️

**Issue**: Some SNMP queries check for null, others don't. Some use `??` operator, others use explicit null checks.

**Example**:
```php
$topologyValue = $topologyQuery->value() ?? 3; // Good
$stackState = $stackStateQuery->value(); // No null check
```

**Fix**: Standardize error handling pattern.

---

### 3. **Missing Type Hints** ⚠️

**Location**: Various methods

**Issue**: Some methods lack return type hints, especially helper methods.

**Example**:
```php
private function extractModel(?string $description): ?string
```

**Fix**: Add return types to all methods.

---

### 4. **Hardcoded OIDs** ⚠️

**Issue**: OIDs are hardcoded as strings throughout the code.

**Recommendation**: Define constants at class level:
```php
private const OID_STACK_MEMBER_COUNT = '.1.3.6.1.4.1.1991.1.1.2.1.1.0';
private const OID_STACK_OPER_TABLE = '.1.3.6.1.4.1.1991.1.1.3.31.3.1';
```

---

## LibreNMS Compliance Issues

### 1. **OS Name** ⚠️

**Current**: `brocade-stack`

**Issue**: Brocade/Ruckus stackable switches need enhanced monitoring capabilities.

**Recommendation**: 
- Use `brocade-stack` for enhanced stack monitoring
- Add stack detection as enhancement to existing OS
- Or create `ironware-stack` variant

---

### 2. **File Structure** ✅

**Status**: Correct
- `resources/definitions/os_detection/brocade-stack.yaml` ✅
- `resources/definitions/os_discovery/brocade-stack.yaml` ✅
- `LibreNMS/OS/BrocadeStack.php` ✅

---

### 3. **Missing Test Files** ❌

**Required**:
- `tests/snmpsim/brocade-stack_*.snmprec`
- `tests/data/brocade-stack_*.json`

**Status**: Test files exist but may need updates based on real SNMP data.

---

### 4. **YAML Detection** ✅

**Status**: Correctly uses sysObjectID as primary detection method.

---

## Optimization Suggestions

### 1. **Reduce SNMP Queries**

**Current**: Multiple individual queries for stack information.

**Optimization**: Use `snmpwalk` to get all stack data in one query, then parse.

---

### 2. **Cache Stack State**

**Suggestion**: Store stack state in device attributes to avoid re-querying on every discovery.

---

### 3. **Parallel SNMP Queries**

**Current**: Sequential queries.

**Optimization**: Use LibreNMS's parallel SNMP capabilities if available.

---

### 4. **Early Exit Conditions**

**Current**: Checks stack state, then capability, then tries multiple fallbacks.

**Optimization**: Combine conditions for faster exit.

---

## Recommended Fixes

### Priority 1: Fix OID Mismatch

1. **Document actual working OIDs** from SNMP data
2. **Update code** to use correct OIDs (if any work)
3. **Implement alternative detection** using interface names, sysName parsing, etc.

### Priority 2: Remove Custom Base Class Dependency

1. **Extend `LibreNMS\OS` directly**
2. **Implement `ProcessorDiscovery`** in `BrocadeStack` class
3. **Remove `LibreNMS\OS\Shared\Foundry`** dependency

### Priority 3: Improve Error Handling

1. **Standardize null checks**
2. **Add proper exception handling**
3. **Log errors at appropriate levels**

### Priority 4: Clean Up Debug Code

1. **Remove or conditionally compile** debug logs
2. **Use Laravel's logging levels** properly
3. **Remove hypothesis IDs** from production code

---

## SNMP Data Analysis

### Working OIDs (from real devices)

| OID | Description | Status |
|-----|------------|--------|
| `.1.3.6.1.2.1.1.1.0` | sysDescr | ✅ Works |
| `.1.3.6.1.2.1.1.2.0` | sysObjectID | ✅ Works |
| `.1.3.6.1.4.1.1991.1.1.2.1.1.0` | snStackMemberCount | ✅ Returns 1 (unreliable) |
| `.1.3.6.1.4.1.1991.1.1.2.1.3.0` | snStackPortCount | ✅ Returns 1 |
| `.1.3.6.1.2.1.2.2` | ifTable | ✅ Works |

### Non-Working OIDs

| OID | Description | Error |
|-----|------------|-------|
| `.1.3.6.1.4.1.1991.1.1.3.31.1.1.0` | snStackingGlobalConfigState | No Such Object |
| `.1.3.6.1.4.1.1991.1.1.3.31.1.2.0` | snStackingGlobalTopology | No Such Object |
| `.1.3.6.1.4.1.1991.1.1.3.31.3.1` | snStackingOperUnitTable | No Such Object |
| `.1.3.6.1.4.1.1991.1.1.2.1.2` | snStackMemberTable | No Such Instance |
| `.1.3.6.1.4.1.1991.1.1.1.4.1.1` | snChasUnitTable | No Such Instance |
| `.1.3.6.1.2.1.47.1.1.1` | entPhysicalTable | No Such Object |

---

## Alternative Detection Strategy

Since standard stack MIBs don't work, implement:

1. **Interface-Based Detection**:
   - Look for interfaces matching pattern: `Stack\d+/\d+`
   - Count unique stack interfaces
   - Map to units based on interface numbering

2. **sysName Parsing**:
   - FCX648: `"h08-h05_stack"` suggests stack
   - Parse for stack indicators

3. **MAC Address Analysis**:
   - Multiple base MACs might indicate stack
   - Compare MACs across interfaces

4. **Port Count Analysis**:
   - Stacks have more ports than standalone
   - Compare against known model port counts

---

## Next Steps

1. **Immediate**: Document that stack MIBs don't work on firmware 08.0.30u
2. **Short-term**: Implement alternative detection methods
3. **Medium-term**: Test with different firmware versions
4. **Long-term**: Contact vendor/Ruckus for correct MIB documentation

---

## Conclusion

The code is well-structured but uses **incorrect OIDs** that don't exist on real devices. The stack detection feature **cannot work** with the current implementation. Alternative detection methods must be implemented to achieve the project goals.
