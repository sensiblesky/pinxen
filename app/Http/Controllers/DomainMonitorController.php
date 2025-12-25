<?php

namespace App\Http\Controllers;

use App\Models\DomainMonitor;
use App\Models\DomainMonitorAlert;
use App\Models\MonitorCommunicationPreference;
use App\Jobs\DomainExpirationCheckJob;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DomainMonitorController extends Controller
{
    /**
     * Display a listing of domain monitors.
     */
    public function index(): View
    {
        $user = Auth::user();
        
        $monitors = $user->domainMonitors()
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('client.domain-monitors.index', [
            'monitors' => $monitors,
        ]);
    }

    /**
     * Show the form for creating a new domain monitor.
     */
    public function create(): View
    {
        return view('client.domain-monitors.create');
    }

    /**
     * Store a newly created domain monitor.
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
                    $exists = DomainMonitor::where('user_id', $user->id)
                        ->where('domain', $domain)
                        ->exists();
                    
                    if ($exists) {
                        $fail('You already have a domain monitor for this domain. Please edit the existing monitor instead.');
                    }
                },
            ],
            'alert_30_days' => ['nullable', 'boolean'],
            'alert_5_days' => ['nullable', 'boolean'],
            'alert_daily_under_30' => ['nullable', 'boolean'],
            'communication_channels' => ['required', 'array', 'min:1'],
            'communication_channels.*' => ['in:email,sms,whatsapp,telegram,discord'],
        ], [
            'communication_channels.required' => 'Please select at least one communication channel.',
            'communication_channels.min' => 'Please select at least one communication channel.',
        ]);

        $monitor = DomainMonitor::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'domain' => strtolower(trim($validated['domain'])),
            'alert_30_days' => $request->has('alert_30_days') ? (bool)$request->input('alert_30_days') : false,
            'alert_5_days' => $request->has('alert_5_days') ? (bool)$request->input('alert_5_days') : false,
            'alert_daily_under_30' => $request->has('alert_daily_under_30') ? (bool)$request->input('alert_daily_under_30') : false,
            'is_active' => true,
        ]);

        // Save communication preferences
        if (isset($validated['communication_channels']) && is_array($validated['communication_channels'])) {
            foreach ($validated['communication_channels'] as $channel) {
                // Determine channel_value based on channel type
                $channelValue = match($channel) {
                    'email' => $user->email,
                    'sms' => $user->phone ?? $user->email, // Fallback to email if phone not set
                    'whatsapp' => $user->phone ?? $user->email,
                    'telegram' => $user->email, // Will need to be configured later
                    'discord' => $user->email, // Will need to be configured later
                    default => $user->email,
                };

                MonitorCommunicationPreference::create([
                    'monitor_id' => $monitor->id,
                    'monitor_type' => 'domain',
                    'communication_channel' => $channel,
                    'channel_value' => $channelValue,
                    'is_enabled' => true,
                ]);
            }
        }

        // Dispatch check job immediately to get initial expiration date
        if ($monitor->is_active) {
            DomainExpirationCheckJob::dispatch($monitor->id);
        }

        return redirect()->route('domain-monitors.index')
            ->with('success', 'Domain monitor created successfully.');
    }

    /**
     * Display the specified domain monitor.
     */
    public function show(DomainMonitor $domainMonitor): View
    {
        // Ensure monitor belongs to authenticated user
        if ($domainMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $domainMonitor->load([
            'alerts' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(50);
            },
        ]);

        // Get communication preferences
        $communicationPreferences = MonitorCommunicationPreference::where('monitor_id', $domainMonitor->id)
            ->where('monitor_type', 'domain')
            ->get();

        return view('client.domain-monitors.show', [
            'monitor' => $domainMonitor,
            'communicationPreferences' => $communicationPreferences,
        ]);
    }

    /**
     * Show the form for editing the specified domain monitor.
     */
    public function edit(DomainMonitor $domainMonitor): View
    {
        // Ensure monitor belongs to authenticated user
        if ($domainMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        // Get communication preferences
        $communicationPreferences = MonitorCommunicationPreference::where('monitor_id', $domainMonitor->id)
            ->where('monitor_type', 'domain')
            ->pluck('communication_channel')
            ->toArray();

        return view('client.domain-monitors.edit', [
            'monitor' => $domainMonitor,
            'communicationPreferences' => $communicationPreferences,
        ]);
    }

    /**
     * Update the specified domain monitor.
     */
    public function update(Request $request, DomainMonitor $domainMonitor): RedirectResponse
    {
        // Ensure monitor belongs to authenticated user
        if ($domainMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => [
                'required', 
                'string', 
                'max:255', 
                'regex:/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i',
                function ($attribute, $value, $fail) use ($user, $domainMonitor) {
                    $domain = strtolower(trim($value));
                    $exists = DomainMonitor::where('user_id', $user->id)
                        ->where('domain', $domain)
                        ->where('id', '!=', $domainMonitor->id)
                        ->exists();
                    
                    if ($exists) {
                        $fail('You already have a domain monitor for this domain. Please use a different domain or edit the existing monitor.');
                    }
                },
            ],
            'alert_30_days' => ['nullable', 'boolean'],
            'alert_5_days' => ['nullable', 'boolean'],
            'alert_daily_under_30' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'communication_channels' => ['required', 'array', 'min:1'],
            'communication_channels.*' => ['in:email,sms,whatsapp,telegram,discord'],
        ], [
            'communication_channels.required' => 'Please select at least one communication channel.',
            'communication_channels.min' => 'Please select at least one communication channel.',
        ]);

        $domainMonitor->update([
            'name' => $validated['name'],
            'domain' => strtolower(trim($validated['domain'])),
            'alert_30_days' => $request->has('alert_30_days') ? (bool)$request->input('alert_30_days') : false,
            'alert_5_days' => $request->has('alert_5_days') ? (bool)$request->input('alert_5_days') : false,
            'alert_daily_under_30' => $request->has('alert_daily_under_30') ? (bool)$request->input('alert_daily_under_30') : false,
            'is_active' => $request->has('is_active') ? (bool)$request->input('is_active') : false,
        ]);

        // Update communication preferences
        MonitorCommunicationPreference::where('monitor_id', $domainMonitor->id)
            ->where('monitor_type', 'domain')
            ->delete();

        if (isset($validated['communication_channels']) && is_array($validated['communication_channels'])) {
            $user = Auth::user();
            foreach ($validated['communication_channels'] as $channel) {
                // Determine channel_value based on channel type
                $channelValue = match($channel) {
                    'email' => $user->email,
                    'sms' => $user->phone ?? $user->email, // Fallback to email if phone not set
                    'whatsapp' => $user->phone ?? $user->email,
                    'telegram' => $user->email, // Will need to be configured later
                    'discord' => $user->email, // Will need to be configured later
                    default => $user->email,
                };

                MonitorCommunicationPreference::create([
                    'monitor_id' => $domainMonitor->id,
                    'monitor_type' => 'domain',
                    'communication_channel' => $channel,
                    'channel_value' => $channelValue,
                    'is_enabled' => true,
                ]);
            }
        }

        return redirect()->route('domain-monitors.show', $domainMonitor->uid)
            ->with('success', 'Domain monitor updated successfully.');
    }

    /**
     * Remove the specified domain monitor.
     */
    public function destroy(DomainMonitor $domainMonitor): RedirectResponse
    {
        // Ensure monitor belongs to authenticated user
        if ($domainMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $domainMonitor->delete();

        return redirect()->route('domain-monitors.index')
            ->with('success', 'Domain monitor deleted successfully.');
    }

    /**
     * Recheck domain expiration immediately.
     */
    public function recheck(DomainMonitor $domainMonitor): RedirectResponse
    {
        // Ensure monitor belongs to authenticated user
        if ($domainMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        // Dispatch check job immediately
        DomainExpirationCheckJob::dispatch($domainMonitor->id);

        return redirect()->route('domain-monitors.show', $domainMonitor->uid)
            ->with('success', 'Domain expiration check has been queued. Results will be updated shortly.');
    }
}
