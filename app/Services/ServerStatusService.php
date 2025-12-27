<?php

namespace App\Services;

use App\Models\Server;
use Carbon\Carbon;

class ServerStatusService
{
    /**
     * Default thresholds (in minutes).
     * These can be overridden per server or via config.
     */
    private const DEFAULT_ONLINE_THRESHOLD = 5;      // Server is online if seen in last 5 minutes
    private const DEFAULT_WARNING_THRESHOLD = 60;   // Server is in warning if seen in last 60 minutes
    private const DEFAULT_OFFLINE_THRESHOLD = 120;  // Server is offline if not seen in last 120 minutes

    /**
     * Determine server status.
     * 
     * @param Server $server
     * @param int|null $onlineThresholdMinutes Custom online threshold (null = use default)
     * @param int|null $warningThresholdMinutes Custom warning threshold (null = use default)
     * @param int|null $offlineThresholdMinutes Custom offline threshold (null = use default)
     * @return array Returns ['status' => 'online'|'warning'|'offline', 'last_seen' => Carbon, 'minutes_ago' => int, 'reason' => string]
     */
    public function determineStatus(
        Server $server,
        ?int $onlineThresholdMinutes = null,
        ?int $warningThresholdMinutes = null,
        ?int $offlineThresholdMinutes = null
    ): array {
        // Use custom thresholds or defaults
        $onlineThreshold = $onlineThresholdMinutes ?? self::DEFAULT_ONLINE_THRESHOLD;
        $warningThreshold = $warningThresholdMinutes ?? self::DEFAULT_WARNING_THRESHOLD;
        $offlineThreshold = $offlineThresholdMinutes ?? self::DEFAULT_OFFLINE_THRESHOLD;

        // If server is not active, it's considered offline
        if (!$server->is_active) {
            return [
                'status' => 'offline',
                'last_seen' => $server->last_seen_at,
                'minutes_ago' => $server->last_seen_at ? now()->diffInMinutes($server->last_seen_at) : null,
                'reason' => 'Server is marked as inactive',
                'threshold_used' => 'inactive'
            ];
        }

        // If server has never been seen, it's offline
        if (!$server->last_seen_at) {
            return [
                'status' => 'offline',
                'last_seen' => null,
                'minutes_ago' => null,
                'reason' => 'Server has never sent data',
                'threshold_used' => 'never_seen'
            ];
        }

        $minutesAgo = now()->diffInMinutes($server->last_seen_at);
        $lastSeen = $server->last_seen_at;

        // Determine status based on thresholds
        if ($minutesAgo <= $onlineThreshold) {
            return [
                'status' => 'online',
                'last_seen' => $lastSeen,
                'minutes_ago' => $minutesAgo,
                'reason' => "Server was seen {$minutesAgo} minute(s) ago (within {$onlineThreshold} minute threshold)",
                'threshold_used' => 'online'
            ];
        } elseif ($minutesAgo <= $warningThreshold) {
            return [
                'status' => 'warning',
                'last_seen' => $lastSeen,
                'minutes_ago' => $minutesAgo,
                'reason' => "Server was seen {$minutesAgo} minute(s) ago (exceeds {$onlineThreshold} min threshold, but within {$warningThreshold} min warning threshold)",
                'threshold_used' => 'warning'
            ];
        } elseif ($minutesAgo <= $offlineThreshold) {
            return [
                'status' => 'offline',
                'last_seen' => $lastSeen,
                'minutes_ago' => $minutesAgo,
                'reason' => "Server was seen {$minutesAgo} minute(s) ago (exceeds {$warningThreshold} min warning threshold, but within {$offlineThreshold} min offline threshold)",
                'threshold_used' => 'offline_grace'
            ];
        } else {
            return [
                'status' => 'offline',
                'last_seen' => $lastSeen,
                'minutes_ago' => $minutesAgo,
                'reason' => "Server was seen {$minutesAgo} minute(s) ago (exceeds {$offlineThreshold} minute offline threshold)",
                'threshold_used' => 'offline'
            ];
        }
    }

    /**
     * Check if server is online.
     * 
     * @param Server $server
     * @param int|null $thresholdMinutes Custom threshold (null = use default)
     * @return bool
     */
    public function isOnline(Server $server, ?int $thresholdMinutes = null): bool
    {
        $status = $this->determineStatus($server, $thresholdMinutes);
        return $status['status'] === 'online';
    }

