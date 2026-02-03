# LibreNMS FastIron & ICX Stack Discovery — Project Plan

**Note:** This project uses a **single unified OS** (`brocade-stack`) for both FastIron (FCX, FWS, FLS, etc.) and ICX. One codebase, one set of definitions. See [docs/UNIFIED_PLATFORM_SCOPE.md](docs/UNIFIED_PLATFORM_SCOPE.md).

## Project Overview

This project improves OS discovery and monitoring for IronWare/FastIron-based stackable switches in LibreNMS, with a **unified module** covering FastIron and ICX (no separate OS per platform).

## Background

### About IronWare/FastIron Platform
- **Vendor History**: Foundry Networks → Brocade → Ruckus (CommScope)
- **Product Lines**: 
  - **Foundry**: FCX series (legacy, IronWare OS)
  - **Brocade/Ruckus**: ICX series (current, FastIron OS)
- **Key Feature**: Virtual chassis stacking allowing multiple switches to operate as a single logical unit
- **Protocol**: SNMP v2c/v3 for management and monitoring
- **Enterprise OIDs**: 1991 (Foundry), 1588 (Brocade)

### Supported Switch Families

#### Foundry FCX Series (Legacy)
- FCX624, FCX648
- IronWare operating system
- Original stacking implementation
- Enterprise OID: 1991

#### Brocade/Ruckus ICX Series (Current)
- **ICX 6450**: 24/48-port campus access switches
- **ICX 7150**: Stackable campus switches with PoE
- **ICX 7250**: Advanced Layer 3 campus switches
- **ICX 7450**: Campus aggregation switches
- **ICX 7650**: High-performance data center switches
- **ICX 7750**: Modular chassis and stackable switches
- FastIron operating system
- Enhanced stacking features
- Enterprise OID: 1588 (also may use 1991 for compatibility)

### Current Limitations
- Incomplete or inaccurate OS detection for both FCX and ICX families
- Limited stack member discovery across platforms
- Missing stack health monitoring
- Inadequate handling of stack-specific metrics
- Poor differentiation between standalone and stacked configurations
- No differentiation between FCX (Foundry) and ICX (Brocade/Ruckus) platforms
- Missing support for ICX-specific features and enhancements

## Project Objectives

### Primary Goals
1. **Accurate OS Detection**: Properly identify IronWare/FastIron switches (FCX and ICX) and their firmware versions
2. **Multi-Platform Support**: Handle both Foundry (FCX) and Brocade/Ruckus (ICX) platforms
3. **Stack Discovery**: Detect and enumerate all stack members across all platforms
4. **Stack Topology**: Map stack connections and identify master/member roles
5. **Hardware Inventory**: Collect detailed hardware information for each stack member
6. **Stack Health Monitoring**: Track stack status, redundancy, and inter-switch links

### Secondary Goals
7. Enhanced port monitoring for stack ports
8. Stack failover event detection
9. Performance metrics for stacked configurations
10. Proper handling of stack serial numbers and asset tracking
11. ICX-specific feature detection (e.g., longer stack support, enhanced monitoring)
12. Backward compatibility with older IronWare versions

## Technical Approach

### Phase 1: Research & Analysis
- [ ] Analyze MIB files for all platforms:
  - Foundry FCX MIBs (IronWare)
  - Brocade ICX MIBs (FastIron)
  - Ruckus ICX MIBs (latest versions)
- [ ] Document SNMP OIDs for:
  - System identification (both enterprise OIDs)
  - Stack detection
  - Stack member enumeration
  - Hardware details
  - Stack topology
  - Platform-specific differences
- [ ] Review existing LibreNMS OS definitions
- [ ] Test SNMP walks on live switches:
  - Foundry FCX (standalone and stacked)
  - Brocade ICX 6450, 7150, 7250, 7450, 7650, 7750
  - Various firmware versions
- [ ] Document current vs. desired behavior
- [ ] Identify MIB compatibility and differences between platforms

### Phase 2: OS Discovery Enhancement
- [ ] Create/update OS definitions:
  - Foundry (FCX series, IronWare)
  - Brocade ICX series (FastIron)
  - Ruckus ICX series (FastIron, latest)
- [ ] Implement OS detection logic using:
  - sysObjectID (both 1991 and 1588 enterprise OIDs)
  - sysDescr patterns (Foundry, FastIron, ICX, FCX)
  - Enterprise OID matching
  - Model number extraction
- [ ] Add version detection from SNMP data:
  - IronWare version format
  - FastIron version format
- [ ] Platform differentiation (FCX vs ICX)
- [ ] Create test cases for OS detection across all platforms

### Phase 3: Stack Discovery Implementation
- [ ] Implement stack detection logic
- [ ] Create database schema for stack information
- [ ] Develop stack member discovery module
- [ ] Add stack topology mapping
- [ ] Implement stack role detection (master/member/standalone)

