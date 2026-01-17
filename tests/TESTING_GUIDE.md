# LibreNMS Testing Guide for IronWare Enhancement

This guide explains how to create proper test data for LibreNMS compliance.

---

## 📋 Official Testing Requirements

Per LibreNMS guidelines, we need:
1. **snmprec files** - SNMP response data
2. **json files** - Database dumps after discovery/polling
3. **Tests must pass** with `lnms dev:check unit`

---

## 📁 Test Files Created

### 1. FCX648 Test Data

**SNMP Data**: `tests/snmpsim/ironware_fcx648.snmprec`
- Based on real FCX648 device
- Firmware: 08.0.30uT7f1
- Configuration: Stacking System
- Verified sysObjectID: `.1.3.6.1.4.1.1991.1.3.48.2.1`

**Database Dump**: `tests/data/ironware_fcx648.json` (to be generated)

### 2. ICX6450-48 Test Data

**SNMP Data**: `tests/snmpsim/ironware_icx6450.snmprec`
- Based on real ICX6450-48 device
- Firmware: 08.0.30uT311
- Configuration: Stacking System
- Verified sysObjectID: `.1.3.6.1.4.1.1991.1.3.48.5.1`

**Database Dump**: `tests/data/ironware_icx6450.json` (to be generated)

---

## 🔧 Generating Database Dumps

### Using LibreNMS Scripts

Once the snmprec files are in place:

```bash
# In LibreNMS repository
cd /path/to/librenms

# Copy our snmprec files
cp /workspace/tests/snmpsim/ironware_fcx648.snmprec tests/snmpsim/
cp /workspace/tests/snmpsim/ironware_icx6450.snmprec tests/snmpsim/

# Generate database dumps
./scripts/save-test-data.php -o ironware -v fcx648
./scripts/save-test-data.php -o ironware -v icx6450

# This creates:
# tests/data/ironware_fcx648.json
# tests/data/ironware_icx6450.json
```

---

## 🧪 Running Tests

### Test Specific OS:
```bash
lnms dev:check unit -o ironware
```

### Test with Database and SNMP:
```bash
lnms dev:check unit --db --snmpsim -o ironware
```

### Test Only Specific Variant:
```bash
lnms dev:check unit -o ironware --variant fcx648
lnms dev:check unit -o ironware --variant icx6450
```

### Test and Stop on Failure:
```bash
lnms dev:check unit --db --snmpsim -o ironware -f
```

---

## 📊 Test Coverage

### What Our Test Data Covers:

**OS Detection**:
- ✅ sysObjectID detection
- ✅ sysDescr parsing
- ✅ Version extraction
- ✅ Hardware identification

**Stack Detection**:
- ✅ Stack global state
- ✅ Stack topology (ring/chain/standalone)
- ✅ Stack unit identification
- ✅ Master/member roles

**Hardware Monitoring**:
- ✅ Serial numbers
- ✅ Hardware model
- ✅ Firmware version

**System Monitoring**:
- ✅ CPU utilization
- ✅ Memory pools
- ✅ Temperature sensors
- ✅ Power supply status
- ✅ Fan status

---

## 🔍 SNMP Data Format

### snmprec File Format:

```
<OID>|<TYPE>|<VALUE>

Where TYPE is:
4  = OCTET STRING
4x = HEX STRING (use for MAC addresses, binary data)
2  = Integer32
5  = NULL
6  = OBJECT IDENTIFIER
64 = IpAddress
65 = Counter32
66 = Gauge32
67 = TimeTicks
68 = Opaque
70 = Counter64
```

### Example Entries:

```snmp
# sysDescr (STRING)
1.3.6.1.2.1.1.1.0|4|Brocade Communications Systems, Inc...

# sysObjectID (OID)
1.3.6.1.2.1.1.2.0|6|1.3.6.1.4.1.1991.1.3.48.2.1

# MAC Address (HEX)
1.3.6.1.4.1.1991.1.1.3.31.2.2.1.6.1|4x|001ebe800001

# Integer
1.3.6.1.4.1.1991.1.1.3.31.2.2.1.7.1|2|128
```

---

