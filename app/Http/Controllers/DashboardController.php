<?php

namespace App\Http\Controllers;

use App\Models\UptimeMonitor;
use App\Models\DomainMonitor;
use App\Models\SSLMonitor;
use App\Models\DNSMonitor;
use App\Models\ApiMonitor;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the comprehensive dashboard.
     */
    public function index(): View
    {
        $user = Auth::user();

        // Get counts for all monitor types
        $uptimeMonitors = UptimeMonitor::where('user_id', $user->id)->get();
        $domainMonitors = DomainMonitor::where('user_id', $user->id)->get();
        $sslMonitors = SSLMonitor::where('user_id', $user->id)->get();
        $dnsMonitors = DNSMonitor::where('user_id', $user->id)->get();
        $apiMonitors = ApiMonitor::where('user_id', $user->id)->get();
        $servers = Server::where('user_id', $user->id)->get();

        // Calculate statistics
        $stats = [
            'uptime' => [
                'total' => $uptimeMonitors->count(),
                'active' => $uptimeMonitors->where('is_active', true)->count(),
                'up' => $uptimeMonitors->where('status', 'up')->count(),
                'down' => $uptimeMonitors->where('status', 'down')->count(),
                'partial' => $uptimeMonitors->where('status', 'partial')->count(),
            ],
            'domain' => [
                'total' => $domainMonitors->count(),
                'active' => $domainMonitors->where('is_active', true)->count(),
                'expiring_soon' => $domainMonitors->where('days_until_expiration', '<=', 30)
                    ->where('days_until_expiration', '>', 0)->count(),
                'expired' => $domainMonitors->where('days_until_expiration', '<=', 0)->count(),
            ],
            'ssl' => [
                'total' => $sslMonitors->count(),
                'active' => $sslMonitors->where('is_active', true)->count(),
                'valid' => $sslMonitors->where('status', 'valid')->count(),
                'expiring_soon' => $sslMonitors->where('days_until_expiration', '<=', 30)
                    ->where('days_until_expiration', '>', 0)->count(),
                'expired' => $sslMonitors->where('status', 'expired')->count(),
                'invalid' => $sslMonitors->where('status', 'invalid')->count(),
            ],
            'dns' => [
                'total' => $dnsMonitors->count(),
                'active' => $dnsMonitors->where('is_active', true)->count(),
                'healthy' => $dnsMonitors->where('status', 'healthy')->count(),
                'unhealthy' => $dnsMonitors->where('status', 'unhealthy')->count(),
            ],
            'api' => [
                'total' => $apiMonitors->count(),
                'active' => $apiMonitors->where('is_active', true)->count(),
                'up' => $apiMonitors->where('status', 'up')->count(),
                'down' => $apiMonitors->where('status', 'down')->count(),
            ],
            'servers' => [
                'total' => $servers->count(),
                'active' => $servers->where('is_active', true)->count(),
                'online' => $servers->filter(function($server) {
                    return $server->isOnline();
                })->count(),
                'offline' => $servers->filter(function($server) {
                    return $server->isOffline();
                })->count(),
                'warning' => $servers->filter(function($server) {
                    $status = $server->getStatus();
                    return $status['status'] === 'warning';
                })->count(),
            ],
        ];

        // Get recent monitors (latest 5 of each type)
        $recentUptime = $uptimeMonitors->sortByDesc('created_at')->take(5)->values();
        $recentDomain = $domainMonitors->sortByDesc('created_at')->take(5)->values();
        $recentSSL = $sslMonitors->sortByDesc('created_at')->take(5)->values();
        $recentDNS = $dnsMonitors->sortByDesc('created_at')->take(5)->values();
        $recentAPI = $apiMonitors->sortByDesc('created_at')->take(5)->values();
        $recentServers = $servers->sortByDesc('created_at')->take(5)->values();

        // Get uptime monitors for slider (maximum 15)
        $sliderUptimeMonitors = $uptimeMonitors->sortByDesc('created_at')->take(15)->values();

        // Generate favicon URLs for slider monitors (limited to 15)
        $uptimeMonitorFavicons = [];
        foreach ($sliderUptimeMonitors as $monitor) {
            $host = parse_url($monitor->url, PHP_URL_HOST);
            $domain = $host ? strtolower(trim($host)) : null;
            $uptimeMonitorFavicons[$monitor->id] = $domain 
                ? "https://www.google.com/s2/favicons?domain={$domain}&sz=32" 
                : null;
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
        foreach ($domainMonitors as $monitor) {
            $domain = $monitor->domain ? strtolower(trim($monitor->domain)) 
                : null;
            $domainMonitorFavicons[$monitor->id] = $domain 
                ? "https://www.google.com/s2/favicons?domain={$domain}&sz=32" 
                : null;
        }

        $sslMonitorFavicons = [];
        foreach ($sslMonitors as $monitor) {
            $domain = $monitor->domain ? strtolower(trim($monitor->domain)) : null;
            $sslMonitorFavicons[$monitor->id] = $domain 
                ? "https://www.google.com/s2/favicons?domain={$domain}&sz=32" 
                : null;
        }

        $dnsMonitorFavicons = [];
        foreach ($dnsMonitors as $monitor) {
            $domain = $monitor->domain ? strtolower(trim($monitor->domain)) : null;
            $dnsMonitorFavicons[$monitor->id] = $domain 
                ? "https://www.google.com/s2/favicons?domain={$domain}&sz=32" 
                : null;
        }

        $apiMonitorFavicons = [];
        foreach ($apiMonitors as $monitor) {
            $host = parse_url($monitor->url, PHP_URL_HOST);
            $domain = $host ? strtolower(trim($host)) : null;
            $apiMonitorFavicons[$monitor->id] = $domain 
                ? "https://www.google.com/s2/favicons?domain={$domain}&sz=32" 
                : null;
        }

        return view('client.dashboard', compact(
            'stats',
            'uptimeMonitors',
            'domainMonitors',
            'sslMonitors',
            'dnsMonitors',
            'apiMonitors',
            'servers',
            'recentUptime',
            'recentDomain',
            'recentSSL',
            'recentDNS',
            'recentAPI',
            'recentServers',
            'sliderUptimeMonitors',
            'uptimeMonitorFavicons',
            'recentUptimeFavicons',
            'domainMonitorFavicons',
            'sslMonitorFavicons',
            'dnsMonitorFavicons',
            'apiMonitorFavicons'
        ));
    }
}

