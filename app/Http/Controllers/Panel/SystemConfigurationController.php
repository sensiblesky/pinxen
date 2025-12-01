<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SystemConfigurationController extends Controller
{
    /**
     * Display the system configuration page.
     */
    public function index(): View
    {
        $settings = Setting::getAllCached();
        
        // Get timezones for dropdown
        $timezones = \App\Models\Timezone::where('is_active', true)->get();
        
        return view('panel.system-configuration.index', compact('settings', 'timezones'));
    }

    /**
     * Update system configuration.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['nullable', 'string', 'max:255'],
            'app_author' => ['nullable', 'string', 'max:255'],
            'app_phone' => ['nullable', 'string', 'max:20'],
            'app_email' => ['nullable', 'email', 'max:255'],
            'app_address' => ['nullable', 'string', 'max:500'],
            'currency' => ['nullable', 'string', 'max:10'],
            'app_footer_text' => ['nullable', 'string', 'max:500'],
            'desktop_logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif', 'max:5120'],
            'desktop_dark_logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif', 'max:5120'],
            'toggle_logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif', 'max:5120'],
            'toggle_dark_logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif', 'max:5120'],
            'favicon' => ['nullable', 'file', 'mimes:ico', 'max:1024'],
            'timezone_id' => ['nullable', 'exists:timezones,id'],
        ]);

        // Handle text settings
        Setting::set('app_name', $validated['app_name'] ?? '');
        Setting::set('app_author', $validated['app_author'] ?? '');
        Setting::set('app_phone', $validated['app_phone'] ?? '');
        Setting::set('app_email', $validated['app_email'] ?? '');
        Setting::set('app_address', $validated['app_address'] ?? '');
        Setting::set('currency', $validated['currency'] ?? '');
        Setting::set('app_footer_text', $validated['app_footer_text'] ?? '');
        Setting::set('timezone_id', $validated['timezone_id'] ?? null);
        
        // Handle checkbox values - checkboxes with value="1" send "1" when checked, nothing when unchecked
        // $request->has() returns true if the field exists in the request (checked), false if not (unchecked)
        $forceHttps = $request->has('force_https');
        $maintenanceMode = $request->has('maintenance_mode');
        
        Setting::set('force_https', $forceHttps ? '1' : '0', 'boolean');
        Setting::set('maintenance_mode', $maintenanceMode ? '1' : '0', 'boolean');

        // Logo storage path
        $logoPath = public_path('build/assets/images/brand-logos');
        
        // Ensure directory exists
        if (!file_exists($logoPath)) {
            File::makeDirectory($logoPath, 0755, true);
        }

        // Handle desktop logo upload (150x35)
        if ($request->hasFile('desktop_logo')) {
            $file = $request->file('desktop_logo');
            
            // Validate image dimensions (150x35)
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo && ($imageInfo[0] != 150 || $imageInfo[1] != 35)) {
                return redirect()->route('panel.system-configuration.index')
                    ->with('error', 'Desktop logo must be exactly 150x35 pixels.');
            }
            
            // Validate it's actually an image
            if ($imageInfo === false) {
                return redirect()->route('panel.system-configuration.index')
                    ->with('error', 'Invalid image file for desktop logo.');
            }
            
            // Convert to PNG if needed and save with exact name
            $targetPath = $logoPath . '/desktop-logo.png';
            $this->saveImageAsPng($file, $targetPath);
        }

        // Handle desktop dark logo upload (150x35)
        if ($request->hasFile('desktop_dark_logo')) {
            $file = $request->file('desktop_dark_logo');
            
            // Validate image dimensions (150x35)
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo && ($imageInfo[0] != 150 || $imageInfo[1] != 35)) {
                return redirect()->route('panel.system-configuration.index')
                    ->with('error', 'Desktop dark logo must be exactly 150x35 pixels.');
            }
            
            // Validate it's actually an image
            if ($imageInfo === false) {
                return redirect()->route('panel.system-configuration.index')
                    ->with('error', 'Invalid image file for desktop dark logo.');
            }
            
            // Convert to PNG if needed and save with exact name
            $targetPath = $logoPath . '/desktop-dark.png';
            $this->saveImageAsPng($file, $targetPath);
        }

        // Handle toggle logo upload (36x41)
        if ($request->hasFile('toggle_logo')) {
            $file = $request->file('toggle_logo');
            
            // Validate image dimensions (36x41)
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo && ($imageInfo[0] != 36 || $imageInfo[1] != 41)) {
                return redirect()->route('panel.system-configuration.index')
                    ->with('error', 'Toggle logo must be exactly 36x41 pixels.');
            }
            
            // Validate it's actually an image
            if ($imageInfo === false) {
                return redirect()->route('panel.system-configuration.index')
                    ->with('error', 'Invalid image file for toggle logo.');
            }
            
            // Convert to PNG if needed and save with exact name
            $targetPath = $logoPath . '/toggle-logo.png';
            $this->saveImageAsPng($file, $targetPath);
        }

        // Handle toggle dark logo upload (36x41)
        if ($request->hasFile('toggle_dark_logo')) {
            $file = $request->file('toggle_dark_logo');
            
            // Validate image dimensions (36x41)
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo && ($imageInfo[0] != 36 || $imageInfo[1] != 41)) {
                return redirect()->route('panel.system-configuration.index')
                    ->with('error', 'Toggle dark logo must be exactly 36x41 pixels.');
            }
            
            // Validate it's actually an image
            if ($imageInfo === false) {
                return redirect()->route('panel.system-configuration.index')
                    ->with('error', 'Invalid image file for toggle dark logo.');
            }
            
            // Convert to PNG if needed and save with exact name
            $targetPath = $logoPath . '/toggle-dark.png';
            $this->saveImageAsPng($file, $targetPath);
        }

        // Handle favicon upload (32x32 to 50x50, .ico only)
        if ($request->hasFile('favicon')) {
            $file = $request->file('favicon');
            
            // Validate file extension is .ico
            $extension = strtolower($file->getClientOriginalExtension());
            if ($extension !== 'ico') {
                return redirect()->route('panel.system-configuration.index')
                    ->with('error', 'Favicon must be a .ico file.');
            }
            
            // Validate image dimensions (32x32 to 50x50)
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo === false) {
                return redirect()->route('panel.system-configuration.index')
                    ->with('error', 'Invalid image file for favicon.');
            }
            
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            
            // Check if dimensions are between 32x32 and 50x50
            if ($width < 32 || $width > 50 || $height < 32 || $height > 50) {
                return redirect()->route('panel.system-configuration.index')
                    ->with('error', 'Favicon dimensions must be between 32x32 and 50x50 pixels.');
            }
            
            // For ICO files, copy directly
            $targetPath = $logoPath . '/favicon.ico';
            copy($file->getRealPath(), $targetPath);
        }

        return redirect()->route('panel.system-configuration.index')
            ->with('success', 'System configuration updated successfully.');
    }

    /**
     * Save image as PNG with exact dimensions.
     */
    private function saveImageAsPng($file, $targetPath)
    {
        $imageInfo = @getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            throw new \Exception('Invalid image file');
        }

        $mimeType = $imageInfo['mime'];
        $width = $imageInfo[0];
        $height = $imageInfo[1];

        // Create image resource based on MIME type
        switch ($mimeType) {
            case 'image/png':
                $source = imagecreatefrompng($file->getRealPath());
                break;
            case 'image/jpeg':
            case 'image/jpg':
                $source = imagecreatefromjpeg($file->getRealPath());
                break;
            case 'image/gif':
                $source = imagecreatefromgif($file->getRealPath());
                break;
            default:
                throw new \Exception('Unsupported image type');
        }

        // Create new image with exact dimensions
        $target = imagecreatetruecolor($width, $height);
        
        // Preserve transparency for PNG
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
        imagefill($target, 0, 0, $transparent);
        
        // Copy and resize if needed
        imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, $width, $height);
        
        // Save as PNG
        imagepng($target, $targetPath, 9);
        
        // Clean up
        imagedestroy($source);
        imagedestroy($target);
    }
}
