<?php
/**
 * TEST 3: Operations Data Test
 * Tests retrieving current flight operations from the dispatcher
 */

require_once 'config.php';

echo "<h1>Test 3: Operations Data Test</h1>\n";

// Get current date/time for query
$now = new DateTime('now', new DateTimeZone('America/Chicago'));
$start = clone $now;
$start->sub(new DateInterval('PT2H')); // 2 hours ago
$stop = clone $now;
$stop->add(new DateInterval('PT6H')); // 6 hours from now

$startDateTime = $start->format('d M Y H:i');
$stopDateTime = $stop->format('d M Y H:i');

echo "<p>Querying operations from <strong>$startDateTime</strong> to <strong>$stopDateTime</strong></p>\n";

// Build URL with parameters for operations query
$params = [
    'customercode' => CUSTOMER_CODE,
    'accesscode' => ACCESS_CODE,
    'username' => USERNAME,
    'operation' => 'export',
    'opstype' => 'operation',
    'location' => LOCATION,
    'startdatetime' => $startDateTime,
    'stopdatetime' => $stopDateTime
];

$url = API_URL . '?' . http_build_query($params);

echo "<h2>Request Parameters:</h2>\n";
echo "<pre>" . print_r($params, true) . "</pre>\n";

echo "<h2>Making API Request...</h2>\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

if ($error) {
    echo "<p style='color: red;'>❌ Request Failed: $error</p>\n";
    curl_close($ch);
    exit;
}

echo "<p style='color: green;'>✓ Request Completed</p>\n";
echo "<p>HTTP Status Code: $httpCode</p>\n";

curl_close($ch);

// Parse response
if (!$response) {
    echo "<p style='color: red;'>❌ Empty response</p>\n";
    exit;
}

// Check if it's a SOAP envelope
if (strpos($response, 'soap:Envelope') !== false || strpos($response, 'soap:Body') !== false) {
    echo "<h3>Detected SOAP Envelope - Extracting inner XML...</h3>\n";
    
    // Extract content from SOAP body
    $response = preg_replace('/<\?xml[^>]*>/', '', $response);
    $soapXml = simplexml_load_string($response);
    
    if ($soapXml) {
        $soapXml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $body = $soapXml->xpath('//soap:Body');
        
        if (!empty($body)) {
            $innerXml = $body[0]->children()->asXML();
            $xml = simplexml_load_string($innerXml);
            echo "<p style='color: green;'>✓ Successfully extracted data from SOAP envelope</p>\n";
        } else {
            echo "<p style='color: orange;'>⚠ Could not find SOAP Body</p>\n";
            $xml = $soapXml;
        }
    }
} else {
    $xml = @simplexml_load_string($response);
}

if ($xml === false) {
    echo "<h3 style='color: red;'>❌ Could not parse XML</h3>\n";
    echo "<pre>" . htmlspecialchars(substr($response, 0, 2000)) . "...</pre>\n";
    exit;
}

echo "<h3 style='color: green;'>✓ Successfully Parsed XML</h3>\n";

// Check for operations
if (isset($xml->OPERATIONS->DISPATCH)) {
    $operations = $xml->OPERATIONS->DISPATCH;
    $count = count($operations);
    
    echo "<h2 style='color: green;'>✓ Found $count Operations</h2>\n";
    
    // Display summary of operations
    echo "<h3>Operations Summary:</h3>\n";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>\n";
    echo "<tr style='background: #ddd;'><th>Aircraft</th><th>Status</th><th>Start</th><th>Student</th><th>Instructor</th></tr>\n";
    
    $displayCount = 0;
    foreach ($operations as $op) {
        if ($displayCount++ >= 10) {
            echo "<tr><td colspan='5'><em>... showing first 10 of $count operations</em></td></tr>\n";
            break;
        }
        
        $aircraft = (string)$op->RESOURCE_DISPLAY_NAME;
        $status = (string)$op->STATUS;
        $start = (string)$op->ACTIVITY_START;
        $student = (string)$op->STUDENT1;
        $instructor = (string)$op->INSTRUCTOR;
        
        echo "<tr>";
        echo "<td>$aircraft</td>";
        echo "<td>$status</td>";
        echo "<td>$start</td>";
        echo "<td>$student</td>";
        echo "<td>$instructor</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
} else {
    echo "<h3 style='color: orange;'>⚠ No operations found in response</h3>\n";
    echo "<p>This might mean there are no active operations in the time window.</p>\n";
    echo "<h4>Response Structure:</h4>\n";
    echo "<pre>" . htmlspecialchars(substr($xml->asXML(), 0, 1000)) . "...</pre>\n";
}

echo "\n<hr>\n";
echo "<p><strong>Next Step:</strong> If this test passes, you can build the dashboard!</p>\n";
?>
