# Aircraft Status Dashboard - Clean Build

This is a fresh, clean implementation of the Aircraft Status Dashboard for Talon ETA.

## Files Overview

1. **config.php** - All configuration settings in one place
2. **talon_api.php** - Reusable API functions library
3. **dashboard.html** - The main dashboard interface
4. **dashboard_api.php** - API endpoint that feeds data to the dashboard

### Test Files (for systematic testing)
- **test_1_connectivity.php** - Tests basic connection to API
- **test_2_auth.php** - Tests authentication credentials
- **test_3_operations.php** - Tests operations data retrieval

## Setup Instructions

### Step 1: Configure Credentials

Edit `config.php` and replace these placeholders with your actual values:

```php
define('CUSTOMER_CODE', 'YOUR_CUSTOMER_CODE');
define('ACCESS_CODE', 'YOUR_ACCESS_CODE');
define('USERNAME', 'YOUR_USERNAME');
```

### Step 2: Test Systematically

Run each test file in order to verify everything works:

1. **Test Connectivity**
   - Open: `test_1_connectivity.php`
   - Should show: ✓ Connection Successful
   
2. **Test Authentication**
   - Open: `test_2_auth.php`
   - Should show: ✓ Success! Found X aircraft
   
3. **Test Operations Data**
   - Open: `test_3_operations.php`
   - Should show: ✓ Found X Operations

### Step 3: Run the Dashboard

Once all tests pass:
- Open `dashboard.html` in your browser
- It will automatically load and refresh every 30 seconds

## Configuration Options

In `config.php`, you can adjust:

- **Location**: Your ETA location name
- **Refresh Interval**: Dashboard auto-refresh time (in seconds)
- **Excluded Aircraft**: Aircraft to hide from dashboard
- **Included Activity Types**: Types of operations to show (Flight, Sim, etc.)

## How It Works

### Data Flow

1. Dashboard HTML loads in browser
2. JavaScript calls `dashboard_api.php` via AJAX
3. `dashboard_api.php` uses functions from `talon_api.php`
4. `talon_api.php` makes API calls to Talon ETA
5. Data flows back through the chain to display in browser
6. Auto-refreshes every 30 seconds

### API Functions

The `talon_api.php` library provides these functions:

- `callTalonAPI($opstype, $params)` - Make any API request
- `getActiveAircraft()` - Get list of active aircraft
- `getCurrentOperations($start, $stop)` - Get operations in time window
- `getActiveInstructors()` - Get list of active instructors
- `getAircraftStatus()` - Get complete aircraft status summary

### Key Improvements

This clean build includes:

1. **Proper SOAP Parsing**: Correctly handles SOAP envelope responses
2. **Modular Design**: Reusable functions for easy extension
3. **Systematic Testing**: Step-by-step tests to verify each component
4. **Clean Configuration**: All settings in one place
5. **Error Handling**: Graceful handling of API errors
6. **Auto-refresh**: Dashboard updates automatically

## Troubleshooting

### If Test 1 Fails
- Check that the API URL is correct in config.php
- Verify network connectivity to etasql.etasoftware.com

### If Test 2 Fails
- Verify your CUSTOMER_CODE, ACCESS_CODE, and USERNAME
- Check that the user has "Web Services" role in ETA
- Ensure the user account is Active (not disabled)

### If Test 3 Fails
- Check that your LOCATION name exactly matches ETA
- Verify there are active operations in the time window
- Check the activity type filters

### If Dashboard Shows No Aircraft
- Run test_2_auth.php to verify aircraft data is available
- Check the EXCLUDED_AIRCRAFT list in config.php
- Verify the aircraftstatus='active' filter

## Adding Features

To extend the dashboard:

1. **Add new data types**: Use `callTalonAPI()` with different opstypes
2. **Add new filters**: Modify the parameters in API calls
3. **Change time windows**: Adjust the DateTime objects in functions
4. **Add new visualizations**: Modify dashboard.html

## Support

For Talon ETA API documentation, refer to:
- ETA_API_Data_Services_v74.pdf

For dashboard issues, check:
1. Browser console for JavaScript errors
2. PHP error logs for backend issues
3. Test files to isolate problems
