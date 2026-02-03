# Comprehensive Code Review Summary

**Date**: January 17, 2026  
**Project**: LibreNMS Brocade Stack Discovery

---

## Executive Summary

After thorough review of the codebase, SNMP data from real devices, and LibreNMS conventions, I've identified **critical issues** that prevent the stack discovery feature from working on actual stacked switches. The primary issue is that the code uses OIDs from a MIB structure (`.1.3.6.1.4.1.1991.1.1.3.31`) that **does not exist** on the tested firmware version (08.0.30u).

---

## Critical Findings

### 1. Stack MIBs Don't Exist on Real Devices ❌

**Evidence from SNMP Data**:
- ICX6450 (2-switch stack): All `snStacking*` OIDs return "No Such Object"
- FCX648 (6-switch stack): All `snStacking*` OIDs return "No Such Object"

**OIDs That Don't Work**:
- `.1.3.6.1.4.1.1991.1.1.3.31.1.1.0` - snStackingGlobalConfigState
- `.1.3.6.1.4.1.1991.1.1.3.31.1.2.0` - snStackingGlobalTopology
- `.1.3.6.1.4.1.1991.1.1.3.31.3.1` - snStackingOperUnitTable
- `.1.3.6.1.4.1.1991.1.1.3.31.2.1` - snStackingConfigUnitTable

**OIDs That Exist But Are Unreliable**:
- `.1.3.6.1.4.1.1991.1.1.2.1.1.0` - snStackMemberCount (returns 1 even on 2-stack and 6-stack!)
- `.1.3.6.1.4.1.1991.1.1.2.1.3.0` - snStackPortCount (returns 1)

**Conclusion**: The entire stack detection approach using these MIBs is **fundamentally broken** for firmware 08.0.30u.

---

### 2. Wrong OID for Stack MAC Address ❌

**File**: `LibreNMS/OS/BrocadeStack.php:180`

```php
$stackMacQuery = \SnmpQuery::get('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalMacAddress.0');
```

**Issue**: This queries `.1.3.6.1.4.1.1991.1.1.3.31.1.1.0` which is actually `snStackingGlobalConfigState`, not the MAC address!

**Correct OID** (if it existed): `.1.3.6.1.4.1.1991.1.1.3.31.1.3.0`

---

### 3. Custom Base Class Not in LibreNMS ⚠️

**Issue**: `LibreNMS\OS\Shared\Foundry` doesn't exist in LibreNMS.

**Current Workaround**: Created custom class in this project.

**Recommendation**: 
- Remove dependency on Foundry base class
- Extend `LibreNMS\OS` directly
- Implement `ProcessorDiscovery` interface in BrocadeStack

---

### 4. YAML Uses Wrong OID Paths ❌

**File**: `resources/definitions/os_discovery/brocade-stack.yaml:239`

```yaml
num_oid: '.1.3.6.1.4.1.1991.1.1.3.31.2.2.1.5.{{ $index }}'
```

**Issue**: 
- Uses `.2.2` (config table path) 
- Should be `.3.1.3` for operational state (if it existed)
- But operational table is at `.3.1`, not `.2.2`!

**Additional Issues**:
- Line 257: Uses `.2.2.1.8` for stack port 1 state
- Line 278: Uses `.2.2.1.10` for stack port 2 state
- These should be `.3.1.7` and `.3.1.8` (if they existed)

---

## Code Quality Issues

### 1. Excessive Debug Logging

**Location**: Throughout `BrocadeStack.php`

**Issue**: Debug logs with hypothesis IDs (H1-H14) should be removed or made conditional.

**Fix**:
```php
if (config('app.debug')) {
    \Log::debug("BrocadeStack: ...");
}
```

---

### 2. Inconsistent Error Handling

**Examples**:
- Line 179: Uses null coalescing `??`
- Line 181: No null check
- Line 218: No error handling

**Recommendation**: Standardize on checking for null and logging errors.

---

### 3. Hardcoded OIDs

**Issue**: OIDs are scattered as strings throughout the code.

**Recommendation**: Define as class constants:
```php
private const OID_STACK_MEMBER_COUNT = '.1.3.6.1.4.1.1991.1.1.2.1.1.0';
```

---

### 4. Missing Type Hints

**Status**: Most methods have type hints ✅, but some helper methods lack return types.

---

## LibreNMS Compliance

### ✅ Compliant

1. **File Structure**: Correct locations for YAML and PHP files
2. **Detection Method**: Uses sysObjectID as primary (preferred)
3. **YAML Format**: Follows LibreNMS YAML schema

### ⚠️ Needs Adjustment

1. **OS Name**: Using `brocade-stack` for Brocade/Ruckus stackable switches
   - Maintains compatibility with existing IronWare devices via standard `ironware` OS
   - `brocade-stack` provides enhanced stack monitoring capabilities

2. **Base Class**: Using non-existent `Foundry` class
   - Should extend `LibreNMS\OS` directly
   - Or check if `Ironware` class exists to extend

3. **Test Files**: 
   - Test files exist but may need updates
   - Should match real SNMP data structure

---

## Optimization Opportunities

### 1. Reduce SNMP Queries

**Current**: Multiple individual queries  
**Optimization**: Batch queries where possible

### 2. Cache Results

**Suggestion**: Store stack state in device attributes to avoid re-querying

### 3. Early Exit

**Current**: Checks multiple conditions sequentially  
**Optimization**: Combine conditions for faster exit

---

## Recommended Action Plan

### Immediate (Critical)

1. **Document Limitation**: Stack MIBs don't work on firmware 08.0.30u
2. **Remove/Fix Wrong OIDs**: Fix stack MAC OID query
3. **Implement Alternative Detection**: Use interface names, sysName parsing

### Short-term

1. Remove custom Foundry base class dependency
2. Clean up debug logging
3. Standardize error handling
4. Update YAML to remove non-working sensors

### Medium-term

1. Test with different firmware versions
2. Contact vendor for correct MIB documentation
3. Implement full alternative detection methods

### Long-term

1. Contribute to LibreNMS upstream
2. Add comprehensive tests
3. Document all findings

---

## Alternative Detection Strategy

Since standard MIBs don't work, implement:

### 1. Interface-Based Detection

```php
// Look for interfaces like "Stack1/1", "Stack1/2"
$stackInterfaces = \DB::table('ports')
    ->where('device_id', $device->device_id)
    ->where('ifDescr', 'regexp', '^Stack[0-9]+/[0-9]+')
    ->get();
```

### 2. sysName Parsing

```php
// FCX648: "h08-h05_stack" indicates stack
if (preg_match('/_stack$/', $device->sysName)) {
    // Likely a stack
}
```

### 3. Port Count Analysis

```php
// Compare actual port count to known model specs
// Stacks have more ports
```

### 4. MAC Address Analysis

```php
// Multiple base MACs might indicate stack
// Analyze ifPhysAddress table
```

---

## Conclusion

The code is well-structured and follows good practices, but **cannot work** with the current OID implementation because those OIDs don't exist on real devices. The project needs:

1. **Alternative detection methods** (interface-based, sysName parsing)
2. **Removal of non-working MIB queries**
3. **Clear documentation** of limitations
4. **Testing with different firmware** to find working OIDs

The foundation is solid, but the stack detection feature requires a complete redesign using alternative methods.
