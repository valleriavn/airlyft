# SMS Integration Debugging Guide

## Overview
SMS notifications are being recorded in the database but may not be actually sending. Use these diagnostic tools to identify the root cause.

## Diagnostic Tools Created

### 1. **test_sms.php**
Path: `/airlyft/integrations/paypal/test_sms.php`

**Purpose:** Comprehensive SMS gateway configuration and connectivity test

**Features:**
- Validates SMS configuration (username, password, API URL loaded from .env)
- Tests phone number formatting with multiple input formats
- Tests SMS gateway connectivity
- Checks database connection and smsnotification table structure

**Access:** 
```
http://localhost/airlyft/integrations/paypal/test_sms.php
```

**What to look for:**
- ✓ All configuration items must show as "SET"
- ✓ Phone formatting tests should show "Valid format"
- ✓ Gateway connectivity should show successful response or at least "Gateway responded"

---

### 2. **test_sms_direct.php**
Path: `/airlyft/integrations/paypal/test_sms_direct.php`

**Purpose:** Send an actual test SMS to verify gateway is working

**Features:**
- User-friendly form to input a test phone number
- Formats the phone number automatically
- Sends test SMS directly to SMS gateway
- Shows gateway response and parsing

**Access:**
```
http://localhost/airlyft/integrations/paypal/test_sms_direct.php
```

**How to use:**
1. Open the URL above
2. Enter a test phone number (any format: 09123456789, +639123456789, etc.)
3. Click "Send Test SMS"
4. Check if you receive the SMS and what the gateway response says

**Expected SMS:** "AirLyft Test SMS - [timestamp]"

---

### 3. **diagnostic.php**
Path: `/airlyft/integrations/paypal/diagnostic.php`

**Purpose:** View complete SMS diagnostic report for a specific booking

**Features:**
- Shows booking details and contact information
- Shows payment records
- Shows all SMS notifications saved for that booking
- Shows relevant error log entries

**Access:**
```
http://localhost/airlyft/integrations/paypal/diagnostic.php?booking_id=44
```

Replace `44` with your actual booking ID.

