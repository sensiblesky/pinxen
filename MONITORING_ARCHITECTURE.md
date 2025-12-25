# Monitoring Services Architecture Decision

## Current Architecture

**Single Table Approach** with JSON configuration:
- `monitors` table stores all monitor types
- `service_config` JSON field stores service-specific settings
- `monitoring_service_id` links to service definition
- Unified relationships (checks, alerts, etc.)

## Recommendation: **Hybrid Approach** (Best of Both Worlds)

### ✅ Keep Single `monitors` Table (Base Structure)

**Why:**
1. **Unified Management**: All monitors share common fields (name, status, check_interval, user_id, etc.)
2. **Simplified Relationships**: One table for checks, alerts, communication preferences
3. **Easy Queries**: "Get all monitors for user" is simple
4. **Flexibility**: Add new services without migrations
5. **Scalability**: Works well for 1M+ records with proper indexing

### ✅ Use JSON `service_config` for Flexible Settings

**Why:**
1. **Different Requirements**: DNS needs `record_type`, WHOIS needs `domain`, SSL needs `port`
2. **No Schema Changes**: Add new services without altering table structure
3. **Future-Proof**: Easy to extend for new monitoring types

### ✅ Add Service-Specific Tables ONLY When Needed

**When to create separate tables:**
1. **Complex Relationships**: If a service needs its own related data
   - Example: DNS monitoring might need `dns_records` table to track historical DNS changes
2. **Performance-Critical Queries**: If you need to query/filter by service-specific fields frequently
   - Example: "Find all DNS monitors checking A records" - if this is common, add indexed columns
3. **Large Service-Specific Data**: If a service generates lots of unique data
   - Example: SSL certificate details, WHOIS history

## Recommended Architecture

### Core Structure (Keep Current)

```sql
monitors
├── id
├── uid
├── user_id
├── monitoring_service_id  -- Links to service type
├── service_category_id
├── name
├── status (up/down/unknown)
├── is_active
├── check_interval
├── timeout
├── service_config (JSON)  -- Service-specific settings
├── last_checked_at
└── timestamps
```

### Service-Specific Extensions (Add Only When Needed)

**Option 1: Service-Specific Config Tables** (Recommended for complex services)

```sql
-- Only create when service needs complex, queryable data
dns_monitor_configs
├── monitor_id (FK to monitors)
├── record_type (A, AAAA, MX, etc.) -- Indexed
├── expected_value
├── check_nameservers
└── timestamps

ssl_monitor_configs
├── monitor_id (FK to monitors)
├── port
├── check_expiry_days
├── alert_before_expiry
└── timestamps
```

**Option 2: Polymorphic Relationships** (For service-specific data)

```sql
-- Generic table for service-specific data
monitor_service_data
├── id
├── monitor_id (FK)
├── service_type (dns, ssl, whois, etc.)
├── data (JSON) -- Flexible storage
└── timestamps
```

## Implementation Strategy

### Phase 1: Current Approach (Keep It) ✅

**For Most Services:**
- Use `monitors` table with `service_config` JSON
- Works for: Uptime, Basic DNS, Basic SSL, WHOIS, API monitoring

**Example:**
```php
// DNS Monitor
$monitor->service_config = [
    'domain' => 'example.com',
    'record_type' => 'A',
    'expected_value' => '192.0.2.1',
    'nameservers' => ['ns1.example.com']
];

// SSL Monitor
$monitor->service_config = [
    'domain' => 'example.com',
    'port' => 443,
    'check_expiry' => true,
    'alert_days_before' => 30
];
```

### Phase 2: Add Service-Specific Tables (When Needed)

**Create separate tables only when:**
1. You need to query by service-specific fields frequently
2. Service has complex relationships
3. Performance requires indexed columns

**Example:**
```php
// If DNS monitoring needs to track historical record changes
Schema::create('dns_monitor_records', function (Blueprint $table) {
    $table->id();
    $table->foreignId('monitor_id')->constrained();
    $table->string('record_type');
    $table->text('record_value');
    $table->timestamp('detected_at');
    $table->index(['monitor_id', 'record_type']);
});
```

## Comparison Table

