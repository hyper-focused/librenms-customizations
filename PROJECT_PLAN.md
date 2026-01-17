# LibreNMS Foundry FCX Stacked Discovery Project Plan

## Project Overview

This project aims to improve OS discovery and monitoring capabilities for Foundry (Ruckus) FCX switches in LibreNMS, with special focus on stacked switch configurations.

## Background

### About Foundry FCX Switches
- **Vendor**: Originally Foundry Networks, acquired by Brocade, now part of Ruckus (CommScope)
- **Product Line**: FastIron FCX series (e.g., FCX648, FCX624)
- **Key Feature**: Stack technology allowing multiple switches to operate as a single logical unit
- **Protocol**: SNMP v2c/v3 for management and monitoring

### Current Limitations
- Incomplete or inaccurate OS detection
- Limited stack member discovery
- Missing stack health monitoring
- Inadequate handling of stack-specific metrics
- Poor differentiation between standalone and stacked configurations

## Project Objectives

### Primary Goals
1. **Accurate OS Detection**: Properly identify Foundry FCX switches and their firmware versions
2. **Stack Discovery**: Detect and enumerate all stack members
3. **Stack Topology**: Map stack connections and identify master/member roles
4. **Hardware Inventory**: Collect detailed hardware information for each stack member
5. **Stack Health Monitoring**: Track stack status, redundancy, and inter-switch links

### Secondary Goals
6. Enhanced port monitoring for stack ports
7. Stack failover event detection
8. Performance metrics for stacked configurations
9. Proper handling of stack serial numbers and asset tracking

## Technical Approach

### Phase 1: Research & Analysis
- [ ] Analyze Foundry FCX MIB files
- [ ] Document SNMP OIDs for:
  - System identification
  - Stack detection
  - Stack member enumeration
  - Hardware details
  - Stack topology
- [ ] Review existing LibreNMS OS definitions
- [ ] Test SNMP walks on live FCX switches (standalone and stacked)
- [ ] Document current vs. desired behavior

### Phase 2: OS Discovery Enhancement
- [ ] Create/update OS definition for Foundry FCX
- [ ] Implement OS detection logic using:
  - sysObjectID
  - sysDescr patterns
  - Enterprise OID matching
- [ ] Add version detection from SNMP data
- [ ] Create test cases for OS detection

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
│           └── foundry.inc.php          # OS detection logic
├── includes/
│   └── definitions/
│       └── foundry.yaml                 # OS definition file
├── includes/
│   └── polling/
│       └── foundry-stack.inc.php        # Stack polling module
├── mibs/
│   ├── foundry/
│   │   ├── FOUNDRY-SN-ROOT-MIB
│   │   ├── FOUNDRY-SN-AGENT-MIB
│   │   ├── FOUNDRY-SN-SWITCH-GROUP-MIB
│   │   └── FOUNDRY-SN-STACKING-MIB
├── sql-schema/
│   └── migrations/
│       └── XXX_add_foundry_stack_tables.sql
├── tests/
│   ├── OSDiscoveryTest.php
│   └── data/
│       └── foundry_*.json              # Test SNMP data
└── docs/
    ├── IMPLEMENTATION.md
    └── SNMP_REFERENCE.md
```

### Key SNMP OIDs (Preliminary)

#### System Identification
- **sysObjectID**: `.1.3.6.1.4.1.1991.1.*` (Foundry Enterprise ID: 1991)
- **sysDescr**: `.1.3.6.1.2.1.1.1.0` (Contains "Foundry" or "FCX")

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

#### Stack Tables
```sql
-- Main stack information table
foundry_stacks (
    stack_id INT PRIMARY KEY AUTO_INCREMENT,
    device_id INT FOREIGN KEY,
    stack_count INT,
    stack_master_unit INT,
    last_discovered TIMESTAMP
)

-- Stack member details
foundry_stack_members (
    member_id INT PRIMARY KEY AUTO_INCREMENT,
    stack_id INT FOREIGN KEY,
    unit_id INT,
    unit_role ENUM('master', 'member', 'standalone'),
    unit_state VARCHAR(32),
    unit_mac VARCHAR(17),
    unit_priority INT,
    serial_number VARCHAR(64),
    model VARCHAR(64),
    sw_version VARCHAR(64),
    last_seen TIMESTAMP
)

-- Stack port status
foundry_stack_ports (
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
- Access to Foundry FCX switches (physical or virtual)
- SNMP tools (snmpwalk, snmpget, snmpbulkwalk)
- PHP 8.1+
- MySQL/MariaDB
- Foundry MIB files

### Testing Strategy
1. **Unit Testing**: Mock SNMP responses for various scenarios
2. **Integration Testing**: Test against actual devices
3. **Regression Testing**: Ensure existing functionality not broken
4. **Edge Cases**:
   - Standalone switch
   - 2-unit stack
   - Maximum stack size (typically 8-12 units)
   - Heterogeneous stacks (mixed models)
   - Stack with failed members
   - Stack during master failover

## Success Criteria

1. ✅ FCX switches correctly identified as "foundry" OS
2. ✅ Firmware version accurately extracted
3. ✅ Stack configuration detected (vs. standalone)
4. ✅ All stack members discovered and tracked
5. ✅ Stack master correctly identified
6. ✅ Hardware inventory complete for each unit
7. ✅ Stack topology properly mapped
8. ✅ Stack health monitoring functional
9. ✅ Alerts triggered for stack events
10. ✅ Code accepted into LibreNMS upstream

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
