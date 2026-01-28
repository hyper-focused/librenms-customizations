# Foundry MIB Files

This directory contains SNMP MIB files for Foundry/Brocade/Ruckus switches downloaded from the LibreNMS repository.

## MIB Files

### Core Stack Discovery MIBs

- **FOUNDRY-SN-STACKING-MIB** (13KB, 435 lines)
  - Stack-specific OIDs for topology and member discovery
  - **Critical for stack discovery functionality**
  - Source: `mibs/brocade/` in LibreNMS repo

- **FOUNDRY-SN-SWITCH-GROUP-MIB** (249KB, 9,172 lines)
  - Switch-specific OIDs including stacking base definitions
  - Contains `snSwitch` module that `FOUNDRY-SN-STACKING-MIB` depends on
  - Source: `mibs/brocade/` in LibreNMS repo

### System Information MIBs

- **FOUNDRY-SN-AGENT-MIB** (175KB, 6,455 lines)
  - Agent and system information
  - CPU, memory, chassis information
  - Source: `mibs/brocade/` in LibreNMS repo

- **FOUNDRY-SN-ROOT-MIB** (123KB, 1,615 lines)
  - Root MIB with enterprise definitions
  - Base OID definitions for Foundry enterprise (1991)
  - Source: `mibs/brocade/` in LibreNMS repo

### Additional Feature MIBs

- **FOUNDRY-SN-MAC-AUTHENTICATION-MIB** (7.8KB, 256 lines)
  - MAC authentication features
  - Source: `mibs/foundry/` in LibreNMS repo

- **FOUNDRY-SN-MAC-VLAN-MIB** (8.0KB, 285 lines)
  - MAC VLAN features
  - Source: `mibs/foundry/` in LibreNMS repo

- **FOUNDRY-SN-MRP-MIB** (12KB, 347 lines)
  - Multiple Registration Protocol (MRP) features
  - Source: `mibs/foundry/` in LibreNMS repo

## Source

All MIB files were downloaded from:
- **Primary**: https://github.com/librenms/librenms/tree/master/mibs/brocade
- **Secondary**: https://github.com/librenms/librenms/tree/master/mibs/foundry

## Usage

These MIB files are used by:
- LibreNMS OS detection and discovery
- SNMP tools for OID translation
- Code verification and OID path validation

## OID Corrections

Based on analysis of these MIB files, the following OID corrections were made to the code:

1. **Stack MAC Address**: `.1.3.6.1.4.1.1991.1.1.3.31.1.3.0` → `.1.3.6.1.4.1.1991.1.1.3.31.1.2.0`
2. **Stack Topology**: `.1.3.6.1.4.1.1991.1.1.3.31.1.2.0` → `.1.3.6.1.4.1.1991.1.1.3.31.1.5.0`
3. **Operational Table**: `.1.3.6.1.4.1.1991.1.1.3.31.3.1` → `.1.3.6.1.4.1.1991.1.1.3.31.2.2`

See `docs/MIB_OID_CORRECTIONS.md` for detailed analysis.

## Legal Notice

MIB files are typically copyrighted by the original manufacturer (Brocade/Ruckus/CommScope). These files are provided for reference and development purposes. Ensure you have the right to use and distribute these files in your environment.
