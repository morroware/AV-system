<?php
/**
 * Generated Configuration File for AV52725 Project
 * Last Updated: 2025-06-08 (Generated from JAP project data)
 * 
 * This configuration includes all devices from the Just Add Power project:
 * - 9 Transmitters (TX)
 * - 19 Receivers (RX)
 * Total: 28 devices
 */

// RECEIVER DEVICES (RX) - All video/audio output devices
const RECEIVERS = [
    // Bowling Bar TVs
    'Bowling Bar TV 1' => [
        'ip' => '192.168.8.60',
        'show_power' => true,
        'device_id' => 'RX60',
        'model' => '3G RX'
    ],
    'Bowling Bar TV 2' => [
        'ip' => '192.168.8.61',
        'show_power' => true,
        'device_id' => 'RX61',
        'model' => '3G RX'
    ],
    'Bowling Bar TV 3' => [
        'ip' => '192.168.8.62',
        'show_power' => true,
        'device_id' => 'RX62',
        'model' => '3G RX'
    ],
    'Bowling Bar TV 4' => [
        'ip' => '192.168.8.63',
        'show_power' => true,
        'device_id' => 'RX63',
        'model' => '3G RX'
    ],
    
    // NeoVerse Gaming Zones
    'NeoVerse 1' => [
        'ip' => '192.168.8.50',
        'show_power' => true,
        'device_id' => 'RX50',
        'model' => '3G RX'
    ],
    'NeoVerse 2' => [
        'ip' => '192.168.8.51',
        'show_power' => true,
        'device_id' => 'RX51',
        'model' => '3G RX'
    ],
    'NeoVerse 3' => [
        'ip' => '192.168.8.52',
        'show_power' => true,
        'device_id' => 'RX52',
        'model' => '3G RX'
    ],
    
    // Other Video Displays
    'Dining Area TV' => [
        'ip' => '192.168.8.70',
        'show_power' => true,
        'device_id' => 'RX70',
        'model' => '3G RX'
    ],
    'Rink Video Display' => [
        'ip' => '192.168.8.13',
        'show_power' => true,
        'device_id' => 'RX13',
        'model' => '3G RX'
    ],
    
    // Attic/Infrastructure Receivers
    'Attic RX 2' => [
        'ip' => '192.168.8.12',
        'show_power' => true,
        'device_id' => 'RX12',
        'model' => '3G RX'
    ],
    'Attic RX 4' => [
        'ip' => '192.168.8.20',
        'show_power' => true,
        'device_id' => 'RX20',
        'model' => '3G RX'
    ],
    
    // Audio Zones (No power controls for audio-only zones)
    'Bowling Bar Music' => [
        'ip' => '192.168.8.28',
        'show_power' => false,
        'device_id' => 'RX28',
        'model' => '2G/3G SX-RX'
    ],
    'Axe/Billiards Music' => [
        'ip' => '192.168.8.27',
        'show_power' => false,
        'device_id' => 'RX27',
        'model' => '2G/3G SX-RX'
    ],
    'Bowling Music' => [
        'ip' => '192.168.8.25',
        'show_power' => false,
        'device_id' => 'RX25',
        'model' => '2G/3G SX-RX'
    ],
    'Rink Music' => [
        'ip' => '192.168.8.15',
        'show_power' => false,
        'device_id' => 'RX15',
        'model' => '2G/3G SX-RX'
    ],
    'Facility Zone Pro' => [
        'ip' => '192.168.8.81',
        'show_power' => false,
        'device_id' => 'RX81',
        'model' => '2G/3G SX-RX'
    ],
];

// TRANSMITTER DEVICES (TX) - All video/audio input sources
const TRANSMITTERS = [
    // Cable Boxes / Video Sources
    'Cable Box 1 (Attic TX 1)' => 2,        // IP: 192.168.8.30, Channel 2
    'Cable Box 2 (Attic TX 2)' => 3,        // IP: 192.168.8.18, Channel 3  
    'Cable Box 3 (Attic TX 3)' => 4,        // IP: 192.168.8.19, Channel 4
    
    // Streaming/Digital Sources
    'Apple TV' => 7,                         // IP: 192.168.8.10, Channel 7
    'Unifi Signage' => 5,                    // IP: 192.168.8.26, Channel 5
    
    // Mobile/Portable Sources
    'Mobile Video TX' => 9,                  // IP: 192.168.8.11, Channel 9
    'Mobile Audio TX' => 1,                  // IP: 192.168.8.16, Channel 1
    
    // Audio Sources
    'RockBot Audio' => 10,                   // IP: 192.168.8.17, Channel 10
    'Wireless Mic TX' => 8,                  // IP: 192.168.8.80, Channel 8
];

