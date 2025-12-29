<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    /**
     * Display a listing of API keys.
     */
    public function index(): View
    {
        $user = Auth::user();
        // Get all API keys (no pagination, DataTables will handle it client-side)
        $apiKeys = $user->apiKeys()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('client.api-keys.index', compact('apiKeys'));
    }

    /**
     * Show the form for creating a new API key.
     */
    public function create(): View
    {
        return view('client.api-keys.create');
    }

    /**
     * Store a newly created API key.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1000'],
                'scopes' => ['required', 'array', 'min:1'],
                'scopes.*' => ['in:create,update,view,delete,*'],
                'expires_at' => ['nullable', 'date', 'after:now'],
                'allowed_ips' => ['nullable', 'string', 'max:500'],
                'rate_limit' => ['nullable', 'integer', 'min:1', 'max:10000'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        $user = Auth::user();

        // Generate API key
        $key = ApiKey::generate();
        $keyPrefix = ApiKey::generatePrefix($key);

        // Parse allowed IPs
        $allowedIps = null;
        if ($request->filled('allowed_ips')) {
            $ips = array_map('trim', explode(',', $request->input('allowed_ips')));
            $allowedIps = implode(',', array_filter($ips));
        }

        $apiKey = ApiKey::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'key' => $key,
            'key_prefix' => $keyPrefix,
            'scopes' => $validated['scopes'],
            'description' => $validated['description'] ?? null,
            'expires_at' => isset($validated['expires_at']) && $validated['expires_at'] ? \Carbon\Carbon::parse($validated['expires_at']) : null,
            'allowed_ips' => $allowedIps,
            'rate_limit' => $validated['rate_limit'] ?? 60,
            'is_active' => true,
        ]);

            // If AJAX request, return JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'API key created successfully.',
                    'api_key' => [
                        'id' => $apiKey->id,
                        'name' => $apiKey->name,
                        'key_prefix' => $apiKey->key_prefix,
                        'scopes' => $apiKey->scopes,
                    ]
                ], 201);
            }

        // Store the full key in session to show only once
        session()->flash('api_key_created', $key);
        session()->flash('api_key_id', $apiKey->id);

        return redirect()->route('api-keys.show', $apiKey)
            ->with('success', 'API key created successfully. Please copy it now as it will not be shown again.');
    }

    /**
     * Display the specified API key.
     */
    public function show(ApiKey $apiKey): View
    {
        $user = Auth::user();
        
        // Ensure API key belongs to user
        if ($apiKey->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        // Get the full key from session if just created
        $fullKey = session('api_key_created');
        $isNewKey = session('api_key_id') == $apiKey->id;

        return view('client.api-keys.show', compact('apiKey', 'fullKey', 'isNewKey'));
    }

    /**
     * Show the form for editing the specified API key.
     */
    public function edit(ApiKey $apiKey): View
    {
        $user = Auth::user();
        
        // Ensure API key belongs to user
        if ($apiKey->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        return view('client.api-keys.edit', compact('apiKey'));
    }

    /**
     * Update the specified API key.
     */
    public function update(Request $request, ApiKey $apiKey): RedirectResponse
    {
        $user = Auth::user();
        
        // Ensure API key belongs to user
        if ($apiKey->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['in:create,update,view,delete,*'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'allowed_ips' => ['nullable', 'string', 'max:500'],
            'rate_limit' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Parse allowed IPs
        $allowedIps = null;
        if ($request->filled('allowed_ips')) {
            $ips = array_map('trim', explode(',', $request->input('allowed_ips')));
            $allowedIps = implode(',', array_filter($ips));
        }

        $apiKey->update([
            'name' => $validated['name'],
            'scopes' => $validated['scopes'],
            'description' => $validated['description'] ?? null,
            'expires_at' => isset($validated['expires_at']) && $validated['expires_at'] ? \Carbon\Carbon::parse($validated['expires_at']) : null,
            'allowed_ips' => $allowedIps,
            'rate_limit' => isset($validated['rate_limit']) ? $validated['rate_limit'] : 60,
            'is_active' => $request->has('is_active') && $request->input('is_active') == '1',
        ]);

        return redirect()->route('api-keys.index')
            ->with('success', 'API key updated successfully.');
    }

    /**
     * Remove the specified API key.
     */
    public function destroy(ApiKey $apiKey): RedirectResponse
    {
        $user = Auth::user();
        
        // Ensure API key belongs to user
        if ($apiKey->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $apiKey->delete();

        return redirect()->route('api-keys.index')
            ->with('success', 'API key deleted successfully.');
    }

    /**
     * Regenerate API key (creates new key, keeps same record).
     */
    public function regenerate(ApiKey $apiKey): RedirectResponse
    {
        $user = Auth::user();
        
        // Ensure API key belongs to user
        if ($apiKey->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        // Generate new key
        $newKey = ApiKey::generate();
        $newKeyPrefix = ApiKey::generatePrefix($newKey);

        $apiKey->update([
            'key' => $newKey,
            'key_prefix' => $newKeyPrefix,
            'last_used_at' => null, // Reset last used
        ]);

        // Store the full key in session to show only once
        session()->flash('api_key_created', $newKey);
        session()->flash('api_key_id', $apiKey->id);

        return redirect()->route('api-keys.show', $apiKey)
            ->with('success', 'API key regenerated successfully. Please copy it now as it will not be shown again.');
    }

    /**
     * Toggle API key active status.
     */
    public function toggle(ApiKey $apiKey): RedirectResponse
    {
        $user = Auth::user();
        
        // Ensure API key belongs to user
        if ($apiKey->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $apiKey->update([
            'is_active' => !$apiKey->is_active,
        ]);

        $status = $apiKey->is_active ? 'activated' : 'deactivated';

        return redirect()->route('api-keys.index')
            ->with('success', "API key {$status} successfully.");
    }
}
