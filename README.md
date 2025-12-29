# Castle Fun Center AV Control System

A centralized, password-protected web-based audio-visual control system designed for Castle Fun Center. This system manages AV equipment across multiple entertainment zones including bowling lanes, an ice rink, arcade areas, DJ booth, and outdoor spaces. Each zone features dedicated controls for receiver management, IR remote functionality, power management, volume control, and smart lighting integration.

---

## Table of Contents

- [System Overview](#system-overview)
- [Project Structure](#project-structure)
- [Features](#features)
- [Architecture](#architecture)
- [Hardware Integration](#hardware-integration)
- [Zone Descriptions](#zone-descriptions)
- [Setup Instructions](#setup-instructions)
- [Configuration Guide](#configuration-guide)
- [Usage Guide](#usage-guide)
- [Authentication and Security](#authentication-and-security)
- [API Reference](#api-reference)
- [Alert & Monitoring System](#alert--monitoring-system)
- [Troubleshooting](#troubleshooting)
- [Maintenance](#maintenance)

---

## System Overview

The Castle Fun Center AV Control System is a production-grade web application that provides centralized control over 50+ AV devices distributed across 9 entertainment zones. The system is designed for reliability and ease of use by venue staff.

### Key Capabilities

- **Multi-Zone Control**: 9 independent zones with tailored interfaces
- **Bulk Operations**: Control all zones simultaneously or select multiple zones
- **Real-time Feedback**: Instant status updates from connected devices
- **Smart Lighting**: WLED-compatible addressable LED control
- **IR Remote Emulation**: Full cable box and media device control
- **Power Management**: CEC-based display power control
- **Anti-Popping Audio**: Intelligent audio muting during channel changes
- **Persistent Settings**: Volume and configuration persistence across sessions

### Technology Stack

| Component | Technology |
|-----------|------------|
| Backend | PHP 7.4+ with cURL |
| Frontend | HTML5, CSS3 (Material Design), jQuery 3.7.1 |
| Storage | JSON, INI, PHP constants (file-based) |
| Network | HTTP API over private 192.168.8.0/24 network |
| Lighting | WLED JSON API on 192.168.6.0/24 network |

---

## Project Structure

```
AV-system/
├── index.html                 # Password-protected landing page
├── script.js                  # Authentication & navigation logic
├── logo.png                   # Castle Fun Center branding
├── zones.json                 # Master zone configuration registry
├── config.ini                 # System-wide alerts & webhooks config
│
├── zonemanager.php            # Zone management interface (add/edit/delete zones)
├── settings.php               # Settings entry point (?zone=X)
├── editini.php                # Config file editor entry point (?zone=X)
├── wled.php                   # WLED control entry point (?zone=X)
│
├── api/
│   └── zones.php              # API endpoint for zone configuration
│
├── shared/                    # Shared codebase (core functionality)
│   ├── BaseController.php     # Base class for AJAX request handling (380 lines)
│   ├── utils.php              # Utility functions - API, volume, channel (568 lines)
│   ├── zones.php              # Zone CRUD operations (737 lines)
│   ├── settings.php           # Settings management UI
│   ├── editini.php            # Configuration file editor
│   ├── wled.php               # WLED control handler
│   ├── styles.css             # Material Design dark theme (16,554 chars)
│   └── script.js              # Shared JavaScript utilities
│
├── zone-templates/            # Template files for new zone creation
│   └── README.md              # Zone template instructions
│
└── [zone]/                    # Zone directories (9 total)
    ├── index.php              # Zone entry point (handles AJAX)
    ├── config.php             # Zone-specific configuration
    ├── template.php           # Zone UI template
    ├── transmitters.txt       # IR blaster device list
    ├── payloads.txt           # IR command codes (sendir format)
    ├── favorites.ini          # Favorite channel mappings
    ├── WLEDlist.ini           # WLED device IP addresses
    ├── saved_volumes.json     # Persistent volume state (some zones)
    └── av_controls.log        # Zone activity log

Zone Directories:
├── bowling/                   # Bowling Lanes (4 receivers)
├── bowlingbar/                # Bowling Bar (5 receivers)
├── rink/                      # Ice Rink
├── jesters/                   # Jesters Arcade Area
├── facility/                  # Facility-wide Controls
├── outside/                   # Outdoor Area
├── dj/                        # DJ Booth (17 receivers - largest zone)
├── multi/                     # Multi-zone Selection Control
└── all/                       # ALL Zones Simultaneous Control
```

---

## Features

### Core Features

| Feature | Description |
|---------|-------------|
| **Multi-Zone Control** | 9 independent zones with customized interfaces |
| **Receiver Management** | Channel and volume control for all AV receivers |
| **IR Remote Control** | Full cable box emulation (power, guide, navigation, numbers) |
| **Power Management** | CEC-based TV/display power on/off |
| **Volume Control** | Per-receiver volume with model-based support detection |
| **Anti-Popping Audio** | Mutes audio during channel changes to prevent pops |
| **WLED Integration** | Smart addressable LED lighting control |
| **Favorite Channels** | Quick-access channel presets per zone |
| **Bulk Operations** | Control multiple receivers/zones simultaneously |

### Management Features

| Feature | Description |
|---------|-------------|
| **Zone Manager** | Add, edit, duplicate, reorder, and remove zones |
| **Settings Editor** | Web-based receiver/transmitter configuration |
| **Config File Editor** | Edit INI files (transmitters, favorites, WLED, payloads) |
| **Automatic Backups** | Configuration backups before every save (keeps 3) |
| **Atomic File Writes** | File locking prevents corruption during saves |

### User Experience

| Feature | Description |
|---------|-------------|
| **Responsive Design** | Works on desktop, tablet, and mobile |
| **Material Design** | Dark theme with accessibility features |
| **Session Persistence** | Authentication persists until midnight |
| **Real-time Feedback** | Status messages for all operations |
| **Keyboard Navigation** | Full keyboard accessibility support |

---

## Architecture

### Request Flow

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Web Browser   │────▶│   index.html     │────▶│  Zone Selection │
│                 │     │  (Password Gate) │     │                 │
└─────────────────┘     └──────────────────┘     └────────┬────────┘
                                                          │
                                                          ▼
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  JSON Response  │◀────│   utils.php      │◀────│  /[zone]/index  │
│                 │     │  (API Calls)     │     │  (Controller)   │
└─────────────────┘     └────────┬─────────┘     └─────────────────┘
                                 │
                                 ▼
                        ┌──────────────────┐
                        │   AV Devices     │
                        │  (192.168.8.x)   │
                        └──────────────────┘
```

### Component Responsibilities

| Component | Responsibility |
|-----------|---------------|
| `index.html` | Password gate, zone navigation, session management |
| `BaseController.php` | AJAX routing, request validation, response formatting |
| `utils.php` | Device communication, volume/channel control, input validation |
| `zones.php` | Zone CRUD operations, configuration persistence |
| `[zone]/config.php` | Zone-specific settings (receivers, transmitters, limits) |
| `[zone]/template.php` | Zone UI rendering |

### Data Flow

1. User authenticates via password on landing page
2. Session stored in localStorage with daily expiration
3. User selects zone from navigation
4. Zone interface loads current device states via API
5. User actions trigger AJAX requests to zone controller
6. Controller validates input and calls appropriate utility functions
7. Utility functions communicate with devices via HTTP API
8. Response returned to UI for display

---

## Hardware Integration

### AV Receivers/Processors

The system controls Crestron-compatible AV receivers via HTTP API:

| Model | Volume Support | Features |
|-------|---------------|----------|
| 3G+4+ TX | Yes | Full control |
| 3G+AVP RX | Yes | Full control |
| 3G+AVP TX | Yes | DSP audio control |
| 3G+WP4 TX | Yes | DSP audio control |
| 2G/3G SX | Yes | Basic control |

**Network**: All receivers on 192.168.8.0/24

### IR Transmitters (Blasters)

GE Cresnet-compatible IR blasters with HTTP API:
- Support for SENDIR format IR codes
- Support for hex format IR codes
- Multiple channels per transmitter (1-10)

### Input Sources

| Source | Description |
|--------|-------------|
| Cable Box 1-3 | Cable TV receivers |
| Apple TV | Streaming device |
| RockBot Audio | Background music system |
| Wireless Mic TX | Microphone transmitter |
| Mobile Video TX | Portable video source |
| Mobile Audio TX | Portable audio source |
| Unifi Signage | Digital signage system |

### Smart Lighting (WLED)

WLED-compatible addressable LED controllers:
- Network: 192.168.6.0/24
- Protocol: JSON API
- Per-zone device lists

---

## Zone Descriptions

| Zone | ID | Receivers | Description |
|------|-----|-----------|-------------|
| **Bowling Lanes** | `bowling` | 4 | NeoVerse displays + Bowling Music receiver |
| **Bowling Bar** | `bowlingbar` | 5 | Bar area with TV displays |
| **Ice Rink** | `rink` | - | Ice rink video/audio system |
| **Jesters** | `jesters` | - | Arcade and entertainment area |
| **Facility** | `facility` | - | Facility-wide control center |
| **Outside** | `outside` | - | Outdoor displays and audio |
| **DJ Booth** | `dj` | 17 | Main entertainment control (largest zone) |
| **Multi** | `multi` | - | Select multiple zones for batch control |
| **ALL** | `all` | - | Control all zones simultaneously |

### Special Zones

- **ALL Zone**: Sends commands to all receivers across all zones
- **Multi Zone**: Allows selecting specific zones for batch operations
- **DJ Zone**: Central control hub with 17 receivers for main event management

---

## Setup Instructions

### Requirements

**Server:**
- PHP 7.4+ with extensions: `curl`, `json`
- Web server (Apache/Nginx) with PHP support
- Write permissions on zone directories

**Network:**
- Access to AV devices on 192.168.8.0/24 network
- Access to WLED devices on 192.168.6.0/24 network
- IR transmitter devices at configured IPs
- CEC-enabled displays for power control

### Installation

1. **Clone the repository:**
   ```bash
   git clone [repository_url] /var/www/html/AV-system
   cd /var/www/html/AV-system
   ```

2. **Set directory permissions:**
   ```bash
   chmod -R 755 .
   chmod -R 777 */  # Allow writes to zone directories
   ```

3. **Configure web server** (Apache example):
   ```apache
   <Directory /var/www/html/AV-system>
       AllowOverride All
       Require all granted
   </Directory>
   ```

4. **Verify network connectivity:**
   ```bash
   ping 192.168.8.25  # Test receiver connectivity
   ping 192.168.6.13  # Test WLED connectivity
   ```

### Password Configuration

The system password is defined in `/script.js`:
```javascript
const x7y9z3 = "your_password";
```

**Important**: Change this password before deploying to production.

---

## Configuration Guide

### Zone Configuration (config.php)

Each zone has a `config.php` file defining its settings:

```php
<?php
// Receivers: AV devices that accept commands
const RECEIVERS = [
    'Display Name' => [
        'ip' => '192.168.8.XX',    // Device IP address
        'show_power' => true,       // Show power on/off buttons
    ],
];

// Transmitters: Input sources mapped to channel numbers
const TRANSMITTERS = [
    'Cable Box 1' => 7,
    'Apple TV' => 2,
    'RockBot Audio' => 10,
];

// Volume settings
const MAX_VOLUME = 10;
const MIN_VOLUME = 5;
const VOLUME_STEP = 1;

// API settings
const API_TIMEOUT = 2;           // Seconds
const LOG_LEVEL = 'error';       // debug, info, warning, error

// System URLs
const HOME_URL = 'http://192.168.8.127';
const LOG_FILE = __DIR__ . '/av_controls.log';
```

### Master Zone Registry (zones.json)

The `zones.json` file is the single source of truth for zone configuration:

```json
{
    "zones": [
        {
            "id": "bowling",
            "name": "Bowling Lanes",
            "description": "Bowling lanes area AV controls",
            "enabled": true,
            "showInNav": true,
            "icon": "bowling",
            "color": "#00C853"
        }
    ],
    "specialLinks": [
        {
            "id": "dashboard",
            "name": "Dashboard",
            "url": "/dashboard",
            "enabled": true
        }
    ],
    "settings": {
        "defaultColor": "#00C853",
        "allowUserZoneCreation": true,
        "requirePasswordForZoneManagement": true
    }
}
```

### Data Files

| File | Format | Purpose |
|------|--------|---------|
| `transmitters.txt` | CSV | IR blaster devices: `Name, http://IP` |
| `payloads.txt` | INI | IR commands: `command=sendir,...` |
| `favorites.ini` | INI | Quick channels: `channel_number=Channel Name` |
| `WLEDlist.ini` | INI | WLED IPs: `ip1 = "192.168.X.X"` |
| `saved_volumes.json` | JSON | Persistent volume state |

### IR Payload Format

IR commands in `payloads.txt` use the SENDIR format:

```ini
power=sendir,1:1,1,58000,1,1,192,192,48,145,...
guide=0000 0048 0000 0018 00c0 00c0...
channel_up=sendir,1:1,1,58000,1,1,193,192,49,...
0=sendir,1:1,1,58000,1,1,192,192,48,145,...
```

---

## Usage Guide

### Daily Operations

1. **Access the System**: Navigate to the server address in a web browser
2. **Authenticate**: Enter the system password
3. **Select Zone**: Click desired zone from the navigation grid
4. **Control Devices**:
   - **Channel**: Use dropdown to select input source
   - **Volume**: Adjust slider (auto-saves)
   - **Power**: Click Power On/Off buttons
   - **Remote**: Use virtual remote for detailed control

### Zone Manager

Access via Ctrl+Click on logo or navigate to `/zonemanager.php`:

- **Add Zone**: Create new zone with optional template copy
- **Edit Zone**: Modify name, description, visibility
- **Duplicate Zone**: Clone existing zone configuration
- **Reorder**: Drag and drop to change navigation order
- **Delete Zone**: Remove zone (optionally delete files)

### Settings Editor

Access via Ctrl+Click on zone logo or `/settings.php?zone=zonename`:

- Add/remove receivers
- Configure transmitter mappings
- Set volume limits
- Configure API timeout

### WLED Control

Control smart lighting via `/wled.php`:

- Zone-specific lighting control
- Bulk on/off operations
- Per-device status tracking

---

## Authentication and Security

### Authentication Model

- **Client-Side Validation**: Password verified in browser
- **Session Storage**: localStorage with daily expiration (midnight)
- **Attempt Limiting**: 10 failed attempts locks form until refresh
- **Zone Validation**: Server-side whitelist prevents unauthorized access

### Security Features

| Feature | Implementation |
|---------|---------------|
| Input Sanitization | `sanitizeInput()` validates all user input |
| IP Validation | `filter_var()` with `FILTER_VALIDATE_IP` |
| Output Escaping | `htmlspecialchars()` prevents XSS |
| Zone Whitelist | Validation against `zones.json` registry |
| File Locking | Atomic writes prevent race conditions |
| Error Masking | Internal paths not exposed to users |

### Security Recommendations

For production deployment:
- Implement server-side authentication (LDAP, SSO)
- Enable HTTPS for all connections
- Restrict access by IP address
- Move `config.ini` outside web root
- Implement rate limiting on API endpoints
- Enable PHP error logging to file (not display)

---

## API Reference

### Device API Endpoints

Communication with AV receivers:

| Endpoint | Method | Purpose | Response |
|----------|--------|---------|----------|
| `/cgi-bin/api/details/channel` | GET | Get current channel | `{"data": 2}` |
| `/cgi-bin/api/command/channel` | POST | Set channel | `{"data": "OK"}` |
| `/cgi-bin/api/details/audio/stereo/volume` | GET | Get volume | `{"data": 10}` |
| `/cgi-bin/api/command/audio/stereo/volume` | POST | Set volume | `{"data": "OK"}` |
| `/cgi-bin/api/details/device/model` | GET | Get device model | `{"data": "3G+AVP RX"}` |
| `/cgi-bin/api/command/cli` | POST | Execute CLI command | `{"data": "OK"}` |
| `/cgi-bin/api/command/audio/dsp/line` | POST | DSP line control | `{"data": "OK"}` |
| `/cgi-bin/api/command/audio/dsp/hdmi` | POST | DSP HDMI control | `{"data": "OK"}` |
| `/cgi-bin/api/command/hdmi/audio/mute` | POST | Mute HDMI audio | `{"data": "OK"}` |
| `/cgi-bin/api/command/hdmi/audio/unmute` | POST | Unmute HDMI | `{"data": "OK"}` |

### System Web APIs

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/zones.php` | GET | Get zones configuration |
| `/[zone]/` | POST | Control zone receivers |
| `/settings.php?zone=X` | GET/POST | Zone settings |
| `/editini.php?zone=X` | GET/POST | Config file editor |
| `/wled.php` | POST | WLED lighting control |
| `/zonemanager.php` | POST | Zone management |

### WLED API

| Endpoint | Method | Payload |
|----------|--------|---------|
| `/json/state` | POST | `{"on": true}` or `{"on": false}` |

---

## Alert & Monitoring System

### Configuration (config.ini)

```ini
[general]
alerts_enabled    = "true"
alert_cooldown    = "1"          # Minutes between alerts
alert_hours_start = ""           # Optional: start hour (0-23)
alert_hours_end   = ""           # Optional: end hour (0-23)

[slack_bot]
bot_token         = "xoxb-..."
channel           = "#av-alerts"
dashboard_url     = "http://192.168.8.127/monitor"
alert_on_down     = "true"
alert_on_recovery = "true"

[email]
recipients        = "tech@castlefun.com"
from_email        = "av-system@castlefun.com"
alert_on_down     = "true"
alert_on_recovery = "true"

[textbee]
enabled           = true
api_key           = ""
device_id         = ""
recipients        = "+1234567890"
high_priority_only = false
```

### Supported Alert Channels

| Channel | Description |
|---------|-------------|
| Slack Bot | Direct channel messages via Bot API |
| Slack Webhook | Incoming webhook notifications |
| Email | PHP mail() function |
| TextBee SMS | SMS alerts via TextBee API |
| Custom Webhook | Configurable HTTP webhooks |

---

## Troubleshooting

### Authentication Issues

| Problem | Solution |
|---------|----------|
| Locked out | Refresh page to reset attempt counter |
| Session expired | Re-enter password (expires at midnight) |
| Clear session | Delete localStorage entries for the site |

### Device Communication

| Problem | Solution |
|---------|----------|
| Connection timeout | Verify device IP in config.php, check network |
| API errors | Confirm device is powered on and accessible |
| Volume not changing | Device may not support volume (check model) |

### IR Commands

| Problem | Solution |
|---------|----------|
| Commands not working | Verify IPs in transmitters.txt |
| Wrong actions | Check payloads.txt has correct IR codes |
| Intermittent failures | Check network connectivity to IR blasters |

### WLED Issues

| Problem | Solution |
|---------|----------|
| Lights not responding | Verify IPs in WLEDlist.ini |
| Partial control | Some devices may be offline |

### Configuration

| Problem | Solution |
|---------|----------|
| Settings not saving | Check write permissions on zone directories |
| Backup failures | Ensure sufficient disk space |

### Enabling Debug Logging

In zone `config.php`:
```php
define('LOG_LEVEL', 'debug');
```

Logs written to `[zone]/av_controls.log`

---

## Maintenance

### Backup Procedures

The system automatically creates backups:
- `config_backup_YYYYMMDD_HHMMSS.php` - Before each config save
- Keeps 3 most recent backups per zone

**Manual backup:**
```bash
tar -czf av-system-backup-$(date +%Y%m%d).tar.gz /var/www/html/AV-system
```

### Log Management

Logs are stored per-zone in `av_controls.log`. To rotate:
```bash
for zone in bowling bowlingbar rink jesters facility outside dj multi all; do
    mv /var/www/html/AV-system/$zone/av_controls.log \
       /var/www/html/AV-system/$zone/av_controls.log.$(date +%Y%m%d)
done
```

### Health Checks

1. **Verify zone accessibility**: Visit each zone in browser
2. **Test device communication**: Change channel on each receiver
3. **Check WLED connectivity**: Toggle lights in each zone
4. **Review logs**: Check for error patterns

### Adding New Hardware

1. **New Receiver**:
   - Add entry to zone's `config.php` RECEIVERS array
   - Or use Settings Editor UI

2. **New Transmitter (Input Source)**:
   - Add entry to zone's `config.php` TRANSMITTERS array
   - Assign unique channel number

3. **New IR Blaster**:
   - Add entry to zone's `transmitters.txt`

4. **New WLED Device**:
   - Add entry to zone's `WLEDlist.ini`

---

## Authors

- **Seth Morrow** - System architecture and development

---

## License

This project is proprietary software for Castle Fun Center AV Control System.

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 3.0 | 2025 | Complete refactor with shared codebase |
| 2.0 | - | Zone manager and settings UI |
| 1.0 | - | Initial multi-zone implementation |
