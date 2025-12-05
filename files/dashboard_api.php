<?php
/**
 * Dashboard API Endpoint
 * Returns JSON data for the dashboard
 */

header('Content-Type: application/json');

require_once 'talon_api.php';

try {
    // Get aircraft status
    $aircraftStatus = getAircraftStatus();
    
    // Calculate statistics
    $totalAircraft = count($aircraftStatus);
    $availableAircraft = 0;
    $inUseAircraft = 0;
    
    foreach ($aircraftStatus as $status) {
        if ($status['is_available']) {
            $availableAircraft++;
        } else {
            $inUseAircraft++;
        }
    }
    
    // Build response
    $response = [
        'success' => true,
        'stats' => [
            'total' => $totalAircraft,
            'available' => $availableAircraft,
            'in_use' => $inUseAircraft
        ],
        'aircraft' => $aircraftStatus,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
