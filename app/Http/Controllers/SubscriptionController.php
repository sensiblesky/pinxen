<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    /**
     * Display the pricing page.
     */
    public function index(): View
    {
        $user = Auth::user();
        $plans = SubscriptionPlan::active()->ordered()->with('features')->get();
        
        // Get user's active subscriptions to check which plans they're already subscribed to
        $activeSubscriptions = $user->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->with('subscriptionPlan')
            ->get()
            ->keyBy('subscription_plan_id');
        
        // Create a map of plan IDs that user is already subscribed to
        $subscribedPlanIds = $activeSubscriptions->pluck('subscription_plan_id')->toArray();
        
        // Get the highest tier plan the user currently has
        $userHighestTierPlan = null;
        if ($activeSubscriptions->isNotEmpty()) {
            $userHighestTierPlan = $activeSubscriptions->map(function ($subscription) {
                return $subscription->subscriptionPlan;
            })->sortBy('order')->first();
        }
        
        // Determine plan availability for each plan
        $planAvailability = [];
        foreach ($plans as $plan) {
            $isSubscribed = in_array($plan->id, $subscribedPlanIds);
            $canUpgrade = false;
            $canDowngrade = false;
            $isDowngrade = false;
            $isUpgrade = false;
            
            if ($isSubscribed) {
                // User already has this plan
                $canUpgrade = false;
                $canDowngrade = false;
            } elseif ($userHighestTierPlan) {
                // User has a different plan
                if ($plan->isHigherTierThan($userHighestTierPlan)) {
                    // This is an upgrade
                    $isUpgrade = true;
                    $canUpgrade = true;
                    $canDowngrade = false;
                } elseif ($plan->isLowerTierThan($userHighestTierPlan)) {
                    // This is a downgrade
                    $isDowngrade = true;
                    $canUpgrade = false;
                    $canDowngrade = false; // Prevent downgrades
                } else {
                    // Same tier, different plan (sidegrade)
                    $canUpgrade = true;
                    $canDowngrade = false;
                }
            } else {
                // User has no active subscription, all plans available
                $canUpgrade = true;
                $canDowngrade = false;
            }
            
            $planAvailability[$plan->id] = [
                'is_subscribed' => $isSubscribed,
                'can_upgrade' => $canUpgrade,
                'can_downgrade' => $canDowngrade,
                'is_downgrade' => $isDowngrade,
                'is_upgrade' => $isUpgrade,
            ];
        }
        
        return view('subscriptions.pricing', compact(
            'plans', 
            'activeSubscriptions', 
            'subscribedPlanIds',
            'userHighestTierPlan',
            'planAvailability'
        ));
    }

    /**
     * Subscribe to a plan - redirects to payment page.
     */
    public function subscribe(Request $request, SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $user = Auth::user();
        
        // Validate billing period
        $billingPeriod = $request->input('billing_period', 'monthly');
        if (!in_array($billingPeriod, ['monthly', 'yearly'])) {
            return redirect()->route('subscriptions.index')->with('error', 'Invalid billing period.');
        }

        // Ensure plan is loaded and has uid
        if (!$subscriptionPlan || !$subscriptionPlan->uid) {
            \Log::error('Subscription plan not found', [
                'route_param' => $request->route('subscriptionPlan'),
                'plan_id' => $subscriptionPlan->id ?? 'null',
                'plan_uid' => $subscriptionPlan->uid ?? 'null'
            ]);
            return redirect()->route('subscriptions.index')
                ->with('error', 'Invalid subscription plan.');
        }

        // Check if user already has an active subscription for this plan
        $existingActiveSubscription = $user->subscriptions()
            ->where('subscription_plan_id', $subscriptionPlan->id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();

        if ($existingActiveSubscription) {
            return redirect()->route('subscriptions.index')
                ->with('info', 'You already have an active subscription for ' . $subscriptionPlan->name . '. It expires on ' . $existingActiveSubscription->ends_at->format('M d, Y') . '.');
        }

        // Check for downgrade prevention
        $userHighestTierPlan = $user->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->with('subscriptionPlan')
            ->get()
            ->map(function ($subscription) {
                return $subscription->subscriptionPlan;
            })
            ->sortBy('order')
            ->first();

        if ($userHighestTierPlan && $subscriptionPlan->isLowerTierThan($userHighestTierPlan)) {
            return redirect()->route('subscriptions.index')
                ->with('error', 'You cannot downgrade from ' . $userHighestTierPlan->name . ' to ' . $subscriptionPlan->name . '. Please contact support if you need to change your plan.');
        }

        // Redirect to payment page
        // Build URL manually to avoid route generation issues
        $url = url('/subscriptions/' . $subscriptionPlan->uid . '/payment?billing_period=' . urlencode($billingPeriod));
        return redirect($url);
    }

    /**
     * Show current subscription.
     */
    public function show(): View
    {
        $user = Auth::user();
        
        // Only load active subscription for display (not all history)
        $activeSubscription = $user->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->with(['subscriptionPlan.features', 'payment'])
            ->first();

        // Don't load all subscription history here - it will be loaded via AJAX with pagination
        return view('subscriptions.show', compact('activeSubscription'));
    }

    /**
     * Get subscription history data for DataTables (server-side processing).
     */
    public function getSubscriptionHistoryData(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Start with base query - only current user's subscriptions
            $query = $user->subscriptions()->with(['subscriptionPlan', 'payment']);

            // Global search
            $searchValue = $request->input('search.value');
            if (!empty($searchValue)) {
                $query->where(function($q) use ($searchValue) {
                    $q->whereHas('subscriptionPlan', function($planQuery) use ($searchValue) {
                        $planQuery->where('name', 'like', '%' . $searchValue . '%');
                    })
                    ->orWhere('billing_period', 'like', '%' . $searchValue . '%')
                    ->orWhere('status', 'like', '%' . $searchValue . '%')
                    ->orWhere('price', 'like', '%' . $searchValue . '%');
                });
            }

            // Get total count before pagination
            $totalRecords = $query->count();

            // Apply ordering
            // Column mapping: 0=row_number, 1=plan, 2=billing_period, 3=price, 4=starts_at, 5=ends_at, 6=status, 7=payment, 8=created_at
            $orderColumn = $request->input('order.0.column', 8);
            $orderDir = $request->input('order.0.dir', 'desc');
            
            $columnMap = [
                1 => 'subscription_plan_id',
                2 => 'billing_period',
                3 => 'price',
                4 => 'starts_at',
                5 => 'ends_at',
                6 => 'status',
                8 => 'created_at',
            ];
            
            $orderBy = $columnMap[$orderColumn] ?? 'created_at';
            
            $query->orderBy($orderBy, $orderDir);

            // Apply pagination
            $start = $request->input('start', 0);
            $length = $request->input('length', 10); // Default to 10 items per page
            $subscriptions = $query->skip($start)->take($length)->get();

            // Format data for DataTables
            $data = [];
            $rowNumber = $start + 1;
            
            foreach ($subscriptions as $subscription) {
                $paymentInfo = 'N/A';
                if ($subscription->payment) {
                    $gateway = '<span class="badge bg-info">' . ucfirst($subscription->payment->payment_gateway) . '</span>';
                    $status = $subscription->payment->status === 'completed' 
                        ? '<span class="badge bg-success ms-1">Paid</span>' 
                        : '<span class="badge bg-danger ms-1">Unpaid</span>';
                    $paymentInfo = $gateway . ' ' . $status;
                }

                $data[] = [
                    'row_number' => $rowNumber++,
                    'plan' => $subscription->subscriptionPlan->name ?? 'N/A',
                    'billing_period' => ucfirst($subscription->billing_period),
                    'price' => '$' . number_format($subscription->price, 2),
                    'starts_at' => $subscription->starts_at ? $subscription->starts_at->format('Y-m-d H:i') : 'N/A',
                    'ends_at' => $subscription->ends_at ? $subscription->ends_at->format('Y-m-d H:i') : 'N/A',
                    'status' => '<span class="badge bg-' . ($subscription->status === 'active' ? 'success' : ($subscription->status === 'expired' ? 'danger' : ($subscription->status === 'cancelled' ? 'secondary' : 'warning'))) . '">' . ucfirst($subscription->status) . '</span>',
                    'payment' => $paymentInfo,
                    'created_at' => $subscription->created_at->format('Y-m-d H:i'),
                ];
            }

            // Get total records count (without filters for recordsTotal)
            $totalRecordsCount = $user->subscriptions()->count();
            
            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => $totalRecordsCount,
                'recordsFiltered' => $totalRecords,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Log::error('Subscription History DataTables Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while loading data. Please refresh the page.',
            ], 500);
        }
    }
}

