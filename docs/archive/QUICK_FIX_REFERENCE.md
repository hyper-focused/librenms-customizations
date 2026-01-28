# Quick Fix Reference Guide

## Immediate Code Fixes

### Fix 1: Remove Stack MAC Query (Wrong OID)

**File**: `LibreNMS/OS/BrocadeStack.php`  
**Line**: 180-181

**Change**:
```php
// OLD
$stackMacQuery = \SnmpQuery::get('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalMacAddress.0');
$stackMac = $stackMacQuery->value();

// NEW
$stackMac = null; // OID doesn't exist on real devices
// TODO: Find correct OID or use alternative method
```

---

### Fix 2: Fix Base Class

**File**: `LibreNMS/OS/BrocadeStack.php`  
**Line**: 40

**Change**:
```php
// OLD
use LibreNMS\OS\Shared\Foundry;
class BrocadeStack extends Foundry

// NEW Option 1 (if Ironware exists)
use LibreNMS\OS\Ironware;
class BrocadeStack extends Ironware

// NEW Option 2 (direct)
use LibreNMS\OS;
use LibreNMS\Interfaces\Discovery\ProcessorDiscovery;
class BrocadeStack extends OS implements ProcessorDiscovery
{
    // Copy discoverProcessors() from Foundry.php
}
```

---

### Fix 3: Remove Non-Working Stack OID Queries

**File**: `LibreNMS/OS/BrocadeStack.php`  
**Lines**: 87-88, 178-181, 198-199

**Change**: Wrap in try-catch or check for null, don't assume they exist:

```php
// OLD
$stackStateQuery = \SnmpQuery::get('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalConfigState.0');
$stackState = $stackStateQuery->value();

// NEW
$stackStateQuery = \SnmpQuery::get('FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalConfigState.0');
$stackState = $stackStateQuery->value();
if ($stackState === null) {
    // OID doesn't exist - use alternative detection
    return $this->discoverStackViaAlternativeMethod($device);
}
```

---

### Fix 4: Fix YAML Stack Sensor OIDs

**File**: `resources/definitions/os_discovery/brocade-stack.yaml`  
**Lines**: 226-295

**Action**: Comment out or remove all stack sensor definitions, OR add skip_values:

```yaml
# OLD
oid: snStackingGlobalConfigState
num_oid: '.1.3.6.1.4.1.1991.1.1.3.31.1.1.{{ $index }}'

# NEW (make optional)
oid: snStackingGlobalConfigState
num_oid: '.1.3.6.1.4.1.1991.1.1.3.31.1.1.{{ $index }}'
skip_values:
    -
        oid: snStackingGlobalConfigState.0
        op: '='
        value: null  # Skip if doesn't exist
```

---

### Fix 5: Add OID Constants

**File**: `LibreNMS/OS/BrocadeStack.php`  
**Add after line 40**:

```php
class BrocadeStack extends Foundry
{
    // Stack OIDs that exist (but unreliable)
    private const OID_STACK_MEMBER_COUNT = '.1.3.6.1.4.1.1991.1.1.2.1.1.0';
    private const OID_STACK_PORT_COUNT = '.1.3.6.1.4.1.1991.1.1.2.1.3.0';
    
    // Stack OIDs from documentation (don't work on firmware 08.0.30u)
    private const OID_STACK_CONFIG_STATE = '.1.3.6.1.4.1.1991.1.1.3.31.1.1.0';
    private const OID_STACK_TOPOLOGY = '.1.3.6.1.4.1.1991.1.1.3.31.1.2.0';
    private const OID_STACK_OPER_TABLE = '.1.3.6.1.4.1.1991.1.1.3.31.3.1';
    private const OID_STACK_CONFIG_TABLE = '.1.3.6.1.4.1.1991.1.1.3.31.2.1';
    
    // Then use: self::OID_STACK_MEMBER_COUNT instead of string
}
```

---

### Fix 6: Clean Up Debug Logs

**File**: `LibreNMS/OS/BrocadeStack.php`  
**Throughout**

**Change**:
```php
// OLD
\Log::debug("BrocadeStack [H1]: Starting stack topology discovery", [...]);

// NEW (remove hypothesis IDs)
\Log::debug("BrocadeStack: Starting stack topology discovery", [...]);
```

Or make conditional:
```php
if (config('app.debug')) {
    \Log::debug("BrocadeStack: ...", [...]);
}
```

---

### Fix 7: Implement Interface-Based Stack Detection

**File**: `LibreNMS/OS/BrocadeStack.php`  
**Add new method**:

```php
/**
 * Detect stack via interface names
 * Stack interfaces are named like "Stack1/1", "Stack1/2", etc.
 *
 * @param Device $device
 * @return array|null Array of detected units or null
 */
private function detectStackViaInterfaces(Device $device): ?array
{
    // Get ports from database (already discovered)
    $ports = \DB::table('ports')
        ->where('device_id', $device->device_id)
        ->where('ifDescr', 'like', 'Stack%')
        ->get(['ifIndex', 'ifDescr', 'ifOperStatus']);
    
    if ($ports->isEmpty()) {
        return null;
    }
    
    $units = [];
    foreach ($ports as $port) {
        // Parse "Stack1/1" -> unit 1, port 1
        if (preg_match('/^Stack(\d+)\/(\d+)$/', $port->ifDescr, $matches)) {
            $unitId = (int)$matches[1];
            if (!isset($units[$unitId])) {
                $units[$unitId] = [
                    'unit_id' => $unitId,
                    'ports' => [],
                    'port_count' => 0
                ];
            }
            $units[$unitId]['ports'][] = $port;
            $units[$unitId]['port_count']++;
        }
    }
    
    return !empty($units) ? $units : null;
}
```

Then use in `discoverStackViaAlternativeMethod`:
```php
$stackInterfaces = $this->detectStackViaInterfaces($device);
if ($stackInterfaces) {
    $unitCount = count($stackInterfaces);
    // Create topology record
    // Create member records
}
```

---

## Summary Checklist

- [ ] Fix wrong OID for stack MAC address
- [ ] Remove/update base class dependency
- [ ] Add null checks for all SNMP queries
- [ ] Fix YAML OID paths (or remove non-working sensors)
- [ ] Add OID constants
- [ ] Clean up debug logging
- [ ] Implement interface-based detection
- [ ] Update documentation with limitations
- [ ] Test with real devices
