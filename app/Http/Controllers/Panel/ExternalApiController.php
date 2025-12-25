<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ExternalApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ExternalApiController extends Controller
{
    /**
     * Display a listing of external APIs.
     */
    public function index(): View
    {
        $apis = ExternalApi::orderBy('service_type')->orderBy('name')->get();
        
        return view('panel.external-apis.index', compact('apis'));
    }

    /**
     * Show the form for creating a new external API.
     */
    public function create(): View
    {
        return view('panel.external-apis.create');
    }

    /**
     * Store a newly created external API.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['required', 'string', 'max:255'],
            'service_type' => ['required', 'string', 'max:100'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'api_secret' => ['nullable', 'string', 'max:500'],
            'base_url' => ['nullable', 'url', 'max:500'],
            'endpoint' => ['nullable', 'string', 'max:500'],
            'headers' => ['nullable', 'json'],
            'config' => ['nullable', 'json'],
            'rate_limit' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Encrypt sensitive fields
        if (!empty($validated['api_key'])) {
            $validated['api_key'] = Crypt::encryptString($validated['api_key']);
        }
        if (!empty($validated['api_secret'])) {
            $validated['api_secret'] = Crypt::encryptString($validated['api_secret']);
        }

        // Parse JSON fields
        if (isset($validated['headers']) && is_string($validated['headers'])) {
            $validated['headers'] = json_decode($validated['headers'], true);
        }
        if (isset($validated['config']) && is_string($validated['config'])) {
            $validated['config'] = json_decode($validated['config'], true);
        }

        ExternalApi::create($validated);

        return redirect()->route('panel.external-apis.index')
            ->with('success', 'External API created successfully.');
    }

    /**
     * Display the specified external API.
     */
    public function show(ExternalApi $externalApi): View
    {
        // Decrypt sensitive fields for display
        if ($externalApi->api_key) {
            try {
                $externalApi->api_key = Crypt::decryptString($externalApi->api_key);
            } catch (\Exception $e) {
                $externalApi->api_key = '';
            }
        }
        if ($externalApi->api_secret) {
            try {
                $externalApi->api_secret = Crypt::decryptString($externalApi->api_secret);
            } catch (\Exception $e) {
                $externalApi->api_secret = '';
            }
        }

        return view('panel.external-apis.show', compact('externalApi'));
    }

    /**
     * Show the form for editing the specified external API.
     */
    public function edit(ExternalApi $externalApi): View
    {
        // Decrypt sensitive fields for display
        if ($externalApi->api_key) {
            try {
                $externalApi->api_key = Crypt::decryptString($externalApi->api_key);
            } catch (\Exception $e) {
                $externalApi->api_key = '';
            }
        }
        if ($externalApi->api_secret) {
            try {
                $externalApi->api_secret = Crypt::decryptString($externalApi->api_secret);
            } catch (\Exception $e) {
                $externalApi->api_secret = '';
            }
        }

        return view('panel.external-apis.edit', compact('externalApi'));
    }

    /**
     * Update the specified external API.
     */
    public function update(Request $request, ExternalApi $externalApi): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['required', 'string', 'max:255'],
            'service_type' => ['required', 'string', 'max:100'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'api_secret' => ['nullable', 'string', 'max:500'],
            'base_url' => ['nullable', 'url', 'max:500'],
            'endpoint' => ['nullable', 'string', 'max:500'],
            'headers' => ['nullable', 'json'],
            'config' => ['nullable', 'json'],
            'rate_limit' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Encrypt sensitive fields (only if new values provided)
        if (isset($validated['api_key']) && !empty($validated['api_key'])) {
            $validated['api_key'] = Crypt::encryptString($validated['api_key']);
        } elseif (!isset($validated['api_key'])) {
            // Keep existing value if not provided
            unset($validated['api_key']);
        }
        
        if (isset($validated['api_secret']) && !empty($validated['api_secret'])) {
            $validated['api_secret'] = Crypt::encryptString($validated['api_secret']);
        } elseif (!isset($validated['api_secret'])) {
            // Keep existing value if not provided
            unset($validated['api_secret']);
        }

        // Parse JSON fields
        if (isset($validated['headers']) && is_string($validated['headers'])) {
            $validated['headers'] = json_decode($validated['headers'], true);
        }
        if (isset($validated['config']) && is_string($validated['config'])) {
            $validated['config'] = json_decode($validated['config'], true);
        }

        $externalApi->update($validated);

        return redirect()->route('panel.external-apis.index')
            ->with('success', 'External API updated successfully.');
    }

    /**
     * Remove the specified external API.
     */
    public function destroy(ExternalApi $externalApi): RedirectResponse
    {
        $externalApi->delete();

        return redirect()->route('panel.external-apis.index')
            ->with('success', 'External API deleted successfully.');
    }
}
