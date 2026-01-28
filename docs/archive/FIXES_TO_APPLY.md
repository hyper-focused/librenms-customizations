# Fixes to Apply - Brocade Stack Discovery

## Critical Fixes

### 1. Fix Wrong OID for Stack MAC Address

**File**: `LibreNMS/OS/BrocadeStack.php`  
**Line**: 180

**Current (WRONG)**:
```php
$stackMacQuery = \SnmpQuery::get('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalMacAddress.0');
```

**Issue**: This OID (`.1.3.6.1.4.1.1991.1.1.3.31.1.1.0`) is actually for `snStackingGlobalConfigState`, not MAC address!

**Fix**: According to documentation, MAC address should be at `.1.3.6.1.4.1.1991.1.1.3.31.1.3.0`, but this OID doesn't exist on real devices. Remove or make optional.

---

### 2. Fix Wrong OID Path in YAML

**File**: `resources/definitions/os_discovery/brocade-stack.yaml`  
**Line**: 239

**Current (WRONG)**:
```yaml
num_oid: '.1.3.6.1.4.1.1991.1.1.3.31.2.2.1.5.{{ $index }}'
```

**Issue**: This is mixing config table (`.2.2`) with operational table structure. Should be `.3.1.3` for operational state.

**Fix**: Since operational table doesn't exist, this entire sensor definition should be removed or marked as optional.

---

### 3. Remove Non-Existent Stack MIB References

**Files**: 
- `resources/definitions/os_discovery/brocade-stack.yaml` (lines 226-295)
- `LibreNMS/OS/BrocadeStack.php` (multiple locations)

**Action**: All references to `snStacking*` OIDs under `.1.3.6.1.4.1.1991.1.1.3.31` should be removed or wrapped in try-catch with fallback.

---

### 4. Fix Base Class Dependency

**File**: `LibreNMS/OS/BrocadeStack.php`

**Current**:
```php
class BrocadeStack extends Foundry
```

**Fix Option 1** (Recommended):
```php
use LibreNMS\Interfaces\Discovery\ProcessorDiscovery;

class BrocadeStack extends OS implements ProcessorDiscovery
{
    public function discoverProcessors(): void
    {
        // Copy CPU discovery from Foundry.php
    }
}
```

**Fix Option 2** (If Ironware class exists):
```php
class BrocadeStack extends Ironware
{
    // Ironware already extends Foundry or OS
}
```

---

### 5. Implement Alternative Stack Detection

Since stack MIBs don't work, add interface-based detection:

```php
private function detectStackViaInterfaces(Device $device): ?array
{
    $interfaces = \DB::table('ports')
        ->where('device_id', $device->device_id)
        ->where('ifDescr', 'like', 'Stack%')
        ->get();
    
    if ($interfaces->isEmpty()) {
        return null;
    }
    
    // Parse stack interfaces: Stack1/1, Stack1/2, etc.
    $units = [];
    foreach ($interfaces as $if) {
        if (preg_match('/Stack(\d+)\/(\d+)/', $if->ifDescr, $matches)) {
            $unitId = (int)$matches[1];
            if (!isset($units[$unitId])) {
                $units[$unitId] = [
                    'unit_id' => $unitId,
                    'ports' => []
                ];
            }
            $units[$unitId]['ports'][] = $if;
        }
    }
    
    return !empty($units) ? $units : null;
}
```

---

## Code Quality Fixes

### 1. Remove Debug Hypothesis Logging

**File**: `LibreNMS/OS/BrocadeStack.php`  
**Lines**: 102-209, 424-438

**Action**: Remove all `[H1]`, `[H2]`, etc. hypothesis markers from log messages. Keep only essential debug logs.

---

### 2. Standardize Error Handling

**Pattern to Use**:
```php
$query = \SnmpQuery::get($oid);
$value = $query->value();

if ($value === null) {
    \Log::debug("BrocadeStack: OID {$oid} not available", [
        'device_id' => $device->device_id,
        'error' => $query->error()
    ]);
    // Handle gracefully
}
```

---

### 3. Define OID Constants

**Add to BrocadeStack.php**:
```php
class BrocadeStack extends Foundry
{
    // Stack OIDs (that might work)
    private const OID_STACK_MEMBER_COUNT = '.1.3.6.1.4.1.1991.1.1.2.1.1.0';
    private const OID_STACK_PORT_COUNT = '.1.3.6.1.4.1.1991.1.1.2.1.3.0';
    
    // Stack OIDs (documented but don't work on real devices)
    private const OID_STACK_CONFIG_STATE = '.1.3.6.1.4.1.1991.1.1.3.31.1.1.0';
    private const OID_STACK_TOPOLOGY = '.1.3.6.1.4.1.1991.1.1.3.31.1.2.0';
    private const OID_STACK_OPER_TABLE = '.1.3.6.1.4.1.1991.1.1.3.31.3.1';
    
    // Use constants instead of strings
}
```

---

### 4. Fix Missing Return Type

**File**: `LibreNMS/OS/BrocadeStack.php`  
**Method**: `isStackCapableDevice`

**Current**:
```php
private function isStackCapableDevice(Device $device): bool
```

**Status**: Already has return type ✅

---

## YAML File Fixes

### 1. Remove Non-Working Stack Sensors

**File**: `resources/definitions/os_discovery/brocade-stack.yaml`  
**Lines**: 226-295

**Action**: Comment out or remove all `snStacking*` sensor definitions since they don't work on real devices.

**Alternative**: Add `skip_values` to make them optional:
```yaml
skip_values:
    -
        oid: snStackingGlobalConfigState.0
        op: '!='
        value: null  # Skip if OID doesn't exist
```

---

### 2. Fix OID Paths in Stack Sensors

**File**: `resources/definitions/os_discovery/brocade-stack.yaml`  
**Line**: 257, 278

**Issue**: Using wrong table path (`.2.2` instead of `.3.1`)

**Note**: Since these OIDs don't work anyway, this is less critical.

---

## Documentation Updates

### 1. Update SNMP_REFERENCE.md

Add section documenting:
- Which OIDs actually work on firmware 08.0.30u
- Which OIDs don't work (with evidence)
- Alternative detection methods

---

### 2. Update README.md

Update project status from "Planning Phase" to reflect current implementation state.

---

## Testing Recommendations

1. **Test with snmpsim**: Use the provided snmprec files
2. **Test with real devices**: Document which OIDs work/fail
3. **Test alternative detection**: Verify interface-based detection works
4. **Test with different firmware**: See if newer firmware exposes stack MIBs

---

## Migration Path

Since stack MIBs don't work, the implementation needs a different approach:

1. **Phase 1**: Detect stack-capable devices (already working)
2. **Phase 2**: Use alternative methods (interfaces, sysName) to detect stacks
3. **Phase 3**: Monitor stack health via interface status
4. **Phase 4**: If/when correct MIBs are found, add proper stack discovery

---

## Summary of Changes Needed

1. ✅ Remove all `snStacking*` OID queries (they don't exist)
2. ✅ Implement interface-based stack detection
3. ✅ Fix base class to not depend on non-existent Foundry class
4. ✅ Clean up debug logging
5. ✅ Update YAML to remove non-working sensors
6. ✅ Document limitations clearly
7. ✅ Add alternative detection methods
