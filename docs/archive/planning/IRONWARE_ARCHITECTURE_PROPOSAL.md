# LibreNMS IronWare Architecture Improvement Proposal

**Date**: January 17, 2026  
**Issue**: Current LibreNMS lumps all IronWare devices into single OS  
**Proposal**: Split into logical device families

---

## 🔍 Current Problem

### LibreNMS "ironware" OS Currently Includes:

From official `Ironware.php` hardware mappings (650+ entries):

1. **NetIron** - Modular chassis routers
   - NetIron MLX (MLX-4, MLX-8, MLX-16, MLX-32)
   - NetIron XMR (XMR4000, XMR8000, XMR16000, XMR32000)
   - NetIron CER/CES (2024, 2048)
   - NetIron 400, 800, 1500, 4802

2. **BigIron** - Large modular chassis switches/routers
   - BigIron RX (RX4, RX8, RX16, RX32)
   - BigIron SuperX
   - BigIron MG8
   - BigIron 4000, 8000, 15000

3. **ServerIron** - Application delivery controllers / load balancers
   - ServerIron 100, 350, 450, 850
   - ServerIron GT
   - ServerIron4G
   - ServerIronXL

4. **FastIron/EdgeIron/FCX/ICX** - Stackable campus switches
   - FastIron (FI, FI2, FI3, FWS, FLS, FES)
   - FastIron SuperX (800, 1600)
   - FCX series (FCX624, FCX648)
   - ICX series (6430, 6450, 6610, 6650, 7150, 7250, 7450, 7750)

5. **SecureIron** - Security appliances
   - SecureIronLS
   - SecureIronTM

6. **TurboIron** - Layer 3 switches
   - TurboIron
   - TurboIron-X24
   - TurboIron SuperX

**Problem**: These are **fundamentally different device types** with vastly different:
- Hardware architecture (modular chassis vs fixed switches)
- Purpose (routing vs switching vs load balancing)
- Capabilities (BGP, MPLS, stacking, ADC features)
- Management (different MIBs, different features)
- Monitoring needs (different metrics matter)

---

## 🎯 Proposed Architecture Split

### Proposal: Three Logical OSes

#### 1. `ironware-router` (Chassis-Based Routers)

**Devices**:
- NetIron family (MLX, XMR, CER, CES)
- BigIron family (RX, SuperX, MG8)

**Characteristics**:
- Modular chassis
- High-end routing (BGP, MPLS, IPv6)
- Line cards and modules
- 10G/40G/100G uplinks
- Core/aggregation focus
- No stacking

**Key Features**:
- Module inventory
- Line card monitoring
- MPLS metrics
- BGP peer tracking
- High-capacity routing

#### 2. `ironware-adc` (Application Delivery Controllers)

**Devices**:
- ServerIron family (all variants)

**Characteristics**:
- Load balancing
- Application delivery
- Health checking
- SSL offload
- Virtual servers

**Key Features**:
- Virtual server monitoring
- Real server health
- Load balancing pools
- Application metrics
- ADC-specific sensors

#### 3. `brocade-stack` (Stackable Switches) ⭐ **OUR FOCUS**

**Devices**:
- FastIron family (FI, FI2, FI3, FES, FLS, FWS)
- EdgeIron family
- FCX series (624, 648)
- ICX series (6430, 6450, 6610, 6650, 7150, 7250, 7450, 7750)

**Characteristics**:
- Fixed configuration switches
- Virtual chassis stacking
- Campus access/aggregation
- 1G/10G/40G switching
- PoE support

**Key Features**:
- **Stack topology detection**
- **Per-unit inventory**
- **Stack health monitoring**
- **Master/member tracking**
- Port-based switching metrics

**Status**: ✅ **Our implementation targets this!**

---

## 📊 Comparison: Current vs Proposed

### Current (Single OS):

```
ironware (everything)
├── NetIron (routers)
├── BigIron (chassis switches)
├── ServerIron (load balancers)
├── FastIron (stackable switches)
├── FCX (stackable switches)
├── ICX (stackable switches)
└── TurboIron (L3 switches)
```

