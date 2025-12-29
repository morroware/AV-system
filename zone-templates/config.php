<?php
/**
 * Zone Configuration Template
 *
 * This is a template configuration file for new zones.
 * Edit this file to configure receivers, transmitters, and other settings.
 *
 * RECEIVERS: Define AV receivers in this zone
 *   - 'Device Name' => ['ip' => '192.168.8.XX', 'show_power' => true/false]
 *   - show_power: Whether to display power on/off buttons
 *
 * TRANSMITTERS: Define input sources and their channel numbers
 *   - 'Input Name' => channel_number
 *   - Channel numbers correspond to your AV matrix input channels
 *
 * @version 1.0 (Template)
 */

// ============================================================================
// RECEIVERS - AV devices that receive signals (TVs, Monitors, Audio systems)
// ============================================================================
const RECEIVERS = [
    // Example entries - modify for your setup:
    // 'Living Room TV' => ['ip' => '192.168.8.100', 'show_power' => true],
    // 'Kitchen Display' => ['ip' => '192.168.8.101', 'show_power' => false],
    // 'Zone Audio' => ['ip' => '192.168.8.102', 'show_power' => false],
];

// ============================================================================
// TRANSMITTERS - Input sources (Apple TV, Cable Box, etc.)
// ============================================================================
const TRANSMITTERS = [
    // Example entries - modify for your setup:
    // 'Apple TV' => 1,
    // 'Cable Box' => 2,
    // 'Streaming Box' => 3,
    // 'Music Player' => 4,
];

// ============================================================================
// VOLUME SETTINGS
// ============================================================================
const MAX_VOLUME = 11;      // Maximum volume level
const MIN_VOLUME = 0;       // Minimum volume level
const VOLUME_STEP = 1;      // Volume increment/decrement step

// ============================================================================
// SYSTEM SETTINGS
// ============================================================================
const HOME_URL = '/';  // URL for the home button (relative path)
const LOG_LEVEL = 'error';                 // Logging level: debug, info, warning, error
const API_TIMEOUT = 2;                     // API request timeout in seconds

// ============================================================================
// REMOTE CONTROL COMMANDS (Usually no need to modify)
// ============================================================================
const REMOTE_CONTROL_COMMANDS = [
    'power', 'guide', 'up', 'down', 'left', 'right', 'select',
    'channel_up', 'channel_down',
    '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
    'last', 'exit'
];

// ============================================================================
// VOLUME CONTROL MODELS (Device models that support volume control)
// ============================================================================
const VOLUME_CONTROL_MODELS = [
    '3G+4+ TX',
    '3G+AVP RX',
    '3G+AVP TX',
    '3G+WP4 TX',
    '2G/3G SX'
];

// ============================================================================
// ERROR MESSAGES
// ============================================================================
const ERROR_MESSAGES = [
    'connection' => 'Unable to connect to %s (%s). Please check the connection and try again.',
    'global' => 'Unable to connect to any receivers. Please check your network connection and try again.',
    'remote' => 'Unable to send remote command. Please try again.',
];

// ============================================================================
// LOG FILE LOCATION
// ============================================================================
const LOG_FILE = __DIR__ . '/av_controls.log';
