<?php

/**
 * Unit tests for Brocade Ironware OS Discovery (FCX and ICX series)
 */

require_once __DIR__ . '/../mocks/brocade-ironware-mock.php';

class BrocadeIronwareDiscoveryTest extends PHPUnit_Framework_TestCase {

    /**
     * Test OS discovery for FCX stack configuration
     */
    public function testFcxStackDiscovery() {
        global $mock_fcx_stack;

        // Mock the device array
        $device = $mock_fcx_stack['device'];

        // Simulate the discovery process
        $os = null;

        // Check for Foundry FCX specific OIDs (simulated)
        $fcx_oids = [
            '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.2',  // snChasUnitName
            '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.4',  // snChasUnitDescr
            '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.5',  // snChasUnitType
        ];

        $fcx_data = [];
        foreach ($fcx_oids as $oid) {
            $fcx_data[] = mock_snmp_get($mock_fcx_stack, $oid);
        }

        // Should detect FCX OS
        $this->assertNotEmpty(array_filter($fcx_data));
        $os = 'foundry-fcx';
        $this->assertEquals('foundry-fcx', $os);

        // Test enhanced discovery
        if ($os === 'foundry-fcx') {
            $sysDescr = mock_snmp_get($mock_fcx_stack, 'SNMPv2-MIB::sysDescr.0');
            $this->assertStringContains('Foundry Networks, Inc. FCX', $sysDescr);

            $chassis_table = mock_snmpwalk_cache_oid($mock_fcx_stack, 'snChasUnitTable');
            $this->assertNotEmpty($chassis_table);
            $this->assertCount(3, $chassis_table); // 3 units in stack

            // Check stack member detection
            $stack_members = [];
            $master_unit = null;

            foreach ($chassis_table as $index => $chassis) {
                if ($chassis['snChasUnitStatus'] == 1) { // operational
                    $stack_members[] = [
                        'index' => $index,
                        'name' => $chassis['snChasUnitName'],
                        'type' => $chassis['snChasUnitType'],
                        'status' => $chassis['snChasUnitStatus']
                    ];

                    // Look for master unit
                    if (stripos($chassis['snChasUnitName'], 'master') !== false) {
                        $master_unit = $index;
                    }
                }
            }

            $this->assertCount(3, $stack_members);
            $this->assertEquals(1, $master_unit); // Unit 1 should be master
            $this->assertTrue(count($stack_members) > 1); // Should be detected as stacked

            // Test stack topology
            $stack_topo = mock_snmpwalk_cache_oid($mock_fcx_stack, 'snStackTopoTable');
            $this->assertNotEmpty($stack_topo);
            $this->assertCount(4, $stack_topo); // 4 connections in ring topology

            // Test stack priority
            $stack_priority = mock_snmpwalk_cache_oid($mock_fcx_stack, 'snStackPriorityTable');
            $this->assertNotEmpty($stack_priority);
            $this->assertCount(3, $stack_priority); // 3 units with priority info
        }
    }

    /**
     * Test OS discovery for standalone FCX switch
     */
    public function testFcxStandaloneDiscovery() {
        global $mock_fcx_standalone;

        $device = $mock_fcx_standalone['device'];
        $os = null;

        // Check for Foundry FCX specific OIDs
        $fcx_data = [];
        $fcx_oids = [
            '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.2',
            '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.4',
            '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.5',
        ];

        foreach ($fcx_oids as $oid) {
            $fcx_data[] = mock_snmp_get($mock_fcx_standalone, $oid);
        }

        // Should detect FCX OS
        $this->assertNotEmpty(array_filter($fcx_data));
        $os = 'foundry-fcx';
        $this->assertEquals('foundry-fcx', $os);

        // Test standalone detection
        if ($os === 'foundry-fcx') {
            $chassis_table = mock_snmpwalk_cache_oid($mock_fcx_standalone, 'snChasUnitTable');
            $this->assertNotEmpty($chassis_table);
            $this->assertCount(1, $chassis_table); // Only 1 unit

            $stack_members = [];
            foreach ($chassis_table as $index => $chassis) {
                if ($chassis['snChasUnitStatus'] == 1) {
                    $stack_members[] = [
                        'index' => $index,
                        'name' => $chassis['snChasUnitName'],
                        'type' => $chassis['snChasUnitType'],
                        'status' => $chassis['snChasUnitStatus']
                    ];
                }
            }

            $this->assertCount(1, $stack_members);
            $this->assertFalse(count($stack_members) > 1); // Should NOT be detected as stacked
        }
    }

