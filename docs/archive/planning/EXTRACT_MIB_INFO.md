# Guide: Extracting Key Information from MIB Files

This guide helps extract critical information from the device-specific MIBs for implementation.

## Priority Information Needed

### 1. sysObjectID Values (CRITICAL)

These unique identifiers are essential for OS detection.

**How to find**:
```bash
# Search for product registration section in each MIB
grep -B 5 -A 10 "registration\|products\|OBJECT IDENTIFIER ::=" FCXR08030u.mib

# Look for patterns like:
# fcx624 OBJECT IDENTIFIER ::= { something X }
# fcx648 OBJECT IDENTIFIER ::= { something Y }

# Or search for "sysObjectID" assignments:
grep -i "sysobjectid" *.mib
```

**What we need**:
```
FCX624:     .1.3.6.1.4.1.1991.1.3.51.X
FCX648:     .1.3.6.1.4.1.1991.1.3.52.X
ICX6400:    .1.3.6.1.4.1.1588.2.1.1.1.3.X
ICX6450-24: .1.3.6.1.4.1.1588.2.1.1.1.3.X
ICX6450-48: .1.3.6.1.4.1.1588.2.1.1.1.3.X
ICX6610:    .1.3.6.1.4.1.1588.2.1.1.1.3.X
ICX6650:    .1.3.6.1.4.1.1588.2.1.1.1.3.X
ICX7250-24: .1.3.6.1.4.1.1588.2.1.1.1.3.X
ICX7250-48: .1.3.6.1.4.1.1588.2.1.1.1.3.X
ICX7750-26Q: .1.3.6.1.4.1.1588.2.1.1.1.3.X
ICX7750-48F: .1.3.6.1.4.1.1588.2.1.1.1.3.X
```

### 2. Stack OID Verification (HIGH)

Confirm stack OIDs are consistent across platforms.

**How to find**:
```bash
# Search for stacking definitions
grep -A 20 "snStacking\|stackingGlobal\|stackingOper" *.mib

# Verify these exist in each platform:
# snStackingGlobalTopology
# snStackingOperUnitTable
# snStackingOperUnitRole
# snStackingOperUnitState
```

**Expected OID**: `.1.3.6.1.4.1.1991.1.1.3.31.*`

### 3. Enterprise OID Definition (HIGH)

Verify which enterprise OID(s) each platform uses.

**How to find**:
```bash
# Look at the top of each MIB file for enterprise definition
head -50 *.mib | grep -i "enterprise\|foundry\|brocade"

# Look for:
# foundry OBJECT IDENTIFIER ::= { enterprises 1991 }
# brocade OBJECT IDENTIFIER ::= { enterprises 1588 }
```

### 4. Model Description Strings (MEDIUM)

Get the exact text used in sysDescr for each model.

**How to find**:
```bash
# Search for description text
grep -i "description\|descr" *.mib | grep -i "fcx\|icx"

# Or look for sysDescr definitions
```

**What we need**:
- How "FCX624" appears in sysDescr
- How "ICX7250-48P" appears in sysDescr
- Whether it says "Foundry", "Brocade", or "Ruckus"
- Whether it says "IronWare" or "FastIron"

## Quick Extraction Script

Save this as `extract_mib_info.sh`:

```bash
#!/bin/bash

# Extract key info from all MIBs
echo "=== Extracting MIB Information ==="

for mib in *.mib; do
    echo ""
    echo "=== Processing $mib ==="
    
    # Enterprise OID
    echo "--- Enterprise Definition ---"
    grep -m 5 "OBJECT IDENTIFIER ::= { enterprises" "$mib" || echo "Not found"
    
    # Product registration
    echo "--- Product Registration ---"
    grep -A 2 "registration\|products.*OBJECT IDENTIFIER" "$mib" | head -20
    
    # Stack OIDs
    echo "--- Stack OIDs ---"
    grep -c "snStacking" "$mib" && echo "Stack OIDs present" || echo "No stack OIDs"
    
    # sysObjectID assignments
    echo "--- sysObjectID Assignments ---"
    grep -E "::=.*\{.*(registration|products)" "$mib" | head -10
    
    echo ""
done
```

## Information Template

For each platform, fill in this template:

### FCX624
```yaml
platform: FCX624
sysObjectID: .1.3.6.1.4.1.1991.1.3.51.?
sysDescr_pattern: "Foundry Networks, Inc. FCX624"
enterprise_oid: 1991
os_version_string: "IronWare Version"
stack_support: true
max_stack_size: 8
stack_oid_base: .1.3.6.1.4.1.1991.1.1.3.31
notes: ""
```

### ICX7250-48
```yaml
platform: ICX7250-48
sysObjectID: .1.3.6.1.4.1.1588.2.1.1.1.3.?
sysDescr_pattern: "Brocade ICX7250-48 Switch" or "Ruckus ICX 7250-48"
enterprise_oid: 1588 (primary), 1991 (compatible)
os_version_string: "FastIron Version" or "IronWare Version"
stack_support: true
max_stack_size: 12
stack_oid_base: .1.3.6.1.4.1.1991.1.1.3.31 (backward compatible)
notes: "Uses Foundry stack MIBs for compatibility"
```

## Manual Extraction Steps

If you can open the MIB files, please extract:

### Step 1: Open each MIB and find the header
Look for the MODULE-IDENTITY section:
```
SOME-MIB DEFINITIONS ::= BEGIN

IMPORTS
    ...
    
foundry OBJECT IDENTIFIER ::= { enterprises 1991 }
-- or --
brocade OBJECT IDENTIFIER ::= { enterprises 1588 }
```

### Step 2: Find the products section
```
products OBJECT IDENTIFIER ::= { foundry 1 }
registration OBJECT IDENTIFIER ::= { products 1 }

-- Then look for model definitions like:
fcx624Switch OBJECT IDENTIFIER ::= { registration 51 }
fcx648Switch OBJECT IDENTIFIER ::= { registration 52 }
```

### Step 3: Find stack definitions
```
snStacking OBJECT IDENTIFIER ::= { snSwitch 31 }

snStackingGlobalObjects OBJECT IDENTIFIER ::= { snStacking 1 }
snStackingConfigUnit OBJECT IDENTIFIER ::= { snStacking 2 }
snStackingOperUnit OBJECT IDENTIFIER ::= { snStacking 3 }
```

### Step 4: Document findings
Create a text file with:
```
MIB: FCXR08030u.mib
Enterprise: 1991 (Foundry)
Products:
  - fcx624Switch: 51
  - fcx648Switch: 52
Stack OIDs: Present at .1.3.6.1.4.1.1991.1.1.3.31
```

## Alternative: SNMP Walk from Real Devices

If MIB parsing is difficult, we can get this info from real devices:

```bash
# Get sysObjectID
snmpget -v2c -c public device.example.com .1.3.6.1.2.1.1.2.0

# Get sysDescr
snmpget -v2c -c public device.example.com .1.3.6.1.2.1.1.1.0

# Test stack OID
snmpget -v2c -c public device.example.com .1.3.6.1.4.1.1991.1.1.3.31.1.2.0
```

## What to Share

Please provide any of the following:

1. **grep output** from the MIB files:
   ```bash
   grep -E "OBJECT IDENTIFIER ::=|registration|products" FCXR08030u.mib
   ```

2. **First 100 lines** of each MIB (headers and definitions)

3. **SNMP walks** from actual devices if available

4. **Copy/paste** of the product registration section from each MIB

This information is critical for accurate device detection!
