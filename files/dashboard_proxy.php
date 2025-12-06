<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ============================================
// CONFIGURATION - UPDATE THESE VALUES
// ============================================
$API_URL = 'https://yourdomain.com/etaws/apipost.php';  // Your Talon API URL
$CUSTOMER_CODE = '749A55FB62C55F87174E085ED4D4E9CC';
$ACCESS_CODE = 'flightline';
$USERNAME = 'asavka';
$LOCATION = 'SIU Aviation';  // e.g., "Dallas"

// ============================================
// FUNCTION: Fetch from Talon API
// ============================================
function fetch_talon_data($params) {
    global $API_URL, $CUSTOMER_CODE, $ACCESS_CODE, $USERNAME;
    
    // Add credentials to parameters
    $params['customercode'] = $CUSTOMER_CODE;
    $params['accesscode'] = $ACCESS_CODE;
    $params['username'] = $USERNAME;
    
    // Build URL with GET parameters
    $url = $API_URL . '?' . http_build_query($params);
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        return ['error' => 'cURL Error: ' . $curl_error];
    }
    
    if ($http_code !== 200) {
        return ['error' => 'HTTP Error: ' . $http_code];
    }
    
    if (empty($response)) {
        return ['error' => 'Empty response from API'];
    }
    
    // Parse XML response
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($response);
    
    if ($xml === false) {
        $errors = libxml_get_errors();
        return ['error' => 'XML Parse Error: ' . print_r($errors, true)];
    }
    
    return $xml;
}

// ============================================
// FETCH AIRCRAFT DATA
// ============================================
$aircraft_params = [
    'opstype' => 'aircraft',
    'operation' => 'export',
    'aircraftstatus' => 'Active'  // Only get active aircraft
];

$aircraft_xml = fetch_talon_data($aircraft_params);

if (isset($aircraft_xml['error'])) {
    echo json_encode(['error' => $aircraft_xml['error']]);
    exit;
}

// Process aircraft data
$aircraft_list = [];
if (isset($aircraft_xml->AIRCRAFT)) {
    foreach ($aircraft_xml->AIRCRAFT as $ac) {
        $aircraft_list[] = [
            'id' => (string)$ac->AIRCRAFT_ID,
            'name' => (string)$ac->AIRCRAFT,
            'type' => (string)$ac->RESOURCE_TYPE,
            'n_number' => (string)$ac->NNUMBER,
            'description' => (string)$ac->DESCRIPTION,
            'tach' => (string)$ac->TACH1,
            'hobbs' => (string)$ac->HOBBS,
            'obsolete' => (string)$ac->OBSOLETE
        ];
    }
}

// ============================================
// FETCH TODAY'S OPERATIONS DATA
// ============================================
$today = date('d M Y');
$ops_params = [
    'location' => $LOCATION,
    'opstype' => 'operation',
    'operation' => 'export',
    'startdatetime' => $today . ' 0000',
    'stopdatetime' => $today . ' 2359',
    'activitytype' => 'Flight'
];

$ops_xml = fetch_talon_data($ops_params);

// Process operations data
$operations_list = [];
if (!isset($ops_xml['error']) && isset($ops_xml->OPERATION)) {
    foreach ($ops_xml->OPERATION as $op) {
        // Only include non-cancelled operations
        $status = strtoupper((string)$op->ACTIVITY_STATUS);
        if ($status !== 'CANCELLED') {
            $operations_list[] = [
                'id' => (string)$op->ACTIVITY_ID,
                'aircraft' => (string)$op->RES,
                'instructor' => (string)$op->IP,
                'student' => (string)$op->PERSON_NAME,
                'start_time' => (string)$op->ACT_START,
                'due_back' => (string)$op->ACT_DUE_BACK,
                'status' => (string)$op->ACTIVITY_STATUS,
                'activity_type' => (string)$op->ACTIVITY_TYPE
            ];
        }
    }
}

// ============================================
// CALCULATE SUMMARY
// ============================================
$total_aircraft = count($aircraft_list);
$in_use_aircraft = [];

foreach ($operations_list as $op) {
    if (!in_array($op['aircraft'], $in_use_aircraft)) {
        $in_use_aircraft[] = $op['aircraft'];
    }
}

$in_use_count = count($in_use_aircraft);
$available_count = $total_aircraft - $in_use_count;

// ============================================
// RETURN JSON RESPONSE
// ============================================
$response = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'aircraft' => $aircraft_list,
    'operations' => $operations_list,
    'summary' => [
        'total' => $total_aircraft,
        'available' => $available_count,
        'in_use' => $in_use_count
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>
