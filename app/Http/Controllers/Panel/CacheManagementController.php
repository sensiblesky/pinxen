<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CacheManagementController extends Controller
{
    /**
     * Display the cache management page.
     */
    public function index(): View
    {
        // Get cache statistics
        $cacheStats = $this->getCacheStatistics();
        
        return view('panel.cache-management.index', compact('cacheStats'));
    }

    /**
     * Clear all cache.
     */
    public function clearAll(Request $request): RedirectResponse
    {
        try {
            // Clear application cache using both methods
            Cache::flush();
            Artisan::call('cache:clear');
            
            // Clear config cache
            Artisan::call('config:clear');
            if (file_exists(base_path('bootstrap/cache/config.php'))) {
                @unlink(base_path('bootstrap/cache/config.php'));
            }
            
            // Clear route cache
            Artisan::call('route:clear');
            $routeCacheFiles = [
                base_path('bootstrap/cache/routes-v7.php'),
                base_path('bootstrap/cache/routes.php'),
            ];
            foreach ($routeCacheFiles as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
            
            // Clear view cache
            Artisan::call('view:clear');
            $viewPath = storage_path('framework/views');
            if (is_dir($viewPath)) {
                $files = glob($viewPath . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
            
            // Clear optimization cache
            Artisan::call('optimize:clear');
            
            // Clear event cache
            if (file_exists(base_path('bootstrap/cache/events.php'))) {
                @unlink(base_path('bootstrap/cache/events.php'));
            }
            
            // Clear settings cache
            Setting::clearCache();
            
            // Use the request's URL to maintain the same host/port
            $baseUrl = $request->getSchemeAndHttpHost() . '/panel/cache-management';
            return redirect($baseUrl)->with('success', 'All cache cleared successfully.');
        } catch (\Exception $e) {
            $baseUrl = $request->getSchemeAndHttpHost() . '/panel/cache-management';
            return redirect($baseUrl)->with('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }

    /**
     * Clear specific cache type.
     */
    public function clearSpecific(Request $request): RedirectResponse
    {
        $type = $request->input('type');
        
        // Get the base URL from the request
        $baseUrl = $request->getSchemeAndHttpHost() . '/panel/cache-management';
        
        try {
            switch ($type) {
                case 'application':
                    // Clear application cache using both methods
                    Cache::flush();
                    Artisan::call('cache:clear');
                    $message = 'Application cache cleared successfully.';
                    break;
                case 'config':
                    Artisan::call('config:clear');
                    if (file_exists(base_path('bootstrap/cache/config.php'))) {
                        @unlink(base_path('bootstrap/cache/config.php'));
                    }
                    $message = 'Configuration cache cleared successfully.';
                    break;
                case 'route':
                    Artisan::call('route:clear');
                    $routeCacheFiles = [
                        base_path('bootstrap/cache/routes-v7.php'),
                        base_path('bootstrap/cache/routes.php'),
                    ];
                    foreach ($routeCacheFiles as $file) {
                        if (file_exists($file)) {
                            @unlink($file);
                        }
                    }
                    $message = 'Route cache cleared successfully.';
                    break;
                case 'view':
                    Artisan::call('view:clear');
                    $viewPath = storage_path('framework/views');
                    if (is_dir($viewPath)) {
                        $files = glob($viewPath . '/*');
                        foreach ($files as $file) {
                            if (is_file($file)) {
                                @unlink($file);
                            }
                        }
                    }
                    $message = 'View cache cleared successfully.';
                    break;
                case 'optimize':
                    Artisan::call('optimize:clear');
                    // Also clear individual optimization files
                    $optimizeFiles = [
                        base_path('bootstrap/cache/config.php'),
                        base_path('bootstrap/cache/routes-v7.php'),
                        base_path('bootstrap/cache/routes.php'),
                        base_path('bootstrap/cache/events.php'),
                    ];
                    foreach ($optimizeFiles as $file) {
                        if (file_exists($file)) {
                            @unlink($file);
                        }
                    }
                    $message = 'Optimization cache cleared successfully.';
                    break;
                case 'settings':
                    Setting::clearCache();
                    $message = 'Settings cache cleared successfully.';
                    break;
                default:
                    return redirect($baseUrl)->with('error', 'Invalid cache type.');
            }
            
            return redirect($baseUrl)->with('success', $message);
        } catch (\Exception $e) {
            return redirect($baseUrl)->with('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }

    /**
     * Optimize application.
     */
    public function optimize(Request $request): RedirectResponse
    {
        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            Artisan::call('event:cache');
            
            // Use the request's URL to maintain the same host/port
            $baseUrl = $request->getSchemeAndHttpHost() . '/panel/cache-management';
            return redirect($baseUrl)
                ->with('success', 'Application optimized successfully. All caches have been regenerated.');
        } catch (\Exception $e) {
            $baseUrl = $request->getSchemeAndHttpHost() . '/panel/cache-management';
            return redirect($baseUrl)
                ->with('error', 'Failed to optimize: ' . $e->getMessage());
        }
    }

    /**
     * Warm up cache.
     */
    public function warmup(Request $request): RedirectResponse
    {
        try {
            // Warm up settings cache
            Setting::getAllCached();
            
            // Clear and rebuild config cache
            Artisan::call('config:cache');
            
            // Clear and rebuild route cache
            Artisan::call('route:cache');
            
            // Use the request's URL to maintain the same host/port
            $baseUrl = $request->getSchemeAndHttpHost() . '/panel/cache-management';
            return redirect($baseUrl)
                ->with('success', 'Cache warmed up successfully.');
        } catch (\Exception $e) {
            $baseUrl = $request->getSchemeAndHttpHost() . '/panel/cache-management';
            return redirect($baseUrl)
                ->with('error', 'Failed to warm up cache: ' . $e->getMessage());
        }
    }

    /**
     * Get cache statistics.
     */
    private function getCacheStatistics(): array
    {
        $stats = [
            'cache_driver' => config('cache.default'),
            'cache_size' => $this->getCacheSize(),
            'config_cached' => file_exists(base_path('bootstrap/cache/config.php')),
            'route_cached' => file_exists(base_path('bootstrap/cache/routes-v7.php')) || file_exists(base_path('bootstrap/cache/routes.php')),
            'view_cached' => $this->getViewCacheCount(),
            'settings_cached' => Cache::has(Setting::CACHE_KEY),
        ];
        
        return $stats;
    }

    /**
     * Get cache size in MB.
     */
    private function getCacheSize(): string
    {
        $cachePath = storage_path('framework/cache');
        if (!is_dir($cachePath)) {
            return '0 MB';
        }
        
        try {
            $size = 0;
            $files = File::allFiles($cachePath);
            foreach ($files as $file) {
                $size += $file->getSize();
            }
            
            return number_format($size / 1048576, 2) . ' MB';
        } catch (\Exception $e) {
            return '0 MB';
        }
    }

    /**
     * Get view cache count.
     */
    private function getViewCacheCount(): int
    {
        $viewPath = storage_path('framework/views');
        if (!is_dir($viewPath)) {
            return 0;
        }
        
        return count(File::allFiles($viewPath));
    }
}
