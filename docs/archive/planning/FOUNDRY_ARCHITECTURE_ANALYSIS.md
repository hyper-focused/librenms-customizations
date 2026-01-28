# LibreNMS Foundry Architecture Deep Dive

**Date**: January 17, 2026  
**Discovery**: There are TWO distinct OSes for Foundry platforms!

---

## 🔍 Critical Architecture Discovery

### LibreNMS Has TWO Foundry-Based OSes:

1. **`foundryos`** - Original Foundry Networks devices
2. **`ironware`** - Brocade/Ruckus rebranded devices (IronWare OS)

Both extend the same **`Foundry`** base class!

---

## 📊 OS Architecture

### Class Hierarchy

```
OS (base)
 └── Foundry (shared base class)
      ├── foundryos (OS class - for Foundry Networks branding)
      └── Ironware (OS class - for IronWare OS)
```

### Foundry.php (Shared Base Class)

**Location**: `LibreNMS/OS/Shared/Foundry.php`

**Purpose**: Provides common functionality for all Foundry-based devices

**Features**:
- ✅ CPU/Processor discovery
- ✅ Uses FOUNDRY-SN-AGENT-MIB
- ✅ Per-slot CPU utilization monitoring
- ✅ Supports both 100th percent and percent metrics

**Code Structure**:
```php
namespace LibreNMS\OS\Shared;

class Foundry extends OS implements ProcessorDiscovery
{
    public function discoverProcessors()
    {
        // Discovers CPU per slot/module from:
        // FOUNDRY-SN-AGENT-MIB::snAgentCpuUtilTable
        // Only monitors 5-minute interval (300 seconds)
    }
}
```

**Key Methods**:
- `discoverProcessors()` - Discovers CPU utilization per module/slot

---

## 🔀 Two OS Implementations

### 1. foundryos (Original Foundry)

**Detection**: `os_detection/foundryos.yaml`
```yaml
os: foundryos
text: 'Foundry Networking'
discovery:
    - sysDescr:
        - Foundry Networks
```

**Discovery**: `os_discovery/foundryos.yaml`
```yaml
modules:
  os:
    sysDescr_regex: '/Foundry Networks, Inc. (?<hardware>[^,]+), IronWare Version (?<version>\S+)/'
```

**Detection Criteria**: sysDescr contains "**Foundry Networks**"

**Use Case**: Original Foundry-branded devices
- Legacy Foundry Networks switches
- Pre-Brocade acquisition devices

### 2. ironware (Brocade/Ruckus)

**Detection**: `os_detection/ironware.yaml`
```yaml
os: ironware
text: 'Brocade IronWare'
discovery:
    - sysDescr:
        - IronWare
```

**Discovery**: `os_discovery/ironware.yaml`
```yaml
modules:
  os:
    sysDescr_regex: '/IronWare Version V(?<version>.*) Compiled on/'
    # Plus extensive sensor/monitoring configuration
```

**Detection Criteria**: sysDescr contains "**IronWare**"

**Use Case**: Brocade/Ruckus rebranded devices
- Brocade-branded switches
- Ruckus-branded switches
- Modern firmware versions

---

## 📋 Detection Logic Comparison

### foundryos Detection:

**Matches**:
```
"Foundry Networks, Inc. FCX648, IronWare Version 08.0.30..."
```

**Pattern**: Looks for "**Foundry Networks**" in sysDescr

**Result**: OS = `foundryos`

### ironware Detection:

**Matches**:
```
"Brocade Communications Systems, Inc. FCX648, IronWare Version 08.0.30..."
"Ruckus ICX 7150-48P Switch, FastIron Version 08.0.95..."
```

**Pattern**: Looks for "**IronWare**" (or "FastIron") in sysDescr

**Result**: OS = `ironware`

---

## 🎯 Our Real Device Data Analysis

### FCX648 (Our Testing):
```
sysDescr: "Brocade Communications Systems, Inc. Stacking System FCX648, IronWare Version 08.0.30uT7f1..."
```

**Detection Analysis**:
- ❌ Does NOT contain "Foundry Networks" → NOT foundryos
- ✅ DOES contain "IronWare" → **IS ironware**
- ✅ Contains "Brocade" branding

**Result**: Detected as **`ironware`** OS ✅

### ICX6450-48 (Our Testing):
```
sysDescr: "Brocade Communications Systems, Inc. Stacking System ICX6450-48, IronWare Version 08.0.30uT311..."
```

**Detection Analysis**:
- ❌ Does NOT contain "Foundry Networks" → NOT foundryos
- ✅ DOES contain "IronWare" → **IS ironware**
- ✅ Contains "Brocade" branding

**Result**: Detected as **`ironware`** OS ✅

---

## 💡 Key Insights

### 1. Architecture Purpose

