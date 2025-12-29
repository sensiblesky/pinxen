<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\ServerStat;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ServerStatsController extends Controller
{
    /**
     * Receive and store server stats from agent.
     * Handles stats from all operating systems (Linux, Windows, macOS, FreeBSD).
     */
    public function store(Request $request): JsonResponse
    {
        // Get API key from request (set by middleware)
        $apiKey = $request->get('api_key');
        
        // Get server key from request
        $serverKey = $request->input('server_key');
        
        if (!$serverKey) {
            return response()->json([
                'success' => false,
                'message' => 'Server key is required.'
            ], 400);
        }

        // Find server by key and verify it uses the authenticated API key
        $server = Server::where('server_key', $serverKey)
            ->where('api_key_id', $apiKey->id)
            ->where('is_active', true)
            ->first();

        if (!$server) {
            return response()->json([
                'success' => false,
                'message' => 'Server not found or inactive. Verify your server key and API key match.'
            ], 404);
        }

        // Validate and store stats
        // Note: All fields are nullable to handle OS differences (e.g., Windows may not have load averages)
        $validated = $request->validate([
            'server_key' => ['required', 'string'],
            
            // CPU Metrics (load averages may not be available on all OS)
            'cpu_usage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'cpu_cores' => ['nullable', 'integer', 'min:1'],
            'cpu_load_1min' => ['nullable', 'numeric', 'min:0'], // May be null on Windows
            'cpu_load_5min' => ['nullable', 'numeric', 'min:0'], // May be null on Windows
            'cpu_load_15min' => ['nullable', 'numeric', 'min:0'], // May be null on Windows
            
            // Memory Metrics
            'memory_total_bytes' => ['nullable', 'integer', 'min:0'],
            'memory_used_bytes' => ['nullable', 'integer', 'min:0'],
            'memory_free_bytes' => ['nullable', 'integer', 'min:0'],
            'memory_usage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            
            // Swap Metrics (may not be available on all systems)
            'swap_total_bytes' => ['nullable', 'integer', 'min:0'],
            'swap_used_bytes' => ['nullable', 'integer', 'min:0'],
            'swap_free_bytes' => ['nullable', 'integer', 'min:0'],
            'swap_usage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            
            // Disk Metrics (array of partitions - OS-specific)
            'disk_usage' => ['nullable', 'array'],
            'disk_usage.*.device' => ['nullable', 'string'],
            'disk_usage.*.mount_point' => ['nullable', 'string'],
            'disk_usage.*.fs_type' => ['nullable', 'string'],
            'disk_usage.*.total_bytes' => ['nullable', 'integer', 'min:0'],
            'disk_usage.*.used_bytes' => ['nullable', 'integer', 'min:0'],
            'disk_usage.*.free_bytes' => ['nullable', 'integer', 'min:0'],
            'disk_usage.*.usage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'disk_total_bytes' => ['nullable', 'integer', 'min:0'],
            'disk_used_bytes' => ['nullable', 'integer', 'min:0'],
            'disk_free_bytes' => ['nullable', 'integer', 'min:0'],
            'disk_usage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            
            // Network Metrics (array of interfaces - OS-specific)
            'network_interfaces' => ['nullable', 'array'],
            'network_interfaces.*.name' => ['nullable', 'string'],
            'network_interfaces.*.bytes_sent' => ['nullable', 'integer', 'min:0'],
            'network_interfaces.*.bytes_received' => ['nullable', 'integer', 'min:0'],
            'network_interfaces.*.packets_sent' => ['nullable', 'integer', 'min:0'],
            'network_interfaces.*.packets_received' => ['nullable', 'integer', 'min:0'],
            'network_interfaces.*.errors_in' => ['nullable', 'integer', 'min:0'],
            'network_interfaces.*.errors_out' => ['nullable', 'integer', 'min:0'],
            'network_interfaces.*.drop_in' => ['nullable', 'integer', 'min:0'],
            'network_interfaces.*.drop_out' => ['nullable', 'integer', 'min:0'],
            'network_bytes_sent' => ['nullable', 'integer', 'min:0'],
            'network_bytes_received' => ['nullable', 'integer', 'min:0'],
            'network_packets_sent' => ['nullable', 'integer', 'min:0'],
            'network_packets_received' => ['nullable', 'integer', 'min:0'],
            
            // System Info
            'uptime_seconds' => ['nullable', 'integer', 'min:0'],
            'processes_total' => ['nullable', 'integer', 'min:0'],
            'processes_running' => ['nullable', 'integer', 'min:0'],
            'processes_sleeping' => ['nullable', 'integer', 'min:0'],
            
            // Process Details (array of process information)
            'processes' => ['nullable', 'array'],
            'processes.*.pid' => ['nullable', 'integer', 'min:1'],
            'processes.*.name' => ['nullable', 'string'],
            'processes.*.status' => ['nullable', 'string'],
            'processes.*.cpu_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'processes.*.memory_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'processes.*.memory_bytes' => ['nullable', 'integer', 'min:0'],
            'processes.*.user' => ['nullable', 'string'],
            'processes.*.command' => ['nullable', 'string'],
            'processes.*.created_at' => ['nullable', 'integer', 'min:0'],
            
            // Server Info (can be updated from agent)
            'hostname' => ['nullable', 'string', 'max:255'],
            'os_type' => ['nullable', 'string', 'max:50'],
            'os_version' => ['nullable', 'string', 'max:100'],
            'ip_address' => ['nullable', 'string', 'max:45'], // IPv4 or IPv6 (validated separately)
            'agent_version' => ['nullable', 'string', 'max:50'],
            'machine_id' => ['nullable', 'string', 'max:255'],
            'system_uuid' => ['nullable', 'string', 'max:255'],
            'disk_uuid' => ['nullable', 'string', 'max:255'],
            'agent_id' => ['nullable', 'string', 'max:255'],
            'recorded_at' => ['nullable', 'date'], // ISO8601 timestamp from agent
        ]);

        try {
            // Update server info if provided (OS-specific fields)
            $serverUpdates = [];
            if (isset($validated['hostname']) && !empty($validated['hostname'])) {
                $serverUpdates['hostname'] = $validated['hostname'];
            }
            if (isset($validated['os_type']) && !empty($validated['os_type'])) {
                $serverUpdates['os_type'] = $validated['os_type'];
            }
            if (isset($validated['os_version']) && !empty($validated['os_version'])) {
                $serverUpdates['os_version'] = $validated['os_version'];
            }
            if (isset($validated['ip_address']) && !empty($validated['ip_address'])) {
                // Validate IP address (IPv4 or IPv6)
                $ip = filter_var($validated['ip_address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6);
                if ($ip !== false) {
                    $serverUpdates['ip_address'] = $ip;
                } else {
                    Log::warning('Invalid IP address received from agent', [
                        'server_id' => $server->id,
                        'ip_address' => $validated['ip_address'],
                    ]);
                }
            }
            if (isset($validated['agent_version']) && !empty($validated['agent_version'])) {
                $serverUpdates['agent_version'] = $validated['agent_version'];
                if (!$server->agent_installed_at) {
                    $serverUpdates['agent_installed_at'] = now();
                }
            }
            
            // Update persistent agent identifiers (only on first send or if changed)
            if (isset($validated['machine_id']) && !empty($validated['machine_id'])) {
                $serverUpdates['machine_id'] = $validated['machine_id'];
            }
            if (isset($validated['system_uuid']) && !empty($validated['system_uuid'])) {
                $serverUpdates['system_uuid'] = $validated['system_uuid'];
            }
            if (isset($validated['disk_uuid']) && !empty($validated['disk_uuid'])) {
                $serverUpdates['disk_uuid'] = $validated['disk_uuid'];
            }
            if (isset($validated['agent_id']) && !empty($validated['agent_id'])) {
                $serverUpdates['agent_id'] = $validated['agent_id'];
            }
            
            $serverUpdates['last_seen_at'] = now();
            
            if (!empty($serverUpdates)) {
                $server->update($serverUpdates);
            }

            // Parse recorded_at from agent (if provided) or use current time
            $recordedAt = now();
            if (isset($validated['recorded_at']) && !empty($validated['recorded_at'])) {
                try {
                    $recordedAt = Carbon::parse($validated['recorded_at']);
                } catch (\Exception $e) {
                    // If parsing fails, use current time
                    Log::warning('Failed to parse recorded_at from agent', [
                        'server_id' => $server->id,
                        'recorded_at' => $validated['recorded_at'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Normalize disk_usage array (ensure consistent format across OS)
            $diskUsage = null;
            if (isset($validated['disk_usage']) && is_array($validated['disk_usage'])) {
                $diskUsage = [];
                foreach ($validated['disk_usage'] as $disk) {
                    if (is_array($disk)) {
                        $diskUsage[] = [
                            'device' => $disk['device'] ?? null,
                            'mount_point' => $disk['mount_point'] ?? null,
                            'fs_type' => $disk['fs_type'] ?? null,
                            'total_bytes' => isset($disk['total_bytes']) ? (int)$disk['total_bytes'] : null,
                            'used_bytes' => isset($disk['used_bytes']) ? (int)$disk['used_bytes'] : null,
                            'free_bytes' => isset($disk['free_bytes']) ? (int)$disk['free_bytes'] : null,
                            'usage_percent' => isset($disk['usage_percent']) ? (float)$disk['usage_percent'] : null,
                        ];
                    }
                }
            }

            // Normalize network_interfaces array (ensure consistent format across OS)
            $networkInterfaces = null;
            if (isset($validated['network_interfaces']) && is_array($validated['network_interfaces'])) {
                $networkInterfaces = [];
                foreach ($validated['network_interfaces'] as $interface) {
                    if (is_array($interface)) {
                        $networkInterfaces[] = [
                            'name' => $interface['name'] ?? null,
                            'bytes_sent' => isset($interface['bytes_sent']) ? (int)$interface['bytes_sent'] : null,
                            'bytes_received' => isset($interface['bytes_received']) ? (int)$interface['bytes_received'] : null,
                            'packets_sent' => isset($interface['packets_sent']) ? (int)$interface['packets_sent'] : null,
                            'packets_received' => isset($interface['packets_received']) ? (int)$interface['packets_received'] : null,
                            'errors_in' => isset($interface['errors_in']) ? (int)$interface['errors_in'] : null,
                            'errors_out' => isset($interface['errors_out']) ? (int)$interface['errors_out'] : null,
                            'drop_in' => isset($interface['drop_in']) ? (int)$interface['drop_in'] : null,
                            'drop_out' => isset($interface['drop_out']) ? (int)$interface['drop_out'] : null,
                        ];
                    }
                }
            }

            // Normalize processes array (ensure consistent format across OS)
            $processes = null;
            if (isset($validated['processes']) && is_array($validated['processes'])) {
                $processes = [];
                foreach ($validated['processes'] as $proc) {
                    if (is_array($proc)) {
                        $processes[] = [
                            'pid' => isset($proc['pid']) ? (int)$proc['pid'] : null,
                            'name' => $proc['name'] ?? null,
                            'status' => $proc['status'] ?? null,
                            'cpu_percent' => isset($proc['cpu_percent']) ? (float)$proc['cpu_percent'] : null,
                            'memory_percent' => isset($proc['memory_percent']) ? (float)$proc['memory_percent'] : null,
                            'memory_bytes' => isset($proc['memory_bytes']) ? (int)$proc['memory_bytes'] : null,
                            'user' => $proc['user'] ?? null,
                            'command' => $proc['command'] ?? null,
                            'created_at' => isset($proc['created_at']) ? (int)$proc['created_at'] : null,
                        ];
                    }
                }
            }

            // Create stat record with OS-agnostic handling
            $stat = ServerStat::create([
                'server_id' => $server->id,
                
                // CPU Metrics (handle null values for OS that don't support load averages)
                'cpu_usage_percent' => isset($validated['cpu_usage_percent']) ? (float)$validated['cpu_usage_percent'] : null,
                'cpu_cores' => isset($validated['cpu_cores']) ? (int)$validated['cpu_cores'] : null,
                'cpu_load_1min' => isset($validated['cpu_load_1min']) ? (float)$validated['cpu_load_1min'] : null,
                'cpu_load_5min' => isset($validated['cpu_load_5min']) ? (float)$validated['cpu_load_5min'] : null,
                'cpu_load_15min' => isset($validated['cpu_load_15min']) ? (float)$validated['cpu_load_15min'] : null,
                
                // Memory Metrics
                'memory_total_bytes' => isset($validated['memory_total_bytes']) ? (int)$validated['memory_total_bytes'] : null,
                'memory_used_bytes' => isset($validated['memory_used_bytes']) ? (int)$validated['memory_used_bytes'] : null,
                'memory_free_bytes' => isset($validated['memory_free_bytes']) ? (int)$validated['memory_free_bytes'] : null,
                'memory_usage_percent' => isset($validated['memory_usage_percent']) ? (float)$validated['memory_usage_percent'] : null,
                
                // Swap Metrics (may be null on systems without swap)
                'swap_total_bytes' => isset($validated['swap_total_bytes']) ? (int)$validated['swap_total_bytes'] : null,
                'swap_used_bytes' => isset($validated['swap_used_bytes']) ? (int)$validated['swap_used_bytes'] : null,
                'swap_free_bytes' => isset($validated['swap_free_bytes']) ? (int)$validated['swap_free_bytes'] : null,
                'swap_usage_percent' => isset($validated['swap_usage_percent']) ? (float)$validated['swap_usage_percent'] : null,
                
                // Disk Metrics (normalized array)
                'disk_usage' => $diskUsage,
                'disk_total_bytes' => isset($validated['disk_total_bytes']) ? (int)$validated['disk_total_bytes'] : null,
                'disk_used_bytes' => isset($validated['disk_used_bytes']) ? (int)$validated['disk_used_bytes'] : null,
                'disk_free_bytes' => isset($validated['disk_free_bytes']) ? (int)$validated['disk_free_bytes'] : null,
                'disk_usage_percent' => isset($validated['disk_usage_percent']) ? (float)$validated['disk_usage_percent'] : null,
                
                // Network Metrics (normalized array)
                'network_interfaces' => $networkInterfaces,
                'network_bytes_sent' => isset($validated['network_bytes_sent']) ? (int)$validated['network_bytes_sent'] : null,
                'network_bytes_received' => isset($validated['network_bytes_received']) ? (int)$validated['network_bytes_received'] : null,
                'network_packets_sent' => isset($validated['network_packets_sent']) ? (int)$validated['network_packets_sent'] : null,
                'network_packets_received' => isset($validated['network_packets_received']) ? (int)$validated['network_packets_received'] : null,
                
                // System Info
                'uptime_seconds' => isset($validated['uptime_seconds']) ? (int)$validated['uptime_seconds'] : null,
                'processes_total' => isset($validated['processes_total']) ? (int)$validated['processes_total'] : null,
                'processes_running' => isset($validated['processes_running']) ? (int)$validated['processes_running'] : null,
                'processes_sleeping' => isset($validated['processes_sleeping']) ? (int)$validated['processes_sleeping'] : null,
                'processes' => $processes, // Detailed process information
                
                // Timestamp (use agent's timestamp if provided, otherwise server time)
                'recorded_at' => $recordedAt,
            ]);

            Log::info('Server stats stored successfully', [
                'server_id' => $server->id,
                'stat_id' => $stat->id,
                'os_type' => $validated['os_type'] ?? 'unknown',
                'recorded_at' => $recordedAt->toIso8601String(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stats recorded successfully.',
                'server_id' => $server->id,
                'stat_id' => $stat->id,
                'recorded_at' => $recordedAt->toIso8601String(),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Server stats validation failed', [
                'server_id' => $server->id ?? null,
                'errors' => $e->errors(),
                'request_data' => $request->except(['server_key']), // Don't log server key
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to store server stats', [
                'server_id' => $server->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['server_key']), // Don't log server key
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to store stats: ' . $e->getMessage()
            ], 500);
        }
    }
}
