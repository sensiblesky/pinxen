<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\UserSubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DemoPaymentsAndSubscriptionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get subscription plans
        $plans = SubscriptionPlan::all();
        
        if ($plans->isEmpty()) {
            $this->command->warn('No subscription plans found. Please run SubscriptionSeeder first.');
            return;
        }

        $paymentGateways = ['stripe', 'paypal', 'razorpay', 'square', 'authorize_net', 'mollie'];
        $paymentStatuses = ['completed', 'pending', 'failed', 'cancelled', 'refunded'];
        $subscriptionStatuses = ['active', 'expired', 'cancelled', 'pending'];
        $billingPeriods = ['monthly', 'yearly'];

        $users = [1, 2];
        $createdPayments = [];
        $createdSubscriptions = [];

        // Create 50 payments for each user (100 total)
        foreach ($users as $userId) {
            for ($i = 0; $i < 50; $i++) {
                $plan = $plans->random();
                $billingPeriod = $billingPeriods[array_rand($billingPeriods)];
                $price = $billingPeriod === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
                
                // Vary the amount slightly (±20%)
                $amount = $price * (0.8 + (rand(0, 40) / 100));
                
                $status = $paymentStatuses[array_rand($paymentStatuses)];
                $gateway = $paymentGateways[array_rand($paymentGateways)];
                
                // Set paid_at based on status
                $paidAt = null;
                if ($status === 'completed') {
                    // Random date within last 90 days
                    $paidAt = Carbon::now()->subDays(rand(0, 90))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
                } elseif ($status === 'refunded') {
                    $paidAt = Carbon::now()->subDays(rand(0, 90))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
                }

                $payment = Payment::create([
                    'user_id' => $userId,
                    'subscription_plan_id' => $plan->id,
                    'payment_gateway' => $gateway,
                    'gateway_transaction_id' => 'txn_' . Str::random(20),
                    'amount' => round($amount, 2),
                    'currency' => 'USD',
                    'status' => $status,
                    'paid_at' => $paidAt,
                    'refunded_at' => $status === 'refunded' && $paidAt ? $paidAt->copy()->addDays(rand(1, 7)) : null,
                    'gateway_response' => [
                        'transaction_id' => 'txn_' . Str::random(20),
                        'status' => $status,
                        'timestamp' => $paidAt ? $paidAt->toIso8601String() : null,
                    ],
                    'metadata' => [
                        'source' => 'demo_seeder',
                        'billing_period' => $billingPeriod,
                    ],
                ]);

                $createdPayments[] = $payment;
            }
        }

        $this->command->info('Created ' . count($createdPayments) . ' payments.');

        // Create 60 subscriptions for each user (120 total)
        foreach ($users as $userId) {
            for ($i = 0; $i < 60; $i++) {
                $plan = $plans->random();
                $billingPeriod = $billingPeriods[array_rand($billingPeriods)];
                $price = $billingPeriod === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
                
                $status = $subscriptionStatuses[array_rand($subscriptionStatuses)];
                
                // Set dates based on status
                $startsAt = Carbon::now()->subDays(rand(0, 120));
                $endsAt = null;
                $cancelledAt = null;
                $paymentId = null;

                if ($status === 'active') {
                    // Active subscription: ends in future
                    $endsAt = $billingPeriod === 'yearly' 
                        ? $startsAt->copy()->addYear() 
                        : $startsAt->copy()->addMonth();
                    
                    // Ensure it's in the future
                    if ($endsAt->isPast()) {
                        $endsAt = Carbon::now()->addDays(rand(1, 30));
                    }
                    
                    // Link to a completed payment
                    $completedPayment = collect($createdPayments)
                        ->where('user_id', $userId)
                        ->where('status', 'completed')
                        ->where('subscription_plan_id', $plan->id)
                        ->first();
                    
                    if ($completedPayment) {
                        $paymentId = $completedPayment->id;
                    }
                } elseif ($status === 'expired') {
                    // Expired subscription: ended in past
                    $endsAt = $startsAt->copy()->add($billingPeriod === 'yearly' ? 1 : 0, 'year')
                                     ->add($billingPeriod === 'monthly' ? 1 : 0, 'month');
                    
                    // Make sure it's in the past
                    if ($endsAt->isFuture()) {
                        $endsAt = Carbon::now()->subDays(rand(1, 30));
                    }
                } elseif ($status === 'cancelled') {
                    // Cancelled subscription
                    $endsAt = $startsAt->copy()->add($billingPeriod === 'yearly' ? 1 : 0, 'year')
                                     ->add($billingPeriod === 'monthly' ? 1 : 0, 'month');
                    $cancelledAt = $startsAt->copy()->addDays(rand(1, (int)$startsAt->diffInDays($endsAt)));
                } else {
                    // Pending subscription
                    $endsAt = $billingPeriod === 'yearly' 
                        ? $startsAt->copy()->addYear() 
                        : $startsAt->copy()->addMonth();
                }

                $subscription = UserSubscription::create([
                    'user_id' => $userId,
                    'subscription_plan_id' => $plan->id,
                    'billing_period' => $billingPeriod,
                    'price' => round($price, 2),
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'cancelled_at' => $cancelledAt,
                    'status' => $status,
                    'payment_id' => $paymentId,
                ]);

                // Update payment with subscription_id if payment exists
                if ($paymentId) {
                    Payment::where('id', $paymentId)->update([
                        'user_subscription_id' => $subscription->id
                    ]);
                }

                $createdSubscriptions[] = $subscription;
            }
        }

        $this->command->info('Created ' . count($createdSubscriptions) . ' subscriptions.');
        
        // Summary
        $this->command->info("\n=== Summary ===");
        $this->command->info('Total Payments: ' . count($createdPayments));
        $this->command->info('  - Completed: ' . collect($createdPayments)->where('status', 'completed')->count());
        $this->command->info('  - Pending: ' . collect($createdPayments)->where('status', 'pending')->count());
        $this->command->info('  - Failed: ' . collect($createdPayments)->where('status', 'failed')->count());
        $this->command->info('  - Refunded: ' . collect($createdPayments)->where('status', 'refunded')->count());
        
        $this->command->info("\nTotal Subscriptions: " . count($createdSubscriptions));
        $this->command->info('  - Active: ' . collect($createdSubscriptions)->where('status', 'active')->count());
        $this->command->info('  - Expired: ' . collect($createdSubscriptions)->where('status', 'expired')->count());
        $this->command->info('  - Cancelled: ' . collect($createdSubscriptions)->where('status', 'cancelled')->count());
        $this->command->info('  - Pending: ' . collect($createdSubscriptions)->where('status', 'pending')->count());
        
        $totalRevenue = collect($createdPayments)
            ->where('status', 'completed')
            ->sum('amount');
        
        $this->command->info("\nTotal Revenue (Completed Payments): $" . number_format($totalRevenue, 2));
    }
}

