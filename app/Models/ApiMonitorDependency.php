<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiMonitorDependency extends Model
{
    protected $table = 'api_monitor_dependencies';

    protected $fillable = [
        'api_monitor_id',
        'depends_on_monitor_id',
        'dependency_type',
        'dependency_name',
        'dependency_url',
        'discovery_method',
        'discovery_evidence',
        'confidence_score',
        'is_confirmed',
        'suppress_child_alerts',
    ];

    protected $casts = [
        'discovery_evidence' => 'array',
        'confidence_score' => 'integer',
        'is_confirmed' => 'boolean',
        'suppress_child_alerts' => 'boolean',
    ];

    /**
     * The monitor that has this dependency.
     */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(ApiMonitor::class, 'api_monitor_id');
    }

    /**
     * The monitor this depends on (if it's an API dependency).
     */
    public function dependsOnMonitor(): BelongsTo
    {
        return $this->belongsTo(ApiMonitor::class, 'depends_on_monitor_id');
    }
}
