<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SSLMonitor extends Model
{
    use SoftDeletes;

    protected $table = 'ssl_monitors';

    protected $fillable = [
        'uid',
        'user_id',
        'name',
        'domain',
        'check_interval',
        'alert_expiring_soon',
        'alert_expired',
        'alert_invalid',
        'is_active',
        'last_checked_at',
        'next_check_at',
        'status',
        'days_until_expiration',
        'expiration_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'alert_expiring_soon' => 'boolean',
        'alert_expired' => 'boolean',
        'alert_invalid' => 'boolean',
        'check_interval' => 'integer',
        'expiration_date' => 'date',
        'last_checked_at' => 'datetime',
        'next_check_at' => 'datetime',
        'days_until_expiration' => 'integer',
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
     * Get the user that owns the SSL monitor.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the checks for the SSL monitor.
     */
    public function checks(): HasMany
    {
        return $this->hasMany(SSLMonitorCheck::class, 'ssl_monitor_id')->orderBy('checked_at', 'desc');
    }

    /**
     * Get the alerts for the SSL monitor.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(SSLMonitorAlert::class, 'ssl_monitor_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get the communication preferences for the SSL monitor.
     */
    public function communicationPreferences(): HasMany
    {
        return $this->hasMany(MonitorCommunicationPreference::class, 'monitor_id')
            ->where('monitor_type', 'ssl');
    }

    /**
     * Scope to get active monitors.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
