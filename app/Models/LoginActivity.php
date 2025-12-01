<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'location',
        'action',
        'logged_in_at',
        'logged_out_at',
        'is_active',
    ];

    protected $casts = [
        'logged_in_at' => 'datetime',
        'logged_out_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the login activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get active sessions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->whereNotNull('logged_in_at')
            ->whereNull('logged_out_at');
    }

    /**
     * Scope to get recent activities.
     */
    public function scopeRecent($query, $limit = 50)
    {
        return $query->orderBy('logged_in_at', 'desc')->limit($limit);
    }
}
