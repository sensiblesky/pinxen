<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerStat extends Model
{
    protected $fillable = [
        'server_id',
        'cpu_usage_percent',
        'cpu_cores',
        'cpu_load_1min',
        'cpu_load_5min',
        'cpu_load_15min',
        'memory_total_bytes',
        'memory_used_bytes',
        'memory_free_bytes',
        'memory_usage_percent',
        'swap_total_bytes',
        'swap_used_bytes',
        'swap_free_bytes',
        'swap_usage_percent',
        'disk_usage',
        'disk_total_bytes',
        'disk_used_bytes',
        'disk_free_bytes',
        'disk_usage_percent',
        'network_interfaces',
        'network_bytes_sent',
        'network_bytes_received',
        'network_packets_sent',
        'network_packets_received',
        'uptime_seconds',
        'processes_total',
        'processes_running',
        'processes_sleeping',
        'processes',
        'recorded_at',
    ];

    protected $casts = [
        'cpu_usage_percent' => 'decimal:2',
        'cpu_load_1min' => 'decimal:2',
        'cpu_load_5min' => 'decimal:2',
        'cpu_load_15min' => 'decimal:2',
        'memory_usage_percent' => 'decimal:2',
        'swap_usage_percent' => 'decimal:2',
        'disk_usage' => 'array',
        'disk_usage_percent' => 'decimal:2',
        'network_interfaces' => 'array',
        'processes' => 'array',
        'recorded_at' => 'datetime',
    ];

    /**
     * Get the server that owns this stat.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Format bytes to human readable format.
     */
    public static function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Format uptime seconds to human readable format.
     */
    public static function formatUptime($seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        $parts = [];
        if ($days > 0) $parts[] = $days . 'd';
        if ($hours > 0) $parts[] = $hours . 'h';
        if ($minutes > 0) $parts[] = $minutes . 'm';
        
        return !empty($parts) ? implode(' ', $parts) : '0m';
    }
}
