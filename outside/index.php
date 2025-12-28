<?php
/**
 * Combined AV Controls and Remote Control Interface
 * 
 * @author Seth Morrow
 * @version 2.0
 * @date 2024-11-03
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

ob_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    $response = array('success' => false, 'message' => '');
    
    if (isset($_POST['receiver_ip'])) {
        // Handle receiver control requests
        $deviceIp = sanitizeInput($_POST['receiver_ip'], 'ip');

        // Find the receiver configuration for this IP
        $receiverConfig = null;
        foreach (RECEIVERS as $name => $config) {
            if ($config['ip'] === $deviceIp) {
                $receiverConfig = $config;
                break;
            }
        }

        if (isset($_POST['power_command'])) {
            // Handle power command only if show_power is true for this receiver
            if ($receiverConfig && $receiverConfig['show_power']) {
                $powerCommand = sanitizeInput($_POST['power_command'], 'string');
                try {
                    $commandResponse = makeApiCall('POST', $deviceIp, 'command/cli', $powerCommand, 'text/plain');
                    $responseData = json_decode($commandResponse, true);
                    if (isset($responseData['data']) && $responseData['data'] === 'OK') {
                        $response['success'] = true;
                        $response['message'] = "Power command sent successfully.";
                    } else {
                        $response['message'] = "Error sending power command: Unexpected response.";
                    }
                } catch (Exception $e) {
                    $response['message'] = "Error sending power command: " . $e->getMessage();
                    logMessage("Error sending power command: " . $e->getMessage(), 'error');
                }
            } else {
                $response['message'] = "Power control not enabled for this receiver.";
            }
        } 
        // NEW: Handle volume-only updates
        else if (isset($_POST['volume']) && !isset($_POST['channel'])) {
            if (supportsVolumeControl($deviceIp)) {
                $selectedVolume = sanitizeInput($_POST['volume'], 'int', ['min' => MIN_VOLUME, 'max' => MAX_VOLUME]);
                if ($selectedVolume) {
                    try {
                        $volumeResponse = setVolume($deviceIp, $selectedVolume);
                        $response['success'] = $volumeResponse;
                        $response['message'] = "Volume: " . ($volumeResponse ? "Successfully updated" : "Update failed");
                        logMessage("Volume updated for $deviceIp to $selectedVolume - Result: " . ($volumeResponse ? "Success" : "Failed"), 'info');
                    } catch (Exception $e) {
                        $response['message'] = "Error updating volume: " . $e->getMessage();
                        logMessage("Error updating volume: " . $e->getMessage(), 'error');
                    }
                }
            } else {
                $response['message'] = "Device does not support volume control";
                logMessage("Volume control not supported for IP: $deviceIp", 'info');
            }
        }
        // NEW: Handle channel-only updates
        else if (isset($_POST['channel']) && !isset($_POST['volume'])) {
            $selectedChannel = sanitizeInput($_POST['channel'], 'int');
            if ($selectedChannel) {
                try {
                    $channelResponse = setChannel($deviceIp, $selectedChannel);
                    $response['success'] = $channelResponse;
                    $response['message'] = "Channel: " . ($channelResponse ? "Successfully updated" : "Update failed");
                    logMessage("Channel updated for $deviceIp to $selectedChannel - Result: " . ($channelResponse ? "Success" : "Failed"), 'info');
                } catch (Exception $e) {
                    $response['message'] = "Error updating channel: " . $e->getMessage();
                    logMessage("Error updating channel: " . $e->getMessage(), 'error');
                }
            }
        }
        // Keep the original combined handler for backward compatibility
        else {
            // Handle channel and volume update
            $selectedChannel = sanitizeInput($_POST['channel'], 'int');

            if ($selectedChannel && $deviceIp) {
                try {
                    $channelResponse = setChannel($deviceIp, $selectedChannel);
                    $response['message'] .= "Channel: " . ($channelResponse ? "Successfully updated" : "Update failed") . "\n";

                    if (supportsVolumeControl($deviceIp)) {
                        $selectedVolume = sanitizeInput($_POST['volume'], 'int', ['min' => MIN_VOLUME, 'max' => MAX_VOLUME]);
                        if ($selectedVolume) {
                            $volumeResponse = setVolume($deviceIp, $selectedVolume);
                            $response['message'] .= "Volume: " . ($volumeResponse ? "Successfully updated" : "Update failed") . "\n";
                        }
                    }

                    $response['success'] = true;
                } catch (Exception $e) {
                    $response['message'] = "Error updating settings: " . $e->getMessage();
                    logMessage("Error updating settings: " . $e->getMessage(), 'error');
                }
            }
        }
    } else if (isset($_POST['device_url'])) {
        // Handle remote control requests
        $deviceUrl = rtrim($_POST['device_url'], '/');
        $action = $_POST['action'];
        
        // Load payloads from the text file
        $payloads = loadPayloads('payloads.txt');
        
        if (isset($payloads[$action])) {
            try {
                $url = $deviceUrl . "/cgi-bin/api/command/cli";
                $payload = 'echo "' . $payloads[$action] . '" | ./fluxhandlerV2.sh';
                
                $result = makeApiCall('POST', $deviceUrl, 'command/cli', $payload, 'text/plain');
                $response['success'] = true;
                $response['message'] = "Command sent successfully";
            } catch (Exception $e) {
                $response['message'] = "Error sending command: " . $e->getMessage();
                logMessage("Error sending remote command: " . $e->getMessage(), 'error');
            }
        } else {
            $response['message'] = "Invalid action: " . htmlspecialchars($action);
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Check if any receivers are reachable before rendering the page
$allReceiversUnreachable = true;
foreach (RECEIVERS as $receiverName => $receiverConfig) {
    try {
        getCurrentChannel($receiverConfig['ip']);
        $allReceiversUnreachable = false;
        break;
    } catch (Exception $e) {
        continue;
    }
}

// Include the main template
include __DIR__ . '/template.php';

ob_end_flush();
