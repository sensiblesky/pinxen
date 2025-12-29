<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\DomainMonitor;
use App\Models\DomainMonitorAlert;
use App\Models\MonitorCommunicationPreference;
use App\Models\SSLMonitor;
use App\Jobs\DomainExpirationCheckJob;
use App\Jobs\SSLMonitorCheckJob;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class DomainMonitorController extends Controller
{
    /**
     * Display a listing of all domain monitors (admin view - shows all users' monitors).
     */
    public function index(Request $request): View
    {
        // Start building query
        $query = DomainMonitor::with('user');
        
        // Apply filters
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }
        
        if ($request->filled('domain')) {
            $query->where('domain', 'like', '%' . $request->input('domain') . '%');
        }
        
        if ($request->filled('is_active')) {
            $isActive = $request->input('is_active');
            if ($isActive === '1') {
                $query->where('is_active', true);
            } elseif ($isActive === '0') {
                $query->where('is_active', false);
            }
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
        
        return view('panel.domain-monitors.index', [
            'monitors' => $monitors,
            'filters' => $request->only(['name', 'domain', 'is_active', 'expiration_from', 'expiration_to', 'created_from', 'created_to']),
        ]);
    }

    /**
     * Show the form for creating a new domain monitor.
     */
    public function create(): View
    {
        $users = \App\Models\User::where('role', 2)->orderBy('name')->get();
        
        return view('panel.domain-monitors.create', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created domain monitor.
     */
    public function store(Request $request): RedirectResponse
    {
        $userId = $request->input('user_id', auth()->id());
        $user = \App\Models\User::findOrFail($userId);
        
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'domain' => [
                'required', 
                'string', 
                'max:255', 
                'regex:/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i',
                function ($attribute, $value, $fail) use ($userId) {
                    $domain = strtolower(trim($value));
                    $exists = DomainMonitor::where('user_id', $userId)
                        ->where('domain', $domain)
                        ->exists();
                    
                    if ($exists) {
                        $fail('This user already has a domain monitor for this domain. Please edit the existing monitor instead.');
                    }
                },
            ],
            'alert_30_days' => ['nullable', 'boolean'],
            'alert_5_days' => ['nullable', 'boolean'],
            'alert_daily_under_30' => ['nullable', 'boolean'],
            'communication_channels' => ['required', 'array', 'min:1'],
            'communication_channels.*' => ['in:email,sms,whatsapp,telegram,discord'],
            'create_ssl_monitor' => ['nullable', 'boolean'],
            'ssl_check_interval' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'ssl_alert_expiring_soon' => ['nullable', 'boolean'],
            'ssl_alert_expired' => ['nullable', 'boolean'],
            'ssl_alert_invalid' => ['nullable', 'boolean'],
            'ssl_communication_channels' => ['nullable', 'array'],
            'ssl_communication_channels.*' => ['in:email,sms,whatsapp,telegram,discord'],
        ], [
            'communication_channels.required' => 'Please select at least one communication channel.',
            'communication_channels.min' => 'Please select at least one communication channel.',
        ]);

        $monitor = DomainMonitor::create([
            'user_id' => $userId,
            'name' => $validated['name'],
            'domain' => strtolower(trim($validated['domain'])),
            'alert_30_days' => $request->has('alert_30_days') && $request->input('alert_30_days') == '1',
            'alert_5_days' => $request->has('alert_5_days') && $request->input('alert_5_days') == '1',
            'alert_daily_under_30' => $request->has('alert_daily_under_30') && $request->input('alert_daily_under_30') == '1',
            'is_active' => true,
        ]);

        // Save communication preferences
        foreach ($validated['communication_channels'] as $channel) {
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
                'monitor_type' => 'domain',
                'communication_channel' => $channel,
                'channel_value' => $channelValue,
                'is_enabled' => true,
            ]);
        }

        // Create SSL monitor if requested
        $sslMonitorCreated = false;
        if ($request->has('create_ssl_monitor') && $request->input('create_ssl_monitor') == '1') {
            $domain = strtolower(trim($validated['domain']));
            
            $existingSslMonitor = SSLMonitor::where('user_id', $userId)
                ->where('domain', $domain)
                ->first();
            
            if (!$existingSslMonitor) {
                $sslChannels = $validated['ssl_communication_channels'] ?? ['email'];
                
                $sslMonitor = SSLMonitor::create([
                    'user_id' => $userId,
                    'name' => $validated['name'] . ' (SSL)',
                    'domain' => $domain,
                    'check_interval' => $validated['ssl_check_interval'] ?? 60,
                    'alert_expiring_soon' => $request->has('ssl_alert_expiring_soon') && $request->input('ssl_alert_expiring_soon') == '1',
                    'alert_expired' => $request->has('ssl_alert_expired') && $request->input('ssl_alert_expired') == '1',
                    'alert_invalid' => $request->has('ssl_alert_invalid') && $request->input('ssl_alert_invalid') == '1',
                    'is_active' => true,
                    'status' => 'unknown',
                ]);
                
                foreach ($sslChannels as $channel) {
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
                
                if ($sslMonitor->is_active) {
                    SSLMonitorCheckJob::dispatch($sslMonitor->id);
                }
                
                $sslMonitorCreated = true;
            }
        }

        // Dispatch check job immediately
        DomainExpirationCheckJob::dispatch($monitor->id);

        $successMessage = 'Domain monitor created successfully.';
        if ($sslMonitorCreated) {
            $successMessage .= ' SSL monitor has also been created for this domain.';
        }

        return redirect()->route('panel.domain-monitors.index')
            ->with('success', $successMessage);
    }

    /**
     * Display the specified domain monitor (admin can view any monitor).
     */
    public function show(DomainMonitor $domainMonitor): View
    {
        $domainMonitor->load([
            'user',
            'alerts' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(50);
            },
        ]);

        $communicationPreferences = MonitorCommunicationPreference::where('monitor_id', $domainMonitor->id)
            ->where('monitor_type', 'domain')
            ->get();

        return view('panel.domain-monitors.show', [
            'monitor' => $domainMonitor,
            'communicationPreferences' => $communicationPreferences,
        ]);
    }

    /**
     * Show the form for editing the specified domain monitor.
     */
    public function edit(DomainMonitor $domainMonitor): View
    {
        $users = \App\Models\User::where('role', 2)->orderBy('name')->get();
        
        $communicationPreferences = MonitorCommunicationPreference::where('monitor_id', $domainMonitor->id)
            ->where('monitor_type', 'domain')
            ->pluck('communication_channel')
            ->toArray();

        return view('panel.domain-monitors.edit', [
            'monitor' => $domainMonitor->load('user'),
            'users' => $users,
            'communicationPreferences' => $communicationPreferences,
        ]);
    }

    /**
     * Update the specified domain monitor.
     */
    public function update(Request $request, DomainMonitor $domainMonitor): RedirectResponse
    {
        // User ID remains locked to original owner - cannot be changed on update
        $userId = $domainMonitor->user_id;
        $user = \App\Models\User::findOrFail($userId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => [
                'required', 
                'string', 
                'max:255', 
                'regex:/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i',
                function ($attribute, $value, $fail) use ($userId, $domainMonitor) {
                    $domain = strtolower(trim($value));
                    $exists = DomainMonitor::where('user_id', $userId)
                        ->where('domain', $domain)
                        ->where('id', '!=', $domainMonitor->id)
                        ->exists();
                    
                    if ($exists) {
                        $fail('This user already has a domain monitor for this domain. Please use a different domain or edit the existing monitor.');
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

        // Update monitor (user_id remains unchanged - locked to original owner)
        $domainMonitor->update([
            'name' => $validated['name'],
            'domain' => strtolower(trim($validated['domain'])),
            'alert_30_days' => $request->has('alert_30_days') && $request->input('alert_30_days') == '1',
            'alert_5_days' => $request->has('alert_5_days') && $request->input('alert_5_days') == '1',
            'alert_daily_under_30' => $request->has('alert_daily_under_30') && $request->input('alert_daily_under_30') == '1',
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Update communication preferences
        MonitorCommunicationPreference::where('monitor_id', $domainMonitor->id)
            ->where('monitor_type', 'domain')
            ->delete();

        foreach ($validated['communication_channels'] as $channel) {
            $channelValue = match($channel) {
                'email' => $user->email,
                'sms' => $user->phone ?? $user->email,
                'whatsapp' => $user->phone ?? $user->email,
                'telegram' => $user->email,
                'discord' => $user->email,
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

        return redirect()->route('panel.domain-monitors.show', $domainMonitor->uid)
            ->with('success', 'Domain monitor updated successfully.');
    }

    /**
     * Remove the specified domain monitor.
     */
    public function destroy(DomainMonitor $domainMonitor): RedirectResponse
    {
        $domainMonitor->delete();

        return redirect()->route('panel.domain-monitors.index')
            ->with('success', 'Domain monitor deleted successfully.');
    }

    /**
     * Recheck domain expiration immediately.
     */
    public function recheck(DomainMonitor $domainMonitor): RedirectResponse
    {
        DomainExpirationCheckJob::dispatch($domainMonitor->id);

        return redirect()->route('panel.domain-monitors.show', $domainMonitor->uid)
            ->with('success', 'Domain expiration check has been queued. Results will be updated shortly.');
    }
}
