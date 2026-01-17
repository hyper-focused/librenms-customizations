# Foundry MIB Files

This directory contains SNMP MIB files required for Foundry FCX switch monitoring.

## Required MIBs

### Foundry-Specific MIBs

- **FOUNDRY-SN-ROOT-MIB**: Root MIB with enterprise definitions
- **FOUNDRY-SN-AGENT-MIB**: System and agent information
- **FOUNDRY-SN-SWITCH-GROUP-MIB**: Switch-specific OIDs including stacking
- **FOUNDRY-SN-STACKING-MIB**: Dedicated stacking MIB (if separate)
- **FOUNDRY-SN-IP-MIB**: IP configuration and routing
- **FOUNDRY-SN-MAC-ADDRESS-MIB**: MAC address tables

### Standard MIBs (Usually included with SNMP tools)

- **SNMPv2-MIB**: Standard SNMP definitions
- **IF-MIB**: Interface information (RFC 2863)
- **ENTITY-MIB**: Physical entity information (RFC 4133)
- **BRIDGE-MIB**: Bridge/switching information

## Obtaining MIB Files

### From Ruckus (Current Owner)

1. Visit [Ruckus Support Portal](https://support.ruckuswireless.com/)
2. Search for "ICX MIB files" (ICX is the current branding)
3. Download MIB pack for your firmware version

### From Brocade (Previous Owner)

Historical MIB files may be available from:
- Brocade support archives
- Network equipment resellers
- Community archives

### From Device

Some switches allow MIB downloads via TFTP/HTTP:
```
# Example CLI command (varies by version)
copy mib tftp <tftp-server> <filename>
```

## Installing MIBs

### For LibreNMS

```bash
# Copy MIBs to LibreNMS MIB directory
cp foundry/* /opt/librenms/mibs/foundry/

# Verify MIBs are recognized
snmptranslate -m +FOUNDRY-SN-AGENT-MIB -IR -On snAgentBrdIndex
```

### For SNMP Tools

```bash
# Copy to system MIB directory
sudo cp foundry/* /usr/share/snmp/mibs/

# Or use MIBDIRS environment variable
export MIBDIRS=+/path/to/this/directory/foundry
```

## MIB Validation

Test MIB loading:

```bash
# Translate OID to name
snmptranslate -m +ALL -M +./foundry .1.3.6.1.4.1.1991.1.1.3.31.3.1.2

# Should output: FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingOperUnitRole

# Translate name to OID
snmptranslate -m +ALL -M +./foundry -On FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingOperUnitRole

# Should output: .1.3.6.1.4.1.1991.1.1.3.31.3.1.2
```

## MIB Structure Overview

### Enterprise Root
```
iso.org.dod.internet.private.enterprises.foundry (1.3.6.1.4.1.1991)
├── products (1)
├── snSwitch (1.1)
│   ├── snAgent (1)
│   │   ├── snAgentGeneral (1)
│   │   ├── snAgentBrd (1)
│   │   └── snAgentChassis (1)
│   └── snL4 (3)
│       └── snStacking (31)
│           ├── snStackingGlobalObjects (1)
│           ├── snStackingConfigUnit (2)
│           └── snStackingOperUnit (3)
└── snTraps (2)
```

## Common Issues

### MIB Not Found

**Error**: "Cannot find module (FOUNDRY-SN-AGENT-MIB)"

**Solutions**:
- Verify MIB files are in correct directory
- Check file permissions (must be readable)
- Use `-M +<directory>` flag to add search path
- Set MIBDIRS environment variable

### Dependency Issues

**Error**: "Cannot adopt OID in FOUNDRY-SN-AGENT-MIB"

**Solutions**:
- Ensure FOUNDRY-SN-ROOT-MIB is loaded first
- Install all dependent MIBs
- Check MIB imports section for required MIBs

### Version Compatibility

Different firmware versions may have:
- Different OID availability
- Modified MIB structures
- Additional or deprecated objects

Always match MIB version to firmware version when possible.

## Testing MIBs

Test against real device:

```bash
# Query using MIB name
snmpget -v2c -c public -m +FOUNDRY-SN-AGENT-MIB device.example.com \
    snAgSoftwareVersion.0

# Walk stack table using MIB
snmpwalk -v2c -c public -m +FOUNDRY-SN-SWITCH-GROUP-MIB device.example.com \
    snStackingOperUnitTable
```

## Contributing MIBs

If you have MIB files not included here:

1. Verify they are the correct Foundry/Brocade/Ruckus MIBs
2. Check license/redistribution terms
3. Create pull request with:
   - MIB files
   - Version information
   - Source/origin documentation

## Legal Notice

MIB files are typically copyrighted by the original manufacturer. Ensure you have the right to use and distribute these files. This project does not include the actual MIB files - users must obtain them from legitimate sources.

## References

- [SNMP MIB Overview](https://en.wikipedia.org/wiki/Management_information_base)
- [RFC 2578 - SMIv2](https://tools.ietf.org/html/rfc2578)
- [Net-SNMP MIB Loading](http://www.net-snmp.org/wiki/index.php/TUT:Using_and_loading_MIBS)