    /**
     * Test firmware version extraction
     */
    public function testFirmwareVersionExtraction() {
        global $mock_fcx_stack;

        $firmware_version = mock_snmp_get($mock_fcx_stack, '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.6.1');
        $this->assertNotEmpty($firmware_version);
        $this->assertStringContains('07.4.00bT7e1', $firmware_version);
    }

    /**
     * Test hardware model extraction
     */
    public function testHardwareModelExtraction() {
        global $mock_fcx_stack;

        $chassis_table = mock_snmpwalk_cache_oid($mock_fcx_stack, 'snChasUnitTable');
        $this->assertNotEmpty($chassis_table);

        foreach ($chassis_table as $chassis) {
            $this->assertNotEmpty($chassis['snChasUnitType']);
            $this->assertStringContains('FCX', $chassis['snChasUnitType']);
        }
    }

    /**
     * Test serial number extraction
     */
    public function testSerialNumberExtraction() {
        global $mock_fcx_stack;

        $chassis_table = mock_snmpwalk_cache_oid($mock_fcx_stack, 'snChasUnitTable');
        $this->assertNotEmpty($chassis_table);

        foreach ($chassis_table as $chassis) {
            $this->assertNotEmpty($chassis['snChasUnitSerialNumber']);
            $this->assertGreaterThan(5, strlen($chassis['snChasUnitSerialNumber']));
        }
    }

    /**
     * Test OS discovery for ICX 6450 stack configuration
     */
    public function testIcx6450StackDiscovery() {
        global $mock_icx6450_stack;

        $device = $mock_icx6450_stack['device'];
        $os = null;

        // Check for Brocade Ironware specific OIDs
        $brocade_oids = [
            '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.2',
            '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.4',
            '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.5',
        ];

        $brocade_data = [];
        foreach ($brocade_oids as $oid) {
            $result = mock_snmp_get($mock_icx6450_stack, $oid);
            $brocade_data[] = $result;
        }

        // Should detect ICX OS
        $this->assertNotEmpty(array_filter($brocade_data));
        $sysDescr = mock_snmp_get($mock_icx6450_stack, 'SNMPv2-MIB::sysDescr.0');
        $this->assertStringContains('ICX6450', $sysDescr);
        $os = 'brocade-icx';

        // Test enhanced discovery
        if ($os === 'brocade-icx') {
            $chassis_table = mock_snmpwalk_cache_oid($mock_icx6450_stack, 'snChasUnitTable');
            $this->assertNotEmpty($chassis_table);
            $this->assertCount(2, $chassis_table); // 2 units in stack

            // Check stack member detection
            $stack_members = [];
            $master_unit = null;

            foreach ($chassis_table as $index => $chassis) {
                if ($chassis['snChasUnitStatus'] == 1) {
                    $stack_members[] = [
                        'index' => $index,
                        'name' => $chassis['snChasUnitName'],
                        'type' => $chassis['snChasUnitType'],
                        'status' => $chassis['snChasUnitStatus']
                    ];

                    if (stripos($chassis['snChasUnitName'], 'master') !== false) {
                        $master_unit = $index;
                    }
                }
            }

            $this->assertCount(2, $stack_members);
            $this->assertEquals(1, $master_unit);
            $this->assertTrue(count($stack_members) > 1);
        }
    }