**What to check:**
- Is User Phone populated? (if empty, SMS won't send)
- What SMS status is recorded? (Pending, Sent, Failed?)
- Are there error log entries for that booking?

---

## Debugging Workflow

### Step 1: Check Configuration
1. Open `test_sms.php`
2. Verify all configuration shows as "SET":
   - SMS_GATEWAY_USERNAME
   - SMS_GATEWAY_PASSWORD
   - SMS_GATEWAY_API

**If any are EMPTY:**
- Check that `.env` file exists in `/airlyft/` directory
- Check that it contains these lines:
  ```
  SMS_GATEWAY_USERNAME=sms
  SMS_GATEWAY_PASSWORD=8m2fKZur
  SMS_GATEWAY_API=http://10.187.225.161:8080/messages
  ```
- Restart Apache after editing .env

### Step 2: Test Gateway Connectivity
1. Still on `test_sms.php`
2. Look at section "3. Testing SMS Gateway Connectivity"
3. Check if gateway responds:

**If gateway responds:**
- ✓ Gateway is reachable
- ✓ Authentication may be working
- Proceed to Step 3

**If gateway doesn't respond:**
- ❌ SMS gateway at `http://10.187.225.161:8080/messages` is unreachable
- Check if SMS gateway server is running
- Check network connectivity from XAMPP server to 10.187.225.161
- The SMS failure is likely due to unreachable gateway

### Step 3: Test Phone Number Formats
1. Still on `test_sms.php`
2. Check section "2. Testing Phone Number Formatting"
3. Verify test phone numbers show "✓ Valid format"

**If validation fails:**
- The phone number format is being rejected
- Check the regex pattern at top of `send_sms()` function
- Current pattern: `/^\+63\d{9,12}$/` (allows +63 followed by 9-12 digits)

### Step 4: Send Test SMS
1. Open `test_sms_direct.php`
2. Enter a valid Philippine phone number (yours if testing)
3. Click "Send Test SMS"
4. Check if you receive an SMS with "AirLyft Test SMS" message

**If test SMS arrives:**
- ✓ SMS gateway is working correctly
- ✓ Problem is specific to capture_order.php code or phone number from booking
- Proceed to Step 5

**If test SMS doesn't arrive:**
- ❌ SMS gateway is not delivering messages
- Problem could be with gateway credentials or gateway status
- Contact SMS gateway provider

### Step 5: Check Booking Details
1. Open `diagnostic.php?booking_id=YOUR_BOOKING_ID`
2. Check the following:

**Check User Phone:**
- If "User Phone" shows "(empty)", this is the problem
  - SMS cannot be sent without a phone number
  - Ensure user provided phone during checkout
  - Check Users table to add phone number manually if needed

**Check SMS Records:**
- Should show SMS notification records for this booking
- Check if status is "Pending", "Sent", or "Failed"
- Look at the message content stored

**Check Error Logs:**
- Should show recent SMS Debug messages
- Look for patterns like:
  - "SMS Original phone: [number]"
  - "SMS Phone formatted: [+63...]"
  - "SMS Gateway Response: [response]"
  - "SMS FAILED: [reason]"

---

## Common Issues and Solutions

### Issue 1: SMS Status Shows "Failed"
**Possible causes:**
1. User phone number is empty in Users table
2. Phone number format is invalid after formatting
3. SMS gateway is unreachable
4. SMS gateway credentials are wrong

**Solutions:**
1. Check User Phone in diagnostic.php output
2. Run test_sms.php to verify phone formatting
3. Run test_sms_direct.php to test gateway connectivity
4. Check error logs in diagnostic.php for specific error message

### Issue 2: User Phone is Empty
**Why this happens:**
- User didn't provide phone during checkout
- Phone field not properly saved to database
- Different field name than expected

**Solution:**
- Manually add phone number to Users table:
  ```sql
  UPDATE Users SET phone = '+639123456789' WHERE user_id = [your_user_id];
  ```

### Issue 3: Phone Number Too Long
**Why this happens:**
- Passenger phone column stored as INT, got rounded to 2147483647
- Phone number pasted with spaces or special characters

**Solution:**
- Verify Passenger table column type:
  ```sql
  DESCRIBE Passenger;
  ```
- If passenger_phone_number is INT, alter it:
  ```sql
  ALTER TABLE Passenger MODIFY COLUMN passenger_phone_number VARCHAR(20);
  ```
- Ensure Users.phone is VARCHAR(20) type

### Issue 4: Gateway Shows No Response
**Why this happens:**
- SMS gateway server is down
- Network can't reach 10.187.225.161:8080
- Firewall blocking connection

**Solution:**
- Verify SMS gateway IP is correct in .env
- Test connectivity from command line:
  ```
  curl -X POST http://10.187.225.161:8080/messages \
    -H "Authorization: Basic c21zOjhtMmZLWnVy" \
    -H "Content-Type: application/json" \
    -d '{"phoneNumbers":["+639123456789"],"message":"test"}'
  ```
- If no response, gateway is unreachable

---

## Code Changes Made

### capture_order.php
- Added SMS record insertion before attempting to send (creates "Pending" status)
- Enhanced phone validation to be more flexible (9-12 digits)
- Added comprehensive debug logging at each SMS step
- Added database update to mark SMS as "Sent" when successfully sent
- Uses Users.phone as primary phone source (not passenger phone)

### send_sms() function
- Validates credentials are defined and not empty
- Formats Philippine phone numbers correctly
- Sends to SMS gateway with Basic Auth
- Logs detailed information at each step
- Handles multiple response formats from gateway

### config.php
- Updated to parse .env file and define SMS_GATEWAY_* constants
- Constants now loaded reliably at startup

---

## Next Steps

1. **Immediate:**
   - Run test_sms.php to verify configuration
   - Run test_sms_direct.php to test actual SMS sending
   - Run diagnostic.php for your booking to see what's saved

2. **If gateway not responding:**
   - Check if SMS gateway server is running
   - Verify IP and port in .env file
   - Check network/firewall settings

3. **If user phone is empty:**
   - Add phone number to Users table manually
   - Or ensure phone field is properly filled during user registration/checkout

4. **If tests pass but booking SMS still fails:**
   - Check error logs for detailed error message
   - May need to modify send_sms() function to handle specific gateway response format

---

## Reference: SMS Gateway Details

**Gateway:** SMS Gateway API
- **URL:** http://10.187.225.161:8080/messages
- **Method:** POST
- **Auth:** Basic Auth (username: sms, password: 8m2fKZur)
- **Payload Format:** JSON with phoneNumbers array and message text
- **Success Indicators:** 
  - response.success === true
  - response.status === "success" or "ok" or "sent"
  - response.code === 0
  - Empty response

---

## Error Log Format

When SMS is processed, look for log entries like:

```
[date time] [notice] SMS: Attempting to send confirmation SMS to: +639123456789 for booking #44
[date time] [notice] SMS Original phone: 09123456789
[date time] [notice] SMS Phone formatted: +639123456789
[date time] [notice] SMS Gateway Response (123 bytes): {"success":true}
[date time] [notice] SMS SUCCESS: Sent to +639123456789
```

Or for failures:

```
[date time] [notice] SMS: No valid phone number found for booking #44
[date time] [warning] SMS FAILED: No response from gateway
[date time] [warning] SMS Invalid format - +639 does not match +63 pattern
```

Use these logs to identify exactly where the SMS process is failing.
