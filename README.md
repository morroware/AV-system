# Castle AV Controls

A centralized, password-protected web-based system for managing audio-visual equipment across multiple zones within a venue. Each zone (Bowling Bar, Rink, DJ, Jesters, Facility, Outside, etc.) has its own dedicated interface with tailored controls for remote functionality, power management, volume control, and WLED lighting integration.

---

## Table of Contents

- [Project Structure](#project-structure)
- [Features](#features)
- [Architecture](#architecture)
- [Setup Instructions](#setup-instructions)
- [Usage Guide](#usage-guide)
- [Authentication and Security](#authentication-and-security)
- [Zone Configuration](#zone-configuration)
- [Shared Components](#shared-components)
- [API Integration](#api-integration)
- [Troubleshooting](#troubleshooting)

---

## Project Structure

```
AV-system/
├── index.html              # Password-protected landing page
├── script.js               # Authentication & navigation logic
├── logo.png                # Branding logo
├── config.ini              # System-wide configuration (alerts, webhooks)
│
├── settings.php            # Single entry-point for zone settings (?zone=X)
├── editini.php             # Single entry-point for config file editor (?zone=X)
├── wled.php                # Single entry-point for WLED control (?zone=X)
│
├── shared/                 # Shared code & resources
│   ├── BaseController.php  # Base class for AJAX request handling
│   ├── utils.php           # Utility functions (API calls, volume, channel control)
│   ├── settings.php        # Settings management interface
│   ├── editini.php         # Configuration file editor
│   ├── wled.php            # WLED control handler
│   ├── styles.css          # Shared styling (Material Design dark theme)
│   └── script.js           # Shared JavaScript utilities
│
└── [zone]/                 # Zone directories (9 zones)
    ├── index.php           # Main control interface for the zone
    ├── config.php          # Zone-specific configuration (receivers, transmitters)
    ├── template.php        # Reusable UI components
    ├── transmitters.txt    # IR transmitter device list
    ├── payloads.txt        # IR command codes (sendir format)
    ├── favorites.ini       # Favorite channel mappings
    ├── WLEDlist.ini        # WLED device IP addresses
    └── saved_volumes.json  # Persistent volume state (some zones)

Zones: all, bowling, bowlingbar, dj, facility, jesters, multi, outside, rink
```

---

## Features

- **Multi-Zone Control**: 9 independent zones, each with tailored AV controls
- **Remote Control**: IR command transmission via network-connected transmitters
- **Power Management**: CEC-based power control for displays
- **Volume Control**: Receiver volume management with anti-popping measures
- **WLED Integration**: Smart lighting control via WLED JSON API
- **Channel Selection**: Quick switching between input sources (Cable, Apple TV, RockBot, etc.)
- **Bulk Operations**: Switch inputs on multiple receivers simultaneously
- **Audio Toggle**: Quick switching between RockBot and wireless mic with automatic volume adjustment
- **Settings Management**: Web-based configuration for receivers and transmitters
- **Daily Session Persistence**: Authentication remains active until end of day
- **Responsive Design**: Material Design dark theme, works on desktop and mobile

---

## Architecture

### Request Flow

```
User → index.html (auth) → Zone Selection → /[zone]/index.php
                                                    ↓
                                           AJAX POST Request
                                                    ↓
                                           BaseController.php
                                                    ↓
                                              utils.php
                                                    ↓
                                        cURL → AV Device API
                                                    ↓
                                           JSON Response → UI
```

### Entry Points

| URL | Description |
|-----|-------------|
| `/` | Password-protected landing page |
| `/[zone]/` | Zone-specific control interface |
| `/settings.php?zone=X` | Settings manager for zone X |
| `/editini.php?zone=X` | Config file editor for zone X |
| `/wled.php?zone=X` | WLED control endpoint for zone X |

---

## Setup Instructions

### Requirements

**Server:**
- PHP 7.4+ with extensions: `curl`, `json`
- Web server (Apache/Nginx) with PHP support
- Write permissions on zone directories

**Network:**
- Access to AV devices on 192.168.8.0/24 network
- IR transmitter devices (blasters) at configured IPs
- WLED-compatible devices (optional)
- CEC-enabled displays for power control

### Installation

1. Clone the repository to your web server's document root:
   ```bash
   git clone [repository_url] /var/www/html/AV-system
   cd /var/www/html/AV-system
   ```

2. Set directory permissions:
   ```bash
   chmod -R 755 .
   chmod -R 777 */saved_volumes.json  # If volume persistence is needed
   ```

3. Configure each zone by editing `[zone]/config.php`:
   ```php
   define('RECEIVERS', [
       'Device Name' => ['ip' => '192.168.8.XX', 'show_power' => true],
   ]);

   define('TRANSMITTERS', [
       'Source Name' => 1,  // Channel number
   ]);
   ```

4. Update IR commands in `[zone]/payloads.txt`:
   ```ini
   power_on=sendir,1:1,1,38000,1,69,...
   power_off=sendir,1:1,1,38000,1,69,...
   ```

5. Configure transmitter devices in `[zone]/transmitters.txt`:
   ```
   Transmitter 1, http://192.168.8.100
   Transmitter 2, http://192.168.8.101
   ```

6. (Optional) Set up WLED devices in `[zone]/WLEDlist.ini`:
   ```ini
   [WLEDs]
   ip1 = "192.168.6.13"
   ip2 = "192.168.6.14"
   ```

### Password Configuration

The password is defined in `/script.js`:
```javascript
const x7y9z3 = "your_password";
```

---

## Usage Guide

1. **Access the System**: Navigate to the server's address in a web browser

2. **Authenticate**: Enter the password to unlock access

3. **Select a Zone**: Click on the desired zone to open its control interface

4. **Control Devices**:
   - **Channel Selection**: Use dropdown or number pad to change input source
   - **Volume Control**: Adjust sliders for each receiver
   - **Power Control**: Toggle power for displays with CEC support
   - **Remote Buttons**: Send IR commands (navigation, playback, etc.)
   - **WLED Control**: Toggle lighting on/off for the zone

5. **Settings**: Click "Settings" to manage receivers and transmitters for the zone

---

## Authentication and Security

- **Client-Side Authentication**: Password validated in browser, session stored in localStorage
- **Daily Expiration**: Sessions expire at midnight, requiring re-authentication
- **Attempt Limiting**: 10 failed attempts disables the form until page refresh
- **Zone Validation**: Server-side whitelist prevents directory traversal attacks
- **Network Isolation**: System designed for use on trusted internal network

### Security Considerations

For production deployments, consider:
- Implementing server-side authentication
- Using HTTPS for all connections
- Restricting access by IP address
- Moving sensitive configuration outside web root

---

## Zone Configuration

### config.php Structure

Each zone's `config.php` defines:

```php
<?php
// Receivers: AV devices that accept commands
define('RECEIVERS', [
    'Display Name' => [
        'ip' => '192.168.8.XX',
        'show_power' => true,  // Show power controls
    ],
]);

// Transmitters: Input sources mapped to channel numbers
define('TRANSMITTERS', [
    'Cable Box' => 1,
    'Apple TV' => 2,
    'RockBot' => 10,
]);

// Volume settings
define('VOLUME_STEP', 1);
define('MAX_VOLUME', 11);
define('MIN_VOLUME', 0);

// API settings
define('API_TIMEOUT', 2);
define('LOG_LEVEL', 'error');  // debug, info, warning, error
```

### Data Files

| File | Format | Purpose |
|------|--------|---------|
| `transmitters.txt` | CSV | IR blaster device list: `Name, http://IP` |
| `payloads.txt` | INI | IR commands: `command=sendir,...` |
| `favorites.ini` | INI | Quick channel access: `[number]=[name]` |
| `WLEDlist.ini` | INI | WLED IPs: `ip1 = "192.168.X.X"` |
| `saved_volumes.json` | JSON | Persistent volume state |

---

## Shared Components

### BaseController.php
Handles AJAX request routing for zone interfaces. Detects request type (receiver control vs remote command) and delegates to appropriate utility functions.

### utils.php
Core utility functions:
- `makeApiCall($url, $method, $data)` - cURL wrapper for device communication
- `getCurrentChannel($ip)` / `setChannel($ip, $channel)` - Channel control
- `getCurrentVolume($ip)` / `setVolume($ip, $volume)` - Volume control
- `setChannelWithoutPopping($ip, $channel)` - Anti-popping channel change
- `generateReceiverForms($receivers)` - Dynamic UI generation

### styles.css
Material Design dark theme with CSS variables:
- Primary: `#bb86fc` (Purple)
- Secondary: `#03dac6` (Cyan)
- Background: `#121212`
- Surface: `#1e1e1e`

---

## API Integration

The system communicates with AV devices via HTTP API:

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/cgi-bin/api/details/channel` | GET | Get current channel |
| `/cgi-bin/api/command/channel` | POST | Set channel |
| `/cgi-bin/api/details/audio/stereo/volume` | GET | Get volume |
| `/cgi-bin/api/command/audio/stereo/volume` | POST | Set volume |
| `/cgi-bin/api/details/device/model` | GET | Get device model |
| `/cgi-bin/api/command/cli` | POST | Send CLI commands |
| `/json/state` (WLED) | POST | Control WLED lighting |

---

## Troubleshooting

### Authentication Issues
- **Locked out**: Refresh the page to reset attempt counter
- **Session expired**: Re-enter password (sessions expire at midnight)
- **Clear session**: Delete localStorage entries for the site

### Device Communication
- **Connection timeout**: Verify device IP in `config.php` is correct and reachable
- **API errors**: Check device is online and API endpoint is accessible
- **Volume not changing**: Verify device supports volume control API

### IR Commands
- **Commands not working**: Check `transmitters.txt` has correct IPs
- **Wrong actions**: Verify `payloads.txt` has correct IR codes for your devices
- **Intermittent failures**: Check network connectivity to IR blasters

### WLED Issues
- **Lights not responding**: Verify IPs in `WLEDlist.ini` are correct
- **Partial control**: Some devices may be offline or unreachable

### Configuration
- **Settings not saving**: Check write permissions on zone directories
- **Backup failures**: Ensure sufficient disk space and permissions

### Logs
Enable debug logging in zone `config.php`:
```php
define('LOG_LEVEL', 'debug');
```

---

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly across zones
5. Submit a pull request

---

## License

This project is proprietary software for Castle AV Control System.
