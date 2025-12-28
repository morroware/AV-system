<?php
/**
 * Combined AV Controls and Remote Control Interface
 *
 * This file serves as the main entry point for the AV Control System. It handles
 * both the initial page rendering and the AJAX requests for device control.
 * 
 * The system allows users to:
 * 1. Control multiple audio/video receivers (TVs, audio zones)
 * 2. Change input sources (channels)
 * 3. Adjust volume levels
 * 4. Send power commands
 * 5. Send remote control commands to transmitter devices
 * 
 * AJAX requests are handled via POST with the X-Requested-With header, allowing
 * the same file to serve both the UI and API functionality.
 * 
 * @author Seth Morrow
 * @version 2.0
 * @date 2024-11-03
 */

// Enable comprehensive error reporting for debugging purposes
// This helps identify issues during development and troubleshooting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Start output buffering to capture all output before sending to browser
// This allows for modifying headers after content has been generated
// and helps prevent "headers already sent" errors
ob_start();

// Include required configuration and utility functions
// config.php contains all system settings (receivers, transmitters, etc.)
// utils.php contains helper functions for API communication and UI generation
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

/**
 * AJAX Request Handler
 * 
 * This section processes AJAX requests from the frontend JavaScript.
 * It detects AJAX requests by checking for the X-Requested-With header
 * and responds with JSON-formatted data.
 * 
 * The handler supports several types of requests:
 * - Receiver control (channel selection, volume adjustment)
 * - Power commands
 * - Remote control commands
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    // Initialize response array with default values
    // This will be converted to JSON before sending back to the client
    $response = array('success' => false, 'message' => '');
    
    // Handle receiver control requests
    if (isset($_POST['receiver_ip'])) {
        // Sanitize the IP address to prevent security issues
        $deviceIp = sanitizeInput($_POST['receiver_ip'], 'ip');

        // Find the receiver configuration for this IP address
        // This allows us to apply device-specific settings and permissions
        $receiverConfig = null;
        foreach (RECEIVERS as $name => $config) {
            if ($config['ip'] === $deviceIp) {
                $receiverConfig = $config;
                break;
            }
        }

        // Process power commands (on/off)
        if (isset($_POST['power_command'])) {
            // Handle power command only if show_power is true for this receiver
            if ($receiverConfig && $receiverConfig['show_power']) {
                $powerCommand = sanitizeInput($_POST['power_command'], 'string');
                try {
                    // Send the power command to the device via its API
                    $commandResponse = makeApiCall('POST', $deviceIp, 'command/cli', $powerCommand, 'text/plain');
                    $responseData = json_decode($commandResponse, true);
                    
                    // Check if the command was successful based on API response
                    if (isset($responseData['data']) && $responseData['data'] === 'OK') {
                        $response['success'] = true;
                        $response['message'] = "Power command sent successfully.";
                    } else {
                        $response['message'] = "Error sending power command: Unexpected response.";
                    }
                } catch (Exception $e) {
                    // Log and return any errors that occur during API communication
                    $response['message'] = "Error sending power command: " . $e->getMessage();
                    logMessage("Error sending power command: " . $e->getMessage(), 'error');
                }
            } else {
                // If power control is disabled for this device
                $response['message'] = "Power control not enabled for this receiver.";
            }
        } 
        // Handle volume-only updates (when only volume is being changed)
        else if (isset($_POST['volume']) && !isset($_POST['channel'])) {
            // First check if the device supports volume control
            if (supportsVolumeControl($deviceIp)) {
                // Sanitize and validate the volume value within allowed range
                $selectedVolume = sanitizeInput($_POST['volume'], 'int', ['min' => MIN_VOLUME, 'max' => MAX_VOLUME]);
                
                if ($selectedVolume) {
                    try {
                        // Send volume update command to the device
                        $volumeResponse = setVolume($deviceIp, $selectedVolume);
                        $response['success'] = $volumeResponse;
                        $response['message'] = "Volume: " . ($volumeResponse ? "Successfully updated" : "Update failed");
                        logMessage("Volume updated for $deviceIp to $selectedVolume - Result: " . ($volumeResponse ? "Success" : "Failed"), 'info');
                    } catch (Exception $e) {
                        // Handle and log any errors
                        $response['message'] = "Error updating volume: " . $e->getMessage();
                        logMessage("Error updating volume: " . $e->getMessage(), 'error');
                    }
                }
            } else {
                // If device doesn't support volume control
                $response['message'] = "Device does not support volume control";
                logMessage("Volume control not supported for IP: $deviceIp", 'info');
            }
        }
        // Handle channel-only updates (when only the input source is being changed)
        else if (isset($_POST['channel']) && !isset($_POST['volume'])) {
            // Sanitize and validate the channel value
            $selectedChannel = sanitizeInput($_POST['channel'], 'int');
            
            if ($selectedChannel) {
                try {
                    // Send channel update command to the device
                    $channelResponse = setChannel($deviceIp, $selectedChannel);
                    $response['success'] = $channelResponse;
                    $response['message'] = "Channel: " . ($channelResponse ? "Successfully updated" : "Update failed");
                    logMessage("Channel updated for $deviceIp to $selectedChannel - Result: " . ($channelResponse ? "Success" : "Failed"), 'info');
                } catch (Exception $e) {
                    // Handle and log any errors
                    $response['message'] = "Error updating channel: " . $e->getMessage();
                    logMessage("Error updating channel: " . $e->getMessage(), 'error');
                }
            }
        }
        // Handle combined channel and volume updates (legacy support)
        else {
            // Process both channel and volume in a single request
            // This is kept for backward compatibility with older UI versions
            $selectedChannel = sanitizeInput($_POST['channel'], 'int');

            if ($selectedChannel && $deviceIp) {
                try {
                    // First update the channel
                    $channelResponse = setChannel($deviceIp, $selectedChannel);
                    $response['message'] .= "Channel: " . ($channelResponse ? "Successfully updated" : "Update failed") . "\n";

                    // Then update volume if the device supports it
                    if (supportsVolumeControl($deviceIp)) {
                        $selectedVolume = sanitizeInput($_POST['volume'], 'int', ['min' => MIN_VOLUME, 'max' => MAX_VOLUME]);
                        if ($selectedVolume) {
                            $volumeResponse = setVolume($deviceIp, $selectedVolume);
                            $response['message'] .= "Volume: " . ($volumeResponse ? "Successfully updated" : "Update failed") . "\n";
                        }
                    }

                    $response['success'] = true;
                } catch (Exception $e) {
                    // Handle and log any errors from either operation
                    $response['message'] = "Error updating settings: " . $e->getMessage();
                    logMessage("Error updating settings: " . $e->getMessage(), 'error');
                }
            }
        }
    } 
    // Handle remote control requests
    else if (isset($_POST['device_url'])) {
        // Process remote control commands for transmitter devices
        $deviceUrl = rtrim($_POST['device_url'], '/');
        $action = $_POST['action'];
        
        // Load IR command payloads from the configuration file
        $payloads = loadPayloads('payloads.txt');
        
        // Check if the requested action exists in our payload list
        if (isset($payloads[$action])) {
            try {
                // Construct the API endpoint and command payload
                $url = $deviceUrl . "/cgi-bin/api/command/cli";
                $payload = 'echo "' . $payloads[$action] . '" | ./fluxhandlerV2.sh';
                
                // Send the command to the device
                $result = makeApiCall('POST', $deviceUrl, 'command/cli', $payload, 'text/plain');
                $response['success'] = true;
                $response['message'] = "Command sent successfully";
            } catch (Exception $e) {
                // Handle and log any errors
                $response['message'] = "Error sending command: " . $e->getMessage();
                logMessage("Error sending remote command: " . $e->getMessage(), 'error');
            }
        } else {
            // If the action doesn't exist in our payload configuration
            $response['message'] = "Invalid action: " . htmlspecialchars($action);
        }
    }

    // Send JSON response and end script execution
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

/**
 * Initial Page Load Preparation
 * 
 * This section handles the initial page load (non-AJAX requests).
 * It performs pre-rendering tasks like checking device connectivity
 * before including the main template file.
 */

// Check if any receivers are reachable before rendering the page
// This helps provide a better user experience by showing a global error
// message if the entire AV network is unreachable
$allReceiversUnreachable = true;
foreach (RECEIVERS as $receiverName => $receiverConfig) {
    try {
        // Attempt to get the current channel from each receiver
        // If at least one succeeds, the network is functioning
        getCurrentChannel($receiverConfig['ip']);
        $allReceiversUnreachable = false;
        break;
    } catch (Exception $e) {
        // Continue checking other receivers if this one fails
        continue;
    }
}

// Include the main template file which contains the HTML structure
// The template has access to the $allReceiversUnreachable variable
// and can display appropriate messages based on network status
include __DIR__ . '/template.php';

// End output buffering and send all captured output to the browser
ob_end_flush();	
