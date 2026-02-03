# LibreNMS FastIron & ICX Stack Discovery (Unified)

This project provides **one unified LibreNMS module** for **FastIron** (FCX, FWS, FLS, etc.) and **ICX** stackable switches — shared OS detection, discovery, and polling for both platforms.

## Overview

A single OS (`brocade-stack`) and one codebase handle:

- **FastIron** — FCX, FWS, FLS, and other IronWare/FastIron stackable and fixed switches
- **ICX** — All ICX stackable series (6430, 6450, 6610, 6650, 7150, 7250, 7450, 7650, 7750)

Capabilities:

- Detect FastIron and ICX devices with shared rules
- Discover stack topology and members
- Monitor stack health and per-unit inventory
- Track hardware (serial, model, version) per stack member
- Use the same MIB set (FOUNDRY-SN-*) for both platforms

## Supported Platforms (This Module)

### FastIron (IronWare / FastIron OS)
- **FCX** — FCX624, FCX648, etc.
- **FWS** — FastIron Workgroup Switch
- **FLS** — FastIron Layer 2/3 Switch
- Other FastIron stackable/fixed models using the same MIBs

### ICX (FastIron OS)
- **ICX 6430 / 6450 / 6610 / 6650 / 7150 / 7250 / 7450 / 7650 / 7750** — all covered by this unified module

### Optional
- **TurboIron** may be included here if it shares the same MIBs and stacking behavior; otherwise it can be handled in a separate routing/modular project.

A separate, **future project** (not started here) will address routing and modular platforms (e.g. NetIron, XMR, MLXe, CES/CER, SuperIron). See [docs/UNIFIED_PLATFORM_SCOPE.md](docs/UNIFIED_PLATFORM_SCOPE.md).

## Project Status

Implementation in progress; core unified module in place. See **[PROJECT_STATUS.md](PROJECT_STATUS.md)** for current state, done, and next steps.

## Quick Links

| Doc | Description |
|-----|-------------|
| [PROJECT_STATUS.md](PROJECT_STATUS.md) | Current status, done, next steps |
| [PROJECT_PLAN.md](PROJECT_PLAN.md) | Goals and technical plan |
| [docs/UNIFIED_PLATFORM_SCOPE.md](docs/UNIFIED_PLATFORM_SCOPE.md) | Scope: FastIron + ICX (this repo) vs future routing/modular project |
| [docs/brocade-stack-readme.md](docs/brocade-stack-readme.md) | Documentation index |
| [docs/SNMP_REFERENCE.md](docs/SNMP_REFERENCE.md) | OIDs and MIBs |
| [tests/TESTING_GUIDE.md](tests/TESTING_GUIDE.md) | Testing |
| [TODO.md](TODO.md) | Task list |

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

1. **Unified OS**: One module for FastIron (FCX, FWS, FLS, etc.) and ICX — shared detection, discovery, polling
2. **Stack Discovery**: Detect and enumerate all stack members on both platforms
3. **Hardware Inventory**: Collect detailed information per unit
4. **Stack Monitoring**: Track stack health, topology, and events
5. **Single Codebase**: No separate FastIron vs ICX OS; one `brocade-stack` OS and one PHP class
6. **Upstream Contribution**: Submit unified enhancements to LibreNMS

## Repository Structure

```
/
├── docs/                      # Documentation
│   ├── brocade-stack-implementation.md     # Implementation details
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