**Why Two OSes?**
- Historical: Foundry Networks existed before Brocade acquisition
- Branding: Different company names in sysDescr
- Firmware: Same hardware, different branding over time

**Separation Criteria**: **Branding in sysDescr**, not hardware

### 2. Our Devices Use "ironware"

All our test devices (FCX648, ICX6450-48) are:
- ✅ Brocade-branded
- ✅ Use IronWare OS
- ✅ Detected as **`ironware`** not `foundryos`

**Conclusion**: Our work applies to **`ironware`** OS, not `foundryos`

### 3. Foundry Base Class Value

The **`Foundry.php`** base class provides:
- Common CPU discovery logic
- Shared across both OSes
- Simple, focused implementation
- Uses FOUNDRY-SN-AGENT-MIB

**Both** `foundryos` and `ironware` inherit this functionality!

---

## 🔧 Integration Implications

### For Our Implementation:

**Target OS**: **`ironware`** (not foundryos)

**Why**:
1. Our devices show "IronWare" in sysDescr
2. Our devices are Brocade-branded
3. Modern FCX/ICX switches use IronWare
4. foundryos is for legacy Foundry Networks branding

### Inheritance Structure:

```
LibreNMS\OS\Shared\Foundry (base)
    ↓
LibreNMS\OS\Ironware
    ↓ (our enhancement)
Extended stack detection
Stack topology
Per-unit inventory
```

### What We Should Extend:

**File**: `LibreNMS/OS/Ironware.php`

**Current**:
```php
class Ironware extends Foundry
{
    public function discoverOS(Device $device): void
    {
        parent::discoverOS($device);  // Calls Foundry base class
        $this->rewriteHardware();     // 650+ hardware mappings
    }
}
```

**Our Enhancement**:
```php
class Ironware extends Foundry
{
    public function discoverOS(Device $device): void
    {
        parent::discoverOS($device);
        $this->rewriteHardware();
        $this->discoverStackTopology();  // ADD THIS
    }
    
    private function discoverStackTopology()  // ADD THIS
    {
        // Our enhanced stack detection
        // Per-unit inventory
        // Topology mapping
    }
}
```

---

## 📊 Feature Matrix

| Feature | Foundry (Base) | foundryos | ironware | Our Enhancement |
|---------|----------------|-----------|----------|-----------------|
| **CPU Discovery** | ✅ Provides | ✅ Inherits | ✅ Inherits | ✅ Keeps |
| **Hardware Mapping** | ❌ None | ❌ None | ✅ 650+ models | ✅ Verify complete |
| **OS Detection** | ❌ None | ✅ "Foundry Networks" | ✅ "IronWare" | ✅ Add OID patterns |
| **Stack Monitoring** | ❌ None | ❌ None | ✅ Basic | ⭐ Enhance |
| **Stack Topology** | ❌ None | ❌ None | ❌ None | ⭐ ADD NEW |
| **Per-Unit Inventory** | ❌ None | ❌ None | ❌ None | ⭐ ADD NEW |

---

## 🎯 Revised Integration Strategy

### Phase 1: Enhanced Detection (ironware OS)

**Target File**: `resources/definitions/os_detection/ironware.yaml`

**Add**:
```yaml
os: ironware
discovery:
    - sysDescr:
        - IronWare
    - sysObjectID:  # ADD OUR VERIFIED PATTERNS
        - .1.3.6.1.4.1.1991.1.3.48  # FCX/ICX pattern
```

### Phase 2: Enhanced Discovery (ironware OS)

**Target File**: `resources/definitions/os_discovery/ironware.yaml`

**Already Has**: Extensive sensor monitoring

**We Can Add**:
- Enhanced stack topology detection
- Per-unit serial number tracking
- Stack ring/chain topology

### Phase 3: Extended Ironware Class

**Target File**: `LibreNMS/OS/Ironware.php`

**Extend**:
```php
class Ironware extends Foundry
{
    public function discoverOS(Device $device): void
    {
        parent::discoverOS($device);  // Gets CPU discovery from Foundry
        $this->rewriteHardware();     // Existing 650+ mappings
        $this->discoverStackTopology();  // NEW: Our enhancement
    }
    
    private function discoverStackTopology()
    {
        // Our stack topology detection
        // Uses FOUNDRY-SN-STACKING-MIB
        // Detects ring vs chain
        // Maps member connectivity
    }
    
    private function discoverStackInventory()  // NEW
    {
        // Per-unit hardware inventory
        // Serial numbers per unit
        // Firmware per unit
        // Model per unit
    }
}
```

---

## 📋 Updated Action Items

### 1. Confirm Our Scope ✅

**Confirmed**: 
- Target OS: **`ironware`** (not foundryos)
- Both FCX and ICX use ironware OS
- Modern Brocade/Ruckus branding

### 2. Understand Base Class ✅

**Foundry.php provides**:
- CPU discovery from FOUNDRY-SN-AGENT-MIB
- Per-slot monitoring
- 5-minute interval metrics

