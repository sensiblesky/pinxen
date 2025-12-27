# New Monitoring Architecture - Service-Specific Tables

## Overview

The monitoring system has been restructured to use **separate tables for each service type** instead of a single unified table. This provides:
- Better data organization
- Service-specific statistics
- Easier management and queries
- Independent scaling per service

## New Structure

### Tables Created

1. **`monitors_service_uptime`** - Stores uptime monitors
   - `id`, `uid`, `user_id`
   - `name`, `url`
   - `check_interval`, `timeout`, `expected_status_code`
   - `keyword_present`, `keyword_absent`, `check_ssl`
   - `is_active`, `status`, `last_checked_at`
   - `timestamps`, `deleted_at` (soft deletes)

2. **`uptime_monitor_checks`** - Stores check results for uptime monitors
   - `id`, `uptime_monitor_id`
   - `status`, `response_time`, `status_code`, `error_message`
   - `checked_at`, `timestamps`

3. **`uptime_monitor_alerts`** - Stores alerts for uptime monitors
   - `id`, `uptime_monitor_id`
   - `alert_type`, `message`, `communication_channel`
   - `sent_at`, `status`, `error_message`
   - `timestamps`

### Models Created

1. **`UptimeMonitor`** - Main model for uptime monitors
2. **`UptimeMonitorCheck`** - Model for check results
3. **`UptimeMonitorAlert`** - Model for alerts

### Controller Created

- **`UptimeMonitorController`** - Full CRUD operations
  - `index()` - List all uptime monitors
  - `create()` - Show create form
  - `store()` - Save new monitor
  - `show()` - Display monitor details with charts
  - `edit()` - Show edit form
  - `update()` - Update monitor
  - `destroy()` - Delete monitor (soft delete)

### Routes Created

```php
Route::resource('uptime-monitors', UptimeMonitorController::class)
    ->parameters(['uptime-monitors' => 'uptimeMonitor']);
```

Routes available:
- `GET /uptime-monitors` - List monitors
- `GET /uptime-monitors/create` - Create form
- `POST /uptime-monitors` - Store new monitor
- `GET /uptime-monitors/{uptimeMonitor}` - Show monitor
- `GET /uptime-monitors/{uptimeMonitor}/edit` - Edit form
- `PUT /uptime-monitors/{uptimeMonitor}` - Update monitor
- `DELETE /uptime-monitors/{uptimeMonitor}` - Delete monitor

### Views Created

1. **`uptime-monitors/index.blade.php`** - List all monitors
2. **`uptime-monitors/create.blade.php`** - Create form
3. **`uptime-monitors/edit.blade.php`** - Edit form
4. **`uptime-monitors/show.blade.php`** - Monitor details with charts

### Navigation Updated

- **Monitoring** menu now has:
  - **Web Monitoring** (dropdown)
    - **Uptime** ← Currently active
    - (Future: DNS, Domain Expiration, etc.)
  - **Server Monitoring** (Coming Soon)

## Next Steps

### 1. Create Uptime Check Job

Create a job to check uptime monitors:

```bash
php artisan make:job UptimeMonitorCheckJob
```

This job should:
- Perform HTTP/HTTPS checks
- Use `MonitorHttpService` for anti-blocking
- Record results in `uptime_monitor_checks`
- Update monitor status
- Send alerts if status changes

### 2. Create Scheduler Command

```bash
php artisan make:command CheckUptimeMonitorsCommand
```

This command should:
- Find due uptime monitors
- Dispatch `UptimeMonitorCheckJob` for each

### 3. Schedule the Command

Add to `routes/console.php`:
```php
Schedule::command('uptime-monitors:check')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
```

### 4. Create Alert Service

Create `UptimeMonitorAlertService` to:
- Send alerts via email (using `MonitorAlertService`)
- Store alerts in `uptime_monitor_alerts` table

### 5. Future Services

When ready, create similar structure for:
- **DNS Monitoring**: `monitors_service_dns`, `dns_monitor_checks`, `dns_monitor_alerts`
- **Domain Expiration**: `monitors_service_domain_expiration`, etc.
- **SSL Monitoring**: `monitors_service_ssl`, etc.

## Benefits of This Architecture

✅ **Service-Specific Data**: Each service has its own optimized table structure  
✅ **Better Statistics**: Easy to query service-specific metrics  
✅ **Independent Scaling**: Each service can be optimized separately  
✅ **Cleaner Code**: Service-specific logic is isolated  
✅ **Easier Maintenance**: Changes to one service don't affect others  
✅ **Better Performance**: Indexed columns instead of JSON queries  

## Migration Path

1. ✅ Created new uptime monitoring structure
2. ⏳ Create check job and scheduler
3. ⏳ Test uptime monitoring
4. ⏳ Create DNS monitoring (next service)
5. ⏳ Create Domain Expiration monitoring
6. ⏳ (Optional) Migrate old monitors data if needed
7. ⏳ Remove old monitoring system

## Current Status

✅ **Database Tables**: Created and migrated  
✅ **Models**: Created with relationships  
✅ **Controller**: Full CRUD implemented  
✅ **Routes**: Resource routes configured  
✅ **Views**: Index, Create, Edit, Show pages created  
✅ **Navigation**: Updated with dropdown menu  
⏳ **Check Job**: Needs to be created  
⏳ **Scheduler**: Needs to be configured  
⏳ **Alert Service**: Needs to be created  






