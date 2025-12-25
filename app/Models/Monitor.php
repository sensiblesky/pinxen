<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Monitor extends Model
{
    protected $fillable = [
        'uid',
        'user_id',
        'service_category_id',
        'monitoring_service_id',
        'name',
        'type',
        'url',
        'check_interval',
        'timeout',
        'expected_status_code',
        'is_active',
        'last_checked_at',
        'status',
        'service_config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'check_interval' => 'integer',
        'timeout' => 'integer',
        'expected_status_code' => 'integer',
        'last_checked_at' => 'datetime',
        'service_config' => 'array',
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

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function monitoringService(): BelongsTo
    {
        return $this->belongsTo(MonitoringService::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(MonitorCheck::class)->orderBy('checked_at', 'desc');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(MonitorAlert::class)->orderBy('created_at', 'desc');
    }

    public function communicationPreferences(): HasMany
    {
        return $this->hasMany(MonitorCommunicationPreference::class, 'monitor_id')
            ->where('monitor_type', 'monitor');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWeb($query)
    {
        return $query->where('type', 'web');
    }

    public function scopeServer($query)
    {
        return $query->where('type', 'server');
    }
}
