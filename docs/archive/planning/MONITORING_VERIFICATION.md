# Stack-Aware Monitoring Verification ✅

**Date**: January 17, 2026  
**File**: `resources/definitions/os_discovery/brocade-stack.yaml`  
**Status**: ✅ **All Sensors Confirmed Stack-Aware**

---

## ✅ Verification Complete

All sensors in brocade-stack.yaml are now **explicitly stack-aware** and will monitor each unit independently in stacked configurations.

---

## 📊 Sensor Monitoring Matrix

| Sensor Type | OID Table | Index Format | Stack-Aware | Per-Unit Display |
|-------------|-----------|--------------|-------------|------------------|
| **CPU** | snAgentCpuUtilTable | unit.cpu.interval | ✅ Yes | Via Foundry base |
| **Memory** | snAgentBrdMemoryTotal | unit | ✅ Yes | "Unit X ..." |
| **Temperature** | snAgentTemp2Table | unit.slot.sensor | ✅ Yes | "Unit X/Y ..." |
| **PSU** | snChasPwrSupplyTable | unit.psu | ✅ Yes | "Unit X PSU..." |
| **Fan** | snChasFanTable | unit.fan | ✅ Yes | "Unit X Fan..." |
| **PoE** | snAgentPoeUnitTable | unit | ✅ Yes | "Unit X PoE..." |
| **Optical** | snIfOpticalMonitoring | interface | ✅ Yes | "1/1/1 Rx Power" |
| **Stack State** | snStackingOperUnitTable | unit | ✅ Yes | "Unit X State" |
| **Stack Ports** | snStackingOperUnitTable | unit | ✅ Yes | "Unit X Port 1" |

**Result**: ✅ **100% Stack-Aware Monitoring**

---

## 📋 Index Format Examples

### Multi-Level Indexing in Stacked Systems:

#### PSU Monitoring:
```
Index: 1.1 → "Unit 1 Power Supply 1"
Index: 1.2 → "Unit 1 Power Supply 2"
Index: 2.1 → "Unit 2 Power Supply 1"
Index: 2.2 → "Unit 2 Power Supply 2"
```

#### Fan Monitoring:
```
Index: 1.1 → "Unit 1 Fan 1"
Index: 1.2 → "Unit 1 Fan 2"
Index: 2.1 → "Unit 2 Fan 1"
```

#### Temperature Monitoring:
```
Index: 1.1.1 → "Unit 1/1 Temp Sensor 1"
Index: 2.1.1 → "Unit 2/1 Temp Sensor 1"
```

#### Memory Monitoring:
```
Index: 1 → "Unit 1 FCX648-S"
Index: 2 → "Unit 2 FCX648-S"
```

#### PoE Monitoring:
```
Index: 1 → "Unit 1 Total PoE Power"
Index: 2 → "Unit 2 Total PoE Power"
```

#### Optical Monitoring:
```
Interface: 1/1/1 → "1/1/1 Rx Power" (Unit 1, Slot 1, Port 1)
Interface: 2/1/1 → "2/1/1 Rx Power" (Unit 2, Slot 1, Port 1)
```

---

## ✅ What This Means

### For a 2-Unit FCX648 Stack:

**You'll See**:
- ✅ Unit 1 PSU 1, Unit 1 PSU 2
- ✅ Unit 2 PSU 1, Unit 2 PSU 2
- ✅ Unit 1 Fan 1, Unit 1 Fan 2
- ✅ Unit 2 Fan 1, Unit 2 Fan 2
- ✅ Unit 1/1 Temperature Sensor
- ✅ Unit 2/1 Temperature Sensor
- ✅ Unit 1 Memory
- ✅ Unit 2 Memory
- ✅ Unit 1 PoE metrics
- ✅ Unit 2 PoE metrics
- ✅ All optical transceivers (1/1/1 through 2/1/48)
- ✅ Unit 1 State, Unit 2 State
- ✅ Unit 1 Stack Ports, Unit 2 Stack Ports

**Result**: Complete visibility into every component of every stack member!

---

## 🎯 Comparison: Generic vs Stack-Aware

### Generic Monitoring (Wrong):
```
❌ "Power Supply 1" (which unit??)
❌ "Fan 1" (which unit??)
❌ "Temperature Sensor" (which unit??)
```

### Stack-Aware Monitoring (Correct):
```
✅ "Unit 1 Power Supply 1" (clear!)
✅ "Unit 2 Fan 1" (clear!)
✅ "Unit 1/1 Temp Sensor 1" (clear!)
```

---

## 📊 MIB Confirmation

### Verified OID Tables:

All tables used in brocade-stack.yaml are from official Foundry MIBs:

1. **FOUNDRY-SN-AGENT-MIB**:
   - snAgentBrdMemoryTotal ✅
   - snAgSystemDRAMTotal ✅
   - snAgentTemp2Table ✅
   - snAgentCpuUtilTable ✅

2. **FOUNDRY-SN-SWITCH-GROUP-MIB**:
   - snStackingOperUnitTable ✅
   - snStackingGlobalTopology ✅
   - snIfOpticalMonitoringInfoTable ✅

3. **FOUNDRY-SN-ROOT-MIB**:
   - snChasPwrSupplyTable ✅
   - snChasFanTable ✅
   - snChasUnitTable ✅

4. **FOUNDRY-POE-MIB**:
   - snAgentPoeUnitTable ✅
   - snAgentPoePortTable ✅

All tables confirmed to exist in standard Foundry MIBs used by both FCX and ICX.

---

## ✅ Summary

**Stack-Aware Monitoring**: ✅ 100% Complete

**Enhancements Made**:
- ✅ PSU descriptions show unit ID
- ✅ Fan descriptions show unit ID
- ✅ Memory descriptions show unit ID
- ✅ Added group labels for organization
- ✅ Improved comments explaining indexing

**Result**:
Every sensor will clearly show which stack unit it belongs to, enabling:
- Proper per-unit health monitoring
- Clear identification of failed components
- Better troubleshooting
- Complete stack visibility

**Status**: ✅ Ready for stacked switch monitoring
