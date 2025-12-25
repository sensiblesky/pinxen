<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DNSMonitorCheck extends Model
{
    protected $table = 'dns_monitor_checks';

    protected $fillable = [
        'dns_monitor_id',
        'record_type',
        'records',
        'previous_records',
        'has_changes',
        'is_missing',
        'error_message',
        'raw_response',
        'checked_at',
    ];

    protected $casts = [
        'records' => 'array',
        'previous_records' => 'array',
        'has_changes' => 'boolean',
        'is_missing' => 'boolean',
        'raw_response' => 'array',
        'checked_at' => 'datetime',
    ];

    /**
     * Get the DNS monitor that owns the check.
     */
    public function dnsMonitor(): BelongsTo
    {
        return $this->belongsTo(DNSMonitor::class);
    }
}
