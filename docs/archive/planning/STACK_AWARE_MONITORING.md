# Stack-Aware Monitoring Implementation

**Date**: January 17, 2026  
**Status**: ✅ Enhanced for Proper Per-Unit Monitoring

---

## 🎯 Stack-Aware Sensor Monitoring

### Analysis Complete

Reviewed all sensor monitoring in `brocade-stack.yaml` to ensure proper per-unit monitoring in stacked configurations.

---

## ✅ Sensors Already Stack-Aware

### 1. Temperature Monitoring ✅

**snAgentTemp2Table** (already stack-aware):
```yaml
descr: 'Unit {{ $subindex0 }}/{{ $subindex1 }} {{ $snAgentTemp2SensorDescr }}'
```
- **subindex0**: Unit ID (1, 2, 3, etc.)
- **subindex1**: Slot/module ID
- **Result**: ✅ Already monitoring each stack unit separately

### 2. PoE Monitoring ✅

**snAgentPoeUnitTable** (explicitly per-unit):
```yaml
descr: 'Unit {{ $index }} Total PoE Power'
descr: 'Unit {{ $index }} Free PoE Power'
```
- **Index**: Unit ID
- **Result**: ✅ Already monitoring each stack unit separately

### 3. Optical Transceiver Monitoring ✅

**snIfOpticalMonitoringInfoTable** (interface-based):
```yaml
descr: '{{ $ifDescr }} Rx Power'
```
- **Interface naming**: Unit/Slot/Port (e.g., "1/1/1", "2/1/1")
- **Result**: ✅ Stack-aware via interface ID, shows unit number

### 4. Stack Status Monitoring ✅

**snStackingOperUnitTable** (per-unit state):
```yaml
descr: 'Unit {{ $index }} State'
descr: 'Unit {{ $index }} Stack Port 1 → Unit {{ $snStackingOperUnitNeighbor1 }}'
```
- **Index**: Unit ID
- **Result**: ✅ Already monitoring each stack member separately

---

## ✅ Enhancements Made

### 1. PSU Monitoring - ENHANCED ✅

**Before**:
```yaml
descr: 'Power Supply {{ $index }}'
```

**After**:
```yaml
descr: 'Unit {{ $subindex0 }} {{ $snChasPwrSupplyDescription }}'
```

**Improvement**:
- ✅ Index format: unit.psu (e.g., 1.1, 2.1)
- ✅ Description now shows unit ID explicitly
- ✅ Shows PSU description from MIB
- ✅ Stack-aware via multi-level indexing

**Example Output**:
- "Unit 1 Power Supply 1"
- "Unit 2 Power Supply 1"

### 2. Fan Monitoring - ENHANCED ✅

**Before**:
```yaml
descr: '{{ $snChasFanDescription }}'
```

**After**:
```yaml
descr: 'Unit {{ $subindex0 }} {{ $snChasFanDescription }}'
```

**Improvement**:
- ✅ Index format: unit.fan (e.g., 1.1, 2.1)
- ✅ Description now shows unit ID explicitly
- ✅ Stack-aware via multi-level indexing

**Example Output**:
- "Unit 1 Fan 1"
- "Unit 2 Fan 1"

### 3. Memory Monitoring - ENHANCED ✅

**Before**:
```yaml
descr: '{{ FOUNDRY-SN-AGENT-MIB::snAgentBrdMainBrdDescription }}'
```

**After**:
```yaml
descr: 'Unit {{ $index }} {{ FOUNDRY-SN-AGENT-MIB::snAgentBrdMainBrdDescription }}'
```

**Improvement**:
- ✅ Shows unit ID in description
- ✅ Per-module memory already stack-aware via index
- ✅ Clearer identification of which unit

**Example Output**:
- "Unit 1 FCX648-S"
- "Unit 2 FCX648-S"

### 4. Optical Monitoring - ENHANCED ✅

**Added group labels**:
```yaml
group: 'Optical Power'
group: 'Optical Current'
```

**Improvement**:
- ✅ Better organization in UI
- ✅ Already stack-aware (interface-based)
- ✅ Interface names show unit (1/1/1, 2/1/1)

---

## 📊 Stack-Aware OID Summary

