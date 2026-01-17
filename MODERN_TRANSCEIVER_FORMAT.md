# Modern Transceiver Format Implementation

**Date**: January 17, 2026  
**Status**: ✅ **Updated to Modern LibreNMS Format**

---

## 🔍 Key Discovery

Modern LibreNMS implementations (iOS, Junos, etc.) use specific parameters to make transceiver data appear in the **Ports → Transceivers** tab instead of cluttering the general Sensors page.

---

## 🎯 Modern Format Requirements

### Critical Parameters:

```yaml
sensors:
    dbm:  # or current, temperature, etc.
        -
            oid: <transceiver table>
            value: <transceiver metric>
            descr: '{{ $ifDescr }} <metric>'
            group: transceiver              ← CRITICAL
            entPhysicalIndex: '{{ $index }}' ← CRITICAL
            entPhysicalIndex_measured: 'ports' ← CRITICAL
            skip_values: 0
```

### What These Do:

**`group: transceiver`**:
- Groups all transceiver-related sensors together
- Separates from chassis sensors (PSU, fans, etc.)
- Enables transceiver-specific display logic

**`entPhysicalIndex: '{{ $index }}'`**:
- Links sensor to physical port entity
- Enables correlation with interface

**`entPhysicalIndex_measured: 'ports'`**:
- ⭐ **KEY PARAMETER** ⭐
- Tells LibreNMS this is port/transceiver data
- Makes it appear in Ports → Transceivers tab
- Not just in Sensors tab

**`skip_values: 0`**:
- Don't create sensors for value = 0
- Avoids sensors for empty ports

---

## ✅ Updates Made to brocade-stack.yaml

### Optical Power (dBm):

**Before** (Old Format):
```yaml
dbm:
    -
        descr: '{{ $ifDescr }} Rx Power'
        index: 'snIfOpticalMonitoringRxPower.{{ $index }}'
        group: 'Optical Power'
```

**After** (Modern Format):
```yaml
dbm:
    -
        descr: '{{ $ifDescr }} Rx Power'
        index: 'rx-{{ $index }}'              ← Cleaner naming
        entPhysicalIndex: '{{ $index }}'      ← Port association
        entPhysicalIndex_measured: 'ports'    ← Transceiver tab display
        group: transceiver                     ← Grouped with transceivers
        skip_values: 0                         ← Skip empty ports
```

### Bias Current (mA):

**Before**:
```yaml
current:
    -
        descr: '{{ $ifDescr }} Tx Bias Current'
        group: 'Optical Current'
```

**After**:
```yaml
current:
    -
        descr: '{{ $ifDescr }} Tx Bias'
        index: 'bias-{{ $index }}'
        entPhysicalIndex: '{{ $index }}'
        entPhysicalIndex_measured: 'ports'
        group: transceiver
        skip_values: 0
```

### Temperature (°C):

**Before**:
```yaml
temperature:
    -
        descr: '{{ $ifDescr }} Transceiver'
        group: 'Transceiver Temperatures'
```

**After**:
```yaml
temperature:
    -
        descr: '{{ $ifDescr }} Temp'
        index: 'temp-{{ $index }}'
        entPhysicalIndex: '{{ $index }}'
        entPhysicalIndex_measured: 'ports'
        group: transceiver
        skip_values: 0
```

---

## 📊 Display Comparison

### Old Format (Cluttered):

**Sensors Tab**:
```
├─ Chassis Sensors
│  ├─ Unit 1 PSU 1
│  ├─ Unit 1 Fan 1
│  ├─ Unit 1 Temperature
│  ├─ 1/1/1 Rx Power        ← Mixed with chassis sensors
│  ├─ 1/1/1 Tx Power        ← Hard to find
│  ├─ 1/1/2 Rx Power        ← Cluttered
│  └─ ... (100+ transceiver sensors)
```

### Modern Format (Organized):

**Sensors Tab**:
```
├─ Chassis Sensors
│  ├─ Unit 1 PSU 1
│  ├─ Unit 1 Fan 1
│  ├─ Unit 1 Temperature
│  └─ ... (only chassis sensors)
```

**Ports → Transceivers Tab** ⭐:
```
Port 1/1/1:
  ├─ Rx Power: -5.2 dBm
  ├─ Tx Power: -3.1 dBm
  ├─ Temperature: 45°C
  └─ Bias Current: 25 mA

Port 1/1/2:
  ├─ Rx Power: -4.8 dBm
  ├─ Tx Power: -2.9 dBm
  └─ ...

Port 2/1/1: (Unit 2)
  ├─ Rx Power: -5.0 dBm
  └─ ...
```

**Much Better Organization!** ✅

---

## 🎯 Benefits of Modern Format

### For Users:

1. ✅ **Transceiver data on Ports page** (where it belongs)
2. ✅ **Organized by port** (easy to find)
3. ✅ **Separate from chassis sensors** (less clutter)
4. ✅ **All transceiver metrics together** (Rx, Tx, temp, bias in one place)

### For Stack Monitoring:

1. ✅ **Unit 1 ports** (1/1/1 through 1/1/48)
2. ✅ **Unit 2 ports** (2/1/1 through 2/1/48)
3. ✅ **Clear unit identification** (via interface naming)
4. ✅ **Per-port transceiver health** (easy comparison)

---

## ✅ All Transceiver Sensors Updated

### Updated Sensors (Modern Format):

| Sensor Type | Group | entPhysicalIndex_measured | Display Location |
|-------------|-------|---------------------------|------------------|
| **Rx Power (dBm)** | transceiver | ports | ✅ Ports → Transceivers |
| **Tx Power (dBm)** | transceiver | ports | ✅ Ports → Transceivers |
| **Temperature (°C)** | transceiver | ports | ✅ Ports → Transceivers |
| **Bias Current (mA)** | transceiver | ports | ✅ Ports → Transceivers |

### Chassis Sensors (Unchanged):

| Sensor Type | Group | Display Location |
|-------------|-------|------------------|
| **PSU** | Power Supply | Sensors |
| **Fan** | Fan Status | Sensors |
| **Chassis Temp** | Stack Unit Temperatures | Sensors |
| **PoE** | Unit PoE | Sensors |

**Result**: Clean separation! ✅

---

## 📋 Modernization Checklist

- [x] Add `group: transceiver` to all optical sensors
- [x] Add `entPhysicalIndex: '{{ $index }}'` to link to ports
- [x] Add `entPhysicalIndex_measured: 'ports'` for transceiver tab display
- [x] Add `skip_values: 0` to avoid empty port sensors
- [x] Use consistent index naming (`rx-`, `tx-`, `temp-`, `bias-`)
- [x] Shorter descriptions (Rx Power, Tx Bias, Temp)

---

## 🎯 Result

**Transceiver data will now appear in**:
1. ✅ **Device → Ports → Transceivers tab** (primary display)
2. ✅ **Device → Graphs** (for historical trending)
3. ✅ **Alert Rules** (for threshold-based alerts)

**Separated from**:
- ✅ Chassis sensors (PSU, fans, chassis temp)
- ✅ Stack status sensors
- ✅ PoE sensors

**Following modern LibreNMS format used by**:
- ✅ iOS/IOS-XE (Cisco)
- ✅ Junos (Juniper)
- ✅ Other modern implementations

**Status**: ✅ Modernized and ready!
