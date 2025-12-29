# Server Offline Detection System

## Overview

The system determines if a server is **online**, **warning**, or **offline** based on when the agent last sent data (`last_seen_at` timestamp).

## How It Works

### 1. **Data Collection**
- When the agent sends stats to the API endpoint (`/api/v1/server-stats`), the `last_seen_at` field is automatically updated to the current timestamp
- This happens in `ServerStatsController@store` (line 158)

### 2. **Status Determination**

The system uses **three configurable thresholds**:

#### **Online Status** (Green)
- **Default**: Server seen within last **5 minutes**
- **Condition**: `last_seen_at` is within `online_threshold_minutes`
- **Meaning**: Server is actively sending data

#### **Warning Status** (Yellow)
- **Default**: Server seen within last **60 minutes** but exceeds online threshold
- **Condition**: `last_seen_at` is within `warning_threshold_minutes` but exceeds `online_threshold_minutes`
- **Meaning**: Server hasn't sent data recently, but might still be operational

#### **Offline Status** (Red)
- **Default**: Server not seen within last **120 minutes**
- **Condition**: `last_seen_at` exceeds `offline_threshold_minutes`
- **Meaning**: Server is likely offline or the agent is not running

### 3. **Configurable Thresholds**

Each server can have custom thresholds:

- **`online_threshold_minutes`**: Minutes since `last_seen_at` to consider server online (default: 5)
- **`warning_threshold_minutes`**: Minutes since `last_seen_at` to show warning (default: 60)
- **`offline_threshold_minutes`**: Minutes since `last_seen_at` to consider offline (default: 120)

**If thresholds are not set**, the system uses defaults.

## Example Scenarios

### Scenario 1: Normal Operation
- Agent sends stats every 60 seconds
- `last_seen_at` = 30 seconds ago
- **Status**: ✅ **Online** (within 5-minute threshold)

### Scenario 2: Agent Stopped
- Agent was sending stats every 60 seconds
- Last stat received 10 minutes ago
- **Status**: ⚠️ **Warning** (exceeds 5 min, but within 60 min)

### Scenario 3: Server Down
- Agent was sending stats every 60 seconds
- Last stat received 3 hours ago
- **Status**: ❌ **Offline** (exceeds 120-minute threshold)

### Scenario 4: Custom Thresholds
- Server configured with:
  - `online_threshold_minutes` = 10
  - `warning_threshold_minutes` = 30
  - `offline_threshold_minutes` = 60
- Last stat received 20 minutes ago
- **Status**: ⚠️ **Warning** (exceeds 10 min, but within 30 min)

## Implementation

### ServerStatusService

The `ServerStatusService` class handles all status logic:

```php
use App\Services\ServerStatusService;

$statusService = app(ServerStatusService::class);

// Get detailed status
$status = $statusService->determineStatus($server);
// Returns: ['status' => 'online'|'warning'|'offline', 'last_seen' => Carbon, 'minutes_ago' => int, 'reason' => string]

// Quick checks
$isOnline = $statusService->isOnline($server);
$isOffline = $statusService->isOffline($server);

// Check if should trigger alert
$shouldAlert = $statusService->shouldTriggerOfflineAlert($server);
```

### Server Model Methods

The `Server` model provides convenient methods:

```php
// Check status
$server->isOnline();  // bool
$server->isOffline(); // bool
$server->getStatus(); // array with detailed info

// UI helpers
$server->getStatusBadgeClass(); // CSS class for badge
$server->getStatusText();       // "Online", "Warning", or "Offline"

// Alert check
$server->shouldTriggerOfflineAlert(); // bool
```

## When is a Server Considered Offline?

A server is considered **offline** when:

1. **Never Seen**: `last_seen_at` is `null` (agent never sent data)
2. **Exceeds Threshold**: `last_seen_at` is older than `offline_threshold_minutes` (default: 120 minutes)
3. **Inactive**: `is_active` is `false`

## Alert Triggering Logic

The `shouldTriggerOfflineAlert()` method uses smart logic:

