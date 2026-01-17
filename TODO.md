# TODO List - Foundry FCX Stack Discovery Project

This file tracks outstanding tasks for the project. Mark items as completed with `[x]`.

## Phase 1: Research & Analysis

- [ ] Obtain Foundry FCX MIB files
  - [ ] FOUNDRY-SN-ROOT-MIB
  - [ ] FOUNDRY-SN-AGENT-MIB
  - [ ] FOUNDRY-SN-SWITCH-GROUP-MIB
  - [ ] FOUNDRY-SN-STACKING-MIB
- [ ] Analyze MIB files and document all relevant OIDs
- [ ] Collect SNMP walks from real devices:
  - [ ] Standalone FCX624
  - [ ] Standalone FCX648
  - [ ] 2-unit stack
  - [ ] Large stack (4+ units)
  - [ ] Degraded stack
- [ ] Review existing LibreNMS OS implementations for similar devices
- [ ] Document current behavior vs. desired behavior
- [ ] Identify all firmware versions to support

## Phase 2: OS Discovery Enhancement

- [ ] Create `includes/definitions/foundry.yaml`
- [ ] Implement `includes/discovery/os/foundry.inc.php`
  - [ ] sysObjectID detection
  - [ ] sysDescr parsing
  - [ ] Version extraction
  - [ ] Hardware model detection
  - [ ] Basic stack detection
- [ ] Test OS detection with various switch models
- [ ] Validate version string parsing for different firmware versions
- [ ] Create test cases for OS detection

## Phase 3: Stack Discovery Implementation

- [ ] Design database schema for stack tables
- [ ] Create migration script for database changes
- [ ] Implement stack discovery module
  - [ ] Stack topology detection (ring/chain/standalone)
  - [ ] Stack member enumeration
  - [ ] Master/member role detection
  - [ ] Unit state monitoring
  - [ ] MAC address collection
- [ ] Implement stack port discovery
- [ ] Add stack member hardware detection
- [ ] Test with various stack configurations
- [ ] Handle edge cases (failed members, empty slots)

## Phase 4: Hardware Discovery

- [ ] Implement serial number collection per unit
- [ ] Add model detection for each stack member
- [ ] Support heterogeneous stacks (mixed models)
- [ ] Collect firmware version per unit
- [ ] Add module/line card discovery (if applicable)
- [ ] Integrate with entity-physical discovery
- [ ] Test with mixed hardware configurations

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
- [ ] Test with ICX series (newer Ruckus branding)
- [ ] Support other Foundry switch models
- [ ] Add support for virtual chassis (if different)
- [ ] Test with different IronWare versions

## Issues & Blockers

Document any blockers or issues here:

- [ ] Need access to physical FCX switches for testing
- [ ] Waiting for MIB files from vendor
- [ ] Need clarification on database schema design
- [ ] Awaiting LibreNMS maintainer feedback

## Questions for Research

- [ ] Do all FCX models support the same stack MIBs?
- [ ] What is the maximum supported stack size?
- [ ] How does stack numbering work after member removal?
- [ ] Are there differences between IronWare versions for stack OIDs?
- [ ] How to handle firmware version mismatches in stack?
- [ ] What traps are sent for stack events?

## Completed Tasks

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

## Notes

- Update this file as tasks are completed
- Add new tasks as they are identified
- Use GitHub issues for detailed task tracking
- Link commits to specific tasks when possible