### Phase 4: Hardware Discovery
- [ ] Implement per-unit hardware inventory
- [ ] Add serial number collection for each stack member
- [ ] Collect model information for heterogeneous stacks
- [ ] Add module/line card discovery
- [ ] Implement firmware version tracking per unit

### Phase 5: Stack Monitoring
- [ ] Create stack health checks
- [ ] Add stack port status monitoring
- [ ] Implement inter-stack link monitoring
- [ ] Add alerting for stack events:
  - Member addition/removal
  - Stack master change
  - Stack link failures
  - Version mismatch warnings

### Phase 6: Testing & Documentation
- [ ] Unit tests for discovery modules
- [ ] Integration tests with test devices
- [ ] Performance testing with large stacks
- [ ] User documentation
- [ ] Developer documentation
- [ ] Example configurations

### Phase 7: Integration & Contribution
- [ ] Code review and refinement
- [ ] Compliance with LibreNMS coding standards
- [ ] Create pull request to LibreNMS repository
- [ ] Address community feedback
- [ ] Final testing and validation

## Technical Components

### File Structure
```
/
├── includes/
│   └── discovery/
│       └── os/
│           ├── foundry.inc.php          # Foundry FCX OS detection
│           └── icx.inc.php              # Brocade/Ruckus ICX detection
├── includes/
│   └── definitions/
│       ├── foundry.yaml                 # Foundry OS definition
│       └── icx.yaml                     # ICX OS definition
├── includes/
│   └── polling/
│       └── brocade-stack.inc.php       # Stack polling for all platforms
├── mibs/
│   ├── foundry/
│   │   ├── FOUNDRY-SN-ROOT-MIB
│   │   ├── FOUNDRY-SN-AGENT-MIB
│   │   ├── FOUNDRY-SN-SWITCH-GROUP-MIB
│   │   └── FOUNDRY-SN-STACKING-MIB
│   └── brocade/
│       ├── BROCADE-REG-MIB
│       ├── BROCADE-PRODUCTS-MIB
│       ├── BROCADE-ENTITY-MIB
│       └── BROCADE-STACKABLE-MIB
├── sql-schema/
│   └── migrations/
│       └── XXX_add_brocade_stack_tables.sql
├── tests/
│   ├── OSDiscoveryTest.php
│   └── data/
│       ├── foundry_*.json              # Foundry test data
│       └── icx_*.json                  # ICX test data
└── docs/
    ├── IMPLEMENTATION.md
    ├── SNMP_REFERENCE.md
    └── PLATFORM_DIFFERENCES.md          # Platform-specific notes
```

### Key SNMP OIDs (Preliminary)

#### System Identification
- **sysObjectID**: 
  - `.1.3.6.1.4.1.1991.1.*` (Foundry Enterprise ID: 1991)
  - `.1.3.6.1.4.1.1588.2.1.*` (Brocade Enterprise ID: 1588)
- **sysDescr**: `.1.3.6.1.2.1.1.1.0` (Contains "Foundry", "FCX", "FastIron", "ICX", or "Brocade")

#### Stack Information (Foundry Specific)
- **snStackingConfigUnitTable**: Information about stack units
- **snStackingOperUnitRole**: Master/Member/Standalone status
- **snStackingOperUnitState**: Unit operational state
- **snStackingOperUnitMac**: MAC address per unit
- **snStackingOperUnitPriority**: Unit priority for master election

#### Hardware Details
- **snChasUnitIndex**: Chassis unit index in stack
- **snChasUnitSerNum**: Serial number per unit
- **snChasUnitDescription**: Model description
- **entPhysicalTable**: Standard ENTITY-MIB data

### Database Schema

#### Stack Tables (Platform-Agnostic)
```sql
-- Main stack information table (supports FCX and ICX)
brocade_stack_topologies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_id INT FOREIGN KEY,
    topology VARCHAR(16),            -- 'ring', 'chain', 'standalone', 'unknown'
    unit_count INT,
    master_unit INT,
    stack_mac VARCHAR(17),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)

-- Stack member details
brocade_stack_members (
    member_id INT PRIMARY KEY AUTO_INCREMENT,
    stack_id INT FOREIGN KEY,
    unit_id INT,
    unit_role ENUM('master', 'member', 'standalone'),
    unit_state VARCHAR(32),
    unit_mac VARCHAR(17),
    unit_priority INT,
    serial_number VARCHAR(64),
    model VARCHAR(64),               -- FCX648, ICX7150, etc.
    sw_version VARCHAR(64),
    last_seen TIMESTAMP
)

-- Stack port status
brocade_stack_ports (
    port_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT FOREIGN KEY,
    port_index INT,
    port_oper_status ENUM('up', 'down'),
    neighbor_unit INT,
    last_updated TIMESTAMP
)
```

