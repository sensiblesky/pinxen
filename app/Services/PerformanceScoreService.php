<?php

namespace App\Services;

use App\Models\Server;
use App\Models\ServerStat;
use App\Models\UptimeMonitor;
use App\Models\ApiMonitor;

class PerformanceScoreService
{
    /**
     * Calculate performance score for a server (0-100).
     * 
     * @param Server $server
     * @param ServerStat|null $latestStat
     * @return array Returns ['score' => int, 'breakdown' => array, 'grade' => string]
     */
    public function calculateServerScore(Server $server, ?ServerStat $latestStat = null): array
    {
        if (!$latestStat) {
            $latestStat = $server->latestStat;
        }

        if (!$latestStat) {
            return [
                'score' => 0,
                'breakdown' => [],
                'grade' => 'N/A',
                'message' => 'No data available'
            ];
        }

        $breakdown = [];
        $totalWeight = 0;
        $weightedScore = 0;

        // CPU Score (Weight: 25%)
        $cpuWeight = 25;
        $cpuScore = $this->calculateCpuScore($latestStat, $server);
        $breakdown['cpu'] = [
            'score' => $cpuScore,
            'weight' => $cpuWeight,
            'usage' => $latestStat->cpu_usage_percent ?? 0,
            'threshold' => $server->cpu_threshold
        ];
        $weightedScore += ($cpuScore * $cpuWeight);
        $totalWeight += $cpuWeight;

        // Memory Score (Weight: 25%)
        $memoryWeight = 25;
        $memoryScore = $this->calculateMemoryScore($latestStat, $server);
        $breakdown['memory'] = [
            'score' => $memoryScore,
            'weight' => $memoryWeight,
            'usage' => $latestStat->memory_usage_percent ?? 0,
            'threshold' => $server->memory_threshold
        ];
        $weightedScore += ($memoryScore * $memoryWeight);
        $totalWeight += $memoryWeight;

        // Disk Score (Weight: 20%)
        $diskWeight = 20;
        $diskScore = $this->calculateDiskScore($latestStat, $server);
        $breakdown['disk'] = [
            'score' => $diskScore,
            'weight' => $diskWeight,
            'usage' => $latestStat->disk_usage_percent ?? 0,
            'threshold' => $server->disk_threshold
        ];
        $weightedScore += ($diskScore * $diskWeight);
        $totalWeight += $diskWeight;

        // Uptime Score (Weight: 20%)
        $uptimeWeight = 20;
        $uptimeScore = $this->calculateUptimeScore($server);
        $breakdown['uptime'] = [
            'score' => $uptimeScore,
            'weight' => $uptimeWeight,
            'percentage' => $this->getServerUptimePercentage($server)
        ];
        $weightedScore += ($uptimeScore * $uptimeWeight);
        $totalWeight += $uptimeWeight;

        // Response Time Score (Weight: 10%) - if we have response time data
        $responseTimeWeight = 10;
        $responseTimeScore = $this->calculateResponseTimeScore($server);
        if ($responseTimeScore !== null) {
            $breakdown['response_time'] = [
                'score' => $responseTimeScore,
                'weight' => $responseTimeWeight
            ];
            $weightedScore += ($responseTimeScore * $responseTimeWeight);
            $totalWeight += $responseTimeWeight;
        }

        // Calculate final score
        $finalScore = $totalWeight > 0 ? round($weightedScore / $totalWeight) : 0;
        $finalScore = max(0, min(100, $finalScore)); // Ensure between 0-100

        return [
            'score' => $finalScore,
            'breakdown' => $breakdown,
            'grade' => $this->getGrade($finalScore),
            'color' => $this->getColor($finalScore),
            'message' => $this->getMessage($finalScore, $breakdown)
        ];
    }