**Problem**: 
- ❌ One-size-fits-all monitoring
- ❌ Can't optimize for device type
- ❌ Stack features wasted on routers
- ❌ Router features wasted on switches
- ❌ 650+ hardware mappings in one class

### Proposed (Logical Split):

```
IronWare Platform
├── ironware-router
│   ├── NetIron (modular routers)
│   └── BigIron (modular switches)
│
├── ironware-adc
│   └── ServerIron (load balancers)
│
└── brocade-stack ⭐ OUR IMPLEMENTATION
    ├── FastIron (stackable)
    ├── FCX (stackable)
    └── ICX (stackable)
```

**Benefits**:
- ✅ Appropriate monitoring per device type
- ✅ Stack features only for stackable devices
- ✅ Router metrics only for routers
- ✅ ADC metrics only for load balancers
- ✅ Cleaner, focused codebase

---

## 🎯 Our Implementation Fits Perfectly

### `brocade-stack` OS (What We Built):

**Target Devices**: Stackable switches only
- ✅ FCX series
- ✅ ICX series
- ✅ FastIron stackable models

**Features**: Stack-specific
- ✅ Stack topology detection (ring/chain)
- ✅ Per-unit inventory
- ✅ Master/member roles
- ✅ Stack port monitoring
- ✅ Visual topology

**Not Applicable To**: Chassis routers, load balancers

**Perfect Fit**: ✅ Our OS is exactly what's needed for the stackable switch family!

---

## 📋 Detection Strategy for Split

### brocade-stack (Stackable Switches):

```yaml
os: brocade-stack
discovery:
  - sysDescr:
      - Stacking System  # Specific to stackable switches
  - sysObjectID:
      - .1.3.6.1.4.1.1991.1.3.48  # FCX/ICX pattern
```

**Catches**: FCX, ICX, stackable FastIron

### ironware-router (Chassis Routers):

```yaml
os: ironware-router
discovery:
  - sysDescr:
      - NetIron
      - BigIron
  - sysObjectID:
      - .1.3.6.1.4.1.1991.1.3.1  # NetIron pattern
      - .1.3.6.1.4.1.1991.1.3.2  # BigIron pattern
```

**Catches**: NetIron MLX/XMR, BigIron RX

### ironware-adc (Load Balancers):

```yaml
os: ironware-adc
discovery:
  - sysDescr:
      - ServerIron
  - sysObjectID:
      - .1.3.6.1.4.1.1991.1.3.6  # ServerIron pattern
```

**Catches**: ServerIron family

### ironware (Fallback/Legacy):

```yaml
os: ironware
discovery:
  - sysDescr:
      - IronWare  # Generic catch-all
```

**Catches**: Anything else IronWare-based

---

## 💡 Why This Split Makes Sense

### Device Type Differences:

| Aspect | NetIron/BigIron | ServerIron | FCX/ICX Stack |
|--------|----------------|------------|---------------|
| **Form Factor** | Modular chassis | Chassis/Appliance | Fixed stackable |
| **Purpose** | Core routing | Load balancing | Campus switching |
| **Key Feature** | MPLS, BGP | Virtual servers | Stacking |
| **Management** | Line cards | Server pools | Stack members |
| **Monitoring Focus** | Routing tables | Health checks | Stack topology |
| **Typical Deployment** | Core/WAN | Data center | Access/Aggregation |
| **Port Density** | Variable (modules) | 10G+ uplinks | 24/48 ports |

### Monitoring Differences:

**Chassis Routers Need**:
- ✅ Module/line card status
- ✅ Routing protocol metrics
- ✅ MPLS statistics
- ✅ Per-module sensors
- ❌ Stack topology (not stackable)

**Load Balancers Need**:
- ✅ Virtual server status
- ✅ Real server health
- ✅ Pool statistics
- ✅ SSL metrics
- ❌ Stack topology (not stackable)

**Stackable Switches Need**:
- ✅ **Stack topology** ⭐
- ✅ **Per-unit inventory** ⭐
- ✅ **Stack port status** ⭐
- ✅ Port-level metrics
- ❌ MPLS metrics (not typical)
- ❌ Virtual servers (not ADC)

