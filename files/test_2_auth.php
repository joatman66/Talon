<?php
/**
 * TEST 2: Authentication Test
 * Tests if our credentials are accepted by the API
 * Uses the Aircraft extraction service as a simple test
 */

require_once 'config.php';

echo "<h1>Test 2: Authentication Test</h1>\n";

// Build the XML request for aircraft data
$xml = <<<XML
<etaws>
    <operation opstype="aircraft">export</operation>
    <parameters>
        <customercode>{CUSTOMER_CODE}</customercode>
        <accesscode>{ACCESS_CODE}</accesscode>
        <username>{USERNAME}</username>
        <aircraftstatus>active</aircraftstatus>
    </parameters>
</etaws>
XML;

echo "<h2>Request XML:</h2>\n";
echo "<pre>" . htmlspecialchars($xml) . "</pre>\n";

// Build URL with parameters
$url = API_URL . '?' . http_build_query([
    'customercode' => CUSTOMER_CODE,
    'accesscode' => ACCESS_CODE,
    'username' => USERNAME,
    'operation' => 'export',
    'opstype' => 'aircraft',
    'aircraftstatus' => 'active'
]);

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
} else {
    echo "<p style='color: green;'>✓ Request Completed</p>\n";
    echo "<p>HTTP Status Code: $httpCode</p>\n";
    
    // Try to parse as XML
    if ($response) {
        $xml = @simplexml_load_string($response);
        
        if ($xml === false) {
            echo "<h3 style='color: orange;'>⚠ Response is not valid XML (might be SOAP envelope)</h3>\n";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "...</pre>\n";
        } else {
            echo "<h3 style='color: green;'>✓ Valid XML Response</h3>\n";
            
            // Check for authentication errors
            if (isset($xml->error)) {
                echo "<p style='color: red;'>❌ API Error: " . $xml->error . "</p>\n";
            } else {
                echo "<h3>Response Structure:</h3>\n";
                echo "<pre>" . htmlspecialchars($xml->asXML()) . "</pre>\n";
                
                // Count aircraft if present
                if (isset($xml->RESOURCES->AIRCRAFT)) {
                    $count = count($xml->RESOURCES->AIRCRAFT);
                    echo "<p style='color: green;'><strong>✓ Success! Found $count aircraft</strong></p>\n";
                }
            }
        }
    }
}

curl_close($ch);

echo "\n<hr>\n";
echo "<p><strong>Next Step:</strong> If this test passes, run test_3_operations.php to test operations data</p>\n";
?>
