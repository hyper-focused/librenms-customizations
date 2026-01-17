# Stack-Aware Sensor Monitoring Analysis

## Current Sensor Monitoring Review

### Issues to Address:

1. **PSU Monitoring**: snChasPwrSupplyTable - Is this per-unit indexed?
2. **Fan Monitoring**: snChasFanTable - Is this per-unit indexed?
3. **Temperature**: snAgentTempTable vs snAgentTemp2Table - Which is per-unit?
4. **PoE**: ✅ Already has per-unit (snAgentPoeUnitTable)

## MIB Analysis

### Chassis Tables Structure:

**snChasPwrSupplyTable** (.1.3.6.1.4.1.1991.1.1.1.2.1.1):
- Index: Likely unit.psu (e.g., 1.1, 1.2, 2.1)
- Per-unit indexed in stacked systems

**snChasFanTable** (.1.3.6.1.4.1.1991.1.1.1.3.1.1):
- Index: Likely unit.fan (e.g., 1.1, 1.2, 2.1)
- Per-unit indexed in stacked systems

**snAgentTempTable** (.1.3.6.1.4.1.1991.1.1.2.13.1.1):
- Older format, might not be stack-aware

**snAgentTemp2Table** (.1.3.6.1.4.1.1991.1.1.2.13.3.1):
- Newer format with subindexes
- subindex0 = unit ID
- subindex1 = sensor ID
- Stack-aware ✅

## Recommendations

### 1. PSU Monitoring - Already Stack-Aware ✅

The snChasPwrSupplyTable uses multi-level indexing:
- Index format: unit.psu
- Example: 1.1 (unit 1, PSU 1), 2.1 (unit 2, PSU 1)
- Current monitoring captures all units automatically

**Enhancement**: Add unit ID to description for clarity

### 2. Fan Monitoring - Already Stack-Aware ✅

The snChasFanTable uses multi-level indexing:
- Index format: unit.fan
- Automatically monitors all stack members

**Enhancement**: Include unit ID in description

### 3. Temperature - Use snAgentTemp2Table ✅

Already using snAgentTemp2Table which is stack-aware:
- descr: 'Unit {{ $subindex0 }} Sensor {{ $subindex1 }}'
- Properly shows unit ID

**Current implementation is correct!**

### 4. PoE - Already Per-Unit ✅

Using snAgentPoeUnitTable:
- Explicitly per-unit
- descr: 'Unit {{ $index }} ...'

**Current implementation is correct!**

### 5. Optical Monitoring - Interface-Based

snIfOpticalMonitoringInfoTable:
- Indexed by interface
- Interfaces in stack are already unit-prefixed (1/1/1 = unit 1, port 1)
- Stack-aware via interface naming

**Current implementation is correct!**

## Enhancements Needed

### Add Explicit Per-Unit Monitoring:

1. **Per-Unit CPU**: Already have from Foundry base
2. **Per-Unit Memory**: Add explicit per-unit memory
3. **Per-Unit PSU**: Clarify unit in description
4. **Per-Unit Fan**: Clarify unit in description
5. **Per-Unit Temperature**: ✅ Already good
