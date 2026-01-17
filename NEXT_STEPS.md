# Next Steps for Implementation

This document outlines the immediate next steps to move from planning to implementation.

## Phase 1: MIB Analysis (CURRENT)

### Critical Information Needed from MIBs

You have provided MIB files for:
- FCX series (FCXR/FCXS)
- FSX series (SXLR/SXLS)
- ICX 6400 series (ICX64R/ICX64S)
- ICX 6650 series (ICXR/ICXS)
- ICX 7250 series (SPR/SPS)
- ICX 7750 series (SWR/SWS)

**Action Required**: Extract key information from these MIBs.

See **EXTRACT_MIB_INFO.md** for detailed extraction guide.

### Priority Information to Extract:

1. **sysObjectID values** for each model (CRITICAL)
   - Needed for OS detection logic
   - Example: FCX624 = .1.3.6.1.4.1.1991.1.3.51.X

2. **Enterprise OID verification**
   - Confirm FCX uses 1991 (Foundry)
   - Confirm ICX uses 1588 (Brocade) or both 1588 and 1991

3. **Stack OID compatibility**
   - Verify all platforms use .1.3.6.1.4.1.1991.1.1.3.31.* for stacking
   - Document any platform-specific differences

4. **sysDescr patterns**
   - Exact text format for each model
   - Variations in branding (Foundry/Brocade/Ruckus)

### Quick Extraction Method:

If you can run these commands on the MIB files:

```bash
cd "/Users/rob/Library/CloudStorage/Dropbox/Switch Firmware/Brocade FCX/FCXR08030u.mib/FCX/MIBS/"

# Extract enterprise and product definitions
grep -A 10 "OBJECT IDENTIFIER ::=" FCXR08030u.mib | head -50 > fcx_oids.txt

# Do the same for each platform
cd "../ICX7250/MIBs/"
grep -A 10 "OBJECT IDENTIFIER ::=" SPR08030u.mib | head -50 > icx7250_oids.txt

# Repeat for all platforms
```

Then share the output files, or copy/paste the relevant sections.

## Phase 2: OS Detection Implementation

Once we have sysObjectID values, implement:

### 2.1 Create OS Definitions

**Files to create**:
- `includes/definitions/foundry.yaml` - FCX series
- `includes/definitions/icx.yaml` - ICX series

**Template**:
```yaml
os: foundry  # or icx
text: 'Foundry Networks'  # or 'Brocade ICX' / 'Ruckus ICX'
type: network
icon: foundry  # or brocade/ruckus
discovery:
    - sysObjectID:
        - .1.3.6.1.4.1.1991.1.3.51  # FCX624
        - .1.3.6.1.4.1.1991.1.3.52  # FCX648
    - sysDescr:
        - Foundry
        - FCX
mib_dir:
    - foundry
```

### 2.2 Create OS Detection Logic

**Files to create**:
- `includes/discovery/os/foundry.inc.php`
- `includes/discovery/os/icx.inc.php`

**Key functions**:
- Detect platform (FCX vs ICX)
- Extract hardware model
- Parse version string (IronWare vs FastIron)
- Identify if stacked

### 2.3 Testing

**Create test data files**:
- `tests/data/fcx624_standalone.json`
- `tests/data/fcx648_stack.json`
- `tests/data/icx6450_standalone.json`
- `tests/data/icx7250_stack.json`
- `tests/data/icx7750_stack.json`

**Test cases**:
- OS detection accuracy
- Version parsing
- Model identification
- Stack detection

## Phase 3: Database Schema

### 3.1 Design Tables

Already designed in PROJECT_PLAN.md:
- `ironware_stacks`
- `ironware_stack_members`
- `ironware_stack_ports`

### 3.2 Create Migration

**File to create**:
- `sql-schema/migrations/XXX_add_ironware_stack_tables.sql`

**Actions**:
- Create tables with proper indexes
- Add foreign key constraints
- Test migration up and down

### 3.3 Integration

- Update LibreNMS schema
- Test with existing LibreNMS data
- Ensure backward compatibility

## Phase 4: Stack Discovery Module

### 4.1 Core Discovery

**File to create**:
- `includes/discovery/ironware-stack.inc.php`

**Functions**:
- Detect stack topology
- Enumerate stack members
- Identify master/member roles
- Collect hardware info per unit

### 4.2 Testing

- Test with various stack sizes
- Test degraded stacks
- Test both FCX and ICX
- Verify database updates

## Phase 5: Stack Polling Module

### 5.1 Polling Implementation

**File to create**:
- `includes/polling/ironware-stack.inc.php`

**Functions**:
- Update stack member status
- Monitor stack port health
- Detect stack changes
- Trigger alerts

### 5.2 Performance

- Optimize SNMP queries
- Implement caching
- Rate limit updates

## Phase 6: Testing & Documentation

### 6.1 Unit Tests

- Create PHPUnit tests
- Mock SNMP responses
- Test all scenarios

### 6.2 Integration Tests

- Test with snmpsim
- Test with real hardware (if available)
- Performance testing

### 6.3 Documentation

- User guide
- Troubleshooting guide
- API documentation
- Examples and screenshots

## Phase 7: Upstream Contribution

### 7.1 Code Quality

- PSR-2 compliance check
- Code review
- Security audit
- Performance optimization

### 7.2 LibreNMS Integration

- Fork LibreNMS repository
- Create feature branch
- Integrate code
- Run LibreNMS tests

### 7.3 Pull Request

- Create comprehensive PR description
- Include test results
- Provide examples
- Respond to feedback

## Immediate Action Items

### This Week:

1. **Extract MIB information** (see EXTRACT_MIB_INFO.md)
   - Priority: sysObjectID values
   - Priority: Enterprise OID verification
   - Priority: Stack OID compatibility

2. **Collect SNMP walks** from real devices if available:
   - FCX624 or FCX648 (standalone)
   - FCX stack (2+ units)
   - ICX6450, 7150, or 7250 (standalone)
   - ICX stack (2+ units)

3. **Document findings** in MIB_ANALYSIS.md

### Next Week:

4. **Implement OS detection**
   - Create YAML definitions
   - Write detection logic
   - Create test cases

5. **Test OS detection**
   - Unit tests
   - Integration tests

### Following Weeks:

6. **Database schema** and migration
7. **Stack discovery** module
8. **Stack polling** module
9. **Testing** and refinement
10. **Documentation** completion
11. **Upstream contribution**

## Questions & Blockers

### Current Blockers:

- **MIB Information**: Need sysObjectID values from provided MIBs
- **Test Devices**: Need access to real switches for SNMP testing

### Questions to Resolve:

1. Do you have access to real switches for testing?
2. Can you provide SNMP walks from your switches?
3. What firmware versions are you running?
4. Do you need this for production use soon, or is this for contribution?

## Resources Available

- **Documentation**: Comprehensive planning complete
- **MIBs**: Device-specific MIBs provided (need extraction)
- **References**: LibreNMS docs, SNMP standards
- **Community**: LibreNMS Discord, GitHub

## Getting Help

If you need assistance:
1. LibreNMS Discord: #development channel
2. GitHub Issues: For specific technical questions
3. SNMP tools: net-snmp, snmpsim for testing

## Success Metrics

- ✅ Planning Complete
- ⏳ MIB Analysis (In Progress)
- ⬜ OS Detection Implementation
- ⬜ Stack Discovery Implementation
- ⬜ Testing Complete
- ⬜ Documentation Complete
- ⬜ Upstream Contribution

---

**Current Status**: Awaiting MIB information extraction to proceed with implementation.

**Next Action**: Extract sysObjectID values and enterprise OID information from provided MIBs.
