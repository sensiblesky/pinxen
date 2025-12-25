<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalApi extends Model
{
    protected $fillable = [
        'name',
        'provider',
        'service_type',
        'api_key',
        'api_secret',
        'base_url',
        'endpoint',
        'headers',
        'config',
        'is_active',
        'rate_limit',
        'description',
    ];

    protected $casts = [
        'headers' => 'array',
        'config' => 'array',
        'is_active' => 'boolean',
        'rate_limit' => 'integer',
    ];

    /**
     * Scope to get active APIs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get APIs by service type.
     */
    public function scopeByServiceType($query, string $serviceType)
    {
        return $query->where('service_type', $serviceType);
    }

    /**
     * Get API for a specific service type.
     */
    public static function getForService(string $serviceType): ?self
    {
        return self::active()
            ->byServiceType($serviceType)
            ->first();
    }
}
