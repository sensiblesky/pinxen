# API Monitor Implementation Plan

## Overview

API Monitor is a new monitoring service that validates API endpoints by:
- Checking HTTP status codes
- Validating response format (JSON, XML, etc.)
- Comparing actual response with expected response
- Detecting API downtime or unexpected responses

## Architecture Decision

**✅ Create as Separate Service** (like Uptime, DNS, SSL monitors)

### Why Separate?
1. Different validation logic (response comparison vs. just uptime)
2. Different data structure (expected response format, validation rules)
3. Different UI/UX (response comparison view, format selector)
4. Follows existing pattern (each service has own table/model/controller)
5. Easier to maintain and extend independently

## Database Structure

### 1. Main Table: `monitors_service_api`

```sql
CREATE TABLE monitors_service_api (
    id BIGINT PRIMARY KEY,
    uid VARCHAR(36) UNIQUE,
    user_id BIGINT,
    name VARCHAR(255),
    url VARCHAR(500),
    
    -- Request Configuration
    request_method VARCHAR(10) DEFAULT 'GET',
    basic_auth_username VARCHAR(255) NULL,
    basic_auth_password VARCHAR(255) NULL,
    custom_headers JSON NULL,
    cache_buster BOOLEAN DEFAULT false,
    
    -- Response Validation
    expected_status_code INT DEFAULT 200,
    expected_response_format ENUM('json', 'xml', 'text', 'html') DEFAULT 'json',
    expected_response_body TEXT NULL, -- Expected JSON/XML structure
    response_validation_type ENUM('exact', 'schema', 'contains', 'regex') DEFAULT 'schema',
    response_validation_rules JSON NULL, -- Validation rules (JSON schema, XPath, etc.)
    
    -- Monitoring Settings
    check_interval INT DEFAULT 5, -- Minutes
    timeout INT DEFAULT 30, -- Seconds
    check_ssl BOOLEAN DEFAULT true,
    is_active BOOLEAN DEFAULT true,
    
    -- Status
    status ENUM('up', 'down', 'unexpected') DEFAULT 'unknown',
    last_checked_at TIMESTAMP NULL,
    next_check_at TIMESTAMP NULL,
    
    -- Maintenance
    maintenance_start_time TIME NULL,
    maintenance_end_time TIME NULL,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

### 2. Checks Table: `api_monitor_checks`

```sql
CREATE TABLE api_monitor_checks (
    id BIGINT PRIMARY KEY,
    api_monitor_id BIGINT,
    status ENUM('up', 'down', 'unexpected'),
    response_time INT, -- milliseconds
    status_code INT,
    response_format VARCHAR(20), -- json, xml, text, html
    response_body TEXT, -- Actual response (truncated if too long)
    expected_response_match BOOLEAN NULL, -- Did response match expected?
    validation_errors JSON NULL, -- Validation errors if mismatch
    error_message TEXT NULL,
    checked_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 3. Alerts Table: `api_monitor_alerts`

```sql
CREATE TABLE api_monitor_alerts (
    id BIGINT PRIMARY KEY,
    api_monitor_id BIGINT,
    alert_type ENUM('down', 'unexpected', 'recovery'),
    message TEXT,
    communication_channel VARCHAR(50),
    sent_at TIMESTAMP NULL,
    status ENUM('pending', 'sent', 'failed'),
    error_message TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Models

### 1. ApiMonitor Model
- Location: `app/Models/ApiMonitor.php`
- Relationships: User, Checks, Alerts
- Features: Soft deletes, UUID generation

### 2. ApiMonitorCheck Model
- Location: `app/Models/ApiMonitorCheck.php`
- Stores check results with validation details

### 3. ApiMonitorAlert Model
- Location: `app/Models/ApiMonitorAlert.php`
- Stores alert history

## Services

### 1. ApiValidationService
- Location: `app/Services/ApiValidationService.php`
- Responsibilities:
  - Parse response (JSON, XML, text)
  - Validate against expected format
  - Compare responses (exact, schema, contains, regex)
  - Return validation results

### 2. ApiMonitorCheckService
- Location: `app/Services/ApiMonitorCheckService.php`
- Responsibilities:
  - Make HTTP request to API
  - Parse response
  - Validate response
  - Determine status (up/down/unexpected)
  - Store check results

## Jobs

### ApiMonitorCheckJob
- Location: `app/Jobs/ApiMonitorCheckJob.php`
- Queue: `api-checks`
- Responsibilities:
  - Load monitor
  - Call ApiMonitorCheckService
  - Store results
  - Trigger alerts if needed
  - Update monitor status

## Controllers

### ApiMonitorController
- Location: `app/Http/Controllers/ApiMonitorController.php`
- Routes: `/api-monitors`
- Actions:
  - `index()` - List all API monitors
  - `create()` - Show create form
  - `store()` - Save new monitor
  - `show()` - Display monitor details with validation history
  - `edit()` - Show edit form
  - `update()` - Update monitor
  - `destroy()` - Delete monitor

## Views

### Directory: `resources/views/client/api-monitors/`

1. **index.blade.php** - List all API monitors
2. **create.blade.php** - Create new API monitor form
3. **edit.blade.php** - Edit API monitor form
4. **show.blade.php** - Monitor details with:
   - Response comparison view
   - Validation history
   - Response format indicators
   - Validation error details

## Features

### Response Format Support
- **JSON**: Validate structure, schema, or exact match
- **XML**: Validate structure, XPath queries
- **Text**: Exact match, contains, regex
- **HTML**: Contains check, regex

### Validation Types
1. **Exact Match**: Response must exactly match expected
2. **Schema Validation**: Validate against JSON Schema or XML Schema
3. **Contains**: Response must contain specified text/keys
4. **Regex**: Response must match regex pattern

### Status Types
- **up**: API is responding correctly with expected format
- **down**: API is not responding or returning error status
- **unexpected**: API is responding but with unexpected format/content

## Integration Points

### 1. Scheduler Command
Update `CheckAllMonitorsCommand` to include API monitors:
```php
'api' => [
    'name' => 'API Monitoring',
    'model' => ApiMonitor::class,
    'job' => ApiMonitorCheckJob::class,
],
```

### 2. Monitoring Service Seeder
Add API monitor service to `MonitoringServiceSeeder`:
```php
[
    'key' => 'api',
    'name' => 'API Monitoring',
    'description' => 'Monitor API endpoints and validate response format and content',
    'category' => 'core',
    'icon' => 'ri-code-s-slash-line',
    'is_active' => true,
    'order' => 2,
],
```

### 3. Routes
Add to `routes/client.php`:
```php
Route::resource('api-monitors', ApiMonitorController::class)
    ->parameters(['api-monitors' => 'apiMonitor']);
```

## Implementation Steps

1. ✅ Create database migrations
2. ✅ Create models (ApiMonitor, ApiMonitorCheck, ApiMonitorAlert)
3. ✅ Create services (ApiValidationService, ApiMonitorCheckService)
4. ✅ Create job (ApiMonitorCheckJob)
5. ✅ Create controller (ApiMonitorController)
6. ✅ Create views (index, create, edit, show)
7. ✅ Update scheduler command
8. ✅ Add to monitoring service seeder
9. ✅ Add routes
10. ✅ Update start-all.sh to include api-checks queue

## Example Use Cases

### 1. JSON API Validation
- Endpoint: `https://api.example.com/users`
- Expected: JSON with `status: "success"` and `data` array
- Validation: Schema validation

### 2. XML API Validation
- Endpoint: `https://api.example.com/feed.xml`
- Expected: XML with specific structure
- Validation: XPath validation

### 3. Text Response Validation
- Endpoint: `https://api.example.com/health`
- Expected: Response contains "OK"
- Validation: Contains check

## Next Steps

1. Review and approve this plan
2. Start with database migrations
3. Implement core validation service
4. Build UI for creating/editing monitors
5. Implement check job and scheduler integration
6. Add response comparison view
7. Test with various API formats