    /**
     * Calculate CPU score (0-100).
     */
    private function calculateCpuScore(ServerStat $stat, Server $server): int
    {
        $usage = $stat->cpu_usage_percent ?? 0;
        
        // If threshold is set and exceeded, score is 0
        if ($server->cpu_threshold !== null && $usage > $server->cpu_threshold) {
            return 0;
        }

        // Score calculation:
        // 0-50%: 100 points (excellent)
        // 50-70%: 80 points (good)
        // 70-85%: 60 points (fair)
        // 85-95%: 40 points (poor)
        // 95-100%: 20 points (critical)

        if ($usage <= 50) {
            return 100;
        } elseif ($usage <= 70) {
            return 80;
        } elseif ($usage <= 85) {
            return 60;
        } elseif ($usage <= 95) {
            return 40;
        } else {
            return 20;
        }
    }

    /**
     * Calculate Memory score (0-100).
     */
    private function calculateMemoryScore(ServerStat $stat, Server $server): int
    {
        $usage = $stat->memory_usage_percent ?? 0;
        
        // If threshold is set and exceeded, score is 0
        if ($server->memory_threshold !== null && $usage > $server->memory_threshold) {
            return 0;
        }

        // Similar scoring as CPU
        if ($usage <= 50) {
            return 100;
        } elseif ($usage <= 70) {
            return 80;
        } elseif ($usage <= 85) {
            return 60;
        } elseif ($usage <= 95) {
            return 40;
        } else {
            return 20;
        }
    }

    /**
     * Calculate Disk score (0-100).
     */
    private function calculateDiskScore(ServerStat $stat, Server $server): int
    {
        $usage = $stat->disk_usage_percent ?? 0;
        
        // If threshold is set and exceeded, score is 0
        if ($server->disk_threshold !== null && $usage > $server->disk_threshold) {
            return 0;
        }

        // Disk scoring (more critical at higher usage):
        // 0-60%: 100 points (excellent)
        // 60-75%: 80 points (good)
        // 75-85%: 60 points (fair)
        // 85-90%: 40 points (poor)
        // 90-100%: 20 points (critical)

        if ($usage <= 60) {
            return 100;
        } elseif ($usage <= 75) {
            return 80;
        } elseif ($usage <= 85) {
            return 60;
        } elseif ($usage <= 90) {
            return 40;
        } else {
            return 20;
        }
    }

    /**
     * Calculate Uptime score (0-100).
     */
    private function calculateUptimeScore(Server $server): int
    {
        $uptimePercentage = $this->getServerUptimePercentage($server);
        
        // Uptime scoring:
        // 99.9%+: 100 points (excellent)
        // 99.5-99.9%: 80 points (good)
        // 99.0-99.5%: 60 points (fair)
        // 95.0-99.0%: 40 points (poor)
        // <95%: 20 points (critical)

        if ($uptimePercentage >= 99.9) {
            return 100;
        } elseif ($uptimePercentage >= 99.5) {
            return 80;
        } elseif ($uptimePercentage >= 99.0) {
            return 60;
        } elseif ($uptimePercentage >= 95.0) {
            return 40;
        } else {
            return 20;
        }
    }

    /**
     * Calculate Response Time score (0-100) or null if no data.
     */
    private function calculateResponseTimeScore(Server $server): ?int
    {
        // Get average response time from related monitors (if any)
        // This is a placeholder - you can enhance this based on your monitoring data
        
        // For now, return null to exclude from calculation
        return null;
    }

    /**
     * Get server uptime percentage (last 30 days).
     */
    private function getServerUptimePercentage(Server $server): float
    {
        // Calculate uptime based on last_seen_at and isOnline status
        // Optimized to avoid loading too many records
        
        if (!$server->last_seen_at) {
            return 0.0;
        }

        // If server was seen in last 5 minutes, consider it online
        if ($server->isOnline()) {
            // Use a more efficient approach - just check if we have recent stats
            // instead of loading all stats
            $sevenDaysAgo = now()->subDays(7);
            $hasRecentStats = $server->stats()
                ->where('recorded_at', '>=', $sevenDaysAgo)
                ->exists();

            if ($hasRecentStats) {
                // Calculate approximate uptime based on last_seen_at
                // If server is online and has recent stats, assume good uptime
                $minutesSinceLastSeen = now()->diffInMinutes($server->last_seen_at);
                
                if ($minutesSinceLastSeen <= 5) {
                    return 99.9; // Excellent - seen very recently
                } elseif ($minutesSinceLastSeen <= 60) {
                    return 99.5; // Good - seen in last hour
                } elseif ($minutesSinceLastSeen <= 1440) {
                    return 99.0; // Fair - seen in last 24 hours
                } else {
                    return 95.0; // Lower - seen more than 24 hours ago
                }
            }

            return 95.0; // Default if no recent stats
        }

        // Server is offline
        return 0.0;
    }

