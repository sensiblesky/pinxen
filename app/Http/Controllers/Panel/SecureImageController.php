<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class SecureImageController extends Controller
{
    /**
     * Serve user avatar securely without exposing file path.
     */
    public function serveAvatar(Request $request, $encryptedPath)
    {
        try {
            // URL decode first (in case it was encoded)
            $encryptedPath = urldecode($encryptedPath);
            
            // Decrypt the path
            $decryptedPath = Crypt::decryptString($encryptedPath);
            
            // Verify the path is within avatars directory (prevent path traversal)
            if (!str_starts_with($decryptedPath, 'avatars/')) {
                abort(404);
            }
            
            // Check if file exists
            if (!Storage::disk('public')->exists($decryptedPath)) {
                abort(404);
            }
            
            // Get file contents
            $fileContents = Storage::disk('public')->get($decryptedPath);
            
            // Get mime type
            $mimeType = Storage::disk('public')->mimeType($decryptedPath);
            
            // Validate it's an image
            if (!str_starts_with($mimeType, 'image/')) {
                abort(404);
            }
            
            // Return image with proper headers
            return response($fileContents, 200)
                ->header('Content-Type', $mimeType)
                ->header('Cache-Control', 'private, max-age=3600')
                ->header('X-Content-Type-Options', 'nosniff')
                ->header('X-Frame-Options', 'SAMEORIGIN');
                
        } catch (\Exception $e) {
            \Log::warning('Failed to serve secure avatar: ' . $e->getMessage());
            abort(404);
        }
    }
}

