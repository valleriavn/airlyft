# SMS Integration Improvements Summary

## Changes Made

### 1. Enhanced SMS Sending Logic in capture_order.php

**Before:** SMS record was inserted AFTER attempting to send, with status determined by send result

**After:** 
- SMS record is inserted BEFORE sending with "Pending" status
- If send succeeds, status is updated to "Sent"
- Provides better tracking of SMS lifecycle

**Code location:** Lines 814-871

**Benefits:**
- SMS record always exists in database (for audit trail)
- Clear distinction between "Pending" (not yet sent) and "Sent" (successfully sent)
- Can track which SMS failed and why

### 2. Improved send_sms() Function

**Enhancements:**
- Validates all credentials are defined AND not empty
- Comprehensive logging at each step:
  - Original phone number
  - Cleaned phone number
  - Formatted phone number
  - Validation result
  - Payload being sent
  - Gateway response (full content and size)
  - Response parsing (JSON or plain text)
  - Success/failure reason
- More flexible phone validation (9-12 digits instead of exactly 10)
- Multiple success indicators checked:
  - response.success === true
  - response.status in ['success', 'ok', 'sent', 'Success']
  - response.code === 0
  - Empty response (some gateways return nothing on success)
- Proper error handling at each stage
- SSL/TLS context configured for secure connections

**Code location:** Lines 460-588

**Benefits:**
- Every SMS attempt generates detailed logs for debugging
- Phone number formatting is more flexible
- Gateway response parsing is more robust
- Easy to identify exact failure point if SMS doesn't send

### 3. Phone Number Selection and Formatting

**Behavior:**
- Uses Users.phone as primary source (more reliable)
- Fallback to Passenger.passenger_phone_number only if user phone empty
- Flexible formatting handles:
  - 09XXXXXXXXX (10 digits, no country code)
  - 639XXXXXXXXX (11 digits, with country code, no +)
  - +639XXXXXXXXX (11 digits, with country code and +)
  - +6639XXXXXXXXX (12 digits, alternative format)
- Final format: +63 followed by 9-12 digits
- All non-digit characters stripped before processing

**Code location:** Lines 758-801

**Benefits:**
- Accommodates various user input formats
- Consistently outputs +63 format for gateway
- Validates against flexible regex: `/^\+63\d{9,12}$/`
- Clear error logging if validation fails

### 4. SMS Database Updates

**New feature:** After successful SMS send, status is updated from "Pending" to "Sent"

**Code location:** Lines 817-823 (confirmation SMS), Lines 852-858 (snack SMS)

**Benefits:**
- Real-time tracking of SMS delivery status
- Can distinguish between:
  - Pending: Not yet sent
  - Sent: Successfully sent to gateway
  - Failed: Gateway rejected or no response

### 5. New Diagnostic Tools

#### test_sms.php
- Verifies SMS configuration is loaded correctly
- Tests phone number formatting with multiple inputs
- Tests SMS gateway connectivity
- Checks database structure
- **Path:** `/airlyft/integrations/paypal/test_sms.php`

#### test_sms_direct.php
- Web form to send test SMS to any phone number
- Shows exact payload and gateway response
- Useful for verifying gateway is working
- **Path:** `/airlyft/integrations/paypal/test_sms_direct.php`

#### diagnostic.php
- Complete view of booking SMS records
- Shows user phone, payment status, SMS status
- Displays relevant error log entries
- **Path:** `/airlyft/integrations/paypal/diagnostic.php?booking_id=X`

### 6. Documentation

#### SMS_DEBUG_GUIDE.md
- Complete guide to using diagnostic tools
- Step-by-step debugging workflow
- Common issues and solutions
- SMS gateway reference information
- **Path:** `/airlyft/SMS_DEBUG_GUIDE.md`

---

## SMS Flow Architecture

```
User completes PayPal payment
        ↓
capture_order.php receives callback
        ↓
Validate booking and payment
        ↓
Update Booking status to "Confirmed"
        ↓
Update Payment record
        ↓
Send confirmation email
        ↓
Retrieve user phone from Users table
        ↓
Format phone to +63 format
        ↓
INSERT SMS record with status "Pending"
        ↓
Call send_sms() function
        ↓
send_sms() validates credentials
        ↓
send_sms() validates phone format
        ↓
send_sms() sends POST to SMS gateway
        ↓
Send gateway response
        ↓
UPDATE SMS status to "Sent" or stays "Pending"/"Failed"
        ↓
Send snack selection SMS (same process)
        ↓
Return success JSON to browser
```

---

## Logging Structure

### Log Messages Generated

**Validation Phase:**
```
SMS: user_phone from booking: [phone value]
SMS Debug: Original phone: [value], Cleaned: [value], Length: [length]
SMS Debug: Formatted phone: [+63...]
SMS WARNING: [validation details if failed]
SMS: Using user phone for booking #[id]: [phone]
```

**Sending Phase:**
```
SMS: Attempting to send confirmation SMS to: [phone] for booking #[id]
SMS: Record inserted into database for booking #[id]
SMS Original phone: [value]
SMS Phone after cleanup: [value]
SMS Phone formatted: [value]
SMS Sending to: [+63...] (original: [...])
SMS Message: [first 100 chars...]
SMS Payload JSON: [json content]
SMS Auth Header (masked): Authorization: Basic [redacted]
Attempting to send SMS to: [gateway URL]
```

