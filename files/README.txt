# AIRCRAFT DASHBOARD SETUP GUIDE
# ================================

## FILES INCLUDED:
1. test_api.php - Diagnostic tool to test your API connection
2. dashboard.html - The main aircraft status dashboard
3. dashboard_proxy.php - Backend proxy that fetches data from Talon
4. config_example.php - This configuration guide

## SETUP STEPS:

### Step 1: Configure dashboard_proxy.php
Open dashboard_proxy.php and update these lines (around line 6-10):

```php
$API_URL = 'https://yourdomain.com/etaws/apipost.php';  // Your actual Talon API URL
$CUSTOMER_CODE = 'your_customer_code';                   // Your Talon customer code
$ACCESS_CODE = 'your_access_code';                       // Your Talon access code  
$USERNAME = 'your_username';                             // Your Talon username
$LOCATION = 'Your Location';                             // e.g., "Dallas", "Phoenix", etc.
```

### Step 2: Upload Files to Your Web Server
Upload these files to your web server:
- test_api.php
- dashboard.html
- dashboard_proxy.php

Make sure PHP is enabled on your server and has cURL support.

### Step 3: Test the Connection
1. Navigate to: http://yourserver.com/test_api.php
2. Fill in your API credentials
3. Click "Test Aircraft Data" - you should see aircraft records
4. Click "Test Operations Data" - you should see today's flight operations
5. If both work, proceed to Step 4

### Step 4: Open the Dashboard
Navigate to: http://yourserver.com/dashboard.html

The dashboard should now display:
- All active aircraft
- Current flight operations
- Status indicators (Available/In Use/Down)
- Auto-refresh every 30 seconds

## TROUBLESHOOTING:

### Dashboard shows "Loading..." forever:
- Check browser console for errors (F12)
- Verify dashboard_proxy.php is accessible
- Test dashboard_proxy.php directly: http://yourserver.com/dashboard_proxy.php
  (Should return JSON data)

### "No response received" in test_api.php:
- Verify API_URL is correct
- Check if your server can make outbound HTTPS connections
- Verify customer code, access code, and username are correct

### Aircraft show but no operations:
- Verify LOCATION matches exactly with your Talon location name
- Check that you have flights scheduled for today
- Try the "Test Operations Data" button in test_api.php

### XML Parsing Failed:
- The API might be returning an error message
- Check the raw response in test_api.php
- Verify all credentials are correct

## API ENDPOINTS USED:

1. Aircraft Data:
   - opstype: aircraft
   - operation: export
   - aircraftstatus: Active

2. Operations Data:
   - opstype: operation
   - operation: export
   - activitytype: Flight
   - startdatetime/stopdatetime: Today's date range

## CUSTOMIZATION:

### Change refresh interval:
In dashboard.html, find this line (near the end):
```javascript
refreshInterval = setInterval(fetchDashboardData, 30000);
```
Change 30000 to desired milliseconds (e.g., 60000 = 1 minute)

### Filter aircraft types:
In dashboard_proxy.php, add to $aircraft_params:
```php
'resourcetype' => 'C-172'  // Only show C-172s
```

### Change time range for operations:
In dashboard_proxy.php, modify:
```php
'startdatetime' => $today . ' 0600',  // Start at 6 AM
'stopdatetime' => $today . ' 2200',   // End at 10 PM
```

## SECURITY NOTE:
Never expose your access codes in client-side code. The dashboard_proxy.php
keeps credentials server-side and only sends processed data to the browser.

## SUPPORT:
If you continue to have issues:
1. Check the raw API response in test_api.php
2. Verify the XML structure matches what the code expects
3. Test each endpoint separately
4. Check server PHP error logs
