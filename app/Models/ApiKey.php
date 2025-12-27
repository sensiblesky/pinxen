<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ApiKey extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uid',
        'user_id',
        'name',
        'key',
        'key_prefix',
        'scopes',
        'last_used_at',
        'expires_at',
        'is_active',
        'description',
        'allowed_ips',
        'rate_limit',
    ];

    protected $casts = [
        'scopes' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'rate_limit' => 'integer',
    ];

    protected $hidden = [
        'key', // Never expose the full key in JSON
    ];

    /**
     * Generate a new API key.
     */
    public static function generate(): string
    {
        return 'pk_' . Str::random(48); // 51 characters total (pk_ + 48 random)
    }

    /**
     * Generate key prefix for identification.
     */
    public static function generatePrefix(string $key): string
    {
        return substr($key, 0, 8);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($apiKey) {
            if (empty($apiKey->uid)) {
                $apiKey->uid = Str::uuid()->toString();
            }
            if (empty($apiKey->key)) {
                $apiKey->key = self::generate();
            }
            if (empty($apiKey->key_prefix)) {
                $apiKey->key_prefix = self::generatePrefix($apiKey->key);
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'uid';
    }

    /**
     * Get the user that owns the API key.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the servers using this API key.
     */
    public function servers()
    {
        return $this->hasMany(Server::class, 'api_key_id');
    }

    /**
     * Check if the API key has a specific scope.
     */
    public function hasScope(string $scope): bool
    {
        if (!$this->scopes || !is_array($this->scopes)) {
            return false;
        }
        return in_array($scope, $this->scopes) || in_array('*', $this->scopes);
    }

    /**
     * Check if the API key has any of the given scopes.
     */
    public function hasAnyScope(array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if ($this->hasScope($scope)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the API key has all of the given scopes.
     */
    public function hasAllScopes(array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if (!$this->hasScope($scope)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if the API key is valid (active and not expired).
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if IP is allowed.
     */
    public function isIpAllowed(?string $ip): bool
    {
        if (!$this->allowed_ips) {
            return true; // No IP restriction
        }

        $allowedIps = array_map('trim', explode(',', $this->allowed_ips));
        return in_array($ip, $allowedIps);
    }

    /**
     * Update last used timestamp.
     */
    public function updateLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get masked key for display (shows only prefix and last 4 chars).
     */
    public function getMaskedKeyAttribute(): string
    {
        if (!$this->key) {
            return 'N/A';
        }
        return $this->key_prefix . '...' . substr($this->key, -4);
    }

    /**
     * Scope: Active keys only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope: Expired keys.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
}
