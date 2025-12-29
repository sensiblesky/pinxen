<?php

namespace App\Http\Controllers;

use App\Models\UptimeMonitor;
use App\Models\DomainMonitor;
use App\Models\SSLMonitor;
use App\Models\UptimeMonitorCheck;
use App\Models\UptimeMonitorAlert;
use App\Models\MonitorCommunicationPreference;
use App\Jobs\SSLMonitorCheckJob;
use App\Jobs\DomainExpirationCheckJob;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UptimeMonitorController extends Controller
{
    /**
     * Display a listing of uptime monitors.
     */
    public function index(): View
    {
        $user = Auth::user();
        
        $monitors = $user->uptimeMonitors()
            ->with(['checks' => function($query) {
                $query->latest('checked_at')->limit(1);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Extract all unique domains from monitors (optimize: bulk load instead of N+1 queries)
        $domains = [];
        foreach ($monitors as $monitor) {
            $host = parse_url($monitor->url, PHP_URL_HOST);
            if ($host) {
                $domain = strtolower(trim($host));
                $domains[$domain] = true;
            }
        }
        $domainList = array_keys($domains);

        // Bulk load DomainMonitor and SSLMonitor records for matching domains
        // Use whereRaw with multiple OR conditions to match normalized domains
        $domainMonitorsMap = [];
        $sslMonitorsMap = [];
        
        if (!empty($domainList)) {
            // Build whereRaw conditions for normalized domain matching
            $domainConditions = [];
            $bindings = [];
            foreach ($domainList as $domain) {
                $domainConditions[] = 'LOWER(TRIM(domain)) = ?';
                $bindings[] = $domain;
            }
            
            // Load DomainMonitors with normalized domain matching
            $domainMonitors = DomainMonitor::where('user_id', $user->id)
                ->whereRaw('(' . implode(' OR ', $domainConditions) . ')', $bindings)
                ->get();
            
            foreach ($domainMonitors as $dm) {
                $normalizedDomain = strtolower(trim($dm->domain));
                $domainMonitorsMap[$normalizedDomain] = $dm;
            }
            
            // Load SSLMonitors with normalized domain matching
            $sslMonitors = SSLMonitor::where('user_id', $user->id)
                ->whereRaw('(' . implode(' OR ', $domainConditions) . ')', $bindings)
                ->get();
            
            foreach ($sslMonitors as $sm) {
                $normalizedDomain = strtolower(trim($sm->domain));
                $sslMonitorsMap[$normalizedDomain] = $sm;
            }
        }

        // Pre-compute favicon and summary info (WHOIS / SSL) for tooltips
        $monitorMeta = [];
        foreach ($monitors as $monitor) {
            $host = parse_url($monitor->url, PHP_URL_HOST);
            $domain = $host ? strtolower(trim($host)) : null;

            $faviconUrl = $domain ? "https://www.google.com/s2/favicons?domain={$domain}&sz=32" : null;

            // Use pre-loaded maps instead of querying
            $whois = $domain && isset($domainMonitorsMap[$domain]) ? $domainMonitorsMap[$domain] : null;
            $ssl = $domain && isset($sslMonitorsMap[$domain]) ? $sslMonitorsMap[$domain] : null;

            $whoisText = $whois && $whois->expiration_date
                ? 'WHOIS: expires ' . $whois->expiration_date->format('Y-m-d')
                : 'WHOIS: not available';

            $sslText = $ssl && $ssl->expiration_date
                ? 'SSL: ' . ($ssl->status ?? 'unknown') . ', expires ' . $ssl->expiration_date->format('Y-m-d')
                : 'SSL: not available';

            $monitorMeta[$monitor->id] = [
                'favicon_url' => $faviconUrl,
                'tooltip' => "{$whoisText}\n{$sslText}",
            ];
        }
        
        return view('client.uptime-monitors.index', [
            'monitors' => $monitors,
            'monitorMeta' => $monitorMeta,
        ]);
    }

    /**
     * Show the form for creating a new uptime monitor.
     */
    public function create(): View
    {
        return view('client.uptime-monitors.create');
    }

    /**
     * Store a newly created uptime monitor.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => [
                'required', 
                'url', 
                'max:500',
                function ($attribute, $value, $fail) use ($user) {
                    $url = rtrim(strtolower(trim($value)), '/');
                    $exists = UptimeMonitor::where('user_id', $user->id)
                        ->whereRaw('LOWER(TRIM(TRAILING "/" FROM url)) = ?', [$url])
                        ->exists();
                    
                    if ($exists) {
                        $fail('You already have an uptime monitor for this URL. Please edit the existing monitor instead.');
                    }
                },
            ],
            'check_interval' => ['required', 'integer', 'in:1,3,5,10,30,60'], // Only allowed intervals
            'timeout' => ['required', 'integer', 'min:5', 'max:300'], // 5 to 300 seconds
            'expected_status_code' => ['required'], // Will be validated separately for custom or predefined
            'expected_status_code_custom' => ['nullable', 'integer', 'min:100', 'max:599'], // Custom status code validation
            'keyword_present' => ['nullable', 'string', 'max:255'],
            'keyword_absent' => ['nullable', 'string', 'max:255'],
            'check_ssl' => ['nullable', 'boolean'],
            'request_method' => ['nullable', 'string', 'in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS'],
            'basic_auth_username' => ['nullable', 'string', 'max:255'],
            'basic_auth_password' => ['nullable', 'string', 'max:255'],
            'custom_headers' => ['nullable', 'string'],
            'cache_buster' => ['nullable', 'boolean'],
            'maintenance_start_time' => ['nullable', 'date'],
            'maintenance_end_time' => ['nullable', 'date', 'after_or_equal:maintenance_start_time'],
            // SSL Monitor addon fields
            'create_ssl_monitor' => ['nullable', 'boolean'],
            'ssl_check_interval' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'ssl_alert_expiring_soon' => ['nullable', 'boolean'],
            'ssl_alert_expired' => ['nullable', 'boolean'],
            'ssl_alert_invalid' => ['nullable', 'boolean'],
            'ssl_communication_channels' => ['nullable', 'array'],
            'ssl_communication_channels.*' => ['in:email,sms,whatsapp,telegram,discord'],
            // Domain Monitor addon fields
            'create_domain_monitor' => ['nullable', 'boolean'],
            'domain_alert_30_days' => ['nullable', 'boolean'],
            'domain_alert_5_days' => ['nullable', 'boolean'],
            'domain_alert_daily_under_30' => ['nullable', 'boolean'],
            'domain_communication_channels' => ['nullable', 'array'],
            'domain_communication_channels.*' => ['in:email,sms,whatsapp,telegram,discord'],
            // Confirmation logic fields
            'confirmation_enabled' => ['nullable', 'boolean'],
            'confirmation_probes' => ['nullable', 'integer', 'min:2', 'max:10'],
            'confirmation_threshold' => ['nullable', 'integer', 'min:1', 'max:10'],
            'confirmation_retry_delay' => ['nullable', 'integer', 'min:1', 'max:60'],
            'confirmation_max_retries' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        // Handle custom status code
        $expectedStatusCode = $validated['expected_status_code'];
        if ($expectedStatusCode === 'custom') {
            $expectedStatusCode = $request->input('expected_status_code_custom');
            if (!$expectedStatusCode || !is_numeric($expectedStatusCode) || $expectedStatusCode < 100 || $expectedStatusCode > 599) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['expected_status_code_custom' => 'Please enter a valid status code between 100 and 599.']);
            }
        } elseif (!is_numeric($expectedStatusCode) || $expectedStatusCode < 100 || $expectedStatusCode > 599) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['expected_status_code' => 'Please select a valid status code.']);
        }

        // Parse custom headers from textarea format
        $customHeaders = null;
        if ($request->filled('custom_headers')) {
            $headersText = trim($request->input('custom_headers'));
            if (!empty($headersText)) {
                $headersArray = [];
                $lines = explode("\n", $headersText);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    if (strpos($line, ':') !== false) {
                        [$key, $value] = explode(':', $line, 2);
                        $headersArray[trim($key)] = trim($value);
                    }
                }
                $customHeaders = !empty($headersArray) ? $headersArray : null;
            }
        }

        $monitor = UptimeMonitor::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'url' => $validated['url'],
            'request_method' => $validated['request_method'] ?? 'GET',
            'basic_auth_username' => $validated['basic_auth_username'] ?? null,
            'basic_auth_password' => $validated['basic_auth_password'] ?? null,
            'custom_headers' => $customHeaders,
            'cache_buster' => $validated['cache_buster'] ?? false,
            'maintenance_start_time' => $validated['maintenance_start_time'] ?? null,
            'maintenance_end_time' => $validated['maintenance_end_time'] ?? null,
            'check_interval' => $validated['check_interval'],
            'timeout' => $validated['timeout'],
            'expected_status_code' => (int)$expectedStatusCode,
            'keyword_present' => $validated['keyword_present'] ?? null,
            'keyword_absent' => $validated['keyword_absent'] ?? null,
            'check_ssl' => $request->has('check_ssl') && $request->input('check_ssl') == '1',
            'confirmation_enabled' => $request->has('confirmation_enabled') && $request->input('confirmation_enabled') == '1',
            'confirmation_probes' => $validated['confirmation_probes'] ?? 3,
            'confirmation_threshold' => $validated['confirmation_threshold'] ?? 2,
            'confirmation_retry_delay' => $validated['confirmation_retry_delay'] ?? 5,
            'confirmation_max_retries' => $validated['confirmation_max_retries'] ?? 3,
            'is_active' => true,
            'status' => 'unknown',
            'next_check_at' => now(), // Set to now() so it gets checked immediately
        ]);

        // Create SSL monitor if requested
        $sslMonitorCreated = false;
        if ($request->has('create_ssl_monitor') && $request->input('create_ssl_monitor') == '1') {
            // Extract domain from URL
            $parsedUrl = parse_url($validated['url']);
            $domain = $parsedUrl['host'] ?? null;
            
            if ($domain) {
                // Remove port if present
                $domain = preg_replace('/:\d+$/', '', $domain);
                $domain = strtolower(trim($domain));
                
                // Check if SSL monitor already exists for this domain
                $existingSslMonitor = SSLMonitor::where('user_id', $user->id)
                    ->where('domain', $domain)
                    ->first();
                
                if (!$existingSslMonitor) {
                    // Validate SSL communication channels
                    $sslChannels = $validated['ssl_communication_channels'] ?? [];
                    if (empty($sslChannels)) {
                        $sslChannels = ['email']; // Default to email if none selected
                    }
                    
                    $sslMonitor = SSLMonitor::create([
                        'user_id' => $user->id,
                        'name' => $validated['name'] . ' (SSL)',
                        'domain' => $domain,
                        'check_interval' => $validated['ssl_check_interval'] ?? 60,
                        'alert_expiring_soon' => $request->has('ssl_alert_expiring_soon') && $request->input('ssl_alert_expiring_soon') == '1',
                        'alert_expired' => $request->has('ssl_alert_expired') && $request->input('ssl_alert_expired') == '1',
                        'alert_invalid' => $request->has('ssl_alert_invalid') && $request->input('ssl_alert_invalid') == '1',
                        'is_active' => true,
                        'status' => 'unknown',
                    ]);
                    
                    // Save communication preferences
                    foreach ($sslChannels as $channel) {
                        $channelValue = match($channel) {
                            'email' => $user->email,
                            'sms' => $user->phone ?? $user->email,
                            'whatsapp' => $user->phone ?? $user->email,
                            'telegram' => $user->email,
                            'discord' => $user->email,
                            default => $user->email,
                        };
                        
                        MonitorCommunicationPreference::create([
                            'monitor_id' => $sslMonitor->id,
                            'monitor_type' => 'ssl',
                            'communication_channel' => $channel,
                            'channel_value' => $channelValue,
                            'is_enabled' => true,
                        ]);
                    }
                    
                    // Dispatch SSL check job
                    if ($sslMonitor->is_active) {
                        SSLMonitorCheckJob::dispatch($sslMonitor->id);
                    }
                    
                    $sslMonitorCreated = true;
                }
            }
        }

        // Create Domain monitor if requested
        $domainMonitorCreated = false;
        if ($request->has('create_domain_monitor') && $request->input('create_domain_monitor') == '1') {
            // Extract domain from URL
            $parsedUrl = parse_url($validated['url']);
            $domain = $parsedUrl['host'] ?? null;
            
            if ($domain) {
                // Remove port if present
                $domain = preg_replace('/:\d+$/', '', $domain);
                $domain = strtolower(trim($domain));
                
                // Check if Domain monitor already exists for this domain
                $existingDomainMonitor = DomainMonitor::where('user_id', $user->id)
                    ->where('domain', $domain)
                    ->first();
                
                if (!$existingDomainMonitor) {
                    // Validate Domain communication channels
                    $domainChannels = $validated['domain_communication_channels'] ?? [];
                    if (empty($domainChannels)) {
                        $domainChannels = ['email']; // Default to email if none selected
                    }
                    
                    $domainMonitor = DomainMonitor::create([
                        'user_id' => $user->id,
                        'name' => $validated['name'] . ' (Domain)',
                        'domain' => $domain,
                        'alert_30_days' => $request->has('domain_alert_30_days') && $request->input('domain_alert_30_days') == '1',
                        'alert_5_days' => $request->has('domain_alert_5_days') && $request->input('domain_alert_5_days') == '1',
                        'alert_daily_under_30' => $request->has('domain_alert_daily_under_30') && $request->input('domain_alert_daily_under_30') == '1',
                        'is_active' => true,
                    ]);
                    
                    // Save communication preferences
                    foreach ($domainChannels as $channel) {
                        $channelValue = match($channel) {
                            'email' => $user->email,
                            'sms' => $user->phone ?? $user->email,
                            'whatsapp' => $user->phone ?? $user->email,
                            'telegram' => $user->email,
                            'discord' => $user->email,
                            default => $user->email,
                        };
                        
                        MonitorCommunicationPreference::create([
                            'monitor_id' => $domainMonitor->id,
                            'monitor_type' => 'domain',
                            'communication_channel' => $channel,
                            'channel_value' => $channelValue,
                            'is_enabled' => true,
                        ]);
                    }
                    
                    // Dispatch Domain check job
                    if ($domainMonitor->is_active) {
                        DomainExpirationCheckJob::dispatch($domainMonitor->id);
                    }
                    
                    $domainMonitorCreated = true;
                }
            }
        }

        $successMessage = 'Uptime monitor created successfully.';
        $addons = [];
        if ($sslMonitorCreated) {
            $addons[] = 'SSL monitor';
        }
        if ($domainMonitorCreated) {
            $addons[] = 'Domain monitor';
        }
        if (!empty($addons)) {
            $successMessage .= ' ' . implode(' and ', $addons) . ' has also been created for this domain.';
        }

        return redirect()->route('uptime-monitors.index')
            ->with('success', $successMessage);
    }

    /**
     * Display the specified uptime monitor.
     */
    public function show(Request $request, UptimeMonitor $uptimeMonitor): View
    {
        // Ensure monitor belongs to authenticated user
        if ($uptimeMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        // Get time range filter - default to 24h
        $range = $request->input('range', '24h');
        $startDate = null;
        $endDate = null;

        // Calculate date range based on filter
        switch ($range) {
            case '24h':
            default:
                $startDate = now()->subHours(24);
                break;
            case '7d':
                $startDate = now()->subDays(7);
                break;
            case '30d':
                $startDate = now()->subDays(30);
                break;
            case 'custom':
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                    $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
                }
                break;
            case 'all':
                // No date filter - show all data
                break;
        }

        // Increase memory limit for show page
        ini_set('memory_limit', '256M');
        
        // Build query with optional date filter (clear orderBy from relationship for aggregation queries)
        $checksQuery = $uptimeMonitor->checks()->reorder();
        if ($startDate) {
            $checksQuery->where('checked_at', '>=', $startDate);
        }
        if ($endDate) {
            $checksQuery->where('checked_at', '<=', $endDate);
        }

        // Calculate statistics using database aggregation (much faster, uses less memory)
        $totalChecks = (clone $checksQuery)->count();
        $upChecks = (clone $checksQuery)->where('status', 'up')->count();
        $downChecks = (clone $checksQuery)->where('status', 'down')->count();
        $uptimePercentage = $totalChecks > 0 ? round(($upChecks / $totalChecks) * 100, 2) : 0;
        
        // Get response time stats using aggregation (clear orderBy from relationship)
        $responseTimeStats = $uptimeMonitor->checks()
            ->reorder() // Clear any orderBy from relationship definition
            ->where('status', 'up')
            ->whereNotNull('response_time');
        
        // Apply date filters if set
        if ($startDate) {
            $responseTimeStats->where('checked_at', '>=', $startDate);
        }
        if ($endDate) {
            $responseTimeStats->where('checked_at', '<=', $endDate);
        }
        
        $responseTimeStats = $responseTimeStats
            ->selectRaw('AVG(response_time) as avg_response, MIN(response_time) as min_response, MAX(response_time) as max_response')
            ->first();
        
        $avgResponseTime = $responseTimeStats ? round($responseTimeStats->avg_response, 2) : 0;
        $minResponseTime = $responseTimeStats ? round($responseTimeStats->min_response, 2) : 0;
        $maxResponseTime = $responseTimeStats ? round($responseTimeStats->max_response, 2) : 0;
        
        // Get daily status timeline data for last 90 days - use aggregation instead of loading all
        $timelineStartDate = now()->subDays(90)->startOfDay();
        
        // Use database aggregation to get daily summaries instead of loading all checks
        $dailySummaries = $uptimeMonitor->checks()
            ->reorder() // Clear any orderBy from relationship definition
            ->where('checked_at', '>=', $timelineStartDate)
            ->selectRaw('DATE(checked_at) as check_date, 
                        COUNT(*) as total_count,
                        SUM(CASE WHEN status = "up" THEN 1 ELSE 0 END) as up_count,
                        SUM(CASE WHEN status = "down" THEN 1 ELSE 0 END) as down_count')
            ->groupBy('check_date')
            ->orderBy('check_date', 'asc')
            ->get();
        
        // For timeline incidents, we need to sample checks (limit to prevent memory issues)
        $timelineChecks = $uptimeMonitor->checks()
            ->where('checked_at', '>=', $timelineStartDate)
            ->select('id', 'status', 'checked_at')
            ->orderBy('checked_at', 'asc')
            ->limit(5000) // Limit to prevent memory exhaustion
            ->get();
        
        $dailyStatusData = [];
        $days = [];
        $currentDate = $timelineStartDate->copy();
        
        // Initialize all days in the range
        while ($currentDate->lte(now()->startOfDay())) {
            $dayKey = $currentDate->format('Y-m-d');
            $days[$dayKey] = [
                'date' => $currentDate->copy(),
                'up_count' => 0,
                'down_count' => 0,
                'total_count' => 0,
                'incidents' => [],
                'downtime_duration' => 0, // in seconds
            ];
            $currentDate->addDay();
        }
        
        // Use daily summaries for faster processing
        foreach ($dailySummaries as $summary) {
            $dayKey = $summary->check_date;
            if (isset($days[$dayKey])) {
                $days[$dayKey]['total_count'] = $summary->total_count;
                $days[$dayKey]['up_count'] = $summary->up_count;
                $days[$dayKey]['down_count'] = $summary->down_count;
            }
        }
        
        // For incidents, process limited timeline checks (already limited to 5000)
        $previousCheck = null;
        foreach ($timelineChecks as $check) {
            $checkDate = $check->checked_at instanceof \Carbon\Carbon 
                ? $check->checked_at 
                : \Carbon\Carbon::parse($check->checked_at);
            $dayKey = $checkDate->format('Y-m-d');
            
            if (isset($days[$dayKey])) {
                // Track incidents (only for down status)
                if ($check->status === 'down') {
                    $lastIncident = end($days[$dayKey]['incidents']);
                    if ($lastIncident && $previousCheck && $previousCheck->status === 'down') {
                        $days[$dayKey]['incidents'][count($days[$dayKey]['incidents']) - 1]['end'] = $checkDate;
                    } else {
                        $days[$dayKey]['incidents'][] = [
                            'start' => $checkDate,
                            'end' => $checkDate,
                        ];
                    }
                }
            }
            $previousCheck = $check;
        }
        
        // Calculate uptime percentage and downtime duration for each day
        foreach ($days as $dayKey => $dayData) {
            $uptimePercent = $dayData['total_count'] > 0 
                ? round(($dayData['up_count'] / $dayData['total_count']) * 100, 2) 
                : null;
            
            // Calculate total downtime duration for the day
            // Estimate downtime based on check interval and consecutive down checks
            $totalDowntime = 0;
            $checkIntervalSeconds = $uptimeMonitor->check_interval * 60;
            
            foreach ($dayData['incidents'] as $incident) {
                // Calculate duration between start and end of incident
                $duration = $incident['end']->diffInSeconds($incident['start']);
                // If incident spans multiple checks, add estimated duration
                // Otherwise, assume minimum downtime of one check interval
                if ($duration > 0) {
                    $totalDowntime += $duration;
                } else {
                    // Single down check - estimate downtime as check interval
                    $totalDowntime += $checkIntervalSeconds;
                }
            }
            
            $dailyStatusData[] = [
                'date' => $dayData['date']->format('Y-m-d'),
                'uptime_percent' => $uptimePercent,
                'status' => $uptimePercent === null ? 'unknown' : ($uptimePercent >= 99.9 ? 'up' : 'down'),
                'up_count' => $dayData['up_count'],
                'down_count' => $dayData['down_count'],
                'total_count' => $dayData['total_count'],
                'incidents_count' => count($dayData['incidents']),
                'downtime_duration' => $totalDowntime,
            ];
        }
        
        // Calculate overall 90-day uptime using aggregation
        $timelineStats = $uptimeMonitor->checks()
            ->reorder() // Clear any orderBy from relationship definition
            ->where('checked_at', '>=', $timelineStartDate)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = "up" THEN 1 ELSE 0 END) as up_count')
            ->first();
        
        $totalTimelineChecks = $timelineStats->total ?? 0;
        $totalTimelineUp = $timelineStats->up_count ?? 0;
        $overallUptime90Days = $totalTimelineChecks > 0 
            ? round(($totalTimelineUp / $totalTimelineChecks) * 100, 2) 
            : 100;
        
        // Chart data - use database aggregation and limit points
        $groupFormat = 'Y-m-d H:00';
        if ($range === '30d' || ($startDate && $endDate && $startDate->diffInDays($endDate) > 30)) {
            $groupFormat = 'Y-m-d';
        } elseif ($range === 'all') {
            // For "all", default to daily grouping to limit data points
            $groupFormat = 'Y-m-d';
        }
        
        // Use database aggregation for chart data instead of loading all records
        $chartQuery = (clone $checksQuery);
        
        // Build group by clause based on format
        if ($groupFormat === 'Y-m-d') {
            $chartQuery->selectRaw('DATE(checked_at) as time_key,
                                   COUNT(*) as total_count,
                                   SUM(CASE WHEN status = "up" THEN 1 ELSE 0 END) as up_count,
                                   AVG(CASE WHEN status = "up" AND response_time IS NOT NULL THEN response_time ELSE NULL END) as avg_response_time')
                      ->groupBy('time_key')
                      ->orderBy('time_key', 'asc');
        } else {
            $chartQuery->selectRaw('DATE_FORMAT(checked_at, "%Y-%m-%d %H:00") as time_key,
                                   COUNT(*) as total_count,
                                   SUM(CASE WHEN status = "up" THEN 1 ELSE 0 END) as up_count,
                                   AVG(CASE WHEN status = "up" AND response_time IS NOT NULL THEN response_time ELSE NULL END) as avg_response_time')
                      ->groupBy('time_key')
                      ->orderBy('time_key', 'asc');
        }
        
        // Limit to max 300 data points to prevent memory issues
        $chartData = $chartQuery->limit(300)->get();
        
        $responseTimeData = [];
        $uptimeData = [];
        
        foreach ($chartData as $row) {
            if ($groupFormat === 'Y-m-d') {
                $timeCarbon = \Carbon\Carbon::createFromFormat('Y-m-d', $row->time_key)->startOfDay();
            } else {
                $timeCarbon = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $row->time_key);
            }
            
            $responseTimeData[] = [
                'x' => $timeCarbon->toIso8601String(),
                'y' => $row->avg_response_time ? round($row->avg_response_time, 2) : null
            ];
            
            $uptimeData[] = [
                'x' => $timeCarbon->toIso8601String(),
                'y' => $row->total_count > 0 ? round(($row->up_count / $row->total_count) * 100, 2) : 0
            ];
        }
        
        $statusDistribution = [
            ['label' => 'Up', 'value' => $upChecks, 'color' => '#28a745'],
            ['label' => 'Down', 'value' => $downChecks, 'color' => '#dc3545'],
        ];
        
        // Format range text for display
        $rangeText = 'All Time';
        if ($range === '24h') {
            $rangeText = 'Last 24 Hours';
        } elseif ($range === '7d') {
            $rangeText = 'Last 7 Days';
        } elseif ($range === '30d') {
            $rangeText = 'Last 30 Days';
        } elseif ($range === 'custom' && $startDate && $endDate) {
            $rangeText = $startDate->format('M d, Y') . ' - ' . $endDate->format('M d, Y');
        }

        return view('client.uptime-monitors.show', [
            'monitor' => $uptimeMonitor,
            'totalChecks' => $totalChecks,
            'upChecks' => $upChecks,
            'downChecks' => $downChecks,
            'uptimePercentage' => $uptimePercentage,
            'avgResponseTime' => $avgResponseTime,
            'minResponseTime' => $minResponseTime,
            'maxResponseTime' => $maxResponseTime,
            'responseTimeData' => $responseTimeData,
            'uptimeData' => $uptimeData,
            'statusDistribution' => $statusDistribution,
            'selectedRange' => $range,
            'rangeText' => $rangeText,
            'startDate' => $startDate ? $startDate->format('Y-m-d') : null,
            'endDate' => $endDate ? $endDate->format('Y-m-d') : null,
            'dailyStatusData' => $dailyStatusData,
            'overallUptime90Days' => $overallUptime90Days,
        ]);
    }

    /**
     * Show the form for editing the specified uptime monitor.
     */
    public function edit(UptimeMonitor $uptimeMonitor): View
    {
        // Ensure monitor belongs to authenticated user
        if ($uptimeMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        return view('client.uptime-monitors.edit', [
            'monitor' => $uptimeMonitor,
        ]);
    }

    /**
     * Update the specified uptime monitor.
     */
    public function update(Request $request, UptimeMonitor $uptimeMonitor): RedirectResponse
    {
        // Ensure monitor belongs to authenticated user
        if ($uptimeMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => [
                'required', 
                'url', 
                'max:500',
                function ($attribute, $value, $fail) use ($user, $uptimeMonitor) {
                    $url = rtrim(strtolower(trim($value)), '/');
                    $exists = UptimeMonitor::where('user_id', $user->id)
                        ->where('id', '!=', $uptimeMonitor->id)
                        ->whereRaw('LOWER(TRIM(TRAILING "/" FROM url)) = ?', [$url])
                        ->exists();
                    
                    if ($exists) {
                        $fail('You already have an uptime monitor for this URL. Please use a different URL or edit the existing monitor.');
                    }
                },
            ],
            'check_interval' => ['required', 'integer', 'in:1,3,5,10,30,60'], // Only allowed intervals
            'timeout' => ['required', 'integer', 'min:5', 'max:300'],
            'expected_status_code' => ['required'], // Will be validated separately for custom or predefined
            'expected_status_code_custom' => ['nullable', 'integer', 'min:100', 'max:599'], // Custom status code validation
            'keyword_present' => ['nullable', 'string', 'max:255'],
            'keyword_absent' => ['nullable', 'string', 'max:255'],
            'check_ssl' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'request_method' => ['nullable', 'string', 'in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS'],
            'basic_auth_username' => ['nullable', 'string', 'max:255'],
            'basic_auth_password' => ['nullable', 'string', 'max:255'],
            'custom_headers' => ['nullable', 'string'],
            'cache_buster' => ['nullable', 'boolean'],
            'maintenance_start_time' => ['nullable', 'date'],
            'maintenance_end_time' => ['nullable', 'date', 'after_or_equal:maintenance_start_time'],
            // Confirmation logic fields
            'confirmation_enabled' => ['nullable', 'boolean'],
            'confirmation_probes' => ['nullable', 'integer', 'min:2', 'max:10'],
            'confirmation_threshold' => ['nullable', 'integer', 'min:1', 'max:10'],
            'confirmation_retry_delay' => ['nullable', 'integer', 'min:1', 'max:60'],
            'confirmation_max_retries' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        // Handle custom status code for update
        $expectedStatusCode = $validated['expected_status_code'];
        if ($expectedStatusCode === 'custom') {
            $expectedStatusCode = $request->input('expected_status_code_custom');
            if (!$expectedStatusCode || !is_numeric($expectedStatusCode) || $expectedStatusCode < 100 || $expectedStatusCode > 599) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['expected_status_code_custom' => 'Please enter a valid status code between 100 and 599.']);
            }
        } elseif (!is_numeric($expectedStatusCode) || $expectedStatusCode < 100 || $expectedStatusCode > 599) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['expected_status_code' => 'Please select a valid status code.']);
        }

        // Parse custom headers from textarea format
        $customHeaders = null;
        if ($request->filled('custom_headers')) {
            $headersText = trim($request->input('custom_headers'));
            if (!empty($headersText)) {
                $headersArray = [];
                $lines = explode("\n", $headersText);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    if (strpos($line, ':') !== false) {
                        [$key, $value] = explode(':', $line, 2);
                        $headersArray[trim($key)] = trim($value);
                    }
                }
                $customHeaders = !empty($headersArray) ? $headersArray : null;
            }
        }

        // Prepare update data
        $updateData = [
            'name' => $validated['name'],
            'url' => $validated['url'],
            'check_interval' => $validated['check_interval'],
            'timeout' => $validated['timeout'],
            'expected_status_code' => (int)$expectedStatusCode,
            'keyword_present' => $validated['keyword_present'] ?? null,
            'keyword_absent' => $validated['keyword_absent'] ?? null,
            'check_ssl' => $request->has('check_ssl') && $request->input('check_ssl') == '1',
            'is_active' => $validated['is_active'] ?? true,
            'request_method' => $validated['request_method'] ?? 'GET',
            'basic_auth_username' => $validated['basic_auth_username'] ?? null,
            'custom_headers' => $customHeaders,
            'cache_buster' => $validated['cache_buster'] ?? false,
            'maintenance_start_time' => $validated['maintenance_start_time'] ?? null,
            'maintenance_end_time' => $validated['maintenance_end_time'] ?? null,
        ];

        // Only update password if provided
        if ($request->filled('basic_auth_password')) {
            $updateData['basic_auth_password'] = $validated['basic_auth_password'];
        }

        // If check_interval changed, recalculate next_check_at
        if ($uptimeMonitor->check_interval != $validated['check_interval']) {
            if ($uptimeMonitor->last_checked_at) {
                // Recalculate based on last check time
                $updateData['next_check_at'] = $uptimeMonitor->last_checked_at->copy()->addMinutes((int) $validated['check_interval']);
            } else {
                // Never checked, set to now
                $updateData['next_check_at'] = now();
            }
        }

        $uptimeMonitor->update($updateData);

        return redirect()->route('uptime-monitors.show', $uptimeMonitor->uid)
            ->with('success', 'Uptime monitor updated successfully.');
    }

    /**
     * Remove the specified uptime monitor.
     */
    public function destroy(UptimeMonitor $uptimeMonitor): RedirectResponse
    {
        // Ensure monitor belongs to authenticated user
        if ($uptimeMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $uptimeMonitor->delete();

        return redirect()->route('uptime-monitors.index')
            ->with('success', 'Uptime monitor deleted successfully.');
    }

    /**
     * Get chart data for different time ranges.
     */
    public function getChartData(Request $request, UptimeMonitor $uptimeMonitor): \Illuminate\Http\JsonResponse
    {
        // Ensure monitor belongs to authenticated user
        if ($uptimeMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $range = $request->input('range', '24h');
        $startDate = null;
        $endDate = null;
        $groupFormat = 'Y-m-d H:00'; // Default: group by hour
        
        // Calculate time range
        switch ($range) {
            case '7d':
                $startDate = now()->subDays(7);
                $groupFormat = 'Y-m-d H:00'; // Group by hour
                break;
            case '30d':
                $startDate = now()->subDays(30);
                $groupFormat = 'Y-m-d'; // Group by day
                break;
            case 'custom':
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                    $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
                    $daysDiff = $startDate->diffInDays($endDate);
                    // If more than 30 days, group by day, otherwise by hour
                    $groupFormat = $daysDiff > 30 ? 'Y-m-d' : 'Y-m-d H:00';
                }
                break;
            case '24h':
            default:
                $startDate = now()->subHours(24);
                $groupFormat = 'Y-m-d H:00'; // Group by hour
                break;
        }

        $checksQuery = $uptimeMonitor->checks();
        if ($startDate) {
            $checksQuery->where('checked_at', '>=', $startDate);
        }
        if ($endDate) {
            $checksQuery->where('checked_at', '<=', $endDate);
        }
        $checksQuery = $checksQuery->orderBy('checked_at', 'asc')->get();
        
        $responseTimeData = [];
        $uptimeData = [];
        
        if ($checksQuery->isNotEmpty()) {
            $grouped = $checksQuery->groupBy(function($check) use ($groupFormat) {
                $checkedAt = $check->checked_at instanceof \Carbon\Carbon 
                    ? $check->checked_at 
                    : \Carbon\Carbon::parse($check->checked_at);
                return $checkedAt->format($groupFormat);
            });
            
            foreach ($grouped as $timeKey => $checks) {
                $upCount = $checks->where('status', 'up')->count();
                $totalCount = $checks->count();
                
                $upChecksWithResponseTime = $checks->where('status', 'up')
                    ->filter(function($check) {
                        return !is_null($check->response_time) && $check->response_time > 0;
                    });
                
                $avgResponse = $upChecksWithResponseTime->isNotEmpty() 
                    ? $upChecksWithResponseTime->avg('response_time') 
                    : null;
                
                // Parse the time key based on format
                if ($range === '30d') {
                    $timeCarbon = \Carbon\Carbon::createFromFormat('Y-m-d', $timeKey)->startOfDay();
                } else {
                    $timeCarbon = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $timeKey);
                }
                
                $responseTimeData[] = [
                    'x' => $timeCarbon->toIso8601String(),
                    'y' => $avgResponse ? round($avgResponse, 2) : null
                ];
                
                $uptimeData[] = [
                    'x' => $timeCarbon->toIso8601String(),
                    'y' => $totalCount > 0 ? round(($upCount / $totalCount) * 100, 2) : 0
                ];
            }
        }

        return response()->json([
            'responseTimeData' => $responseTimeData,
            'uptimeData' => $uptimeData,
        ]);
    }

    /**
     * Get checks data for DataTables (server-side processing).
     */
    public function getChecksData(Request $request, UptimeMonitor $uptimeMonitor): \Illuminate\Http\JsonResponse
    {
        // Ensure monitor belongs to authenticated user
        if ($uptimeMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $query = $uptimeMonitor->checks();

        // Global search
        $searchValue = $request->input('search.value');
        if (!empty($searchValue)) {
            $query->where(function($q) use ($searchValue) {
                $q->where('status', 'like', '%' . $searchValue . '%')
                  ->orWhere('status_code', 'like', '%' . $searchValue . '%')
                  ->orWhere('error_message', 'like', '%' . $searchValue . '%')
                  ->orWhere('failure_type', 'like', '%' . $searchValue . '%')
                  ->orWhere('failure_classification', 'like', '%' . $searchValue . '%');
            });
        }

        // Get total count before pagination
        $totalRecords = $query->count();

        // Apply ordering
        $orderColumn = $request->input('order.0.column', 5);
        $orderDir = $request->input('order.0.dir', 'desc');
        
        $columnMap = [
            1 => 'status',
            2 => 'response_time',
            3 => 'status_code',
            4 => 'layer_checks', // Layer checks column (not directly sortable)
            5 => 'error_message',
            6 => 'checked_at',
        ];
        
        $orderBy = $columnMap[$orderColumn] ?? 'checked_at';
        $query->orderBy($orderBy, $orderDir);

        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $checks = $query->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = [];
        $rowNumber = $start + 1;
        
        foreach ($checks as $check) {
            $data[] = [
                'row_number' => $rowNumber++,
                'status' => $check->status,
                'response_time' => $check->response_time,
                'status_code' => $check->status_code,
                'error_message' => $check->error_message,
                'failure_type' => $check->failure_type,
                'failure_classification' => $check->failure_classification,
                'layer_checks' => $check->layer_checks,
                'probe_results' => $check->probe_results,
                'is_confirmed' => $check->is_confirmed,
                'probes_failed' => $check->probes_failed,
                'probes_total' => $check->probes_total,
                'checked_at' => $check->checked_at->format('Y-m-d H:i:s'),
                'checked_at_human' => $check->checked_at->diffForHumans(),
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ]);
    }

    /**
     * Get alerts data for DataTables (server-side processing).
     */
    public function getAlertsData(Request $request, UptimeMonitor $uptimeMonitor): \Illuminate\Http\JsonResponse
    {
        // Ensure monitor belongs to authenticated user
        if ($uptimeMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $query = $uptimeMonitor->alerts();
        
        // Apply date range filter if provided
        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', \Carbon\Carbon::parse($request->input('start_date'))->startOfDay());
        }
        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', \Carbon\Carbon::parse($request->input('end_date'))->endOfDay());
        }

        // Global search
        $searchValue = $request->input('search.value');
        if (!empty($searchValue)) {
            $query->where(function($q) use ($searchValue) {
                $q->where('alert_type', 'like', '%' . $searchValue . '%')
                  ->orWhere('communication_channel', 'like', '%' . $searchValue . '%')
                  ->orWhere('status', 'like', '%' . $searchValue . '%')
                  ->orWhere('message', 'like', '%' . $searchValue . '%');
            });
        }

        // Get total count before pagination
        $totalRecords = $query->count();

        // Apply ordering
        $orderColumn = $request->input('order.0.column', 5);
        $orderDir = $request->input('order.0.dir', 'desc');
        
        $columnMap = [
            1 => 'alert_type',
            2 => 'communication_channel',
            3 => 'status',
            4 => 'message',
            5 => 'sent_at',
        ];
        
        $orderBy = $columnMap[$orderColumn] ?? 'sent_at';
        if ($orderBy === 'sent_at') {
            $query->orderBy('created_at', $orderDir);
        } else {
            $query->orderBy($orderBy, $orderDir);
        }

        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $alerts = $query->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = [];
        $rowNumber = $start + 1;
        
        foreach ($alerts as $alert) {
            $data[] = [
                'row_number' => $rowNumber++,
                'alert_type' => $alert->alert_type,
                'communication_channel' => $alert->communication_channel,
                'status' => $alert->status,
                'message' => $alert->message,
                'sent_at' => $alert->sent_at ? $alert->sent_at->format('Y-m-d H:i:s') : null,
                'sent_at_human' => $alert->sent_at ? $alert->sent_at->diffForHumans() : null,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ]);
    }
}
