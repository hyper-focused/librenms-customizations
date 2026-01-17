# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial project structure and planning
- Comprehensive project plan (PROJECT_PLAN.md)
- SNMP reference documentation (docs/SNMP_REFERENCE.md)
- Implementation guide (docs/IMPLEMENTATION.md)
- Contributing guidelines (CONTRIBUTING.md)
- Platform differences documentation (docs/PLATFORM_DIFFERENCES.md)
- Directory structure for LibreNMS integration
- Git ignore file
- Support for Brocade/Ruckus ICX series (6450, 7150, 7250, 7450, 7650, 7750)
- Multi-platform architecture for FCX and ICX families
- Detailed ICX series model identification
- Platform detection strategy documentation

### Changed
- Expanded project scope from FCX-only to full IronWare/FastIron platform support
- Updated all documentation to include ICX series
- Renamed database schema to platform-agnostic names (`ironware_stacks`)
- Enhanced SNMP reference with both Foundry (1991) and Brocade (1588) enterprise OIDs

### Deprecated
- N/A

### Removed
- N/A

### Fixed
- N/A

### Security
- N/A

## Version History

### [0.1.0] - 2026-01-17

#### Project Initialization
- Created project repository structure
- Established comprehensive project plan covering all phases:
  - Research & Analysis
  - OS Discovery Enhancement
  - Stack Discovery Implementation
  - Hardware Discovery
  - Stack Monitoring
  - Testing & Documentation
  - Integration & Contribution
- Documented SNMP OIDs and MIB requirements for both platforms
- Created implementation guidelines for LibreNMS integration
- Established contributing guidelines

#### Multi-Platform Support
- Expanded scope to include Brocade/Ruckus ICX series
- Documented all ICX series models:
  - ICX 6450 (Campus Access)
  - ICX 7150 (Stackable Campus)
  - ICX 7250 (Advanced Campus)
  - ICX 7450 (Aggregation)
  - ICX 7650 (Data Center)
  - ICX 7750 (Modular/High-End)
- Created platform differences guide (PLATFORM_DIFFERENCES.md)
- Updated SNMP reference for dual enterprise OID support (1991, 1588)
- Designed platform-agnostic database schema
- Documented detection strategy for both FCX and ICX platforms

[Unreleased]: https://github.com/yourusername/librenms-foundry-fcx/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/yourusername/librenms-foundry-fcx/releases/tag/v0.1.0
