# LibreNMS Compatibility Analysis

**Date**: January 17, 2026  
**Purpose**: Compare our implementation with official LibreNMS Ironware support

---

## 🔍 Critical Discovery

**LibreNMS already has comprehensive Ironware support!**

The official LibreNMS repository contains:
- `LibreNMS/OS/Ironware.php` - Full OS class
- `resources/definitions/os_detection/ironware.yaml` - Detection rules
- `resources/definitions/os_discovery/ironware.yaml` - Monitoring configuration

---

## 📊 Official LibreNMS Implementation

### 1. Ironware.php (Official)

**Location**: `LibreNMS/OS/Ironware.php`

**Structure**:
```php
namespace LibreNMS\OS;
use LibreNMS\OS\Shared\Foundry;

class Ironware extends Foundry
{
    public function discoverOS(Device $device): void
    {
        parent::discoverOS($device); // yaml
        $this->rewriteHardware();
    }
}
```

**Key Features**:
- ✅ Extends Foundry base class
- ✅ Massive hardware rewrite array (650+ model mappings)
- ✅ Maps internal names to friendly names
- ✅ Covers FCX, ICX, FastIron, NetIron, ServerIron, BigIron, TurboIron, etc.
- ✅ Includes ALL models: FCX624, FCX648, ICX6430, ICX6450, ICX6610, ICX7150, ICX7250, ICX7450, ICX7750

**Hardware Mappings Include**:
```php
'snFCX624SSwitch' => 'FCX624S',
'snFCX648SSwitch' => 'FCX648S',
'snICX645024Switch' => 'FastIron ICX 6450 24-port Switch',
'snICX645048Switch' => 'FastIron ICX 6450 48-port Switch',
'snICX715024Switch' => 'Ruckus ICX 7150 24-port Switch',
'snFastIronStackFCXSwitch' => 'FCX',
'snFastIronStackICX6450Switch' => 'FastIron ICX 6450 stack',
'snFastIronStackICX7750Switch' => 'FastIron ICX 7750 stack',
// ... 650+ more mappings
```

### 2. OS Detection (Official)

**Location**: `resources/definitions/os_detection/ironware.yaml`

```yaml
os: ironware
text: 'Brocade IronWare'
type: network
icon: brocade
group: brocade
discovery:
    - sysDescr:
        - IronWare
```

**Detection Strategy**:
- ✅ Simple: looks for "IronWare" in sysDescr
- ✅ Catches ALL IronWare devices
- ✅ Single OS for entire platform family

### 3. OS Discovery Configuration (Official)

**Location**: `resources/definitions/os_discovery/ironware.yaml`

**Features**:
- ✅ Memory pools monitoring
- ✅ Temperature sensors
- ✅ PoE monitoring (per-port and per-unit)
- ✅ Power supply status
- ✅ Fan status
- ✅ **Stack monitoring** (already implemented!)
  - Stack global config state
  - Stack unit state per member
  - Stack port 1 and 2 status
  - Stack neighbor detection
- ✅ Optical transceiver monitoring (Tx/Rx power, temperature)
- ✅ Hardware/serial extraction

**Stack Monitoring** (Already Present):
```yaml
state:
    -
        oid: snStackingGlobalConfigState
        descr: 'Global Stack Config State'
        states:
            - { value: 0, descr: none }
            - { value: 1, descr: enabled }
            - { value: 2, descr: disabled }
    -
        oid: snStackingOperUnitTable
        value: snStackingOperUnitState
        descr: 'Unit {{ $index }} Stack State'
        states:
            - { value: 1, descr: local }
            - { value: 2, descr: remote }
            - { value: 3, descr: reserved }
            - { value: 4, descr: empty }
    -
        oid: snStackingOperUnitTable
        value: snStackingOperUnitStackPort1State
        descr: 'Unit {{ $index }} Stack-port 1 to Unit {{ $snStackingOperUnitNeighbor1 }}'
```

---

## 📊 Our Implementation

### 1. Our Approach

**What We Created**:
- Separate OS definitions: `foundry-fcx`, `brocade-icx6450`, `brocade-icx7150`, etc.
- Granular detection with verified sysObjectID patterns
- Enhanced stack discovery logic
- Real device verification

**Our Detection Logic**:
```php
// Check for specific sysObjectID patterns
if (preg_match('/\.1\.3\.6\.1\.4\.1\.1991\.1\.3\.48\.(\d+)\./', $sysObjectID)) {
    if (stripos($sysDescr, 'FCX') !== false) {
        $os = 'foundry-fcx';  // NEW OS
    } elseif (preg_match('/ICX\s*(\d{4})/', $sysDescr)) {
        $os = 'brocade-icx6450';  // NEW OS
    }
}
```