**We inherit**: All CPU discovery functionality

### 3. Enhance Ironware Class

**What to add**:
- Stack topology detection
- Per-unit inventory
- Visualization support

**What NOT to duplicate**:
- CPU discovery (inherited from Foundry)
- Basic hardware mapping (already in Ironware)
- Sensor monitoring (already in ironware.yaml)

---

## 🔍 foundryos vs ironware Decision Tree

```
Device sysDescr Check
    |
    ├─ Contains "Foundry Networks"?
    |   └─ YES → foundryos (legacy devices)
    |
    └─ Contains "IronWare" or "FastIron"?
        └─ YES → ironware (modern devices)
            |
            └─ Extends Foundry base class
                └─ Gets CPU discovery
                    └─ Adds hardware mapping
                        └─ We enhance with stack topology
```

---

## ✅ Verification Against Our Data

### Our FCX648:
```
sysDescr: "Brocade ... IronWare Version 08.0.30..."
Detection: ironware ✅
Reason: Contains "IronWare"
Base Class: Foundry
```

### Our ICX6450-48:
```
sysDescr: "Brocade ... IronWare Version 08.0.30..."
Detection: ironware ✅
Reason: Contains "IronWare"
Base Class: Foundry
```

### Hypothetical Old Foundry:
```
sysDescr: "Foundry Networks, Inc. ... IronWare Version 07.x..."
Detection: foundryos ✅
Reason: Contains "Foundry Networks"
Base Class: Foundry
```

---

## 💡 Critical Clarification

### Our Previous Analysis Was Correct! ✅

We correctly identified:
- Target OS: ironware
- Need to enhance, not replace
- Stack monitoring exists
- Hardware mapping exists

### New Understanding:

1. **Two OSes exist** (foundryos + ironware)
2. **Both extend Foundry** base class
3. **Our devices use ironware** (confirmed)
4. **Foundry base provides CPU** discovery
5. **We enhance Ironware class** (not Foundry base)

---

## 🎯 Final Architecture Summary

```
┌─────────────────────────────────────┐
│   LibreNMS\OS (base)                │
└─────────────┬───────────────────────┘
              │
┌─────────────▼───────────────────────┐
│   LibreNMS\OS\Shared\Foundry        │
│   • CPU discovery                   │
│   • FOUNDRY-SN-AGENT-MIB           │
└─────────────┬───────────────────────┘
              │
      ┌───────┴────────┐
      │                │
┌─────▼──────┐  ┌─────▼──────┐
│ foundryos  │  │ ironware   │ ← OUR TARGET
│ (legacy)   │  │ (modern)   │
│            │  │ • Hardware │
│            │  │   mapping  │
│            │  │ • Stack    │
│            │  │   monitor  │
└────────────┘  └─────┬──────┘
                      │
                ┌─────▼──────────────┐
                │ OUR ENHANCEMENTS:  │
                │ • Stack topology   │
                │ • Per-unit inv.    │
                │ • Visualization    │
                └────────────────────┘
```

---

## 📊 Decision Matrix

| Aspect | foundryos | ironware | Our Choice |
|--------|-----------|----------|------------|
| **Branding** | Foundry Networks | Brocade/Ruckus | ironware ✅ |
| **Our Devices** | No match | Match | ironware ✅ |
| **Modern Devices** | No | Yes | ironware ✅ |
| **Stack Support** | Unknown | Yes | ironware ✅ |
| **Enhancement Target** | No | Yes | ironware ✅ |

---

## 🎯 Confirmed Integration Path

### 1. Enhance `ironware` OS Detection
- Add our verified sysObjectID patterns
- Keep existing "IronWare" string detection

### 2. Extend `Ironware.php` Class
- Add `discoverStackTopology()` method
- Add `discoverStackInventory()` method
- Inherit CPU discovery from Foundry base

### 3. Enhance `ironware.yaml` Discovery
- Add enhanced stack state detection
- Add per-unit inventory collection

### 4. Add New Features
- Database schema for topology
- Web interface for visualization
- Stack health dashboard

---

## ✅ Summary

### What We Learned:
- ✅ LibreNMS has TWO Foundry-based OSes
- ✅ foundryos = legacy Foundry Networks branding
- ✅ ironware = modern Brocade/Ruckus branding
- ✅ Both extend Foundry base class (CPU discovery)
- ✅ Our devices use **ironware** OS
- ✅ Our enhancements target **Ironware.php** class

### Architecture Confirmed:
```
Foundry (base) → ironware (current) → Our Enhancements (future)
```

### Integration Confirmed:
- ✅ Enhance ironware OS
- ✅ Extend Ironware class
- ✅ Don't touch foundryos
- ✅ Don't modify Foundry base

**Status**: Architecture fully understood, integration path confirmed! ✅