    /**
     * Check if server is offline.
     * 
     * @param Server $server
     * @param int|null $thresholdMinutes Custom threshold (null = use default)
     * @return bool
     */
    public function isOffline(Server $server, ?int $thresholdMinutes = null): bool
    {
        $status = $this->determineStatus($server, null, null, $thresholdMinutes);
        return $status['status'] === 'offline';
    }

    /**
     * Get human-readable status description.
     * 
     * @param Server $server
     * @return string
     */
    public function getStatusDescription(Server $server): string
    {
        $status = $this->determineStatus($server);
        
        switch ($status['status']) {
            case 'online':
                return "Online - Last seen {$status['minutes_ago']} minute(s) ago";
            case 'warning':
                return "Warning - Last seen {$status['minutes_ago']} minute(s) ago";
            case 'offline':
                if ($status['minutes_ago'] === null) {
                    return "Offline - Never seen";
                }
                return "Offline - Last seen {$status['minutes_ago']} minute(s) ago";
            default:
                return "Unknown";
        }
    }

    /**
     * Get status badge class for UI.
     * 
     * @param Server $server
     * @return string
     */
    public function getStatusBadgeClass(Server $server): string
    {
        $status = $this->determineStatus($server);
        
        return match($status['status']) {
            'online' => 'bg-success-transparent text-success',
            'warning' => 'bg-warning-transparent text-warning',
            'offline' => 'bg-danger-transparent text-danger',
            default => 'bg-secondary-transparent text-secondary'
        };
    }

    /**
     * Get status text for UI.
     * 
     * @param Server $server
     * @return string
     */
    public function getStatusText(Server $server): string
    {
        $status = $this->determineStatus($server);
        return ucfirst($status['status']);
    }

    /**
     * Check if server should trigger an offline alert.
     * This considers the server's check interval to avoid false positives.
     * 
     * @param Server $server
     * @param int|null $checkIntervalMinutes Expected check interval (if null, will estimate from stats)
     * @return bool
     */
    public function shouldTriggerOfflineAlert(Server $server, ?int $checkIntervalMinutes = null): bool
    {
        // If server is not active, don't alert
        if (!$server->is_active) {
            return false;
        }

        // If never seen, don't alert (server might not be set up yet)
        if (!$server->last_seen_at) {
            return false;
        }

        // Determine expected check interval
        if ($checkIntervalMinutes === null) {
            // Try to estimate from recent stats
            $recentStats = $server->stats()
                ->where('recorded_at', '>=', now()->subHours(24))
                ->orderBy('recorded_at', 'desc')
                ->limit(10)
                ->pluck('recorded_at');

            if ($recentStats->count() >= 2) {
                // Calculate average interval between stats
                $intervals = [];
                $dates = $recentStats->sort()->values();
                for ($i = 1; $i < $dates->count(); $i++) {
                    $intervals[] = $dates[$i]->diffInMinutes($dates[$i - 1]);
                }
                $checkIntervalMinutes = $intervals ? round(array_sum($intervals) / count($intervals)) : 60;
            } else {
                // Default to 60 minutes if we can't determine
                $checkIntervalMinutes = 60;
            }
        }

        // Server is considered offline if:
        // 1. It hasn't been seen in more than 2x the expected check interval
        // 2. This prevents false positives from temporary network issues
        $offlineThreshold = $checkIntervalMinutes * 2;
        $minutesAgo = now()->diffInMinutes($server->last_seen_at);

        return $minutesAgo > $offlineThreshold;
    }

    /**
     * Get time until server is considered offline.
     * 
     * @param Server $server
     * @param int|null $offlineThresholdMinutes
     * @return int|null Minutes until offline, or null if already offline
     */
    public function getMinutesUntilOffline(Server $server, ?int $offlineThresholdMinutes = null): ?int
    {
        if (!$server->last_seen_at) {
            return null; // Already offline
        }

        $threshold = $offlineThresholdMinutes ?? self::DEFAULT_OFFLINE_THRESHOLD;
        $minutesAgo = now()->diffInMinutes($server->last_seen_at);
        $minutesUntilOffline = $threshold - $minutesAgo;

        return $minutesUntilOffline > 0 ? $minutesUntilOffline : null;
    }
}