**Response Phase:**
```
SMS FAILED: No response from gateway - [error]
SMS Gateway Response (XXX bytes): [full response]
SMS Response decoded as JSON: [parsed json]
SMS SUCCESS: Sent to [phone]
SMS FAILED: Gateway returned error - [error details]
SMS SUCCESS: Empty response from gateway (typically indicates success)
SMS FAILED: Invalid response format - [response start]
```

---

## Testing Instructions

### Quick Test
1. Open: `http://localhost/airlyft/integrations/paypal/test_sms.php`
2. Verify configuration shows all items as "SET"
3. Check phone formatting tests show "✓ Valid format"
4. Check gateway connectivity (if available)

### Full Test
1. Open: `http://localhost/airlyft/integrations/paypal/test_sms_direct.php`
2. Enter a test phone number
3. Click "Send Test SMS"
4. Wait for result

### Production Test
1. Process a real booking with PayPal payment
2. Open: `http://localhost/airlyft/integrations/paypal/diagnostic.php?booking_id=[id]`
3. Check that:
   - User Phone is populated
   - SMS records show status "Sent" (not "Pending" or "Failed")
   - Error logs show successful SMS messages
4. Verify SMS arrives on your phone

---

## Configuration Requirements

### .env File
Must be at: `/airlyft/.env`

Required content:
```
SMS_GATEWAY_USERNAME=sms
SMS_GATEWAY_PASSWORD=8m2fKZur
SMS_GATEWAY_API=http://10.187.225.161:8080/messages

PAYPAL_CLIENT_ID=your_client_id
PAYPAL_CLIENT_SECRET=your_secret
PAYPAL_MODE=sandbox
PAYPAL_API_BASE=https://api-m.sandbox.paypal.com

GMAIL_EMAIL=your_email@gmail.com
GMAIL_PASSWORD=your_app_password
```

### Database
Required tables:
- `Booking` (booking_id, user_id, status, ...)
- `Users` (user_id, phone, email, ...)
- `Payment` (payment_id, booking_id, sms_notif_status, ...)
- `smsnotification` (sms_id, booking_id, message, sms_status, ...)
- `Passenger` (passenger_id, passenger_phone_number, ...)

Phone number columns should be VARCHAR(20) or TEXT, NOT INT.

---

## Known Issues and Limitations

### Issue: SMS Status Shows "Failed"
**Diagnosis:** Run diagnostic.php to see error logs

### Issue: User Phone is Empty
**Solution:** Ensure phone is collected during user registration and checkout

### Issue: Gateway Returns No Response
**Cause:** SMS gateway server may be unreachable
**Solution:** Verify IP and port in .env, check network connectivity

### Issue: Phone Number Format Validation Fails
**Solution:** Check if phone is in Philippine format, adjust regex if needed

---

## Future Improvements

1. **Retry Logic:** Add automatic retry for failed SMS with exponential backoff
2. **Delivery Reports:** Update SMS status when delivery confirmation received from gateway
3. **Multi-Language:** Support SMS templates in multiple languages
4. **Rate Limiting:** Implement rate limiting to avoid gateway throttling
5. **SMS History UI:** Add admin dashboard to view SMS history and resend if needed
6. **Alternative Gateways:** Support switching between multiple SMS providers

---

## Files Modified

1. **c:\xampp\htdocs\airlyft\integrations\paypal\capture_order.php**
   - Enhanced send_sms() function with comprehensive logging
   - Improved phone validation and formatting
   - Added SMS database record insertion before sending
   - Added status update logic for successful sends

2. **c:\xampp\htdocs\airlyft\auth\config.php** (from previous session)
   - SMS Gateway configuration loading from .env

3. **c:\xampp\htdocs\airlyft\db\connect.php**
   - Database connection (unchanged, but required for SMS operations)

## Files Created

1. **c:\xampp\htdocs\airlyft\integrations\paypal\test_sms.php**
   - SMS configuration and connectivity test

2. **c:\xampp\htdocs\airlyft\integrations\paypal\test_sms_direct.php**
   - Web form for sending test SMS

3. **c:\xampp\htdocs\airlyft\integrations\paypal\diagnostic.php**
   - Booking-specific SMS diagnostic report

4. **c:\xampp\htdocs\airlyft\SMS_DEBUG_GUIDE.md**
   - Comprehensive debugging guide (this document)

5. **c:\xampp\htdocs\airlyft\SMS_IMPROVEMENTS_SUMMARY.md**
   - Summary of all changes (this document)

---

## Version History

### Current Version
- SMS record inserted with "Pending" status before sending
- Enhanced logging at all stages
- Flexible phone number validation (9-12 digits)
- Multiple success indicator checks
- Database update to mark SMS as "Sent" when successful
- Three new diagnostic tools
- Comprehensive debugging guide

### Previous Issues Resolved
- ✅ SMS not being recorded in database
- ✅ SMS status always showing as "Failed" immediately
- ✅ Phone number formatting too strict
- ✅ PayPal credentials not loading from .env
- ✅ SMS gateway response parsing failing
- ✅ Email notifications working but SMS not

---

## Support and Debugging

For SMS not sending:
1. Run test_sms.php to verify configuration
2. Run test_sms_direct.php to test gateway
3. Run diagnostic.php to check booking details
4. Check error logs for specific error message
5. Verify SMS gateway server is running and accessible
6. Ensure user phone number is populated in Users table

For additional help, check the error logs at:
```
C:\xampp\apache\logs\error.log
```

Look for entries containing "SMS:" or "SMS Debug:" for detailed information about each SMS attempt.
