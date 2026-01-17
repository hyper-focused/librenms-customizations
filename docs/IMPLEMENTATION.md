# Implementation Guide

This document provides detailed implementation guidance for LibreNMS Foundry FCX stack discovery and monitoring.

## Overview

The implementation follows LibreNMS architecture and coding standards, integrating seamlessly with existing discovery and polling mechanisms.

## LibreNMS Architecture

### Key Components

1. **OS Definitions** (`includes/definitions/*.yaml`)
   - Declarative OS configuration
   - Basic device properties
   - Discovery module settings

2. **OS Detection** (`includes/discovery/os/*.inc.php`)
   - Pattern matching for sysObjectID and sysDescr
   - Version extraction logic
   - Hardware/features detection

3. **Discovery Modules** (`includes/discovery/*.inc.php`)
   - Run periodically (default: every 6 hours)
   - Discover new devices, ports, sensors, etc.
   - Update database with discovered information

4. **Polling Modules** (`includes/polling/*.inc.php`)
   - Run frequently (default: every 5 minutes)
   - Collect metrics and status
   - Update performance data

5. **Database Schema** (`sql-schema/`)
   - Table definitions for storing data
   - Migrations for schema changes

## Implementation Steps

### Step 1: OS Definition (foundry.yaml)

**Location**: `includes/definitions/foundry.yaml`

```yaml
os: foundry
text: 'Foundry Networks'
type: network
icon: foundry
over:
    - { graph: device_bits, text: 'Device Traffic' }
    - { graph: device_processor, text: 'CPU Usage' }
    - { graph: device_mempool, text: 'Memory Usage' }
discovery:
    - sysObjectID:
        - .1.3.6.1.4.1.1991.1.3.
    - sysDescr:
        - Foundry
        - FastIron
        - FCX
mib_dir:
    - foundry
poller_modules:
    bgp: false
    ospf: false
    stp: true
discovery_modules:
    entity-physical: true
    stp: true
    vlans: true
```

### Step 2: OS Detection Logic (foundry.inc.php)

**Location**: `includes/discovery/os/foundry.inc.php`

```php
<?php

/**
 * Foundry OS Detection
 * 
 * Detects Foundry Networks devices (FCX, ICX series)
 * Extracts version, hardware, and stack information
 */

// Check sysObjectID for Foundry enterprise (1991)
if (strpos($device['sysObjectID'], '.1.3.6.1.4.1.1991.') !== false) {
    $os = 'foundry';
    
    // Parse sysDescr for detailed information
    // Example: "Foundry Networks, Inc. FCX648, IronWare Version 08.0.30..."
    if (preg_match('/(?:Foundry|FastIron|FCX|ICX).*?(?:IronWare|Version)\s+([\d.]+)/i', 
                   $device['sysDescr'], $matches)) {
        $version = $matches[1];
    }
    
    // Extract hardware model
    if (preg_match('/(?:Foundry Networks,?\s+Inc\.\s+)?([A-Z]+\d+[A-Z]?)/i', 
                   $device['sysDescr'], $matches)) {
        $hardware = $matches[1];
    }
    
    // Detect if this is a stacked configuration
    $stack_info = snmp_walk($device, 'snStackingOperUnitTable', '-OQUs', 
                           'FOUNDRY-SN-SWITCH-GROUP-MIB');
    
    if (!empty($stack_info)) {
        $unit_count = count($stack_info);
        if ($unit_count > 1) {
            $features = 'Stack (' . $unit_count . ' units)';
        } else {
            $features = 'Standalone';
        }
    }
}
```

### Step 3: Stack Discovery Module

**Location**: `includes/discovery/foundry-stack.inc.php`