## Development Environment

### Requirements
- LibreNMS development environment (Docker or local)
- Access to test switches (physical or virtual):
  - Foundry FCX series
  - Brocade/Ruckus ICX series (6450, 7150, 7250, 7450, 7650, 7750)
- SNMP tools (snmpwalk, snmpget, snmpbulkwalk)
- PHP 8.1+
- MySQL/MariaDB
- MIB files:
  - Foundry MIBs (IronWare)
  - Brocade MIBs (FastIron)

### Testing Strategy
1. **Unit Testing**: Mock SNMP responses for various scenarios
2. **Integration Testing**: Test against actual devices
3. **Regression Testing**: Ensure existing functionality not broken
4. **Cross-Platform Testing**: Verify all platforms work correctly
5. **Edge Cases**:
   - Standalone switch (FCX and ICX)
   - 2-unit stack
   - Maximum stack size (8-12 units)
   - Heterogeneous stacks (mixed models within same family)
   - Stack with failed members
   - Stack during master failover
   - Different firmware versions
   - Mixed IronWare/FastIron versions

## Success Criteria

1. ✅ FCX switches correctly identified as "foundry" OS
2. ✅ ICX switches correctly identified as "icx" OS
3. ✅ Firmware version accurately extracted (IronWare and FastIron formats)
4. ✅ Platform correctly detected (Foundry vs Brocade/Ruckus)
5. ✅ Stack configuration detected (vs. standalone)
6. ✅ All stack members discovered and tracked across all platforms
7. ✅ Stack master correctly identified
8. ✅ Hardware inventory complete for each unit
9. ✅ Stack topology properly mapped
10. ✅ Stack health monitoring functional
11. ✅ Alerts triggered for stack events
12. ✅ ICX-specific models properly differentiated (6450, 7150, 7250, etc.)
13. ✅ Code accepted into LibreNMS upstream

## Timeline & Milestones

### Milestone 1: Foundation
- Research complete
- MIB analysis done
- OS definition created
- Basic detection working

### Milestone 2: Core Discovery
- Stack detection implemented
- Member enumeration working
- Database schema deployed
- Basic hardware discovery

### Milestone 3: Full Monitoring
- Stack topology mapped
- Health monitoring active
- Alerting configured
- Performance metrics collected

### Milestone 4: Production Ready
- All tests passing
- Documentation complete
- Code review addressed
- Pull request submitted

## Resources & References

### LibreNMS Documentation
- [Adding a new OS](https://docs.librenms.org/Developing/os/Initial-Detection/)
- [YAML-based OS definition](https://docs.librenms.org/Developing/os/YAML-OS-Definition/)
- [Writing discovery modules](https://docs.librenms.org/Developing/Discovery-Development/)

### Foundry/Ruckus Resources
- Foundry MIB files
- FCX CLI reference guide
- SNMP configuration guide
- Stack configuration documentation

### SNMP Standards
- RFC 3411-3418 (SNMPv3)
- RFC 2863 (IF-MIB)
- RFC 4133 (ENTITY-MIB)

## Risk Assessment

### Technical Risks
- **MIB Availability**: May need to source older Foundry MIBs
- **Device Access**: May have limited access to physical stacks for testing
- **SNMP Variations**: Different firmware versions may report differently
- **Heterogeneous Stacks**: Mixed models may complicate discovery

### Mitigation Strategies
- Collect SNMP walks from community members
- Use snmpsim for simulated testing
- Support version detection and adaptation
- Document known limitations

## Community Engagement

### Contribution Process
1. Discuss approach on LibreNMS Discord/GitHub
2. Create draft implementation
3. Share for early feedback
4. Iterate based on community input
5. Submit formal pull request
6. Respond to code review
7. Coordinate with maintainers for merge

### Documentation Needs
- User guide for enabling Foundry stack monitoring
- Troubleshooting guide
- Example SNMP outputs
- Configuration examples
- Screenshots/examples

## Next Steps

1. **Immediate**: Set up LibreNMS development environment
2. **Immediate**: Collect Foundry FCX MIB files
3. **Week 1**: Complete SNMP analysis and OID documentation
4. **Week 1**: Create basic OS definition
5. **Week 2**: Implement stack detection logic
6. **Week 2-3**: Develop full discovery module
7. **Week 3-4**: Add monitoring and alerting
8. **Week 4+**: Testing, documentation, and contribution

## Notes

- This is a living document and will be updated as the project progresses
- Focus on code quality and maintainability for upstream acceptance
- Prioritize compatibility with existing LibreNMS architecture
- Consider backward compatibility with older FCX firmware versions
- Plan for extensibility to other Foundry/Ruckus switch models (ICX series)
