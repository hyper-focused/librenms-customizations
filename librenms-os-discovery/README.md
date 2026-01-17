# LibreNMS Brocade Ironware OS Discovery Enhancement

This project aims to improve OS discovery and monitoring capabilities for Brocade/Ruckus Ironware switches in LibreNMS, with particular focus on stacked switch configurations. It supports both FCX and ICX series switches.

## Supported Platforms

### FCX Series
- FCX648S, FCX624S, and other FCX models
- Original Foundry Networks switches rebranded by Brocade

### ICX Series
- ICX 6450 (stackable)
- ICX 7150 (stackable)
- ICX 7250 (stackable)
- ICX 7450 (stackable)
- ICX 7750 (stackable)
- Modern Ruckus Networks switches

## Problem Statement

Brocade Ironware switches (FCX and ICX series), especially when deployed in stack configurations, present unique challenges for network monitoring systems like LibreNMS:

- **Stack Discovery Issues**: Traditional OS discovery methods often fail to properly identify and monitor individual stack members
- **Inconsistent MIB Support**: FCX switches use a mix of Foundry and Brocade MIBs that aren't fully supported in standard LibreNMS distributions
- **Stack Member Identification**: Difficulty in distinguishing between master and member switches in a stack
- **Firmware Version Detection**: Challenges in accurately detecting and reporting firmware versions across stack members

## Project Goals

1. **Enhanced OS Discovery**: Develop robust discovery mechanisms for FCX switches in both standalone and stacked configurations
2. **Stack Member Detection**: Implement proper identification and monitoring of individual stack members
3. **MIB Optimization**: Provide optimized MIB files and polling configurations for FCX switches
4. **Testing Framework**: Create comprehensive testing suite with mock data for validation
5. **Documentation**: Provide detailed guides for deployment and troubleshooting

## Project Structure

```
librenms-os-discovery/
├── includes/
│   ├── discovery/
│   │   └── os/
│   │       └── brocade-ironware.inc.php    # Main OS discovery module
│   └── definitions/
│       ├── foundry-fcx.yaml                # FCX device definition
│       ├── brocade-icx.yaml                # Base ICX device definition
│       ├── brocade-icx6450.yaml            # ICX 6450 specific definition
│       ├── brocade-icx7150.yaml            # ICX 7150 specific definition
│       ├── brocade-icx7250.yaml            # ICX 7250 specific definition
│       ├── brocade-icx7450.yaml            # ICX 7450 specific definition
│       └── brocade-icx7750.yaml            # ICX 7750 specific definition
├── mibs/                                   # MIB files for Ironware switches
├── tests/
│   ├── unit/
│   │   └── BrocadeIronwareDiscoveryTest.php # Unit tests
│   ├── integration/                        # Integration tests
│   └── mocks/
│       └── brocade-ironware-mock.php       # Mock SNMP data
├── docs/
│   └── brocade-ironware-stack-discovery-challenges.md
├── scripts/
│   └── test-discovery.php                  # Test script
└── config/                                # Configuration files
```

## Installation and Setup

### Prerequisites

- LibreNMS installation (version 1.70+ recommended)
- PHP 7.4+ with SNMP extensions
- Access to Foundry FCX switches for testing

### Installation Steps

1. Clone this repository:
   ```bash
   git clone <repository-url>
   cd librenms-os-discovery
   ```

2. Copy discovery files to LibreNMS:
   ```bash
   cp includes/discovery/os/brocade-ironware.inc.php /opt/librenms/includes/discovery/os/
   cp includes/definitions/foundry-fcx.yaml /opt/librenms/includes/definitions/
   cp includes/definitions/brocade-icx*.yaml /opt/librenms/includes/definitions/
   ```

3. Copy MIB files:
   ```bash
   cp mibs/* /opt/librenms/mibs/
   ```

4. Update LibreNMS MIB cache:
   ```bash
   cd /opt/librenms
   ./scripts/compile-mibs.sh
   ```

5. Restart LibreNMS services:
   ```bash
   systemctl restart librenms
   ```

## Testing

Run the test suite to validate the implementation:

```bash
cd tests
phpunit unit/
phpunit integration/
```

## Configuration

### Stack Discovery Configuration

For optimal stack discovery, configure the following in LibreNMS:

```yaml
# In foundry-fcx.yaml
mib: FOUNDRY-SN-ROOT
modules:
  - os
  - stack
```

### SNMP Configuration

Ensure FCX switches are configured with proper SNMP settings:

```
snmp-server community public ro
snmp-server contact "Network Admin"
snmp-server location "Data Center"
```

## Known Issues and Limitations

- Stack discovery may require manual intervention for complex topologies
- Some older FCX firmware versions may have limited MIB support
- High-availability stack configurations need additional testing

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For issues and questions:
- Check the documentation in the `docs/` directory
- Review LibreNMS logs for discovery errors
- Test with mock data before deploying to production

## Changelog

### v1.1.0 (ICX Support Release)
- Extended support for ICX series switches (6450, 7150, 7250, 7450, 7750)
- Unified Brocade Ironware discovery module
- Added ICX-specific device definitions
- Enhanced testing framework with ICX mock data
- Updated documentation for ICX platforms

### v1.0.0 (Initial Release)
- Basic OS discovery for FCX switches
- Stack member identification
- Initial MIB file collection
- Testing framework setup