### Chassis Tables (Multi-Level Indexed):

| Table | OID Base | Index Format | Stack-Aware | Enhanced |
|-------|----------|--------------|-------------|----------|
| **snChasPwrSupplyTable** | .1.3.6.1.4.1.1991.1.1.1.2.1.1 | unit.psu | ✅ Yes | ✅ Added unit in descr |
| **snChasFanTable** | .1.3.6.1.4.1.1991.1.1.1.3.1.1 | unit.fan | ✅ Yes | ✅ Added unit in descr |
| **snAgentTemp2Table** | .1.3.6.1.4.1.1991.1.1.2.13.3.1 | unit.slot.sensor | ✅ Yes | ✅ Already good |
| **snChasUnitTable** | .1.3.6.1.4.1.1991.1.1.1.4.1.1 | unit | ✅ Yes | ✅ Used in class |

### PoE Tables (Explicitly Per-Unit):

| Table | OID Base | Index | Stack-Aware | Status |
|-------|----------|-------|-------------|--------|
| **snAgentPoeUnitTable** | .1.3.6.1.4.1.1991.1.1.2.14.4.1.1 | unit | ✅ Yes | ✅ Already good |
| **snAgentPoePortTable** | .1.3.6.1.4.1.1991.1.1.2.14.2.2.1 | port | ✅ Yes | ✅ Via ifDescr |

### Interface-Based (Stack-Aware via ifDescr):

| Table | OID Base | Index | Stack-Aware | Status |
|-------|----------|-------|-------------|--------|
| **snIfOpticalMonitoringInfoTable** | .1.3.6.1.4.1.1991.1.1.3.3.6.1 | interface | ✅ Yes | ✅ Enhanced |

### Stack Tables (Explicitly Per-Unit):

| Table | OID Base | Index | Stack-Aware | Status |
|-------|----------|-------|-------------|--------|
| **snStackingOperUnitTable** | .1.3.6.1.4.1.1991.1.1.3.31.2.2.1 | unit | ✅ Yes | ✅ Already good |

---

## ✅ Verification

### All Critical Sensors are Stack-Aware:

1. ✅ **CPU**: Per-unit via snAgentCpuUtilTable (from Foundry base class)
2. ✅ **Memory**: Per-unit via index, enhanced with unit ID in description
3. ✅ **Temperature**: Per-unit via snAgentTemp2Table (unit.slot.sensor index)
4. ✅ **PSU**: Per-unit via multi-level index (unit.psu), enhanced description
5. ✅ **Fan**: Per-unit via multi-level index (unit.fan), enhanced description
6. ✅ **PoE**: Explicitly per-unit via snAgentPoeUnitTable
7. ✅ **Optical**: Interface-based (interfaces show unit ID: 1/1/1, 2/1/1)
8. ✅ **Stack State**: Explicitly per-unit via snStackingOperUnitTable
9. ✅ **Stack Ports**: Per-unit stack port state monitoring

---

## 🎯 Index Format Reference

### Multi-Level Indexes in Stacks:

**Format**: `unit.component.subcomponent`

**Examples**:
- PSU: `1.1` = Unit 1, PSU 1 | `2.1` = Unit 2, PSU 1
- Fan: `1.1` = Unit 1, Fan 1 | `2.2` = Unit 2, Fan 2
- Temp: `1.1.1` = Unit 1, Slot 1, Sensor 1
- Interface: `1/1/1` = Unit 1, Slot 1, Port 1

### LibreNMS Variable Extraction:

- `{{ $index }}` = Full index (e.g., "1.1" or "1.1.1")
- `{{ $subindex0 }}` = First level (unit ID)
- `{{ $subindex1 }}` = Second level (component ID)
- `{{ $subindex2 }}` = Third level (sub-component ID)

---

## ✅ Result

**All sensors are now explicitly stack-aware**:

- ✅ PSU descriptions show unit ID
- ✅ Fan descriptions show unit ID
- ✅ Temperature descriptions show unit ID
- ✅ Memory descriptions show unit ID
- ✅ PoE already showed unit ID
- ✅ Optical already stack-aware via interface
- ✅ Stack status explicitly per-unit

**Monitoring will properly track each stack member separately!** ✅
