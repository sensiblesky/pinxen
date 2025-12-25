<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DNSMonitor extends Model
{
    use SoftDeletes;

    protected $table = 'dns_monitors';

    protected $fillable = [
        'uid',
        'user_id',
        'name',
        'domain',
        'record_types',
        'check_interval',
        'alert_on_change',
        'alert_on_missing',
        'is_active',
        'last_checked_at',
        'next_check_at',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'alert_on_change' => 'boolean',
        'alert_on_missing' => 'boolean',
        'check_interval' => 'integer',
        'record_types' => 'array',
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

    /**
     * Get the route key name for model binding.
     */
    public function getRouteKeyName()
    {
        return 'uid';
    }

    /**
     * Get the user that owns the DNS monitor.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the checks for the DNS monitor.
     */
    public function checks(): HasMany
    {
        return $this->hasMany(DNSMonitorCheck::class, 'dns_monitor_id')->orderBy('checked_at', 'desc');
    }

    /**
     * Get the alerts for the DNS monitor.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(DNSMonitorAlert::class, 'dns_monitor_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get the communication preferences for the DNS monitor.
     */
    public function communicationPreferences(): HasMany
    {
        return $this->hasMany(MonitorCommunicationPreference::class, 'monitor_id')
            ->where('monitor_type', 'dns');
    }

    /**
     * Scope to get active monitors.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the latest check for a specific record type.
     */
    public function getLatestCheckForType(string $recordType)
    {
        return $this->checks()
            ->where('record_type', $recordType)
            ->orderBy('checked_at', 'desc')
            ->first();
    }
}
