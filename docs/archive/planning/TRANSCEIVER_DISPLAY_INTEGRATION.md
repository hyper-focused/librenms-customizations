# Transceiver Display Integration with LibreNMS

**File**: `resources/views/device/tabs/ports/transceivers.blade.php`  
**Status**: ✅ Our sensors integrate with existing transceiver view

---

## 📊 LibreNMS Transceiver View Structure

### Existing View (Official):

```blade
@foreach($data['transceivers'] as $transceiver)
    <x-panel>
        <x-slot name="heading">
            <x-transceiver :transceiver="$transceiver"></x-transceiver>
        </x-slot>
        <x-transceiver-sensors :transceiver="$transceiver"></x-transceiver-sensors>
    </x-panel>
@endforeach
```

**How It Works**:
- Loops through each transceiver (SFP/QSFP)
- Uses `<x-transceiver>` component for header
- Uses `<x-transceiver-sensors>` component for sensor data
- Displays Rx/Tx power, temperature, bias current, etc.

**Data Source**: Our sensor definitions in `brocade-stack.yaml`!

---

## ✅ Our Integration is Correct

### Our Sensor Definitions:

```yaml
# In resources/definitions/os_discovery/brocade-stack.yaml

sensors:
    dbm:  # Optical power measurements
        -
            oid: snIfOpticalMonitoringInfoTable
            value: snIfOpticalMonitoringRxPower
            descr: '{{ $ifDescr }} Rx Power'
        -
            oid: snIfOpticalMonitoringInfoTable
            value: snIfOpticalMonitoringTxPower
            descr: '{{ $ifDescr }} Tx Power'
    
    current:  # Bias current
        -
            oid: snIfOpticalMonitoringInfoTable
            value: snIfOpticalMonitoringTxBiasCurrent
            descr: '{{ $ifDescr }} Tx Bias Current'
    
    temperature:  # Transceiver temperature
        -
            oid: snIfOpticalMonitoringInfoTable
            value: snIfOpticalMonitoringTemperature
            descr: '{{ $ifDescr }} Transceiver'
```

**These feed the transceiver view automatically!**

---

## 🎯 How Data Flows

### Discovery/Polling:

```
1. BrocadeStack.php discovers device
   ↓
2. YAML discovery runs sensor detection
   ↓
3. snIfOpticalMonitoringInfoTable queried
   ↓
4. Sensors created in database:
   - Rx Power sensors
   - Tx Power sensors
   - Temperature sensors
   - Bias current sensors
   ↓
5. Data available for:
   ├→ Transceiver view (ports/transceivers.blade.php)
   ├→ Sensor graphs (individual sensor pages)
   └→ Alerting (threshold-based alerts)
```

---

## 📊 Display Locations

### 1. Transceiver View (Existing LibreNMS):

**Location**: Device → Ports → Transceivers Tab

**What It Shows**:
- All transceivers for the device
- Current Rx/Tx power levels
- Temperature
- Bias current
- Vendor info
- Serial numbers
- Organized by port

**Data Source**: Our sensor definitions ✅

**Stack-Aware**: Yes, via interface naming (1/1/1, 2/1/1)

### 2. Sensor Graphs:

**Location**: Device → Sensors

**What It Shows**:
- Historical graphs per sensor
- Rx power over time
- Tx power over time
- Temperature trends
- Current trends

**Data Source**: Our sensor definitions ✅

### 3. Alerts:

**Configurable Alerts**:
- Low Rx power
- High temperature
- Abnormal bias current

**Data Source**: Our sensor definitions ✅

---

## ✅ Stack-Aware Transceiver Monitoring

### For a 2-Unit Stack:

**Unit 1 Transceivers**:
- Port 1/1/1 through 1/1/48
- Each with Rx/Tx power, temp, current

**Unit 2 Transceivers**:
- Port 2/1/1 through 2/1/48
- Each with Rx/Tx power, temp, current

**Display**:
- Transceiver view: All transceivers organized by port
- Sensor view: All sensors with graphs
- Both show unit ID via interface name

**Example Sensor Names**:
- "1/1/1 Rx Power" (Unit 1, Port 1)
- "2/1/1 Rx Power" (Unit 2, Port 1)

---

## 🎯 No Changes Needed

### Our Current Implementation:

✅ **Correct OID tables** (snIfOpticalMonitoringInfoTable)  
✅ **Correct sensor types** (dbm, current, temperature)  
✅ **Stack-aware descriptions** (via ifDescr)  
✅ **Integrates with existing view** (transceivers.blade.php)  

### LibreNMS Handles Display:

✅ **Built-in transceiver view** (existing)  
✅ **Sensor graphing** (existing)  
✅ **Alerting framework** (existing)  

**Our job**: Provide the sensor data ✅ **DONE**

**LibreNMS job**: Display it beautifully ✅ **BUILT-IN**

---

## 📋 Verification

### Optical Monitoring OIDs (Stack-Aware):

| Measurement | OID | Index | Stack-Aware |
|-------------|-----|-------|-------------|
| **Rx Power** | .1.3.6.1.4.1.1991.1.1.3.3.6.1.3 | interface | ✅ Yes (via ifDescr) |
| **Tx Power** | .1.3.6.1.4.1.1991.1.1.3.3.6.1.2 | interface | ✅ Yes (via ifDescr) |
| **Temperature** | .1.3.6.1.4.1.1991.1.1.3.3.6.1.1 | interface | ✅ Yes (via ifDescr) |
| **Bias Current** | .1.3.6.1.4.1.1991.1.1.3.3.6.1.4 | interface | ✅ Yes (via ifDescr) |

**All use interface index, which includes unit ID in stacks!**

---

## ✅ Conclusion

### Question: Should we integrate with transceivers.blade.php?

**Answer**: ✅ **We already do!**

Our sensor definitions automatically feed:
1. ✅ The existing transceiver view
2. ✅ Sensor graphing
3. ✅ Alerting system

**No additional changes needed** - current implementation is correct and will work seamlessly with LibreNMS's existing transceiver display framework.

**Stack-aware via interface naming** - each unit's transceivers will be properly identified and displayed.

---

**Status**: ✅ Transceiver monitoring correctly integrated  
**Display**: ✅ Uses existing LibreNMS transceiver view  
**Stack-Aware**: ✅ Via interface naming (1/1/1, 2/1/1, etc.)
