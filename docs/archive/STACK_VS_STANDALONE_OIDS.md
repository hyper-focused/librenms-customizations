# Stack vs Standalone OID Usage Guide

**Date**: January 17, 2026  
**Based on**: FOUNDRY-SN-AGENT-MIB and FOUNDRY-SN-STACKING-MIB analysis

---

## Overview

The code now uses **different OIDs** based on whether a stack is detected:

- **Stacked Configuration**: Uses unit-indexed table OIDs (supports multiple units)
- **Standalone Configuration**: Uses scalar OIDs (single device, no unit indexing)

---

## Stack Detection Logic

The code determines stack status by:
1. Querying `snStackingGlobalConfigState` (if available)
2. Checking `snStackingOperUnitTable` for multiple units
3. Using alternative detection (interface names, sysName parsing)
4. If no stack detected: Falls back to standalone mode

---

## OID Selection by Configuration Type

### Hardware Information

#### Stacked Configuration (Unit-Indexed Tables)

| Data Type | OID | Format | Example |
|-----------|-----|--------|---------|
| Serial Number | `snChasUnitTable.snChasUnitSerNum` | Table indexed by `snChasUnitIndex` | `.1.3.6.1.4.1.1991.1.1.1.4.1.1.2.1` (unit 1) |
| Description | `snChasUnitTable.snChasUnitPartNum` | Table indexed by `snChasUnitIndex` | `.1.3.6.1.4.1.1991.1.1.1.4.1.1.7.1` (unit 1) |
| Temperature | `snChasUnitTable.snChasUnitActualTemperature` | Table indexed by `snChasUnitIndex` | `.1.3.6.1.4.1.1991.1.1.1.4.1.1.4.1` (unit 1) |

**Usage in Code**:
```php
// Walk the table to get all units
$serials = \SnmpQuery::walk('FOUNDRY-SN-AGENT-MIB::snChasUnitTable')->table();
// Access by unit ID: $serials[$unitId]['snChasUnitSerNum']
```

#### Standalone Configuration (Scalar OIDs)

| Data Type | OID | Format | Example |
|-----------|-----|--------|---------|
| Serial Number | `snChasSerNum.0` | Scalar (no index) | `.1.3.6.1.4.1.1991.1.1.1.4.0` |
| Description | `snChasUnitDescription.1` | Scalar (unit 1 only) | `.1.3.6.1.4.1.1991.1.1.1.4.1.1.3.1` |
| Temperature | `snChasActualTemperature.0` | Scalar (if available) | `.1.3.6.1.4.1.1991.1.1.1.4.0` |

**Usage in Code**:
```php
// Get scalar value directly
$serial = \SnmpQuery::get('FOUNDRY-SN-AGENT-MIB::snChasSerNum.0')->value();
```

---

### CPU Monitoring

#### Both Configurations (Table-Based)

| Configuration | OID | Index Meaning |
|---------------|-----|---------------|
| Stacked | `snAgentCpuUtilTable` | Index = Unit ID (1, 2, 3, etc.) |
| Standalone | `snAgentCpuUtilTable` | Index = 1 (single unit) |

**Usage**: Same OID for both, but:
- **Stacked**: Returns multiple entries (one per unit)
- **Standalone**: Returns single entry (unit 1)

**Code Logic**:
```php
$cpuData = \SnmpQuery::walk('FOUNDRY-SN-AGENT-MIB::snAgentCpuUtilTable')->table();
// Stacked: $cpuData contains [1 => [...], 2 => [...], 3 => [...]]
// Standalone: $cpuData contains [1 => [...]]
```

---

### Memory Monitoring

#### Both Configurations (Mixed)

| Data Type | OID | Stacked Behavior | Standalone Behavior |
|-----------|-----|------------------|---------------------|
| System Memory | `snAgSystemDRAMTotal` | Master unit memory | Single unit memory |
| Per-Unit Memory | `snAgentBrdMemoryTotal` | Indexed by unit ID | Index = 1 only |

**Usage**: 
- System memory: Always scalar (master unit for stacks)
- Per-unit memory: Table-based, automatically adapts

---

### Stack Member Information

#### Stacked Configuration Only

| Data Type | OID | Format |
|-----------|-----|--------|
| Unit Role | `snStackingOperUnitTable.snStackingOperUnitRole` | Table indexed by `snStackingOperUnitIndex` |
| Unit State | `snStackingOperUnitTable.snStackingOperUnitState` | Table indexed by `snStackingOperUnitIndex` |
| Unit MAC | `snStackingOperUnitTable.snStackingOperUnitMac` | Table indexed by `snStackingOperUnitIndex` |
| Unit Priority | `snStackingOperUnitTable.snStackingOperUnitPriority` | Table indexed by `snStackingOperUnitIndex` |
| Unit Version | `snStackingOperUnitTable.snStackingOperUnitImgVer` | Table indexed by `snStackingOperUnitIndex` |

**Full OID Path**: `.1.3.6.1.4.1.1991.1.1.3.31.2.2.1.X.Y`
- `X` = Column (1=Index, 2=Role, 3=MAC, 4=Priority, 5=State, etc.)
- `Y` = Unit ID (1, 2, 3, etc.)

**Example**:
- Unit 1 Role: `.1.3.6.1.4.1.1991.1.1.3.31.2.2.1.2.1`
- Unit 2 Role: `.1.3.6.1.4.1.1991.1.1.3.31.2.2.1.2.2`

---

## Code Implementation

### Stack Detection

```php
// Check if stack is detected
$topology = IronwareStackTopology::where('device_id', $device->device_id)->first();
$isStacked = $topology && $topology->topology !== 'standalone' && $topology->unit_count > 1;
```

### Hardware Discovery

**Stacked**:
```php
// Use unit-indexed table
$serials = \SnmpQuery::walk('FOUNDRY-SN-AGENT-MIB::snChasUnitTable')->table();
foreach ($members as $unitId => $member) {
    $serial = $serials[$unitId]['snChasUnitSerNum'] ?? null;
}
```

**Standalone**:
```php
// Use scalar OID
$serial = \SnmpQuery::get('FOUNDRY-SN-AGENT-MIB::snChasSerNum.0')->value();
```

---

## YAML Configuration

The YAML file (`resources/definitions/os_discovery/brocade-stack.yaml`) uses OIDs that automatically adapt:

- **Memory**: `snAgentBrdMemoryTotal` - Returns multiple entries for stacks, single for standalone
- **CPU**: `snAgentCpuUtilTable` - Same behavior
- **Hardware/Serial**: Handled in PHP code based on stack detection

---

## MIB File References

- **FOUNDRY-SN-AGENT-MIB**: Contains `snChasUnitTable` (stack-aware) and `snChasSerNum.0` (standalone)
- **FOUNDRY-SN-STACKING-MIB**: Contains `snStackingOperUnitTable` (stacked only)

---

## Testing Checklist

- [ ] Verify stacked config uses `snChasUnitTable` with unit IDs
- [ ] Verify standalone config uses `snChasSerNum.0` (scalar)
- [ ] Verify CPU discovery works for both configurations
- [ ] Verify memory discovery works for both configurations
- [ ] Verify stack member discovery only runs when stacked

---

## Notes

1. **Firmware 08.0.30u**: Many stack MIBs don't exist, so alternative detection is used
2. **Newer Firmware**: Should support full stack MIBs with correct OIDs
3. **Automatic Adaptation**: Some OIDs (like CPU/memory tables) automatically work for both configs
4. **Code Override**: PHP code overrides YAML defaults when stack is detected
