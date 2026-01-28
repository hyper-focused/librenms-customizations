# Known Limitations and Working/Non-Working OIDs

**Date**: January 17, 2026  
**Firmware Tested**: IronWare Version 08.0.30u (FCX648, ICX6450-48)

---

## Critical Finding: Stack MIBs Don't Work

**All stack MIBs under `.1.3.6.1.4.1.1991.1.1.3.31` return "No Such Object" on firmware 08.0.30u**, even on actual stacked switches:
- ICX6450-48: 2-switch stack
- FCX648: 6-switch stack

---

## Working OIDs ✅

| OID | Description | Status | Notes |
|-----|-------------|--------|-------|
| `.1.3.6.1.2.1.1.1.0` | sysDescr | ✅ Works | Contains "Stacking System" |
| `.1.3.6.1.2.1.1.2.0` | sysObjectID | ✅ Works | `.1.3.6.1.4.1.1991.1.3.48.X.Y` |
| `.1.3.6.1.2.1.2.2` | ifTable | ✅ Works | Interface information |
| `.1.3.6.1.4.1.1991.1.1.2.1.1.0` | snStackMemberCount | ⚠️ Returns 1 | Unreliable - returns 1 even on 6-stack |
| `.1.3.6.1.4.1.1991.1.1.2.1.3.0` | snStackPortCount | ⚠️ Returns 1 | Unreliable |
| `.1.3.6.1.4.1.1991.1.1.3` | Foundry Agent Config | ✅ Works | Various agent settings |

---

## Non-Working OIDs ❌

| OID | Description | Error | Impact |
|-----|-------------|-------|--------|
| `.1.3.6.1.4.1.1991.1.1.3.31.1.1.0` | snStackingGlobalConfigState | No Such Object | Cannot detect stack state |
| `.1.3.6.1.4.1.1991.1.1.3.31.1.2.0` | snStackingGlobalTopology | No Such Object | Cannot detect topology |
| `.1.3.6.1.4.1.1991.1.1.3.31.1.3.0` | snStackingGlobalMacAddress | No Such Object | Cannot get stack MAC |
| `.1.3.6.1.4.1.1991.1.1.3.31.3.1` | snStackingOperUnitTable | No Such Object | Cannot enumerate members |
| `.1.3.6.1.4.1.1991.1.1.3.31.2.1` | snStackingConfigUnitTable | No Such Object | Cannot get config |
| `.1.3.6.1.4.1.1991.1.1.2.1.2` | snStackMemberTable | No Such Instance | Cannot get member details |
| `.1.3.6.1.4.1.1991.1.1.2.1.4` | snStackPortTable | No Such Object | Cannot get port details |
| `.1.3.6.1.4.1.1991.1.1.1.4.1.1` | snChasUnitTable | No Such Instance | Cannot get chassis info |
| `.1.3.6.1.2.1.47.1.1.1` | entPhysicalTable | No Such Object | Cannot use ENTITY-MIB |

---

## Alternative Detection Methods

Since standard MIBs don't work, the implementation uses:

### 1. Interface-Based Detection ✅

**Method**: Parse interface names for stack ports
- Pattern: `Stack1/1`, `Stack1/2`, `Stack2/1`, etc.
- Unit ID from first number, port from second number
- Count unique units to determine stack size

**Limitation**: Requires interfaces to be discovered first

### 2. sysName Parsing ✅

**Method**: Parse sysName for stack indicators
- Pattern: `"h08-h05_stack"` suggests 2-unit stack
- Count hyphens before `_stack` suffix

**Limitation**: Not all devices use this naming convention

### 3. Configuration Table Fallback ⚠️

**Method**: Try `snStackingConfigUnitTable` as alternative
- Status: Also doesn't work on 08.0.30u
- Kept for future firmware compatibility

---

## Firmware Version Impact

**Tested**: IronWare Version 08.0.30u (compiled Apr 23 2020)

**Unknown**:
- Do newer firmware versions expose stack MIBs?
- Do different IronWare/FastIron versions use different OIDs?
- Are there firmware-specific MIB implementations?

**Recommendation**: Test with multiple firmware versions to find working OIDs.

---

## Workarounds Implemented

1. **Stack-Capable Detection**: Uses sysDescr "Stacking System" pattern
2. **Interface Parsing**: Detects stack via interface names
3. **sysName Analysis**: Extracts stack info from device name
4. **Graceful Degradation**: Falls back to standalone mode if no stack detected
5. **Database Records**: Still creates topology/member records with available data

---

## Recommendations

1. **Test with Different Firmware**: Newer versions may expose stack MIBs
2. **Contact Vendor**: Request correct MIB documentation for stack features
3. **Use Alternative Methods**: Interface-based detection is most reliable
4. **Document Findings**: Update this file as new OIDs are discovered

---

## Testing Checklist

- [ ] Test with firmware 08.0.30u (current - stack MIBs don't work)
- [ ] Test with newer FastIron versions (09.x, 10.x)
- [ ] Test with different ICX models (7150, 7250, 7450, 7750)
- [ ] Test with different stack sizes (2, 4, 6, 8, 12 units)
- [ ] Verify interface-based detection works
- [ ] Verify sysName parsing works
- [ ] Document any working stack OIDs found
