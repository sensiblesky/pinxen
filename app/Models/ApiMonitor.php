<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApiMonitor extends Model
{
    use SoftDeletes;

    protected $table = 'api_monitors';

    protected $fillable = [
        'uid',
        'user_id',
        'name',
        'url',
        'request_method',
        'auth_type',
        'auth_token',
        'auth_username',
        'auth_password',
        'auth_header_name',
        'request_headers',
        'request_body',
        'content_type',
        'expected_status_code',
        'response_assertions',
        'variable_extraction_rules',
        'monitoring_steps',
        'is_stateful',
        'auto_auth_enabled',
        'auto_auth_type',
        'oauth2_token_url',
        'oauth2_client_id',
        'oauth2_client_secret',
        'oauth2_username',
        'oauth2_password',
        'oauth2_scope',
        'oauth2_refresh_token',
        'current_access_token',
        'token_expires_at',
        'token_refreshed_at',
        'jwt_token_path',
        'jwt_expiration_buffer_seconds',
        'apikey_rotation_reminder_at',
        'apikey_rotation_days',
        'auto_refresh_on_expiry',
        'retry_after_refresh',
        'max_refresh_attempts',
        'schema_drift_enabled',
        'schema_source_type',
        'schema_content',
        'schema_url',
        'schema_parsed',
        'detect_missing_fields',
        'detect_type_changes',
        'detect_breaking_changes',
        'detect_enum_violations',
        'schema_version',
        'schema_last_validated_at',
        'max_latency_ms',
        'validate_response_body',
        'check_interval',
        'timeout',
        'check_ssl',
        'is_active',
        'last_checked_at',
        'next_check_at',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'check_ssl' => 'boolean',
        'validate_response_body' => 'boolean',
        'is_stateful' => 'boolean',
        'auto_auth_enabled' => 'boolean',
        'auto_refresh_on_expiry' => 'boolean',
        'retry_after_refresh' => 'boolean',
        'schema_drift_enabled' => 'boolean',
        'detect_missing_fields' => 'boolean',
        'detect_type_changes' => 'boolean',
        'detect_breaking_changes' => 'boolean',
        'detect_enum_violations' => 'boolean',
        'check_interval' => 'integer',
        'timeout' => 'integer',
        'expected_status_code' => 'integer',
        'max_latency_ms' => 'integer',
        'jwt_expiration_buffer_seconds' => 'integer',
        'apikey_rotation_days' => 'integer',
        'max_refresh_attempts' => 'integer',
        'request_headers' => 'array',
        'response_assertions' => 'array',
        'variable_extraction_rules' => 'array',
        'monitoring_steps' => 'array',
        'schema_parsed' => 'array',
        'last_checked_at' => 'datetime',
        'next_check_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'token_refreshed_at' => 'datetime',
        'apikey_rotation_reminder_at' => 'datetime',
        'schema_last_validated_at' => 'datetime',
    ];

    protected $hidden = [
        'auth_token',
        'auth_password',
        'oauth2_client_secret',
        'oauth2_password',
        'oauth2_refresh_token',
        'current_access_token',
    ];

    /**
     * Check if the current token is expired or about to expire.
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        $buffer = $this->jwt_expiration_buffer_seconds ?? 300; // Default 5 minutes
        return now()->addSeconds($buffer)->gte($this->token_expires_at);
    }

    /**
     * Check if API key rotation reminder is due.
     */
    public function isRotationReminderDue(): bool
    {
        if (!$this->apikey_rotation_days || !$this->apikey_rotation_reminder_at) {
            return false;
        }

        return now()->gte($this->apikey_rotation_reminder_at);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($monitor) {
            if (empty($monitor->uid)) {
                $monitor->uid = Str::uuid()->toString();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(ApiMonitorCheck::class, 'api_monitor_id')->orderBy('checked_at', 'desc');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(ApiMonitorAlert::class, 'api_monitor_id')->orderBy('created_at', 'desc');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(ApiMonitorDependency::class, 'api_monitor_id');
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(ApiMonitorDependency::class, 'depends_on_monitor_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
