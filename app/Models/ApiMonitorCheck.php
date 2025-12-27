<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiMonitorCheck extends Model
{
    protected $table = 'api_monitor_checks';

    protected $fillable = [
        'api_monitor_id',
        'request_method',
        'request_url',
        'request_headers',
        'request_body',
        'request_content_type',
        'status',
        'response_time',
        'status_code',
        'response_body',
        'response_headers',
        'error_message',
        'validation_errors',
        'latency_exceeded',
        'is_replay',
        'replay_of_check_id',
        'replayed_at',
        'checked_at',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'response_headers' => 'array',
        'response_time' => 'integer',
        'status_code' => 'integer',
        'latency_exceeded' => 'boolean',
        'validation_errors' => 'array',
        'is_replay' => 'boolean',
        'checked_at' => 'datetime',
        'replayed_at' => 'datetime',
    ];

    public function apiMonitor(): BelongsTo
    {
        return $this->belongsTo(ApiMonitor::class, 'api_monitor_id');
    }

    /**
     * The check this is a replay of.
     */
    public function replayOf(): BelongsTo
    {
        return $this->belongsTo(ApiMonitorCheck::class, 'replay_of_check_id');
    }

    /**
     * Replays of this check.
     */
    public function replays(): HasMany
    {
        return $this->hasMany(ApiMonitorCheck::class, 'replay_of_check_id');
    }
}
