# Contributing to LibreNMS Foundry FCX Support

Thank you for your interest in improving LibreNMS support for Foundry FCX switches! This document provides guidelines for contributing to this project.

## Project Goals

This project aims to enhance LibreNMS with:
- Accurate OS detection for Foundry FCX switches
- Complete stack configuration discovery
- Comprehensive hardware inventory
- Stack health monitoring and alerting

All contributions should align with these goals and LibreNMS best practices.

## How to Contribute

### Reporting Issues

If you encounter problems or have suggestions:

1. **Check existing issues** to avoid duplicates
2. **Provide details**:
   - LibreNMS version
   - Foundry switch model and firmware version
   - Stack configuration (standalone, 2-unit, etc.)
   - SNMP version in use
   - Error messages or unexpected behavior
3. **Include SNMP data** when possible (see below)

### Sharing SNMP Data

SNMP walks from real devices are invaluable for testing and development:

```bash
# Capture full SNMP walk (sanitize sensitive info before sharing)
snmpwalk -v2c -c YOUR_COMMUNITY -ObentU switch.example.com > fcx_snmpwalk.txt

# For stacked switches, please indicate:
# - Number of units in stack
# - Stack topology (ring/chain)
# - Master unit ID
# - Any failed/missing units
```

**Before sharing**:
- Remove or redact sensitive information (passwords, locations, etc.)
- Remove internal IP addresses if needed
- Note your switch model and IronWare version

### Code Contributions

#### Getting Started

1. **Fork the repository**
2. **Clone your fork**:
   ```bash
   git clone https://github.com/YOUR_USERNAME/librenms-foundry-fcx.git
   cd librenms-foundry-fcx
   ```
3. **Create a feature branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```

#### Development Guidelines

1. **Follow LibreNMS Coding Standards**:
   - PSR-2 PHP coding style
   - Use LibreNMS helper functions
   - Proper error handling
   - Appropriate logging levels

2. **Write Clean Code**:
   - Meaningful variable and function names
   - Comments for complex logic
   - DRY (Don't Repeat Yourself) principle
   - KISS (Keep It Simple, Stupid) principle

3. **Test Your Changes**:
   - Unit tests for new functions
   - Integration tests with real or simulated devices
   - Verify existing functionality not broken
   - Test edge cases

4. **Document Your Work**:
   - Update relevant documentation
   - Add inline code comments
   - Update SNMP_REFERENCE.md with new OIDs
   - Include examples where helpful

#### Code Review Checklist

Before submitting, ensure:
- [ ] Code follows PSR-2 standards
- [ ] All tests pass
- [ ] No new linter errors introduced
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
- [ ] Commit messages are clear and descriptive
- [ ] No debug code or comments left in
- [ ] Sensitive information removed

#### Submitting Changes

1. **Commit your changes**:
   ```bash
   git add .
   git commit -m "Add feature: brief description"
   ```
   
   **Good commit messages**:
   - "Add stack topology detection for FCX switches"
   - "Fix serial number parsing for stacked units"
   - "Update SNMP reference with stack port OIDs"
   
   **Poor commit messages**:
   - "Fixed stuff"
   - "Update"
   - "WIP"

2. **Push to your fork**:
   ```bash
   git push origin feature/your-feature-name
   ```

3. **Create a Pull Request**:
   - Describe what your changes do
   - Reference any related issues
   - Include testing details
   - Note any breaking changes

### Testing Contributions

Help improve test coverage:

1. **Provide SNMP Simulation Data**:
   - Captures from various configurations
   - Different firmware versions
   - Edge cases (failed units, degraded stacks)

2. **Test on Real Hardware**:
   - Verify discovery works correctly
   - Check polling functionality
   - Validate alerting behavior

3. **Report Test Results**:
   - Document your test environment
   - Include any issues encountered
   - Suggest additional test scenarios

### Documentation Contributions

Documentation improvements are always welcome:

- Fix typos or unclear explanations
- Add examples or use cases
- Improve SNMP reference with additional details
- Create tutorials or how-to guides
- Translate documentation (if applicable)

## Development Workflow

### Typical Development Cycle

1. **Plan**: Review PROJECT_PLAN.md and identify a task
2. **Research**: Study relevant code and documentation
3. **Implement**: Write code following guidelines
4. **Test**: Verify functionality with tests
5. **Document**: Update relevant documentation
6. **Review**: Self-review using checklist
7. **Submit**: Create pull request
8. **Iterate**: Address feedback and refine

### Branch Strategy

- `main`: Stable code ready for upstream contribution
- `develop`: Integration branch for new features
- `feature/*`: Individual feature branches
- `bugfix/*`: Bug fix branches
- `hotfix/*`: Critical fixes

### Testing Locally

#### With LibreNMS Development Environment

```bash
# Set up LibreNMS dev environment (Docker recommended)
git clone https://github.com/librenms/librenms.git
cd librenms
# Follow LibreNMS dev setup instructions

# Copy your code to appropriate locations
cp /path/to/includes/discovery/os/foundry.inc.php includes/discovery/os/
cp /path/to/includes/definitions/foundry.yaml includes/definitions/

# Run discovery on test device
./discovery.php -h device.example.com -d

# Run tests
./vendor/bin/phpunit tests/
```

#### With SNMP Simulator

```bash
# Install snmpsim
pip install snmpsim

# Convert SNMP walk to simulation data
snmprec.py --agent-udpv4-endpoint=127.0.0.1:1024 \
           --data-file=fcx_snmpwalk.txt

# Run simulator
snmpsimd.py --data-dir=./data --agent-udpv4-endpoint=127.0.0.1:1024

# Test against simulator
./discovery.php -h 127.0.0.1:1024 -d
```

## Code Style

### PHP Standards

```php
<?php

namespace LibreNMS\SomeNamespace;

use LibreNMS\SomeClass;

/**
 * Class documentation
 */
class ExampleClass
{
    /**
     * Method documentation
     *
     * @param string $param Description
     * @return bool Description
     */
    public function exampleMethod($param)
    {
        // Use 4 spaces for indentation
        if ($condition) {
            // Do something
            return true;
        }
        
        return false;
    }
}
```

### SQL Standards

```sql
-- Use uppercase for SQL keywords
-- Proper indentation
-- Descriptive table and column names

CREATE TABLE IF NOT EXISTS `foundry_stacks` (
    `stack_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `device_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`stack_id`),
    CONSTRAINT `foundry_stacks_device_id_fk` 
        FOREIGN KEY (`device_id`) 
        REFERENCES `devices` (`device_id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Communication

### Where to Get Help

- **GitHub Issues**: For bugs and feature requests
- **LibreNMS Discord**: For general questions (when available)
- **Documentation**: Check docs/ directory first
- **Code Comments**: Review inline documentation

### Be Respectful

- Be professional and courteous
- Provide constructive feedback
- Respect different perspectives
- Help others learn and grow

## Recognition

Contributors will be:
- Listed in project acknowledgments
- Credited in pull requests and commits
- Recognized in CHANGELOG.md

## License

By contributing, you agree that your contributions will be licensed under the same license as the project (see LICENSE file).

## Getting Help

If you have questions about contributing:

1. Check existing documentation
2. Review similar contributions
3. Open a GitHub issue with "Question:" prefix
4. Reach out on community channels

## Thank You!

Your contributions help improve network monitoring for everyone using Foundry FCX switches with LibreNMS. We appreciate your time and effort!
