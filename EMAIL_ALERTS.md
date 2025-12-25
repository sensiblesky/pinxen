# Standard Email Alert System

## Overview

The monitoring system uses a **standard email template** that works for **all monitoring services** (uptime, DNS, SSL, API, etc.). Emails are sent using **SMTP configuration from the database** (configured in `/panel/comm-channels`).

## Architecture

### Components

1. **`MonitorAlertService`** (`app/Services/MonitorAlertService.php`)
   - Central service for sending alerts
   - Handles all communication channels (email, SMS, WhatsApp, Telegram, Discord)
   - Uses database SMTP configuration
   - Creates alert records in database

2. **`MonitorAlertMail`** (`app/Mail/MonitorAlertMail.php`)
   - Laravel Mailable class
   - Standardizes email structure
   - Works for all monitoring service types

3. **Email Template** (`resources/views/emails/monitor-alert.blade.php`)
   - Single, reusable template
   - Responsive HTML design
   - Displays monitor details, status, response time, errors
   - Works for all service types

4. **`MailService`** (`app/Services/MailService.php`)
   - Retrieves SMTP config from database
   - Configures Laravel mailer dynamically
   - Handles encrypted passwords

## How It Works

### 1. Monitor Status Change

When a monitor's status changes (up → down or down → up):

```php
// In MonitorCheckJob
MonitorAlertService::sendAlerts(
    $monitor,
    'down',  // or 'up', 'recovery'
    $message,
    'down',  // current status
    $responseTime,
    $statusCode,
    $errorMessage
);
```

### 2. Alert Service Processing

`MonitorAlertService::sendAlerts()`:
1. Gets enabled communication preferences for the monitor
2. For each preference (email, SMS, etc.):
   - Creates `MonitorAlert` record
   - Sends via appropriate channel
   - Updates alert status (sent/failed)

### 3. Email Sending

For email alerts:
1. Checks if SMTP is configured (`MailService::isSmtpConfigured()`)
2. Gets configured mailer from database (`MailService::getConfiguredMailer()`)
3. Creates `MonitorAlertMail` mailable
4. Sends email using database SMTP settings
5. Updates alert record with status

### 4. Email Template

The template displays:
- **Alert Type**: DOWN, UP, or RECOVERED (with emoji badges)
- **Monitor Information**: Name, service type, category, URL, check interval
- **Check Details**: Response time, HTTP status code, last checked time
- **Alert Message**: Custom message from the alert
- **Error Details**: If any errors occurred
- **Action Button**: Link to view monitor details

## SMTP Configuration

SMTP settings are stored in the `settings` table and configured via:
- **Admin Panel**: `/panel/comm-channels`
- **Settings Stored**:
  - `smtp_driver` (default: 'smtp')
  - `smtp_host`
  - `smtp_port`
  - `smtp_encryption` (tls, ssl, none)
  - `smtp_username`
  - `smtp_password` (encrypted)
  - `smtp_from_address`
  - `smtp_from_name`

## Usage Example

```php
// Send alert when monitor goes down
MonitorAlertService::sendAlerts(
    $monitor,
    'down',
    "Monitor '{$monitor->name}' is DOWN. URL: {$monitor->url}",
    'down',
    $responseTime,      // e.g., 5000 (ms)
    $statusCode,        // e.g., 500
    $errorMessage       // e.g., "Connection timeout"
);

// Send alert when monitor recovers
MonitorAlertService::sendAlerts(
    $monitor,
    'recovery',
    "Monitor '{$monitor->name}' is UP. Response time: {$result['response_time']}ms",
    'up',
    $responseTime,
    $statusCode,
    null
);
```

## Email Template Features

### Responsive Design
- Works on desktop and mobile
- Clean, professional layout
- Color-coded status badges

### Information Displayed
- Monitor name and type
- Service category
- URL (if applicable)
- Check interval
- Last checked timestamp
- Response time (if available)
- HTTP status code (if applicable)
- Alert message
- Error details (if any)

### Status Badges
- **DOWN**: Red background, ⚠️ icon
- **UP**: Green background, ✅ icon
- **RECOVERED**: Blue background, 🔄 icon

## Database Schema

### `monitor_alerts` Table
- `id`: Alert ID
- `monitor_id`: Foreign key to monitors
- `alert_type`: 'down', 'up', or 'recovery'
- `message`: Alert message text
- `communication_channel`: 'email', 'sms', 'whatsapp', 'telegram', 'discord'
- `sent_at`: Timestamp when sent
- `status`: 'pending', 'sent', or 'failed'
- `error_message`: Error details if failed

### `monitor_communication_preferences` Table
- `monitor_id`: Foreign key to monitors
- `communication_channel`: Channel type
- `channel_value`: Email address, phone number, etc.
- `is_enabled`: Whether channel is active

## Future Enhancements

### Other Channels (TODO)
- **SMS**: Integrate SMS provider (Twilio, etc.)
- **WhatsApp**: WhatsApp Business API
- **Telegram**: Telegram Bot API
- **Discord**: Discord Webhook API

### Template Customization
- Allow admins to customize email template
- Support HTML/text versions
- Add branding/logo customization

## Testing

### Test Email Sending
```bash
php artisan tinker
>>> $monitor = \App\Models\Monitor::first();
>>> \App\Services\MonitorAlertService::sendAlerts(
    $monitor,
    'down',
    'Test alert message',
    'down',
    5000,
    500,
    'Test error message'
);
```

### Verify SMTP Configuration
```bash
php artisan tinker
>>> \App\Services\MailService::isSmtpConfigured()
```

## Troubleshooting

### Emails Not Sending

1. **Check SMTP Configuration**:
   - Go to `/panel/comm-channels`
   - Verify SMTP settings are saved
   - Test connection

2. **Check Logs**:
   ```bash
   tail -f storage/logs/laravel.log | grep -i "alert\|smtp\|mail"
   ```

3. **Check Alert Status**:
   ```sql
   SELECT * FROM monitor_alerts WHERE status = 'failed';
   ```

4. **Verify Queue Worker**:
   - Ensure queue worker is running
   - Check if emails are queued

### Common Issues

- **"SMTP is not configured"**: Configure SMTP in `/panel/comm-channels`
- **"Failed to send"**: Check SMTP credentials, firewall, port
- **Emails in spam**: Configure SPF/DKIM records for your domain

## Benefits

✅ **Single Template**: One template for all monitoring services  
✅ **Database SMTP**: No hardcoded credentials  
✅ **Scalable**: Works for 1M+ monitors  
✅ **Extensible**: Easy to add new channels  
✅ **Trackable**: All alerts logged in database  
✅ **Professional**: Clean, responsive email design