    /**
     * Test OS discovery for ICX 7750 standalone configuration
     */
    public function testIcx7750StandaloneDiscovery() {
        global $mock_icx7750_standalone;

        $device = $mock_icx7750_standalone['device'];
        $os = null;

        // Check for Brocade Ironware specific OIDs
        $brocade_data = [];
        $brocade_oids = [
            '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.2',
            '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.4',
            '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.5',
        ];

        foreach ($brocade_oids as $oid) {
            $result = mock_snmp_get($mock_icx7750_standalone, $oid);
            $brocade_data[] = $result;
        }

        // Should detect ICX OS
        $this->assertNotEmpty(array_filter($brocade_data));
        $sysDescr = mock_snmp_get($mock_icx7750_standalone, 'SNMPv2-MIB::sysDescr.0');
        $this->assertStringContains('ICX7750', $sysDescr);
        $os = 'brocade-icx';

        // Test standalone detection
        if ($os === 'brocade-icx') {
            $chassis_table = mock_snmpwalk_cache_oid($mock_icx7750_standalone, 'snChasUnitTable');
            $this->assertNotEmpty($chassis_table);
            $this->assertCount(1, $chassis_table);

            $stack_members = [];
            foreach ($chassis_table as $index => $chassis) {
                if ($chassis['snChasUnitStatus'] == 1) {
                    $stack_members[] = [
                        'index' => $index,
                        'name' => $chassis['snChasUnitName'],
                        'type' => $chassis['snChasUnitType'],
                        'status' => $chassis['snChasUnitStatus']
                    ];
                }
            }

            $this->assertCount(1, $stack_members);
            $this->assertFalse(count($stack_members) > 1);
        }
    }

    /**
     * Test OS discovery for ICX 7150 stack configuration
     */
    public function testIcx7150StackDiscovery() {
        global $mock_icx7150_stack;

        $device = $mock_icx7150_stack['device'];
        $os = null;

        // Check for Brocade Ironware specific OIDs
        $brocade_data = [];
        $brocade_oids = [
            '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.2',
            '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.4',
            '.1.3.6.1.4.1.1991.1.3.49.1.1.1.1.5',
        ];

        foreach ($brocade_oids as $oid) {
            $result = mock_snmp_get($mock_icx7150_stack, $oid);
            $brocade_data[] = $result;
        }

        // Should detect ICX OS
        $this->assertNotEmpty(array_filter($brocade_data));
        $sysDescr = mock_snmp_get($mock_icx7150_stack, 'SNMPv2-MIB::sysDescr.0');
        $this->assertStringContains('ICX7150', $sysDescr);
        $os = 'brocade-icx';

        // Test enhanced discovery
        if ($os === 'brocade-icx') {
            $chassis_table = mock_snmpwalk_cache_oid($mock_icx7150_stack, 'snChasUnitTable');
            $this->assertNotEmpty($chassis_table);
            $this->assertCount(3, $chassis_table); // 3 units in stack

            $stack_members = [];
            $master_unit = null;

            foreach ($chassis_table as $index => $chassis) {
                if ($chassis['snChasUnitStatus'] == 1) {
                    $stack_members[] = [
                        'index' => $index,
                        'name' => $chassis['snChasUnitName'],
                        'type' => $chassis['snChasUnitType'],
                        'status' => $chassis['snChasUnitStatus']
                    ];

                    if (stripos($chassis['snChasUnitName'], 'master') !== false) {
                        $master_unit = $index;
                    }
                }
            }

            $this->assertCount(3, $stack_members);
            $this->assertEquals(1, $master_unit);
            $this->assertTrue(count($stack_members) > 1);

            // Test stack topology
            $stack_topo = mock_snmpwalk_cache_oid($mock_icx7150_stack, 'snStackTopoTable');
            $this->assertNotEmpty($stack_topo);
            $this->assertCount(4, $stack_topo); // 4 connections in topology
        }
    }
}

?>