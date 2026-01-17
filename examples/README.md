# Examples

This directory contains example configurations, outputs, and usage scenarios for Foundry FCX stack monitoring in LibreNMS.

## Directory Structure

```
examples/
├── snmp_configs/          # Example SNMP configurations for FCX switches
├── discovery_outputs/     # Example discovery command outputs
├── polling_outputs/       # Example polling outputs
├── api_examples/          # LibreNMS API usage examples
└── graphs/                # Example graphs and visualizations
```

## Example Configurations

### SNMP Configuration on FCX Switch

**Basic SNMPv2c Configuration**:
```
! Enable SNMP
snmp-server
snmp-server community public ro

! Set system information
snmp-server contact "Network Admin <admin@example.com>"
snmp-server location "Data Center 1, Rack 42"

! Enable traps
snmp-server enable traps
snmp-server host 10.0.0.100 public
```

**Secure SNMPv3 Configuration**:
```
! Enable SNMP
snmp-server
snmp-server user librenms group admin v3 auth md5 AuthPassword priv des PrivPassword

! Configure access
snmp-server group admin v3 auth read all write all

! Set system information
snmp-server contact "Network Admin <admin@example.com>"
snmp-server location "Data Center 1, Rack 42"
```

### LibreNMS Device Configuration

**Adding FCX Device**:
```bash
# Via CLI
./addhost.php fcx-stack.example.com public v2c

# With SNMPv3
./addhost.php fcx-stack.example.com "" v3 \
  -A AuthPassword -X PrivPassword \
  -l authPriv -a MD5 -x DES \
  -u librenms

# Force OS detection
./discovery.php -h fcx-stack.example.com -d -m os
```

## Example Discovery Outputs

### Standalone FCX Switch

```
Foundry Networks Device Detected
=====================================
Hostname:     fcx-01.example.com
Model:        FCX624
Serial:       ABC1234567890
Version:      08.0.30
Configuration: Standalone

Discovery Modules:
  OS:              Foundry FastIron
  Ports:           24 ports discovered
  VLANs:           5 VLANs discovered
  Entity-Physical: 1 chassis discovered
  Foundry Stack:   Standalone configuration detected
```

### Two-Unit Stack

```
Foundry Networks Stack Detected
=====================================
Hostname:     fcx-stack.example.com
Stack Info:   2-unit stack
Topology:     Ring
Master Unit:  Unit 1

Stack Members:
  Unit 1 [MASTER]
    Model:      FCX648
    Serial:     ABC1111111111
    Version:    08.0.30
    MAC:        001e.be00.0001
    Priority:   128
    State:      Local/Active
    
  Unit 2 [MEMBER]
    Model:      FCX648
    Serial:     ABC2222222222
    Version:    08.0.30
    MAC:        001e.be00.0002
    Priority:   64
    State:      Remote/Active

Stack Ports:
  Unit 1 Stack1/1: UP (connected to Unit 2)
  Unit 1 Stack1/2: UP (connected to Unit 2)
  Unit 2 Stack1/1: UP (connected to Unit 1)
  Unit 2 Stack1/2: UP (connected to Unit 1)

Total Ports Discovered: 96 (48 per unit)
```

## Example SNMP Queries

### Check Stack Configuration

```bash
# Get stack topology
snmpget -v2c -c public fcx-stack.example.com \
  FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalTopology.0

# Output: INTEGER: ring(1)

# Get all stack members
snmpwalk -v2c -c public fcx-stack.example.com \
  FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingOperUnitTable

# Output shows all stack units with roles and status
```

### Query Hardware Information

```bash
# Get serial numbers for all units
snmpwalk -v2c -c public fcx-stack.example.com \
  FOUNDRY-SN-AGENT-MIB::snChasUnitSerNum

# Get software version
snmpget -v2c -c public fcx-stack.example.com \
  FOUNDRY-SN-AGENT-MIB::snAgSoftwareVersion.0
```

## Example API Usage

### Get Stack Information via LibreNMS API

```bash
# Get device details
curl -H 'X-Auth-Token: YOUR_API_TOKEN' \
  https://librenms.example.com/api/v0/devices/fcx-stack.example.com

# Get stack members (custom endpoint - to be implemented)
curl -H 'X-Auth-Token: YOUR_API_TOKEN' \
  https://librenms.example.com/api/v0/devices/fcx-stack.example.com/foundry-stack
```

