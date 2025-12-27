<?php

namespace App\Http\Controllers;

use App\Models\ApiMonitor;
use App\Models\ApiMonitorCheck;
use App\Models\ApiMonitorAlert;
use App\Models\ApiMonitorDependency;
use App\Models\MonitorCommunicationPreference;
use App\Jobs\ApiMonitorCheckJob;
use App\Services\ApiMonitorService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ApiMonitorController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        
        $monitors = $user->apiMonitors()
            ->with(['checks' => function($query) {
                $query->latest('checked_at')->limit(1);
            }])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('client.api-monitors.index', [
            'monitors' => $monitors,
        ]);
    }

    public function create(): View
    {
        return view('client.api-monitors.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
            'request_method' => ['required', 'in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS'],
            'auth_type' => ['required', 'in:none,bearer,basic,apikey'],
            'auth_token' => ['nullable', 'string', 'max:500'],
            'auth_username' => ['nullable', 'string', 'max:255'],
            'auth_password' => ['nullable', 'string', 'max:255'],
            'auth_header_name' => ['nullable', 'string', 'max:100'],
            'request_headers' => ['nullable', 'string'],
            'request_body' => ['nullable', 'string'],
            'content_type' => ['nullable', 'string', 'max:100'],
            'expected_status_code' => ['required', 'integer', 'min:100', 'max:599'],
            'response_assertions' => ['nullable', 'string'],
            'variable_extraction_rules' => ['nullable', 'string'],
            'monitoring_steps' => ['nullable', 'string'],
            'is_stateful' => ['nullable', 'boolean'],
            'max_latency_ms' => ['nullable', 'integer', 'min:1'],
            'validate_response_body' => ['nullable', 'boolean'],
            'check_interval' => ['required', 'integer', 'in:1,3,5,10,30,60'],
            'timeout' => ['required', 'integer', 'min:5', 'max:300'],
            'check_ssl' => ['nullable', 'boolean'],
            'communication_channels' => ['required', 'array', 'min:1'],
            'communication_channels.*' => ['in:email,sms,whatsapp,telegram,discord'],
        ]);

        // Parse request headers
        $requestHeaders = [];
        if ($validated['request_headers']) {
            $lines = explode("\n", $validated['request_headers']);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                if (strpos($line, ':') !== false) {
                    [$name, $value] = explode(':', $line, 2);
                    $requestHeaders[] = [
                        'name' => trim($name),
                        'value' => trim($value),
                    ];
                }
            }
        }

        // Parse response assertions
        $responseAssertions = [];
        if ($validated['response_assertions']) {
            $assertions = json_decode($validated['response_assertions'], true);
            if (is_array($assertions)) {
                $responseAssertions = $assertions;
            }
        }

        // Parse variable extraction rules
        $variableExtractionRules = [];
        if (!empty($validated['variable_extraction_rules'])) {
            $rules = json_decode($validated['variable_extraction_rules'], true);
            if (is_array($rules)) {
                $variableExtractionRules = $rules;
            }
        }

        // Parse monitoring steps
        $monitoringSteps = [];
        if (!empty($validated['monitoring_steps'])) {
            $steps = json_decode($validated['monitoring_steps'], true);
            if (is_array($steps)) {
                $monitoringSteps = $steps;
            }
        }

        // Handle schema drift detection
        $schemaContent = null;
        $schemaParsed = null;
        $schemaUrl = null;
        
        if ($request->has('schema_drift_enabled') && $request->input('schema_drift_enabled') == '1') {
            if ($request->hasFile('schema_file')) {
                // Upload schema file
                $file = $request->file('schema_file');
                $schemaContent = file_get_contents($file->getRealPath());
            } elseif (!empty($validated['schema_content'])) {
                // Use provided schema content
                $schemaContent = $validated['schema_content'];
            } elseif (!empty($validated['schema_url'])) {
                // Fetch schema from URL
                $schemaUrl = $validated['schema_url'];
                try {
                    $response = \Illuminate\Support\Facades\Http::get($schemaUrl);
                    if ($response->successful()) {
                        $schemaContent = $response->body();
                    }
                } catch (\Exception $e) {
                    // Handle error
                }
            }

            // Parse schema if content is available
            if ($schemaContent) {
                $schemaService = new \App\Services\SchemaDriftService();
                $schemaParsed = $schemaService->parseSchema($schemaContent);
            }
        }

        $monitor = ApiMonitor::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'url' => $validated['url'],
            'request_method' => $validated['request_method'],
            'auth_type' => $validated['auth_type'],
            'auth_token' => $validated['auth_token'] ?? null,
            'auth_username' => $validated['auth_username'] ?? null,
            'auth_password' => $validated['auth_password'] ?? null,
            'auth_header_name' => $validated['auth_header_name'] ?? null,
            'request_headers' => $requestHeaders,
            'request_body' => $validated['request_body'] ?? null,
            'content_type' => $validated['content_type'] ?? 'application/json',
            'expected_status_code' => $validated['expected_status_code'],
            'response_assertions' => $responseAssertions,
            'max_latency_ms' => $validated['max_latency_ms'] ?? null,
            'validate_response_body' => $request->has('validate_response_body') && $request->input('validate_response_body') == '1',
            'check_interval' => $validated['check_interval'],
            'timeout' => $validated['timeout'],
            'check_ssl' => $request->has('check_ssl') && $request->input('check_ssl') == '1',
            'is_active' => true,
            'status' => 'unknown',
        ]);

        // Save communication preferences
        foreach ($validated['communication_channels'] as $channel) {
            $channelValue = match($channel) {
                'email' => $user->email,
                'sms' => $user->phone ?? $user->email,
                'whatsapp' => $user->phone ?? $user->email,
                'telegram' => $user->email,
                'discord' => $user->email,
                default => $user->email,
            };

            MonitorCommunicationPreference::create([
                'monitor_id' => $monitor->id,
                'monitor_type' => 'api',
                'communication_channel' => $channel,
                'channel_value' => $channelValue,
                'is_enabled' => true,
            ]);
        }

        // Dispatch check job
        if ($monitor->is_active) {
            ApiMonitorCheckJob::dispatch($monitor->id);
        }

        return redirect()->route('api-monitors.index')
            ->with('success', 'API monitor created successfully.');
    }

    public function show(ApiMonitor $apiMonitor): View
    {
        if ($apiMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        // Get total checks count for statistics
        $totalChecks = $apiMonitor->checks()->count();
        $upChecks = $apiMonitor->checks()->where('status', 'up')->count();
        $downChecks = $apiMonitor->checks()->where('status', 'down')->count();
        $avgResponseTime = $apiMonitor->checks()->whereNotNull('response_time')->avg('response_time');

        // Get total alerts count for statistics
        $totalAlerts = $apiMonitor->alerts()->count();

        // Get performance metrics
        $checksWithResponseTime = $apiMonitor->checks()->whereNotNull('response_time');
        $responseTimeStats = (object) [
            'min_response_time' => $checksWithResponseTime->min('response_time'),
            'max_response_time' => $checksWithResponseTime->max('response_time'),
            'avg_response_time' => $checksWithResponseTime->avg('response_time'),
            'total_checks' => $checksWithResponseTime->count(),
        ];

        // Calculate percentiles (p50, p95, p99)
        $percentiles = $this->calculatePercentiles($apiMonitor);

        // Get chart data for last 24 hours
        $chartData = $this->getChartData($apiMonitor, '24h');

        $communicationPreferences = MonitorCommunicationPreference::where('monitor_id', $apiMonitor->id)
            ->where('monitor_type', 'api')
            ->get();

        // Get dependency tree and root cause analysis
        $dependencyService = new \App\Services\DependencyDiscoveryService();
        $dependencyTree = $dependencyService->buildDependencyTree($apiMonitor);
        $rootCause = $dependencyService->findRootCause($apiMonitor);

        // Calculate uptime for last 90 days
        $uptimeData = $this->calculateUptimeData($apiMonitor, 90);

        return view('client.api-monitors.show', [
            'monitor' => $apiMonitor,
            'totalChecks' => $totalChecks,
            'upChecks' => $upChecks,
            'downChecks' => $downChecks,
            'avgResponseTime' => $avgResponseTime,
            'totalAlerts' => $totalAlerts,
            'responseTimeStats' => $responseTimeStats,
            'percentiles' => $percentiles,
            'chartData' => $chartData,
            'communicationPreferences' => $communicationPreferences,
            'dependencyTree' => $dependencyTree,
            'rootCause' => $rootCause,
            'uptimeData' => $uptimeData,
        ]);
    }

    /**
     * Calculate percentiles for response time.
     */
    private function calculatePercentiles(ApiMonitor $monitor): array
    {
        $responseTimes = $monitor->checks()
            ->whereNotNull('response_time')
            ->orderBy('response_time')
            ->pluck('response_time')
            ->toArray();

        if (empty($responseTimes)) {
            return ['p50' => null, 'p95' => null, 'p99' => null];
        }

        $count = count($responseTimes);
        $p50Index = (int) ceil($count * 0.5) - 1;
        $p95Index = (int) ceil($count * 0.95) - 1;
        $p99Index = (int) ceil($count * 0.99) - 1;

        return [
            'p50' => $responseTimes[$p50Index] ?? null,
            'p95' => $responseTimes[$p95Index] ?? null,
            'p99' => $responseTimes[$p99Index] ?? null,
        ];
    }

    /**
     * Get chart data for response time and status.
     */
    private function getChartData(ApiMonitor $monitor, string $range = '24h'): array
    {
        $startDate = match($range) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subHours(24),
        };

        $checks = $monitor->checks()
            ->where('checked_at', '>=', $startDate)
            ->orderBy('checked_at', 'asc')
            ->get();

        $responseTimeData = [];
        $statusData = [];

        foreach ($checks as $check) {
            $timestamp = $check->checked_at->timestamp * 1000; // JavaScript timestamp
            $responseTimeData[] = [
                'x' => $timestamp,
                'y' => $check->response_time ?? null,
            ];
            $statusData[] = [
                'x' => $timestamp,
                'y' => $check->status === 'up' ? 1 : 0,
            ];
        }

        // Status distribution
        $statusDistribution = [
            'up' => $monitor->checks()->where('status', 'up')->count(),
            'down' => $monitor->checks()->where('status', 'down')->count(),
        ];

        return [
            'responseTime' => $responseTimeData,
            'status' => $statusData,
            'statusDistribution' => $statusDistribution,
        ];
    }

    /**
     * Get checks data for DataTables (server-side processing).
     */
    public function getChecksData(Request $request, ApiMonitor $apiMonitor): \Illuminate\Http\JsonResponse
    {
        // Ensure monitor belongs to authenticated user
        if ($apiMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        // Get base query without default ordering from relationship
        $query = $apiMonitor->checks()->reorder();

        // Global search
        $searchValue = $request->input('search.value');
        if (!empty($searchValue)) {
            $query->where(function($q) use ($searchValue) {
                $q->where('status', 'like', '%' . $searchValue . '%')
                  ->orWhere('status_code', 'like', '%' . $searchValue . '%')
                  ->orWhere('error_message', 'like', '%' . $searchValue . '%')
                  ->orWhere('response_time', 'like', '%' . $searchValue . '%');
            });
        }

        // Get total count before ordering (count doesn't need ordering)
        $totalRecords = $query->count();

        // CRITICAL: Always sort by checked_at DESC to ensure newest records appear first
        // This is the primary requirement - the latest checks must be at the top
        // We'll handle user sorting as a secondary sort if needed
        $orderColumn = $request->input('order.0.column');
        $orderDir = $request->input('order.0.dir', 'desc');
        
        // Column mapping for DataTable columns (0-indexed)
        $columnMap = [
            0 => 'id',
            1 => 'status',
            2 => 'response_time',
            3 => 'status_code',
            4 => 'error_message',
            5 => 'actions',
            6 => 'checked_at',
        ];
        
        // ALWAYS start with checked_at DESC (newest first) - this is non-negotiable
        $query->orderBy('checked_at', 'desc');
        
        // If user wants to sort by a different column, add it as secondary sort
        // But checked_at DESC remains the primary sort to ensure newest first
        if ($orderColumn !== null && $orderColumn !== '' && isset($columnMap[$orderColumn])) {
            $orderBy = $columnMap[$orderColumn];
            $orderDir = in_array(strtolower($orderDir), ['asc', 'desc']) ? strtolower($orderDir) : 'desc';
            
            if ($orderBy === 'checked_at') {
                // User wants to change checked_at sort direction - replace the default
                $query->reorder()->orderBy('checked_at', $orderDir);
            } elseif ($orderBy !== 'actions' && $orderBy !== 'id') {
                // User wants to sort by another column - add as secondary sort
                // checked_at DESC remains primary, so newest still appear first
                $query->orderBy($orderBy, $orderDir);
            }
        }

        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 5);
        $checks = $query->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = [];
        $rowNumber = $start + 1;
        
        foreach ($checks as $check) {
            // Build status badge HTML
            $statusBadge = $check->status === 'up' 
                ? '<span class="badge bg-success-transparent text-success">Up</span>'
                : '<span class="badge bg-danger-transparent text-danger">Down</span>';

            // Build response time HTML
            $responseTimeHtml = $check->response_time 
                ? $check->response_time . 'ms' 
                : 'N/A';
            if ($check->latency_exceeded) {
                $responseTimeHtml .= ' <span class="badge bg-warning-transparent text-warning ms-1">Exceeded</span>';
            }

            // Build status code HTML
            $statusCodeHtml = $check->status_code 
                ? '<span class="badge bg-' . ($check->status_code == $apiMonitor->expected_status_code ? 'success' : 'danger') . '-transparent">' . $check->status_code . '</span>'
                : '<span class="text-muted">N/A</span>';

            // Build response HTML
            $responseHtml = 'OK';
            if ($check->error_message) {
                $errorMessage = htmlspecialchars($check->error_message, ENT_QUOTES, 'UTF-8');
                $responseHtml = '<span class="text-danger" title="' . $errorMessage . '">' . Str::limit($check->error_message, 50) . '</span>';
            } elseif ($check->validation_errors && count($check->validation_errors) > 0) {
                $validationErrors = htmlspecialchars(implode(', ', $check->validation_errors), ENT_QUOTES, 'UTF-8');
                $responseHtml = '<span class="text-warning" title="' . $validationErrors . '">Validation failed</span>';
            } else {
                $responseHtml = '<span class="text-success">OK</span>';
            }

            // Add view response button if response body exists
            if ($check->response_body) {
                // Use base64 encoding to safely store response body in data attribute
                $responseBodyEncoded = base64_encode($check->response_body);
                $responseHtml .= ' <button type="button" class="btn btn-xs btn-outline-info view-response-btn ms-1" data-response-encoded="' . $responseBodyEncoded . '" title="View Response Body"><i class="ri-eye-line"></i></button>';
            }

            // Add replay and debug buttons for failed checks with request details
            $actionsHtml = '';
            if ($check->status === 'down' && $check->request_method && $check->request_url) {
                $actionsHtml = '<button type="button" class="btn btn-xs btn-warning" onclick="replayCheck(' . $check->id . ')" title="Replay this failed request">
                    <i class="ri-repeat-line me-1"></i>Replay
                </button>';
            }
            if ($check->request_method || $check->request_url) {
                $actionsHtml .= '<button type="button" class="btn btn-xs btn-outline-secondary ms-1" onclick="viewCheckDetails(' . $check->id . ')" title="View full request/response details">
                    <i class="ri-bug-line me-1"></i>Debug
                </button>';
            }
            if (empty($actionsHtml)) {
                $actionsHtml = '<span class="text-muted">-</span>';
            }

            $data[] = [
                'row_number' => $rowNumber++,
                'status' => $statusBadge,
                'response_time' => $responseTimeHtml,
                'status_code' => $statusCodeHtml,
                'response' => $responseHtml,
                'actions' => $actionsHtml,
                'checked_at' => $check->checked_at->format('Y-m-d H:i:s'),
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $apiMonitor->checks()->count(),
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ]);
    }

    /**
     * Get alerts data for DataTables (server-side processing).
     */
    public function getAlertsData(Request $request, ApiMonitor $apiMonitor): \Illuminate\Http\JsonResponse
    {
        // Ensure monitor belongs to authenticated user
        if ($apiMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $query = $apiMonitor->alerts();

        // Global search
        $searchValue = $request->input('search.value');
        if (!empty($searchValue)) {
            $query->where(function($q) use ($searchValue) {
                $q->where('alert_type', 'like', '%' . $searchValue . '%')
                  ->orWhere('message', 'like', '%' . $searchValue . '%');
            });
        }

        // Get total count before pagination
        $totalRecords = $query->count();

        // Apply ordering
        $orderColumn = $request->input('order.0.column', 4);
        $orderDir = $request->input('order.0.dir', 'desc');
        
        $columnMap = [
            0 => 'id', // Row number (we'll handle this differently)
            1 => 'alert_type',
            2 => 'message',
            3 => 'is_sent',
            4 => 'created_at',
        ];
        
        $orderBy = $columnMap[$orderColumn] ?? 'created_at';
        $query->orderBy($orderBy, $orderDir);

        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 5);
        $alerts = $query->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = [];
        $rowNumber = $start + 1;
        
        foreach ($alerts as $alert) {
            // Build alert type badge HTML
            $alertTypeBadge = '<span class="badge bg-' . ($alert->alert_type === 'up' ? 'success' : 'danger') . '-transparent">' 
                . ucfirst(str_replace('_', ' ', $alert->alert_type)) . '</span>';

            // Build message HTML (truncated)
            $messageHtml = Str::limit($alert->message, 100);

            // Build sent status badge HTML
            $sentBadge = $alert->is_sent
                ? '<span class="badge bg-success-transparent text-success">Yes</span>'
                : '<span class="badge bg-warning-transparent text-warning">No</span>';

            $data[] = [
                'row_number' => $rowNumber++,
                'alert_type' => $alertTypeBadge,
                'message' => $messageHtml,
                'is_sent' => $sentBadge,
                'created_at' => $alert->created_at->format('Y-m-d H:i:s'),
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $apiMonitor->alerts()->count(),
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ]);
    }

    public function edit(ApiMonitor $apiMonitor): View
    {
        if ($apiMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $communicationPreferences = MonitorCommunicationPreference::where('monitor_id', $apiMonitor->id)
            ->where('monitor_type', 'api')
            ->get();

        return view('client.api-monitors.edit', [
            'monitor' => $apiMonitor,
            'communicationPreferences' => $communicationPreferences,
        ]);
    }

    public function update(Request $request, ApiMonitor $apiMonitor): RedirectResponse
    {
        if ($apiMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
            'request_method' => ['required', 'in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS'],
            'auth_type' => ['required', 'in:none,bearer,basic,apikey'],
            'auth_token' => ['nullable', 'string', 'max:500'],
            'auth_username' => ['nullable', 'string', 'max:255'],
            'auth_password' => ['nullable', 'string', 'max:255'],
            'auth_header_name' => ['nullable', 'string', 'max:100'],
            'request_headers' => ['nullable', 'string'],
            'request_body' => ['nullable', 'string'],
            'content_type' => ['nullable', 'string', 'max:100'],
            'expected_status_code' => ['required', 'integer', 'min:100', 'max:599'],
            'response_assertions' => ['nullable', 'string'],
            'variable_extraction_rules' => ['nullable', 'string'],
            'monitoring_steps' => ['nullable', 'string'],
            'is_stateful' => ['nullable', 'boolean'],
            'max_latency_ms' => ['nullable', 'integer', 'min:1'],
            'validate_response_body' => ['nullable', 'boolean'],
            'check_interval' => ['required', 'integer', 'in:1,3,5,10,30,60'],
            'timeout' => ['required', 'integer', 'min:5', 'max:300'],
            'check_ssl' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'communication_channels' => ['required', 'array', 'min:1'],
            'communication_channels.*' => ['in:email,sms,whatsapp,telegram,discord'],
        ]);

        // Parse request headers
        $requestHeaders = [];
        if ($validated['request_headers']) {
            $lines = explode("\n", $validated['request_headers']);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                if (strpos($line, ':') !== false) {
                    [$name, $value] = explode(':', $line, 2);
                    $requestHeaders[] = [
                        'name' => trim($name),
                        'value' => trim($value),
                    ];
                }
            }
        }

        // Parse response assertions
        $responseAssertions = [];
        if ($validated['response_assertions']) {
            $assertions = json_decode($validated['response_assertions'], true);
            if (is_array($assertions)) {
                $responseAssertions = $assertions;
            }
        }

        // Parse variable extraction rules
        $variableExtractionRules = [];
        if (!empty($validated['variable_extraction_rules'])) {
            $rules = json_decode($validated['variable_extraction_rules'], true);
            if (is_array($rules)) {
                $variableExtractionRules = $rules;
            }
        }

        // Parse monitoring steps
        $monitoringSteps = [];
        if (!empty($validated['monitoring_steps'])) {
            $steps = json_decode($validated['monitoring_steps'], true);
            if (is_array($steps)) {
                $monitoringSteps = $steps;
            }
        }

        // Handle schema drift detection
        $schemaContent = $apiMonitor->schema_content;
        $schemaParsed = $apiMonitor->schema_parsed;
        $schemaUrl = $apiMonitor->schema_url;
        
        if ($request->has('schema_drift_enabled') && $request->input('schema_drift_enabled') == '1') {
            if ($request->hasFile('schema_file')) {
                // Upload schema file
                $file = $request->file('schema_file');
                $schemaContent = file_get_contents($file->getRealPath());
            } elseif (!empty($validated['schema_content'])) {
                // Use provided schema content
                $schemaContent = $validated['schema_content'];
            } elseif (!empty($validated['schema_url'])) {
                // Fetch schema from URL
                $schemaUrl = $validated['schema_url'];
                try {
                    $response = \Illuminate\Support\Facades\Http::get($schemaUrl);
                    if ($response->successful()) {
                        $schemaContent = $response->body();
                    }
                } catch (\Exception $e) {
                    // Handle error
                }
            }

            // Parse schema if content is available
            if ($schemaContent) {
                $schemaService = new \App\Services\SchemaDriftService();
                $schemaParsed = $schemaService->parseSchema($schemaContent);
            }
        } else {
            // If disabled, clear schema data
            $schemaContent = null;
            $schemaParsed = null;
            $schemaUrl = null;
        }

        $apiMonitor->update([
            'name' => $validated['name'],
            'url' => $validated['url'],
            'request_method' => $validated['request_method'],
            'auth_type' => $validated['auth_type'],
            'auth_token' => $validated['auth_token'] ?? null,
            'auth_username' => $validated['auth_username'] ?? null,
            'auth_password' => $validated['auth_password'] ?? null,
            'auth_header_name' => $validated['auth_header_name'] ?? null,
            'request_headers' => $requestHeaders,
            'request_body' => $validated['request_body'] ?? null,
            'content_type' => $validated['content_type'] ?? 'application/json',
            'expected_status_code' => $validated['expected_status_code'],
            'response_assertions' => $responseAssertions,
            'variable_extraction_rules' => $variableExtractionRules,
            'monitoring_steps' => $monitoringSteps,
            'is_stateful' => $request->has('is_stateful') && $request->input('is_stateful') == '1',
            'schema_drift_enabled' => $request->has('schema_drift_enabled') && $request->input('schema_drift_enabled') == '1',
            'schema_source_type' => $validated['schema_source_type'] ?? null,
            'schema_content' => $schemaContent,
            'schema_url' => $schemaUrl,
            'schema_parsed' => $schemaParsed,
            'detect_missing_fields' => $request->has('detect_missing_fields') && $request->input('detect_missing_fields') == '1',
            'detect_type_changes' => $request->has('detect_type_changes') && $request->input('detect_type_changes') == '1',
            'detect_breaking_changes' => $request->has('detect_breaking_changes') && $request->input('detect_breaking_changes') == '1',
            'detect_enum_violations' => $request->has('detect_enum_violations') && $request->input('detect_enum_violations') == '1',
            'max_latency_ms' => $validated['max_latency_ms'] ?? null,
            'validate_response_body' => $request->has('validate_response_body') && $request->input('validate_response_body') == '1',
            'check_interval' => $validated['check_interval'],
            'timeout' => $validated['timeout'],
            'check_ssl' => $request->has('check_ssl') && $request->input('check_ssl') == '1',
            'is_active' => $request->has('is_active') && $request->input('is_active') == '1',
        ]);

        // Update communication preferences
        MonitorCommunicationPreference::where('monitor_id', $apiMonitor->id)
            ->where('monitor_type', 'api')
            ->delete();

        foreach ($validated['communication_channels'] as $channel) {
            $channelValue = match($channel) {
                'email' => Auth::user()->email,
                'sms' => Auth::user()->phone ?? Auth::user()->email,
                'whatsapp' => Auth::user()->phone ?? Auth::user()->email,
                'telegram' => Auth::user()->email,
                'discord' => Auth::user()->email,
                default => Auth::user()->email,
            };

            MonitorCommunicationPreference::create([
                'monitor_id' => $apiMonitor->id,
                'monitor_type' => 'api',
                'communication_channel' => $channel,
                'channel_value' => $channelValue,
                'is_enabled' => true,
            ]);
        }

        return redirect()->route('api-monitors.show', $apiMonitor)
            ->with('success', 'API monitor updated successfully.');
    }

    public function destroy(ApiMonitor $apiMonitor): RedirectResponse
    {
        if ($apiMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $apiMonitor->delete();

        return redirect()->route('api-monitors.index')
            ->with('success', 'API monitor deleted successfully.');
    }

    /**
     * Test API monitor immediately.
     */
    public function testNow(ApiMonitor $apiMonitor): \Illuminate\Http\JsonResponse
    {
        if ($apiMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        try {
            $service = new ApiMonitorService();
            $result = $service->check($apiMonitor);

            // Store the check result
            $check = ApiMonitorCheck::create([
                'api_monitor_id' => $apiMonitor->id,
                'status' => $result['status'],
                'response_time' => $result['response_time'],
                'status_code' => $result['status_code'] ?? null,
                'response_body' => $result['response_body'] ?? null,
                'error_message' => $result['error_message'] ?? null,
                'validation_errors' => $result['validation_errors'] ?? null,
                'latency_exceeded' => $result['latency_exceeded'] ?? false,
                'checked_at' => now(),
            ]);

            // Update monitor status
            $apiMonitor->update([
                'status' => $result['status'],
                'last_checked_at' => now(),
                'next_check_at' => now()->addMinutes($apiMonitor->check_interval),
            ]);

            return response()->json([
                'success' => true,
                'check' => [
                    'status' => $check->status,
                    'response_time' => $check->response_time,
                    'status_code' => $check->status_code,
                    'error_message' => $check->error_message,
                    'response_body' => $check->response_body,
                    'validation_errors' => $check->validation_errors,
                    'checked_at' => $check->checked_at->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get chart data for different time ranges (API endpoint).
     */
    public function getChartDataApi(Request $request, ApiMonitor $apiMonitor): \Illuminate\Http\JsonResponse
    {
        if ($apiMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $range = $request->input('range', '24h');
        $chartData = $this->getChartData($apiMonitor, $range);

        return response()->json($chartData);
    }

    /**
     * Duplicate an API monitor.
     */
    public function duplicate(ApiMonitor $apiMonitor): RedirectResponse
    {
        if ($apiMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $newMonitor = $apiMonitor->replicate();
        $newMonitor->name = $apiMonitor->name . ' (Copy)';
        $newMonitor->status = 'unknown';
        $newMonitor->last_checked_at = null;
        $newMonitor->next_check_at = null;
        $newMonitor->save();

        // Copy communication preferences
        $communicationPreferences = MonitorCommunicationPreference::where('monitor_id', $apiMonitor->id)
            ->where('monitor_type', 'api')
            ->get();

        foreach ($communicationPreferences as $pref) {
            MonitorCommunicationPreference::create([
                'monitor_id' => $newMonitor->id,
                'monitor_type' => 'api',
                'communication_channel' => $pref->communication_channel,
                'channel_value' => $pref->channel_value,
                'is_enabled' => $pref->is_enabled,
            ]);
        }

        return redirect()->route('api-monitors.edit', $newMonitor)
            ->with('success', 'API monitor duplicated successfully.');
    }

    /**
     * Bulk operations (delete, enable, disable).
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => ['required', 'in:delete,enable,disable'],
            'monitor_ids' => ['required', 'array'],
            'monitor_ids.*' => ['exists:api_monitors,id'],
        ]);

        $monitors = ApiMonitor::whereIn('id', $request->monitor_ids)
            ->where('user_id', Auth::id())
            ->get();

        $action = $request->input('action');
        $count = 0;

        foreach ($monitors as $monitor) {
            switch ($action) {
                case 'delete':
                    $monitor->delete();
                    $count++;
                    break;
                case 'enable':
                    $monitor->update(['is_active' => true]);
                    $count++;
                    break;
                case 'disable':
                    $monitor->update(['is_active' => false]);
                    $count++;
                    break;
            }
        }

        $message = match($action) {
            'delete' => "{$count} monitor(s) deleted successfully.",
            'enable' => "{$count} monitor(s) enabled successfully.",
            'disable' => "{$count} monitor(s) disabled successfully.",
        };

        return redirect()->route('api-monitors.index')
            ->with('success', $message);
    }

    /**
     * Export checks to CSV/JSON.
     */
    public function exportChecks(Request $request, ApiMonitor $apiMonitor)
    {
        if ($apiMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $format = $request->input('format', 'csv');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = $apiMonitor->checks();

        if ($startDate) {
            $query->where('checked_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('checked_at', '<=', $endDate);
        }

        $checks = $query->orderBy('checked_at', 'desc')->get();

        if ($format === 'json') {
            return response()->json($checks->map(function($check) {
                return [
                    'status' => $check->status,
                    'response_time' => $check->response_time,
                    'status_code' => $check->status_code,
                    'error_message' => $check->error_message,
                    'checked_at' => $check->checked_at->format('Y-m-d H:i:s'),
                ];
            }))->header('Content-Type', 'application/json')
                ->header('Content-Disposition', 'attachment; filename="api-monitor-' . $apiMonitor->id . '-checks.json"');
        }

        // CSV export
        $filename = 'api-monitor-' . $apiMonitor->id . '-checks.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($checks) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Status', 'Response Time (ms)', 'Status Code', 'Error Message', 'Checked At']);

            foreach ($checks as $check) {
                fputcsv($file, [
                    $check->status,
                    $check->response_time ?? '',
                    $check->status_code ?? '',
                    $check->error_message ?? '',
                    $check->checked_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export alerts to CSV/JSON.
     */
    public function exportAlerts(Request $request, ApiMonitor $apiMonitor)
    {
        if ($apiMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $format = $request->input('format', 'csv');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = $apiMonitor->alerts();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $alerts = $query->orderBy('created_at', 'desc')->get();

        if ($format === 'json') {
            return response()->json($alerts->map(function($alert) {
                return [
                    'alert_type' => $alert->alert_type,
                    'message' => $alert->message,
                    'is_sent' => $alert->is_sent,
                    'sent_at' => $alert->sent_at?->format('Y-m-d H:i:s'),
                    'created_at' => $alert->created_at->format('Y-m-d H:i:s'),
                ];
            }))->header('Content-Type', 'application/json')
                ->header('Content-Disposition', 'attachment; filename="api-monitor-' . $apiMonitor->id . '-alerts.json"');
        }

        // CSV export
        $filename = 'api-monitor-' . $apiMonitor->id . '-alerts.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($alerts) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Alert Type', 'Message', 'Is Sent', 'Sent At', 'Created At']);

            foreach ($alerts as $alert) {
                fputcsv($file, [
                    $alert->alert_type,
                    $alert->message,
                    $alert->is_sent ? 'Yes' : 'No',
                    $alert->sent_at?->format('Y-m-d H:i:s') ?? '',
                    $alert->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Confirm a dependency (mark as confirmed).
     */
    public function confirmDependency(ApiMonitorDependency $dependency): \Illuminate\Http\JsonResponse
    {
        if ($dependency->monitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $dependency->update(['is_confirmed' => true]);

        return response()->json(['success' => true, 'message' => 'Dependency confirmed']);
    }

    /**
     * Delete a dependency.
     */
    public function deleteDependency(ApiMonitorDependency $dependency): \Illuminate\Http\JsonResponse
    {
        if ($dependency->monitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $dependency->delete();

        return response()->json(['success' => true, 'message' => 'Dependency removed']);
    }

    /**
     * Toggle suppress child alerts setting.
     */
    public function toggleSuppressAlerts(Request $request, ApiMonitorDependency $dependency): \Illuminate\Http\JsonResponse
    {
        if ($dependency->monitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $dependency->update([
            'suppress_child_alerts' => $request->input('suppress', true),
        ]);

        return response()->json(['success' => true, 'message' => 'Setting updated']);
    }

    /**
     * Replay a failed check.
     */
    public function replayCheck(ApiMonitorCheck $check): \Illuminate\Http\JsonResponse
    {
        if ($check->apiMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        // Check if this check has request details stored
        if (!$check->request_method || !$check->request_url) {
            return response()->json([
                'success' => false,
                'message' => 'Request details not available for this check. This feature requires request details to be stored.',
            ], 400);
        }

        try {
            $monitor = $check->apiMonitor;
            $service = new ApiMonitorService();
            
            // Create a temporary monitor object with the stored request details
            $replayMonitor = clone $monitor;
            $replayMonitor->url = $check->request_url;
            $replayMonitor->request_method = $check->request_method;
            $replayMonitor->request_body = $check->request_body;
            $replayMonitor->content_type = $check->request_content_type;
            
            // Restore headers
            if ($check->request_headers) {
                $headers = [];
                foreach ($check->request_headers as $name => $value) {
                    // Skip masked auth headers, use current monitor's auth
                    if (in_array(strtolower($name), ['authorization']) && $value === '***') {
                        continue; // Will use monitor's current auth
                    }
                    $headers[] = ['name' => $name, 'value' => $value];
                }
                $replayMonitor->request_headers = $headers;
            }

            // Perform the replay check
            $result = $service->check($replayMonitor);

            // Store the replay result
            $replayCheck = ApiMonitorCheck::create([
                'api_monitor_id' => $monitor->id,
                'request_method' => $check->request_method,
                'request_url' => $check->request_url,
                'request_headers' => $check->request_headers,
                'request_body' => $check->request_body,
                'request_content_type' => $check->request_content_type,
                'status' => $result['status'],
                'response_time' => $result['response_time'],
                'status_code' => $result['status_code'],
                'response_body' => $result['response_body'] ? substr($result['response_body'], 0, 10000) : null,
                'response_headers' => $result['response_headers'] ?? [],
                'error_message' => $result['error_message'],
                'validation_errors' => $result['validation_errors'] ?? [],
                'latency_exceeded' => $result['latency_exceeded'] ?? false,
                'is_replay' => true,
                'replay_of_check_id' => $check->id,
                'replayed_at' => now(),
                'checked_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Request replayed successfully',
                'check' => [
                    'id' => $replayCheck->id,
                    'status' => $replayCheck->status,
                    'status_code' => $replayCheck->status_code,
                    'response_time' => $replayCheck->response_time,
                    'error_message' => $replayCheck->error_message,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to replay check', [
                'check_id' => $check->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to replay request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get check details for debug view.
     */
    public function getCheckDetails(ApiMonitorCheck $check): \Illuminate\Http\JsonResponse
    {
        if ($check->apiMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        return response()->json([
            'check' => [
                'id' => $check->id,
                'status' => $check->status,
                'checked_at' => $check->checked_at->toIso8601String(),
                'request_method' => $check->request_method,
                'request_url' => $check->request_url,
                'request_headers' => $check->request_headers,
                'request_body' => $check->request_body,
                'request_content_type' => $check->request_content_type,
                'response_time' => $check->response_time,
                'status_code' => $check->status_code,
                'response_body' => $check->response_body,
                'response_headers' => $check->response_headers,
                'error_message' => $check->error_message,
                'validation_errors' => $check->validation_errors,
                'is_replay' => $check->is_replay,
                'replay_of_check_id' => $check->replay_of_check_id,
            ],
        ]);
    }

    /**
     * Calculate uptime data for a given period.
     */
    protected function calculateUptimeData(ApiMonitor $monitor, int $days = 90): array
    {
        $endDate = now();
        $startDate = now()->subDays($days);
        
        // Get all checks in the period
        $checks = $monitor->checks()
            ->where('checked_at', '>=', $startDate)
            ->where('checked_at', '<=', $endDate)
            ->orderBy('checked_at', 'asc')
            ->get();

        // Calculate daily status
        $dailyStatus = [];
        $totalUp = 0;
        $totalDown = 0;
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $nextDate = $date->copy()->endOfDay();
            
            // Get checks for this day
            $dayChecks = $checks->filter(function($check) use ($date, $nextDate) {
                return $check->checked_at >= $date && $check->checked_at <= $nextDate;
            });
            
            if ($dayChecks->isEmpty()) {
                // No checks for this day - mark as unknown
                $dailyStatus[] = [
                    'date' => $date->format('Y-m-d'),
                    'status' => 'unknown',
                    'up_count' => 0,
                    'down_count' => 0,
                    'total_count' => 0,
                ];
            } else {
                $upCount = $dayChecks->where('status', 'up')->count();
                $downCount = $dayChecks->where('status', 'down')->count();
                $totalCount = $dayChecks->count();
                
                // Determine day status based on majority
                $dayStatus = 'up';
                if ($downCount > $upCount) {
                    $dayStatus = 'down';
                } elseif ($downCount > 0 && $upCount > 0) {
                    $dayStatus = 'partial'; // Mixed status
                }
                
                $dailyStatus[] = [
                    'date' => $date->format('Y-m-d'),
                    'status' => $dayStatus,
                    'up_count' => $upCount,
                    'down_count' => $downCount,
                    'total_count' => $totalCount,
                ];
                
                $totalUp += $upCount;
                $totalDown += $downCount;
            }
        }
        
        // Calculate uptime percentage
        $totalChecks = $totalUp + $totalDown;
        $uptimePercentage = $totalChecks > 0 ? round(($totalUp / $totalChecks) * 100, 2) : 0;
        
        return [
            'uptime_percentage' => $uptimePercentage,
            'total_checks' => $totalChecks,
            'up_checks' => $totalUp,
            'down_checks' => $totalDown,
            'daily_status' => $dailyStatus,
            'period_days' => $days,
        ];
    }
}
