# TODO List - IronWare/FastIron Stack Discovery Project

This file tracks outstanding tasks for the project. Mark items as completed with `[x]`.

## Phase 1: Research & Analysis

### MIB Collection
- [ ] Obtain Foundry FCX MIB files
  - [ ] FOUNDRY-SN-ROOT-MIB
  - [ ] FOUNDRY-SN-AGENT-MIB
  - [ ] FOUNDRY-SN-SWITCH-GROUP-MIB
  - [ ] FOUNDRY-SN-STACKING-MIB
- [ ] Obtain Brocade/Ruckus ICX MIB files
  - [ ] BROCADE-REG-MIB
  - [ ] BROCADE-PRODUCTS-MIB
  - [ ] BROCADE-ENTITY-MIB
  - [ ] BROCADE-STACKABLE-MIB
- [ ] Analyze MIB files and document all relevant OIDs
- [ ] Document MIB compatibility between FCX and ICX

### SNMP Data Collection - FCX Series
- [ ] Collect SNMP walks from FCX switches:
  - [ ] Standalone FCX624
  - [ ] Standalone FCX648
  - [ ] 2-unit FCX stack
  - [ ] Large FCX stack (4+ units)
  - [ ] Degraded FCX stack

### SNMP Data Collection - ICX Series
- [ ] Collect SNMP walks from ICX switches:
  - [ ] Standalone ICX6450-24
  - [ ] Standalone ICX6450-48
  - [ ] Standalone ICX7150-24
  - [ ] Standalone ICX7150-48
  - [ ] 2-unit ICX7150 stack
  - [ ] Large ICX7150 stack (8+ units)
  - [ ] Maximum ICX7450 stack (12 units)
  - [ ] ICX7250 series
  - [ ] ICX7450 series
  - [ ] ICX7650 series
  - [ ] ICX7750 series
  - [ ] Degraded ICX stack
  - [ ] Mixed ICX models in stack (if supported)

### Analysis
- [ ] Review existing LibreNMS OS implementations for similar devices
- [ ] Document current behavior vs. desired behavior
- [ ] Identify firmware versions to support:
  - [ ] IronWare versions (FCX)
  - [ ] FastIron versions (ICX)
- [ ] Document platform-specific differences
- [ ] Create compatibility matrix

## Phase 2: OS Discovery Enhancement

### Foundry FCX Support
- [ ] Create `includes/definitions/foundry.yaml`
- [ ] Implement `includes/discovery/os/foundry.inc.php`
  - [ ] sysObjectID detection (Enterprise 1991)
  - [ ] sysDescr parsing (Foundry patterns)
  - [ ] IronWare version extraction
  - [ ] FCX model detection (FCX624, FCX648)
  - [ ] Basic stack detection
- [ ] Test FCX OS detection with various models
- [ ] Validate IronWare version parsing

### Brocade/Ruckus ICX Support
- [ ] Create `includes/definitions/icx.yaml`
- [ ] Implement `includes/discovery/os/icx.inc.php`
  - [ ] sysObjectID detection (Enterprise 1588, also check 1991)
  - [ ] sysDescr parsing (Brocade, Ruckus, ICX, FastIron patterns)
  - [ ] FastIron version extraction
  - [ ] ICX model detection (6450, 7150, 7250, 7450, 7650, 7750)
  - [ ] Series identification
  - [ ] Basic stack detection
- [ ] Test ICX OS detection across all series
- [ ] Validate FastIron version parsing
- [ ] Handle both "Brocade" and "Ruckus" branding

### Testing
- [ ] Create test cases for FCX OS detection
- [ ] Create test cases for ICX OS detection
- [ ] Test cross-platform detection (ensure no false positives)
- [ ] Validate version string parsing for different firmware versions

## Phase 3: Stack Discovery Implementation

### Database Design
- [ ] Design platform-agnostic database schema (`ironware_stacks`)
- [ ] Add platform column ('fcx' or 'icx')
- [ ] Support different stack sizes (8 vs 12 units)
- [ ] Create migration script for database changes
- [ ] Test migration on development database

