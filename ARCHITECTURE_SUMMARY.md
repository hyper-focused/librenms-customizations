# LibreNMS Foundry Platform Architecture - Complete Summary

**Last Updated**: January 17, 2026

---

## 🏗️ Complete Architecture Map

### LibreNMS Foundry Platform Structure

```
┌──────────────────────────────────────────────────┐
│          LibreNMS\OS (Base)                      │
│          • Core OS functionality                 │
└────────────────┬─────────────────────────────────┘
                 │
┌────────────────▼─────────────────────────────────┐
│     LibreNMS\OS\Shared\Foundry                   │
│     • Shared base class for Foundry platforms    │
│     • CPU/Processor discovery                    │
│     • FOUNDRY-SN-AGENT-MIB integration          │
│     • Per-slot CPU monitoring                    │
└────────────────┬─────────────────────────────────┘
                 │
        ┌────────┴─────────┐
        │                  │
┌───────▼────────┐  ┌─────▼────────────────────────┐
│  foundryos     │  │  ironware                    │
│                │  │                              │
│  Detection:    │  │  Detection:                  │
│  "Foundry      │  │  "IronWare" or "FastIron"   │
│   Networks"    │  │                              │
│                │  │  Features:                   │
│  Use Case:     │  │  • 650+ hardware mappings    │
│  Legacy        │  │  • Stack monitoring          │
│  Foundry       │  │  • Sensor monitoring         │
│  branding      │  │  • PoE monitoring           │
│                │  │                              │
│  YAML:         │  │  YAML:                       │
│  foundryos.    │  │  ironware.yaml              │
│  yaml          │  │                              │
└────────────────┘  └─────┬────────────────────────┘
                          │
                   ┌──────▼────────────────────┐
                   │  OUR ENHANCEMENTS         │
                   │  (Future Integration)     │
                   │                           │
                   │  • Enhanced sysObjectID   │
                   │    detection              │
                   │  • Stack topology visual  │
                   │  • Per-unit inventory     │
                   │  • Ring/chain detection   │
                   │  • Member connectivity    │
                   └───────────────────────────┘
```

---

## 📊 The Three Components

### 1️⃣ Foundry.php (Shared Base Class)

**Location**: `LibreNMS/OS/Shared/Foundry.php`

**Purpose**: Common functionality for all Foundry-based platforms

**Provides**:
```php
class Foundry extends OS implements ProcessorDiscovery
{
    public function discoverProcessors()
    {
        // CPU discovery from FOUNDRY-SN-AGENT-MIB
        // Per-slot/per-module monitoring
        // 5-minute interval (300 seconds)
    }
}
```

**Used By**: Both `foundryos` and `ironware`