1. **Checks if server is active** - Inactive servers don't trigger alerts
2. **Checks if server was ever seen** - Never-seen servers don't trigger alerts (might not be set up)
3. **Estimates check interval** - Analyzes recent stats to determine expected check frequency
4. **Uses 2x interval rule** - Server is considered offline if not seen in **2x the expected check interval**

**Example:**
- Agent sends stats every 60 seconds
- Expected check interval: 60 seconds
- Offline threshold: 60 × 2 = 120 seconds
- If `last_seen_at` is > 120 seconds ago → **Trigger alert**

This prevents false positives from temporary network issues.

## Configuration

### Per-Server Configuration

Set thresholds when creating or editing a server:

1. Go to **Server Monitoring** → **Create/Edit Server**
2. Scroll to **"Status Detection Thresholds"** section
3. Set custom thresholds (or leave empty for defaults)

### System Defaults

If not configured per-server, defaults are used:

- **Online**: 5 minutes
- **Warning**: 60 minutes  
- **Offline**: 120 minutes

## Best Practices

### For Agents Sending Every 60 Seconds:
- **Online Threshold**: 5-10 minutes (allows for temporary delays)
- **Warning Threshold**: 15-30 minutes (indicates potential issues)
- **Offline Threshold**: 30-60 minutes (definitely offline)

### For Agents Sending Every 5 Minutes:
- **Online Threshold**: 10-15 minutes
- **Warning Threshold**: 30-60 minutes
- **Offline Threshold**: 60-120 minutes

### For Agents Sending Every 15 Minutes:
- **Online Threshold**: 30-45 minutes
- **Warning Threshold**: 60-90 minutes
- **Offline Threshold**: 120-180 minutes

## Status Flow

```
Agent Sends Stats
    ↓
last_seen_at Updated
    ↓
Status Checked
    ↓
┌─────────────────────────────────┐
│ Is last_seen_at within          │
│ online_threshold_minutes?        │
└─────────────────────────────────┘
    ↓ Yes                    ↓ No
[Online ✅]          ┌──────────────────────┐
                     │ Is last_seen_at      │
                     │ within warning_      │
                     │ threshold_minutes?   │
                     └──────────────────────┘
                          ↓ Yes        ↓ No
                    [Warning ⚠️]  [Offline ❌]
```

## Troubleshooting

### Server Shows Offline But Agent is Running

1. **Check agent configuration**: Verify `api_url` and `server_key` are correct
2. **Check agent logs**: Look for connection errors
3. **Check network**: Ensure agent can reach the API endpoint
4. **Check API key**: Verify API key is active and has correct scopes
5. **Check thresholds**: Verify offline threshold isn't too low

### Server Shows Online But Should Be Offline

1. **Check last_seen_at**: Verify it's being updated correctly
2. **Check thresholds**: Verify offline threshold is appropriate
3. **Check agent**: Agent might be sending data even if server is having issues

### False Positive Alerts

1. **Increase offline threshold**: Give more time before considering offline
2. **Use smart alert logic**: `shouldTriggerOfflineAlert()` uses 2x check interval rule
3. **Check agent interval**: Ensure agent is sending at expected intervals

## API Usage

### Check Server Status

```php
use App\Models\Server;
use App\Services\ServerStatusService;

$server = Server::find($id);
$statusService = app(ServerStatusService::class);

$status = $statusService->determineStatus($server);
// Returns: ['status' => 'online', 'last_seen' => Carbon, 'minutes_ago' => 2, 'reason' => '...']
```

### Check if Should Alert

```php
$shouldAlert = $server->shouldTriggerOfflineAlert();
if ($shouldAlert) {
    // Trigger alert logic here
}
```

## Future Enhancements

- **Heartbeat monitoring**: Dedicated heartbeat endpoint for faster detection
- **Multi-region checks**: Check from multiple locations
- **Network diagnostics**: Detect network issues vs server issues
- **Auto-adjust thresholds**: Learn from agent behavior patterns

