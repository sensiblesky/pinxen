<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\ServiceCategory;
use App\Models\MonitoringService;
use App\Models\MonitorCommunicationPreference;
use App\Jobs\MonitorCheckJob;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MonitorController extends Controller
{
    /**
     * Display a listing of monitors by category.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $categorySlug = $request->get('category', 'web');
        $serviceKey = $request->get('service'); // Filter by monitoring service (uptime, dns, whois, etc.)
        
        $category = ServiceCategory::where('slug', $categorySlug)->firstOrFail();
        
        // Build query
        $query = $user->monitors()
            ->where('service_category_id', $category->id)
            ->with(['serviceCategory', 'monitoringService', 'checks' => function($query) {
                $query->latest('checked_at')->limit(1);
            }]);
        
        // Filter by monitoring service if specified
        if ($serviceKey) {
            $query->whereHas('monitoringService', function($q) use ($serviceKey) {
                $q->where('key', $serviceKey);
            });
        }
        
        $monitors = $query->orderBy('created_at', 'desc')->get();
        
        // Get available monitoring services for this category
        // For web category, get all services. For server, filter appropriately
        $availableServices = MonitoringService::active()
            ->when($categorySlug === 'web', function($q) {
                // Web category can have: core, performance, security, content services
                $q->whereIn('category', ['core', 'performance', 'security', 'content', 'email_api']);
            })
            ->when($categorySlug === 'server', function($q) {
                // Server category can have: infrastructure services
                $q->whereIn('category', ['infrastructure']);
            })
            ->orderBy('category')
            ->orderBy('order')
            ->get()
            ->groupBy('category');
        
        return view('client.monitors.index', [
            'category' => $category,
            'monitors' => $monitors,
            'categories' => ServiceCategory::where('is_active', true)->orderBy('order')->get(),
            'availableServices' => $availableServices,
            'selectedService' => $serviceKey,
        ]);
    }

    /**
     * Show the form for creating a new monitor.
     */
    public function create(Request $request): View
    {
        $categorySlug = $request->get('category', 'web');
        $category = ServiceCategory::where('slug', $categorySlug)->firstOrFail();
        
        $categories = ServiceCategory::where('is_active', true)->orderBy('order')->get();
        
        // Get monitoring services for this category
        // For web category, show all services. For server, filter appropriately
        $monitoringServices = MonitoringService::active()
            ->orderBy('category')
            ->orderBy('order')
            ->get()
            ->groupBy('category');
        
        return view('client.monitors.create', [
            'category' => $category,
            'categories' => $categories,
            'monitoringServices' => $monitoringServices,
        ]);
    }

    /**
     * Store a newly created monitor.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'service_category_id' => ['required', 'exists:service_categories,id'],
            'monitoring_service_id' => ['required', 'exists:monitoring_services,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:web,server'],
            'url' => ['nullable', 'url', 'max:500'],
            'check_interval' => ['required', 'integer', 'min:1', 'max:1440'], // 1 minute to 24 hours
            'timeout' => ['required', 'integer', 'min:5', 'max:300'], // 5 to 300 seconds
            'expected_status_code' => ['nullable', 'integer', 'min:100', 'max:599'],
            'service_config' => ['nullable', 'array'],
            'communication_channels' => ['required', 'array', 'min:1'],
            'communication_channels.*' => ['required', 'string', 'in:email,sms,whatsapp,telegram,discord'],
            'email' => ['required_if:communication_channels.*,email', 'nullable', 'email', 'max:255'],
            'phone' => ['required_if:communication_channels.*,sms', 'nullable', 'string', 'max:20'],
            'whatsapp_number' => ['required_if:communication_channels.*,whatsapp', 'nullable', 'string', 'max:20'],
            'telegram_chat_id' => ['required_if:communication_channels.*,telegram', 'nullable', 'string', 'max:255'],
            'discord_webhook' => ['required_if:communication_channels.*,discord', 'nullable', 'url', 'max:500'],
        ]);

        // Get the monitoring service to validate config
        $monitoringService = MonitoringService::findOrFail($validated['monitoring_service_id']);
        
        // Build service_config from request
        $serviceConfig = [];
        if ($monitoringService->config_schema) {
            foreach ($monitoringService->config_schema as $key => $schema) {
                if ($request->has($key)) {
                    $value = $request->input($key);
                    // Convert boolean strings to actual booleans
                    if ($schema['type'] === 'boolean' && is_string($value)) {
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    }
                    $serviceConfig[$key] = $value;
                } elseif (isset($schema['default'])) {
                    $serviceConfig[$key] = $schema['default'];
                }
            }
        }
        
        // Create monitor
        $monitor = Monitor::create([
            'user_id' => $user->id,
            'service_category_id' => $validated['service_category_id'],
            'monitoring_service_id' => $validated['monitoring_service_id'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'url' => $validated['url'] ?? null,
            'check_interval' => $validated['check_interval'],
            'timeout' => $validated['timeout'],
            'expected_status_code' => $validated['expected_status_code'] ?? 200,
            'service_config' => $serviceConfig,
            'is_active' => true,
            'status' => 'unknown',
        ]);

        // Create communication preferences
        foreach ($validated['communication_channels'] as $channel) {
            $channelValue = match($channel) {
                'email' => $validated['email'] ?? null,
                'sms' => $validated['phone'] ?? null,
                'whatsapp' => $validated['whatsapp_number'] ?? null,
                'telegram' => $validated['telegram_chat_id'] ?? null,
                'discord' => $validated['discord_webhook'] ?? null,
                default => null,
            };

            if ($channelValue) {
                MonitorCommunicationPreference::create([
                    'monitor_id' => $monitor->id,
                    'monitor_type' => 'monitor',
                    'communication_channel' => $channel,
                    'channel_value' => $channelValue,
                    'is_enabled' => true,
                ]);
            }
        }

        // Reload monitor with relationships to ensure monitoringService is loaded
        $monitor->load('monitoringService');
        
        // Dispatch immediate check for newly created monitor
        if ($monitor->is_active && $monitor->monitoringService && $monitor->monitoringService->key === 'uptime') {
            MonitorCheckJob::dispatch($monitor->id);
            \App\Jobs\MonitorCheckJob::dispatch($monitor->id);
        }

        return redirect()->route('monitors.index', ['category' => $monitor->serviceCategory->slug])
            ->with('success', 'Monitor created successfully. Initial check has been queued.');
    }

    /**
     * Display the specified monitor.
     */
    public function show(Monitor $monitor): View
    {
        // Ensure monitor belongs to authenticated user
        if ($monitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $monitor->load([
            'serviceCategory',
            'monitoringService',
            'communicationPreferences',
            'checks' => function($query) {
                $query->orderBy('checked_at', 'desc')->limit(500); // More data for charts
            },
            'alerts' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(50);
            },
        ]);

        // Calculate statistics for charts and summary cards
        $allChecks = $monitor->checks()->orderBy('checked_at', 'asc')->get();
        
        // Summary statistics
        $totalChecks = $allChecks->count();
        $upChecks = $allChecks->where('status', 'up')->count();
        $downChecks = $allChecks->where('status', 'down')->count();
        $uptimePercentage = $totalChecks > 0 ? round(($upChecks / $totalChecks) * 100, 2) : 0;
        
        // Average response time (only for successful checks)
        $avgResponseTime = $allChecks->where('status', 'up')
            ->whereNotNull('response_time')
            ->avg('response_time');
        $avgResponseTime = $avgResponseTime ? round($avgResponseTime, 2) : 0;
        
        // Min/Max response times
        $minResponseTime = $allChecks->where('status', 'up')
            ->whereNotNull('response_time')
            ->min('response_time');
        $maxResponseTime = $allChecks->where('status', 'up')
            ->whereNotNull('response_time')
            ->max('response_time');
        
        // Last 24 hours data for charts
        $last24Hours = now()->subHours(24);
        
        // Get recent checks from database (not from collection) to ensure proper date filtering
        $recentChecksQuery = $monitor->checks()
            ->where('checked_at', '>=', $last24Hours)
            ->orderBy('checked_at', 'asc')
            ->get();
        
        // Chart data: Response time over time (last 24 hours, grouped by hour)
        $responseTimeData = [];
        $uptimeData = [];
        $statusData = [];
        
        if ($recentChecksQuery->isNotEmpty()) {
            // Group by hour for better visualization
            $groupedByHour = $recentChecksQuery->groupBy(function($check) {
                // Ensure checked_at is a Carbon instance
                $checkedAt = $check->checked_at instanceof \Carbon\Carbon 
                    ? $check->checked_at 
                    : \Carbon\Carbon::parse($check->checked_at);
                return $checkedAt->format('Y-m-d H:00');
            });
            
            foreach ($groupedByHour as $hour => $checks) {
                $upCount = $checks->where('status', 'up')->count();
                $totalCount = $checks->count();
                
                // Calculate average response time for up checks
                $upChecksWithResponseTime = $checks->where('status', 'up')
                    ->filter(function($check) {
                        return !is_null($check->response_time) && $check->response_time > 0;
                    });
                
                $avgResponse = $upChecksWithResponseTime->isNotEmpty() 
                    ? $upChecksWithResponseTime->avg('response_time') 
                    : null;
                
                $responseTimeData[] = [
                    'x' => $hour,
                    'y' => $avgResponse ? round($avgResponse, 2) : null
                ];
                
                $uptimeData[] = [
                    'x' => $hour,
                    'y' => $totalCount > 0 ? round(($upCount / $totalCount) * 100, 2) : 0
                ];
            }
        } else {
            // If no data in last 24 hours, show all available data (for new monitors)
            if ($allChecks->isNotEmpty()) {
                $groupedByHour = $allChecks->groupBy(function($check) {
                    $checkedAt = $check->checked_at instanceof \Carbon\Carbon 
                        ? $check->checked_at 
                        : \Carbon\Carbon::parse($check->checked_at);
                    return $checkedAt->format('Y-m-d H:00');
                });
                
                foreach ($groupedByHour as $hour => $checks) {
                    $upCount = $checks->where('status', 'up')->count();
                    $totalCount = $checks->count();
                    
                    $upChecksWithResponseTime = $checks->where('status', 'up')
                        ->filter(function($check) {
                            return !is_null($check->response_time) && $check->response_time > 0;
                        });
                    
                    $avgResponse = $upChecksWithResponseTime->isNotEmpty() 
                        ? $upChecksWithResponseTime->avg('response_time') 
                        : null;
                    
                    $responseTimeData[] = [
                        'x' => $hour,
                        'y' => $avgResponse ? round($avgResponse, 2) : null
                    ];
                    
                    $uptimeData[] = [
                        'x' => $hour,
                        'y' => $totalCount > 0 ? round(($upCount / $totalCount) * 100, 2) : 0
                    ];
                }
            }
        }
        
        // Status distribution (pie chart data)
        $statusDistribution = [
            ['label' => 'Up', 'value' => $upChecks, 'color' => '#28a745'],
            ['label' => 'Down', 'value' => $downChecks, 'color' => '#dc3545'],
        ];
        
        // Recent checks for table (last 100)
        $recentChecksForTable = $monitor->checks()
            ->orderBy('checked_at', 'desc')
            ->limit(100)
            ->get();

        return view('client.monitors.show', [
            'monitor' => $monitor,
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
            'recentChecks' => $recentChecksForTable,
        ]);
    }

    /**
     * Show the form for editing the specified monitor.
     */
    public function edit(Monitor $monitor): View
    {
        // Ensure monitor belongs to authenticated user
        if ($monitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $monitor->load(['communicationPreferences', 'monitoringService']);
        $categories = ServiceCategory::where('is_active', true)->orderBy('order')->get();
        
        $monitoringServices = MonitoringService::active()
            ->orderBy('category')
            ->orderBy('order')
            ->get()
            ->groupBy('category');

        return view('client.monitors.edit', [
            'monitor' => $monitor,
            'categories' => $categories,
            'monitoringServices' => $monitoringServices,
        ]);
    }

    /**
     * Update the specified monitor.
     */
    public function update(Request $request, Monitor $monitor): RedirectResponse
    {
        // Ensure monitor belongs to authenticated user
        if ($monitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'monitoring_service_id' => ['required', 'exists:monitoring_services,id'],
            'name' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:500'],
            'check_interval' => ['required', 'integer', 'min:1', 'max:1440'],
            'timeout' => ['required', 'integer', 'min:5', 'max:300'],
            'expected_status_code' => ['nullable', 'integer', 'min:100', 'max:599'],
            'service_config' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'communication_channels' => ['required', 'array', 'min:1'],
            'communication_channels.*' => ['required', 'string', 'in:email,sms,whatsapp,telegram,discord'],
            'email' => ['required_if:communication_channels.*,email', 'nullable', 'email', 'max:255'],
            'phone' => ['required_if:communication_channels.*,sms', 'nullable', 'string', 'max:20'],
            'whatsapp_number' => ['required_if:communication_channels.*,whatsapp', 'nullable', 'string', 'max:20'],
            'telegram_chat_id' => ['required_if:communication_channels.*,telegram', 'nullable', 'string', 'max:255'],
            'discord_webhook' => ['required_if:communication_channels.*,discord', 'nullable', 'url', 'max:500'],
        ]);

        // Get the monitoring service to validate config
        $monitoringService = MonitoringService::findOrFail($validated['monitoring_service_id']);
        
        // Build service_config from request
        $serviceConfig = [];
        if ($monitoringService->config_schema) {
            foreach ($monitoringService->config_schema as $key => $schema) {
                if ($request->has($key)) {
                    $value = $request->input($key);
                    // Convert boolean strings to actual booleans
                    if ($schema['type'] === 'boolean' && is_string($value)) {
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    }
                    $serviceConfig[$key] = $value;
                } elseif (isset($schema['default'])) {
                    $serviceConfig[$key] = $schema['default'];
                }
            }
        }
        
        // Update monitor
        $monitor->update([
            'monitoring_service_id' => $validated['monitoring_service_id'],
            'name' => $validated['name'],
            'url' => $validated['url'] ?? $monitor->url,
            'check_interval' => $validated['check_interval'],
            'timeout' => $validated['timeout'],
            'expected_status_code' => $validated['expected_status_code'] ?? $monitor->expected_status_code,
            'service_config' => $serviceConfig,
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        // Update communication preferences
        // Delete existing preferences
        $monitor->communicationPreferences()->delete();

        // Create new preferences
        foreach ($validated['communication_channels'] as $channel) {
            $channelValue = match($channel) {
                'email' => $validated['email'] ?? null,
                'sms' => $validated['phone'] ?? null,
                'whatsapp' => $validated['whatsapp_number'] ?? null,
                'telegram' => $validated['telegram_chat_id'] ?? null,
                'discord' => $validated['discord_webhook'] ?? null,
                default => null,
            };

            if ($channelValue) {
                MonitorCommunicationPreference::create([
                    'monitor_id' => $monitor->id,
                    'monitor_type' => 'monitor',
                    'communication_channel' => $channel,
                    'channel_value' => $channelValue,
                    'is_enabled' => true,
                ]);
            }
        }

        // Reload monitor with relationships
        $monitor->refresh();
        $monitor->load('monitoringService');
        
        // If monitor was reactivated, dispatch immediate check
        if ($monitor->is_active && $monitor->monitoringService && $monitor->monitoringService->key === 'uptime') {
            // Only check if it hasn't been checked recently (avoid duplicate checks)
            if (!$monitor->last_checked_at || $monitor->last_checked_at->diffInMinutes(now()) >= $monitor->check_interval) {
                MonitorCheckJob::dispatch($monitor->id);
            }
        }

        return redirect()->route('monitors.show', $monitor->uid)
            ->with('success', 'Monitor updated successfully.');
    }

    /**
     * Remove the specified monitor.
     */
    public function destroy(Monitor $monitor): RedirectResponse
    {
        // Ensure monitor belongs to authenticated user
        if ($monitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $categorySlug = $monitor->serviceCategory->slug;
        $monitor->delete();

        return redirect()->route('monitors.index', ['category' => $categorySlug])
            ->with('success', 'Monitor deleted successfully.');
    }
}
