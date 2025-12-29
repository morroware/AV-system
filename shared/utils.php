<?php
/**
 * Unified Utility Functions for AV Controls System
 *
 * This file consolidates all utility functions used across all zones.
 * It includes basic receiver control functions as well as advanced
 * DSP audio control functions for devices that support them.
 *
 * @author Seth Morrow
 * @version 3.0 (Refactored)
 */

// Set default values for configuration constants if they're not defined
if (!defined('API_TIMEOUT')) define('API_TIMEOUT', 5);
if (!defined('LOG_LEVEL')) define('LOG_LEVEL', 'error');
if (!defined('MAX_VOLUME')) define('MAX_VOLUME', 11);
if (!defined('MIN_VOLUME')) define('MIN_VOLUME', 0);
if (!defined('VOLUME_STEP')) define('VOLUME_STEP', 1);
if (!defined('HOME_URL')) define('HOME_URL', 'http://localhost');
if (!defined('ERROR_MESSAGES')) {
    define('ERROR_MESSAGES', [
        'connection' => 'Unable to connect to %s (%s). Please check the connection and try again.',
        'global' => 'Unable to connect to any receivers. Please check your network connection and try again.',
        'remote' => 'Unable to send remote command. Please try again.'
    ]);
}
if (!defined('VOLUME_CONTROL_MODELS')) {
    define('VOLUME_CONTROL_MODELS', ['3G+4+ TX', '3G+AVP RX', '3G+AVP TX', '3G+WP4 TX', '2G/3G SX']);
}

// ============================================================================
// RECEIVER FORM GENERATION
// ============================================================================

/**
 * Generate the HTML for all receiver forms
 *
 * @return string HTML for all receiver forms
 */
function generateReceiverForms() {
    if (!defined('RECEIVERS')) {
        return '<div class="error">No receivers configured</div>';
    }

    $html = '';
    foreach (RECEIVERS as $receiverName => $settings) {
        try {
            $html .= generateReceiverForm($receiverName, $settings['ip'], MIN_VOLUME, MAX_VOLUME, VOLUME_STEP, $settings['show_power']);
        } catch (Exception $e) {
            $html .= "<div class='receiver'><p class='warning'>Error generating form for " . htmlspecialchars($receiverName) . ": " . htmlspecialchars($e->getMessage()) . "</p></div>";
            logMessage("Error generating form for {$receiverName}: " . $e->getMessage(), 'error');
        }
    }
    return $html;
}

/**
 * Generate a single receiver form
 *
 * @param string $receiverName Display name for the receiver
 * @param string $deviceIp IP address of the device
 * @param int $minVolume Minimum volume level
 * @param int $maxVolume Maximum volume level
 * @param int $volumeStep Volume adjustment step
 * @param bool $showPower Whether to show power controls
 * @return string HTML for the receiver form
 */
function generateReceiverForm($receiverName, $deviceIp, $minVolume, $maxVolume, $volumeStep, $showPower = true) {
    try {
        $currentChannel = getCurrentChannel($deviceIp);
        if ($currentChannel === null) {
            throw new Exception("Unable to get current channel");
        }
        $supportsVolume = supportsVolumeControl($deviceIp);

        $html = "<div class='receiver' data-ip='" . htmlspecialchars($deviceIp) . "'>";
        $html .= "<button type='button' class='receiver-title'>" . htmlspecialchars($receiverName) . "</button>";

        // Generate channel selection dropdown
        $html .= "<label for='channel_" . htmlspecialchars($receiverName) . "'>Channel:</label>";
        $html .= "<select id='channel_" . htmlspecialchars($receiverName) . "' class='channel-select' data-ip='" . htmlspecialchars($deviceIp) . "'>";
        if (defined('TRANSMITTERS')) {
            foreach (TRANSMITTERS as $transmitterName => $channelNumber) {
                $selected = ($channelNumber == $currentChannel) ? ' selected' : '';
                $html .= "<option value='$channelNumber'$selected>" . htmlspecialchars($transmitterName) . "</option>";
            }
        }
        $html .= "</select>";

        // Generate volume control if supported
        if ($supportsVolume) {
            $currentVolume = getCurrentVolume($deviceIp);
            if ($currentVolume === null) {
                $currentVolume = $minVolume;
            }
            $html .= "<label for='volume_" . htmlspecialchars($receiverName) . "'>Volume:</label>";
            $html .= "<input type='range' id='volume_" . htmlspecialchars($receiverName) . "' class='volume-slider' data-ip='" . htmlspecialchars($deviceIp) . "' min='$minVolume' max='$maxVolume' step='$volumeStep' value='$currentVolume'>";
            $html .= "<span class='volume-label'>$currentVolume</span>";
        }

        // Add power buttons if enabled
        if ($showPower) {
            $html .= "<div class='power-buttons'>";
            $html .= "<button type='button' class='power-on' onclick='sendPowerCommand(\"" . htmlspecialchars($deviceIp) . "\", \"cec_tv_on.sh\")'>Power On</button>";
            $html .= "<button type='button' class='power-off' onclick='sendPowerCommand(\"" . htmlspecialchars($deviceIp) . "\", \"cec_tv_off.sh\")'>Power Off</button>";
            $html .= "</div>";
        }

        $html .= "</div>";

        return $html;
    } catch (Exception $e) {
        return "<div class='receiver'>" .
               "<button type='button' class='receiver-title'>" . htmlspecialchars($receiverName) . "</button>" .
               "<p style='text-align: center; color: #ff6b6b; padding: 1rem;'>" .
               "Device unreachable. Please check connection." .
               "</p></div>";
    }
}

