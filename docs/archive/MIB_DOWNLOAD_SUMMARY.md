# MIB Files Download and OID Correction Summary

**Date**: January 17, 2026  
**Source**: LibreNMS GitHub Repository  
**URL**: https://github.com/librenms/librenms/tree/master/mibs/brocade

---

## ✅ MIB Files Downloaded

Successfully downloaded **7 MIB files** from the LibreNMS repository:

### From `mibs/brocade/` directory:

1. **FOUNDRY-SN-STACKING-MIB** (435 lines, 13KB)
   - Source: `https://raw.githubusercontent.com/librenms/librenms/master/mibs/brocade/FOUNDRY-SN-STACKING-MIB`
   - Location: `mibs/foundry/FOUNDRY-SN-STACKING-MIB`
   - **Purpose**: Stack-specific OIDs (primary focus for stack discovery)

2. **FOUNDRY-SN-SWITCH-GROUP-MIB** (9,172 lines, 249KB)
   - Source: `https://raw.githubusercontent.com/librenms/librenms/master/mibs/brocade/FOUNDRY-SN-SWITCH-GROUP-MIB`
   - Location: `mibs/foundry/FOUNDRY-SN-SWITCH-GROUP-MIB`
   - **Purpose**: Switch-specific OIDs including stacking base definitions

3. **FOUNDRY-SN-AGENT-MIB** (6,455 lines, 175KB)
   - Source: `https://raw.githubusercontent.com/librenms/librenms/master/mibs/brocade/FOUNDRY-SN-AGENT-MIB`
   - Location: `mibs/foundry/FOUNDRY-SN-AGENT-MIB`
   - **Purpose**: Agent and system information

4. **FOUNDRY-SN-ROOT-MIB** (1,615 lines, 123KB)
   - Source: `https://raw.githubusercontent.com/librenms/librenms/master/mibs/brocade/FOUNDRY-SN-ROOT-MIB`
   - Location: `mibs/foundry/FOUNDRY-SN-ROOT-MIB`
   - **Purpose**: Root MIB with enterprise definitions

### From `mibs/foundry/` directory (secondary location):

5. **FOUNDRY-SN-MAC-AUTHENTICATION-MIB** (256 lines, 7.8KB)
   - Source: `https://raw.githubusercontent.com/librenms/librenms/master/mibs/foundry/FOUNDRY-SN-MAC-AUTHENTICATION-MIB`
   - Location: `mibs/foundry/FOUNDRY-SN-MAC-AUTHENTICATION-MIB`
   - **Purpose**: MAC authentication features

6. **FOUNDRY-SN-MAC-VLAN-MIB** (285 lines, 8.0KB)
   - Source: `https://raw.githubusercontent.com/librenms/librenms/master/mibs/foundry/FOUNDRY-SN-MAC-VLAN-MIB`
   - Location: `mibs/foundry/FOUNDRY-SN-MAC-VLAN-MIB`
   - **Purpose**: MAC VLAN features

7. **FOUNDRY-SN-MRP-MIB** (347 lines, 12KB)
   - Source: `https://raw.githubusercontent.com/librenms/librenms/master/mibs/foundry/FOUNDRY-SN-MRP-MIB`
   - Location: `mibs/foundry/FOUNDRY-SN-MRP-MIB`
   - **Purpose**: Multiple Registration Protocol (MRP) features

**Total**: 7 MIB files, ~588KB

---

## 🔍 Critical Findings: Incorrect OID Paths

After analyzing the actual MIB files, **3 OID paths in the code were incorrect**:

### 1. Stack MAC Address OID ❌→✅

**Before (WRONG)**:
```php
private const OID_STACK_MAC = '.1.3.6.1.4.1.1991.1.1.3.31.1.3.0';
```

**After (CORRECT)**:
```php
private const OID_STACK_MAC = '.1.3.6.1.4.1.1991.1.1.3.31.1.2.0';
```

**MIB Definition**: `snStackingGlobalMacAddress ::= { snStackingGlobalObjects 2 }`

### 2. Stack Topology OID ❌→✅

**Before (WRONG)**:
```php
private const OID_STACK_TOPOLOGY = '.1.3.6.1.4.1.1991.1.1.3.31.1.2.0';
```

**After (CORRECT)**:
```php
private const OID_STACK_TOPOLOGY = '.1.3.6.1.4.1.1991.1.1.3.31.1.5.0';
```

