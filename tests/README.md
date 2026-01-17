# Tests

This directory contains test cases and test data for Foundry FCX discovery and monitoring.

## Directory Structure

```
tests/
├── Feature/                    # Feature/integration tests
│   ├── FoundryOsTest.php      # OS detection tests
│   ├── FoundryStackTest.php   # Stack discovery tests
│   └── FoundryPollingTest.php # Polling module tests
├── Unit/                       # Unit tests
│   └── FoundryHelperTest.php  # Helper function tests
└── data/                       # Test SNMP data
    ├── fcx624_standalone.json
    ├── fcx648_stack_2unit.json
    ├── fcx648_stack_8unit.json
    └── fcx_stack_degraded.json
```

## Running Tests

### All Tests
```bash
./vendor/bin/phpunit tests/
```

### Specific Test Suite
```bash
./vendor/bin/phpunit tests/Feature/FoundryStackTest.php
```

### With Coverage
```bash
./vendor/bin/phpunit --coverage-html coverage/ tests/
```

## Test Data Format

Test data files contain captured SNMP responses in JSON format:

```json
{
  "device": {
    "hostname": "test-fcx-stack",
    "os": "foundry",
    "sysDescr": "Foundry Networks, Inc. FCX648, IronWare Version 08.0.30...",
    "sysObjectID": ".1.3.6.1.4.1.1991.1.3.52.1"
  },
  "snmp": {
    ".1.3.6.1.2.1.1.1.0": "Foundry Networks, Inc. FCX648...",
    ".1.3.6.1.4.1.1991.1.1.3.31.3.1.2.1": "3",
    ".1.3.6.1.4.1.1991.1.1.3.31.3.1.2.2": "2"
  }
}
```

## Creating Test Data

### From Real Device

Capture SNMP walk:
```bash
# Full walk
snmpwalk -v2c -c public -ObentU device.example.com > device_walk.txt

# Convert to test format (script to be created)
./tools/convert_snmpwalk_to_json.php device_walk.txt > tests/data/new_test.json
```

### From SNMP Simulator

```bash
# Run discovery against simulator with debugging
./discovery.php -h 127.0.0.1:1024 -d -m os > discovery_output.txt

# Capture SNMP queries made
tcpdump -i lo -s 0 -w snmp_capture.pcap port 1024
```

## Test Scenarios

### Test Scenario 1: Standalone FCX624
**File**: `fcx624_standalone.json`
- Single switch, no stacking
- Basic OS detection
- Hardware inventory
- Firmware version parsing

### Test Scenario 2: Two-Unit Stack
**File**: `fcx648_stack_2unit.json`
- Ring topology
- One master, one member
- Stack port detection
- Role identification

### Test Scenario 3: Eight-Unit Stack
**File**: `fcx648_stack_8unit.json`
- Maximum typical stack size
- Full ring topology
- Multiple members
- Hardware diversity (optional)

### Test Scenario 4: Degraded Stack
**File**: `fcx_stack_degraded.json`
- Stack with failed/removed unit
- Chain topology (broken ring)
- Empty unit slots
- Error condition handling

### Test Scenario 5: Mixed Models
**File**: `fcx_mixed_stack.json`
- FCX624 and FCX648 in same stack
- Different hardware capabilities
- Version consistency checks

## Writing Tests

### Example OS Detection Test

```php
<?php

namespace LibreNMS\Tests\Feature;

use LibreNMS\Tests\TestCase;

class FoundryOsTest extends TestCase
{
    public function testFcx624Detection()
    {
        $device = $this->getTestDevice('fcx624_standalone.json');
        
        // Run OS detection
        $result = detect_os($device);
        
        // Assertions
        $this->assertEquals('foundry', $result['os']);
        $this->assertEquals('FCX624', $result['hardware']);
        $this->assertStringContainsString('08.0', $result['version']);
    }
    
    public function testStackDetection()
    {
        $device = $this->getTestDevice('fcx648_stack_2unit.json');
        
        // Run stack discovery
        $stack = discover_foundry_stack($device);
        
        // Assertions
        $this->assertEquals(2, $stack['unit_count']);
        $this->assertEquals('ring', $stack['topology']);
        $this->assertCount(2, $stack['members']);
        $this->assertTrue($this->hasRole($stack['members'], 'master'));
        $this->assertTrue($this->hasRole($stack['members'], 'member'));
    }
    
    private function hasRole($members, $role)
    {
        foreach ($members as $member) {
            if ($member['role'] === $role) {
                return true;
            }
        }
        return false;
    }
}
```

### Example Polling Test

```php
<?php

namespace LibreNMS\Tests\Feature;

use LibreNMS\Tests\TestCase;

class FoundryPollingTest extends TestCase
{
    public function testStackHealthPolling()
    {
        $device = $this->getTestDevice('fcx648_stack_2unit.json');
        
        // Initial discovery
        discover_foundry_stack($device);
        
        // Simulate unit failure
        $this->setSnmpData($device, [
            'snStackingOperUnitState.1' => 1, // local/active
            'snStackingOperUnitState.2' => 4  // empty/failed
        ]);
        
        // Run polling
        $result = poll_foundry_stack($device);
        
        // Should detect failed unit
        $this->assertTrue($result['stack_degraded']);
        $this->assertCount(1, $result['failed_units']);
        $this->assertEquals(2, $result['failed_units'][0]['unit_id']);
    }
}
```

## Continuous Integration

Tests should be run on:
- Pull requests
- Before merging to main branch
- Before creating releases

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: ./vendor/bin/phpunit tests/
```

## Test Coverage Goals

Aim for:
- **OS Detection**: 100% coverage of detection logic
- **Stack Discovery**: 90%+ coverage including edge cases
- **Polling**: 85%+ coverage of polling modules
- **Overall**: 80%+ total code coverage

## Regression Testing

When bugs are found:
1. Create test case that reproduces the bug
2. Verify test fails with current code
3. Fix the bug
4. Verify test passes
5. Keep test to prevent regression

## Performance Testing

Test discovery/polling performance:

```php
public function testStackDiscoveryPerformance()
{
    $device = $this->getTestDevice('fcx648_stack_8unit.json');
    
    $startTime = microtime(true);
    discover_foundry_stack($device);
    $duration = microtime(true) - $startTime;
    
    // Discovery should complete in reasonable time
    $this->assertLessThan(5.0, $duration, 
        'Stack discovery took too long: ' . $duration . 's');
}
```

## Contributing Tests

When contributing:
1. Add tests for all new features
2. Update existing tests if behavior changes
3. Ensure all tests pass before submitting PR
4. Document test scenarios in comments
5. Use descriptive test method names

## References

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [LibreNMS Testing Guide](https://docs.librenms.org/Developing/Testing/)
- [Test-Driven Development](https://en.wikipedia.org/wiki/Test-driven_development)
