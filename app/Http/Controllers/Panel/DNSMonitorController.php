<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\DNSMonitor;
use App\Models\MonitorCommunicationPreference;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class DNSMonitorController extends Controller
{
    public function index(Request $request): View
    {
        // Start building query
        $query = DNSMonitor::with('user');
        
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
        
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->input('created_from'));
        }
        
        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->input('created_to'));
        }
        
        // Get filtered monitors
        $monitors = $query->orderBy('created_at', 'desc')->get();
        
        return view('panel.dns-monitors.index', [
            'monitors' => $monitors,
            'filters' => $request->only(['name', 'domain', 'status', 'created_from', 'created_to']),
        ]);
    }

    public function create(): View
    {
        $users = \App\Models\User::where('role', 2)->orderBy('name')->get();
        return view('panel.dns-monitors.create', ['users' => $users]);
    }

    public function store(Request $request): RedirectResponse
    {
        // TODO: Implement store method
        return redirect()->route('panel.dns-monitors.index')
            ->with('success', 'DNS monitor created successfully.');
    }

    public function show(DNSMonitor $dnsMonitor): View
    {
        $dnsMonitor->load([
            'user',
            'checks' => function($query) {
                $query->orderBy('checked_at', 'desc')->limit(50);
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

        return view('panel.dns-monitors.show', [
            'monitor' => $dnsMonitor,
            'communicationPreferences' => $communicationPreferences,
            'checksByType' => $checksByType,
        ]);
    }

    public function edit(DNSMonitor $dnsMonitor): View
    {
        $users = \App\Models\User::where('role', 2)->orderBy('name')->get();
        
        // Get communication preferences
        $communicationPreferences = MonitorCommunicationPreference::where('monitor_id', $dnsMonitor->id)
            ->where('monitor_type', 'dns')
            ->pluck('communication_channel')
            ->toArray();
        
        return view('panel.dns-monitors.edit', [
            'monitor' => $dnsMonitor->load('user'),
            'users' => $users,
            'communicationPreferences' => $communicationPreferences,
        ]);
    }

    public function update(Request $request, DNSMonitor $dnsMonitor): RedirectResponse
    {
        // TODO: Implement update method
        return redirect()->route('panel.dns-monitors.show', $dnsMonitor->uid)
            ->with('success', 'DNS monitor updated successfully.');
    }

    public function destroy(DNSMonitor $dnsMonitor): RedirectResponse
    {
        $dnsMonitor->delete();
        return redirect()->route('panel.dns-monitors.index')
            ->with('success', 'DNS monitor deleted successfully.');
    }

    public function recheck(DNSMonitor $dnsMonitor): RedirectResponse
    {
        // TODO: Implement recheck method
        return redirect()->route('panel.dns-monitors.show', $dnsMonitor->uid)
            ->with('success', 'DNS check has been queued.');
    }
}
