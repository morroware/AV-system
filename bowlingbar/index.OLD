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
        foreach (RECEIVERS as $config) {
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
        // Handle channel changes
        else if (isset($_POST['channel'])) {
            $selectedChannel = sanitizeInput($_POST['channel'], 'int');
            if ($selectedChannel && $deviceIp) {
                try {
                    // Get current channel and only change if different
                    $currentChannel = getCurrentChannel($deviceIp);
                    if ($currentChannel !== $selectedChannel) {
                        $channelResponse = setChannel($deviceIp, $selectedChannel);
                        $response['success'] = $channelResponse;
                        $response['message'] = $channelResponse ? "Channel successfully updated" : "Channel update failed";
                    } else {
                        $response['success'] = true;
                        $response['message'] = "Channel unchanged";
                    }
                } catch (Exception $e) {
                    $response['message'] = "Error updating channel: " . $e->getMessage();
                    logMessage("Error updating channel: " . $e->getMessage(), 'error');
                }
            }
        }
        // Handle volume changes
        else if (isset($_POST['volume']) && supportsVolumeControl($deviceIp)) {
            $selectedVolume = sanitizeInput($_POST['volume'], 'int', ['min' => MIN_VOLUME, 'max' => MAX_VOLUME]);
            if ($selectedVolume) {
                try {
                    // Get current volume and only change if different
                    $currentVolume = getCurrentVolume($deviceIp);
                    if ($currentVolume !== $selectedVolume) {
                        $volumeResponse = setVolume($deviceIp, $selectedVolume);
                        $response['success'] = $volumeResponse;
                        $response['message'] = $volumeResponse ? "Volume successfully updated" : "Volume update failed";
                    } else {
                        $response['success'] = true;
                        $response['message'] = "Volume unchanged";
                    }
                } catch (Exception $e) {
                    $response['message'] = "Error updating volume: " . $e->getMessage();
                    logMessage("Error updating volume: " . $e->getMessage(), 'error');
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
?>

<!-- WLED Power Buttons Section -->
<!-- Replace the current footer in index.php with this improved version -->

<footer>
    <div id="wled-footer-controls">
        <button id="led-on-footer" class="button power-on">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                <path d="M15 5h2a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-2M5 5h10M5 19h10M5 5v14"></path>
                <path d="M10 9v6"></path>
            </svg>
            LEDs ON
        </button>
        <button id="led-off-footer" class="button power-off">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                <path d="M18 6 6 18"></path>
                <path d="m6 6 12 12"></path>
            </svg>
            LEDs OFF
        </button>
    </div>
</footer>

<script>
    // Event listeners for the footer WLED buttons
    document.getElementById('led-on-footer').addEventListener('click', function () {
        this.classList.add('clicked');
        fetch('wled.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=on',
        })
            .then(response => response.json())
            .then(data => {
                console.log("LEDs ON command sent.");
                showResponseMessage("LEDs ON command sent successfully", true);
                setTimeout(() => this.classList.remove('clicked'), 300);
            })
            .catch(error => {
                console.error('Error sending LEDs ON command:', error);
                showResponseMessage("Error sending LEDs ON command", false);
                setTimeout(() => this.classList.remove('clicked'), 300);
            });
    });

    document.getElementById('led-off-footer').addEventListener('click', function () {
        this.classList.add('clicked');
        fetch('wled.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=off',
        })
            .then(response => response.json())
            .then(data => {
                console.log("LEDs OFF command sent.");
                showResponseMessage("LEDs OFF command sent successfully", true);
                setTimeout(() => this.classList.remove('clicked'), 300);
            })
            .catch(error => {
                console.error('Error sending LEDs OFF command:', error);
                showResponseMessage("Error sending LEDs OFF command", false);
                setTimeout(() => this.classList.remove('clicked'), 300);
            });
    });
</script>
