# LibreNMS Foundry/Brocade/Ruckus IronWare Stack Discovery

This project enhances LibreNMS support for Foundry, Brocade, and Ruckus switches running IronWare/FastIron OS, with special focus on properly discovering and monitoring stacked switch configurations.

## Overview

IronWare-based switches (Foundry FCX, Brocade/Ruckus ICX series) support advanced stacking technology where multiple physical switches operate as a single logical unit. This project improves LibreNMS's ability to:

- Accurately detect IronWare-based switches (FCX, ICX series)
- Discover stack configurations and members
- Monitor stack health and topology
- Track hardware inventory per stack member
- Alert on stack-related events

## Supported Platforms

### Foundry Networks (Legacy)
- **FCX Series**: FCX624, FCX648 (original Foundry branding)

### Brocade/Ruckus (Current)
- **ICX 6450 Series**: Campus access switches
- **ICX 7150 Series**: Stackable campus switches
- **ICX 7250 Series**: Advanced campus switches
- **ICX 7450 Series**: Aggregation/core switches
- **ICX 7650 Series**: High-performance data center switches
- **ICX 7750 Series**: Modular chassis and stackable switches

All platforms run IronWare (Foundry) or FastIron (Brocade/Ruckus) operating systems and share similar SNMP MIB structures for stack management.

## Project Status

🚧 **Planning Phase** - Initial project setup and planning

See [PROJECT_PLAN.md](PROJECT_PLAN.md) for detailed project planning and technical approach.

## Quick Links

- **Project Plan**: [PROJECT_PLAN.md](PROJECT_PLAN.md) - Comprehensive project planning document
- **Implementation Notes**: Coming soon
- **SNMP Reference**: Coming soon
- **Testing Guide**: Coming soon

## Background

### About IronWare-Based Switches

- **Manufacturer**: Originally Foundry Networks → Brocade → Ruckus (CommScope)
- **Product Lines**: 
  - Foundry FCX series (legacy)
  - Brocade/Ruckus ICX series (current)
- **Operating Systems**: IronWare (Foundry) / FastIron (Brocade/Ruckus)
- **Key Feature**: Virtual chassis stacking (up to 12 units)
- **Management**: SNMP v2c/v3, CLI
- **Enterprise OID**: 1991 (Foundry), 1588 (Brocade)

### Why This Project?

Current LibreNMS support for IronWare-based switches, particularly in stacked configurations, is limited. This leads to:

- Incorrect or incomplete device discovery
- Missing stack member information
- No visibility into stack topology
- Inability to monitor stack health
- Poor hardware inventory management

## Project Goals

1. **Accurate OS Detection**: Properly identify IronWare-based switches (FCX and ICX series)
2. **Stack Discovery**: Detect and enumerate all stack members
3. **Hardware Inventory**: Collect detailed information for each unit
4. **Stack Monitoring**: Track stack health, topology, and events
5. **Multi-Platform Support**: Handle differences between Foundry, Brocade, and Ruckus variants
6. **Upstream Contribution**: Submit enhancements to LibreNMS project

## Repository Structure

```
/
├── docs/                      # Documentation
│   ├── IMPLEMENTATION.md     # Implementation details
│   └── SNMP_REFERENCE.md     # SNMP OID reference
├── includes/                  # LibreNMS discovery/polling code
│   ├── discovery/
│   ├── definitions/
│   └── polling/
├── mibs/                      # Foundry MIB files
├── sql-schema/                # Database migrations
├── tests/                     # Test cases and test data
│   └── data/
└── examples/                  # Example configurations and outputs
```

## Getting Started

### Prerequisites

- LibreNMS development environment
- PHP 8.1 or higher
- MySQL/MariaDB
- SNMP tools (snmpwalk, snmpget)
- Access to Foundry FCX switches (or SNMP simulation data)

### Development Setup

Instructions coming soon.

### Testing

Instructions coming soon.

## Contributing to Upstream LibreNMS

This project is developed with the goal of contributing back to the LibreNMS project. All code will follow LibreNMS coding standards and contribution guidelines.

- [LibreNMS GitHub](https://github.com/librenms/librenms)
- [LibreNMS Documentation](https://docs.librenms.org/)
- [LibreNMS Discord](https://discord.gg/librenms)

## References

### LibreNMS Documentation
- [Adding a new OS](https://docs.librenms.org/Developing/os/Initial-Detection/)
- [YAML-based OS Definition](https://docs.librenms.org/Developing/os/YAML-OS-Definition/)
- [Discovery Development](https://docs.librenms.org/Developing/Discovery-Development/)

### Foundry/Brocade/Ruckus Resources
- Foundry Networks MIB files (IronWare)
- Brocade FastIron MIB files
- Ruckus ICX Switch documentation and MIBs
- FastIron configuration and management guides

## License

See [LICENSE](LICENSE) file for details.

## Contact & Support

This is an independent development project aimed at improving LibreNMS support for Foundry FCX switches.

For LibreNMS-related questions, please use the official LibreNMS community channels.

## Acknowledgments

- LibreNMS community and maintainers
- Foundry Networks / Brocade / Ruckus (CommScope) documentation
- Community members providing SNMP test data from various switch models
- Network engineers supporting IronWare/FastIron platforms