### 2. Our Verified OIDs

✅ **Real Device Testing**:
- FCX648: `.1.3.6.1.4.1.1991.1.3.48.2.1`
- ICX6450-48: `.1.3.6.1.4.1.1991.1.3.48.5.1`

---

## ⚠️ Compatibility Issues

### 1. **Duplicate OS Definitions**

**Problem**: We're creating new OSes that overlap with existing "ironware" OS

```
Official:     ironware (covers ALL)
Our approach: foundry-fcx, brocade-icx6450, brocade-icx7150, etc. (separate OSes)
```

**Impact**:
- ❌ Conflicts with existing deployments
- ❌ Devices already discovered as "ironware" won't change
- ❌ Duplicates monitoring configuration
- ❌ Splits community support

### 2. **Architecture Mismatch**

**Official**: Single OS + hardware mapping  
**Our Approach**: Multiple OSes

**Official Way**:
```php
class Ironware extends Foundry {
    // One OS, differentiate via hardware string
}
```

**Our Way**:
```php
$os = 'foundry-fcx';  // Different OS entirely
$os = 'brocade-icx6450';  // Different OS entirely
```

### 3. **Stack Monitoring Already Exists**

Official LibreNMS **already monitors**:
- ✅ Stack global state
- ✅ Stack unit state per member  
- ✅ Stack port status
- ✅ Stack neighbor relationships

Our implementation focuses on the **same features**!

### 4. **Hardware Detection Already Exists**

Official has **650+ hardware mappings** including:
- ✅ All FCX models
- ✅ All ICX 6450/6610/6650 models
- ✅ All ICX 7150/7250/7450/7750 models
- ✅ Stack configurations

---

## ✅ What LibreNMS Needs (Gaps We Can Fill)

### 1. **Enhanced Detection** ⭐

**Current**: Simple "IronWare" string match  
**Improvement**: Use specific sysObjectID patterns

**Benefit**: More accurate, faster detection

### 2. **Verified OID Patterns** ⭐⭐

**Current**: Generic detection  
**Improvement**: Real-world verified OIDs from testing

**Our Contribution**:
```yaml
# Add to existing ironware.yaml
discovery:
    - sysDescr:
        - IronWare
    - sysObjectID:  # NEW - Add verified patterns
        - .1.3.6.1.4.1.1991.1.3.48  # FCX/ICX pattern
```

### 3. **Missing Hardware Mappings** ⭐

**Check if these exist**:
- ICX7650 series (appears to be missing)
- Newer Ruckus-branded models
- Latest firmware versions

### 4. **Stack Topology Visualization** ⭐⭐⭐

**Current**: Monitors stack state  
**Missing**: Visual topology, master identification, ring vs chain

**Our Contribution**: Database schema + visualization

### 5. **Per-Unit Hardware Inventory** ⭐⭐

**Current**: Global device info  
**Missing**: Per-stack-member serial numbers, models, versions

**Our Contribution**: Track each unit independently

---

## 🎯 Recommended Integration Strategy

### Option 1: Enhance Existing "ironware" OS (RECOMMENDED ✅)

**Approach**: Contribute improvements to existing codebase

**Changes**:
1. **Add to `os_detection/ironware.yaml`**:
   ```yaml
   discovery:
       - sysDescr:
           - IronWare
       - sysObjectID:
           - .1.3.6.1.4.1.1991.1.3.48  # Add verified pattern
   ```

2. **Enhance `Ironware.php`**:
   ```php
   public function discoverOS(Device $device): void
   {
       parent::discoverOS($device);
       $this->rewriteHardware();
       $this->detectStackTopology();  // NEW
   }
   
   private function detectStackTopology()
   {
       // Our enhanced stack detection
   }
   ```

3. **Add Database Tables**:
   - `ironware_stacks`
   - `ironware_stack_members`
   - `ironware_stack_ports`

4. **Add Stack Visualization**:
   - Web interface for stack topology
   - Per-unit inventory

**Benefits**:
- ✅ Compatible with existing deployments
- ✅ Leverages existing code
- ✅ Single OS to maintain
- ✅ Community accepts more easily
- ✅ Existing devices auto-upgrade
- ✅ Uses proven architecture

**Drawbacks**:
- ⚠️  Need to understand Foundry base class
- ⚠️  More complex integration

### Option 2: Keep Separate OSes (NOT RECOMMENDED ❌)

**Approach**: Maintain our separate OS definitions

**Drawbacks**:
- ❌ Conflicts with existing "ironware" OS
- ❌ Community resistance (duplicate effort)
- ❌ Existing deployments don't benefit
- ❌ Splits documentation and support
- ❌ More code to maintain

### Option 3: Hybrid Approach (COMPROMISE ⚠️)

