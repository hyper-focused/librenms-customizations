# Brocade/Ruckus ICX Examples

This document provides specific examples for Brocade/Ruckus ICX series switches.

## ICX Switch Configuration Examples

### SNMP Configuration

#### SNMPv2c on ICX Switch
```
ICX7150-48P Router# configure terminal
ICX7150-48P Router(config)# snmp-server community public ro
ICX7150-48P Router(config)# snmp-server location "Building A, Floor 2"
ICX7150-48P Router(config)# snmp-server contact "netadmin@example.com"
ICX7150-48P Router(config)# snmp-server enable traps
ICX7150-48P Router(config)# snmp-server host 10.0.0.100 public
ICX7150-48P Router(config)# write memory
```

#### SNMPv3 on ICX Switch
```
ICX7150-48P Router# configure terminal
ICX7150-48P Router(config)# snmp-server user librenms groupname v3 auth md5 AuthPass123 priv des PrivPass456
ICX7150-48P Router(config)# snmp-server group groupname v3 auth read all write all
ICX7150-48P Router(config)# write memory
```

### Stack Configuration

#### Basic Stack Setup (ICX7150)
```
! On all units - configure unit ID before stacking
ICX7150-48P Router# stack unit 1
ICX7150-48P Router# stack unit 2

! Configure priorities (higher = preferred master)
ICX7150-48P Router# configure terminal
ICX7150-48P Router(config)# stack unit 1 priority 128
ICX7150-48P Router(config)# stack unit 2 priority 64
ICX7150-48P Router(config)# write memory

! Reload to apply stack configuration
ICX7150-48P Router# reload
```

#### Verify Stack Configuration
```
ICX7150-Stack# show stack

Standalone unit, unit ID 1 (default).
T: Stack Topology - R=Ring, C=Chain, S=Standalone

T Stack Unit Pwr Unit   Unit   Stack    Stack    Model      Pwr
  Unit ID    Stat Type   Pri    Role     Port1    Port2
+ 1  1       On   U      128    Master   1/2/1    1/2/2    ICX7150-48P    320W
  2  2       On   U      64     Member   2/2/1    2/2/2    ICX7150-48P    320W
```

## LibreNMS Discovery Examples

### ICX7150 Stack Discovery

```bash
# Add ICX stack to LibreNMS
./addhost.php icx-stack.example.com public v2c

# Run discovery
./discovery.php -h icx-stack.example.com -d

# Expected output:
```

```
Ruckus ICX Device Detected
=====================================
Hostname:     icx-stack.example.com
Platform:     ICX Series
Model:        ICX7150-48P
Series:       7150
Version:      FastIron 08.0.95
Configuration: Stacked (2 units)

Stack Information:
  Topology:     Ring
  Units:        2
  Master:       Unit 1

Stack Members:
  Unit 1 [MASTER]
    Model:      ICX7150-48P
    Serial:     XXX1234567890
    Version:    08.0.95
    MAC:        609c.9f00.0001
    Priority:   128
    State:      Active
    Ports:      48 + 4 SFP+
    
  Unit 2 [MEMBER]
    Model:      ICX7150-48P
    Serial:     XXX0987654321
    Version:    08.0.95
    MAC:        609c.9f00.0002
    Priority:   64
    State:      Active
    Ports:      48 + 4 SFP+

Stack Ports:
  Unit 1/2/1: UP (to Unit 2/2/2)
  Unit 1/2/2: UP (to Unit 2/2/1)
  Unit 2/2/1: UP (to Unit 1/2/2)
  Unit 2/2/2: UP (to Unit 1/2/1)

Total Ports Discovered: 96 data ports + 8 SFP+ uplinks
PoE Enabled: Yes (320W per unit)
```

### ICX6450 Standalone Discovery

```bash
./discovery.php -h icx6450.example.com -d
```

```
Ruckus ICX Device Detected
=====================================
Hostname:     icx6450.example.com
Platform:     ICX Series
Model:        ICX6450-48
Series:       6450
Version:      FastIron 08.0.90
Configuration: Standalone

Hardware Information:
  Serial:     XYZ1234567890
  MAC:        609c.9fab.cd01
  Ports:      48 + 2 SFP+
  PoE:        Not available

Discovery Modules:
  OS:              Brocade/Ruckus ICX
  Ports:           48 data + 2 uplink ports discovered
  VLANs:           3 VLANs discovered
  Entity-Physical: 1 chassis discovered
  Stack:           Standalone configuration detected
```

### ICX7750 Large Stack Discovery

```bash
./discovery.php -h icx7750-stack.example.com -d
```