### Example API Response

```json
{
  "status": "ok",
  "device": {
    "device_id": 123,
    "hostname": "fcx-stack.example.com",
    "os": "foundry",
    "hardware": "FCX648",
    "version": "08.0.30",
    "stack": {
      "enabled": true,
      "unit_count": 2,
      "topology": "ring",
      "master_unit": 1,
      "members": [
        {
          "unit_id": 1,
          "role": "master",
          "state": "active",
          "model": "FCX648",
          "serial": "ABC1111111111",
          "mac": "001e.be00.0001",
          "priority": 128
        },
        {
          "unit_id": 2,
          "role": "member",
          "state": "active",
          "model": "FCX648",
          "serial": "ABC2222222222",
          "mac": "001e.be00.0002",
          "priority": 64
        }
      ]
    }
  }
}
```

## Example Alerts

### Stack Member Down Alert

```yaml
# Alert rule
Rule: %devices.os = "foundry" && %foundry_stack_members.unit_state != "active"
Severity: Critical
Title: Foundry Stack Member Down
Message: Stack member Unit {{ $unit_id }} on {{ $hostname }} is {{ $unit_state }}
```

### Stack Link Failure Alert

```yaml
# Alert rule
Rule: %devices.os = "foundry" && %foundry_stack_ports.port_oper_status = "down"
Severity: Warning
Title: Foundry Stack Port Down
Message: Stack port {{ $port_index }} on Unit {{ $unit_id }} of {{ $hostname }} is down
```

### Stack Master Change Alert

```yaml
# Alert rule based on log events
Rule: %eventlog.type = "foundry_stack_master_change"
Severity: Warning
Title: Foundry Stack Master Changed
Message: Stack master changed on {{ $hostname }} from Unit {{ $old_master }} to Unit {{ $new_master }}
```

## Example Graphs

### Stack Port Utilization

Graph showing traffic on stack ports over time to identify:
- Stack link saturation
- Asymmetric traffic patterns
- Potential bottlenecks

### Stack Health Dashboard

Custom dashboard showing:
- Stack topology diagram
- Member status table
- Stack port status
- Hardware health (fans, PSU, temp)
- Historical master changes

## Troubleshooting Examples

### Issue: Stack Not Detected

```bash
# Check SNMP connectivity
snmpwalk -v2c -c public fcx-stack.example.com sysDescr

# Check if stack OIDs are accessible
snmpwalk -v2c -c public fcx-stack.example.com \
  .1.3.6.1.4.1.1991.1.1.3.31

# Run discovery with debugging
./discovery.php -h fcx-stack.example.com -d -m foundry-stack
```

### Issue: Incorrect Serial Numbers

```bash
# Verify chassis table
snmpwalk -v2c -c public fcx-stack.example.com \
  FOUNDRY-SN-AGENT-MIB::snChasUnitTable

# Check entity-physical table
snmpwalk -v2c -c public fcx-stack.example.com \
  entPhysicalSerialNum

# Force rediscovery
./discovery.php -h fcx-stack.example.com -d -m entity-physical
```

## Best Practices

### Stack Configuration

1. **Consistent Numbering**: Number units sequentially (1, 2, 3...)
2. **Master Priority**: Set higher priority on preferred master
3. **Firmware Consistency**: Keep all units on same firmware version
4. **Documentation**: Document stack topology and unit positions

### Monitoring Configuration

1. **Polling Interval**: Stack status can be polled less frequently (15-30 min)
2. **Alerting**: Configure alerts for stack events
3. **Logging**: Enable SNMP trap reception for immediate notifications
4. **Graphing**: Create custom graphs for stack-specific metrics

### Maintenance

1. **Pre-change Discovery**: Run discovery before making changes
2. **Post-change Verification**: Verify stack status after changes
3. **Backup Configs**: Backup configurations for each stack member
4. **Test Failover**: Periodically test stack failover procedures

## Additional Resources

- [FCX CLI Reference Guide](docs/FCX_CLI_Reference.pdf)
- [SNMP Configuration Guide](docs/SNMP_Configuration.pdf)
- [Stack Configuration Best Practices](docs/Stack_Best_Practices.pdf)

## Contributing Examples

To contribute examples:
1. Sanitize any sensitive information (IPs, passwords, etc.)
2. Include context and description
3. Test examples before submitting
4. Add comments explaining non-obvious steps
5. Update this README with new examples
