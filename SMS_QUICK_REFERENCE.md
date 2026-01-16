# SMS Integration Quick Reference

## Diagnostic Tools

| Tool | URL | Purpose |
|------|-----|---------|
| Status Check | `/integrations/paypal/check_status.php` | Quick overview of SMS configuration |
| Configuration Test | `/integrations/paypal/test_sms.php` | Detailed configuration and gateway test |
| SMS Test | `/integrations/paypal/test_sms_direct.php` | Send test SMS to verify gateway |
| Booking Diagnostic | `/integrations/paypal/diagnostic.php?booking_id=X` | View SMS details for a booking |

## Quick Start

1. **Check Status**
   ```
   http://localhost/airlyft/integrations/paypal/check_status.php
   ```
   Look for ✓ marks, fix any ✗ issues

2. **Test SMS Gateway**
   ```
   http://localhost/airlyft/integrations/paypal/test_sms_direct.php
   ```
   Enter a phone number and send test SMS

3. **Debug Booking Issue**
   ```
   http://localhost/airlyft/integrations/paypal/diagnostic.php?booking_id=44
   ```
   Replace 44 with your booking ID

## Most Common Issues

### SMS Not Sending
1. Run `check_status.php` to verify configuration
2. Run `test_sms_direct.php` to test gateway
3. Run `diagnostic.php` to check booking phone number

### User Phone Empty
Phone must be in Users table. Add it with:
```sql
UPDATE Users SET phone = '+639123456789' WHERE user_id = X;
```

### Gateway Not Responding
- Verify SMS gateway URL in .env is correct: `http://10.187.225.161:8080/messages`
- Check if SMS gateway server is running
- Check network connectivity

### Phone Format Invalid
- Phone must be Philippine format: 09XXXXXXXXX or +639XXXXXXXXX
- System converts to +63 format automatically

## Configuration

### .env File Location
```
c:\xampp\htdocs\airlyft\.env
```

### Required Settings
```
SMS_GATEWAY_USERNAME=sms
SMS_GATEWAY_PASSWORD=8m2fKZur
SMS_GATEWAY_API=http://10.187.225.161:8080/messages
```

## Error Log Location
```
C:\xampp\apache\logs\error.log
```

Search for "SMS:" messages to debug issues.

## SMS Flow

```
PayPal Payment → Confirmation Email → SMS Inserted (Pending) → Send to Gateway → Update to Sent
```

## Database Tables

### smsnotification
```
- sms_id (INT, Primary Key)
- booking_id (INT)
- message (TEXT)
- sms_status (VARCHAR) - Pending/Sent/Failed
```

### Users (phone field)
```
- phone (VARCHAR(20)) - Must be populated
```

## Booking Status

Check SMS status for a booking:
```sql
SELECT sms_id, booking_id, sms_status, message 
FROM smsnotification 
WHERE booking_id = 44 
ORDER BY sms_id DESC;
```

## Testing Phone Numbers

These formats all work:
- `09123456789` (local format, 10 digits)
- `639123456789` (with country code, no +)
- `+639123456789` (international format)
- `09-123-456-789` (with hyphens, removed automatically)

All convert to: `+639123456789`

## Success Indicators

SMS is working if:
- ✓ Status Check shows all ✓
- ✓ Test SMS arrives on your phone
- ✓ Diagnostic shows SMS status as "Sent"
- ✓ Error logs show "SMS SUCCESS" messages

## Common Log Messages

### Success
```
SMS: Confirmation SMS sent successfully to +639123456789 for booking #44
SMS SUCCESS: Sent to +639123456789
```

### Failure
```
SMS: No valid phone number found for booking #44
SMS FAILED: No response from gateway
SMS WARNING: Phone format validation failed
```

## Support Resources

1. **Full Guide:** `/airlyft/SMS_DEBUG_GUIDE.md`
2. **Changes Summary:** `/airlyft/SMS_IMPROVEMENTS_SUMMARY.md`
3. **Error Logs:** `C:\xampp\apache\logs\error.log`

## Quick Checklist

- [ ] .env file exists with SMS credentials
- [ ] SMS gateway URL is correct (10.187.225.161:8080)
- [ ] Database connection working
- [ ] smsnotification table exists
- [ ] User phone is populated in Users table
- [ ] Test SMS sends successfully
- [ ] Booking SMS status shows "Sent" not "Failed"
