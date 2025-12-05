<?php
// Talon ETA API Configuration
// All configuration in one place for easy management

// API Endpoint
define('API_URL', 'https://etasql.etasoftware.com/etaservice.asmx');

// Authentication - REPLACE THESE WITH YOUR ACTUAL VALUES
define('CUSTOMER_CODE', 'YOUR_CUSTOMER_CODE');
define('ACCESS_CODE', 'YOUR_ACCESS_CODE');
define('USERNAME', 'YOUR_USERNAME');

// Location Settings
define('LOCATION', 'Southern Illinois');

// Date/Time Settings
date_default_timezone_set('America/Chicago');

// Refresh interval for dashboard (in seconds)
define('REFRESH_INTERVAL', 30);

// Aircraft to exclude (obsolete/inactive aircraft)
$EXCLUDED_AIRCRAFT = [
    'N2340E',
    'N99126',
    'N4376C',
    'N5297G',
    'N739SB'
];

// Activity types to include (filter out non-flight operations)
$INCLUDED_ACTIVITY_TYPES = [
    'Flight',
    'Sim'
];

return [
    'api_url' => API_URL,
    'customer_code' => CUSTOMER_CODE,
    'access_code' => ACCESS_CODE,
    'username' => USERNAME,
    'location' => LOCATION,
    'refresh_interval' => REFRESH_INTERVAL,
    'excluded_aircraft' => $EXCLUDED_AIRCRAFT,
    'included_activity_types' => $INCLUDED_ACTIVITY_TYPES
];