// TRANSMITTER IP MAPPING (for IR remote control)
// Maps transmitter names to their actual IP addresses for remote control
const TRANSMITTER_IPS = [
    'Cable Box 1 (Attic TX 1)' => '192.168.8.30',
    'Cable Box 2 (Attic TX 2)' => '192.168.8.18', 
    'Cable Box 3 (Attic TX 3)' => '192.168.8.19',
    'Apple TV' => '192.168.8.10',
    'Unifi Signage' => '192.168.8.26',
    'Mobile Video TX' => '192.168.8.11',
    'Mobile Audio TX' => '192.168.8.16',
    'RockBot Audio' => '192.168.8.17',
    'Wireless Mic TX' => '192.168.8.80',
];

// DEVICE GROUPS FOR BULK OPERATIONS
const DEVICE_GROUPS = [
    'Bowling Bar TVs' => [
        'Bowling Bar TV 1',
        'Bowling Bar TV 2', 
        'Bowling Bar TV 3',
        'Bowling Bar TV 4'
    ],
    'NeoVerse Gaming' => [
        'NeoVerse 1',
        'NeoVerse 2',
        'NeoVerse 3', 
        'NeoVerse 4',
        'NeoVerse 5',
        'NeoVerse 6'
    ],
    'Audio Zones' => [
        'Bowling Bar Music',
        'Axe/Billiards Music',
        'Bowling Music',
        'Rink Music',
        'Facility Zone Pro'
    ],
    'All TVs' => [
        'Bowling Bar TV 1',
        'Bowling Bar TV 2',
        'Bowling Bar TV 3', 
        'Bowling Bar TV 4',
        'NeoVerse 1',
        'NeoVerse 2',
        'NeoVerse 3',
        'NeoVerse 4', 
        'NeoVerse 5',
        'NeoVerse 6',
        'Dining Area TV',
        'Rink Video Display'
    ]
];

// SYSTEM SETTINGS
const MAX_VOLUME = 11;
const MIN_VOLUME = 5;
const VOLUME_STEP = 1;
const HOME_URL = 'http://192.168.8.127';  // Updated to match your network
const LOG_LEVEL = 'error';
const API_TIMEOUT = 2;  // Increased for larger network

// NETWORK SETTINGS
const MULTICAST_IP = '192.168.8.150';
const NETWORK_GATEWAY = '192.168.8.1';
const NETWORK_SUBNET = '255.255.255.0';

// Remote control configuration
const REMOTE_CONTROL_COMMANDS = [
    'power',
    'guide', 
    'up',
    'down',
    'left',
    'right',
    'select',
    'channel_up',
    'channel_down',
    '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
    'last',
    'exit'
];

// Models that support volume control
const VOLUME_CONTROL_MODELS = [
    '3G+4+ TX',
    '3G+AVP RX', 
    '3G+AVP TX',
    '3G+WP4 TX',
    '2G/3G SX'  // SX models support volume control
];

// Error message templates
const ERROR_MESSAGES = [
    'connection' => 'Unable to connect to %s (%s). Please check the connection and try again.',
    'global' => 'Unable to connect to any receivers. Please check your network connection and try again.',
    'remote' => 'Unable to send remote command. Please try again.',
    'bulk' => 'Bulk operation completed with %d successes and %d failures.',
];

// Log file location
const LOG_FILE = __DIR__ . '/av_controls.log';

// PROJECT METADATA
const PROJECT_NAME = 'AV52725';
const PROJECT_VERSION = '1.3.3';
const DEVICE_COUNT = [
    'transmitters' => 9,
    'receivers' => 19, 
    'total' => 28
];

// CHANNEL USAGE SUMMARY
const CHANNEL_USAGE = [
    1 => 'Mobile Audio TX',
    2 => 'Cable Box 1 (Attic TX 1)',
    3 => 'Cable Box 2 (Attic TX 2)', 
    4 => 'Cable Box 3 (Attic TX 3)',
    5 => 'Unifi Signage',
    7 => 'Apple TV',
    8 => 'Wireless Mic TX (Default for most RX)',
    9 => 'Mobile Video TX',
    10 => 'RockBot Audio'
];
