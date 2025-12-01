<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class StorageController extends Controller
{
    /**
     * Display the Storage configuration page.
     */
    public function index(): View
    {
        $settings = Setting::getAllCached();
        
        // Decrypt sensitive fields for display (if they exist and are encrypted)
        $encryptedFields = [
            's3_secret_key',
            'wasabi_secret_key',
        ];
        
        foreach ($encryptedFields as $field) {
            if (isset($settings[$field]) && !empty($settings[$field])) {
                try {
                    $settings[$field] = Crypt::decryptString($settings[$field]);
                } catch (\Exception $e) {
                    // If decryption fails, it might not be encrypted yet, keep original value
                    $settings[$field] = $settings[$field];
                }
            } else {
                $settings[$field] = '';
            }
        }
        
        return view('panel.storage.index', compact('settings'));
    }

    /**
     * Update Storage configuration.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Default Storage Disk
            'default_storage_disk' => ['required', 'string', 'in:local,s3,wasabi'],
            
            // Amazon S3 Settings
            's3_key' => ['nullable', 'string', 'max:255'],
            's3_secret_key' => ['nullable', 'string', 'max:255'],
            's3_region' => ['nullable', 'string', 'max:50'],
            's3_bucket' => ['nullable', 'string', 'max:255'],
            's3_endpoint' => ['nullable', 'url', 'max:500'],
            
            // Wasabi Settings
            'wasabi_key' => ['nullable', 'string', 'max:255'],
            'wasabi_secret_key' => ['nullable', 'string', 'max:255'],
            'wasabi_region' => ['nullable', 'string', 'max:50'],
            'wasabi_bucket' => ['nullable', 'string', 'max:255'],
            'wasabi_endpoint' => ['nullable', 'url', 'max:500'],
        ]);

        // Save default storage disk
        Setting::set('default_storage_disk', $validated['default_storage_disk']);

        // Save Amazon S3 settings
        Setting::set('s3_key', $validated['s3_key'] ?? '');
        if (!empty($validated['s3_secret_key'])) {
            Setting::set('s3_secret_key', Crypt::encryptString($validated['s3_secret_key']));
        }
        Setting::set('s3_region', $validated['s3_region'] ?? '');
        Setting::set('s3_bucket', $validated['s3_bucket'] ?? '');
        Setting::set('s3_endpoint', $validated['s3_endpoint'] ?? '');

        // Save Wasabi settings
        Setting::set('wasabi_key', $validated['wasabi_key'] ?? '');
        if (!empty($validated['wasabi_secret_key'])) {
            Setting::set('wasabi_secret_key', Crypt::encryptString($validated['wasabi_secret_key']));
        }
        Setting::set('wasabi_region', $validated['wasabi_region'] ?? '');
        Setting::set('wasabi_bucket', $validated['wasabi_bucket'] ?? '');
        Setting::set('wasabi_endpoint', $validated['wasabi_endpoint'] ?? '');

        return redirect()->route('panel.storage.index')
            ->with('success', 'Storage configuration updated successfully.');
    }

    /**
     * Test connection to storage provider.
     */
    public function testConnection(Request $request): RedirectResponse
    {
        $provider = $request->input('provider'); // 's3' or 'wasabi'
        
        if (!in_array($provider, ['s3', 'wasabi'])) {
            return redirect()->route('panel.storage.index')
                ->with('error', 'Invalid storage provider.');
        }

        $settings = Setting::getAllCached();
        
        try {
            if ($provider === 's3') {
                $key = $settings['s3_key'] ?? '';
                $secret = isset($settings['s3_secret_key']) && !empty($settings['s3_secret_key']) 
                    ? Crypt::decryptString($settings['s3_secret_key']) 
                    : '';
                $region = $settings['s3_region'] ?? '';
                $bucket = $settings['s3_bucket'] ?? '';
                $endpoint = $settings['s3_endpoint'] ?? '';
                
                if (empty($key) || empty($secret) || empty($bucket)) {
                    return redirect()->route('panel.storage.index')
                        ->with('error', 'S3 credentials are incomplete. Please fill in all required fields.');
                }
                
                // Test S3 connection using AWS SDK directly
                $s3Config = [
                    'version' => 'latest',
                    'region' => $region,
                    'credentials' => [
                        'key' => $key,
                        'secret' => $secret,
                    ],
                ];
                
                if (!empty($endpoint)) {
                    $s3Config['endpoint'] = $endpoint;
                }
                
                $s3Client = new S3Client($s3Config);
                // Test connection by checking if bucket exists
                $s3Client->headBucket(['Bucket' => $bucket]);
                
            } elseif ($provider === 'wasabi') {
                $key = $settings['wasabi_key'] ?? '';
                $secret = isset($settings['wasabi_secret_key']) && !empty($settings['wasabi_secret_key']) 
                    ? Crypt::decryptString($settings['wasabi_secret_key']) 
                    : '';
                $region = $settings['wasabi_region'] ?? '';
                $bucket = $settings['wasabi_bucket'] ?? '';
                $endpoint = $settings['wasabi_endpoint'] ?? '';
                
                if (empty($key) || empty($secret) || empty($bucket)) {
                    return redirect()->route('panel.storage.index')
                        ->with('error', 'Wasabi credentials are incomplete. Please fill in all required fields.');
                }
                
                // Test Wasabi connection using AWS SDK (Wasabi is S3-compatible)
                $wasabiEndpoint = $endpoint ?: 'https://s3.wasabisys.com';
                
                $s3Config = [
                    'version' => 'latest',
                    'region' => $region,
                    'credentials' => [
                        'key' => $key,
                        'secret' => $secret,
                    ],
                    'endpoint' => $wasabiEndpoint,
                    'use_path_style_endpoint' => true,
                ];
                
                $s3Client = new S3Client($s3Config);
                // Test connection by checking if bucket exists
                $s3Client->headBucket(['Bucket' => $bucket]);
            }
            
            return redirect()->route('panel.storage.index')
                ->with('success', ucfirst($provider) . ' connection test successful!');
                
        } catch (\Exception $e) {
            return redirect()->route('panel.storage.index')
                ->with('error', ucfirst($provider) . ' connection test failed: ' . $e->getMessage());
        }
    }
}