```php
<?php

/**
 * Foundry Stack Discovery Module
 * 
 * Discovers stack configuration and members
 */

if ($device['os'] == 'foundry') {
    echo 'Foundry Stack: ';
    
    // Get global stack topology
    $stack_topology = snmp_get($device, 'snStackingGlobalTopology.0', '-Oqv', 
                               'FOUNDRY-SN-SWITCH-GROUP-MIB');
    $stack_mac = snmp_get($device, 'snStackingGlobalMacAddress.0', '-Oqv', 
                          'FOUNDRY-SN-SWITCH-GROUP-MIB');
    
    // Topology: 1=ring, 2=chain, 3=standalone
    $topology_map = [
        1 => 'ring',
        2 => 'chain',
        3 => 'standalone'
    ];
    
    // Walk stack operational table
    $stack_units = snmpwalk_cache_oid($device, 'snStackingOperUnitTable', [], 
                                      'FOUNDRY-SN-SWITCH-GROUP-MIB');
    
    // Get serial numbers and descriptions for each unit
    $chassis_units = snmpwalk_cache_oid($device, 'snChasUnitTable', [], 
                                       'FOUNDRY-SN-AGENT-MIB');
    
    if (!empty($stack_units)) {
        // Check if stack record exists
        $stack_id = dbFetchCell('SELECT stack_id FROM foundry_stacks 
                                WHERE device_id = ?', [$device['device_id']]);
        
        if (empty($stack_id)) {
            // Create new stack record
            $stack_id = dbInsert([
                'device_id' => $device['device_id'],
                'stack_count' => count($stack_units),
                'stack_topology' => $topology_map[$stack_topology] ?? 'unknown',
                'stack_mac' => $stack_mac,
                'last_discovered' => ['NOW()']
            ], 'foundry_stacks');
            echo 'Created stack record. ';
        } else {
            // Update existing stack record
            dbUpdate([
                'stack_count' => count($stack_units),
                'stack_topology' => $topology_map[$stack_topology] ?? 'unknown',
                'stack_mac' => $stack_mac,
                'last_discovered' => ['NOW()']
            ], 'foundry_stacks', 'stack_id = ?', [$stack_id]);
            echo 'Updated stack record. ';
        }
        
        // Process each stack member
        $discovered_members = [];
        foreach ($stack_units as $unit_index => $unit_data) {
            $unit_id = $unit_data['snStackingOperUnitIndex'];
            
            // Role: 1=standalone, 2=member, 3=master
            $role_map = [1 => 'standalone', 2 => 'member', 3 => 'master'];
            $role = $role_map[$unit_data['snStackingOperUnitRole']] ?? 'unknown';
            
            // State: 1=local, 2=remote, 3=reserved, 4=empty, 5=unknown
            $state_map = [
                1 => 'local', 
                2 => 'remote', 
                3 => 'reserved', 
                4 => 'empty', 
                5 => 'unknown'
            ];
            $state = $state_map[$unit_data['snStackingOperUnitState']] ?? 'unknown';
            
            // Get serial number from chassis table
            $serial = $chassis_units[$unit_id]['snChasUnitSerNum'] ?? '';
            $description = $chassis_units[$unit_id]['snChasUnitDescription'] ?? '';
            
            // Check if member exists
            $member_id = dbFetchCell('SELECT member_id FROM foundry_stack_members 
                                     WHERE stack_id = ? AND unit_id = ?', 
                                     [$stack_id, $unit_id]);
            
            if (empty($member_id)) {
                // Create new member record
                $member_id = dbInsert([
                    'stack_id' => $stack_id,
                    'unit_id' => $unit_id,
                    'unit_role' => $role,
                    'unit_state' => $state,
                    'unit_mac' => $unit_data['snStackingOperUnitMac'] ?? '',
                    'unit_priority' => $unit_data['snStackingOperUnitPriority'] ?? 0,
                    'serial_number' => $serial,
                    'model' => $description,
                    'sw_version' => $unit_data['snStackingOperUnitImgVer'] ?? '',
                    'last_seen' => ['NOW()']
                ], 'foundry_stack_members');
                echo "Unit $unit_id: created. ";
            } else {
                // Update existing member record
                dbUpdate([
                    'unit_role' => $role,
                    'unit_state' => $state,
                    'unit_mac' => $unit_data['snStackingOperUnitMac'] ?? '',
                    'unit_priority' => $unit_data['snStackingOperUnitPriority'] ?? 0,
                    'serial_number' => $serial,
                    'model' => $description,
                    'sw_version' => $unit_data['snStackingOperUnitImgVer'] ?? '',
                    'last_seen' => ['NOW()']
                ], 'foundry_stack_members', 'member_id = ?', [$member_id]);
                echo "Unit $unit_id: updated. ";
            }
            
            $discovered_members[] = $member_id;
            
            // Update master in stack table
            if ($role == 'master') {
                dbUpdate(['stack_master_unit' => $unit_id], 
                        'foundry_stacks', 'stack_id = ?', [$stack_id]);
            }
        }
        
        // Remove members that weren't discovered (indicates removed from stack)
        if (!empty($discovered_members)) {
            dbDelete('foundry_stack_members', 
                    'stack_id = ? AND member_id NOT IN ' . 
                    dbGenPlaceholders(count($discovered_members)), 
                    array_merge([$stack_id], $discovered_members));
        }
        
        echo "\n";
    } else {
        echo "No stack information available.\n";
    }
}
```

