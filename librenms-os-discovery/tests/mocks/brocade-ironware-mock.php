<?php

/**
 * Mock SNMP data for Brocade Ironware switch testing (FCX and ICX series)
 *
 * This file provides mock SNMP responses for testing FCX and ICX stack discovery
 */

// Mock device configuration for a 3-unit FCX stack
$mock_fcx_stack = [
    'device' => [
        'hostname' => 'fcx-stack-01',
        'ip' => '192.168.1.10',
        'snmp_version' => 'v2c',
        'snmp_community' => 'public'
    ],

    // SNMPv2-MIB responses
    'SNMPv2-MIB::sysDescr.0' => 'Foundry Networks, Inc. FCX648S, IronWare Version 07.4.00bT7e1 Compiled on Oct 18 2013 at 14:07:26',
    'SNMPv2-MIB::sysObjectID.0' => '.1.3.6.1.4.1.1991.1.3.49',

    // FOUNDRY-SN-ROOT chassis table (3 units in stack)
    'snChasUnitTable' => [
        1 => [
            'snChasUnitIndex' => 1,
            'snChasUnitName' => 'FCX648S-Unit1-Master',
            'snChasUnitDescr' => 'Foundry FCX648S Switch',
            'snChasUnitType' => 'FCX648S',
            'snChasUnitStatus' => 1, // operational
            'snChasUnitSerialNumber' => 'ABC123456789',
            'snChasUnitFirmwareVersion' => '07.4.00bT7e1'
        ],
        2 => [
            'snChasUnitIndex' => 2,
            'snChasUnitName' => 'FCX648S-Unit2-Member',
            'snChasUnitDescr' => 'Foundry FCX648S Switch',
            'snChasUnitType' => 'FCX648S',
            'snChasUnitStatus' => 1, // operational
            'snChasUnitSerialNumber' => 'DEF987654321',
            'snChasUnitFirmwareVersion' => '07.4.00bT7e1'
        ],
        3 => [
            'snChasUnitIndex' => 3,
            'snChasUnitName' => 'FCX648S-Unit3-Member',
            'snChasUnitDescr' => 'Foundry FCX648S Switch',
            'snChasUnitType' => 'FCX648S',
            'snChasUnitStatus' => 1, // operational
            'snChasUnitSerialNumber' => 'GHI456789123',
            'snChasUnitFirmwareVersion' => '07.4.00bT7e1'
        ]
    ],

    // Stack topology connections
    'snStackTopoTable' => [
        '1.1' => [ // Unit 1, Port 1 -> Unit 2, Port 1
            'snStackTopoLocalUnit' => 1,
            'snStackTopoLocalPort' => 1,
            'snStackTopoRemoteUnit' => 2,
            'snStackTopoRemotePort' => 1,
            'snStackTopoLinkStatus' => 1 // up
        ],
        '1.2' => [ // Unit 1, Port 2 -> Unit 3, Port 1
            'snStackTopoLocalUnit' => 1,
            'snStackTopoLocalPort' => 2,
            'snStackTopoRemoteUnit' => 3,
            'snStackTopoRemotePort' => 1,
            'snStackTopoLinkStatus' => 1 // up
        ],
        '2.1' => [ // Unit 2, Port 1 -> Unit 1, Port 1
            'snStackTopoLocalUnit' => 2,
            'snStackTopoLocalPort' => 1,
            'snStackTopoRemoteUnit' => 1,
            'snStackTopoRemotePort' => 1,
            'snStackTopoLinkStatus' => 1 // up
        ],
        '3.1' => [ // Unit 3, Port 1 -> Unit 1, Port 2
            'snStackTopoLocalUnit' => 3,
            'snStackTopoLocalPort' => 1,
            'snStackTopoRemoteUnit' => 1,
            'snStackTopoRemotePort' => 2,
            'snStackTopoLinkStatus' => 1 // up
        ]
    ],

    // Stack priority information
    'snStackPriorityTable' => [
        1 => [
            'snStackPriorityUnit' => 1,
            'snStackPriorityValue' => 255, // Highest priority
            'snStackPriorityRole' => 1 // master
        ],
        2 => [
            'snStackPriorityUnit' => 2,
            'snStackPriorityValue' => 128,
            'snStackPriorityRole' => 2 // member
        ],
        3 => [
            'snStackPriorityUnit' => 3,
            'snStackPriorityValue' => 64,
            'snStackPriorityRole' => 2 // member
        ]
    ]
];

