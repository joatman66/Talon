<?php
/**
 * Talon API Functions Library
 * Reusable functions for interacting with the Talon ETA API
 */

require_once 'config.php';

/**
 * Make a request to the Talon API
 * 
 * @param string $opstype The operation type (e.g., 'aircraft', 'operation', 'instructor')
 * @param array $params Additional parameters for the query
 * @return SimpleXMLElement|false The parsed XML response or false on error
 */
function callTalonAPI($opstype, $params = []) {
    // Build base parameters
    $apiParams = [
        'customercode' => 749A55FB62C55F87174E085ED4D4E9CC,
        'accesscode' => flightline,
        'username' => asavka,
        'operation' => 'export',
        'opstype' => $opstype
    ];
    
    // Merge with additional parameters
    $apiParams = array_merge($apiParams, $params);
    
    // Build URL
    $url = API_URL . '?' . http_build_query($apiParams);
    
    // Make request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Talon API Error: $error");
        return false;
    }
    
    if (!$response) {
        error_log("Talon API: Empty response");
        return false;
    }
    
    // Parse SOAP envelope if present
    $xml = parseSoapResponse($response);
    
    return $xml;
}

/**
 * Parse a SOAP response and extract the inner XML
 * 
 * @param string $response The raw response string
 * @return SimpleXMLElement|false
 */
function parseSoapResponse($response) {
    // Check if it's a SOAP envelope
    if (strpos($response, 'soap:Envelope') !== false || strpos($response, 'soap:Body') !== false) {
        $response = preg_replace('/<\?xml[^>]*>/', '', $response);
        $soapXml = @simplexml_load_string($response);
        
        if ($soapXml) {
            $soapXml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $soapXml->xpath('//soap:Body');
            
            if (!empty($body)) {
                $innerXml = $body[0]->children()->asXML();
                return @simplexml_load_string($innerXml);
            }
        }
    }
    
    // Try to parse as regular XML
    return @simplexml_load_string($response);
}

/**
 * Get active aircraft
 * 
 * @return array Array of aircraft data
 */
function getActiveAircraft() {
    global $EXCLUDED_AIRCRAFT;
    
    $xml = callTalonAPI('aircraft', [
        'aircraftstatus' => 'active',
        'location' => LOCATION
    ]);
    
    if (!$xml || !isset($xml->RESOURCES->AIRCRAFT)) {
        return [];
    }
    
    $aircraft = [];
    foreach ($xml->RESOURCES->AIRCRAFT as $ac) {
        $tailNumber = (string)$ac->AIRCRAFT;
        
        // Skip excluded aircraft
        if (in_array($tailNumber, $EXCLUDED_AIRCRAFT)) {
            continue;
        }
        
        $aircraft[] = [
            'id' => (string)$ac->AIRCRAFT_ID,
            'tail_number' => $tailNumber,
            'type' => (string)$ac->RESOURCE_TYPE,
            'description' => (string)$ac->DESCRIPTION
        ];
    }
    
    return $aircraft;
}

/**
 * Get current operations for a time window
 * 
 * @param DateTime $start Start time
 * @param DateTime $stop Stop time
 * @return array Array of operations data
 */
function getCurrentOperations($start, $stop) {
    global $INCLUDED_ACTIVITY_TYPES;
    
    $params = [
        'location' => LOCATION,
        'startdatetime' => $start->format('d M Y H:i'),
        'stopdatetime' => $stop->format('d M Y H:i')
    ];
    
    $xml = callTalonAPI('operation', $params);
    
    if (!$xml || !isset($xml->OPERATIONS->DISPATCH)) {
        return [];
    }
    
    $operations = [];
    foreach ($xml->OPERATIONS->DISPATCH as $op) {
        $activityType = (string)$op->ACTIVITY_TYPE;
        
        // Filter by activity type
        if (!in_array($activityType, $INCLUDED_ACTIVITY_TYPES)) {
            continue;
        }
        
        $operations[] = [
            'id' => (string)$op->ACTIVITY_ID,
            'aircraft' => (string)$op->RESOURCE_DISPLAY_NAME,
            'status' => (string)$op->STATUS,
            'activity_type' => $activityType,
            'start_time' => (string)$op->ACTIVITY_START,
            'due_back' => (string)$op->ACTIVITY_STOP,
            'student1' => (string)$op->STUDENT1,
            'student2' => (string)$op->STUDENT2,
            'instructor' => (string)$op->INSTRUCTOR,
            'pic' => (string)$op->PIC_USERNAME
        ];
    }
    
    return $operations;
}

/**
 * Get active instructors
 * 
 * @return array Array of instructor data
 */
function getActiveInstructors() {
    $xml = callTalonAPI('instructor', [
        'location' => LOCATION,
        'instructorstatus' => 'active'
    ]);
    
    if (!$xml || !isset($xml->PERSONNEL->INSTRUCTOR)) {
        return [];
    }
    
    $instructors = [];
    foreach ($xml->PERSONNEL->INSTRUCTOR as $inst) {
        $instructors[] = [
            'id' => (string)$inst->PERSONNEL_ID,
            'name' => trim((string)$inst->FIRST_NAME . ' ' . (string)$inst->LAST_NAME),
            'eta_id' => (string)$inst->ETA_ID,
            'team' => (string)$inst->TEAM
        ];
    }
    
    return $instructors;
}

/**
 * Get aircraft status summary
 * Groups operations by aircraft to show current status
 * 
 * @return array Aircraft status summary
 */
function getAircraftStatus() {
    // Get time window: 1 hour ago to 8 hours from now
    $now = new DateTime('now', new DateTimeZone('America/Chicago'));
    $start = clone $now;
    $start->sub(new DateInterval('PT1H'));
    $stop = clone $now;
    $stop->add(new DateInterval('PT8H'));
    
    $aircraft = getActiveAircraft();
    $operations = getCurrentOperations($start, $stop);
    
    // Build status for each aircraft
    $status = [];
    
    foreach ($aircraft as $ac) {
        $tailNumber = $ac['tail_number'];
        
        // Find current operation for this aircraft
        $currentOp = null;
        foreach ($operations as $op) {
            if ($op['aircraft'] === $tailNumber) {
                // Check if operation is current (within time window)
                $opStart = new DateTime($op['start_time']);
                $opStop = new DateTime($op['due_back']);
                
                if ($now >= $opStart && $now <= $opStop) {
                    $currentOp = $op;
                    break;
                }
            }
        }
        
        $status[] = [
            'aircraft' => $ac,
            'current_operation' => $currentOp,
            'is_available' => ($currentOp === null)
        ];
    }
    
    return $status;
}
?>
