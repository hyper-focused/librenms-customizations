# SNMP Data Collection for FCX/ICX Switch Research

## Target Devices

- **ICX 6450**: 172.16.255.5 (Community: vweb-ro)
- **FCX 648**: 172.16.255.6 (Community: vweb-ro)

## Required SNMP Data

### System Information
- `sysDescr.0` (OID: 1.3.6.1.2.1.1.1.0) - System description
- `sysObjectID.0` (OID: 1.3.6.1.2.1.1.2.0) - System object identifier
- `sysUpTime.0` (OID: 1.3.6.1.2.1.1.3.0) - System uptime
- `sysName.0` (OID: 1.3.6.1.2.1.1.5.0) - System name
- `sysLocation.0` (OID: 1.3.6.1.2.1.1.6.0) - System location
- `sysContact.0` (OID: 1.3.6.1.2.1.1.4.0) - System contact

### Foundry/Brocade Stack Information
- `snStackMemberCount.0` (OID: 1.3.6.1.4.1.1991.1.1.2.1.1.0) - Number of stack members
- `snStackMemberTable` (OID: 1.3.6.1.4.1.1991.1.1.2.1.2) - Stack member details
- `snStackPortCount.0` (OID: 1.3.6.1.4.1.1991.1.1.2.1.3.0) - Number of stack ports
- `snStackPortTable` (OID: 1.3.6.1.4.1.1991.1.1.2.1.4) - Stack port information

### Stack Member Details (per member)
For each stack member (1-8):
- `snStackMemberId.{member_id}` (OID: 1.3.6.1.4.1.1991.1.1.2.1.2.1.1.{member_id})
- `snStackMemberType.{member_id}` (OID: 1.3.6.1.4.1.1991.1.1.2.1.2.1.2.{member_id})
- `snStackMemberStatus.{member_id}` (OID: 1.3.6.1.4.1.1991.1.1.2.1.2.1.3.{member_id})
- `snStackMemberMacAddr.{member_id}` (OID: 1.3.6.1.4.1.1991.1.1.2.1.2.1.4.{member_id})
- `snStackMemberPriority.{member_id}` (OID: 1.3.6.1.4.1.1991.1.1.2.1.2.1.5.{member_id})

### Hardware Information
- `snChasUnit.1` (OID: 1.3.6.1.4.1.1991.1.1.1.1.1.1) - Chassis unit information
- `snChasSerNum.1` (OID: 1.3.6.1.4.1.1991.1.1.1.1.2.1) - Serial number
- `snChasPwrSupplyDescription.1` (OID: 1.3.6.1.4.1.1991.1.1.1.1.7.1) - Power supply info

### Entity MIB (RFC 2737) - Physical Inventory
- `entPhysicalTable` (OID: 1.3.6.1.2.1.47.1.1.1) - Physical entity table

### Interface Information
- `ifTable` (OID: 1.3.6.1.2.1.2.2) - Interface table (first few entries)

## Collection Script

Use the provided `collect_snmp_data.sh` script to collect this data:

```bash
# For ICX 6450
./collect_snmp_data.sh 172.16.255.5 vweb-ro icx6450

# For FCX 648
./collect_snmp_data.sh 172.16.255.6 vweb-ro fcx648
```

## Output Format

The script will create timestamped output files in the `snmp_data/` directory with the following naming convention:
- `{device_name}_{timestamp}.txt` - All collected data
- `{device_name}_{timestamp}_errors.txt` - Any errors encountered

## Analysis

After collecting the data, analyze:
1. System description patterns for OS identification
2. Stack configuration and member details
3. Hardware model identification
4. MIB support and OID availability

This data will help improve FCX/ICX discovery and monitoring in LibreNMS.