    /**
     * Get grade letter based on score.
     */
    private function getGrade(int $score): string
    {
        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        if ($score >= 50) return 'D';
        return 'F';
    }

    /**
     * Get color class based on score.
     */
    private function getColor(int $score): string
    {
        if ($score >= 80) return 'success';
        if ($score >= 60) return 'warning';
        return 'danger';
    }

    /**
     * Get human-readable message based on score.
     */
    private function getMessage(int $score, array $breakdown): string
    {
        if ($score >= 90) {
            return 'Excellent performance across all metrics';
        } elseif ($score >= 80) {
            return 'Good performance with minor areas for improvement';
        } elseif ($score >= 60) {
            return 'Fair performance - some metrics need attention';
        } elseif ($score >= 40) {
            return 'Poor performance - immediate action recommended';
        } else {
            return 'Critical performance issues - urgent attention required';
        }
    }

    /**
     * Calculate performance score for an uptime monitor.
     */
    public function calculateUptimeMonitorScore(UptimeMonitor $monitor): array
    {
        // Get checks from last 30 days
        $thirtyDaysAgo = now()->subDays(30);
        $checks = $monitor->checks()
            ->where('checked_at', '>=', $thirtyDaysAgo)
            ->get();

        if ($checks->isEmpty()) {
            return [
                'score' => 0,
                'breakdown' => [],
                'grade' => 'N/A',
                'message' => 'No data available'
            ];
        }

        $totalChecks = $checks->count();
        $successfulChecks = $checks->where('status', 'up')->count();
        $uptimePercentage = ($totalChecks > 0) ? ($successfulChecks / $totalChecks) * 100 : 0;

        // Calculate average response time
        $avgResponseTime = $checks->where('status', 'up')->avg('response_time') ?? 0;

        // Score based on uptime and response time
        $uptimeScore = $this->calculateUptimeScoreFromPercentage($uptimePercentage);
        $responseTimeScore = $this->calculateResponseTimeScoreFromMs($avgResponseTime);

        // Weighted: 70% uptime, 30% response time
        $finalScore = round(($uptimeScore * 0.7) + ($responseTimeScore * 0.3));

        return [
            'score' => $finalScore,
            'breakdown' => [
                'uptime' => [
                    'score' => $uptimeScore,
                    'percentage' => round($uptimePercentage, 2)
                ],
                'response_time' => [
                    'score' => $responseTimeScore,
                    'ms' => round($avgResponseTime, 2)
                ]
            ],
            'grade' => $this->getGrade($finalScore),
            'color' => $this->getColor($finalScore),
            'message' => $this->getMessage($finalScore, [])
        ];
    }

    /**
     * Calculate uptime score from percentage.
     */
    private function calculateUptimeScoreFromPercentage(float $percentage): int
    {
        if ($percentage >= 99.9) return 100;
        if ($percentage >= 99.5) return 80;
        if ($percentage >= 99.0) return 60;
        if ($percentage >= 95.0) return 40;
        return 20;
    }

    /**
     * Calculate response time score from milliseconds.
     */
    private function calculateResponseTimeScoreFromMs(float $ms): int
    {
        // Scoring based on response time:
        // <100ms: 100 points (excellent)
        // 100-200ms: 80 points (good)
        // 200-500ms: 60 points (fair)
        // 500-1000ms: 40 points (poor)
        // >1000ms: 20 points (critical)

        if ($ms < 100) return 100;
        if ($ms < 200) return 80;
        if ($ms < 500) return 60;
        if ($ms < 1000) return 40;
        return 20;
    }
}