**MIB Definition**: `snStackingGlobalTopology ::= { snStackingGlobalObjects 5 }`

### 3. Operational Unit Table OID ❌→✅

**Before (WRONG)**:
```php
private const OID_STACK_OPER_TABLE = '.1.3.6.1.4.1.1991.1.1.3.31.3.1';
```

**After (CORRECT)**:
```php
private const OID_STACK_OPER_TABLE = '.1.3.6.1.4.1.1991.1.1.3.31.2.2';
```

**MIB Definition**: `snStackingOperUnitTable ::= { snStackingTableObjects 2 }`

---

## 📋 Complete OID Structure (Verified)

### Base Paths
- `snSwitch` = `.1.3.6.1.4.1.1991.1.1.3` (from FOUNDRY-SN-SWITCH-GROUP-MIB)
- `snStacking` = `.1.3.6.1.4.1.1991.1.1.3.31` (from FOUNDRY-SN-STACKING-MIB)

### Global Objects (`.1.3.6.1.4.1.1991.1.1.3.31.1`)
1. `snStackingGlobalConfigState` = `.1.3.6.1.4.1.1991.1.1.3.31.1.1` ✅
2. `snStackingGlobalMacAddress` = `.1.3.6.1.4.1.1991.1.1.3.31.1.2` ✅ **FIXED**
3. `snStackingGlobalPersistentMacTimerState` = `.1.3.6.1.4.1.1991.1.1.3.31.1.3`
4. `snStackingGlobalPersistentMacTimer` = `.1.3.6.1.4.1.1991.1.1.3.31.1.4`
5. `snStackingGlobalTopology` = `.1.3.6.1.4.1.1991.1.1.3.31.1.5` ✅ **FIXED**

### Table Objects (`.1.3.6.1.4.1.1991.1.1.3.31.2`)
1. `snStackingConfigUnitTable` = `.1.3.6.1.4.1.1991.1.1.3.31.2.1` ✅
2. `snStackingOperUnitTable` = `.1.3.6.1.4.1.1991.1.1.3.31.2.2` ✅ **FIXED**

---

## 🔧 Code Changes Applied

### 1. Fixed OID Constants (`LibreNMS/OS/BrocadeStack.php`)

Updated the OID constants with correct paths and added comments explaining the structure.

### 2. Updated YAML Sensor Definitions (`resources/definitions/os_discovery/brocade-stack.yaml`)

Updated commented-out sensor definitions with correct OID paths for future use.

### 3. Created Documentation

- `docs/MIB_OID_CORRECTIONS.md` - Detailed OID correction analysis
- `MIB_DOWNLOAD_SUMMARY.md` - This file

---

## ⚠️ Important Note

**These OID corrections may not immediately resolve the "No Such Object" errors** because:

1. The MIBs may still not be implemented in firmware 08.0.30u
2. However, **if the MIBs do exist**, we were querying the wrong OIDs before
3. The corrected OIDs should work on newer firmware versions that implement these MIBs

---

## 📝 Next Steps

1. ✅ Download MIB files (completed)
2. ✅ Analyze OID structure (completed)
3. ✅ Fix OID constants in code (completed)
4. ✅ Update YAML definitions (completed)
5. ⏳ **Test with corrected OIDs on real devices**
6. ⏳ **Test with newer firmware versions** (if available)

---

## 📚 References

- **Primary MIB Source**: https://github.com/librenms/librenms/tree/master/mibs/brocade
- **Secondary MIB Source**: https://github.com/librenms/librenms/tree/master/mibs/foundry
- **MIB Analysis**: See `docs/MIB_OID_CORRECTIONS.md`
- **Limitations**: See `docs/LIMITATIONS.md`

---

## Summary

✅ **7 MIB files downloaded** from LibreNMS repository (4 from `mibs/brocade/`, 3 from `mibs/foundry/`)  
✅ **3 OID paths corrected** in code  
✅ **YAML definitions updated** with correct OIDs  
✅ **Documentation created** for future reference  

The code now uses **correct OID paths** as defined in the official MIB files. If the stack MIBs are implemented in newer firmware versions, the corrected OIDs should work properly.

**Note**: The additional MIBs (MAC-AUTHENTICATION, MAC-VLAN, MRP) don't contain stack-related OIDs but are included for completeness and potential future use.
