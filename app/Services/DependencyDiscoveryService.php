<?php

namespace App\Services;

use App\Models\ApiMonitor;
use App\Models\ApiMonitorDependency;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DependencyDiscoveryService
{
    /**
     * Analyze API response and discover dependencies.
     *
     * @param ApiMonitor $monitor
     * @param string $responseBody
     * @param array $responseHeaders
     * @param string|null $errorMessage
     * @return array Discovered dependencies
     */
    public function discoverDependencies(ApiMonitor $monitor, string $responseBody, array $responseHeaders = [], ?string $errorMessage = null): array
    {
        $dependencies = [];

        // 1. Check response body for API URLs
        $dependencies = array_merge($dependencies, $this->extractApiUrlsFromResponse($responseBody, $monitor));

        // 2. Check error messages for dependency clues
        if ($errorMessage) {
            $dependencies = array_merge($dependencies, $this->extractDependenciesFromError($errorMessage, $monitor));
        }

        // 3. Check response headers for service names
        $dependencies = array_merge($dependencies, $this->extractDependenciesFromHeaders($responseHeaders, $monitor));

        // 4. Check for database connection errors
        $dependencies = array_merge($dependencies, $this->extractDatabaseDependencies($errorMessage, $responseBody, $monitor));

        // 5. Cross-reference with other monitors
        $dependencies = array_merge($dependencies, $this->crossReferenceMonitors($monitor, $dependencies));

        return $dependencies;
    }

    /**
     * Extract API URLs from response body.
     */
    protected function extractApiUrlsFromResponse(string $responseBody, ApiMonitor $monitor): array
    {
        $dependencies = [];
        
        // Extract URLs from JSON responses
        if (json_decode($responseBody) !== null) {
            $data = json_decode($responseBody, true);
            $urls = $this->extractUrlsFromArray($data);
            
            foreach ($urls as $url) {
                // Check if this URL matches another monitor
                $relatedMonitor = $this->findRelatedMonitor($url, $monitor->user_id);
                
                if ($relatedMonitor) {
                    $dependencies[] = [
                        'type' => 'api',
                        'name' => $relatedMonitor->name,
                        'url' => $url,
                        'monitor_id' => $relatedMonitor->id,
                        'confidence' => 80,
                        'evidence' => "Found URL in response body: {$url}",
                    ];
                } else {
                    // Unknown API dependency
                    $dependencies[] = [
                        'type' => 'api',
                        'name' => $this->extractServiceNameFromUrl($url),
                        'url' => $url,
                        'monitor_id' => null,
                        'confidence' => 60,
                        'evidence' => "Found URL in response body: {$url}",
                    ];
                }
            }
        }

        return $dependencies;
    }

    /**
     * Extract URLs from nested array/object.
     */
    protected function extractUrlsFromArray($data, array &$urls = []): array
    {
        if (is_array($data)) {
            foreach ($data as $value) {
                if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                    $urls[] = $value;
                } elseif (is_array($value) || is_object($value)) {
                    $this->extractUrlsFromArray($value, $urls);
                }
            }
        } elseif (is_object($data)) {
            foreach ($data as $value) {
                if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                    $urls[] = $value;
                } elseif (is_array($value) || is_object($value)) {
                    $this->extractUrlsFromArray($value, $urls);
                }
            }
        }

        return array_unique($urls);
    }

    /**
     * Extract dependencies from error messages.
     */
    protected function extractDependenciesFromError(string $errorMessage, ApiMonitor $monitor): array
    {
        $dependencies = [];
        
        // Common error patterns
        $patterns = [
            '/connection.*refused.*(?:to|on)\s+([^\s]+)/i' => 'api',
            '/failed.*connect.*(?:to|on)\s+([^\s]+)/i' => 'api',
            '/timeout.*(?:connecting|connecting to)\s+([^\s]+)/i' => 'api',
            '/database.*connection.*failed/i' => 'database',
            '/mysql.*connection.*failed/i' => 'database',
            '/postgresql.*connection.*failed/i' => 'database',
            '/redis.*connection.*failed/i' => 'database',
            '/mongodb.*connection.*failed/i' => 'database',
        ];

        foreach ($patterns as $pattern => $type) {
            if (preg_match($pattern, $errorMessage, $matches)) {
                $serviceName = $matches[1] ?? 'Unknown Service';
                
                if ($type === 'database') {
                    $dependencies[] = [
                        'type' => 'database',
                        'name' => $this->extractDatabaseName($errorMessage),
                        'url' => null,
                        'monitor_id' => null,
                        'confidence' => 70,
                        'evidence' => "Error message suggests database dependency: {$errorMessage}",
                    ];
                } else {
                    // Try to find related monitor
                    $relatedMonitor = $this->findRelatedMonitor($serviceName, $monitor->user_id);
                    
                    $dependencies[] = [
                        'type' => 'api',
                        'name' => $relatedMonitor ? $relatedMonitor->name : $serviceName,
                        'url' => $relatedMonitor ? $relatedMonitor->url : null,
                        'monitor_id' => $relatedMonitor ? $relatedMonitor->id : null,
                        'confidence' => 75,
                        'evidence' => "Error message suggests dependency: {$errorMessage}",
                    ];
                }
            }
        }

        return $dependencies;
    }

    /**
     * Extract dependencies from response headers.
     */
    protected function extractDependenciesFromHeaders(array $headers, ApiMonitor $monitor): array
    {
        $dependencies = [];
        
        // Check for service name headers
        $serviceHeaders = ['X-Service-Name', 'X-Upstream-Service', 'X-Dependency', 'Server'];
        
        foreach ($serviceHeaders as $headerName) {
            if (isset($headers[$headerName])) {
                $serviceName = $headers[$headerName];
                
                // Handle case where header value is an array (multiple values for same header)
                if (is_array($serviceName)) {
                    // Take the first value if it's an array
                    $serviceName = !empty($serviceName) ? (string) reset($serviceName) : null;
                } else {
                    // Convert to string if not already
                    $serviceName = (string) $serviceName;
                }
                
                // Skip if service name is empty or not a valid string
                if (empty($serviceName) || !is_string($serviceName)) {
                    continue;
                }
                
                $relatedMonitor = $this->findRelatedMonitorByName($serviceName, $monitor->user_id);
                
                $dependencies[] = [
                    'type' => 'api',
                    'name' => $serviceName,
                    'url' => $relatedMonitor ? $relatedMonitor->url : null,
                    'monitor_id' => $relatedMonitor ? $relatedMonitor->id : null,
                    'confidence' => 65,
                    'evidence' => "Found in response header: {$headerName} = {$serviceName}",
                ];
            }
        }

        return $dependencies;
    }

    /**
     * Extract database dependencies.
     */
    protected function extractDatabaseDependencies(?string $errorMessage, string $responseBody, ApiMonitor $monitor): array
    {
        $dependencies = [];
        
        $databasePatterns = [
            '/mysql/i' => 'MySQL',
            '/postgresql|postgres/i' => 'PostgreSQL',
            '/mongodb|mongo/i' => 'MongoDB',
            '/redis/i' => 'Redis',
            '/elasticsearch/i' => 'Elasticsearch',
            '/cassandra/i' => 'Cassandra',
        ];

        $text = ($errorMessage ?? '') . ' ' . $responseBody;
        
        foreach ($databasePatterns as $pattern => $dbName) {
            if (preg_match($pattern, $text)) {
                $dependencies[] = [
                    'type' => 'database',
                    'name' => $dbName,
                    'url' => null,
                    'monitor_id' => null,
                    'confidence' => 60,
                    'evidence' => "Database reference found: {$dbName}",
                ];
            }
        }

        return $dependencies;
    }

    /**
     * Cross-reference with other monitors to find relationships.
     */
    protected function crossReferenceMonitors(ApiMonitor $monitor, array $discoveredDeps): array
    {
        $dependencies = [];
        
        // Get all monitors for the same user
        $otherMonitors = ApiMonitor::where('user_id', $monitor->user_id)
            ->where('id', '!=', $monitor->id)
            ->get();

        foreach ($discoveredDeps as $dep) {
            if ($dep['monitor_id']) {
                continue; // Already matched
            }

            // Try to match by URL
            if ($dep['url']) {
                foreach ($otherMonitors as $otherMonitor) {
                    if ($this->urlsMatch($dep['url'], $otherMonitor->url)) {
                        $dep['monitor_id'] = $otherMonitor->id;
                        $dep['name'] = $otherMonitor->name;
                        $dep['confidence'] = 90;
                        break;
                    }
                }
            }

            // Try to match by name
            if (!$dep['monitor_id'] && $dep['name']) {
                foreach ($otherMonitors as $otherMonitor) {
                    if (stripos($otherMonitor->name, $dep['name']) !== false || 
                        stripos($dep['name'], $otherMonitor->name) !== false) {
                        $dep['monitor_id'] = $otherMonitor->id;
                        $dep['url'] = $otherMonitor->url;
                        $dep['confidence'] = 85;
                        break;
                    }
                }
            }

            $dependencies[] = $dep;
        }

        return $dependencies;
    }

    /**
     * Find related monitor by URL.
     */
    protected function findRelatedMonitor(string $url, int $userId): ?ApiMonitor
    {
        // Extract base URL
        $parsed = parse_url($url);
        $baseUrl = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        
        return ApiMonitor::where('user_id', $userId)
            ->where(function($query) use ($url, $baseUrl) {
                $query->where('url', 'like', "%{$baseUrl}%")
                      ->orWhere('url', 'like', "%{$url}%");
            })
            ->first();
    }

    /**
     * Find related monitor by name.
     */
    protected function findRelatedMonitorByName(string $name, int $userId): ?ApiMonitor
    {
        return ApiMonitor::where('user_id', $userId)
            ->where('name', 'like', "%{$name}%")
            ->first();
    }

    /**
     * Check if two URLs match (same host/base).
     */
    protected function urlsMatch(string $url1, string $url2): bool
    {
        $parsed1 = parse_url($url1);
        $parsed2 = parse_url($url2);
        
        $host1 = $parsed1['host'] ?? '';
        $host2 = $parsed2['host'] ?? '';
        
        return $host1 === $host2 && !empty($host1);
    }

    /**
     * Extract service name from URL.
     */
    protected function extractServiceNameFromUrl(string $url): string
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? 'Unknown Service';
        
        // Remove common TLDs and extract service name
        $host = preg_replace('/\.(com|net|org|io|co|dev)$/i', '', $host);
        $parts = explode('.', $host);
        
        return ucfirst(end($parts)) . ' API';
    }

    /**
     * Extract database name from error message.
     */
    protected function extractDatabaseName(string $errorMessage): string
    {
        $patterns = [
            '/mysql/i' => 'MySQL',
            '/postgresql|postgres/i' => 'PostgreSQL',
            '/mongodb|mongo/i' => 'MongoDB',
            '/redis/i' => 'Redis',
        ];

        foreach ($patterns as $pattern => $name) {
            if (preg_match($pattern, $errorMessage)) {
                return $name;
            }
        }

        return 'Database';
    }

    /**
     * Save discovered dependencies.
     */
    public function saveDependencies(ApiMonitor $monitor, array $dependencies): void
    {
        foreach ($dependencies as $dep) {
            // Check if dependency already exists
            $existing = ApiMonitorDependency::where('api_monitor_id', $monitor->id)
                ->where(function($query) use ($dep) {
                    if ($dep['monitor_id']) {
                        $query->where('depends_on_monitor_id', $dep['monitor_id']);
                    } else {
                        $query->where('dependency_name', $dep['name'])
                              ->where('dependency_type', $dep['type']);
                    }
                })
                ->first();

            if ($existing) {
                // Update confidence and evidence
                $existing->update([
                    'confidence_score' => max($existing->confidence_score, $dep['confidence']),
                    'discovery_evidence' => array_merge(
                        $existing->discovery_evidence ?? [],
                        [$dep['evidence']]
                    ),
                ]);
            } else {
                // Create new dependency
                ApiMonitorDependency::create([
                    'api_monitor_id' => $monitor->id,
                    'depends_on_monitor_id' => $dep['monitor_id'] ?? null,
                    'dependency_type' => $dep['type'],
                    'dependency_name' => $dep['name'],
                    'dependency_url' => $dep['url'] ?? null,
                    'discovery_method' => 'auto',
                    'discovery_evidence' => [$dep['evidence']],
                    'confidence_score' => $dep['confidence'],
                    'is_confirmed' => false,
                    'suppress_child_alerts' => true,
                ]);
            }
        }
    }

    /**
     * Build dependency tree for a monitor.
     *
     * @param ApiMonitor $monitor
     * @return array Dependency tree structure
     */
    public function buildDependencyTree(ApiMonitor $monitor): array
    {
        $tree = [
            'monitor' => [
                'id' => $monitor->id,
                'name' => $monitor->name,
                'url' => $monitor->url,
                'status' => $monitor->status,
            ],
            'dependencies' => [],
            'dependents' => [], // Monitors that depend on this one
        ];

        // Get direct dependencies
        $dependencies = ApiMonitorDependency::where('api_monitor_id', $monitor->id)
            ->where('is_confirmed', true)
            ->with('dependsOnMonitor')
            ->get();

        foreach ($dependencies as $dep) {
            $depNode = [
                'id' => $dep->id,
                'name' => $dep->dependency_name,
                'type' => $dep->dependency_type,
                'url' => $dep->dependency_url,
                'confidence' => $dep->confidence_score,
                'status' => $dep->dependsOnMonitor ? $dep->dependsOnMonitor->status : 'unknown',
            ];

            // Recursively build tree for API dependencies
            if ($dep->depends_on_monitor_id && $dep->dependsOnMonitor) {
                $depNode['children'] = $this->buildDependencyTree($dep->dependsOnMonitor)['dependencies'];
            }

            $tree['dependencies'][] = $depNode;
        }

        // Get dependents (monitors that depend on this one)
        $dependents = ApiMonitorDependency::where('depends_on_monitor_id', $monitor->id)
            ->where('is_confirmed', true)
            ->with('monitor')
            ->get();

        foreach ($dependents as $dependent) {
            $tree['dependents'][] = [
                'id' => $dependent->monitor->id,
                'name' => $dependent->monitor->name,
                'url' => $dependent->monitor->url,
                'status' => $dependent->monitor->status,
            ];
        }

        return $tree;
    }

    /**
     * Find root cause of failure.
     *
     * @param ApiMonitor $monitor
     * @return array Root cause analysis
     */
    public function findRootCause(ApiMonitor $monitor): array
    {
        $rootCause = [
            'root' => null,
            'chain' => [],
            'affected_services' => [],
        ];

        // If monitor is down, check dependencies
        if ($monitor->status === 'down') {
            $dependencies = ApiMonitorDependency::where('api_monitor_id', $monitor->id)
                ->where('is_confirmed', true)
                ->with('dependsOnMonitor')
                ->get();

            foreach ($dependencies as $dep) {
                if ($dep->dependsOnMonitor && $dep->dependsOnMonitor->status === 'down') {
                    // This dependency is also down - it might be the root cause
                    $rootCause['chain'][] = [
                        'monitor' => $monitor->name,
                        'depends_on' => $dep->dependsOnMonitor->name,
                        'status' => 'down',
                    ];

                    // Recursively find root cause
                    $subRoot = $this->findRootCause($dep->dependsOnMonitor);
                    if ($subRoot['root']) {
                        $rootCause['root'] = $subRoot['root'];
                        $rootCause['chain'] = array_merge($rootCause['chain'], $subRoot['chain']);
                    } else {
                        $rootCause['root'] = $dep->dependsOnMonitor;
                    }
                }
            }

            // If no dependency is down, this might be the root
            if (!$rootCause['root']) {
                $rootCause['root'] = $monitor;
            }
        }

        // Find all affected services (dependents)
        $dependents = ApiMonitorDependency::where('depends_on_monitor_id', $monitor->id)
            ->where('is_confirmed', true)
            ->with('monitor')
            ->get();

        foreach ($dependents as $dependent) {
            $rootCause['affected_services'][] = [
                'id' => $dependent->monitor->id,
                'name' => $dependent->monitor->name,
                'status' => $dependent->monitor->status,
            ];
        }

        return $rootCause;
    }

    /**
     * Check if alerts should be suppressed for a monitor.
     *
     * @param ApiMonitor $monitor
     * @return bool
     */
    public function shouldSuppressAlerts(ApiMonitor $monitor): bool
    {
        // Check if any parent dependency is down
        $dependencies = ApiMonitorDependency::where('api_monitor_id', $monitor->id)
            ->where('is_confirmed', true)
            ->where('suppress_child_alerts', true)
            ->with('dependsOnMonitor')
            ->get();

        foreach ($dependencies as $dep) {
            if ($dep->dependsOnMonitor && $dep->dependsOnMonitor->status === 'down') {
                return true; // Suppress alert because parent is down
            }
        }

        return false;
    }
}

