<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ApiMonitor;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ApiMonitorController extends Controller
{
    public function index(Request $request): View
    {
        // Start building query
        $query = ApiMonitor::with('user');
        
        // Apply filters
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }
        
        if ($request->filled('url')) {
            $query->where('url', 'like', '%' . $request->input('url') . '%');
        }
        
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'up') {
                $query->where('status', 'up');
            } elseif ($status === 'down') {
                $query->where('status', 'down');
            }
        }
        
        if ($request->filled('request_method')) {
            $query->where('request_method', $request->input('request_method'));
        }
        
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->input('created_from'));
        }
        
        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->input('created_to'));
        }
        
        // Get filtered monitors
        $monitors = $query->orderBy('created_at', 'desc')->get();
        
        return view('panel.api-monitors.index', [
            'monitors' => $monitors,
            'filters' => $request->only(['name', 'url', 'status', 'request_method', 'created_from', 'created_to']),
        ]);
    }

    public function create(): View
    {
        $users = \App\Models\User::where('role', 2)->orderBy('name')->get();
        return view('panel.api-monitors.create', ['users' => $users]);
    }

    public function store(Request $request): RedirectResponse
    {
        // TODO: Implement store method
        return redirect()->route('panel.api-monitors.index')
            ->with('success', 'API monitor created successfully.');
    }

    public function show(ApiMonitor $apiMonitor): View
    {
        $apiMonitor->load('user');
        return view('panel.api-monitors.show', ['monitor' => $apiMonitor]);
    }

    public function edit(ApiMonitor $apiMonitor): View
    {
        $users = \App\Models\User::where('role', 2)->orderBy('name')->get();
        return view('panel.api-monitors.edit', [
            'monitor' => $apiMonitor->load('user'),
            'users' => $users,
        ]);
    }

    public function update(Request $request, ApiMonitor $apiMonitor): RedirectResponse
    {
        // TODO: Implement update method
        return redirect()->route('panel.api-monitors.show', $apiMonitor->uid)
            ->with('success', 'API monitor updated successfully.');
    }

    public function destroy(ApiMonitor $apiMonitor): RedirectResponse
    {
        $apiMonitor->delete();
        return redirect()->route('panel.api-monitors.index')
            ->with('success', 'API monitor deleted successfully.');
    }

    public function getChecksData(Request $request, ApiMonitor $apiMonitor): \Illuminate\Http\JsonResponse
    {
        // TODO: Implement getChecksData method
        return response()->json(['data' => []]);
    }

    public function getAlertsData(Request $request, ApiMonitor $apiMonitor): \Illuminate\Http\JsonResponse
    {
        // TODO: Implement getAlertsData method
        return response()->json(['data' => []]);
    }

    public function testNow(ApiMonitor $apiMonitor): RedirectResponse
    {
        // TODO: Implement testNow method
        return redirect()->back()->with('success', 'Test completed.');
    }

    public function getChartDataApi(Request $request, ApiMonitor $apiMonitor): \Illuminate\Http\JsonResponse
    {
        // TODO: Implement getChartDataApi method
        return response()->json(['data' => []]);
    }

    public function duplicate(ApiMonitor $apiMonitor): RedirectResponse
    {
        // TODO: Implement duplicate method
        return redirect()->route('panel.api-monitors.index')
            ->with('success', 'API monitor duplicated successfully.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        // TODO: Implement bulkAction method
        return redirect()->route('panel.api-monitors.index')
            ->with('success', 'Bulk action completed.');
    }

    public function exportChecks(ApiMonitor $apiMonitor): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // TODO: Implement exportChecks method
        return response()->streamDownload(function() {}, 'checks.csv');
    }

    public function exportAlerts(ApiMonitor $apiMonitor): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // TODO: Implement exportAlerts method
        return response()->streamDownload(function() {}, 'alerts.csv');
    }
}