// ============================================================================
// API COMMUNICATION
// ============================================================================

/**
 * Make API calls to AV devices
 *
 * @param string $method HTTP method (GET, POST, etc.)
 * @param string $deviceIp IP address of the device
 * @param string $endpoint API endpoint path
 * @param mixed $data Data to send (optional)
 * @param string $contentType Content type header
 * @return string API response
 * @throws Exception on failure
 */
function makeApiCall($method, $deviceIp, $endpoint, $data = null, $contentType = 'application/x-www-form-urlencoded') {
    $timeout = defined('API_TIMEOUT') ? API_TIMEOUT : 5;

    $apiUrl = 'http://' . $deviceIp . '/cgi-bin/api/' . $endpoint;
    $ch = curl_init($apiUrl);

    if ($ch === false) {
        throw new Exception('Failed to initialize cURL');
    }

    try {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        if ($data !== null) {
            if ($contentType === 'application/json' && !is_string($data)) {
                $data = json_encode($data);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: ' . $contentType));
        }

        $result = curl_exec($ch);

        if ($result === false) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode >= 400) {
            throw new Exception('HTTP error: ' . $httpCode . ' - Response: ' . $result);
        }

        return $result;
    } finally {
        if ($ch) {
            curl_close($ch);
        }
    }
}

// ============================================================================
// VOLUME CONTROL
// ============================================================================

/**
 * Get current volume level from device
 */
function getCurrentVolume($deviceIp) {
    try {
        $response = makeApiCall('GET', $deviceIp, 'details/audio/stereo/volume');
        $data = json_decode($response, true);
        return isset($data['data']) ? intval($data['data']) : null;
    } catch (Exception $e) {
        logMessage('Error getting current volume: ' . $e->getMessage(), 'error');
        return null;
    }
}

/**
 * Set volume level on device
 */
