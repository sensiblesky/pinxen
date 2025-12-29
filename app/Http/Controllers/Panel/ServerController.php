<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\PerformanceScoreService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ServerController extends Controller
{
    public function index(Request $request): View
    {
        // Start building query
        $query = Server::with(['user', 'apiKey', 'latestStat']);
        
        // Apply filters
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }
        
        if ($request->filled('hostname')) {
            $query->where('hostname', 'like', '%' . $request->input('hostname') . '%');
        }
        
        if ($request->filled('os_type')) {
            $query->where('os_type', $request->input('os_type'));
        }
        
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->input('created_from'));
        }
        
        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->input('created_to'));
        }
        
        // Get filtered servers first (before status filtering)
        $servers = $query->orderBy('created_at', 'desc')->get();
        
        // Filter by status if requested (using ServerStatusService)
        if ($request->filled('status')) {
            $statusFilter = $request->input('status');
            $statusService = new \App\Services\ServerStatusService();
            
            $servers = $servers->filter(function($server) use ($statusFilter, $statusService) {
                $statusResult = $statusService->determineStatus(
                    $server,
                    $server->online_threshold_minutes,
                    $server->warning_threshold_minutes,
                    $server->offline_threshold_minutes
                );
                return $statusResult['status'] === $statusFilter;
            });
        }
        
        return view('panel.servers.index', [
            'servers' => $servers,
            'filters' => $request->only(['name', 'hostname', 'os_type', 'status', 'created_from', 'created_to']),
        ]);
    }

    public function create(): View
    {
        $users = \App\Models\User::where('role', 2)->orderBy('name')->get();
        $apiKeys = \App\Models\ApiKey::where('is_active', true)->orderBy('name')->get();
        return view('panel.servers.create', [
            'users' => $users,
            'apiKeys' => $apiKeys,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // TODO: Implement store method
        return redirect()->route('panel.servers.index')
            ->with('success', 'Server created successfully.');
    }

    public function show(Request $request, Server $server): View
    {
        // Increase memory limit for servers with large amounts of data
        ini_set('memory_limit', '256M');
        
        // Admin can view any server - no ownership check needed
        $server->load(['user', 'apiKey']);
        
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

        return view('panel.servers.show', compact('server', 'latestStat', 'chartData', 'range', 'startDate', 'endDate', 'performanceScore'));
    }

    public function edit(Server $server): View
    {
        $users = \App\Models\User::where('role', 2)->orderBy('name')->get();
        $apiKeys = \App\Models\ApiKey::where('is_active', true)->orderBy('name')->get();
        
        // Load latestStat if the edit view needs it
        $server->load(['user', 'latestStat']);
        
        return view('panel.servers.edit', [
            'server' => $server,
            'users' => $users,
            'apiKeys' => $apiKeys,
        ]);
    }

    public function update(Request $request, Server $server): RedirectResponse
    {
        // TODO: Implement update method
        return redirect()->route('panel.servers.show', $server->uid)
            ->with('success', 'Server updated successfully.');
    }

    public function destroy(Server $server): RedirectResponse
    {
        $server->delete();
        return redirect()->route('panel.servers.index')
            ->with('success', 'Server deleted successfully.');
    }

    public function regenerateKey(Server $server): RedirectResponse
    {
        // TODO: Implement regenerateKey method
        return redirect()->route('panel.servers.show', $server->uid)
            ->with('success', 'Server key regenerated successfully.');
    }

    /**
     * Get disk partitions data for DataTables (server-side processing).
     */
    public function getDiskData(Request $request, Server $server): \Illuminate\Http\JsonResponse
    {
        // Admin can view any server - no ownership check needed
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
        // Admin can view any server - no ownership check needed
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
        // Admin can view any server - no ownership check needed
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
     * Install agent via SSH (Admin can install on any server)
     */
    public function installViaSSH(Request $request, Server $server): \Illuminate\Http\JsonResponse
    {
        // Admin can install on any server - no ownership check needed
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
     * Test SSH connection (Admin can test any server)
     */
    public function testSSH(Request $request, Server $server): \Illuminate\Http\JsonResponse
    {
        // Admin can test any server - no ownership check needed
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
