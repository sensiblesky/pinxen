<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Server extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uid',
        'user_id',
        'api_key_id',
        'name',
        'server_key',
        'description',
        'hostname',
        'os_type',
        'os_version',
        'ip_address',
        'location',
        'is_active',
        'cpu_threshold',
        'memory_threshold',
        'disk_threshold',
        'online_threshold_minutes',
        'warning_threshold_minutes',
        'offline_threshold_minutes',
        'last_seen_at',
        'agent_installed_at',
        'agent_version',
        'machine_id',
        'system_uuid',
        'disk_uuid',
        'agent_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cpu_threshold' => 'decimal:2',
        'memory_threshold' => 'decimal:2',
        'disk_threshold' => 'decimal:2',
        'last_seen_at' => 'datetime',
        'agent_installed_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($server) {
            if (empty($server->uid)) {
                $server->uid = Str::uuid()->toString();
            }
            if (empty($server->server_key)) {
                $server->server_key = 'srv_' . Str::random(48); // 51 characters total
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'uid';
    }

    /**
     * Generate a new server key.
     */
    public static function generateServerKey(): string
    {
        return 'srv_' . Str::random(48);
    }

    /**
     * Get the user that owns the server.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the API key associated with this server.
     */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class, 'api_key_id');
    }

    /**
     * Get the stats for this server.
     */
    public function stats(): HasMany
    {
        return $this->hasMany(ServerStat::class)->orderBy('recorded_at', 'desc');
    }

    /**
     * Get the latest stat for this server.
     */
    public function latestStat()
    {
        return $this->hasOne(ServerStat::class)->latestOfMany('recorded_at');
    }

    /**
     * Check if server is online.
     * Uses ServerStatusService for configurable thresholds.
     */
    public function isOnline(): bool
    {
        $statusService = app(\App\Services\ServerStatusService::class);
        return $statusService->isOnline(
            $this,
            $this->online_threshold_minutes
        );
    }

    /**
     * Check if server is offline.
     */
    public function isOffline(): bool
    {
        $statusService = app(\App\Services\ServerStatusService::class);
        return $statusService->isOffline(
            $this,
            $this->offline_threshold_minutes
        );
    }

    /**
     * Get detailed status information.
     */
    public function getStatus(): array
    {
        $statusService = app(\App\Services\ServerStatusService::class);
        return $statusService->determineStatus(
            $this,
            $this->online_threshold_minutes,
            $this->warning_threshold_minutes,
            $this->offline_threshold_minutes
        );
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClass(): string
    {
        $statusService = app(\App\Services\ServerStatusService::class);
        return $statusService->getStatusBadgeClass($this);
    }

    /**
     * Get status text.
     */
    public function getStatusText(): string
    {
        $statusService = app(\App\Services\ServerStatusService::class);
        return $statusService->getStatusText($this);
    }

    /**
     * Check if server should trigger an offline alert.
     */
    public function shouldTriggerOfflineAlert(?int $checkIntervalMinutes = null): bool
    {
        $statusService = app(\App\Services\ServerStatusService::class);
        return $statusService->shouldTriggerOfflineAlert($this, $checkIntervalMinutes);
    }

    /**
     * Check if CPU usage exceeds threshold.
     */
    public function isCpuExceeded($currentUsage): bool
    {
        return $this->cpu_threshold !== null && $currentUsage > $this->cpu_threshold;
    }

    /**
     * Check if Memory usage exceeds threshold.
     */
    public function isMemoryExceeded($currentUsage): bool
    {
        return $this->memory_threshold !== null && $currentUsage > $this->memory_threshold;
    }

    /**
     * Check if Disk usage exceeds threshold.
     */
    public function isDiskExceeded($currentUsage): bool
    {
        return $this->disk_threshold !== null && $currentUsage > $this->disk_threshold;
    }
}
