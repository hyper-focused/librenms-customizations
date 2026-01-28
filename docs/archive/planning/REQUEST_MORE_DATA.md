# Request for Additional SNMP Data

Thank you for the excellent FCX648 and ICX6450-48 data! This is exactly what we needed.

To complete the implementation, we need a few more pieces of information from your switches.

## 🔍 Stack Configuration Data Needed

Please run these SNMP queries on your **FCX648** and **ICX6450-48**:

### 1. Stack Topology (Critical)
```bash
# Get stack topology (ring/chain/standalone)
snmpget -v2c -c vweb-ro 172.16.255.6 .1.3.6.1.4.1.1991.1.1.3.31.1.2.0
snmpget -v2c -c vweb-ro 172.16.255.5 .1.3.6.1.4.1.1991.1.1.3.31.1.2.0

# Get stack MAC address
snmpget -v2c -c vweb-ro 172.16.255.6 .1.3.6.1.4.1.1991.1.1.3.31.1.1.0
snmpget -v2c -c vweb-ro 172.16.255.5 .1.3.6.1.4.1.1991.1.1.3.31.1.1.0
```

### 2. Stack Members (Critical)
```bash
# Get all stack member information
snmpwalk -v2c -c vweb-ro 172.16.255.6 .1.3.6.1.4.1.1991.1.1.3.31.3.1
snmpwalk -v2c -c vweb-ro 172.16.255.5 .1.3.6.1.4.1.1991.1.1.3.31.3.1
```

This will show:
- How many units in the stack
- Master vs member roles
- Unit IDs
- MAC addresses per unit
- Priority values

### 3. Serial Numbers
```bash
# Get serial numbers for all stack members
snmpwalk -v2c -c vweb-ro 172.16.255.6 .1.3.6.1.4.1.1991.1.1.1.4.1.1.2
snmpwalk -v2c -c vweb-ro 172.16.255.5 .1.3.6.1.4.1.1991.1.1.1.4.1.1.2
```

### 4. Additional System Info
```bash
# Software version
snmpget -v2c -c vweb-ro 172.16.255.6 .1.3.6.1.4.1.1991.1.1.2.1.1.0
snmpget -v2c -c vweb-ro 172.16.255.5 .1.3.6.1.4.1.1991.1.1.2.1.1.0

# Boot version
snmpget -v2c -c vweb-ro 172.16.255.6 .1.3.6.1.4.1.1991.1.1.2.1.2.0
snmpget -v2c -c vweb-ro 172.16.255.5 .1.3.6.1.4.1.1991.1.1.2.1.2.0
```

## 📊 Example Output Format

The output should look something like this:

```
# Stack topology
.1.3.6.1.4.1.1991.1.1.3.31.1.2.0 = INTEGER: 1

# Stack members walk
.1.3.6.1.4.1.1991.1.1.3.31.3.1.1.1 = INTEGER: 1
.1.3.6.1.4.1.1991.1.1.3.31.3.1.2.1 = INTEGER: 3
.1.3.6.1.4.1.1991.1.1.3.31.3.1.3.1 = INTEGER: 1
.1.3.6.1.4.1.1991.1.1.3.31.3.1.4.1 = Hex-STRING: 74 9D CE 80 00 00
...
```

## 🎯 What This Data Will Tell Us

1. **Stack Configuration**: Are your switches currently stacked or standalone?
2. **Stack Roles**: Which unit is master, which are members?
3. **Stack Topology**: Ring or chain configuration?
4. **Hardware Details**: Serial numbers for each unit
5. **Verification**: Confirm our stack OID mappings are correct

## 🔧 Alternative: Use the Provided Script

You can also use the provided `collect_snmp_data.sh` script to collect comprehensive data:

```bash
# For ICX 6450
./collect_snmp_data.sh 172.16.255.5 vweb-ro icx6450

# For FCX 648
./collect_snmp_data.sh 172.16.255.6 vweb-ro fcx648
```

Or for a complete walk of the Foundry enterprise OID:

```bash
# Complete walk (will take a minute or two)
snmpwalk -v2c -c vweb-ro 172.16.255.6 .1.3.6.1.4.1.1991 > fcx648_complete_walk.txt
snmpwalk -v2c -c vweb-ro 172.16.255.5 .1.3.6.1.4.1.1991 > icx6450_complete_walk.txt
```

Then share those text files. This gives us everything!

## 📝 Other Models

If you have access to any other switch models, the same basic queries would be incredibly helpful:

- FCX624
- ICX6430, ICX6610, ICX6650
- ICX7150, ICX7250, ICX7450, ICX7750

Even just the sysDescr and sysObjectID from these would be valuable:
```bash
snmpget -v2c -c vweb-ro SWITCH_IP .1.3.6.1.2.1.1.1.0 .1.3.6.1.2.1.1.2.0
```

## 🚀 What Happens Next

Once we have this stack data, we can:
1. ✅ Complete OS detection logic with verified OIDs
2. ✅ Implement stack discovery module
3. ✅ Create realistic test cases
4. ✅ Build the database schema
5. ✅ Test against your real devices

This puts us in a great position to have working code very soon!

## 📋 Quick Summary

**Minimum needed**:
- Stack topology query result
- Stack members walk result

**Ideal**:
- Complete SNMP walk of .1.3.6.1.4.1.1991

**Bonus**:
- Data from other switch models you may have

Thank you! This data is making implementation much more accurate and realistic.
