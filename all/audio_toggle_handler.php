<?php
/**
 * Audio Toggle Handler with Volume Operation Debugging
 */

require_once __DIR__ . '/../shared/utils.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST requests allowed']);
    exit;
}

$audioZones = [
    'Bowling Bar Music' => '192.168.8.28',
    'Axe/Billiards Music' => '192.168.8.27',
    'Bowling Music' => '192.168.8.25',
    'Rink Music' => '192.168.8.15',
    'Facility Zone Pro' => '192.168.8.81'
];

$audioSources = [
    'rockbot' => 10,
    'wireless' => 8
];

$wirelessVolumeSettings = [
    'Bowling Bar Music' => 10,
    'Axe/Billiards Music' => 10,
    'Bowling Music' => 11,
    'Rink Music' => 5,
    'Facility Zone Pro' => 10
];

$volumeStorageFile = __DIR__ . '/saved_volumes.json';
$response = [
    'success' => false, 
    'message' => '', 
    'results' => [],
    'volume_debug' => []
];

try {
    $source = $_POST['source'] ?? null;
    if (!$source || !isset($audioSources[$source])) {
        throw new Exception('Invalid audio source specified');
    }
    
    $targetChannel = $audioSources[$source];
    $successCount = 0;
    $failureCount = 0;
    
    // Step 1: Save volumes if switching TO wireless
    if ($source === 'wireless') {
        $response['volume_debug'][] = "Attempting to save current volumes";
        $volumeLevels = [];
        
        foreach ($audioZones as $zoneName => $zoneIp) {
            $currentVolume = getCurrentVolume($zoneIp);
            if ($currentVolume !== null) {
                $volumeLevels[$zoneName] = $currentVolume;
                $response['volume_debug'][] = "Got volume for $zoneName: $currentVolume";
            } else {
                $response['volume_debug'][] = "Failed to get volume for $zoneName";
            }
        }
        
        if (!empty($volumeLevels)) {
            $volumeData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'volumes' => $volumeLevels
            ];
            
            $writeResult = file_put_contents($volumeStorageFile, json_encode($volumeData, JSON_PRETTY_PRINT));
            if ($writeResult !== false) {
                $response['volume_debug'][] = "Successfully saved volumes to file: " . json_encode($volumeLevels);
            } else {
                $response['volume_debug'][] = "Failed to write volumes to file";
            }
        } else {
            $response['volume_debug'][] = "No volumes collected to save";
        }
    }
    
    // Step 2: Change channels FIRST
    foreach ($audioZones as $zoneName => $zoneIp) {
        $result = setChannel($zoneIp, $targetChannel);
        
        if ($result) {
            $successCount++;
            $response['results'][$zoneName] = ['success' => true, 'message' => 'Channel switched'];
        } else {
            $failureCount++;
            $response['results'][$zoneName] = ['success' => false, 'message' => 'Channel switch failed'];
        }
        
        usleep(200000); // 200ms delay
    }
    
    // Step 3: Handle volumes AFTER channel changes
    if ($source === 'wireless') {
        $response['volume_debug'][] = "Setting wireless volumes after 1 second delay";
        sleep(1);
        
        foreach ($audioZones as $zoneName => $zoneIp) {
            if (isset($wirelessVolumeSettings[$zoneName])) {
                $targetVolume = $wirelessVolumeSettings[$zoneName];
                $volumeResult = setVolume($zoneIp, $targetVolume);
                
                if ($volumeResult) {
                    $response['volume_debug'][] = "Successfully set $zoneName volume to $targetVolume";
                } else {
                    $response['volume_debug'][] = "Failed to set $zoneName volume to $targetVolume";
                }
                
                usleep(100000);
            }
        }
        
    } else if ($source === 'rockbot') {
        $response['volume_debug'][] = "Attempting to restore saved volumes after 1 second delay";
        sleep(1);
        
        if (file_exists($volumeStorageFile)) {
            $response['volume_debug'][] = "Volume file exists, reading content";
            $content = file_get_contents($volumeStorageFile);
            
            if ($content !== false) {
                $response['volume_debug'][] = "File content read successfully";
                $data = json_decode($content, true);
                
                if ($data && isset($data['volumes'])) {
                    $savedVolumes = $data['volumes'];
                    $response['volume_debug'][] = "Found saved volumes: " . json_encode($savedVolumes);
                    
                    foreach ($audioZones as $zoneName => $zoneIp) {
                        if (isset($savedVolumes[$zoneName])) {
                            $targetVolume = $savedVolumes[$zoneName];
                            $volumeResult = setVolume($zoneIp, $targetVolume);
                            
                            if ($volumeResult) {
                                $response['volume_debug'][] = "Successfully restored $zoneName volume to $targetVolume";
                            } else {
                                $response['volume_debug'][] = "Failed to restore $zoneName volume to $targetVolume";
                            }
                            
                            usleep(100000);
                        } else {
                            $response['volume_debug'][] = "No saved volume found for $zoneName";
                        }
                    }
                } else {
                    $response['volume_debug'][] = "Invalid JSON format in volume file";
                }
            } else {
                $response['volume_debug'][] = "Failed to read volume file content";
            }
        } else {
            $response['volume_debug'][] = "Volume file does not exist: $volumeStorageFile";
        }
    }
    
    // Determine success
    if ($failureCount === 0) {
        $response['success'] = true;
        $sourceLabel = ($source === 'rockbot' ? 'RockBot Audio' : 'Wireless Mic');
        $response['message'] = "Successfully switched all zones to $sourceLabel";
    } else if ($successCount > 0) {
        $response['success'] = true;
        $response['message'] = "$successCount zones succeeded, $failureCount zones failed";
    } else {
        $response['message'] = "All zones failed to switch";
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Error: ' . $e->getMessage();
    $response['volume_debug'][] = "Exception: " . $e->getMessage();
}

echo json_encode($response);
exit;
?>