### Core Stack Discovery
- [ ] Implement unified stack discovery module (`ironware-stack.inc.php`)
  - [ ] Platform detection (FCX vs ICX)
  - [ ] Stack topology detection (ring/chain/standalone)
  - [ ] Stack member enumeration
  - [ ] Master/member role detection
  - [ ] Unit state monitoring
  - [ ] MAC address collection
  - [ ] Priority values
- [ ] Implement stack port discovery
- [ ] Add stack member hardware detection
- [ ] Handle platform-specific stack features

### Testing
- [ ] Test with FCX stack configurations (2, 4, 8 units)
- [ ] Test with ICX stack configurations (2, 8, 12 units)
- [ ] Test standalone switches (both platforms)
- [ ] Handle edge cases:
  - [ ] Failed members
  - [ ] Empty slots
  - [ ] Mixed firmware versions
  - [ ] Ring-to-chain transition (degraded)
- [ ] Cross-platform regression testing

## Phase 4: Hardware Discovery

### FCX Hardware Discovery
- [ ] Implement FCX serial number collection per unit
- [ ] Add FCX model detection for each stack member
- [ ] Collect IronWare version per unit
- [ ] Test FCX heterogeneous stacks (FCX624 + FCX648)

### ICX Hardware Discovery
- [ ] Implement ICX serial number collection per unit
- [ ] Add ICX model detection for each stack member
- [ ] Support ICX series identification (6450, 7150, etc.)
- [ ] Collect FastIron version per unit
- [ ] Handle Brocade vs Ruckus branding differences
- [ ] Test ICX heterogeneous stacks (if supported)

### Advanced Features
- [ ] Add module/line card discovery (if applicable)
- [ ] Integrate with entity-physical discovery (ENTITY-MIB)
- [ ] Leverage BROCADE-ENTITY-MIB for ICX switches
- [ ] Collect PoE information
- [ ] Test with mixed hardware configurations
- [ ] Validate against high-end models (ICX7750)

## Phase 5: Stack Monitoring (Polling)

- [ ] Implement stack polling module
  - [ ] Stack health status
  - [ ] Member state changes
  - [ ] Stack port status
  - [ ] Master unit tracking
- [ ] Add performance metrics
  - [ ] Stack port utilization
  - [ ] Unit CPU/memory (if available)
- [ ] Implement stack event detection
  - [ ] Member addition/removal
  - [ ] Master change events
  - [ ] Stack link failures
- [ ] Create alert rules for stack events
- [ ] Add SNMP trap handling

## Phase 6: Testing & Documentation

### Testing
- [ ] Create unit tests
  - [ ] OS detection tests
  - [ ] Stack discovery tests
  - [ ] Polling tests
- [ ] Create integration tests
  - [ ] Full discovery workflow
  - [ ] Database operations
  - [ ] API endpoints (if created)
- [ ] Set up SNMP simulator for testing
- [ ] Test with real hardware
  - [ ] Standalone switch
  - [ ] Small stack (2-3 units)
  - [ ] Large stack (8+ units)
  - [ ] Degraded stack
- [ ] Performance testing
  - [ ] Discovery performance
  - [ ] Polling performance
  - [ ] Database query performance
- [ ] Create test data files

### Documentation
- [ ] Complete SNMP_REFERENCE.md with all OIDs
- [ ] Complete IMPLEMENTATION.md with code examples
- [ ] Write user documentation
  - [ ] Configuration guide
  - [ ] Troubleshooting guide
  - [ ] FAQ
- [ ] Create example configurations
- [ ] Add screenshots/diagrams
  - [ ] Stack topology visualization
  - [ ] Web interface screenshots
  - [ ] Graph examples
- [ ] Document known limitations
- [ ] Create upgrade/migration guide

## Phase 7: Integration & Contribution

