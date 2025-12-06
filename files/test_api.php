<!DOCTYPE html>
<html>
<head>
    <title>Talon API Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        .success { background-color: #d4edda; }
        .error { background-color: #f8d7da; }
        pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
        input, button { margin: 5px; padding: 8px; }
    </style>
</head>
<body>
    <h1>Talon ETA API Diagnostic Tool</h1>
    
    <div class="section">
        <h2>API Configuration</h2>
        <form method="POST">
            <label>API URL:</label><br>
            <input type="text" name="api_url" value="<?php echo htmlspecialchars($_POST['api_url'] ?? ''); ?>" size="80" placeholder="https://yourdomain.com/etaws/apipost.php"><br>
            
            <label>Customer Code:</label><br>
            <input type="text" name="customer_code" value="<?php echo htmlspecialchars($_POST['customer_code'] ?? ''); ?>" size="40"><br>
            
            <label>Access Code:</label><br>
            <input type="password" name="access_code" value="<?php echo htmlspecialchars($_POST['access_code'] ?? ''); ?>" size="40"><br>
            
            <label>Username:</label><br>
            <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" size="40"><br>
            
            <label>Location:</label><br>
            <input type="text" name="location" value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>" size="40" placeholder="e.g., Dallas"><br>
            
            <button type="submit" name="action" value="test_aircraft">Test Aircraft Data</button>
            <button type="submit" name="action" value="test_operations">Test Operations Data</button>
        </form>
    </div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['api_url'])) {
    $api_url = $_POST['api_url'];
    $customer_code = $_POST['customer_code'];
    $access_code = $_POST['access_code'];
    $username = $_POST['username'];
    $location = $_POST['location'];
    $action = $_POST['action'];
    
    // Test Aircraft Data
    if ($action === 'test_aircraft') {
        echo '<div class="section">';
        echo '<h2>Aircraft Data Test</h2>';
        
        $request_url = $api_url . '?' . http_build_query([
            'customercode' => $customer_code,
            'accesscode' => $access_code,
            'username' => $username,
            'opstype' => 'aircraft',
            'operation' => 'export',
            'aircraftstatus' => 'Active'
        ]);
        
        echo '<p><strong>Request URL:</strong></p>';
        echo '<pre>' . htmlspecialchars($request_url) . '</pre>';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        echo '<p><strong>HTTP Status:</strong> ' . $http_code . '</p>';
        
        if ($curl_error) {
            echo '<p class="error"><strong>cURL Error:</strong> ' . htmlspecialchars($curl_error) . '</p>';
        }
        
        if ($response) {
            echo '<p><strong>Response (first 2000 chars):</strong></p>';
            echo '<pre>' . htmlspecialchars(substr($response, 0, 2000)) . '</pre>';
            
            // Try to parse as XML
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response);
            if ($xml !== false) {
                echo '<p class="success"><strong>✓ Valid XML Response</strong></p>';
                $aircraft_count = count($xml->xpath('//AIRCRAFT'));
                echo '<p><strong>Aircraft Count:</strong> ' . $aircraft_count . '</p>';
                
                if ($aircraft_count > 0) {
                    echo '<p><strong>Sample Aircraft:</strong></p>';
                    $sample = $xml->xpath('//AIRCRAFT')[0];
                    echo '<pre>' . htmlspecialchars($sample->asXML()) . '</pre>';
                }
            } else {
                echo '<p class="error"><strong>✗ XML Parsing Failed</strong></p>';
                foreach(libxml_get_errors() as $error) {
                    echo '<p class="error">' . htmlspecialchars($error->message) . '</p>';
                }
            }
        } else {
            echo '<p class="error"><strong>No response received</strong></p>';
        }
        
        echo '</div>';
    }
    
    // Test Operations Data
    if ($action === 'test_operations') {
        echo '<div class="section">';
        echo '<h2>Operations Data Test (Today)</h2>';
        
        $today = date('d M Y');
        $start_time = $today . ' 0000';
        $stop_time = $today . ' 2359';
        
        $request_url = $api_url . '?' . http_build_query([
            'customercode' => $customer_code,
            'accesscode' => $access_code,
            'username' => $username,
            'location' => $location,
            'opstype' => 'operation',
            'operation' => 'export',
            'startdatetime' => $start_time,
            'stopdatetime' => $stop_time,
            'activitytype' => 'Flight'
        ]);
        
        echo '<p><strong>Request URL:</strong></p>';
        echo '<pre>' . htmlspecialchars($request_url) . '</pre>';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        echo '<p><strong>HTTP Status:</strong> ' . $http_code . '</p>';
        
        if ($curl_error) {
            echo '<p class="error"><strong>cURL Error:</strong> ' . htmlspecialchars($curl_error) . '</p>';
        }
        
        if ($response) {
            echo '<p><strong>Response (first 2000 chars):</strong></p>';
            echo '<pre>' . htmlspecialchars(substr($response, 0, 2000)) . '</pre>';
            
            // Try to parse as XML
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response);
            if ($xml !== false) {
                echo '<p class="success"><strong>✓ Valid XML Response</strong></p>';
                $op_count = count($xml->xpath('//OPERATION'));
                echo '<p><strong>Operations Count:</strong> ' . $op_count . '</p>';
                
                if ($op_count > 0) {
                    echo '<p><strong>Sample Operation:</strong></p>';
                    $sample = $xml->xpath('//OPERATION')[0];
                    echo '<pre>' . htmlspecialchars($sample->asXML()) . '</pre>';
                }
            } else {
                echo '<p class="error"><strong>✗ XML Parsing Failed</strong></p>';
                foreach(libxml_get_errors() as $error) {
                    echo '<p class="error">' . htmlspecialchars($error->message) . '</p>';
                }
            }
        } else {
            echo '<p class="error"><strong>No response received</strong></p>';
        }
        
        echo '</div>';
    }
}
?>

</body>
</html>