| Aspect | Single Table + JSON | Separate Tables | Hybrid (Recommended) |
|--------|---------------------|-----------------|---------------------|
| **Schema Complexity** | ✅ Simple | ❌ Complex | ✅ Moderate |
| **Adding New Services** | ✅ No migration | ❌ Needs migration | ✅ No migration (usually) |
| **Query Performance** | ⚠️ Good (with indexes) | ✅ Excellent | ✅ Excellent |
| **Type Safety** | ⚠️ JSON validation | ✅ Database constraints | ✅ Hybrid validation |
| **Flexibility** | ✅ Very flexible | ❌ Rigid | ✅ Flexible |
| **Maintenance** | ✅ Easy | ❌ More complex | ✅ Moderate |
| **Scalability** | ✅ Excellent | ✅ Excellent | ✅ Excellent |
| **Code Reusability** | ✅ High | ❌ Lower | ✅ High |

## Best Practices

### 1. Use JSON for Simple Configs ✅

```php
// Good: Simple service configs in JSON
$monitor->service_config = [
    'url' => 'https://example.com',
    'expected_status_code' => 200,
    'keyword_present' => 'Welcome'
];
```

### 2. Create Separate Tables for Complex Data ✅

```php
// Good: Complex relationships get their own table
// Example: DNS record history tracking
$dnsRecord = DnsMonitorRecord::create([
    'monitor_id' => $monitor->id,
    'record_type' => 'A',
    'record_value' => '192.0.2.1',
    'detected_at' => now()
]);
```

### 3. Use Indexed JSON Columns (MySQL 5.7+, MariaDB 10.2+)

```php
// Add generated columns for frequently queried JSON fields
Schema::table('monitors', function (Blueprint $table) {
    $table->string('dns_domain')->nullable()
        ->storedAs('JSON_UNQUOTE(JSON_EXTRACT(service_config, "$.domain"))')
        ->index();
});
```

### 4. Service-Specific Models (Optional)

```php
// Create service-specific models that extend Monitor
class DnsMonitor extends Monitor
{
    protected $table = 'monitors';
    
    public function getDomainAttribute()
    {
        return $this->service_config['domain'] ?? null;
    }
    
    public function getRecordTypeAttribute()
    {
        return $this->service_config['record_type'] ?? null;
    }
}
```

## Recommended Migration Path

### Step 1: Keep Current Structure ✅
- Continue using `monitors` table with `service_config` JSON
- This works for 80% of services

### Step 2: Add Service-Specific Helpers (When Needed)
- Create service-specific model methods
- Add validation rules per service
- Use JSON schema validation

### Step 3: Create Separate Tables (Only If Required)
- Only when you need:
  - Complex relationships
  - Frequent queries on service-specific fields
  - Performance optimization

## Example: DNS Monitoring Implementation

### Current Approach (JSON) ✅

```php
// Monitor creation
$monitor = Monitor::create([
    'monitoring_service_id' => $dnsService->id,
    'name' => 'DNS Check for example.com',
    'service_config' => [
        'domain' => 'example.com',
        'record_type' => 'A',
        'expected_value' => '192.0.2.1',
        'nameservers' => ['8.8.8.8']
    ]
]);

// Query (works fine for most cases)
$dnsMonitors = Monitor::where('monitoring_service_id', $dnsService->id)
    ->whereJsonContains('service_config->domain', 'example.com')
    ->get();
```

### If You Need Separate Table (Only If Required)

```php
// Only create if you need to:
// 1. Track historical DNS record changes
// 2. Query by record type frequently
// 3. Store complex DNS data

Schema::create('dns_monitor_configs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('monitor_id')->constrained()->onDelete('cascade');
    $table->string('domain')->index();
    $table->enum('record_type', ['A', 'AAAA', 'MX', 'CNAME', 'TXT'])->index();
    $table->text('expected_value')->nullable();
    $table->json('nameservers')->nullable();
    $table->timestamps();
});
```

## Final Recommendation

### ✅ **Use Single Table + JSON Approach** (Current)

**Reasons:**
1. **Scalability**: Works for 1M+ monitors
2. **Flexibility**: Easy to add new services
3. **Simplicity**: Less code, easier maintenance
4. **Performance**: Good with proper indexing

### ✅ **Add Service-Specific Tables Only When:**
1. You need to query service-specific fields frequently (add indexed columns)
2. Service has complex relationships (create related tables)
3. Performance requires it (measure first, optimize later)

### ✅ **Best Practices:**
1. Validate JSON structure in models/controllers
2. Use service-specific model methods for access
3. Add database indexes on frequently queried JSON paths
4. Consider generated columns for common queries (MySQL 5.7+)

## Conclusion

**Keep your current architecture!** It's well-designed for scalability and flexibility. Only add separate tables when you have a specific, measurable need (performance, complex relationships, etc.). 

The JSON approach with a single table is the right choice for a growing application with many service types.





