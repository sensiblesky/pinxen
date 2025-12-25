<?php

namespace App\Http\Controllers;

use App\Models\DNSMonitor;
use App\Models\DNSMonitorCheck;
use App\Models\DNSMonitorAlert;
use App\Models\MonitorCommunicationPreference;
use App\Jobs\DNSMonitorCheckJob;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DNSMonitorController extends Controller
{
    /**
     * Display a listing of DNS monitors.
     */
    public function index(): View
    {
        $user = Auth::user();
        
        $monitors = $user->dnsMonitors()
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('client.dns-monitors.index', [
            'monitors' => $monitors,
        ]);
    }

    /**
     * Show the form for creating a new DNS monitor.
     */
    public function create(): View
    {
        return view('client.dns-monitors.create');
    }

    /**
     * Store a newly created DNS monitor.
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
                    $exists = DNSMonitor::where('user_id', $user->id)
                        ->where('domain', $domain)
                        ->exists();
                    
                    if ($exists) {
                        $fail('You already have a DNS monitor for this domain. Please edit the existing monitor instead.');
                    }
                },
            ],
            'record_types' => ['required', 'array', 'min:1'],
            'record_types.*' => ['in:A,AAAA,CNAME,MX,NS,TXT,SOA'],
            'check_interval' => ['required', 'integer', 'min:1', 'max:1440'], // 1 minute to 24 hours
            'alert_on_change' => ['nullable', 'boolean'],
            'alert_on_missing' => ['nullable', 'boolean'],
            'communication_channels' => ['required', 'array', 'min:1'],
            'communication_channels.*' => ['in:email,sms,whatsapp,telegram,discord'],
        ], [
            'communication_channels.required' => 'Please select at least one communication channel.',
            'communication_channels.min' => 'Please select at least one communication channel.',
            'record_types.required' => 'Please select at least one DNS record type to monitor.',
            'record_types.min' => 'Please select at least one DNS record type to monitor.',
        ]);

        $monitor = DNSMonitor::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'domain' => strtolower(trim($validated['domain'])),
            'record_types' => $validated['record_types'],
            'check_interval' => $validated['check_interval'],
            'alert_on_change' => $request->has('alert_on_change') ? (bool)$request->input('alert_on_change') : false,
            'alert_on_missing' => $request->has('alert_on_missing') ? (bool)$request->input('alert_on_missing') : false,
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
                    'monitor_type' => 'dns',
                    'communication_channel' => $channel,
                    'channel_value' => $channelValue,
                    'is_enabled' => true,
                ]);
            }
        }

        // Dispatch check job immediately to get initial DNS records
        if ($monitor->is_active) {
            DNSMonitorCheckJob::dispatch($monitor->id);
        }

        return redirect()->route('dns-monitors.index')
            ->with('success', 'DNS monitor created successfully.');
    }

    /**
     * Display the specified DNS monitor.
     */
    public function show(DNSMonitor $dnsMonitor): View
    {
        // Ensure monitor belongs to authenticated user
        if ($dnsMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $dnsMonitor->load([
            'checks' => function($query) {
                $query->orderBy('checked_at', 'desc')->limit(100);
            },
            'alerts' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(50);
            },
        ]);

        // Get communication preferences
        $communicationPreferences = MonitorCommunicationPreference::where('monitor_id', $dnsMonitor->id)
            ->where('monitor_type', 'dns')
            ->get();

        // Group checks by record type for display
        $checksByType = $dnsMonitor->checks->groupBy('record_type');

        return view('client.dns-monitors.show', [
            'monitor' => $dnsMonitor,
            'communicationPreferences' => $communicationPreferences,
            'checksByType' => $checksByType,
        ]);
    }

    /**
     * Show the form for editing the specified DNS monitor.
     */
    public function edit(DNSMonitor $dnsMonitor): View
    {
        // Ensure monitor belongs to authenticated user
        if ($dnsMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        // Get communication preferences
        $communicationPreferences = MonitorCommunicationPreference::where('monitor_id', $dnsMonitor->id)
            ->where('monitor_type', 'dns')
            ->pluck('communication_channel')
            ->toArray();

        return view('client.dns-monitors.edit', [
            'monitor' => $dnsMonitor,
            'communicationPreferences' => $communicationPreferences,
        ]);
    }

    /**
     * Update the specified DNS monitor.
     */
    public function update(Request $request, DNSMonitor $dnsMonitor): RedirectResponse
    {
        // Ensure monitor belongs to authenticated user
        if ($dnsMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => [
                'required', 
                'string', 
                'max:255', 
                'regex:/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i',
                function ($attribute, $value, $fail) use ($user, $dnsMonitor) {
                    $domain = strtolower(trim($value));
                    $exists = DNSMonitor::where('user_id', $user->id)
                        ->where('domain', $domain)
                        ->where('id', '!=', $dnsMonitor->id)
                        ->exists();
                    
                    if ($exists) {
                        $fail('You already have a DNS monitor for this domain. Please use a different domain or edit the existing monitor.');
                    }
                },
            ],
            'record_types' => ['required', 'array', 'min:1'],
            'record_types.*' => ['in:A,AAAA,CNAME,MX,NS,TXT,SOA'],
            'check_interval' => ['required', 'integer', 'min:1', 'max:1440'],
            'alert_on_change' => ['nullable', 'boolean'],
            'alert_on_missing' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'communication_channels' => ['required', 'array', 'min:1'],
            'communication_channels.*' => ['in:email,sms,whatsapp,telegram,discord'],
        ], [
            'communication_channels.required' => 'Please select at least one communication channel.',
            'communication_channels.min' => 'Please select at least one communication channel.',
            'record_types.required' => 'Please select at least one DNS record type to monitor.',
            'record_types.min' => 'Please select at least one DNS record type to monitor.',
        ]);

        $dnsMonitor->update([
            'name' => $validated['name'],
            'domain' => strtolower(trim($validated['domain'])),
            'record_types' => $validated['record_types'],
            'check_interval' => $validated['check_interval'],
            'alert_on_change' => $request->has('alert_on_change') ? (bool)$request->input('alert_on_change') : false,
            'alert_on_missing' => $request->has('alert_on_missing') ? (bool)$request->input('alert_on_missing') : false,
            'is_active' => $request->has('is_active') ? (bool)$request->input('is_active') : false,
        ]);

        // Update communication preferences
        MonitorCommunicationPreference::where('monitor_id', $dnsMonitor->id)
            ->where('monitor_type', 'dns')
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
                    'monitor_id' => $dnsMonitor->id,
                    'monitor_type' => 'dns',
                    'communication_channel' => $channel,
                    'channel_value' => $channelValue,
                    'is_enabled' => true,
                ]);
            }
        }

        return redirect()->route('dns-monitors.show', $dnsMonitor->uid)
            ->with('success', 'DNS monitor updated successfully.');
    }

    /**
     * Remove the specified DNS monitor.
     */
    public function destroy(DNSMonitor $dnsMonitor): RedirectResponse
    {
        // Ensure monitor belongs to authenticated user
        if ($dnsMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $dnsMonitor->delete();

        return redirect()->route('dns-monitors.index')
            ->with('success', 'DNS monitor deleted successfully.');
    }

    /**
     * Recheck DNS records immediately.
     */
    public function recheck(DNSMonitor $dnsMonitor): RedirectResponse
    {
        // Ensure monitor belongs to authenticated user
        if ($dnsMonitor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        // Dispatch check job immediately
        DNSMonitorCheckJob::dispatch($dnsMonitor->id);

        return redirect()->route('dns-monitors.show', $dnsMonitor->uid)
            ->with('success', 'DNS check has been queued. Results will be updated shortly.');
    }
}
