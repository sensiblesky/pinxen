<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DNSMonitorAlert extends Model
{
    protected $table = 'dns_monitor_alerts';

    protected $fillable = [
        'dns_monitor_id',
        'alert_type',
        'record_type',
        'message',
        'changed_records',
        'communication_channel',
        'sent_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'changed_records' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the DNS monitor that owns the alert.
     */
    public function dnsMonitor(): BelongsTo
    {
        return $this->belongsTo(DNSMonitor::class);
    }
}