### Step 4: Database Schema

**Location**: `sql-schema/migrations/2024_01_01_000001_add_foundry_stack_tables.sql`

```sql
-- Foundry Stack Tables
-- For tracking Foundry FCX stacked switch configurations

-- Main stack information table
CREATE TABLE IF NOT EXISTS `foundry_stacks` (
    `stack_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `device_id` INT UNSIGNED NOT NULL,
    `stack_count` TINYINT UNSIGNED DEFAULT 0,
    `stack_topology` ENUM('ring', 'chain', 'standalone', 'unknown') DEFAULT 'unknown',
    `stack_mac` VARCHAR(17) DEFAULT NULL,
    `stack_master_unit` TINYINT UNSIGNED DEFAULT NULL,
    `last_discovered` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`stack_id`),
    UNIQUE KEY `device_id` (`device_id`),
    CONSTRAINT `foundry_stacks_device_id_fk` 
        FOREIGN KEY (`device_id`) 
        REFERENCES `devices` (`device_id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stack member details table
CREATE TABLE IF NOT EXISTS `foundry_stack_members` (
    `member_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `stack_id` INT UNSIGNED NOT NULL,
    `unit_id` TINYINT UNSIGNED NOT NULL,
    `unit_role` ENUM('master', 'member', 'standalone', 'unknown') DEFAULT 'unknown',
    `unit_state` VARCHAR(32) DEFAULT NULL,
    `unit_mac` VARCHAR(17) DEFAULT NULL,
    `unit_priority` TINYINT UNSIGNED DEFAULT 0,
    `serial_number` VARCHAR(64) DEFAULT NULL,
    `model` VARCHAR(64) DEFAULT NULL,
    `sw_version` VARCHAR(64) DEFAULT NULL,
    `last_seen` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`member_id`),
    UNIQUE KEY `stack_unit` (`stack_id`, `unit_id`),
    KEY `stack_id` (`stack_id`),
    CONSTRAINT `foundry_stack_members_stack_id_fk` 
        FOREIGN KEY (`stack_id`) 
        REFERENCES `foundry_stacks` (`stack_id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stack port status table
CREATE TABLE IF NOT EXISTS `foundry_stack_ports` (
    `port_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `member_id` INT UNSIGNED NOT NULL,
    `port_index` TINYINT UNSIGNED NOT NULL,
    `port_oper_status` ENUM('up', 'down', 'testing', 'unknown') DEFAULT 'unknown',
    `neighbor_unit` TINYINT UNSIGNED DEFAULT NULL,
    `last_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`port_id`),
    UNIQUE KEY `member_port` (`member_id`, `port_index`),
    KEY `member_id` (`member_id`),
    CONSTRAINT `foundry_stack_ports_member_id_fk` 
        FOREIGN KEY (`member_id`) 
        REFERENCES `foundry_stack_members` (`member_id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Step 5: Web Interface Integration

**Location**: TBD - Depends on LibreNMS UI architecture

The web interface should display:
- Stack topology visualization
- Member status and roles
- Hardware inventory per unit
- Stack health alerts
- Historical stack changes

## Testing Strategy

### Unit Tests

**Location**: `tests/Feature/SnmpTraps/FoundryStackTest.php`

```php
<?php

namespace LibreNMS\Tests\Feature\SnmpTraps;

use LibreNMS\Tests\TestCase;

class FoundryStackTest extends TestCase
{
    public function testOsDetection()
    {
        // Test OS detection with various sysDescr patterns
        $testCases = [
            'Foundry Networks, Inc. FCX648, IronWare Version 08.0.30...',
            'FastIron FCX624 Router',
        ];
        
        foreach ($testCases as $sysDescr) {
            // Assertion logic here
        }
    }
    
    public function testStackDetection()
    {
        // Test stack member discovery
        // Mock SNMP responses
        // Verify database updates
    }
}
```

### Integration Tests

1. **Test with snmpsim**:
   - Load captured SNMP walks
   - Run discovery
   - Verify results

2. **Test with real devices**:
   - Standalone FCX
   - 2-unit stack
   - Large stack
   - Degraded stack

## Code Style and Standards

Follow LibreNMS coding standards:
- PSR-2 coding style for PHP
- Use LibreNMS helper functions
- Proper error handling
- Logging with appropriate verbosity
- Database operations using LibreNMS DB layer

## Error Handling

```php
// Use LibreNMS logging
d_echo('Debug message');
c_echo('Critical message');

// Handle SNMP failures gracefully
$data = snmp_get($device, $oid, '-Oqv', $mib);
if ($data === false) {
    d_echo("Failed to fetch $oid\n");
    return false;
}

// Database error handling
try {
    dbInsert($data, $table);
} catch (\Exception $e) {
    c_echo("Database error: " . $e->getMessage());
}
```

## Performance Considerations

1. **Use SNMP Bulk Operations**:
   - `snmpwalk_cache_oid()` instead of multiple `snmp_get()`
   - Reduce round trips

2. **Cache Results**:
   - Store discovered data in database
   - Poll only changed information

3. **Limit Discovery Frequency**:
   - Stack configuration changes infrequently
   - Use appropriate discovery intervals

4. **Index Database Tables**:
   - Add indexes on foreign keys
   - Index frequently queried columns

## Integration Checklist

- [ ] OS definition file created
- [ ] OS detection logic implemented
- [ ] Stack discovery module created
- [ ] Database schema defined and tested
- [ ] Migration scripts created
- [ ] MIB files included
- [ ] Unit tests written
- [ ] Integration tests passed
- [ ] Documentation completed
- [ ] Code review conducted
- [ ] PSR-2 compliance verified
- [ ] LibreNMS coding standards met
- [ ] Performance tested
- [ ] Pull request submitted

## Future Enhancements

1. **Enhanced Monitoring**:
   - Stack port utilization graphs
   - Stack member health dashboard
   - Predictive failure detection

2. **Alerting**:
   - Stack member down alerts
   - Master failover notifications
   - Stack link degradation warnings
   - Version mismatch alerts

3. **Automation**:
   - Automatic stack provisioning
   - Configuration backup per unit
   - Firmware consistency checks

4. **Extended Support**:
   - ICX series switches
   - Other Foundry/Ruckus platforms
   - Advanced stack features

## References

- [LibreNMS Documentation](https://docs.librenms.org/)
- [LibreNMS GitHub](https://github.com/librenms/librenms)
- [PSR-2 Coding Standard](https://www.php-fig.org/psr/psr-2/)
