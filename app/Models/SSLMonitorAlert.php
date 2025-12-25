<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SSLMonitorAlert extends Model
{
    protected $table = 'ssl_monitor_alerts';

    protected $fillable = [
        'ssl_monitor_id',
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

    /**
     * Get the SSL monitor that owns the alert.
     */
    public function sslMonitor(): BelongsTo
    {
        return $this->belongsTo(SSLMonitor::class);
    }
}
