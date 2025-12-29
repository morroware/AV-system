<?php
/**
 * Zones API Endpoint
 *
 * Returns zone and navigation data for dynamic loading.
 * Used by the main index page and other dynamic interfaces.
 *
 * @author Seth Morrow
 * @version 1.0
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once dirname(__DIR__) . '/shared/zones.php';

$config = loadZonesConfig();

$response = [
    'zones' => getNavigationZones(),
    'specialLinks' => getSpecialLinks(),
    'settings' => $config['settings'] ?? []
];

echo json_encode($response);