function setVolume($deviceIp, $volume) {
    try {
        $response = makeApiCall('POST', $deviceIp, 'command/audio/stereo/volume', $volume, 'text/plain');
        $data = json_decode($response, true);
        return isset($data['data']) && $data['data'] === 'OK';
    } catch (Exception $e) {
        logMessage('Error setting volume: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Check if device supports volume control
 */
function supportsVolumeControl($deviceIp) {
    try {
        $model = getDeviceModel($deviceIp);
        return in_array($model, VOLUME_CONTROL_MODELS);
    } catch (Exception $e) {
        logMessage('Error checking volume control support: ' . $e->getMessage(), 'error');
        return false;
    }
}

// ============================================================================
// CHANNEL CONTROL
// ============================================================================

/**
 * Get current channel from device
 */
function getCurrentChannel($deviceIp) {
    try {
        $response = makeApiCall('GET', $deviceIp, 'details/channel');
        $data = json_decode($response, true);
        return isset($data['data']) ? intval($data['data']) : null;
    } catch (Exception $e) {
        logMessage('Error getting current channel: ' . $e->getMessage(), 'error');
        return null;
    }
}

/**
 * Set channel on device
 */
function setChannel($deviceIp, $channel) {
    try {
        $response = makeApiCall('POST', $deviceIp, 'command/channel', $channel, 'text/plain');
        $data = json_decode($response, true);
        return isset($data['data']) && $data['data'] === 'OK';
    } catch (Exception $e) {
        logMessage('Error setting channel: ' . $e->getMessage(), 'error');
        return false;
    }
}

// ============================================================================
// DEVICE INFORMATION
// ============================================================================

/**
 * Cache for device models to avoid redundant API calls
 */
$_deviceModelCache = [];

/**
 * Get device model string with caching
 *
 * @param string $deviceIp Device IP address
 * @param bool $forceRefresh Force a fresh API call
 * @return string Device model or empty string on failure
 */
function getDeviceModel($deviceIp, $forceRefresh = false) {
    global $_deviceModelCache;

    // Return cached model if available
    if (!$forceRefresh && isset($_deviceModelCache[$deviceIp])) {
        return $_deviceModelCache[$deviceIp];
    }

    try {
        $response = makeApiCall('GET', $deviceIp, 'details/device/model');
        $data = json_decode($response, true);
        $model = isset($data['data']) ? $data['data'] : '';

        // Cache the result
        $_deviceModelCache[$deviceIp] = $model;

        return $model;
    } catch (Exception $e) {
        logMessage('Error getting device model: ' . $e->getMessage(), 'error');
        // Cache empty string to avoid repeated failed calls
        $_deviceModelCache[$deviceIp] = '';
        return '';
    }
}

// ============================================================================
// DSP AUDIO CONTROL (Advanced)
// ============================================================================

/**
 * Check if device supports DSP audio control
 */
function supportsDspControl($deviceIp) {
    $model = getDeviceModel($deviceIp);
    return (strpos($model, '3G+AVP TX') !== false || strpos($model, '3G+WP4 TX') !== false);
}

/**
 * Disable DSP Line audio
 */
function disableDspLineAudio($deviceIp) {
    try {
        $response = makeApiCall('POST', $deviceIp, 'command/audio/dsp/line', '"off"', 'application/json');
        $data = json_decode($response, true);
        return isset($data['data']) && $data['data'] === 'OK';
    } catch (Exception $e) {
        logMessage("DSP Line audio control not available for $deviceIp: " . $e->getMessage(), 'info');
        return false;
    }
}

/**
 * Enable DSP Line audio
 */
function enableDspLineAudio($deviceIp) {
    try {
        $response = makeApiCall('POST', $deviceIp, 'command/audio/dsp/line', '"on"', 'application/json');
        $data = json_decode($response, true);
        return isset($data['data']) && $data['data'] === 'OK';
    } catch (Exception $e) {
        logMessage("DSP Line audio control not available for $deviceIp: " . $e->getMessage(), 'info');
        return false;
    }
}

/**
 * Disable DSP HDMI audio
 */
function disableDspHdmiAudio($deviceIp) {
    try {
        $response = makeApiCall('POST', $deviceIp, 'command/audio/dsp/hdmi', '"off"', 'application/json');
        $data = json_decode($response, true);
        return isset($data['data']) && $data['data'] === 'OK';
    } catch (Exception $e) {
        logMessage("DSP HDMI audio control not available for $deviceIp: " . $e->getMessage(), 'info');
        return false;
    }
}

/**
 * Enable DSP HDMI audio
 */
function enableDspHdmiAudio($deviceIp) {
    try {
        $response = makeApiCall('POST', $deviceIp, 'command/audio/dsp/hdmi', '"on"', 'application/json');
        $data = json_decode($response, true);
        return isset($data['data']) && $data['data'] === 'OK';
    } catch (Exception $e) {
        logMessage("DSP HDMI audio control not available for $deviceIp: " . $e->getMessage(), 'info');
        return false;
    }
}

/**
 * Disable HDMI audio (mute)
 */
function disableHdmiAudio($deviceIp) {
    try {
        $response = makeApiCall('POST', $deviceIp, 'command/hdmi/audio/mute', null);
        $data = json_decode($response, true);
        return isset($data['data']) && $data['data'] === 'OK';
    } catch (Exception $e) {
        logMessage('Error disabling HDMI audio: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Enable HDMI audio (unmute)
 */
function enableHdmiAudio($deviceIp) {
    try {
        $response = makeApiCall('POST', $deviceIp, 'command/hdmi/audio/unmute', null);
        $data = json_decode($response, true);
        return isset($data['data']) && $data['data'] === 'OK';
    } catch (Exception $e) {
        logMessage('Error enabling HDMI audio: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Disable stereo audio output (mute)
 */
function disableStereoAudio($deviceIp) {
    try {
        $response = makeApiCall('POST', $deviceIp, 'command/cli', 'audio_out.sh mute', 'text/plain');
        $data = json_decode($response, true);
        return isset($data['data']) && $data['data'] === 'OK';
    } catch (Exception $e) {
        logMessage('Error disabling stereo audio: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Enable stereo audio output (unmute)
 */
function enableStereoAudio($deviceIp) {
    try {
        $response = makeApiCall('POST', $deviceIp, 'command/cli', 'audio_out.sh unmute', 'text/plain');
        $data = json_decode($response, true);
        return isset($data['data']) && $data['data'] === 'OK';
    } catch (Exception $e) {
        logMessage('Error enabling stereo audio: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Set channel with comprehensive anti-popping measures
 */
function setChannelWithoutPopping($deviceIp, $channel) {
    try {
        $supportsDsp = supportsDspControl($deviceIp);
        $currentVolume = getCurrentVolume($deviceIp);

        // Step 1: Set volume to zero
        if ($currentVolume !== null && $currentVolume > 0) {
            setVolume($deviceIp, 0);
            sleep(1);
        }

        // Step 2: Disable audio outputs
        if ($supportsDsp) {
            disableDspLineAudio($deviceIp);
            disableDspHdmiAudio($deviceIp);
        }
        disableHdmiAudio($deviceIp);
        disableStereoAudio($deviceIp);

        sleep(2);

        // Step 3: Change channel
        $channelChangeResult = setChannel($deviceIp, $channel);

        sleep(3);

        // Step 4: Re-enable audio
        enableStereoAudio($deviceIp);
        enableHdmiAudio($deviceIp);
        if ($supportsDsp) {
            enableDspHdmiAudio($deviceIp);
            enableDspLineAudio($deviceIp);
        }

        sleep(1);

        // Step 5: Restore volume
        if ($currentVolume !== null && $currentVolume > 0) {
            setVolume($deviceIp, $currentVolume);
        }

        return $channelChangeResult;
    } catch (Exception $e) {
        // Attempt to re-enable audio on error
        try {
            enableHdmiAudio($deviceIp);
            enableStereoAudio($deviceIp);
            if (isset($supportsDsp) && $supportsDsp) {
                enableDspHdmiAudio($deviceIp);
                enableDspLineAudio($deviceIp);
            }
            if (isset($currentVolume) && $currentVolume > 0) {
                setVolume($deviceIp, $currentVolume);
            }
        } catch (Exception $re) {
            logMessage("Error re-enabling audio: " . $re->getMessage(), 'error');
        }
        logMessage('Error setting channel with anti-popping: ' . $e->getMessage(), 'error');
        return false;
    }
}

// ============================================================================
// INPUT VALIDATION
// ============================================================================

/**
 * Sanitize user input
 *
 * @param mixed $data The data to sanitize
 * @param string $type The type of sanitization (int, ip, string)
 * @param array $options Additional options for validation
 * @return mixed Sanitized value or null on failure
 */
function sanitizeInput($data, $type, $options = []) {
    if ($data === null) {
        return null;
    }

    switch ($type) {
        case 'int':
            // Handle string "0" correctly
            if (is_numeric($data)) {
                $intVal = intval($data);
                $min = $options['min'] ?? PHP_INT_MIN;
                $max = $options['max'] ?? PHP_INT_MAX;
                if ($intVal >= $min && $intVal <= $max) {
                    return $intVal;
                }
            }
            return null;
        case 'ip':
            $sanitized = filter_var($data, FILTER_VALIDATE_IP);
            return $sanitized !== false ? $sanitized : null;
        case 'string':
            // FILTER_SANITIZE_STRING is deprecated in PHP 8.1+
            // Use htmlspecialchars for safe output and strip_tags to remove HTML
            if (!is_string($data)) {
                return null;
            }
            return htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
        default:
            return null;
    }
}

// ============================================================================
// LOGGING
// ============================================================================

/**
 * Log messages to file
 */
function logMessage($message, $level = 'info') {
    if ($level === 'error' || strtolower(LOG_LEVEL) === $level) {
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        $logFile = defined('LOG_FILE') ? LOG_FILE : __DIR__ . '/av_controls.log';
        error_log($formattedMessage, 3, $logFile);
    }
}

// ============================================================================
// PAYLOAD LOADING
// ============================================================================

/**
 * Load IR command payloads from file
 */
function loadPayloads($filename) {
    $payloads = [];
    if (file_exists($filename)) {
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            foreach ($lines as $line) {
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $payloads[trim($parts[0])] = trim($parts[1]);
                }
            }
        }
    }
    return $payloads;
}