// Mock device configuration for standalone FCX switch
$mock_fcx_standalone = [
    'device' => [
        'hostname' => 'fcx-standalone-01',
        'ip' => '192.168.1.11',
        'snmp_version' => 'v2c',
        'snmp_community' => 'public'
    ],

    'SNMPv2-MIB::sysDescr.0' => 'Foundry Networks, Inc. FCX624S, IronWare Version 07.4.00bT7e1 Compiled on Oct 18 2013 at 14:07:26',
    'SNMPv2-MIB::sysObjectID.0' => '.1.3.6.1.4.1.1991.1.3.49',

    'snChasUnitTable' => [
        1 => [
            'snChasUnitIndex' => 1,
            'snChasUnitName' => 'FCX624S-Standalone',
            'snChasUnitDescr' => 'Foundry FCX624S Switch',
            'snChasUnitType' => 'FCX624S',
            'snChasUnitStatus' => 1, // operational
            'snChasUnitSerialNumber' => 'XYZ987654321',
            'snChasUnitFirmwareVersion' => '07.4.00bT7e1'
        ]
    ]
];

// Mock device configuration for ICX 6450 stack
$mock_icx6450_stack = [
    'device' => [
        'hostname' => 'icx6450-stack-01',
        'ip' => '192.168.1.20',
        'snmp_version' => 'v2c',
        'snmp_community' => 'public'
    ],

    'SNMPv2-MIB::sysDescr.0' => 'Brocade Communications Systems, Inc. ICX6450-48, IronWare Version 08.0.30T311 Compiled on Sep 14 2018 at 10:24:18',
    'SNMPv2-MIB::sysObjectID.0' => '.1.3.6.1.4.1.1991.1.3.49',

    'snChasUnitTable' => [
        1 => [
            'snChasUnitIndex' => 1,
            'snChasUnitName' => 'ICX6450-48-Unit1-Master',
            'snChasUnitDescr' => 'Brocade ICX6450-48 Switch',
            'snChasUnitType' => 'ICX6450-48',
            'snChasUnitStatus' => 1, // operational
            'snChasUnitSerialNumber' => 'ICX6450ABC123',
            'snChasUnitFirmwareVersion' => '08.0.30T311'
        ],
        2 => [
            'snChasUnitIndex' => 2,
            'snChasUnitName' => 'ICX6450-48-Unit2-Member',
            'snChasUnitDescr' => 'Brocade ICX6450-48 Switch',
            'snChasUnitType' => 'ICX6450-48',
            'snChasUnitStatus' => 1, // operational
            'snChasUnitSerialNumber' => 'ICX6450DEF456',
            'snChasUnitFirmwareVersion' => '08.0.30T311'
        ]
    ],

    'snStackTopoTable' => [
        '1.1/3/1' => [
            'snStackTopoLocalUnit' => 1,
            'snStackTopoLocalPort' => '1/3/1',
            'snStackTopoRemoteUnit' => 2,
            'snStackTopoRemotePort' => '1/3/1',
            'snStackTopoLinkStatus' => 1 // up
        ],
        '2.1/3/1' => [
            'snStackTopoLocalUnit' => 2,
            'snStackTopoLocalPort' => '1/3/1',
            'snStackTopoRemoteUnit' => 1,
            'snStackTopoRemotePort' => '1/3/1',
            'snStackTopoLinkStatus' => 1 // up
        ]
    ],

    'snStackPriorityTable' => [
        1 => [
            'snStackPriorityUnit' => 1,
            'snStackPriorityValue' => 255,
            'snStackPriorityRole' => 1 // master
        ],
        2 => [
            'snStackPriorityUnit' => 2,
            'snStackPriorityValue' => 128,
            'snStackPriorityRole' => 2 // member
        ]
    ]
];

// Mock device configuration for ICX 7750 standalone
$mock_icx7750_standalone = [
    'device' => [
        'hostname' => 'icx7750-standalone-01',
        'ip' => '192.168.1.21',
        'snmp_version' => 'v2c',
        'snmp_community' => 'public'
    ],

    'SNMPv2-MIB::sysDescr.0' => 'Brocade Communications Systems, Inc. ICX7750-26Q, IronWare Version 08.0.30T311 Compiled on Sep 14 2018 at 10:24:18',
    'SNMPv2-MIB::sysObjectID.0' => '.1.3.6.1.4.1.1991.1.3.49',

    'snChasUnitTable' => [
        1 => [
            'snChasUnitIndex' => 1,
            'snChasUnitName' => 'ICX7750-26Q-Standalone',
            'snChasUnitDescr' => 'Brocade ICX7750-26Q Switch',
            'snChasUnitType' => 'ICX7750-26Q',
            'snChasUnitStatus' => 1, // operational
            'snChasUnitSerialNumber' => 'ICX7750XYZ789',
            'snChasUnitFirmwareVersion' => '08.0.30T311'
        ]
    ]
];

