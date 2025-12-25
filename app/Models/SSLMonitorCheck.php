<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SSLMonitorCheck extends Model
{
    protected $table = 'ssl_monitor_checks';

    protected $fillable = [
        'ssl_monitor_id',
        'status',
        'resolved_ip',
        'issued_to',
        'issuer_cn',
        'cert_alg',
        'cert_valid',
        'cert_exp',
        'valid_from',
        'valid_till',
        'validity_days',
        'days_left',
        'hsts_header_enabled',
        'response_time_sec',
        'error_message',
        'raw_response',
        'checked_at',
    ];

    protected $casts = [
        'cert_valid' => 'boolean',
        'cert_exp' => 'boolean',
        'hsts_header_enabled' => 'boolean',
        'valid_from' => 'date',
        'valid_till' => 'date',
        'validity_days' => 'integer',
        'days_left' => 'integer',
        'response_time_sec' => 'float',
        'raw_response' => 'array',
        'checked_at' => 'datetime',
    ];

    /**
     * Get the SSL monitor that owns the check.
     */
    public function sslMonitor(): BelongsTo
    {
        return $this->belongsTo(SSLMonitor::class);
    }
}
