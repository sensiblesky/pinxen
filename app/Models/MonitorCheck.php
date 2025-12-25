<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorCheck extends Model
{
    protected $fillable = [
        'monitor_id',
        'status',
        'response_time',
        'status_code',
        'error_message',
        'checked_at',
    ];

    protected $casts = [
        'response_time' => 'integer',
        'status_code' => 'integer',
        'checked_at' => 'datetime',
    ];

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function scopeUp($query)
    {
        return $query->where('status', 'up');
    }

    public function scopeDown($query)
    {
        return $query->where('status', 'down');
    }
}
