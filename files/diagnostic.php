<?php
/**
 * ADVANCED DASHBOARD DIAGNOSTIC
 * This script helps identify exactly where the problem is
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Advanced Dashboard Diagnostic</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #0f0; padding: 20px; }
        .pass { color: #0f0; }
        .fail { color: #f00; }
        .warn { color: #ff0; }
        .section { margin: 20px 0; border: 1px solid #333; padding: 15px; }
        h2 { color: #0ff; }
        pre { background: #000; padding: 10px; overflow-x: auto; }
        .config { background: #222; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔍 ADVANCED DASHBOARD DIAGNOSTIC</h1>
    
<?php

echo "<div class='section'>\n";
echo "<h2>1. PHP ENVIRONMENT CHECK</h2>\n";

// PHP Version
echo "PHP Version: " . phpversion();
echo (version_compare(phpversion(), '7.0.0', '>=')) ? " <span class='pass'>✓ OK</span>\n" : " <span class='fail'>✗ TOO OLD</span>\n";
echo "<br>\n";

// cURL check
if (function_exists('curl_version')) {
    $curl_info = curl_version();
    echo "cURL Version: " . $curl_info['version'] . " <span class='pass'>✓ AVAILABLE</span>\n";
} else {
    echo "cURL: <span class='fail'>✗ NOT AVAILABLE - CRITICAL ERROR</span>\n";
}
echo "<br>\n";

// SimpleXML check
echo "SimpleXML: ";
echo (extension_loaded('simplexml')) ? "<span class='pass'>✓ AVAILABLE</span>" : "<span class='fail'>✗ NOT AVAILABLE</span>";
echo "<br>\n";

echo "</div>\n";

// ============================================
echo "<div class='section'>\n";
echo "<h2>2. CONFIGURATION CHECK</h2>\n";

// Load config from dashboard_proxy.php
$proxy_file = __DIR__ . '/dashboard_proxy.php';
if (file_exists($proxy_file)) {
    $content = file_get_contents($proxy_file);
    
    // Extract configuration
    preg_match('/\$API_URL\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $api_url_match);
    preg_match('/\$CUSTOMER_CODE\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $customer_code_match);
    preg_match('/\$ACCESS_CODE\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $access_code_match);
    preg_match('/\$USERNAME\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $username_match);
    preg_match('/\$LOCATION\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $location_match);
    
    $api_url = $api_url_match[1] ?? 'NOT SET';
    $customer_code = $customer_code_match[1] ?? 'NOT SET';
    $access_code = $access_code_match[1] ?? 'NOT SET';
    $username = $username_match[1] ?? 'NOT SET';
    $location = $location_match[1] ?? 'NOT SET';
    
    echo "<div class='config'>\n";
    echo "API_URL: " . htmlspecialchars($api_url);
    echo ($api_url !== 'NOT SET' && $api_url !== 'https://yourdomain.com/etaws/apipost.php') ? " <span class='pass'>✓</span>" : " <span class='fail'>✗ NOT CONFIGURED</span>";
    echo "<br>\n";
    
    echo "CUSTOMER_CODE: " . (($customer_code === 'NOT SET' || $customer_code === 'your_customer_code') ? "<span class='fail'>✗ NOT CONFIGURED</span>" : "<span class='pass'>✓ SET</span>");
    echo "<br>\n";
    
    echo "ACCESS_CODE: " . (($access_code === 'NOT SET' || $access_code === 'your_access_code') ? "<span class='fail'>✗ NOT CONFIGURED</span>" : "<span class='pass'>✓ SET (hidden)</span>");
    echo "<br>\n";
    
    echo "USERNAME: " . htmlspecialchars($username);
    echo ($username !== 'NOT SET' && $username !== 'your_username') ? " <span class='pass'>✓</span>" : " <span class='fail'>✗ NOT CONFIGURED</span>";
    echo "<br>\n";
    
    echo "LOCATION: " . htmlspecialchars($location);
    echo ($location !== 'NOT SET' && $location !== 'Your Location') ? " <span class='pass'>✓</span>" : " <span class='fail'>✗ NOT CONFIGURED</span>";
    echo "<br>\n";
    echo "</div>\n";
    
} else {
    echo "<span class='fail'>✗ dashboard_proxy.php NOT FOUND</span>\n";
    $api_url = $customer_code = $access_code = $username = $location = 'NOT SET';
}

echo "</div>\n";

// ============================================
if ($api_url !== 'NOT SET' && $customer_code !== 'NOT SET') {
    
    echo "<div class='section'>\n";
    echo "<h2>3. API CONNECTIVITY TEST</h2>\n";
    
    // Test aircraft endpoint
    $test_url = $api_url . '?' . http_build_query([
        'customercode' => $customer_code,
        'accesscode' => $access_code,
        'username' => $username,
        'opstype' => 'aircraft',
        'operation' => 'export',
        'aircraftstatus' => 'Active'
    ]);
    
    echo "Testing URL: <span class='warn'>" . htmlspecialchars(substr($test_url, 0, 100)) . "...</span><br>\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $test_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $start_time = microtime(true);
    $response = curl_exec($ch);
    $elapsed = round((microtime(true) - $start_time) * 1000);
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    echo "Response time: {$elapsed}ms<br>\n";
    echo "HTTP Status: {$http_code} ";
    echo ($http_code === 200) ? "<span class='pass'>✓ OK</span>" : "<span class='fail'>✗ ERROR</span>";
    echo "<br>\n";
    
    if ($curl_error) {
        echo "cURL Error: <span class='fail'>" . htmlspecialchars($curl_error) . "</span><br>\n";
    }
    
    if ($response) {
        $response_length = strlen($response);
        echo "Response size: {$response_length} bytes<br>\n";
        
        // Check if it's XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response);
        
        if ($xml !== false) {
            echo "XML Parsing: <span class='pass'>✓ SUCCESS</span><br>\n";
            
            $aircraft_count = count($xml->xpath('//AIRCRAFT'));
            echo "Aircraft found: {$aircraft_count} <span class='pass'>✓</span><br>\n";
            
            if ($aircraft_count > 0) {
                echo "\n<h3>Sample Aircraft Data:</h3>\n";
                $sample = $xml->xpath('//AIRCRAFT')[0];
                echo "<pre>" . htmlspecialchars($sample->asXML()) . "</pre>\n";
            }
        } else {
            echo "XML Parsing: <span class='fail'>✗ FAILED</span><br>\n";
            echo "<h3>Errors:</h3>\n";
            foreach(libxml_get_errors() as $error) {
                echo "<span class='fail'>" . htmlspecialchars(trim($error->message)) . "</span><br>\n";
            }
            
            echo "\n<h3>Raw Response (first 500 chars):</h3>\n";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>\n";
        }
    } else {
        echo "<span class='fail'>✗ NO RESPONSE RECEIVED</span><br>\n";
    }
    
    echo "</div>\n";
    
    // ============================================
    echo "<div class='section'>\n";
    echo "<h2>4. OPERATIONS DATA TEST</h2>\n";
    
    $today = date('d M Y');
    $ops_url = $api_url . '?' . http_build_query([
        'customercode' => $customer_code,
        'accesscode' => $access_code,
        'username' => $username,
        'location' => $location,
        'opstype' => 'operation',
        'operation' => 'export',
        'startdatetime' => $today . ' 0000',
        'stopdatetime' => $today . ' 2359',
        'activitytype' => 'Flight'
    ]);
    
    echo "Testing today's operations: " . $today . "<br>\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $ops_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status: {$http_code} ";
    echo ($http_code === 200) ? "<span class='pass'>✓</span>" : "<span class='fail'>✗</span>";
    echo "<br>\n";
    
    if ($response) {
        $xml = simplexml_load_string($response);
        if ($xml !== false) {
            $ops_count = count($xml->xpath('//OPERATION'));
            echo "Operations found: {$ops_count} ";
            echo ($ops_count > 0) ? "<span class='pass'>✓</span>" : "<span class='warn'>⚠ NONE (may be normal if no flights today)</span>";
            echo "<br>\n";
            
            if ($ops_count > 0) {
                echo "\n<h3>Sample Operation:</h3>\n";
                $sample = $xml->xpath('//OPERATION')[0];
                echo "<pre>" . htmlspecialchars($sample->asXML()) . "</pre>\n";
            }
        } else {
            echo "XML Parsing: <span class='fail'>✗ FAILED</span><br>\n";
        }
    }
    
    echo "</div>\n";
    
    // ============================================
    echo "<div class='section'>\n";
    echo "<h2>5. PROXY OUTPUT TEST</h2>\n";
    
    // Simulate what dashboard_proxy.php should return
    echo "Simulating dashboard_proxy.php output...<br>\n";
    
    if ($xml !== false && $aircraft_count > 0) {
        echo "<span class='pass'>✓ Proxy should work correctly</span><br>\n";
        echo "Expected JSON structure:<br>\n";
        echo "<pre>{\n";
        echo "  \"success\": true,\n";
        echo "  \"aircraft\": [{$aircraft_count} items],\n";
        echo "  \"operations\": [{$ops_count} items],\n";
        echo "  \"summary\": {\n";
        echo "    \"total\": {$aircraft_count},\n";
        echo "    \"available\": ...,\n";
        echo "    \"in_use\": ...\n";
        echo "  }\n";
        echo "}</pre>\n";
    } else {
        echo "<span class='fail'>✗ Proxy will fail - aircraft data not available</span><br>\n";
    }
    
    echo "</div>\n";
}

// ============================================
echo "<div class='section'>\n";
echo "<h2>6. RECOMMENDATIONS</h2>\n";

$issues = [];

if (!function_exists('curl_version')) {
    $issues[] = "<span class='fail'>CRITICAL: Install PHP cURL extension</span>";
}

if ($api_url === 'NOT SET' || $api_url === 'https://yourdomain.com/etaws/apipost.php') {
    $issues[] = "<span class='fail'>Configure API_URL in dashboard_proxy.php</span>";
}

if ($customer_code === 'NOT SET' || $customer_code === 'your_customer_code') {
    $issues[] = "<span class='fail'>Configure CUSTOMER_CODE in dashboard_proxy.php</span>";
}

if ($http_code !== 200 && isset($http_code)) {
    $issues[] = "<span class='fail'>Fix API connectivity (HTTP {$http_code})</span>";
}

if (empty($issues)) {
    echo "<span class='pass'>✓✓✓ ALL CHECKS PASSED! ✓✓✓</span><br>\n";
    echo "<span class='pass'>Your dashboard should be working correctly.</span><br>\n";
    echo "<br>\n";
    echo "Next step: Open <a href='dashboard.html' style='color:#0ff'>dashboard.html</a><br>\n";
} else {
    echo "<span class='warn'>Issues found:</span><br>\n";
    foreach ($issues as $issue) {
        echo "• " . $issue . "<br>\n";
    }
}

echo "</div>\n";

?>

</body>
</html>
