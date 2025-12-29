<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\ApiKey;
use App\Services\PerformanceScoreService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class ServerController extends Controller
{
    /**
     * Display a listing of servers.
     */
    public function index(): View
    {
        $user = Auth::user();
        $servers = $user->servers()
            ->with(['apiKey', 'latestStat'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('client.servers.index', compact('servers'));
    }

    /**
     * Show the form for creating a new server.
     */
    public function create(): View
    {
        $user = Auth::user();
        // Get active API keys with view/create/* scopes
        $apiKeys = $user->apiKeys()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->where(function ($query) {
                $query->whereJsonContains('scopes', 'view')
                      ->orWhereJsonContains('scopes', 'create')
                      ->orWhereJsonContains('scopes', '*');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('client.servers.create', compact('apiKeys'));
    }

    /**
     * Store a newly created server.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'api_key_id' => ['required', 'exists:api_keys,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'hostname' => ['nullable', 'string', 'max:255'],
            'os_type' => ['nullable', 'string', 'max:50'],
            'os_version' => ['nullable', 'string', 'max:100'],
            'ip_address' => ['nullable', 'ip'],
            'location' => ['nullable', 'string', 'max:255'],
            'cpu_threshold' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'memory_threshold' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'disk_threshold' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $user = Auth::user();

        // Verify API key belongs to user
        $apiKey = ApiKey::where('id', $validated['api_key_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $server = Server::create([
            'user_id' => $user->id,
            'api_key_id' => $validated['api_key_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'hostname' => $validated['hostname'] ?? null,
            'os_type' => $validated['os_type'] ?? null,
            'os_version' => $validated['os_version'] ?? null,
            'ip_address' => $validated['ip_address'] ?? null,
            'location' => $validated['location'] ?? null,
            'cpu_threshold' => $validated['cpu_threshold'] ?? null,
            'memory_threshold' => $validated['memory_threshold'] ?? null,
            'disk_threshold' => $validated['disk_threshold'] ?? null,
            'online_threshold_minutes' => $validated['online_threshold_minutes'] ?? null,
            'warning_threshold_minutes' => $validated['warning_threshold_minutes'] ?? null,
            'offline_threshold_minutes' => $validated['offline_threshold_minutes'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('servers.show', $server)
            ->with('success', 'Server created successfully. Use the server key below to configure your agent.');
    }

    /**
     * Display the specified server.
     */
    public function show(Request $request, Server $server): View
    {
        // Increase memory limit for servers with large amounts of data
        ini_set('memory_limit', '256M');
        
        $user = Auth::user();
        
        // Ensure server belongs to user
        if ($server->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $server->load(['apiKey']);
        
        // Get date range from request (default to 24 hours)
        $range = $request->input('range', '24h');
        $startDate = null;
        $endDate = now();
        
        if ($range === 'custom') {
            $startDateParam = $request->input('start_date');
            $endDateParam = $request->input('end_date');
            
            if ($startDateParam && $endDateParam) {
                $startDate = \Carbon\Carbon::parse($startDateParam)->startOfDay();
                $endDate = \Carbon\Carbon::parse($endDateParam)->endOfDay();
            } else {
                // Default to 24 hours if custom range is invalid
                $startDate = now()->subHours(24);
                $endDate = now();
            }
        } elseif ($range === '7d') {
            $startDate = now()->subDays(7)->startOfDay();
            $endDate = now()->endOfDay();
        } elseif ($range === '30d') {
            $startDate = now()->subDays(30)->startOfDay();
            $endDate = now()->endOfDay();
        } else {
            // Default to 24 hours - get last 24 hours from now
            $startDate = now()->subHours(24);
            $endDate = now();
        }
        
        // Load stats based on date range with optimization
        // Use reorder() to override the default desc ordering from the relationship
        // Only select columns needed for charts to reduce memory usage
        $statsQuery = $server->stats()
            ->where('recorded_at', '>=', $startDate)
            ->where('recorded_at', '<=', $endDate)
            ->select([
                'id',
                'server_id',
                'cpu_usage_percent',
                'memory_usage_percent',
                'disk_usage_percent',
                'network_bytes_sent',
                'network_bytes_received',
                'processes_total',
                'processes_running',
                'processes_sleeping',
                'recorded_at'
            ])
            ->reorder('recorded_at', 'asc'); // Order ascending for charts (oldest to newest)
        
        // Determine max records based on range to prevent memory issues
        // Use reasonable limits to prevent memory exhaustion
        $maxRecords = match($range) {
            '24h' => 1440,  // 1 per minute for 24 hours (max)
            '7d' => 1008,  // 1 per 10 minutes for 7 days
            '30d' => 720,  // 1 per hour for 30 days
            default => 1000 // Default limit for custom ranges
        };
        
        // Get total count efficiently (using raw query to avoid loading data)
        $totalCount = \DB::table('server_stats')
            ->where('server_id', $server->id)
            ->where('recorded_at', '>=', $startDate)
            ->where('recorded_at', '<=', $endDate)
            ->count();
        
        // Calculate sampling step if we have more records than max
        $step = $totalCount > $maxRecords ? ceil($totalCount / $maxRecords) : 1;
        
        // Use cursor() for memory-efficient iteration (lazy loading)
        // This loads one record at a time instead of all at once
        $chartData = [
            'cpu' => [],
            'memory' => [],
            'disk' => [],
            'network' => [],
            'processes' => [],
            'timestamps' => []
        ];
        
        $recordIndex = 0;
        foreach ($statsQuery->cursor() as $stat) {
            // Sample records based on step
            if ($recordIndex % $step !== 0) {
                $recordIndex++;
                continue;
            }
            
            // Stop if we've reached max records
            if (count($chartData['timestamps']) >= $maxRecords) {
                break;
            }
            
            // Format timestamp based on range
            if ($range === '24h') {
                $chartData['timestamps'][] = $stat->recorded_at->format('H:i');
            } elseif ($range === '7d') {
                $chartData['timestamps'][] = $stat->recorded_at->format('M d H:i');
            } elseif ($range === '30d') {
                $chartData['timestamps'][] = $stat->recorded_at->format('M d');
            } else {
                $chartData['timestamps'][] = $stat->recorded_at->format('M d H:i');
            }
            
            $chartData['cpu'][] = (float)($stat->cpu_usage_percent ?? 0);
            $chartData['memory'][] = (float)($stat->memory_usage_percent ?? 0);
            $chartData['disk'][] = (float)($stat->disk_usage_percent ?? 0);
            $chartData['network'][] = [
                'sent' => (int)($stat->network_bytes_sent ?? 0),
                'received' => (int)($stat->network_bytes_received ?? 0)
            ];
            $chartData['processes'][] = [
                'total' => (int)($stat->processes_total ?? 0),
                'running' => (int)($stat->processes_running ?? 0),
                'sleeping' => (int)($stat->processes_sleeping ?? 0)
            ];
            
            $recordIndex++;
        }
        
        $latestStat = $server->latestStat;

        // Calculate performance score
        $performanceScoreService = new PerformanceScoreService();
        $performanceScore = $performanceScoreService->calculateServerScore($server, $latestStat);

        return view('client.servers.show', compact('server', 'latestStat', 'chartData', 'range', 'startDate', 'endDate', 'performanceScore'));
    }

    /**
     * Show the form for editing the specified server.
     */
    public function edit(Server $server): View
    {
        $user = Auth::user();
        
        // Ensure server belongs to user
        if ($server->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $user = Auth::user();
        $apiKeys = $user->apiKeys()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->where(function ($query) {
                $query->whereJsonContains('scopes', 'view')
                      ->orWhereJsonContains('scopes', 'create')
                      ->orWhereJsonContains('scopes', '*');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('client.servers.edit', compact('server', 'apiKeys'));
    }

    /**
     * Update the specified server.
     */
    public function update(Request $request, Server $server): RedirectResponse
    {
        $user = Auth::user();
        
        // Ensure server belongs to user
        if ($server->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'api_key_id' => ['required', 'exists:api_keys,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'hostname' => ['nullable', 'string', 'max:255'],
            'os_type' => ['nullable', 'string', 'max:50'],
            'os_version' => ['nullable', 'string', 'max:100'],
            'ip_address' => ['nullable', 'ip'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'cpu_threshold' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'memory_threshold' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'disk_threshold' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'online_threshold_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'warning_threshold_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'offline_threshold_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
        ]);

        // Verify API key belongs to user
        $apiKey = ApiKey::where('id', $validated['api_key_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $server->update([
            'name' => $validated['name'],
            'api_key_id' => $validated['api_key_id'],
            'description' => $validated['description'] ?? null,
            'hostname' => $validated['hostname'] ?? null,
            'os_type' => $validated['os_type'] ?? null,
            'os_version' => $validated['os_version'] ?? null,
            'ip_address' => $validated['ip_address'] ?? null,
            'location' => $validated['location'] ?? null,
            'cpu_threshold' => $validated['cpu_threshold'] ?? null,
            'memory_threshold' => $validated['memory_threshold'] ?? null,
            'disk_threshold' => $validated['disk_threshold'] ?? null,
            'online_threshold_minutes' => $validated['online_threshold_minutes'] ?? null,
            'warning_threshold_minutes' => $validated['warning_threshold_minutes'] ?? null,
            'offline_threshold_minutes' => $validated['offline_threshold_minutes'] ?? null,
            'is_active' => $request->has('is_active') && $request->input('is_active') == '1',
        ]);

        return redirect()->route('servers.index')
            ->with('success', 'Server updated successfully.');
    }

    /**
     * Remove the specified server.
     */
    public function destroy(Server $server): RedirectResponse
    {
        $user = Auth::user();
        
        // Ensure server belongs to user
        if ($server->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $server->delete();

        return redirect()->route('servers.index')
            ->with('success', 'Server deleted successfully.');
    }

    /**
     * Regenerate server key.
     */
    public function regenerateKey(Server $server): RedirectResponse
    {
        $user = Auth::user();
        
        // Ensure server belongs to user
        if ($server->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $server->update([
            'server_key' => Server::generateServerKey(),
        ]);

        return redirect()->route('servers.show', $server)
            ->with('success', 'Server key regenerated successfully. Update your agent configuration with the new key.');
    }

    /**
     * Get disk partitions data for DataTables (server-side processing).
     */
    public function getDiskData(Request $request, Server $server): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        
        if ($server->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $latestStat = $server->latestStat;
        if (!$latestStat || !$latestStat->disk_usage || !is_array($latestStat->disk_usage)) {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $diskUsage = $latestStat->disk_usage;
        
        // Global search
        $searchValue = $request->input('search.value');
        if (!empty($searchValue)) {
            $diskUsage = array_filter($diskUsage, function($disk) use ($searchValue) {
                return stripos($disk['device'] ?? '', $searchValue) !== false ||
                       stripos($disk['mount_point'] ?? '', $searchValue) !== false ||
                       stripos($disk['fs_type'] ?? '', $searchValue) !== false;
            });
        }

        $totalRecords = count($diskUsage);

        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 5);
        $paginated = array_slice($diskUsage, $start, $length);

        // Format data for DataTables
        $data = [];
        $rowNumber = $start + 1;
        
        foreach ($paginated as $disk) {
            $usagePercent = $disk['usage_percent'] ?? 0;
            $data[] = [
                'row_number' => $rowNumber++,
                'device' => '<code>' . htmlspecialchars($disk['device'] ?? 'N/A') . '</code>',
                'mount_point' => '<code>' . htmlspecialchars($disk['mount_point'] ?? 'N/A') . '</code>',
                'fs_type' => $disk['fs_type'] ?? 'N/A',
                'total' => isset($disk['total_bytes']) ? \App\Models\ServerStat::formatBytes($disk['total_bytes']) : 'N/A',
                'used' => isset($disk['used_bytes']) ? \App\Models\ServerStat::formatBytes($disk['used_bytes']) : 'N/A',
                'free' => isset($disk['free_bytes']) ? \App\Models\ServerStat::formatBytes($disk['free_bytes']) : 'N/A',
                'usage_percent' => number_format($usagePercent, 1) . '%',
                'usage_percent_raw' => (float)$usagePercent,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => count($latestStat->disk_usage),
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ]);
    }

    /**
     * Get network interfaces data for DataTables (server-side processing).
     */
    public function getNetworkData(Request $request, Server $server): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        
        if ($server->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $latestStat = $server->latestStat;
        if (!$latestStat || !$latestStat->network_interfaces || !is_array($latestStat->network_interfaces)) {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $interfaces = $latestStat->network_interfaces;
        
        // Global search
        $searchValue = $request->input('search.value');
        if (!empty($searchValue)) {
            $interfaces = array_filter($interfaces, function($interface) use ($searchValue) {
                return stripos($interface['name'] ?? '', $searchValue) !== false;
            });
        }

        $totalRecords = count($interfaces);

        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 5);
        $paginated = array_slice($interfaces, $start, $length);

        // Format data for DataTables
        $data = [];
        $rowNumber = $start + 1;
        
        foreach ($paginated as $interface) {
            $data[] = [
                'row_number' => $rowNumber++,
                'name' => '<code>' . htmlspecialchars($interface['name'] ?? 'N/A') . '</code>',
                'bytes_sent' => isset($interface['bytes_sent']) ? \App\Models\ServerStat::formatBytes($interface['bytes_sent']) : 'N/A',
                'bytes_received' => isset($interface['bytes_received']) ? \App\Models\ServerStat::formatBytes($interface['bytes_received']) : 'N/A',
                'packets_sent' => isset($interface['packets_sent']) ? number_format($interface['packets_sent']) : 'N/A',
                'packets_received' => isset($interface['packets_received']) ? number_format($interface['packets_received']) : 'N/A',
                'errors_in' => isset($interface['errors_in']) ? number_format($interface['errors_in']) : 'N/A',
                'errors_out' => isset($interface['errors_out']) ? number_format($interface['errors_out']) : 'N/A',
                'drop_in' => isset($interface['drop_in']) ? number_format($interface['drop_in']) : 'N/A',
                'drop_out' => isset($interface['drop_out']) ? number_format($interface['drop_out']) : 'N/A',
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => count($latestStat->network_interfaces),
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ]);
    }

    /**
     * Get processes data for DataTables (server-side processing).
     */
    public function getProcessesData(Request $request, Server $server): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        
        if ($server->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $latestStat = $server->latestStat;
        if (!$latestStat || !$latestStat->processes || !is_array($latestStat->processes)) {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $processes = $latestStat->processes;
        
        // Global search
        $searchValue = $request->input('search.value');
        if (!empty($searchValue)) {
            $processes = array_filter($processes, function($proc) use ($searchValue) {
                return stripos($proc['name'] ?? '', $searchValue) !== false ||
                       stripos($proc['user'] ?? '', $searchValue) !== false ||
                       stripos($proc['command'] ?? '', $searchValue) !== false ||
                       stripos((string)($proc['pid'] ?? ''), $searchValue) !== false;
            });
        }

        // Apply sorting
        $orderColumn = $request->input('order.0.column', 3);
        $orderDir = $request->input('order.0.dir', 'desc');
        
        $columnMap = [
            0 => 'pid',
            1 => 'name',
            2 => 'status',
            3 => 'cpu_percent',
            4 => 'memory_percent',
            5 => 'memory_bytes',
            6 => 'user',
            7 => 'command',
        ];
        
        $orderBy = $columnMap[$orderColumn] ?? 'cpu_percent';
        usort($processes, function($a, $b) use ($orderBy, $orderDir) {
            $aVal = $a[$orderBy] ?? 0;
            $bVal = $b[$orderBy] ?? 0;
            
            if ($orderDir === 'asc') {
                return $aVal <=> $bVal;
            } else {
                return $bVal <=> $aVal;
            }
        });

        $totalRecords = count($processes);

        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 5);
        $paginated = array_slice($processes, $start, $length);

        // Format data for DataTables
        $data = [];
        $rowNumber = $start + 1;
        
        foreach ($paginated as $proc) {
            $statusBadge = 'N/A';
            if (isset($proc['status'])) {
                $statusClass = $proc['status'] == 'R' ? 'bg-success' : ($proc['status'] == 'S' ? 'bg-info' : 'bg-secondary');
                $statusBadge = '<span class="badge ' . $statusClass . '">' . htmlspecialchars($proc['status']) . '</span>';
            }
            
            $command = $proc['command'] ?? 'N/A';
            $commandDisplay = strlen($command) > 50 ? substr($command, 0, 50) . '...' : $command;
            
            $data[] = [
                'row_number' => $rowNumber++,
                'pid' => $proc['pid'] ?? 'N/A',
                'name' => '<code>' . htmlspecialchars($proc['name'] ?? 'N/A') . '</code>',
                'status' => $statusBadge,
                'cpu_percent' => isset($proc['cpu_percent']) ? number_format($proc['cpu_percent'], 2) . '%' : 'N/A',
                'cpu_percent_raw' => $proc['cpu_percent'] ?? 0,
                'memory_percent' => isset($proc['memory_percent']) ? number_format($proc['memory_percent'], 2) . '%' : 'N/A',
                'memory_percent_raw' => $proc['memory_percent'] ?? 0,
                'memory_bytes' => isset($proc['memory_bytes']) ? \App\Models\ServerStat::formatBytes($proc['memory_bytes']) : 'N/A',
                'memory_bytes_raw' => $proc['memory_bytes'] ?? 0,
                'user' => $proc['user'] ?? 'N/A',
                'command' => '<code class="text-truncate d-inline-block" style="max-width: 200px;" title="' . htmlspecialchars($command) . '">' . htmlspecialchars($commandDisplay) . '</code>',
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => count($latestStat->processes),
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ]);
    }

    /**
     * Install agent via SSH
     */
    public function installViaSSH(Request $request, Server $server): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        
        if ($server->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'host' => 'nullable|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string',
            'private_key' => 'nullable|string',
            'os' => 'nullable|string|in:linux,windows,darwin,freebsd,auto',
            'arch' => 'nullable|string|in:amd64,arm64',
        ]);

        $service = new \App\Services\SSHInstallationService();
        $result = $service->installViaSSH($server, $validated);

        if ($result['success']) {
            return response()->json($result);
        } else {
            return response()->json($result, 400);
        }
    }

    /**
     * Test SSH connection
     */
    public function testSSH(Request $request, Server $server): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        
        if ($server->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'host' => 'nullable|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string',
            'private_key' => 'nullable|string',
        ]);

        $service = new \App\Services\SSHInstallationService();
        $result = $service->testConnection($validated);

        return response()->json($result);
    }
}
