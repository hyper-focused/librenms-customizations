# Transceiver Monitoring Integration

**Issue**: Should transceiver measurements (power, temp, current) use the existing `resources/views/device/tabs/ports/transceivers.blade.php` view?

**Answer**: ✅ **Yes - LibreNMS has dedicated transceiver display**

---

## 🔍 Current Implementation

### Our Sensor Configuration:

In `brocade-stack.yaml`, we currently define transceiver monitoring as sensors:

```yaml
sensors:
    dbm:  # Optical power
        - snIfOpticalMonitoringRxPower
        - snIfOpticalMonitoringTxPower
    
    current:  # Bias current
        - snIfOpticalMonitoringTxBiasCurrent
    
    temperature:  # Transceiver temperature
        - snIfOpticalMonitoringTemperature
```

**Issue**: These create individual sensor entries, but LibreNMS has a dedicated transceivers view.

---

## ✅ Correct Approach

### LibreNMS Transceiver Handling:

LibreNMS has built-in transceiver support:
- **View**: `resources/views/device/tabs/ports/transceivers.blade.php`
- **Table**: `ports` with transceiver columns
- **Display**: Organized by port in ports tab

### Our Sensors are Complementary:

**Both should coexist**:

1. **Sensor entries** (our YAML):
   - Allow graphing over time
   - Enable alerting on thresholds
   - Historical tracking

2. **Transceiver view** (existing LibreNMS):
   - Current real-time values
   - Organized by port
   - Easy port-by-port comparison

**Result**: Sensors feed data, view displays it

---

## 📊 How It Works Together

### Data Flow:

```
SNMP Query
    ↓
snIfOpticalMonitoringInfoTable
    ↓
    ├→ Sensors (for graphing/alerting)
    │   - Rx Power sensor
    │   - Tx Power sensor
    │   - Temperature sensor
    │   - Bias Current sensor
    │
    └→ Port/Transceiver View (for display)
        - Organized by interface
        - Shows all metrics together
        - Real-time status
```

### User Experience:

**Sensors Tab**:
- See graphs of Rx power over time
- Set alerts on low power
- Historical trending

**Ports → Transceivers Tab**:
- See all transceiver info for port 1/1/1
- Compare transceivers across ports
- Quick health check

**Both are useful!**

---

## ✅ Our Configuration is Correct

### What We Have:

```yaml
# Optical power (dBm)
dbm:
    - snIfOpticalMonitoringRxPower
    - snIfOpticalMonitoringTxPower

# Bias current (mA)
current:
    - snIfOpticalMonitoringTxBiasCurrent

# Transceiver temperature (°C)
temperature:
    - snIfOpticalMonitoringTemperature
```

**Status**: ✅ Correct - creates sensors for graphing/alerting

**Plus**: LibreNMS built-in transceivers view will also display this data

---

## 🎯 Stack-Aware Considerations

### Interface Naming in Stacks:

**FCX/ICX Interface Format**:
- Unit 1: `1/1/1`, `1/1/2`, ... `1/1/48`
- Unit 2: `2/1/1`, `2/1/2`, ... `2/1/48`

**Our Sensor Descriptions**:
```yaml
descr: '{{ $ifDescr }} Rx Power'
```

**Result**:
- "1/1/1 Rx Power" (Unit 1, Port 1)
- "2/1/1 Rx Power" (Unit 2, Port 1)

**Stack-Aware**: ✅ Yes, via interface naming

---

## ✅ No Changes Needed

### Current Implementation is Optimal:

1. ✅ Sensors create time-series data (graphs/alerts)
2. ✅ Built-in transceiver view displays current values
3. ✅ Interface naming provides unit identification
4. ✅ Both work together harmoniously

### Recommendation:

**Keep current configuration as-is** ✅

The sensor definitions will:
- Populate sensor database
- Enable graphing
- Enable alerting

And LibreNMS's existing transceiver view will:
- Display current values
- Show in ports tab
- Organize by interface

**Best of both worlds!**