## 📝 Creating Additional Test Variants

### If You Have Other Devices:

1. **Collect SNMP Data**:
```bash
# From real LibreNMS with device added
./scripts/collect-snmp-data.php -h HOSTNAME -v variant_name

# Example for ICX7150
./scripts/collect-snmp-data.php -h icx7150.example.com -v icx7150
```

2. **Save Test Data**:
```bash
./scripts/save-test-data.php -o ironware -v icx7150
```

3. **Run Tests**:
```bash
lnms dev:check unit -o ironware --variant icx7150
```

### Manual snmprec Creation:

If you don't have LibreNMS installed but have SNMP access:

```bash
# Do SNMP walk
snmpwalk -v2c -c public -ObentU device.example.com .1.3.6.1 > walk.txt

# Convert to snmprec format manually or use tools
# Format: <oid>|<type>|<value>
```

---

## 🎯 Test Data Checklist

### Minimum Required:

- [x] sysDescr (1.3.6.1.2.1.1.1.0)
- [x] sysObjectID (1.3.6.1.2.1.1.2.0)
- [x] Software version (snAgSoftwareVersion)
- [x] Hardware info (snAgentBrdMainBrdDescription)
- [x] Serial number (snChasUnitSerNum)

### Stack-Specific:

- [x] Stack global state
- [x] Stack topology
- [x] Stack unit table
- [x] Stack member roles
- [x] Per-unit serial numbers

### Monitoring Data:

- [x] CPU utilization
- [x] Memory pools
- [x] Temperature sensors
- [x] Power supply status
- [x] Fan status

---

## 🔧 Using snmpsim for Development

### Start Simulator:
```bash
lnms dev:simulate ironware_fcx648
```

### Query Simulated Device:
```bash
# Test sysObjectID detection
snmpget -v2c -c ironware_fcx648 127.1.6.1:1161 .1.3.6.1.2.1.1.2.0

# Test sysDescr
snmpget -v2c -c ironware_fcx648 127.1.6.1:1161 .1.3.6.1.2.1.1.1.0

# Test stack OIDs
snmpwalk -v2c -c ironware_fcx648 127.1.6.1:1161 .1.3.6.1.4.1.1991.1.1.3.31
```

### Run Discovery Against Simulator:
```bash
# Add simulated device
lnms device:add snmpsim -c ironware_fcx648

# Run discovery
lnms device:discover snmpsim -vv

# Run polling
lnms device:poll snmpsim -vv
```

---

## 📊 Test Validation

### Before Submitting PR:

1. **All tests pass**:
```bash
lnms dev:check unit -o ironware
```

2. **Database tests pass**:
```bash
lnms dev:check unit --db -o ironware
```

3. **SNMP simulator tests pass**:
```bash
lnms dev:check unit --db --snmpsim -o ironware
```

4. **No regressions**:
```bash
# Test that other OSes still work
lnms dev:check unit
```

5. **Code quality**:
```bash
# PSR-12 compliance
./vendor/bin/php-cs-fixer fix --dry-run --diff

# Static analysis
./vendor/bin/phpstan analyze
```

---

## 📝 Test Data Maintenance

### When to Update:

1. **After code changes**: Re-run save-test-data.php
2. **New monitoring added**: Collect additional SNMP data
3. **Bug fixes**: Verify tests catch the bug
4. **New firmware versions**: Add variant for new version

### How to Update:

```bash
# Modify code
# Re-save test data
./scripts/save-test-data.php -o ironware -v fcx648 -m os

# Verify tests still pass
lnms dev:check unit -o ironware
```

---

## ✅ Test Data Status

### Created:
- ✅ ironware_fcx648.snmprec (based on real device)
- ✅ ironware_icx6450.snmprec (based on real device)

### To Generate (in LibreNMS):
- ⏳ ironware_fcx648.json (use save-test-data.php)
- ⏳ ironware_icx6450.json (use save-test-data.php)

### Testing:
- ⏳ Run in LibreNMS environment
- ⏳ Verify tests pass
- ⏳ Submit with PR

**Next Action**: Copy snmprec files to LibreNMS and generate json dumps