---

## 📊 Proposed Implementation Roadmap

### Phase 1: brocade-stack (Our Current Work) ✅

**Status**: Complete and ready

**Scope**: Stackable switches (FCX/ICX)

**Files**:
- BrocadeStack.php
- brocade-stack detection/discovery YAMLs
- Stack-specific models and views

**Timeline**: Ready now

### Phase 2: ironware-router (Future)

**Scope**: Chassis routers (NetIron, BigIron)

**Files**:
- IronwareRouter.php (new class)
- ironware-router.yaml files
- Module inventory tables
- Routing-specific monitoring

**Timeline**: Future enhancement

### Phase 3: ironware-adc (Future)

**Scope**: Load balancers (ServerIron)

**Files**:
- IronwareAdc.php (new class)
- ironware-adc.yaml files
- Virtual server tables
- ADC-specific monitoring

**Timeline**: Future enhancement

### Phase 4: ironware (Legacy Support)

**Scope**: Catch-all for older/unclassified devices

**Files**:
- Keep existing Ironware.php as fallback
- Generic monitoring only

---

## 🎯 Our Contribution Aligns Perfectly

### What We're Proposing:

**New OS**: `brocade-stack`

**Purpose**: Stack-capable switches (FCX/ICX/FastIron)

**Differentiation from current "ironware"**:
- Focused on stackable switches only
- Enhanced stack topology
- Not trying to handle chassis routers
- Not trying to handle load balancers

**Benefits to LibreNMS**:
1. ✅ Better device classification
2. ✅ Appropriate monitoring per device type
3. ✅ Foundation for further splits (router, ADC)
4. ✅ Cleaner architecture overall

---

## 📋 Community Discussion Points

### For LibreNMS Discord/GitHub:

**Topic**: "Proposal: Split IronWare OS into logical device families"

**Context**:
> The current "ironware" OS handles NetIron routers, BigIron chassis, ServerIron load balancers, and FCX/ICX switches in one OS. These device types have vastly different capabilities and monitoring needs.

**Proposal**:
1. **Phase 1**: Create `brocade-stack` OS for stackable switches (FCX/ICX)
   - Enhanced stack topology
   - Per-unit inventory
   - Stack-specific monitoring
   - We have working implementation ready

2. **Phase 2**: Split chassis routers to separate OS
   - Better module monitoring
   - Routing-focused metrics

3. **Phase 3**: Split load balancers to separate OS
   - ADC-specific monitoring
   - Virtual server tracking

**Benefits**:
- Appropriate monitoring for each device type
- Cleaner code organization
- Better user experience
- Easier maintenance

**Backward Compatibility**:
- Keep "ironware" as fallback
- Gradual migration
- No breaking changes

---

## 📊 Device Family Analysis

### Family 1: Chassis Routers (ironware-router)

**Hardware**:
- NetIron MLX-4/8/16/32
- NetIron XMR4000/8000/16000/32000
- NetIron CER/CES 2024/2048
- BigIron RX4/8/16/32

**Key Characteristics**:
- Modular chassis (multiple slots)
- Line cards/modules
- High-end routing (BGP, OSPF, MPLS)
- Core/edge router deployment
- 10/40/100G interfaces

**Monitoring Needs**:
- Module status and inventory
- Routing table size
- BGP peer status
- MPLS tunnel metrics
- Per-module CPU/memory
- SFP/QSFP optical levels