- [ ] Code review and cleanup
  - [ ] Follow PSR-2 coding standards
  - [ ] Remove debug code
  - [ ] Optimize queries
  - [ ] Add error handling
- [ ] Run PHP linter and fix issues
- [ ] Run PHPStan/Psalm static analysis
- [ ] Ensure all tests pass
- [ ] Update LibreNMS documentation wiki
- [ ] Create contribution to LibreNMS
  - [ ] Fork LibreNMS repository
  - [ ] Create feature branch
  - [ ] Integrate code
  - [ ] Submit pull request
- [ ] Respond to code review feedback
- [ ] Address maintainer comments
- [ ] Final testing before merge

## Additional Tasks

### Web Interface
- [ ] Design stack overview page
- [ ] Create stack topology visualization
- [ ] Add member detail views
- [ ] Implement stack health dashboard
- [ ] Add custom graphs for stack metrics
- [ ] Integrate with existing device overview

### API Development
- [ ] Add API endpoints for stack information
- [ ] Document API usage
- [ ] Create API examples
- [ ] Test API responses

### Advanced Features
- [ ] Stack port utilization graphs
- [ ] Predictive failure detection
- [ ] Configuration backup per unit
- [ ] Firmware consistency checker
- [ ] Stack capacity planning tools

### Extended Platform Support
- [x] Add ICX series support (6450, 7150, 7250, 7450, 7650, 7750)
- [ ] Test with all ICX series models
- [ ] Support other Foundry switch models (if needed)
- [ ] Test with different IronWare/FastIron versions
- [ ] Validate Ruckus Cloud integration (if applicable)
- [ ] Support future ICX models

## Issues & Blockers

Document any blockers or issues here:

- [ ] Need access to physical FCX switches for testing
- [ ] Waiting for MIB files from vendor
- [ ] Need clarification on database schema design
- [ ] Awaiting LibreNMS maintainer feedback

## Questions for Research

### FCX Series
- [ ] Do all FCX models support the same stack MIBs?
- [ ] Are there differences between IronWare versions for stack OIDs?
- [ ] FCX maximum stack bandwidth and topology options?

### ICX Series
- [ ] Do all ICX series use the same stacking MIBs?
- [ ] What is the maximum supported stack size per series?
- [ ] ICX 6450 vs 7150 vs 7450 stacking differences?
- [ ] How does ICX7750 stacking differ (modular vs stackable)?
- [ ] Are there FastIron version differences in SNMP OIDs?
- [ ] Do ICX switches support mixing series in a stack?

### Common Questions
- [ ] How does stack numbering work after member removal?
- [ ] How to handle firmware version mismatches in stack?
- [ ] What SNMP traps are sent for stack events (FCX vs ICX)?
- [ ] Differences in stack behavior between IronWare and FastIron?
- [ ] How to detect Brocade vs Ruckus branding reliably?

## Completed Tasks

### Project Setup
- [x] Create project repository structure
- [x] Write comprehensive PROJECT_PLAN.md
- [x] Create SNMP_REFERENCE.md framework
- [x] Create IMPLEMENTATION.md guide
- [x] Write CONTRIBUTING.md guidelines
- [x] Set up directory structure
- [x] Create README files for each directory
- [x] Initialize CHANGELOG.md
- [x] Create .gitignore file
- [x] Write initial documentation

### Multi-Platform Expansion
- [x] Expand project scope to include ICX series
- [x] Update PROJECT_PLAN.md with ICX support
- [x] Update SNMP_REFERENCE.md with ICX OIDs
- [x] Create PLATFORM_DIFFERENCES.md documentation
- [x] Update README.md with ICX platform information
- [x] Document all ICX series (6450, 7150, 7250, 7450, 7650, 7750)
- [x] Document platform detection strategy
- [x] Update TODO.md with ICX-specific tasks

## Notes

- Update this file as tasks are completed
- Add new tasks as they are identified
- Use GitHub issues for detailed task tracking
- Link commits to specific tasks when possible