**Approach**: Make "ironware" the parent, our OSes inherit

```yaml
# foundry-fcx.yaml
os: foundry-fcx
parent_os: ironware  # Inherit everything from ironware
# Add only FCX-specific overrides
```

**Benefits**:
- ✅ Inherits existing monitoring
- ✅ Allows specialization
- ✅ Compatible with parent

**Drawbacks**:
- ⚠️  Still creates new OSes
- ⚠️  Community may prefer enhancement

---

## 📋 Action Items for Integration

### Immediate Actions:

1. **Research Foundry Base Class** ⭐⭐⭐
   ```bash
   # Check what Foundry class provides
   curl -s https://raw.githubusercontent.com/librenms/librenms/master/LibreNMS/OS/Shared/Foundry.php
   ```

2. **Verify Hardware Mappings** ⭐⭐
   - Check if all our models are in the rewrite array
   - Identify any missing models

3. **Analyze Stack Monitoring** ⭐⭐
   - Understand what's already monitored
   - Identify gaps in current implementation

4. **Contact LibreNMS Community** ⭐⭐⭐
   - Discuss approach on Discord/GitHub
   - Get maintainer feedback
   - Understand preferred contribution method

### Implementation Path:

**Phase 1**: Enhance Detection
- Add verified sysObjectID patterns to `ironware.yaml`
- Update documentation with real OIDs

**Phase 2**: Enhance Hardware Detection  
- Add any missing hardware mappings to `Ironware.php`
- Verify all FCX/ICX models covered

**Phase 3**: Add Stack Features
- Create database schema for per-unit tracking
- Implement stack topology detection
- Add web interface for visualization

**Phase 4**: Documentation & Testing
- Update LibreNMS docs
- Test with real devices
- Create migration guide

---

## 💡 Key Insights

### What We Learned:

1. **LibreNMS Already Has Good Support** ✅
   - Ironware OS exists and is comprehensive
   - Stack monitoring already implemented
   - 650+ hardware model mappings

2. **Our Value Add**:
   - ✅ Real device verification (FCX648, ICX6450-48)
   - ✅ Specific sysObjectID patterns
   - ✅ Enhanced stack topology
   - ✅ Per-unit inventory tracking
   - ✅ Detailed documentation

3. **Best Contribution Strategy**:
   - Enhance existing "ironware" OS
   - Add verified detection patterns
   - Extend stack monitoring
   - Add topology visualization

### What We Should NOT Do:

- ❌ Create competing OS definitions
- ❌ Duplicate existing functionality
- ❌ Ignore existing architecture
- ❌ Submit conflicting PRs

---

## 📊 Compatibility Matrix

| Feature | Official LibreNMS | Our Implementation | Recommendation |
|---------|-------------------|-------------------|----------------|
| OS Detection | ✅ Simple (IronWare string) | ✅ Enhanced (sysObjectID) | Merge: Add our patterns |
| Hardware Mapping | ✅ 650+ models | ❌ Separate | Use: Existing is better |
| Stack Monitoring | ✅ Basic (state/ports) | ✅ Enhanced (topology) | Enhance: Add our features |
| Per-Unit Inventory | ❌ Missing | ✅ Implemented | Add: New feature |
| Stack Visualization | ❌ Missing | ✅ Planned | Add: New feature |
| Database Schema | ❌ Limited | ✅ Comprehensive | Add: New tables |
| Real Device Testing | ⚠️ Unknown | ✅ Verified | Contribute: Test data |

---

## 🎯 Final Recommendation

**ENHANCE, DON'T REPLACE**

1. **Keep Our Documentation** ✅
   - Excellent reference material
   - Real device verification
   - Platform comparison guide

2. **Adapt Our Code** ✅
   - Integrate into existing Ironware class
   - Use LibreNMS architecture
   - Follow coding standards

3. **Contribute Enhancements** ✅
   - Add verified sysObjectID patterns
   - Enhance stack topology detection
   - Add per-unit inventory
   - Create stack visualization

4. **Engage Community** ✅
   - Discuss on LibreNMS Discord
   - Get maintainer buy-in
   - Coordinate integration

---

## 📞 Next Steps

1. **Review Foundry Base Class** (30 minutes)
2. **Test Against Real Devices** (if available)
3. **Contact LibreNMS Maintainers** (1-2 days)
4. **Refactor Code for Integration** (2-3 days)
5. **Submit Enhancement PR** (1 week)

---

**Status**: Analysis Complete  
**Recommendation**: Enhance existing "ironware" OS rather than creating new OSes  
**Priority**: Community engagement and architecture alignment

**Our code is valuable, but needs to integrate with existing LibreNMS architecture for successful contribution.**
