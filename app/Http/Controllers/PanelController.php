<?php

namespace App\Http\Controllers;

use App\Models\UptimeMonitor;
use App\Models\DomainMonitor;
use App\Models\SSLMonitor;
use App\Models\DNSMonitor;
use App\Models\ApiMonitor;
use App\Models\Server;
use App\Models\UserSubscription;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class PanelController extends Controller
{
    /**
     * Display the admin panel dashboard (shows all users' data).
     */
    public function index(): View
    {
        // Increase memory limit for dashboard
        ini_set('memory_limit', '512M');
        
        // Calculate statistics using database aggregation (much faster and uses less memory)
        $stats = [
            'uptime' => [
                'total' => UptimeMonitor::count(),
                'active' => UptimeMonitor::where('is_active', true)->count(),
                'up' => UptimeMonitor::where('status', 'up')->count(),
                'down' => UptimeMonitor::where('status', 'down')->count(),
                'partial' => UptimeMonitor::where('status', 'partial')->count(),
            ],
            'domain' => [
                'total' => DomainMonitor::count(),
                'active' => DomainMonitor::where('is_active', true)->count(),
                'expiring_soon' => DomainMonitor::where('days_until_expiration', '<=', 30)
                    ->where('days_until_expiration', '>', 0)->count(),
                'expired' => DomainMonitor::where('days_until_expiration', '<=', 0)->count(),
            ],
            'ssl' => [
                'total' => SSLMonitor::count(),
                'active' => SSLMonitor::where('is_active', true)->count(),
                'valid' => SSLMonitor::where('status', 'valid')->count(),
                'expiring_soon' => SSLMonitor::where('days_until_expiration', '<=', 30)
                    ->where('days_until_expiration', '>', 0)->count(),
                'expired' => SSLMonitor::where('status', 'expired')->count(),
                'invalid' => SSLMonitor::where('status', 'invalid')->count(),
            ],
            'dns' => [
                'total' => DNSMonitor::count(),
                'active' => DNSMonitor::where('is_active', true)->count(),
                'healthy' => DNSMonitor::where('status', 'healthy')->count(),
                'unhealthy' => DNSMonitor::where('status', 'unhealthy')->count(),
            ],
            'api' => [
                'total' => ApiMonitor::count(),
                'active' => ApiMonitor::where('is_active', true)->count(),
                'up' => ApiMonitor::where('status', 'up')->count(),
                'down' => ApiMonitor::where('status', 'down')->count(),
            ],
            'servers' => [
                'total' => Server::count(),
                'active' => Server::where('is_active', true)->count(),
                'online' => 0, // Simplified - can be calculated via AJAX if needed
                'offline' => 0,
                'warning' => 0,
            ],
        ];

        // Get recent monitors (latest 5 of each type) - only load what we need
        $recentUptime = UptimeMonitor::select('id', 'uid', 'name', 'url', 'status', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        $recentDomain = DomainMonitor::select('id', 'uid', 'name', 'domain', 'days_until_expiration', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        $recentSSL = SSLMonitor::select('id', 'uid', 'name', 'domain', 'status', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        $recentDNS = DNSMonitor::select('id', 'uid', 'name', 'domain', 'status', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        $recentAPI = ApiMonitor::select('id', 'uid', 'name', 'url', 'status', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        $recentServers = Server::select('id', 'uid', 'name', 'hostname', 'last_seen_at', 'is_active', 'online_threshold_minutes', 'warning_threshold_minutes', 'offline_threshold_minutes', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Calculate status for each server using ServerStatusService
        $statusService = new \App\Services\ServerStatusService();
        foreach ($recentServers as $server) {
            $server->calculated_status = $statusService->determineStatus(
                $server,
                $server->online_threshold_minutes,
                $server->warning_threshold_minutes,
                $server->offline_threshold_minutes
            );
        }

        // Get uptime monitors for slider (maximum 15) - only load what we need
        $sliderUptimeMonitors = UptimeMonitor::select('id', 'uid', 'name', 'url', 'status', 'is_active', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        // Generate favicon URLs and sparkline data for slider monitors (limited to 15)
        $uptimeMonitorFavicons = [];
        $uptimeMonitorSparklines = [];
        
        // Only process if we have monitors
        if ($sliderUptimeMonitors->isNotEmpty()) {
            $monitorIds = $sliderUptimeMonitors->pluck('id')->toArray();
            
            // Single optimized query: Get only the latest 20 checks per monitor from last 24 hours
            // Include response_time to show variation in the sparkline
            $recentChecks = \App\Models\UptimeMonitorCheck::whereIn('uptime_monitor_id', $monitorIds)
                ->where('checked_at', '>=', now()->subHours(24))
                ->select('uptime_monitor_id', 'status', 'response_time', 'checked_at')
                ->orderBy('uptime_monitor_id')
                ->orderBy('checked_at', 'asc')
                ->get()
                ->groupBy('uptime_monitor_id')
                ->map(function($checks) {
                    // Take only last 20 checks to keep data minimal
                    return $checks->take(-20);
                });
            
            foreach ($sliderUptimeMonitors as $monitor) {
                $host = parse_url($monitor->url, PHP_URL_HOST);
                $domain = $host ? strtolower(trim($host)) : null;
                $uptimeMonitorFavicons[$monitor->id] = $domain 
                    ? "https://www.google.com/s2/favicons?domain={$domain}&sz=32" 
                    : null;
                
                // Get checks for this monitor (max 20 points)
                $monitorChecks = $recentChecks->get($monitor->id, collect());
                
                // Create sparkline data based on response time variation
                // Normalize response time to 0-100 scale for visual variation
                $sparklineData = [];
                if ($monitorChecks->isNotEmpty()) {
                    // Get min and max response times for normalization
                    $responseTimes = $monitorChecks->where('status', 'up')
                        ->whereNotNull('response_time')
                        ->pluck('response_time')
                        ->filter(function($rt) {
                            return $rt > 0;
                        });
                    
                    $minResponseTime = $responseTimes->min() ?? 0;
                    $maxResponseTime = $responseTimes->max() ?? 1000;
                    $range = $maxResponseTime - $minResponseTime;
                    
                    // If all response times are similar, create a small range for visualization
                    if ($range < 50) {
                        $minResponseTime = max(0, ($minResponseTime + $maxResponseTime) / 2 - 100);
                        $maxResponseTime = ($minResponseTime + $maxResponseTime) / 2 + 100;
                        $range = $maxResponseTime - $minResponseTime;
                    }
                    
                    foreach ($monitorChecks as $check) {
                        if ($check->status === 'up' && $check->response_time && $check->response_time > 0) {
                            // Normalize response time to 0-100 scale (inverted: lower is better, so higher on chart)
                            // We'll show it as: 100 - normalized value, so fast responses are high on chart
                            $normalized = $range > 0 
                                ? (($check->response_time - $minResponseTime) / $range) * 100
                                : 50;
                            // Invert so lower response time = higher on chart (better performance = higher)
                            $sparklineData[] = max(10, min(100, 100 - ($normalized * 0.6) + 20)); // Scale between 20-100
                        } elseif ($check->status === 'down') {
                            $sparklineData[] = 0; // Down = bottom of chart
                        } elseif ($check->status === 'partial') {
                            $sparklineData[] = 30; // Partial = low on chart
                        } else {
                            // Unknown or no response time
                            $sparklineData[] = 50; // Middle value
                        }
                    }
                } else {
                    // If no checks, use current status as single point
                    $sparklineData = [match($monitor->status) {
                        'up' => 80,
                        'down' => 0,
                        'partial' => 30,
                        default => 50
                    }];
                }
                
                // Ensure we have at least some variation for visual appeal
                if (count($sparklineData) > 1) {
                    $allSame = count(array_unique($sparklineData)) === 1;
                    if ($allSame && $sparklineData[0] > 0) {
                        // Add slight variation to show it's a trend, not a flat line
                        $baseValue = $sparklineData[0];
                        $variation = max(2, $baseValue * 0.05); // 5% variation
                        $sparklineData = array_map(function($val, $index) use ($baseValue, $variation) {
                            // Add subtle wave pattern
                            return $baseValue + sin($index * 0.5) * $variation;
                        }, $sparklineData, array_keys($sparklineData));
                    }
                }
                
                $uptimeMonitorSparklines[$monitor->id] = $sparklineData;
            }
        }

        // Generate favicon URLs for recent monitors widgets
        $recentUptimeFavicons = [];
        foreach ($recentUptime as $monitor) {
            $host = parse_url($monitor->url, PHP_URL_HOST);
            $domain = $host ? strtolower(trim($host)) : null;
            $recentUptimeFavicons[$monitor->id] = $domain 
                ? "https://www.google.com/s2/favicons?domain={$domain}&sz=32" 
                : null;
        }

        $domainMonitorFavicons = [];
        foreach ($recentDomain as $monitor) {
            $domain = $monitor->domain ? strtolower(trim($monitor->domain)) 
                : null;
            $domainMonitorFavicons[$monitor->id] = $domain 
                ? "https://www.google.com/s2/favicons?domain={$domain}&sz=32" 
                : null;
        }

        $sslMonitorFavicons = [];
        foreach ($recentSSL as $monitor) {
            $domain = $monitor->domain ? strtolower(trim($monitor->domain)) : null;
            $sslMonitorFavicons[$monitor->id] = $domain 
                ? "https://www.google.com/s2/favicons?domain={$domain}&sz=32" 
                : null;
        }

        $dnsMonitorFavicons = [];
        foreach ($recentDNS as $monitor) {
            $domain = $monitor->domain ? strtolower(trim($monitor->domain)) : null;
            $dnsMonitorFavicons[$monitor->id] = $domain 
                ? "https://www.google.com/s2/favicons?domain={$domain}&sz=32" 
                : null;
        }

        $apiMonitorFavicons = [];
        foreach ($recentAPI as $monitor) {
            $host = parse_url($monitor->url, PHP_URL_HOST);
            $domain = $host ? strtolower(trim($host)) : null;
            $apiMonitorFavicons[$monitor->id] = $domain 
                ? "https://www.google.com/s2/favicons?domain={$domain}&sz=32" 
                : null;
        }

        // Calculate sales statistics for last 30 days only
        $thirtyDaysAgo = now()->subDays(30);
        
        $totalSubscriptions = UserSubscription::where('created_at', '>=', $thirtyDaysAgo)->count();
        $totalSales = Payment::where('status', 'completed')
            ->where('paid_at', '>=', $thirtyDaysAgo)
            ->count();
        $totalRevenue = Payment::where('status', 'completed')
            ->where('paid_at', '>=', $thirtyDaysAgo)
            ->sum('amount');
        
        // Get recent users (latest 5 registered users)
        $recentUsers = User::select('id', 'uid', 'name', 'email', 'role', 'avatar', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get();
        
        // Get sales data for chart (last 30 days grouped by day)
        $salesChartData = Payment::where('status', 'completed')
            ->where('paid_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(paid_at) as date, COUNT(*) as sales_count, SUM(amount) as revenue')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        
        // Prepare chart data
        $salesChartDates = [];
        $salesChartCounts = [];
        $salesChartRevenue = [];
        
        // Fill in all 30 days (even if no sales)
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $salesChartDates[] = now()->subDays($i)->format('M d');
            
            $dayData = $salesChartData->firstWhere('date', $date);
            $salesChartCounts[] = $dayData ? (int)$dayData->sales_count : 0;
            $salesChartRevenue[] = $dayData ? (float)$dayData->revenue : 0;
        }
        
        // Get subscriptions data for chart (last 30 days grouped by day)
        $subscriptionsChartData = UserSubscription::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as subscriptions_count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        
        $subscriptionsChartCounts = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayData = $subscriptionsChartData->firstWhere('date', $date);
            $subscriptionsChartCounts[] = $dayData ? (int)$dayData->subscriptions_count : 0;
        }

        return view('panel.index', compact(
            'stats',
            'recentUptime',
            'recentDomain',
            'recentSSL',
            'recentDNS',
            'recentUsers',
            'recentAPI',
            'recentServers',
            'sliderUptimeMonitors',
            'uptimeMonitorFavicons',
            'uptimeMonitorSparklines',
            'recentUptimeFavicons',
            'domainMonitorFavicons',
            'sslMonitorFavicons',
            'dnsMonitorFavicons',
            'apiMonitorFavicons',
            'totalSubscriptions',
            'totalSales',
            'totalRevenue',
            'salesChartDates',
            'salesChartCounts',
            'salesChartRevenue',
            'subscriptionsChartCounts',
            'recentUsers'
        ));
    }
}