**Our Impact**: None (we inherit, don't modify)

---

### 2️⃣ foundryos (Legacy Foundry Networks)

**Detection File**: `resources/definitions/os_detection/foundryos.yaml`
```yaml
os: foundryos
text: 'Foundry Networking'
discovery:
    - sysDescr:
        - Foundry Networks  # Original branding
```

**Discovery File**: `resources/definitions/os_discovery/foundryos.yaml`
```yaml
modules:
  os:
    sysDescr_regex: '/Foundry Networks, Inc. (?<hardware>[^,]+), IronWare Version (?<version>\S+)/'
```

**Use Case**: 
- Pre-Brocade acquisition devices
- Original "Foundry Networks" branding
- Legacy deployments

**Our Devices**: ❌ Do NOT use this (we have Brocade branding)

**Our Impact**: None (not our target)

---

### 3️⃣ ironware (Modern Brocade/Ruckus) ⭐ **OUR TARGET**

**Detection File**: `resources/definitions/os_detection/ironware.yaml`
```yaml
os: ironware
text: 'Brocade IronWare'
discovery:
    - sysDescr:
        - IronWare  # Brocade/Ruckus branding
```

**Discovery File**: `resources/definitions/os_discovery/ironware.yaml` (extensive)
```yaml
mib: FOUNDRY-SN-AGENT-MIB:FOUNDRY-SN-SWITCH-GROUP-MIB:FOUNDRY-SN-STACKING-MIB
modules:
  os:
    sysDescr_regex: '/IronWare Version V(?<version>.*) Compiled on/'
  mempools: [memory monitoring]
  sensors:
    - temperature
    - power (PoE)
    - optical transceivers
    - fan/PSU status
    - stack monitoring  # Already exists!
```

**Class File**: `LibreNMS/OS/Ironware.php`
```php
class Ironware extends Foundry
{
    public function discoverOS(Device $device): void
    {
        parent::discoverOS($device);  // Inherits CPU from Foundry
        $this->rewriteHardware();     // 650+ model mappings
    }
}
```

**Use Case**:
- Post-Brocade acquisition devices
- Brocade Communications branding
- Ruckus Wireless branding
- Modern FCX and ICX switches

**Our Devices**: ✅ **FCX648 and ICX6450-48 both use this!**

**Our Impact**: ⭐ **Primary target for enhancements**

---

## 🎯 Device Detection Flow

### Real World Example: Our FCX648

```
Step 1: SNMP Query
├─ sysDescr: "Brocade Communications Systems, Inc. Stacking System FCX648, IronWare Version 08.0.30uT7f1..."
└─ sysObjectID: .1.3.6.1.4.1.1991.1.3.48.2.1

Step 2: OS Detection
├─ Check foundryos: Contains "Foundry Networks"? ❌ NO
└─ Check ironware: Contains "IronWare"? ✅ YES

Step 3: OS Selected
└─ OS = ironware ✅

Step 4: Class Inheritance
OS
 └─ Foundry (gets CPU discovery)
     └─ Ironware (gets hardware mapping + sensors)

Step 5: Discovery Process
├─ CPU discovery (from Foundry base)
├─ Hardware mapping (from Ironware.rewriteHardware)
├─ Memory pools (from ironware.yaml)
├─ Sensors (from ironware.yaml)
└─ Stack monitoring (from ironware.yaml)
```

---

## 📋 Feature Comparison

| Feature | Foundry (Base) | foundryos | ironware | Our Enhancement |
|---------|----------------|-----------|----------|-----------------|
| **CPU Discovery** | ✅ Implements | ✅ Inherits | ✅ Inherits | ✅ Keep |
| **Hardware Mapping** | ❌ | ❌ | ✅ 650+ | ✅ Verify |
| **Memory Monitoring** | ❌ | ❌ | ✅ Yes | ✅ Keep |
| **Temperature** | ❌ | ❌ | ✅ Yes | ✅ Keep |
| **PoE Monitoring** | ❌ | ❌ | ✅ Per-port | ✅ Keep |
| **Stack State** | ❌ | ❌ | ✅ Basic | ✅ Keep |
| **Stack Topology** | ❌ | ❌ | ❌ | ⭐ ADD |
| **Per-Unit Inventory** | ❌ | ❌ | ❌ | ⭐ ADD |
| **sysObjectID Detection** | ❌ | ❌ | ❌ | ⭐ ADD |

---

## 🔍 Why Two OSes?

### Historical Context:

1. **Foundry Networks** (1996-2008)
   - Original company
   - "Foundry Networks" branding
   - IronWare OS developed

2. **Brocade Acquisition** (2008)
   - Brocade acquired Foundry
   - Rebranded to "Brocade Communications Systems"
   - Continued IronWare OS

3. **Ruckus Acquisition** (2016)
   - CommScope acquired Brocade
   - Rebranded to "Ruckus"
   - Evolved to FastIron branding

### LibreNMS Approach:

**Two OSes to handle branding differences**:
- `foundryos`: Legacy "Foundry Networks" branding
- `ironware`: Modern "Brocade" / "Ruckus" branding

**Shared functionality**: Foundry base class

**Detection**: Based on company name in sysDescr

---

## ✅ Verification With Our Data

### Our FCX648:
```yaml
sysDescr: "Brocade Communications Systems, Inc. Stacking System FCX648, IronWare Version 08.0.30uT7f1..."
sysObjectID: .1.3.6.1.4.1.1991.1.3.48.2.1

Analysis:
├─ Contains "Brocade" ✅
├─ Contains "IronWare" ✅
├─ Does NOT contain "Foundry Networks" ❌
└─ Detected as: ironware ✅
```

### Our ICX6450-48:
```yaml
sysDescr: "Brocade Communications Systems, Inc. Stacking System ICX6450-48, IronWare Version 08.0.30uT311..."
sysObjectID: .1.3.6.1.4.1.1991.1.3.48.5.1

Analysis:
├─ Contains "Brocade" ✅
├─ Contains "IronWare" ✅
├─ Does NOT contain "Foundry Networks" ❌
└─ Detected as: ironware ✅
```

**Conclusion**: Both our devices correctly use `ironware` OS

---

## 🎯 Our Integration Points

### 1. Enhance Detection (ironware.yaml)

**Current**:
```yaml
discovery:
    - sysDescr:
        - IronWare
```

**Add**:
```yaml
discovery:
    - sysDescr:
        - IronWare
    - sysObjectID:  # NEW
        - .1.3.6.1.4.1.1991.1.3.48  # Verified pattern
```

### 2. Extend Ironware Class

**Current** (`Ironware.php`):
```php
class Ironware extends Foundry
{
    public function discoverOS(Device $device): void
    {
        parent::discoverOS($device);
        $this->rewriteHardware();
    }
    
    private function rewriteHardware()
    {
        // 650+ hardware mappings
    }
}
```

**Enhanced**:
```php
class Ironware extends Foundry
{
    public function discoverOS(Device $device): void
    {
        parent::discoverOS($device);
        $this->rewriteHardware();
        $this->discoverStackTopology();  // NEW
    }
    
    private function rewriteHardware() { /* existing */ }
    
    private function discoverStackTopology()  // NEW
    {
        // Detect ring vs chain
        // Map stack members
        // Track per-unit inventory
    }
}
```

### 3. Database Schema

**New Tables**:
```sql
ironware_stack_topology
ironware_stack_members  
ironware_stack_connectivity
```

### 4. Web Interface

**New Views**:
- Stack topology visualization
- Per-unit inventory display
- Stack health dashboard

---

## 📊 Architecture Benefits

### Inheritance Hierarchy:

```
OS (base)
 └─ Foundry (CPU discovery) ← Shared by both
     ├─ foundryos (legacy)
     └─ ironware (modern) ← Our target
         └─ Our enhancements
```

**Benefits**:
1. ✅ Code reuse via Foundry base
2. ✅ Separate branding handling
3. ✅ Clean separation of concerns
4. ✅ Easy to extend Ironware
5. ✅ No impact on foundryos

---

## 🎯 Final Recommendations

### DO Enhance:
- ✅ `ironware` OS detection
- ✅ `Ironware.php` class
- ✅ `ironware.yaml` discovery
- ✅ Add new database tables
- ✅ Add new web interfaces

### DON'T Touch:
- ❌ `Foundry.php` base class (works well)
- ❌ `foundryos` OS (not our target)
- ❌ Existing ironware monitoring (already good)

### Inherit:
- ✅ CPU discovery from Foundry
- ✅ Hardware mappings from Ironware
- ✅ Sensor monitoring from ironware.yaml

---

## 🔄 Update to Previous Analysis

### What Changed:
- ✅ Discovered TWO Foundry-based OSes
- ✅ Confirmed shared Foundry base class
- ✅ Verified ironware is correct target

### What Stayed Same:
- ✅ ironware OS is our target (confirmed)
- ✅ Enhance, don't replace (still correct)
- ✅ Stack monitoring exists (still true)
- ✅ Integration strategy (still valid)

### What's Clearer:
- ✅ Architecture hierarchy
- ✅ Inheritance structure
- ✅ Feature distribution
- ✅ Integration points

---

## ✅ Final Architecture Summary

### Components:
1. **Foundry.php** - Shared base (CPU discovery)
2. **foundryos** - Legacy Foundry Networks branding
3. **ironware** - Modern Brocade/Ruckus branding ⭐

### Our Target:
- **ironware** OS exclusively
- Extend Ironware class
- Inherit from Foundry base

### Our Enhancements:
- Enhanced sysObjectID detection
- Stack topology visualization
- Per-unit inventory tracking
- Ring/chain topology mapping

### Integration Path:
```
Foundry (base) → Ironware (current) → Our Enhancements (future)
```

**Status**: ✅ **Architecture fully understood and validated!**
