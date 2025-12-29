<?php

namespace App\Http\Controllers;

use App\Models\SSLMonitor;
use App\Models\SSLMonitorCheck;
use App\Models\SSLMonitorAlert;
use App\Models\MonitorCommunicationPreference;
use App\Jobs\SSLMonitorCheckJob;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class SSLMonitorController extends Controller
{
    /**
     * Display a listing of SSL monitors.
     */
    public function index(): View
    {
        $user = Auth::user();
        
        $monitors = $user->sslMonitors()
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('client.ssl-monitors.index', [
            'monitors' => $monitors,
        ]);
    }

    /**
     * Show the form for creating a new SSL monitor.
     */
    public function create(): View
    {
        return view('client.ssl-monitors.create');
    }

    /**
     * Store a newly created SSL monitor.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => [
                'required', 
                'string', 
                'max:255', 
                'regex:/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i',
                function ($attribute, $value, $fail) use ($user) {
                    $domain = strtolower(trim($value));
                    $exists = SSLMonitor::where('user_id', $user->id)
                        ->where('domain', $domain)
                        ->exists();
                    
                    if ($exists) {
                        $fail('You already have an SSL monitor for this domain. Please edit the existing monitor instead.');
                    }
                },
            ],
            'check_interval' => ['required', 'integer', 'min:1', 'max:1440'], // 1 minute to 24 hours
            'alert_expiring_soon' => ['nullable', 'boolean'],
            'alert_expired' => ['nullable', 'boolean'],
            'alert_invalid' => ['nullable', 'boolean'],
            'communication_channels' => ['required', 'array', 'min:1'],
            'communication_channels.*' => ['in:email,sms,whatsapp,telegram,discord'],
        ], [
            'communication_channels.required' => 'Please select at least one communication channel.',
            'communication_channels.min' => 'Please select at least one communication channel.',
        ]);

        $monitor = SSLMonitor::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'domain' => strtolower(trim($validated['domain'])),
            'check_interval' => $validated['check_interval'],
            'alert_expiring_soon' => $request->has('alert_expiring_soon') ? (bool)$request->input('alert_expiring_soon') : false,
            'alert_expired' => $request->has('alert_expired') ? (bool)$request->input('alert_expired') : false,
            'alert_invalid' => $request->has('alert_invalid') ? (bool)$request->input('alert_invalid') : false,
            'is_active' => true,
            'status' => 'unknown',
        ]);

        // Save communication preferences
        if (isset($validated['communication_channels']) && is_array($validated['communication_channels'])) {
            foreach ($validated['communication_channels'] as $channel) {
                // Determine channel_value based on channel type
                $channelValue = match($channel) {
                    'email' => $user->email,
                    'sms' => $user->phone ?? $user->email,
                    'whatsapp' => $user->phone ?? $user->email,
                    'telegram' => $user->email,
                    'discord' => $user->email,
                    default => $user->email,
                };

                MonitorCommunicationPreference::create([
                    'monitor_id' => $monitor->id,
                    'monitor_type' => 'ssl',
                    'communication_channel' => $channel,
                    'channel_value' => $channelValue,
                    'is_enabled' => true,
                ]);
            }
        }

        // Dispatch check job immediately to get initial SSL status
        if ($monitor->is_active) {
            SSLMonitorCheckJob::dispatch($monitor->id);
        }

        return redirect()->route('ssl-monitors.index')
            ->with('success', 'SSL monitor created successfully.');
    }

    /**
     * Display the specified SSL monitor.
     */
    public function show(SSLMonitor $sslMonitor): View
    {
        // Ensure monitor belongs to authenticated user
        if ($sslMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $sslMonitor->load([
            'checks' => function($query) {
                $query->orderBy('checked_at', 'desc')->limit(50);
            },
            'alerts' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(50);
            },
        ]);

        // Get communication preferences
        $communicationPreferences = MonitorCommunicationPreference::where('monitor_id', $sslMonitor->id)
            ->where('monitor_type', 'ssl')
            ->get();

        return view('client.ssl-monitors.show', [
            'monitor' => $sslMonitor,
            'communicationPreferences' => $communicationPreferences,
        ]);
    }

    /**
     * Show the form for editing the specified SSL monitor.
     */
    public function edit(SSLMonitor $sslMonitor): View
    {
        // Ensure monitor belongs to authenticated user
        if ($sslMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        // Get communication preferences
        $communicationPreferences = MonitorCommunicationPreference::where('monitor_id', $sslMonitor->id)
            ->where('monitor_type', 'ssl')
            ->pluck('communication_channel')
            ->toArray();

        return view('client.ssl-monitors.edit', [
            'monitor' => $sslMonitor,
            'communicationPreferences' => $communicationPreferences,
        ]);
    }

    /**
     * Update the specified SSL monitor.
     */
    public function update(Request $request, SSLMonitor $sslMonitor): RedirectResponse
    {
        // Ensure monitor belongs to authenticated user
        if ($sslMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => [
                'required', 
                'string', 
                'max:255', 
                'regex:/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i',
                function ($attribute, $value, $fail) use ($user, $sslMonitor) {
                    $domain = strtolower(trim($value));
                    $exists = SSLMonitor::where('user_id', $user->id)
                        ->where('domain', $domain)
                        ->where('id', '!=', $sslMonitor->id)
                        ->exists();
                    
                    if ($exists) {
                        $fail('You already have an SSL monitor for this domain. Please use a different domain or edit the existing monitor.');
                    }
                },
            ],
            'check_interval' => ['required', 'integer', 'min:1', 'max:1440'],
            'alert_expiring_soon' => ['nullable', 'boolean'],
            'alert_expired' => ['nullable', 'boolean'],
            'alert_invalid' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'communication_channels' => ['required', 'array', 'min:1'],
            'communication_channels.*' => ['in:email,sms,whatsapp,telegram,discord'],
        ], [
            'communication_channels.required' => 'Please select at least one communication channel.',
            'communication_channels.min' => 'Please select at least one communication channel.',
        ]);

        $sslMonitor->update([
            'name' => $validated['name'],
            'domain' => strtolower(trim($validated['domain'])),
            'check_interval' => $validated['check_interval'],
            'alert_expiring_soon' => $request->has('alert_expiring_soon') ? (bool)$request->input('alert_expiring_soon') : false,
            'alert_expired' => $request->has('alert_expired') ? (bool)$request->input('alert_expired') : false,
            'alert_invalid' => $request->has('alert_invalid') ? (bool)$request->input('alert_invalid') : false,
            'is_active' => $request->has('is_active') ? (bool)$request->input('is_active') : false,
        ]);

        // Update communication preferences
        MonitorCommunicationPreference::where('monitor_id', $sslMonitor->id)
            ->where('monitor_type', 'ssl')
            ->delete();

        if (isset($validated['communication_channels']) && is_array($validated['communication_channels'])) {
            $user = Auth::user();
            foreach ($validated['communication_channels'] as $channel) {
                // Determine channel_value based on channel type
                $channelValue = match($channel) {
                    'email' => $user->email,
                    'sms' => $user->phone ?? $user->email,
                    'whatsapp' => $user->phone ?? $user->email,
                    'telegram' => $user->email,
                    'discord' => $user->email,
                    default => $user->email,
                };

                MonitorCommunicationPreference::create([
                    'monitor_id' => $sslMonitor->id,
                    'monitor_type' => 'ssl',
                    'communication_channel' => $channel,
                    'channel_value' => $channelValue,
                    'is_enabled' => true,
                ]);
            }
        }

        return redirect()->route('ssl-monitors.show', $sslMonitor->uid)
            ->with('success', 'SSL monitor updated successfully.');
    }

    /**
     * Remove the specified SSL monitor.
     */
    public function destroy(SSLMonitor $sslMonitor): RedirectResponse
    {
        // Ensure monitor belongs to authenticated user
        if ($sslMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $sslMonitor->delete();

        return redirect()->route('ssl-monitors.index')
            ->with('success', 'SSL monitor deleted successfully.');
    }

    /**
     * Recheck SSL certificate immediately.
     */
    public function recheck(SSLMonitor $sslMonitor): RedirectResponse
    {
        // Ensure monitor belongs to authenticated user
        if ($sslMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        // Dispatch check job immediately
        SSLMonitorCheckJob::dispatch($sslMonitor->id);

        return redirect()->route('ssl-monitors.show', $sslMonitor->uid)
            ->with('success', 'SSL certificate check has been queued. Results will be updated shortly.');
    }
}
