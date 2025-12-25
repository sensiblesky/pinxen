<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DomainMonitor extends Model
{
    use SoftDeletes;

    protected $table = 'domain_monitors';

    protected $fillable = [
        'uid',
        'user_id',
        'name',
        'domain',
        'expiration_date',
        'alert_30_days',
        'alert_5_days',
        'alert_daily_under_30',
        'is_active',
        'last_checked_at',
        'days_until_expiration',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'alert_30_days' => 'boolean',
        'alert_5_days' => 'boolean',
        'alert_daily_under_30' => 'boolean',
        'expiration_date' => 'date',
        'last_checked_at' => 'datetime',
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
     * Get the user that owns the domain monitor.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the alerts for the domain monitor.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(DomainMonitorAlert::class);
    }

    /**
     * Get the communication preferences for the domain monitor.
     */
    public function communicationPreferences(): HasMany
    {
        return $this->hasMany(MonitorCommunicationPreference::class, 'monitor_id')
            ->where('monitor_type', 'domain');
    }

    /**
     * Get the route key name for model binding.
     */
    public function getRouteKeyName()
    {
        return 'uid';
    }

    /**
     * Scope to get active monitors.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if domain is expired.
     */
    public function isExpired(): bool
    {
        if (!$this->expiration_date) {
            return false;
        }
        return $this->expiration_date->isPast();
    }

    /**
     * Get days until expiration.
     */
    public function getDaysUntilExpiration(): ?int
    {
        if (!$this->expiration_date) {
            return null;
        }
        return now()->diffInDays($this->expiration_date, false);
    }
}