**NOT Applicable**:
- ❌ Stack topology (chassis, not stackable)
- ❌ PoE (routers don't have PoE)
- ❌ Member units (single chassis)

### Family 2: Application Delivery Controllers (ironware-adc)

**Hardware**:
- ServerIron 100/350/450/850
- ServerIron GT
- ServerIron4G
- ServerIronXL

**Key Characteristics**:
- Load balancing
- Health checking
- SSL offload
- Application delivery
- Virtual server management

**Monitoring Needs**:
- Virtual server status
- Real server health checks
- Connection counts
- Load balancing algorithms
- SSL certificate status
- Application response times

**NOT Applicable**:
- ❌ Stack topology (not stackable)
- ❌ Campus switching features
- ❌ VLAN trunking (L4-7 focus)

### Family 3: Stackable Switches (brocade-stack) ⭐ **OUR FOCUS**

**Hardware**:
- FastIron (FI, FI2, FI3, FES, FLS, FWS, FGS)
- EdgeIron
- FCX series (624, 648)
- ICX series (6430, 6450, 6610, 6650, 7150, 7250, 7450, 7750)

**Key Characteristics**:
- Fixed configuration switches
- Virtual chassis stacking (2-12 units)
- Campus access/aggregation
- 1G/10G/40G switching
- PoE/PoE+ support
- Layer 2/3 switching

**Monitoring Needs**:
- ✅ **Stack topology** (ring vs chain)
- ✅ **Stack member status**
- ✅ **Per-unit inventory**
- ✅ **Stack port health**
- ✅ **Master/member roles**
- ✅ Port utilization
- ✅ PoE consumption
- ✅ VLAN configuration

**Why Our Implementation Fits**:
- ✅ Focused on stack features
- ✅ Per-unit tracking
- ✅ Visual topology
- ✅ Appropriate for this family

---

## 🎯 Recommended Split Strategy

### Step 1: Introduce brocade-stack (Our Work) ⭐

**Action**: Add new OS for stackable switches

**Detection**:
```yaml
os: brocade-stack
discovery:
  - sysDescr:
      - Stacking System  # Specific to stackable
  - sysObjectID:
      - .1.3.6.1.4.1.1991.1.3.48  # FCX/ICX pattern
```

**Impact**:
- ✅ Catches stackable FCX/ICX
- ✅ Enhanced stack monitoring
- ✅ No impact on existing devices

**Timeline**: Ready now (our implementation)

### Step 2: Create ironware-router (Future)

**Action**: New OS for chassis routers

**Detection**:
```yaml
os: ironware-router
discovery:
  - sysDescr:
      - NetIron
      - BigIron
```

**Impact**:
- ✅ Better routing metrics
- ✅ Module-specific monitoring
- ✅ Removes routers from generic ironware

**Timeline**: Future work

### Step 3: Create ironware-adc (Future)

**Action**: New OS for load balancers

**Detection**:
```yaml
os: ironware-adc
discovery:
  - sysDescr:
      - ServerIron
```

**Impact**:
- ✅ ADC-specific monitoring
- ✅ Virtual server tracking
- ✅ Load balancing metrics

**Timeline**: Future work

### Step 4: Keep ironware as Fallback

**Action**: Maintain for legacy/unclassified

**Detection**:
```yaml
os: ironware
discovery:
  - sysDescr:
      - IronWare  # Catch anything not classified above
```

**Impact**:
- ✅ Backward compatibility
- ✅ Handles edge cases
- ✅ No breaking changes

---

## 💡 Benefits of This Architecture

### For LibreNMS:

1. **Better Classification** ⭐⭐⭐
   - Devices categorized by type, not just vendor
   - Appropriate features per category

2. **Optimized Monitoring** ⭐⭐⭐
   - Stack monitoring for switches
   - Routing metrics for routers
   - ADC metrics for load balancers

3. **Cleaner Code** ⭐⭐
   - Focused classes (not 650+ hardware mappings in one)
   - Easier to maintain
   - Better separation of concerns

4. **Better UX** ⭐⭐
   - Users see relevant metrics
   - Stack topology for switches
   - Routing tables for routers
   - Virtual servers for ADCs

### For Community:

1. **Easier Contributions** ⭐⭐
   - Clear which OS to enhance
   - Focused scope per OS

2. **Better Documentation** ⭐⭐
   - Device-specific guides
   - Relevant examples

3. **Future Extensibility** ⭐⭐⭐
   - Easy to add new device types
   - Clear patterns to follow

---

## 📊 Migration Strategy

### Phase 1: brocade-stack (Immediate) ✅

**What**: Add brocade-stack OS for stackable switches

**Impact**: 
- New devices with "Stacking System" → brocade-stack
- Existing devices remain ironware
- No breaking changes

**Risk**: 🟢 Very Low (additive only)

### Phase 2: ironware-router (3-6 months)

**What**: Split chassis routers

**Impact**:
- NetIron/BigIron → ironware-router
- Better routing metrics

**Risk**: 🟡 Medium (device reclassification)

### Phase 3: ironware-adc (6-12 months)

**What**: Split load balancers

**Impact**:
- ServerIron → ironware-adc
- ADC-specific monitoring

**Risk**: 🟡 Medium (device reclassification)

### Phase 4: Cleanup (12+ months)

**What**: ironware becomes minimal fallback

**Impact**:
- Most devices reclassified
- ironware handles unknowns only

**Risk**: 🟢 Low (gradual transition)

---

## 🎯 Community Proposal Document

### Title: "Proposal: Logical Split of IronWare OS Family"

### Summary:

The current "ironware" OS encompasses fundamentally different device types:
- Chassis routers (NetIron, BigIron)
- Load balancers (ServerIron)
- Stackable switches (FCX, ICX, FastIron)

**Problem**: One-size-fits-all monitoring doesn't serve any device type well.

**Proposal**: Split into logical families:
1. **brocade-stack**: Stackable switches (ready now)
2. **ironware-router**: Chassis routers (future)
3. **ironware-adc**: Load balancers (future)
4. **ironware**: Legacy fallback

**Benefits**:
- Appropriate monitoring per device type
- Better user experience
- Cleaner architecture
- Easier maintenance

**Migration**:
- Gradual, non-breaking
- Start with brocade-stack
- ironware remains as fallback
- Device-specific detection

**We have**: Working brocade-stack implementation ready to submit

---

## 📋 Immediate Action Items

### For brocade-stack OS:

1. **Submit PR** with brocade-stack implementation
   - Detection YAML
   - Discovery YAML
   - BrocadeStack.php class
   - Models and migrations
   - Test data

2. **Include in PR description**:
   - Rationale for split
   - Device type differences
   - Future split vision
   - Non-breaking approach

3. **Community Discussion**:
   - Propose full split architecture
   - Get feedback on approach
   - Plan future phases
   - Coordinate with maintainers

---

## ✅ Our Implementation Status

### What We Have (brocade-stack):

**Complete**:
- ✅ OS detection (unified YAML)
- ✅ OS discovery (unified YAML)
- ✅ OS class (BrocadeStack.php)
- ✅ Database schema
- ✅ Eloquent models
- ✅ Web interface
- ✅ Test data (FCX648, ICX6450-48)

**Coverage**:
- ✅ FCX series
- ✅ ICX 6xxx series
- ✅ ICX 7xxx series

**Quality**:
- ✅ Real device verified
- ✅ LibreNMS compliant
- ✅ Fully consolidated
- ✅ Production ready

**Fits Into**:
- ✅ Proposed architecture split
- ✅ Logical device family
- ✅ Clear purpose
- ✅ Non-breaking

---

## 📊 Summary

### Your Insight is Correct ✅

The current ironware OS lumps together:
- Chassis routers (NetIron, BigIron)
- Load balancers (ServerIron)
- Stackable switches (FCX, ICX, FastIron)

**This doesn't make architectural sense!**

### Our Solution Aligns Perfectly ✅

**brocade-stack OS**:
- Focused on stackable switch family
- Enhanced stack monitoring
- Appropriate for FCX/ICX
- Foundation for proper split

### Proposal to LibreNMS ✅

1. Accept brocade-stack (phase 1)
2. Plan ironware-router split (phase 2)
3. Plan ironware-adc split (phase 3)
4. Keep ironware as fallback

**Our implementation is the first step in a proper architectural improvement for LibreNMS!**

---

**Status**: Proposal ready, implementation complete  
**Architecture**: Logical and well-reasoned  
**Community**: Ready for discussion  

**This is a significant architectural improvement opportunity!** 🚀
