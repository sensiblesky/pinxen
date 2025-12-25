<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainMonitorAlert extends Model
{
    protected $table = 'domain_monitor_alerts';

    protected $fillable = [
        'domain_monitor_id',
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
     * Get the domain monitor that owns the alert.
     */
    public function domainMonitor(): BelongsTo
    {
        return $this->belongsTo(DomainMonitor::class);
    }
}
