<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringService extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'category',
        'icon',
        'config_schema',
        'is_active',
        'order',
    ];

    protected $casts = [
        'config_schema' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function monitors(): HasMany
    {
        return $this->hasMany(Monitor::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
