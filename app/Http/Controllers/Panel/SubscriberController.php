<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\UserSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SubscriberController extends Controller
{
    /**
     * Display a listing of all subscribers.
     */
    public function index(): View
    {
        return view('panel.subscribers.index');
    }

    /**
     * Get subscribers data for DataTables (server-side processing).
     */
    public function getSubscribersData(Request $request)
    {
        try {
            Log::info('Subscribers DataTables request received', [
                'draw' => $request->input('draw'),
                'start' => $request->input('start'),
                'length' => $request->input('length'),
            ]);
            
            // Start with base query - eager load relationships
            $query = UserSubscription::with(['user', 'subscriptionPlan', 'assignedBy']);

            // Filter by status
            if ($request->filled('filter_status')) {
                $query->where('status', $request->input('filter_status'));
            }

            // Global search
            $searchValue = $request->input('search.value');
            if (!empty($searchValue)) {
                $query->where(function($q) use ($searchValue) {
                    $q->whereHas('user', function($userQuery) use ($searchValue) {
                        $userQuery->where('name', 'like', '%' . $searchValue . '%')
                                  ->orWhere('email', 'like', '%' . $searchValue . '%');
                    })
                    ->orWhereHas('subscriptionPlan', function($planQuery) use ($searchValue) {
                        $planQuery->where('name', 'like', '%' . $searchValue . '%');
                    })
                    ->orWhere('billing_period', 'like', '%' . $searchValue . '%')
                    ->orWhere('status', 'like', '%' . $searchValue . '%');
                });
            }

            // Get total count before pagination
            $totalRecords = $query->count();

            // Apply ordering
            // Column mapping: 0=row_number, 1=user, 2=plan, 3=billing_period, 4=price, 5=starts_at, 6=ends_at, 7=status, 8=assigned_by, 9=created_at
            $orderColumn = $request->input('order.0.column', 9);
            $orderDir = $request->input('order.0.dir', 'desc');
            
            $columnMap = [
                1 => 'user_id',
                2 => 'subscription_plan_id',
                3 => 'billing_period',
                4 => 'price',
                5 => 'starts_at',
                6 => 'ends_at',
                7 => 'status',
                8 => 'assigned_by',
                9 => 'created_at',
            ];
            
            $orderBy = $columnMap[$orderColumn] ?? 'created_at';
            
            $query->orderBy($orderBy, $orderDir);

            // Apply pagination
            $start = $request->input('start', 0);
            $length = $request->input('length', 100);
            $subscriptions = $query->skip($start)->take($length)->get();

            // Format data for DataTables
            $data = [];
            $rowNumber = $start + 1;
            
            foreach ($subscriptions as $subscription) {
                // Build actions HTML for active subscriptions
                $actionsHtml = '';
                if ($subscription->status === 'active') {
                    $csrfToken = csrf_token();
                    $actionsHtml = '<div class="btn-list">';
                    $actionsHtml .= '<form action="' . route('panel.subscribers.update-status', [$subscription->user->uid, $subscription->id]) . '" method="POST" class="d-inline cancel-subscription-form" data-user-name="' . htmlspecialchars($subscription->user->name, ENT_QUOTES) . '" data-plan-name="' . htmlspecialchars($subscription->subscriptionPlan->name ?? 'N/A', ENT_QUOTES) . '">';
                    $actionsHtml .= '<input type="hidden" name="_token" value="' . $csrfToken . '">';
                    $actionsHtml .= '<input type="hidden" name="status" value="cancelled">';
                    $actionsHtml .= '<button type="button" class="btn btn-sm btn-danger btn-wave cancel-subscription-btn" data-bs-toggle="tooltip" title="Cancel Subscription">';
                    $actionsHtml .= '<i class="ri-close-circle-line"></i>';
                    $actionsHtml .= '</button>';
                    $actionsHtml .= '</form>';
                    $actionsHtml .= '<form action="' . route('panel.subscribers.update-status', [$subscription->user->uid, $subscription->id]) . '" method="POST" class="d-inline expire-subscription-form" data-user-name="' . htmlspecialchars($subscription->user->name, ENT_QUOTES) . '" data-plan-name="' . htmlspecialchars($subscription->subscriptionPlan->name ?? 'N/A', ENT_QUOTES) . '">';
                    $actionsHtml .= '<input type="hidden" name="_token" value="' . $csrfToken . '">';
                    $actionsHtml .= '<input type="hidden" name="status" value="expired">';
                    $actionsHtml .= '<button type="button" class="btn btn-sm btn-warning btn-wave expire-subscription-btn" data-bs-toggle="tooltip" title="Mark as Expired">';
                    $actionsHtml .= '<i class="ri-time-line"></i>';
                    $actionsHtml .= '</button>';
                    $actionsHtml .= '</form>';
                    $actionsHtml .= '</div>';
                } else {
                    $actionsHtml = '<span class="text-muted">-</span>';
                }

                $data[] = [
                    'row_number' => $rowNumber++,
                    'user' => '<a href="' . route('panel.users.show', $subscription->user->uid) . '">' . htmlspecialchars($subscription->user->name) . '</a><br><small class="text-muted">' . htmlspecialchars($subscription->user->email) . '</small>',
                    'plan' => htmlspecialchars($subscription->subscriptionPlan->name ?? 'N/A'),
                    'billing_period' => ucfirst($subscription->billing_period),
                    'price' => '$' . number_format($subscription->price, 2),
                    'starts_at' => $subscription->starts_at ? $subscription->starts_at->format('Y-m-d H:i') : 'N/A',
                    'ends_at' => $subscription->ends_at ? $subscription->ends_at->format('Y-m-d H:i') : 'N/A',
                    'status' => '<span class="badge bg-' . ($subscription->status === 'active' ? 'success' : ($subscription->status === 'expired' ? 'danger' : ($subscription->status === 'cancelled' ? 'secondary' : 'warning'))) . '">' . ucfirst($subscription->status) . '</span>',
                    'assigned_by' => $subscription->assignedBy ? htmlspecialchars($subscription->assignedBy->name) : '<span class="text-muted">System</span>',
                    'created_at' => $subscription->created_at->format('Y-m-d H:i'),
                    'actions' => $actionsHtml,
                ];
            }

            // Calculate total records count with same filters
            $totalCountQuery = UserSubscription::query();
            if ($request->filled('filter_status')) {
                $totalCountQuery->where('status', $request->input('filter_status'));
            }
            $totalRecordsCount = $totalCountQuery->count();
            
            $response = response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => $totalRecordsCount,
                'recordsFiltered' => $totalRecords,
                'data' => $data,
            ]);
            
            $response->header('Content-Type', 'application/json');
            
            return $response;
        } catch (\Exception $e) {
            \Log::error('Subscribers DataTables Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while loading data. Please check the logs.',
            ], 500);
        }
    }

    /**
     * Update subscription status (cancel or mark as expired).
     */
    public function updateStatus(Request $request, User $user, UserSubscription $subscription): RedirectResponse
    {
        // Ensure the subscription belongs to the user
        if ($subscription->user_id !== $user->id) {
            return redirect()->route('panel.subscribers.index')
                ->with('error', 'Subscription not found for this user.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:cancelled,expired'],
        ]);

        // Update subscription status
        $subscription->status = $validated['status'];
        
        if ($validated['status'] === 'cancelled') {
            $subscription->cancelled_at = now();
        } elseif ($validated['status'] === 'expired') {
            $subscription->ends_at = now();
        }
        
        $subscription->save();

        // If this was the active subscription, update user's subscription_plan_id
        if ($subscription->status !== 'active' && $user->subscription_plan_id === $subscription->subscription_plan_id) {
            $user->update(['subscription_plan_id' => null]);
        }

        $statusLabel = $validated['status'] === 'cancelled' ? 'cancelled' : 'expired';
        
        return redirect()->route('panel.subscribers.index')
            ->with('success', "Subscription has been {$statusLabel} successfully.");
    }
}
