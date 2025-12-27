<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class UptimeMonitor extends Model
{
    use SoftDeletes;

    protected $table = 'monitors_service_uptime';

    protected $fillable = [
        'uid',
        'user_id',
        'name',
        'url',
        'request_method',
        'basic_auth_username',
        'basic_auth_password',
        'custom_headers',
        'cache_buster',
        'maintenance_start_time',
        'maintenance_end_time',
        'check_interval',
        'timeout',
        'expected_status_code',
        'keyword_present',
        'keyword_absent',
        'check_ssl',
        'is_active',
        'confirmation_enabled',
        'confirmation_probes',
        'confirmation_threshold',
        'confirmation_retry_delay',
        'confirmation_max_retries',
        'last_checked_at',
        'next_check_at',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'check_ssl' => 'boolean',
        'cache_buster' => 'boolean',
        'confirmation_enabled' => 'boolean',
        'check_interval' => 'integer',
        'timeout' => 'integer',
        'expected_status_code' => 'integer',
        'confirmation_probes' => 'integer',
        'confirmation_threshold' => 'integer',
        'confirmation_retry_delay' => 'integer',
        'confirmation_max_retries' => 'integer',
        'custom_headers' => 'array',
        'maintenance_start_time' => 'datetime',
        'maintenance_end_time' => 'datetime',
        'last_checked_at' => 'datetime',
        'next_check_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($monitor) {
            if (empty($monitor->uid)) {
                $monitor->uid = Str::uuid()->toString();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(UptimeMonitorCheck::class, 'uptime_monitor_id')->orderBy('checked_at', 'desc');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(UptimeMonitorAlert::class, 'uptime_monitor_id')->orderBy('created_at', 'desc');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if monitor is currently in maintenance period.
     */
    public function isInMaintenancePeriod(): bool
    {
        // If no maintenance period is configured, always return false
        if (!$this->maintenance_start_time || !$this->maintenance_end_time) {
            return false;
        }

        $now = now();

        // Parse maintenance datetimes
        $startDateTime = $this->maintenance_start_time instanceof \Carbon\Carbon 
            ? $this->maintenance_start_time 
            : \Carbon\Carbon::parse($this->maintenance_start_time);
        
        $endDateTime = $this->maintenance_end_time instanceof \Carbon\Carbon 
            ? $this->maintenance_end_time 
            : \Carbon\Carbon::parse($this->maintenance_end_time);

        // Check if current datetime is within maintenance window
        return $now->gte($startDateTime) && $now->lte($endDateTime);
    }
}
