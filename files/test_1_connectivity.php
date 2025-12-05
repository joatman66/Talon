<?php
/**
 * TEST 1: Basic Connectivity
 * Tests if we can reach the Talon API endpoint
 */

require_once 'config.php';

echo "<h1>Test 1: Basic Connectivity</h1>\n";
echo "<p>Testing connection to: " . API_URL . "</p>\n";

// Test simple GET request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

echo "<h2>Making request...</h2>\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

if ($error) {
    echo "<p style='color: red;'>❌ Connection Failed: $error</p>\n";
} else {
    echo "<p style='color: green;'>✓ Connection Successful</p>\n";
    echo "<p>HTTP Status Code: $httpCode</p>\n";
    
    if ($response) {
        echo "<h3>Response Preview (first 500 chars):</h3>\n";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "...</pre>\n";
    }
}

curl_close($ch);

echo "\n<hr>\n";
echo "<p><strong>Next Step:</strong> If this test passes, run test_2_auth.php to test authentication</p>\n";
?>
