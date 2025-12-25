<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UptimeMonitorCheck extends Model
{
    protected $table = 'uptime_monitor_checks';

    protected $fillable = [
        'uptime_monitor_id',
        'status',
        'response_time',
        'status_code',
        'error_message',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function uptimeMonitor(): BelongsTo
    {
        return $this->belongsTo(UptimeMonitor::class, 'uptime_monitor_id');
    }
}
