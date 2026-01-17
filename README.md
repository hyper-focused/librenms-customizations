# LibreNMS Foundry FCX Stacked Discovery

This project enhances LibreNMS support for Foundry (now Ruckus) FCX switches, with special focus on properly discovering and monitoring stacked switch configurations.

## Overview

Foundry FCX switches support stacking technology where multiple physical switches operate as a single logical unit. This project improves LibreNMS's ability to:

- Accurately detect Foundry FCX switches
- Discover stack configurations and members
- Monitor stack health and topology
- Track hardware inventory per stack member
- Alert on stack-related events

## Project Status

🚧 **Planning Phase** - Initial project setup and planning

See [PROJECT_PLAN.md](PROJECT_PLAN.md) for detailed project planning and technical approach.

## Quick Links

- **Project Plan**: [PROJECT_PLAN.md](PROJECT_PLAN.md) - Comprehensive project planning document
- **Implementation Notes**: Coming soon
- **SNMP Reference**: Coming soon
- **Testing Guide**: Coming soon

## Background

### About Foundry FCX Switches

- **Manufacturer**: Originally Foundry Networks → Brocade → Ruckus (CommScope)
- **Product Line**: FastIron FCX series
- **Key Feature**: Virtual chassis stacking (up to 8-12 units typically)
- **Management**: SNMP v2c/v3
- **Enterprise OID**: 1991

### Why This Project?

Current LibreNMS support for Foundry FCX switches, particularly in stacked configurations, is limited. This leads to:

- Incorrect or incomplete device discovery
- Missing stack member information
- No visibility into stack topology
- Inability to monitor stack health
- Poor hardware inventory management

## Project Goals

1. **Accurate OS Detection**: Properly identify Foundry FCX switches
2. **Stack Discovery**: Detect and enumerate all stack members
3. **Hardware Inventory**: Collect detailed information for each unit
4. **Stack Monitoring**: Track stack health, topology, and events
5. **Upstream Contribution**: Submit enhancements to LibreNMS project

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

### Foundry/Ruckus Resources
- Foundry Networks MIB files
- FastIron FCX Series documentation
- Ruckus ICX Switch documentation

## License

See [LICENSE](LICENSE) file for details.

## Contact & Support

This is an independent development project aimed at improving LibreNMS support for Foundry FCX switches.

For LibreNMS-related questions, please use the official LibreNMS community channels.

## Acknowledgments

- LibreNMS community and maintainers
- Foundry Networks / Brocade / Ruckus documentation
- Community members providing SNMP test data