```
Ruckus ICX Device Detected
=====================================
Hostname:     icx7750-stack.example.com
Platform:     ICX Series
Model:        ICX7750-48F
Series:       7750
Version:      FastIron 09.0.10
Configuration: Stacked (8 units)

Stack Information:
  Topology:     Ring
  Units:        8
  Master:       Unit 1
  Bandwidth:    960 Gbps

Stack Members:
  Unit 1 [MASTER] - ICX7750-48F - Serial: AAA111... - Active
  Unit 2 [MEMBER] - ICX7750-48F - Serial: AAA222... - Active
  Unit 3 [MEMBER] - ICX7750-48F - Serial: AAA333... - Active
  Unit 4 [MEMBER] - ICX7750-48F - Serial: AAA444... - Active
  Unit 5 [MEMBER] - ICX7750-48F - Serial: AAA555... - Active
  Unit 6 [MEMBER] - ICX7750-48F - Serial: AAA666... - Active
  Unit 7 [MEMBER] - ICX7750-48F - Serial: AAA777... - Active
  Unit 8 [MEMBER] - ICX7750-48F - Serial: AAA888... - Active

Total Ports Discovered: 384 SFP+ ports
```

## SNMP Query Examples

### ICX7150 Queries

#### Get System Information
```bash
# Get sysDescr
snmpget -v2c -c public icx7150.example.com SNMPv2-MIB::sysDescr.0
# Output: Ruckus ICX 7150-48P Switch, FastIron Version 08.0.95...

# Get sysObjectID
snmpget -v2c -c public icx7150.example.com SNMPv2-MIB::sysObjectID.0
# Output: .1.3.6.1.4.1.1588.2.1.1.1.3.31

# Get software version
snmpget -v2c -c public icx7150.example.com .1.3.6.1.4.1.1991.1.1.2.1.1.0
# Output: 08.0.95
```

#### Get Stack Information
```bash
# Get stack topology
snmpget -v2c -c public icx7150.example.com \
  FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalTopology.0
# Output: INTEGER: ring(1)

# Get stack units
snmpwalk -v2c -c public icx7150.example.com \
  FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingOperUnitTable

# Sample output:
# snStackingOperUnitIndex.1 = 1
# snStackingOperUnitRole.1 = master(3)
# snStackingOperUnitState.1 = local(1)
# snStackingOperUnitMac.1 = 60:9c:9f:00:00:01
# snStackingOperUnitPriority.1 = 128
# snStackingOperUnitIndex.2 = 2
# snStackingOperUnitRole.2 = member(2)
# snStackingOperUnitState.2 = remote(2)
# snStackingOperUnitMac.2 = 60:9c:9f:00:00:02
# snStackingOperUnitPriority.2 = 64
```

#### Get Hardware Information
```bash
# Get serial numbers
snmpwalk -v2c -c public icx7150.example.com \
  FOUNDRY-SN-AGENT-MIB::snChasUnitSerNum

# Get chassis descriptions
snmpwalk -v2c -c public icx7150.example.com \
  FOUNDRY-SN-AGENT-MIB::snChasUnitDescription
```

### ICX6450 Queries

```bash
# System info
snmpget -v2c -c public icx6450.example.com SNMPv2-MIB::sysDescr.0
# Output: Brocade ICX6450-48 Switch, IronWare Version 08.0.40...

# sysObjectID
snmpget -v2c -c public icx6450.example.com SNMPv2-MIB::sysObjectID.0
# Output: .1.3.6.1.4.1.1588.2.1.1.1.3.8

# Stack status (standalone)
snmpget -v2c -c public icx6450.example.com \
  FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalTopology.0
# Output: INTEGER: standalone(3)
```

### ICX7750 Queries

```bash
# System info
snmpget -v2c -c public icx7750.example.com SNMPv2-MIB::sysDescr.0
# Output: ICX7750-48F Router, FastIron Version 09.0.10...

# sysObjectID
snmpget -v2c -c public icx7750.example.com SNMPv2-MIB::sysObjectID.0
# Output: .1.3.6.1.4.1.1588.2.1.1.1.3.41
```

## API Usage Examples

### Get ICX Stack via LibreNMS API

```bash
# Get device information
curl -H 'X-Auth-Token: YOUR_TOKEN' \
  https://librenms.example.com/api/v0/devices/icx-stack.example.com

# Get stack details (custom endpoint)
curl -H 'X-Auth-Token: YOUR_TOKEN' \
  https://librenms.example.com/api/v0/devices/icx-stack.example.com/ironware-stack
```

### Example API Response

```json
{
  "status": "ok",
  "device": {
    "device_id": 456,
    "hostname": "icx-stack.example.com",
    "os": "icx",
    "hardware": "ICX7150-48P",
    "version": "08.0.95",
    "serial": "XXX1234567890",
    "features": "FastIron",
    "location": "Building A, Floor 2",
    "stack": {
      "enabled": true,
      "platform": "icx",
      "unit_count": 2,
      "topology": "ring",
      "master_unit": 1,
      "bandwidth": "84Gbps",
      "members": [
        {
          "unit_id": 1,
          "role": "master",
          "state": "active",
          "model": "ICX7150-48P",
          "series": "7150",
          "serial": "XXX1234567890",
          "mac": "609c.9f00.0001",
          "priority": 128,
          "sw_version": "08.0.95",
          "port_count": 48,
          "poe_enabled": true,
          "poe_capacity": "320W"
        },
        {
          "unit_id": 2,
          "role": "member",
          "state": "active",
          "model": "ICX7150-48P",
          "series": "7150",
          "serial": "XXX0987654321",
          "mac": "609c.9f00.0002",
          "priority": 64,
          "sw_version": "08.0.95",
          "port_count": 48,
          "poe_enabled": true,
          "poe_capacity": "320W"
        }
      ]
    }
  }
}
```

