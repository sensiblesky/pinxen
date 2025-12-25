<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UptimeMonitorAlert extends Model
{
    protected $table = 'uptime_monitor_alerts';

    protected $fillable = [
        'uptime_monitor_id',
        'alert_type',
        'message',
        'communication_channel',
        'sent_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function uptimeMonitor(): BelongsTo
    {
        return $this->belongsTo(UptimeMonitor::class, 'uptime_monitor_id');
    }
}
