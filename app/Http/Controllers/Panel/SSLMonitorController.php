<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\SSLMonitor;
use App\Models\MonitorCommunicationPreference;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SSLMonitorController extends Controller
{
    public function index(Request $request): View
    {
        // Start building query
        $query = SSLMonitor::with('user');
        
        // Apply filters
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }
        
        if ($request->filled('domain')) {
            $query->where('domain', 'like', '%' . $request->input('domain') . '%');
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->filled('expiration_from')) {
            $query->whereDate('expiration_date', '>=', $request->input('expiration_from'));
        }
        
        if ($request->filled('expiration_to')) {
            $query->whereDate('expiration_date', '<=', $request->input('expiration_to'));
        }
        
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->input('created_from'));
        }
        
        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->input('created_to'));
        }
        
        // Get filtered monitors
        $monitors = $query->orderBy('created_at', 'desc')->get();
        
        return view('panel.ssl-monitors.index', [
            'monitors' => $monitors,
            'filters' => $request->only(['name', 'domain', 'status', 'expiration_from', 'expiration_to', 'created_from', 'created_to']),
        ]);
    }

    public function create(): View
    {
        $users = \App\Models\User::where('role', 2)->orderBy('name')->get();
        return view('panel.ssl-monitors.create', ['users' => $users]);
    }

    public function store(Request $request): RedirectResponse
    {
        // TODO: Implement store method
        return redirect()->route('panel.ssl-monitors.index')
            ->with('success', 'SSL monitor created successfully.');
    }

    public function show(SSLMonitor $sslMonitor): View
    {
        $sslMonitor->load([
            'user',
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

        return view('panel.ssl-monitors.show', [
            'monitor' => $sslMonitor,
            'communicationPreferences' => $communicationPreferences,
        ]);
    }

    public function edit(SSLMonitor $sslMonitor): View
    {
        $users = \App\Models\User::where('role', 2)->orderBy('name')->get();
        
        // Get communication preferences
        $communicationPreferences = MonitorCommunicationPreference::where('monitor_id', $sslMonitor->id)
            ->where('monitor_type', 'ssl')
            ->pluck('communication_channel')
            ->toArray();
        
        return view('panel.ssl-monitors.edit', [
            'monitor' => $sslMonitor->load('user'),
            'users' => $users,
            'communicationPreferences' => $communicationPreferences,
        ]);
    }

    public function update(Request $request, SSLMonitor $sslMonitor): RedirectResponse
    {
        // User ID remains locked to original owner - cannot be changed on update
        $userId = $sslMonitor->user_id;
        $user = \App\Models\User::findOrFail($userId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => [
                'required', 
                'string', 
                'max:255', 
                'regex:/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i',
                function ($attribute, $value, $fail) use ($userId, $sslMonitor) {
                    $domain = strtolower(trim($value));
                    $exists = SSLMonitor::where('user_id', $userId)
                        ->where('domain', $domain)
                        ->where('id', '!=', $sslMonitor->id)
                        ->exists();
                    
                    if ($exists) {
                        $fail('This user already has an SSL monitor for this domain. Please use a different domain or edit the existing monitor.');
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

        // Update monitor (user_id remains unchanged - locked to original owner)
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

        return redirect()->route('panel.ssl-monitors.show', $sslMonitor->uid)
            ->with('success', 'SSL monitor updated successfully.');
    }

    public function destroy(SSLMonitor $sslMonitor): RedirectResponse
    {
        $sslMonitor->delete();
        return redirect()->route('panel.ssl-monitors.index')
            ->with('success', 'SSL monitor deleted successfully.');
    }

    public function recheck(SSLMonitor $sslMonitor): RedirectResponse
    {
        // TODO: Implement recheck method
        return redirect()->route('panel.ssl-monitors.show', $sslMonitor->uid)
            ->with('success', 'SSL check has been queued.');
    }
}
