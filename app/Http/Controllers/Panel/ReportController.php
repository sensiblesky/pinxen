<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\UserSubscription;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Language;
use App\Models\Timezone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display subscription reports page.
     */
    public function subscriptions(Request $request): View
    {
        $subscriptionPlans = SubscriptionPlan::active()->ordered()->get();
        $paymentGateways = ['stripe', 'paypal', 'razorpay', 'square', 'authorize_net', 'mollie'];
        
        return view('panel.reports.subscriptions', compact('subscriptionPlans', 'paymentGateways'));
    }

    /**
     * Get subscription report data for DataTables (server-side processing).
     */
    public function getSubscriptionsData(Request $request)
    {
        try {
            $query = UserSubscription::with(['user', 'subscriptionPlan', 'assignedBy', 'payment']);

            // Apply filters
            if ($request->filled('filter_status')) {
                $query->where('status', $request->input('filter_status'));
            }

            if ($request->filled('filter_payment_status')) {
                if ($request->input('filter_payment_status') === 'paid') {
                    $query->whereHas('payment', function($q) {
                        $q->where('status', 'completed');
                    });
                } elseif ($request->input('filter_payment_status') === 'unpaid') {
                    $query->where(function($q) {
                        $q->whereDoesntHave('payment')
                          ->orWhereHas('payment', function($paymentQuery) {
                              $paymentQuery->where('status', '!=', 'completed');
                          });
                    });
                }
            }

            if ($request->filled('filter_payment_gateway')) {
                $query->whereHas('payment', function($q) use ($request) {
                    $q->where('payment_gateway', $request->input('filter_payment_gateway'));
                });
            }

            if ($request->filled('filter_subscription_plan')) {
                $query->where('subscription_plan_id', $request->input('filter_subscription_plan'));
            }

            if ($request->filled('filter_billing_period')) {
                $query->where('billing_period', $request->input('filter_billing_period'));
            }

            if ($request->filled('filter_start_date')) {
                $query->where('created_at', '>=', $request->input('filter_start_date'));
            }

            if ($request->filled('filter_end_date')) {
                $query->where('created_at', '<=', $request->input('filter_end_date') . ' 23:59:59');
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

            // Get total count before pagination (for summary calculations)
            $totalQuery = clone $query;
            $allFilteredSubscriptions = $totalQuery->get();
            
            // Calculate financial metrics
            $totalAmountRequired = $allFilteredSubscriptions->sum('price');
            $paidSubscriptions = $allFilteredSubscriptions->filter(function($subscription) {
                return $subscription->payment && $subscription->payment->status === 'completed';
            });
            $totalAmountPaid = $paidSubscriptions->sum('price');
            $totalAmountUnpaid = $totalAmountRequired - $totalAmountPaid;
            
            // Calculate refunded amount
            $totalAmountRefunded = $allFilteredSubscriptions->filter(function($subscription) {
                return $subscription->payment && $subscription->payment->status === 'refunded';
            })->sum('price');
            
            // Calculate failed payments
            $failedPayments = $allFilteredSubscriptions->filter(function($subscription) {
                return $subscription->payment && $subscription->payment->status === 'failed';
            });
            $totalAmountFailed = $failedPayments->sum('price');
            
            // Calculate payment success rate
            $totalPayments = $allFilteredSubscriptions->filter(function($subscription) {
                return $subscription->payment !== null;
            })->count();
            $successfulPayments = $paidSubscriptions->count();
            $paymentSuccessRate = $totalPayments > 0 ? ($successfulPayments / $totalPayments) * 100 : 0;
            
            // Calculate MRR (Monthly Recurring Revenue) - sum of monthly active subscriptions
            $mrr = $allFilteredSubscriptions->filter(function($subscription) {
                return $subscription->status === 'active' && $subscription->billing_period === 'monthly';
            })->sum('price');
            
            // Calculate ARR (Annual Recurring Revenue) - sum of yearly active subscriptions * 12
            $arr = $allFilteredSubscriptions->filter(function($subscription) {
                return $subscription->status === 'active' && $subscription->billing_period === 'yearly';
            })->sum('price') * 12;
            
            // Calculate average transaction value
            $avgTransactionValue = $successfulPayments > 0 ? $totalAmountPaid / $successfulPayments : 0;
            
            // Revenue breakdown by payment gateway
            $revenueByGateway = [];
            foreach (['stripe', 'paypal', 'razorpay', 'square', 'authorize_net', 'mollie'] as $gateway) {
                $gatewayRevenue = $allFilteredSubscriptions->filter(function($subscription) use ($gateway) {
                    return $subscription->payment && 
                           $subscription->payment->payment_gateway === $gateway &&
                           $subscription->payment->status === 'completed';
                })->sum('price');
                if ($gatewayRevenue > 0) {
                    $revenueByGateway[$gateway] = $gatewayRevenue;
                }
            }
            
            // Revenue breakdown by subscription plan
            $revenueByPlan = [];
            $planGroups = $allFilteredSubscriptions->filter(function($subscription) {
                return $subscription->payment && $subscription->payment->status === 'completed';
            })->groupBy('subscription_plan_id');
            
            foreach ($planGroups as $planId => $subscriptions) {
                $plan = $subscriptions->first()->subscriptionPlan;
                if ($plan) {
                    $revenueByPlan[$plan->name] = $subscriptions->sum('price');
                }
            }

            // Get total records count
            $totalRecords = $query->count();

            // Apply ordering
            // Column mapping: 0=row_number, 1=user, 2=plan, 3=billing_period, 4=price, 5=payment_status, 6=payment_gateway, 7=transaction_id, 8=payment_date, 9=status, 10=created_at
            $orderColumn = $request->input('order.0.column', 10);
            $orderDir = $request->input('order.0.dir', 'desc');
            
            $columnMap = [
                1 => 'user_id',
                2 => 'subscription_plan_id',
                3 => 'billing_period',
                4 => 'price',
                9 => 'status',
                10 => 'created_at',
            ];
            
            $orderBy = $columnMap[$orderColumn] ?? 'created_at';
            
            $query->orderBy($orderBy, $orderDir);

            // Apply pagination
            $start = $request->input('start', 0);
            $length = $request->input('length', 25);
            $subscriptions = $query->skip($start)->take($length)->get();

            // Format data for DataTables
            $data = [];
            $rowNumber = $start + 1;
            
            foreach ($subscriptions as $subscription) {
                $isPaid = $subscription->payment && $subscription->payment->status === 'completed';
                $isRefunded = $subscription->payment && $subscription->payment->status === 'refunded';
                $isFailed = $subscription->payment && $subscription->payment->status === 'failed';
                
                // Determine row class
                $rowClass = '';
                if ($isRefunded) {
                    $rowClass = 'table-warning';
                } elseif ($isFailed) {
                    $rowClass = 'table-danger';
                } elseif (!$isPaid) {
                    $rowClass = 'table-danger';
                }
                
                $data[] = [
                    'row_number' => $rowNumber++,
                    'user' => '<a href="' . route('panel.users.show', $subscription->user->uid) . '">' . htmlspecialchars($subscription->user->name ?? 'N/A') . '</a><br><small class="text-muted">' . htmlspecialchars($subscription->user->email ?? 'N/A') . '</small>',
                    'plan' => htmlspecialchars($subscription->subscriptionPlan->name ?? 'N/A'),
                    'billing_period' => ucfirst($subscription->billing_period),
                    'price' => '$' . number_format($subscription->price, 2),
                    'payment_status' => $isPaid 
                        ? '<span class="badge bg-success">Paid</span>' 
                        : ($isRefunded 
                            ? '<span class="badge bg-warning text-dark">Refunded</span>'
                            : ($isFailed
                                ? '<span class="badge bg-danger">Failed</span>'
                                : '<span class="badge bg-danger">Unpaid</span>')),
                    'payment_gateway' => $subscription->payment 
                        ? '<span class="badge bg-info">' . ucfirst($subscription->payment->payment_gateway) . '</span>' 
                        : '<span class="text-muted">N/A</span>',
                    'transaction_id' => $subscription->payment && $subscription->payment->gateway_transaction_id
                        ? '<small class="font-monospace">' . htmlspecialchars(substr($subscription->payment->gateway_transaction_id, 0, 20)) . '...</small>'
                        : '<span class="text-muted">N/A</span>',
                    'payment_date' => $subscription->payment && $subscription->payment->paid_at
                        ? $subscription->payment->paid_at->format('Y-m-d H:i')
                        : '<span class="text-muted">N/A</span>',
                    'status' => '<span class="badge bg-' . ($subscription->status === 'active' ? 'success' : ($subscription->status === 'expired' ? 'danger' : ($subscription->status === 'cancelled' ? 'secondary' : 'warning'))) . '">' . ucfirst($subscription->status) . '</span>',
                    'created_at' => $subscription->created_at->format('Y-m-d H:i'),
                    'row_class' => $rowClass,
                ];
            }

            $totalRecordsCount = UserSubscription::count();
            
            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => $totalRecordsCount,
                'recordsFiltered' => $totalRecords,
                'data' => $data,
                'summary' => [
                    'total_amount_required' => number_format($totalAmountRequired, 2),
                    'total_amount_paid' => number_format($totalAmountPaid, 2),
                    'total_amount_unpaid' => number_format($totalAmountUnpaid, 2),
                    'total_amount_refunded' => number_format($totalAmountRefunded, 2),
                    'total_amount_failed' => number_format($totalAmountFailed, 2),
                    'payment_success_rate' => number_format($paymentSuccessRate, 2),
                    'mrr' => number_format($mrr, 2),
                    'arr' => number_format($arr, 2),
                    'avg_transaction_value' => number_format($avgTransactionValue, 2),
                    'total_payments' => $totalPayments,
                    'successful_payments' => $successfulPayments,
                    'revenue_by_gateway' => $revenueByGateway,
                    'revenue_by_plan' => $revenueByPlan,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Subscription Report DataTables Error: ' . $e->getMessage(), [
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
     * Export subscription report to CSV.
     */
    public function exportSubscriptions(Request $request)
    {
        $query = UserSubscription::with(['user', 'subscriptionPlan', 'assignedBy', 'payment']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('payment_status')) {
            if ($request->input('payment_status') === 'paid') {
                $query->whereHas('payment', function($q) {
                    $q->where('status', 'completed');
                });
            } elseif ($request->input('payment_status') === 'unpaid') {
                $query->where(function($q) {
                    $q->whereDoesntHave('payment')
                      ->orWhereHas('payment', function($paymentQuery) {
                          $paymentQuery->where('status', '!=', 'completed');
                      });
                });
            }
        }

        if ($request->filled('payment_gateway')) {
            $query->whereHas('payment', function($q) use ($request) {
                $q->where('payment_gateway', $request->input('payment_gateway'));
            });
        }

        if ($request->filled('subscription_plan')) {
            $query->where('subscription_plan_id', $request->input('subscription_plan'));
        }

        if ($request->filled('billing_period')) {
            $query->where('billing_period', $request->input('billing_period'));
        }

        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->input('end_date') . ' 23:59:59');
        }

        $subscriptions = $query->orderBy('created_at', 'desc')->get();

        $filename = 'subscriptions_report_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($subscriptions) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Add headers
            fputcsv($file, [
                'User Name',
                'User Email',
                'Plan Name',
                'Billing Period',
                'Price',
                'Payment Status',
                'Payment Gateway',
                'Transaction ID',
                'Payment Date',
                'Start Date',
                'End Date',
                'Status',
                'Assigned By',
                'Created At'
            ]);

            // Add data rows
            foreach ($subscriptions as $subscription) {
                $isPaid = $subscription->payment && $subscription->payment->status === 'completed';
                $isRefunded = $subscription->payment && $subscription->payment->status === 'refunded';
                $isFailed = $subscription->payment && $subscription->payment->status === 'failed';
                
                if ($isRefunded) {
                    $paymentStatus = 'Refunded';
                } elseif ($isFailed) {
                    $paymentStatus = 'Failed';
                } elseif ($isPaid) {
                    $paymentStatus = 'Paid';
                } else {
                    $paymentStatus = 'Unpaid';
                }
                
                fputcsv($file, [
                    $subscription->user->name ?? 'N/A',
                    $subscription->user->email ?? 'N/A',
                    $subscription->subscriptionPlan->name ?? 'N/A',
                    ucfirst($subscription->billing_period),
                    '$' . number_format($subscription->price, 2),
                    $paymentStatus,
                    $subscription->payment ? ucfirst($subscription->payment->payment_gateway) : 'N/A',
                    $subscription->payment->gateway_transaction_id ?? 'N/A',
                    $subscription->payment && $subscription->payment->paid_at ? $subscription->payment->paid_at->format('Y-m-d H:i:s') : 'N/A',
                    $subscription->starts_at ? $subscription->starts_at->format('Y-m-d H:i:s') : 'N/A',
                    $subscription->ends_at ? $subscription->ends_at->format('Y-m-d H:i:s') : 'N/A',
                    ucfirst($subscription->status),
                    $subscription->assignedBy->name ?? 'System',
                    $subscription->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export subscription report to Excel (CSV format with .xls extension for compatibility).
     */
    public function exportSubscriptionsExcel(Request $request)
    {
        return $this->exportSubscriptions($request);
    }

    /**
     * Display user reports page.
     */
    public function users(Request $request): View
    {
        $subscriptionPlans = SubscriptionPlan::active()->ordered()->get();
        $languages = Language::where('is_active', true)->orderBy('name')->get();
        $timezones = Timezone::where('is_active', true)->orderBy('name')->get();
        
        return view('panel.reports.users', compact('subscriptionPlans', 'languages', 'timezones'));
    }

    /**
     * Get user report data for DataTables (server-side processing).
     */
    public function getUsersData(Request $request)
    {
        try {
            $query = User::with(['subscriptionPlan', 'language', 'timezone', 'subscriptions']);

            // Apply filters
            if ($request->filled('filter_role')) {
                $query->where('role', $request->input('filter_role'));
            }

            if ($request->filled('filter_status')) {
                if ($request->input('filter_status') === 'active') {
                    $query->where('is_active', true)->where('is_deleted', false);
                } elseif ($request->input('filter_status') === 'inactive') {
                    $query->where('is_active', false)->where('is_deleted', false);
                } elseif ($request->input('filter_status') === 'deleted') {
                    $query->where('is_deleted', true);
                }
            }

            if ($request->filled('filter_email_verified')) {
                if ($request->input('filter_email_verified') === 'verified') {
                    $query->whereNotNull('email_verified_at');
                } elseif ($request->input('filter_email_verified') === 'unverified') {
                    $query->whereNull('email_verified_at');
                }
            }

            if ($request->filled('filter_subscription_plan')) {
                $query->where('subscription_plan_id', $request->input('filter_subscription_plan'));
            }

            if ($request->filled('filter_language')) {
                $query->where('language_id', $request->input('filter_language'));
            }

            if ($request->filled('filter_timezone')) {
                $query->where('timezone_id', $request->input('filter_timezone'));
            }

            if ($request->filled('filter_two_factor')) {
                $query->where('two_factor_enabled', $request->input('filter_two_factor') === 'enabled');
            }

            if ($request->filled('filter_start_date')) {
                $query->where('created_at', '>=', $request->input('filter_start_date'));
            }

            if ($request->filled('filter_end_date')) {
                $query->where('created_at', '<=', $request->input('filter_end_date') . ' 23:59:59');
            }

            // Global search
            $searchValue = $request->input('search.value');
            if (!empty($searchValue)) {
                $query->where(function($q) use ($searchValue) {
                    $q->where('name', 'like', '%' . $searchValue . '%')
                      ->orWhere('email', 'like', '%' . $searchValue . '%')
                      ->orWhere('phone', 'like', '%' . $searchValue . '%');
                });
            }

            // Get total count before pagination (for summary calculations)
            $totalQuery = clone $query;
            $allFilteredUsers = $totalQuery->get();
            
            // Calculate summary metrics
            $totalUsers = $allFilteredUsers->count();
            $activeUsers = $allFilteredUsers->where('is_active', true)->where('is_deleted', false)->count();
            $inactiveUsers = $allFilteredUsers->where('is_active', false)->where('is_deleted', false)->count();
            $deletedUsers = $allFilteredUsers->where('is_deleted', true)->count();
            $verifiedUsers = $allFilteredUsers->whereNotNull('email_verified_at')->count();
            $unverifiedUsers = $allFilteredUsers->whereNull('email_verified_at')->count();
            $adminUsers = $allFilteredUsers->where('role', 1)->count();
            $clientUsers = $allFilteredUsers->where('role', 2)->count();
            $twoFactorEnabled = $allFilteredUsers->where('two_factor_enabled', true)->count();
            $usersWithSubscription = $allFilteredUsers->filter(function($user) {
                return $user->subscriptionPlan !== null;
            })->count();

            // Users by role breakdown
            $usersByRole = [
                'admin' => $adminUsers,
                'client' => $clientUsers,
            ];

            // Users by subscription plan breakdown
            $usersByPlan = [];
            $planGroups = $allFilteredUsers->filter(function($user) {
                return $user->subscriptionPlan !== null;
            })->groupBy('subscription_plan_id');
            
            foreach ($planGroups as $planId => $users) {
                $plan = $users->first()->subscriptionPlan;
                if ($plan) {
                    $usersByPlan[$plan->name] = $users->count();
                }
            }

            // Get total records count
            $totalRecords = $query->count();

            // Apply ordering
            // Column mapping: 0=row_number, 1=name, 2=email, 3=role, 4=status, 5=email_verified, 6=subscription_plan, 7=two_factor, 8=created_at
            $orderColumn = $request->input('order.0.column', 8);
            $orderDir = $request->input('order.0.dir', 'desc');
            
            $columnMap = [
                1 => 'name',
                2 => 'email',
                3 => 'role',
                4 => 'is_active',
                8 => 'created_at',
            ];
            
            $orderBy = $columnMap[$orderColumn] ?? 'created_at';
            
            $query->orderBy($orderBy, $orderDir);

            // Apply pagination
            $start = $request->input('start', 0);
            $length = $request->input('length', 25);
            $users = $query->skip($start)->take($length)->get();

            // Format data for DataTables
            $data = [];
            $rowNumber = $start + 1;
            
            foreach ($users as $user) {
                $roleBadge = $user->role == 1 
                    ? '<span class="badge bg-danger">Admin</span>' 
                    : '<span class="badge bg-primary">Client</span>';
                
                $statusBadge = $user->is_deleted 
                    ? '<span class="badge bg-secondary">Deleted</span>'
                    : ($user->is_active 
                        ? '<span class="badge bg-success">Active</span>' 
                        : '<span class="badge bg-warning text-dark">Inactive</span>');
                
                $emailVerifiedBadge = $user->email_verified_at 
                    ? '<span class="badge bg-success"><i class="ri-check-line"></i> Verified</span>' 
                    : '<span class="badge bg-danger"><i class="ri-close-line"></i> Unverified</span>';
                
                $twoFactorBadge = $user->two_factor_enabled 
                    ? '<span class="badge bg-info">Enabled</span>' 
                    : '<span class="badge bg-secondary">Disabled</span>';
                
                $data[] = [
                    'row_number' => $rowNumber++,
                    'name' => '<a href="' . route('panel.users.show', $user->uid) . '">' . htmlspecialchars($user->name) . '</a>',
                    'email' => htmlspecialchars($user->email),
                    'phone' => htmlspecialchars($user->phone ?? 'N/A'),
                    'role' => $roleBadge,
                    'status' => $statusBadge,
                    'email_verified' => $emailVerifiedBadge,
                    'subscription_plan' => $user->subscriptionPlan 
                        ? '<span class="badge bg-info">' . htmlspecialchars($user->subscriptionPlan->name) . '</span>' 
                        : '<span class="text-muted">No Plan</span>',
                    'language' => $user->language 
                        ? htmlspecialchars($user->language->name) 
                        : '<span class="text-muted">N/A</span>',
                    'timezone' => $user->timezone 
                        ? htmlspecialchars($user->timezone->name) 
                        : '<span class="text-muted">N/A</span>',
                    'two_factor' => $twoFactorBadge,
                    'created_at' => $user->created_at->format('Y-m-d H:i'),
                ];
            }

            $totalRecordsCount = User::count();
            
            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => $totalRecordsCount,
                'recordsFiltered' => $totalRecords,
                'data' => $data,
                'summary' => [
                    'total_users' => $totalUsers,
                    'active_users' => $activeUsers,
                    'inactive_users' => $inactiveUsers,
                    'deleted_users' => $deletedUsers,
                    'verified_users' => $verifiedUsers,
                    'unverified_users' => $unverifiedUsers,
                    'admin_users' => $adminUsers,
                    'client_users' => $clientUsers,
                    'two_factor_enabled' => $twoFactorEnabled,
                    'users_with_subscription' => $usersWithSubscription,
                    'users_by_role' => $usersByRole,
                    'users_by_plan' => $usersByPlan,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('User Report DataTables Error: ' . $e->getMessage(), [
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
     * Export user report to CSV.
     */
    public function exportUsers(Request $request)
    {
        $query = User::with(['subscriptionPlan', 'language', 'timezone']);

        // Apply filters
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true)->where('is_deleted', false);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false)->where('is_deleted', false);
            } elseif ($request->input('status') === 'deleted') {
                $query->where('is_deleted', true);
            }
        }

        if ($request->filled('email_verified')) {
            if ($request->input('email_verified') === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->input('email_verified') === 'unverified') {
                $query->whereNull('email_verified_at');
            }
        }

        if ($request->filled('subscription_plan')) {
            $query->where('subscription_plan_id', $request->input('subscription_plan'));
        }

        if ($request->filled('language')) {
            $query->where('language_id', $request->input('language'));
        }

        if ($request->filled('timezone')) {
            $query->where('timezone_id', $request->input('timezone'));
        }

        if ($request->filled('two_factor')) {
            $query->where('two_factor_enabled', $request->input('two_factor') === 'enabled');
        }

        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->input('end_date') . ' 23:59:59');
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        $filename = 'users_report_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Add headers
            fputcsv($file, [
                'Name',
                'Email',
                'Phone',
                'Role',
                'Status',
                'Email Verified',
                'Subscription Plan',
                'Language',
                'Timezone',
                'Two Factor Enabled',
                'Email Verified At',
                'Created At',
                'Last Login'
            ]);

            // Add data rows
            foreach ($users as $user) {
                $lastLogin = $user->loginActivities()->orderBy('logged_in_at', 'desc')->first();
                
                fputcsv($file, [
                    $user->name,
                    $user->email,
                    $user->phone ?? 'N/A',
                    $user->role == 1 ? 'Admin' : 'Client',
                    $user->is_deleted ? 'Deleted' : ($user->is_active ? 'Active' : 'Inactive'),
                    $user->email_verified_at ? 'Yes' : 'No',
                    $user->subscriptionPlan ? $user->subscriptionPlan->name : 'No Plan',
                    $user->language ? $user->language->name : 'N/A',
                    $user->timezone ? $user->timezone->name : 'N/A',
                    $user->two_factor_enabled ? 'Yes' : 'No',
                    $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i:s') : 'N/A',
                    $user->created_at->format('Y-m-d H:i:s'),
                    $lastLogin && $lastLogin->logged_in_at ? $lastLogin->logged_in_at->format('Y-m-d H:i:s') : 'Never'
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export user report to Excel (CSV format with .xls extension for compatibility).
     */
    public function exportUsersExcel(Request $request)
    {
        return $this->exportUsers($request);
    }
}

