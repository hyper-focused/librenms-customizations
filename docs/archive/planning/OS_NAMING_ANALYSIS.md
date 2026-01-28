# OS Naming Strategy Analysis

**Issue**: "ironware" OS already exists in LibreNMS  
**Need**: Distinct OS name for our enhanced stack discovery

---

## 🔍 Device Analysis from Real Data

### Our Verified Devices:

**FCX648**:
```
sysDescr: "Brocade Communications Systems, Inc. Stacking System FCX648..."
```

**ICX6450-48**:
```
sysDescr: "Brocade Communications Systems, Inc. Stacking System ICX6450-48..."
```

### Common Characteristics:
- ✅ Both say "**Brocade Communications Systems**"
- ✅ Both say "**Stacking System**"
- ✅ Both use "IronWare" or "FastIron" OS
- ✅ Both support virtual chassis stacking

---

## 💡 OS Name Options

### Option 1: `brocade-stack` ⭐⭐⭐⭐⭐ (RECOMMENDED)

**Pros**:
- ✅ Covers both FCX and ICX
- ✅ "Stacking System" in sysDescr makes it clear
- ✅ Brocade branding is common to all our devices
- ✅ Distinguishes from generic "ironware"
- ✅ Clearly indicates stack focus

**Cons**:
- ⚠️ Might catch standalone devices too (but we can filter)

**Detection**:
```yaml
os: brocade-stack
discovery:
  - sysDescr:
      - Stacking System
  - sysObjectID:
      - .1.3.6.1.4.1.1991.1.3.48  # Our verified pattern
```

### Option 2: `ironware-stack` ⭐⭐⭐⭐

**Pros**:
- ✅ Clear relation to ironware OS
- ✅ Indicates stack-specific features
- ✅ Covers both FCX and ICX

**Cons**:
- ⚠️ Still contains "ironware" (potential confusion)

### Option 3: `fastiron-stack` ⭐⭐⭐

**Pros**:
- ✅ Modern FastIron OS name
- ✅ Stack focus clear

**Cons**:
- ❌ User noted this doesn't apply well to ICX
- ❌ Our devices show "IronWare" not "FastIron"

### Option 4: `brocade-fastiron` ⭐⭐⭐

**Pros**:
- ✅ Modern branding
- ✅ Vendor + OS name

**Cons**:
- ❌ Our devices show "IronWare" not "FastIron"
- ❌ Doesn't emphasize stack feature

### Option 5: Separate OSes ⭐⭐

**brocade-fcx** and **brocade-icx**:

**Pros**:
- ✅ Clear platform differentiation

**Cons**:
- ❌ More OSes to maintain
- ❌ User wants unified approach
- ❌ Code duplication

---

## 🎯 Recommendation: `brocade-stack`

### Rationale:

1. **Unique Detection Criteria**: "Stacking System" in sysDescr
   - Our FCX648: "Stacking System FCX648" ✅
   - Our ICX6450: "Stacking System ICX6450-48" ✅
   
2. **Covers All Platforms**:
   - FCX series ✅
   - ICX 6450 series ✅
   - ICX 7xxx series ✅
   
3. **Distinct from "ironware"**:
   - ironware: Generic Brocade devices
   - brocade-stack: Specifically stacked configurations
   
4. **Clear Purpose**:
   - Stack topology discovery
   - Per-unit inventory
   - Visual topology

### Detection Logic:

```yaml
# brocade-stack (our new OS)
discovery:
  - sysDescr:
      - Stacking System
  - sysObjectID:
      - .1.3.6.1.4.1.1991.1.3.48

# versus

# ironware (existing OS)  
discovery:
  - sysDescr:
      - IronWare
```

**Result**: Devices with "Stacking System" → `brocade-stack`  
**Result**: Other IronWare devices → `ironware`

---

## 📊 Detection Priority

### Detection Order:

1. **brocade-stack** (new, specific)
   - Checks for "Stacking System" + sysObjectID pattern
   - Higher priority / more specific

2. **ironware** (existing, generic)
   - Checks for "IronWare"
   - Catches everything else

### Example Scenarios:

**Stacked FCX648**:
```
sysDescr: "Brocade ... Stacking System FCX648 ..."
Match: brocade-stack ✅ (has "Stacking System")
```

**Standalone FCX624** (hypothetical):
```
sysDescr: "Brocade ... FCX624, IronWare ..."
Match: ironware ✅ (no "Stacking System")
```

**Stacked ICX7150**:
```
sysDescr: "Ruckus ... Stacking System ICX7150 ..."
Match: brocade-stack ✅ (has "Stacking System")
```

---

## ✅ Final Recommendation

**OS Name**: `brocade-stack`

**Benefits**:
- ✅ Unified (covers FCX + ICX)
- ✅ Distinct from "ironware"
- ✅ Clear purpose (stack focus)
- ✅ Matches device sysDescr ("Stacking System")
- ✅ Simple and memorable

**Implementation**:
- Single unified OS definition
- Single discovery YAML
- Single OS class
- Stack-specific features