// Mock device configuration for ICX 7150 stack
$mock_icx7150_stack = [
    'device' => [
        'hostname' => 'icx7150-stack-01',
        'ip' => '192.168.1.22',
        'snmp_version' => 'v2c',
        'snmp_community' => 'public'
    ],

    'SNMPv2-MIB::sysDescr.0' => 'Ruckus Networks ICX7150-24, IronWare Version 08.0.40T313 Compiled on Nov 20 2019 at 15:30:45',
    'SNMPv2-MIB::sysObjectID.0' => '.1.3.6.1.4.1.1991.1.3.49',

    'snChasUnitTable' => [
        1 => [
            'snChasUnitIndex' => 1,
            'snChasUnitName' => 'ICX7150-24-Unit1-Master',
            'snChasUnitDescr' => 'Ruckus ICX7150-24 Switch',
            'snChasUnitType' => 'ICX7150-24',
            'snChasUnitStatus' => 1, // operational
            'snChasUnitSerialNumber' => 'ICX7150ABC111',
            'snChasUnitFirmwareVersion' => '08.0.40T313'
        ],
        2 => [
            'snChasUnitIndex' => 2,
            'snChasUnitName' => 'ICX7150-24-Unit2-Member',
            'snChasUnitDescr' => 'Ruckus ICX7150-24 Switch',
            'snChasUnitType' => 'ICX7150-24',
            'snChasUnitStatus' => 1, // operational
            'snChasUnitSerialNumber' => 'ICX7150DEF222',
            'snChasUnitFirmwareVersion' => '08.0.40T313'
        ],
        3 => [
            'snChasUnitIndex' => 3,
            'snChasUnitName' => 'ICX7150-24-Unit3-Member',
            'snChasUnitDescr' => 'Ruckus ICX7150-24 Switch',
            'snChasUnitType' => 'ICX7150-24',
            'snChasUnitStatus' => 1, // operational
            'snChasUnitSerialNumber' => 'ICX7150GHI333',
            'snChasUnitFirmwareVersion' => '08.0.40T313'
        ]
    ],

    'snStackTopoTable' => [
        '1.1/2/1' => [
            'snStackTopoLocalUnit' => 1,
            'snStackTopoLocalPort' => '1/2/1',
            'snStackTopoRemoteUnit' => 2,
            'snStackTopoRemotePort' => '1/2/1',
            'snStackTopoLinkStatus' => 1 // up
        ],
        '1.1/2/2' => [
            'snStackTopoLocalUnit' => 1,
            'snStackTopoLocalPort' => '1/2/2',
            'snStackTopoRemoteUnit' => 3,
            'snStackTopoRemotePort' => '1/2/1',
            'snStackTopoLinkStatus' => 1 // up
        ],
        '2.1/2/1' => [
            'snStackTopoLocalUnit' => 2,
            'snStackTopoLocalPort' => '1/2/1',
            'snStackTopoRemoteUnit' => 1,
            'snStackTopoRemotePort' => '1/2/1',
            'snStackTopoLinkStatus' => 1 // up
        ],
        '3.1/2/1' => [
            'snStackTopoLocalUnit' => 3,
            'snStackTopoLocalPort' => '1/2/1',
            'snStackTopoRemoteUnit' => 1,
            'snStackTopoRemotePort' => '1/2/2',
            'snStackTopoLinkStatus' => 1 // up
        ]
    ],

    'snStackPriorityTable' => [
        1 => [
            'snStackPriorityUnit' => 1,
            'snStackPriorityValue' => 255,
            'snStackPriorityRole' => 1 // master
        ],
        2 => [
            'snStackPriorityUnit' => 2,
            'snStackPriorityValue' => 128,
            'snStackPriorityRole' => 2 // member
        ],
        3 => [
            'snStackPriorityUnit' => 3,
            'snStackPriorityValue' => 64,
            'snStackPriorityRole' => 2 // member
        ]
    ]
];

// Helper function to simulate snmp_get for testing
function mock_snmp_get($device_config, $oid) {
    if (isset($device_config[$oid])) {
        return $device_config[$oid];
    }
    return false;
}

// Helper function to simulate snmpwalk_cache_oid for testing
function mock_snmpwalk_cache_oid($device_config, $table_name) {
    if (isset($device_config[$table_name])) {
        return $device_config[$table_name];
    }
    return [];
}

?>