## Troubleshooting Examples

### Issue: ICX Switch Detected as Unknown

```bash
# Check sysObjectID
snmpget -v2c -c public icx.example.com SNMPv2-MIB::sysObjectID.0

# If returns Brocade OID (.1.3.6.1.4.1.1588.*) but not detected:
# 1. Verify icx.yaml OS definition includes the OID
# 2. Check icx.inc.php detection logic
# 3. Run discovery with debug:
./discovery.php -h icx.example.com -d -m os
```

### Issue: Stack Members Not Discovered

```bash
# Verify stack MIB is accessible
snmpwalk -v2c -c public icx.example.com \
  .1.3.6.1.4.1.1991.1.1.3.31

# If no response, check:
# 1. SNMP community string
# 2. SNMP access list on switch
# 3. Firmware version (older versions may differ)

# Test with alternate OID (Brocade enterprise):
snmpwalk -v2c -c public icx.example.com \
  .1.3.6.1.4.1.1588
```

### Issue: FastIron Version Not Detected

```bash
# Check version string format
snmpget -v2c -c public icx.example.com \
  .1.3.6.1.4.1.1991.1.1.2.1.1.0

# May return:
# "08.0.95"     - Standard format
# "08.0.95b"    - With letter suffix
# "09.0.10a"    - Newer version

# Ensure detection regex handles all formats:
# /(?:FastIron|IronWare)\s+(?:Version\s+)?(\d+\.\d+\.\d+[a-z]?)/i
```

## Performance Tuning

### Optimize Discovery for Large ICX Stacks

```bash
# For 12-unit stacks, increase timeouts
# In LibreNMS config.php:
$config['snmp']['timeout'] = 2000000;  # 2 seconds
$config['snmp']['retries'] = 3;

# Run discovery in batches if needed
./discovery.php -h icx-stack.example.com -m os
./discovery.php -h icx-stack.example.com -m ports
./discovery.php -h icx-stack.example.com -m ironware-stack
```

## Alert Examples

### ICX Stack Member Down

```yaml
Rule: %devices.os = "icx" && %ironware_stack_members.unit_state != "active"
Severity: Critical
Title: ICX Stack Member Offline
Message: Stack member Unit {{ $unit_id }} on {{ $hostname }} is {{ $unit_state }}
```

### ICX Firmware Mismatch

```yaml
Rule: %devices.os = "icx" && COUNT(DISTINCT %ironware_stack_members.sw_version) > 1
Severity: Warning
Title: ICX Stack Firmware Mismatch
Message: Stack {{ $hostname }} has members running different FastIron versions
```

### ICX High Stack Port Utilization

```yaml
Rule: %ports.ifDescr LIKE "Stack%" && %ports.ifOutUtilization > 80
Severity: Warning
Title: ICX Stack Port High Utilization
Message: Stack port {{ $ifDescr }} on {{ $hostname }} is at {{ $ifOutUtilization }}% utilization
```

## Model-Specific Notes

### ICX6450 Series
- Maximum 8-unit stacks
- May show "IronWare" or "FastIron" depending on firmware
- Limited Layer 3 features compared to 7150+

### ICX7150 Series
- Most common campus deployment
- Up to 12-unit stacks
- Full Layer 3 routing
- PoE+ on P models

### ICX7250 Series
- 10G uplinks standard
- Advanced routing features
- Higher throughput than 7150

### ICX7450 Series
- 40G uplinks available
- Campus aggregation/core
- Higher stack bandwidth (480 Gbps)

### ICX7650 Series
- Data center focused
- 10/40/100G support
- Very high throughput

### ICX7750 Series
- Highest performance
- Modular and fixed configurations
- 40/100G stacking
- 960 Gbps stack bandwidth

## Best Practices

1. **Firmware Consistency**: Keep all stack members on same FastIron version
2. **Priority Settings**: Set clear priorities to avoid split-brain
3. **Monitoring**: Monitor stack ports as critical interfaces
4. **Documentation**: Label units physically and in LibreNMS
5. **Redundancy**: Use ring topology when possible
6. **Updates**: Update firmware during maintenance windows only
7. **Testing**: Test failover scenarios in lab before production

## Additional Resources

- [Ruckus ICX Documentation](https://support.ruckuswireless.com/products/46-ruckus-icx-switches)
- [FastIron Configuration Guide](https://support.ruckuswireless.com/documents)
- [Ruckus MIB Files](https://support.ruckuswireless.com/software)
