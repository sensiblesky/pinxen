<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\Setting;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
// PayPal SDK imports removed - using direct HTTP requests instead due to PHP 8.3 compatibility issues

class PaymentController extends Controller
{
    /**
     * Show payment page with enabled payment gateways.
     */
    public function show(Request $request, SubscriptionPlan $subscriptionPlan): View|RedirectResponse
    {
        $user = Auth::user();
        $billingPeriod = $request->input('billing_period', 'monthly');
        
        if (!in_array($billingPeriod, ['monthly', 'yearly'])) {
            abort(400, 'Invalid billing period.');
        }

        // Prevent downgrades via direct link: block if user already has a higher tier plan
        if ($user) {
            $highestActivePlan = $this->getUserHighestActivePlan($user);
            if ($highestActivePlan && $subscriptionPlan->isLowerTierThan($highestActivePlan)) {
                return redirect()->route('subscriptions.index')
                    ->with('error', 'You already have a higher tier plan (' . $highestActivePlan->name . '). Downgrades are not allowed.');
            }
        }

        $price = $subscriptionPlan->getPrice($billingPeriod);
        $settings = Setting::getAllCached();

        // Get enabled payment gateways
        $enabledGateways = [];
        
        $gateways = [
            'stripe' => [
                'name' => 'Stripe',
                'icon' => 'ri-bank-card-line',
                'enabled' => ($settings['stripe_enabled'] ?? '0') === '1',
            ],
            'paypal' => [
                'name' => 'PayPal',
                'icon' => 'ri-paypal-line',
                'enabled' => ($settings['paypal_enabled'] ?? '0') === '1',
            ],
            'razorpay' => [
                'name' => 'Razorpay',
                'icon' => 'ri-money-rupee-circle-line',
                'enabled' => ($settings['razorpay_enabled'] ?? '0') === '1',
            ],
            'square' => [
                'name' => 'Square',
                'icon' => 'ri-square-line',
                'enabled' => ($settings['square_enabled'] ?? '0') === '1',
            ],
            'authorize_net' => [
                'name' => 'Authorize.Net',
                'icon' => 'ri-shield-check-line',
                'enabled' => ($settings['authorize_net_enabled'] ?? '0') === '1',
            ],
            'mollie' => [
                'name' => 'Mollie',
                'icon' => 'ri-global-line',
                'enabled' => ($settings['mollie_enabled'] ?? '0') === '1',
            ],
        ];

        foreach ($gateways as $key => $gateway) {
            if ($gateway['enabled']) {
                $enabledGateways[$key] = $gateway;
            }
        }

        if (empty($enabledGateways)) {
            return redirect()->route('subscriptions.index')
                ->with('error', 'No payment gateways are enabled. Please contact administrator.');
        }

        return view('shared.subscriptions.payment', [
            'plan' => $subscriptionPlan,
            'billingPeriod' => $billingPeriod,
            'price' => $price,
            'enabledGateways' => $enabledGateways
        ]);
    }

    /**
     * Process payment and create subscription.
     */
    public function process(Request $request, SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'billing_period' => ['required', 'string', 'in:monthly,yearly'],
            'payment_gateway' => ['required', 'string', 'in:stripe,paypal,razorpay,square,authorize_net,mollie'],
        ]);

        $billingPeriod = $validated['billing_period'];
        $paymentGateway = $validated['payment_gateway'];
        
        // Verify payment gateway is enabled
        $settings = Setting::getAllCached();
        $gatewayEnabled = $settings[$paymentGateway . '_enabled'] ?? '0';
        
        if ($gatewayEnabled !== '1') {
            $url = url('/subscriptions/' . $subscriptionPlan->uid . '/payment?billing_period=' . urlencode($billingPeriod));
            return redirect($url)->with('error', 'Selected payment gateway is not enabled.');
        }

        // Prevent downgrades via direct link: block if user already has a higher tier plan
        $highestActivePlan = $this->getUserHighestActivePlan($user);
        if ($highestActivePlan && $subscriptionPlan->isLowerTierThan($highestActivePlan)) {
            return redirect()->route('subscriptions.index')
                ->with('error', 'You already have a higher tier plan (' . $highestActivePlan->name . '). Downgrades are not allowed.');
        }

        // Get price based on billing period
        $price = $subscriptionPlan->getPrice($billingPeriod);
        
        // Calculate end date
        $startsAt = now();
        $endsAt = $billingPeriod === 'yearly' 
            ? now()->addYear() 
            : now()->addMonth();

        // DO NOT cancel existing subscriptions here - only cancel AFTER payment is confirmed
        // This prevents users from losing their current subscription if payment fails

        // Create payment record (status: pending until payment is confirmed)
        $payment = Payment::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $subscriptionPlan->id,
            'payment_gateway' => $paymentGateway,
            'amount' => $price,
            'currency' => 'USD', // You can make this configurable
            'status' => 'pending',
        ]);

        // Create new subscription (status: pending until payment is confirmed)
        $subscription = $user->subscriptions()->create([
            'subscription_plan_id' => $subscriptionPlan->id,
            'billing_period' => $billingPeriod,
            'price' => $price,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'pending', // Will be updated to 'active' after payment confirmation
            'payment_id' => $payment->id,
        ]);

        // Link payment to subscription
        $payment->update(['user_subscription_id' => $subscription->id]);

        // Process payment based on selected gateway
        switch ($paymentGateway) {
            case 'stripe':
                return $this->processStripePayment($payment, $subscription, $subscriptionPlan, $billingPeriod, $price);
            
            case 'paypal':
                return $this->processPayPalPayment($payment, $subscription, $subscriptionPlan, $billingPeriod, $price);
            
            case 'razorpay':
            case 'square':
            case 'authorize_net':
            case 'mollie':
                // TODO: Implement other payment gateways
                return redirect()->route('subscriptions.index')
                    ->with('error', 'Payment gateway "' . ucfirst($paymentGateway) . '" is not yet implemented.');
            
            default:
                return redirect()->route('subscriptions.index')
                    ->with('error', 'Invalid payment gateway selected.');
        }
    }

    /**
     * Process Stripe payment using Stripe Checkout (self-hosted).
     */
    private function processStripePayment($payment, $subscription, $subscriptionPlan, $billingPeriod, $price): RedirectResponse
    {
        try {
            $settings = Setting::getAllCached();
            
            // Get Stripe credentials
            $stripeSecretKey = $settings['stripe_secret_key'] ?? '';
            $stripeMode = $settings['stripe_mode'] ?? 'sandbox';
            
            if (empty($stripeSecretKey)) {
                return redirect()->route('subscriptions.index')
                    ->with('error', 'Stripe is not properly configured. Please contact administrator.');
            }

            // Decrypt secret key if encrypted
            try {
                $stripeSecretKey = Crypt::decryptString($stripeSecretKey);
            } catch (\Exception $e) {
                // If decryption fails, assume it's not encrypted
            }

            // Set Stripe API key
            Stripe::setApiKey($stripeSecretKey);

            // Create Stripe Checkout Session
            $checkoutSession = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($payment->currency),
                        'product_data' => [
                            'name' => $subscriptionPlan->name . ' Subscription',
                            'description' => ucfirst($billingPeriod) . ' billing period',
                        ],
                        'unit_amount' => (int)($price * 100), // Convert to cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('subscriptions.payment.success', [
                    'subscriptionPlan' => $subscriptionPlan->uid,
                    'payment' => $payment->uid,
                ]) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('subscriptions.payment.cancel', [
                    'subscriptionPlan' => $subscriptionPlan->uid,
                    'payment' => $payment->uid,
                ]),
                'metadata' => [
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscription->id,
                    'user_id' => Auth::id(),
                    'subscription_plan_id' => $subscriptionPlan->id,
                ],
                'customer_email' => Auth::user()->email,
            ]);

            // Store checkout session ID in payment record
            $payment->update([
                'gateway_transaction_id' => $checkoutSession->id,
                'metadata' => [
                    'checkout_session_id' => $checkoutSession->id,
                    'stripe_mode' => $stripeMode,
                ],
            ]);

            // Redirect to Stripe Checkout
            return redirect($checkoutSession->url);

        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'error' => $e->getError(),
            ]);

            return redirect()->route('subscriptions.index')
                ->with('error', 'Payment processing failed. Please try again or contact support.');
        } catch (\Exception $e) {
            Log::error('Stripe Payment Error: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('subscriptions.index')
                ->with('error', 'An unexpected error occurred while processing payment. Please try again or contact support.');
        }
    }

    /**
     * Process PayPal payment using PayPal REST API (direct HTTP requests to avoid SDK compatibility issues).
     */
    private function processPayPalPayment($payment, $subscription, $subscriptionPlan, $billingPeriod, $price): RedirectResponse
    {
        try {
            $settings = Setting::getAllCached();
            
            // Get PayPal credentials
            $paypalClientId = $settings['paypal_client_id'] ?? '';
            $paypalClientSecret = $settings['paypal_client_secret'] ?? '';
            $paypalMode = $settings['paypal_mode'] ?? 'sandbox';
            
            if (empty($paypalClientId) || empty($paypalClientSecret)) {
                return redirect()->route('subscriptions.index')
                    ->with('error', 'PayPal is not properly configured. Please contact administrator.');
            }

            // Decrypt client secret if encrypted
            try {
                $paypalClientSecret = Crypt::decryptString($paypalClientSecret);
            } catch (\Exception $e) {
                // If decryption fails, assume it's not encrypted
            }

            // Determine PayPal API base URL
            $paypalBaseUrl = $paypalMode === 'live' 
                ? 'https://api-m.paypal.com' 
                : 'https://api-m.sandbox.paypal.com';

            // Step 1: Get OAuth access token with increased timeout and retry logic
            $maxRetries = 3;
            $retryDelay = 2; // seconds
            $tokenResponse = null;
            $lastException = null;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $tokenResponse = \Illuminate\Support\Facades\Http::timeout(30) // Increased timeout to 30 seconds
                        ->retry(2, 1000) // Retry 2 times with 1 second delay
                        ->withBasicAuth($paypalClientId, $paypalClientSecret)
                        ->asForm()
                        ->post($paypalBaseUrl . '/v1/oauth2/token', [
                            'grant_type' => 'client_credentials',
                        ]);

                    if ($tokenResponse->successful()) {
                        break; // Success, exit retry loop
                    }
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    $lastException = $e;
                    Log::warning('PayPal OAuth Token Connection Attempt Failed', [
                        'attempt' => $attempt,
                        'max_retries' => $maxRetries,
                        'error' => $e->getMessage(),
                    ]);
                    
                    if ($attempt < $maxRetries) {
                        sleep($retryDelay); // Wait before retrying
                        continue;
                    }
                } catch (\Exception $e) {
                    $lastException = $e;
                    Log::error('PayPal OAuth Token Unexpected Error', [
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                    ]);
                    break; // Don't retry for unexpected errors
                }
            }

            if (!$tokenResponse || !$tokenResponse->successful()) {
                $errorMessage = 'Failed to connect to PayPal. ';
                if ($lastException) {
                    $errorMessage .= 'Connection timeout. Please check your internet connection and try again.';
                } else {
                    $errorMessage .= 'HTTP Status: ' . ($tokenResponse->status() ?? 'Unknown') . '. ';
                    $errorMessage .= 'Please check your PayPal credentials and try again.';
                }
                
                Log::error('PayPal OAuth Token Error After Retries', [
                    'status' => $tokenResponse->status() ?? 'N/A',
                    'body' => $tokenResponse->body() ?? 'N/A',
                    'last_exception' => $lastException ? $lastException->getMessage() : null,
                ]);
                
                throw new \Exception($errorMessage);
            }

            $accessToken = $tokenResponse->json()['access_token'];
            
            if (empty($accessToken)) {
                Log::error('PayPal OAuth Token Missing Access Token', [
                    'response' => $tokenResponse->json(),
                ]);
                throw new \Exception('Failed to retrieve PayPal access token. Please try again.');
            }

            // Step 2: Create payment
            // Ensure URLs are absolute and properly formatted
            // Note: PayPal requires HTTPS URLs in production, but allows HTTP for localhost in sandbox
            $returnUrl = url(route('subscriptions.payment.success', [
                'subscriptionPlan' => $subscriptionPlan->uid,
                'payment' => $payment->uid,
            ], false));
            
            // PayPal will append paymentId and PayerID as query parameters
            // Don't include placeholders in the URL
            $cancelUrl = url(route('subscriptions.payment.cancel', [
                'subscriptionPlan' => $subscriptionPlan->uid,
                'payment' => $payment->uid,
            ], false));
            
            // Log URLs for debugging
            Log::info('PayPal Redirect URLs', [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'paypal_mode' => $paypalMode,
            ]);

            // Ensure currency is exactly 3 characters (USD, EUR, etc.)
            $currency = strtoupper(substr($payment->currency ?? 'USD', 0, 3));
            if (strlen($currency) !== 3) {
                $currency = 'USD'; // Default to USD if invalid
            }

            // Format amount as string with 2 decimal places
            $amountTotal = number_format((float)$price, 2, '.', '');
            
            // Ensure amount is greater than 0
            if ((float)$amountTotal <= 0) {
                throw new \Exception('Payment amount must be greater than zero.');
            }

            $paymentData = [
                'intent' => 'sale',
                'payer' => [
                    'payment_method' => 'paypal',
                ],
                'redirect_urls' => [
                    'return_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                ],
                'transactions' => [
                    [
                        'amount' => [
                            'total' => $amountTotal,
                            'currency' => $currency,
                        ],
                        'description' => substr($subscriptionPlan->name . ' Subscription - ' . ucfirst($billingPeriod) . ' billing period', 0, 127),
                        // Note: invoice_number is optional and may cause issues in some PayPal configurations
                        // Removed invoice_number to avoid potential validation errors
                    ],
                ],
            ];

            Log::info('PayPal Payment Request Data', [
                'payment_data' => $paymentData,
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ]);

            // Create payment with increased timeout and retry logic
            $paymentResponse = null;
            $paymentLastException = null;
            
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $paymentResponse = \Illuminate\Support\Facades\Http::timeout(30) // Increased timeout to 30 seconds
                        ->retry(2, 1000) // Retry 2 times with 1 second delay
                        ->withToken($accessToken)
                        ->withHeaders([
                            'Content-Type' => 'application/json',
                        ])
                        ->post($paypalBaseUrl . '/v1/payments/payment', $paymentData);

                    if ($paymentResponse->successful()) {
                        break; // Success, exit retry loop
                    }
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    $paymentLastException = $e;
                    Log::warning('PayPal Payment Creation Connection Attempt Failed', [
                        'attempt' => $attempt,
                        'max_retries' => $maxRetries,
                        'error' => $e->getMessage(),
                    ]);
                    
                    if ($attempt < $maxRetries) {
                        sleep($retryDelay); // Wait before retrying
                        continue;
                    }
                } catch (\Exception $e) {
                    $paymentLastException = $e;
                    Log::error('PayPal Payment Creation Unexpected Error', [
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                    ]);
                    break; // Don't retry for unexpected errors
                }
            }
            
            if (!$paymentResponse) {
                $errorMessage = 'Failed to connect to PayPal payment API. ';
                if ($paymentLastException) {
                    $errorMessage .= 'Connection timeout. Please check your internet connection and try again.';
                } else {
                    $errorMessage .= 'Please try again later.';
                }
                throw new \Exception($errorMessage);
            }

            if (!$paymentResponse->successful()) {
                $errorBody = $paymentResponse->json();
                $errorMessage = $errorBody['message'] ?? 'Unknown error';
                $errorDetails = $errorBody['details'] ?? [];
                $errorName = $errorBody['name'] ?? 'Unknown';
                
                Log::error('PayPal Payment Creation Error', [
                    'status' => $paymentResponse->status(),
                    'status_code' => $paymentResponse->status(),
                    'body' => $paymentResponse->body(),
                    'raw_body' => $paymentResponse->body(),
                    'error_name' => $errorName,
                    'error_message' => $errorMessage,
                    'error_details' => $errorDetails,
                    'full_error_response' => $errorBody,
                    'payment_data' => $paymentData,
                ]);
                
                // Build detailed error message
                $detailedError = $errorMessage;
                if (!empty($errorDetails) && is_array($errorDetails)) {
                    $detailMessages = [];
                    foreach ($errorDetails as $detail) {
                        if (isset($detail['field']) && isset($detail['issue'])) {
                            $detailMessages[] = $detail['field'] . ': ' . $detail['issue'];
                        } elseif (is_string($detail)) {
                            $detailMessages[] = $detail;
                        }
                    }
                    if (!empty($detailMessages)) {
                        $detailedError .= ' Details: ' . implode('; ', $detailMessages);
                    }
                }
                
                throw new \Exception('Failed to create PayPal payment: ' . $detailedError);
            }

            $paypalPaymentData = $paymentResponse->json();
            $paypalPaymentId = $paypalPaymentData['id'];

            // Extract approval URL from links
            $approvalUrl = null;
            foreach ($paypalPaymentData['links'] ?? [] as $link) {
                if ($link['rel'] === 'approval_url') {
                    $approvalUrl = $link['href'];
                    break;
                }
            }

            if (empty($approvalUrl)) {
                Log::error('PayPal Approval URL Not Found', [
                    'payment_id' => $paypalPaymentId,
                    'links' => $paypalPaymentData['links'] ?? [],
                ]);
                throw new \Exception('Failed to get PayPal approval URL');
            }

            // Store PayPal payment ID in payment record
            $payment->update([
                'gateway_transaction_id' => $paypalPaymentId,
                'metadata' => [
                    'paypal_payment_id' => $paypalPaymentId,
                    'paypal_mode' => $paypalMode,
                    'approval_url' => $approvalUrl,
                    'payment_state' => $paypalPaymentData['state'] ?? 'created',
                ],
            ]);

            // Redirect to PayPal
            return redirect($approvalUrl);

        } catch (\Exception $e) {
            Log::error('PayPal Payment Error: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'payment_uid' => $payment->uid,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('subscriptions.payment.status', [
                'payment' => $payment->uid,
                'status' => 'failed',
            ])->with('error', 'An error occurred while processing PayPal payment. Please try again or contact support.');
        }
    }

    /**
     * Handle successful payment callback (Stripe or PayPal).
     */
    public function success(Request $request, SubscriptionPlan $subscriptionPlan, Payment $payment): RedirectResponse
    {
        try {
            // Verify payment belongs to authenticated user
            if ($payment->user_id !== Auth::id()) {
                Log::warning('Payment Success Callback: Unauthorized access attempt', [
                    'payment_id' => $payment->id,
                    'payment_uid' => $payment->uid,
                    'user_id' => Auth::id(),
                    'payment_user_id' => $payment->user_id,
                    'gateway' => $payment->payment_gateway,
                ]);
                return redirect()->route('subscriptions.index')
                    ->with('error', 'Unauthorized access.');
            }

            // Route to appropriate gateway handler
            switch ($payment->payment_gateway) {
                case 'stripe':
                    return $this->handleStripeSuccess($request, $payment, $subscriptionPlan);
                
                case 'paypal':
                    return $this->handlePayPalSuccess($request, $payment, $subscriptionPlan);
                
                default:
                    Log::error('Payment Success Callback: Unknown payment gateway', [
                        'payment_id' => $payment->id,
                        'payment_uid' => $payment->uid,
                        'gateway' => $payment->payment_gateway,
                    ]);
                    return redirect()->route('subscriptions.payment.status', [
                        'payment' => $payment->uid,
                        'status' => 'failed',
                    ])->with('error', 'Unknown payment gateway.');
            }
        } catch (\Exception $e) {
            Log::error('Payment Success Callback Error: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'payment_uid' => $payment->uid,
                'gateway' => $payment->payment_gateway,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('subscriptions.payment.status', [
                'payment' => $payment->uid,
                'status' => 'failed',
            ])->with('error', 'An error occurred while verifying payment. Please contact support.');
        }
    }

    /**
     * Handle successful Stripe payment callback.
     */
    private function handleStripeSuccess(Request $request, Payment $payment, SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $sessionId = $request->query('session_id');

        try {

            // Get session ID - prioritize query parameter, fallback to stored value
            $paymentMetadata = is_array($payment->metadata) ? $payment->metadata : [];
            $storedSessionId = $paymentMetadata['checkout_session_id'] ?? $payment->gateway_transaction_id;
            
            // Use query parameter if valid, otherwise use stored session ID
            if ($sessionId && $sessionId !== '{CHECKOUT_SESSION_ID}' && strpos($sessionId, 'cs_') === 0) {
                // Valid session ID from query parameter (Stripe session IDs start with 'cs_')
                $finalSessionId = $sessionId;
            } elseif ($storedSessionId && strpos($storedSessionId, 'cs_') === 0) {
                // Use stored session ID
                $finalSessionId = $storedSessionId;
                Log::info('Stripe Success Callback: Using stored session ID', [
                    'payment_id' => $payment->id,
                    'payment_uid' => $payment->uid,
                    'session_id_from_query' => $request->query('session_id'),
                    'stored_session_id' => $storedSessionId,
                ]);
            } else {
                Log::error('Stripe Success Callback: Could not determine valid session ID', [
                    'payment_id' => $payment->id,
                    'payment_uid' => $payment->uid,
                    'session_id_from_query' => $request->query('session_id'),
                    'stored_session_id' => $storedSessionId,
                    'payment_metadata' => $payment->metadata,
                    'gateway_transaction_id' => $payment->gateway_transaction_id,
                ]);
                return redirect()->route('subscriptions.index')
                    ->with('error', 'Invalid payment session. Please contact support.');
            }
            
            $sessionId = $finalSessionId;

            // Get Stripe credentials
            $settings = Setting::getAllCached();
            $stripeSecretKey = $settings['stripe_secret_key'] ?? '';
            
            if (empty($stripeSecretKey)) {
                Log::error('Stripe Success Callback: Missing Stripe secret key');
                return redirect()->route('subscriptions.index')
                    ->with('error', 'Payment gateway configuration error. Please contact support.');
            }
            
            try {
                $stripeSecretKey = Crypt::decryptString($stripeSecretKey);
            } catch (\Exception $e) {
                // If decryption fails, assume it's not encrypted
                Log::info('Stripe Success Callback: Secret key not encrypted, using as-is');
            }

            // CRITICAL: Check if payment is already completed to prevent double processing
            if ($payment->status === 'completed') {
                Log::info('Stripe Success Callback: Payment already completed, skipping processing', [
                    'payment_id' => $paymentId,
                    'session_id' => $sessionId,
                    'completed_at' => $payment->paid_at,
                ]);
                
                // Payment already processed, redirect to dashboard with success message
                $subscription = $payment->userSubscription;
                if ($subscription && $subscription->status === 'active') {
                    return redirect()->route('dashboard')
                        ->with('success', 'Your subscription is already active!');
                }
                
                // If subscription is not active but payment is completed, something went wrong
                return redirect()->route('subscriptions.index')
                    ->with('info', 'Payment was already processed. Please contact support if you have any issues.');
            }

            Stripe::setApiKey($stripeSecretKey);

            // Retrieve the checkout session from Stripe
            $checkoutSession = Session::retrieve($sessionId);

            if ($checkoutSession->payment_status === 'paid') {
                // Use database transaction to prevent race conditions
                DB::transaction(function () use ($payment, $checkoutSession, $subscriptionPlan) {
                    // Double-check payment status inside transaction (pessimistic locking)
                    $payment->refresh();
                    
                    if ($payment->status === 'completed') {
                        Log::warning('Stripe Success Callback: Payment was completed by another process', [
                            'payment_id' => $payment->id,
                        ]);
                        return; // Exit transaction, payment already processed
                    }
                    
                    // Update payment record
                    $payment->update([
                        'status' => 'completed',
                        'gateway_transaction_id' => $checkoutSession->payment_intent ?? $checkoutSession->id,
                        'paid_at' => now(),
                        'gateway_response' => [
                            'session_id' => $checkoutSession->id,
                            'payment_intent' => $checkoutSession->payment_intent ?? null,
                            'payment_status' => $checkoutSession->payment_status,
                            'customer_email' => $checkoutSession->customer_email ?? null,
                            'amount_total' => $checkoutSession->amount_total,
                            'currency' => $checkoutSession->currency,
                            'processed_at' => now()->toIso8601String(),
                        ],
                    ]);

                    // Update subscription status
                    $subscription = $payment->userSubscription;
                    if ($subscription) {
                        // Check if subscription is already active
                        if ($subscription->status !== 'active') {
                            // IMPORTANT: Cancel existing active subscriptions ONLY after payment is confirmed
                            Auth::user()->subscriptions()
                                ->where('status', 'active')
                                ->where('id', '!=', $subscription->id)
                                ->update([
                                    'status' => 'cancelled',
                                    'cancelled_at' => now(),
                                ]);
                            
                            $subscription->update(['status' => 'active']);
                            
                            // Update user's subscription plan
                            Auth::user()->update([
                                'subscription_plan_id' => $subscription->subscription_plan_id,
                            ]);
                            
                            Log::info('Stripe Success Callback: Payment and subscription activated', [
                                'payment_id' => $payment->id,
                                'subscription_id' => $subscription->id,
                                'user_id' => Auth::id(),
                            ]);
                        } else {
                            Log::info('Stripe Success Callback: Subscription already active', [
                                'payment_id' => $payment->id,
                                'subscription_id' => $subscription->id,
                            ]);
                        }
                    }
                });

                // Check if payment was actually updated (in case it was already completed)
                $payment->refresh();
                if ($payment->status === 'completed') {
                    // Redirect to payment status page
                    return redirect()->route('subscriptions.payment.status', [
                        'payment' => $payment->uid,
                        'status' => 'success',
                    ]);
                } else {
                    // Payment was already processed by another request
                    return redirect()->route('subscriptions.payment.status', [
                        'payment' => $payment->uid,
                        'status' => 'success',
                    ]);
                }
            } else {
                Log::warning('Stripe Success Callback: Payment status is not paid', [
                    'payment_id' => $payment->id,
                    'payment_uid' => $payment->uid,
                    'session_id' => $sessionId,
                    'payment_status' => $checkoutSession->payment_status ?? 'unknown',
                ]);
                
                // Update payment status to failed
                $payment->update([
                    'status' => 'failed',
                    'gateway_response' => [
                        'session_id' => $checkoutSession->id,
                        'payment_status' => $checkoutSession->payment_status ?? 'unknown',
                        'error' => 'Payment status is not paid',
                    ],
                ]);
                
                return redirect()->route('subscriptions.payment.status', [
                    'payment' => $payment->uid,
                    'status' => 'failed',
                ])->with('error', 'Payment was not completed. Please try again.');
            }

        } catch (\Exception $e) {
            Log::error('Stripe Success Callback Error: ' . $e->getMessage(), [
                'payment_id' => $payment->id ?? 'unknown',
                'payment_uid' => $payment->uid ?? 'unknown',
                'session_id' => $sessionId ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);

            // Try to update payment status to failed if payment record exists
            try {
                if (isset($payment) && $payment) {
                    $payment->update([
                        'status' => 'failed',
                        'gateway_response' => [
                            'error' => $e->getMessage(),
                            'error_type' => get_class($e),
                        ],
                    ]);
                    
                    return redirect()->route('subscriptions.payment.status', [
                        'payment' => $payment->uid,
                        'status' => 'failed',
                    ])->with('error', 'An error occurred while verifying payment. Please contact support.');
                }
            } catch (\Exception $updateError) {
                Log::error('Failed to update payment status: ' . $updateError->getMessage());
            }

            return redirect()->route('subscriptions.index')
                ->with('error', 'An error occurred while verifying payment. Please contact support.');
        }
    }

    /**
     * Handle successful PayPal payment callback.
     */
    private function handlePayPalSuccess(Request $request, Payment $payment, SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $paymentId = $request->query('paymentId');
        $payerId = $request->query('PayerID');

        try {
            if (empty($paymentId) || empty($payerId)) {
                Log::error('PayPal Success Callback: Missing paymentId or PayerID', [
                    'payment_id' => $payment->id,
                    'payment_uid' => $payment->uid,
                    'paymentId' => $paymentId,
                    'PayerID' => $payerId,
                ]);
                return redirect()->route('subscriptions.payment.status', [
                    'payment' => $payment->uid,
                    'status' => 'failed',
                ])->with('error', 'Invalid PayPal payment parameters.');
            }

            // CRITICAL: Check if payment is already completed to prevent double processing
            if ($payment->status === 'completed') {
                Log::info('PayPal Success Callback: Payment already completed, skipping processing', [
                    'payment_id' => $payment->id,
                    'payment_uid' => $payment->uid,
                    'paypal_payment_id' => $paymentId,
                    'completed_at' => $payment->paid_at,
                ]);
                
                return redirect()->route('subscriptions.payment.status', [
                    'payment' => $payment->uid,
                    'status' => 'success',
                ]);
            }

            // Get PayPal credentials
            $settings = Setting::getAllCached();
            $paypalClientId = $settings['paypal_client_id'] ?? '';
            $paypalClientSecret = $settings['paypal_client_secret'] ?? '';
            $paypalMode = $settings['paypal_mode'] ?? 'sandbox';
            
            if (empty($paypalClientId) || empty($paypalClientSecret)) {
                Log::error('PayPal Success Callback: Missing PayPal credentials');
                return redirect()->route('subscriptions.payment.status', [
                    'payment' => $payment->uid,
                    'status' => 'failed',
                ])->with('error', 'PayPal configuration error. Please contact support.');
            }
            
            try {
                $paypalClientSecret = Crypt::decryptString($paypalClientSecret);
            } catch (\Exception $e) {
                // If decryption fails, assume it's not encrypted
            }

            // Determine PayPal API base URL
            $paypalBaseUrl = $paypalMode === 'live' 
                ? 'https://api-m.paypal.com' 
                : 'https://api-m.sandbox.paypal.com';

            // Step 1: Get OAuth access token with retry logic
            $maxRetries = 3;
            $retryDelay = 2;
            $tokenResponse = null;
            $lastException = null;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $tokenResponse = \Illuminate\Support\Facades\Http::timeout(30)
                        ->retry(2, 1000)
                        ->withBasicAuth($paypalClientId, $paypalClientSecret)
                        ->asForm()
                        ->post($paypalBaseUrl . '/v1/oauth2/token', [
                            'grant_type' => 'client_credentials',
                        ]);

                    if ($tokenResponse->successful()) {
                        break;
                    }
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    $lastException = $e;
                    if ($attempt < $maxRetries) {
                        sleep($retryDelay);
                        continue;
                    }
                } catch (\Exception $e) {
                    $lastException = $e;
                    break;
                }
            }

            if (!$tokenResponse || !$tokenResponse->successful()) {
                Log::error('PayPal OAuth Token Error in Success Callback', [
                    'status' => $tokenResponse->status() ?? 'N/A',
                    'body' => $tokenResponse->body() ?? 'N/A',
                    'last_exception' => $lastException ? $lastException->getMessage() : null,
                ]);
                throw new \Exception('Failed to authenticate with PayPal. Please try again.');
            }

            $accessToken = $tokenResponse->json()['access_token'];
            
            if (empty($accessToken)) {
                throw new \Exception('Failed to retrieve PayPal access token.');
            }

            // Step 2: Execute the payment
            $executionData = [
                'payer_id' => $payerId,
            ];

            // Use database transaction to prevent race conditions
            DB::transaction(function () use ($paypalBaseUrl, $accessToken, $paymentId, $executionData, $payment, $payerId) {
                // Double-check payment status inside transaction
                $payment->refresh();
                
                if ($payment->status === 'completed') {
                    Log::warning('PayPal Success Callback: Payment was completed by another process', [
                        'payment_id' => $payment->id,
                    ]);
                    return;
                }

                // Execute the payment with retry logic
                $executeResponse = null;
                $executeLastException = null;
                
                for ($executeAttempt = 1; $executeAttempt <= $maxRetries; $executeAttempt++) {
                    try {
                        $executeResponse = \Illuminate\Support\Facades\Http::timeout(30)
                            ->retry(2, 1000)
                            ->withToken($accessToken)
                            ->withHeaders([
                                'Content-Type' => 'application/json',
                            ])
                            ->post($paypalBaseUrl . '/v1/payments/payment/' . $paymentId . '/execute', $executionData);

                        if ($executeResponse->successful()) {
                            break;
                        }
                    } catch (\Illuminate\Http\Client\ConnectionException $e) {
                        $executeLastException = $e;
                        if ($executeAttempt < $maxRetries) {
                            sleep($retryDelay);
                            continue;
                        }
                    } catch (\Exception $e) {
                        $executeLastException = $e;
                        break;
                    }
                }
                
                if (!$executeResponse) {
                    throw new \Exception('Failed to connect to PayPal payment execution API. Connection timeout.');
                }

                if (!$executeResponse->successful()) {
                    $errorData = $executeResponse->json();
                    Log::error('PayPal Payment Execution Error', [
                        'status' => $executeResponse->status(),
                        'body' => $executeResponse->body(),
                        'payment_id' => $paymentId,
                    ]);
                    throw new \Exception('Failed to execute PayPal payment: ' . ($errorData['message'] ?? 'Unknown error'));
                }

                $result = $executeResponse->json();

                if ($result['state'] === 'approved') {
                    // Update payment record
                    $payment->update([
                        'status' => 'completed',
                        'gateway_transaction_id' => $paymentId,
                        'paid_at' => now(),
                        'gateway_response' => [
                            'paypal_payment_id' => $paymentId,
                            'payer_id' => $payerId,
                            'state' => $result['state'],
                            'transactions' => $result['transactions'] ?? [],
                            'processed_at' => now()->toIso8601String(),
                        ],
                    ]);

                    // Update subscription status
                    $subscription = $payment->userSubscription;
                    if ($subscription && $subscription->status !== 'active') {
                        // IMPORTANT: Cancel existing active subscriptions ONLY after payment is confirmed
                        Auth::user()->subscriptions()
                            ->where('status', 'active')
                            ->where('id', '!=', $subscription->id)
                            ->update([
                                'status' => 'cancelled',
                                'cancelled_at' => now(),
                            ]);
                        
                        $subscription->update(['status' => 'active']);
                        
                        // Update user's subscription plan
                        Auth::user()->update([
                            'subscription_plan_id' => $subscription->subscription_plan_id,
                        ]);
                        
                        Log::info('PayPal Success Callback: Payment and subscription activated', [
                            'payment_id' => $payment->id,
                            'subscription_id' => $subscription->id,
                            'user_id' => Auth::id(),
                        ]);
                    }
                } else {
                    throw new \Exception('PayPal payment state is not approved: ' . ($result['state'] ?? 'unknown'));
                }
            });

            // Check if payment was actually updated
            $payment->refresh();
            if ($payment->status === 'completed') {
                return redirect()->route('subscriptions.payment.status', [
                    'payment' => $payment->uid,
                    'status' => 'success',
                ]);
            } else {
                return redirect()->route('subscriptions.payment.status', [
                    'payment' => $payment->uid,
                    'status' => 'failed',
                ])->with('error', 'Payment verification failed. Please contact support.');
            }

        } catch (PayPalConnectionException $e) {
            Log::error('PayPal Success Callback Connection Error: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'payment_uid' => $payment->uid,
                'paymentId' => $paymentId ?? 'unknown',
                'data' => $e->getData(),
            ]);

            $payment->update([
                'status' => 'failed',
                'gateway_response' => [
                    'error' => $e->getMessage(),
                    'error_type' => 'PayPalConnectionException',
                ],
            ]);

            return redirect()->route('subscriptions.payment.status', [
                'payment' => $payment->uid,
                'status' => 'failed',
            ])->with('error', 'PayPal connection error. Please try again.');
        } catch (\Exception $e) {
            Log::error('PayPal Success Callback Error: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'payment_uid' => $payment->uid,
                'paymentId' => $paymentId ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);

            $payment->update([
                'status' => 'failed',
                'gateway_response' => [
                    'error' => $e->getMessage(),
                    'error_type' => get_class($e),
                ],
            ]);

            return redirect()->route('subscriptions.payment.status', [
                'payment' => $payment->uid,
                'status' => 'failed',
            ])->with('error', 'An error occurred while verifying PayPal payment. Please contact support.');
        }
    }

    /**
     * Handle cancelled payment callback.
     */
    public function cancel(Request $request, SubscriptionPlan $subscriptionPlan, Payment $payment): RedirectResponse
    {
        try {
            // Verify payment belongs to authenticated user
            if ($payment->user_id === Auth::id()) {
                // Update payment status to cancelled
                $payment->update([
                    'status' => 'cancelled',
                    'gateway_response' => [
                        'cancelled_at' => now()->toIso8601String(),
                        'reason' => 'User cancelled checkout',
                    ],
                ]);

                // Cancel associated subscription
                $subscription = $payment->userSubscription;
                if ($subscription) {
                    $subscription->update(['status' => 'cancelled']);
                }
            }
        } catch (\Exception $e) {
            Log::error('Stripe Cancel Callback Error: ' . $e->getMessage());
        }

        return redirect()->route('subscriptions.payment.status', [
            'payment' => $payment->uid,
            'status' => 'cancelled',
        ]);
    }

    /**
     * Display payment status page.
     */
    public function status(Request $request, Payment $payment): View
    {
        $statusFromQuery = $request->query('status', 'unknown'); // success, failed, cancelled
        
        // Verify payment belongs to authenticated user
        if ($payment->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to payment.');
        }
        
        // Load relationships
        $payment->load(['subscriptionPlan', 'userSubscription.subscriptionPlan']);
        
        $subscription = $payment->userSubscription;
        $message = session('error') ?? session('info') ?? null;

        // Verify payment status with the actual payment gateway
        $verifiedStatus = $this->verifyPaymentStatusWithGateway($payment);
        
        // Use verified status from gateway, fallback to query parameter, then database status
        if ($verifiedStatus !== 'unknown') {
            $status = $verifiedStatus;
        } elseif ($statusFromQuery !== 'unknown') {
            $status = $statusFromQuery;
        } else {
            // Fallback to database status
            if ($payment->status === 'completed') {
                $status = 'success';
            } elseif ($payment->status === 'failed') {
                $status = 'failed';
            } elseif ($payment->status === 'cancelled') {
                $status = 'cancelled';
            } else {
                $status = 'unknown';
            }
        }

        return view('shared.subscriptions.payment-status', compact('status', 'payment', 'subscription', 'message'));
    }

    /**
     * Verify payment status with the actual payment gateway.
     */
    private function verifyPaymentStatusWithGateway(Payment $payment): string
    {
        try {
            switch ($payment->payment_gateway) {
                case 'stripe':
                    return $this->verifyStripePaymentStatus($payment);
                
                case 'paypal':
                    return $this->verifyPayPalPaymentStatus($payment);
                
                case 'razorpay':
                case 'square':
                case 'authorize_net':
                case 'mollie':
                    // TODO: Implement verification for other payment gateways
                    Log::info('Payment gateway verification not implemented', [
                        'payment_id' => $payment->id,
                        'payment_uid' => $payment->uid,
                        'gateway' => $payment->payment_gateway,
                    ]);
                    return 'unknown';
                
                default:
                    Log::warning('Unknown payment gateway', [
                        'payment_id' => $payment->id,
                        'payment_uid' => $payment->uid,
                        'gateway' => $payment->payment_gateway,
                    ]);
                    return 'unknown';
            }
        } catch (\Exception $e) {
            Log::error('Payment Gateway Verification Error: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'payment_uid' => $payment->uid,
                'gateway' => $payment->payment_gateway,
                'trace' => $e->getTraceAsString(),
            ]);
            return 'unknown';
        }
    }

    /**
     * Verify Stripe payment status by retrieving checkout session from Stripe API.
     */
    private function verifyStripePaymentStatus(Payment $payment): string
    {
        try {
            // Get Stripe credentials
            $settings = Setting::getAllCached();
            $stripeSecretKey = $settings['stripe_secret_key'] ?? '';
            
            if (empty($stripeSecretKey)) {
                Log::warning('Stripe secret key not configured for payment verification', [
                    'payment_id' => $payment->id,
                    'payment_uid' => $payment->uid,
                ]);
                return 'unknown';
            }
            
            try {
                $stripeSecretKey = Crypt::decryptString($stripeSecretKey);
            } catch (\Exception $e) {
                // If decryption fails, assume it's not encrypted
            }

            Stripe::setApiKey($stripeSecretKey);

            // Get checkout session ID from payment metadata or gateway_transaction_id
            $paymentMetadata = is_array($payment->metadata) ? $payment->metadata : [];
            $checkoutSessionId = $paymentMetadata['checkout_session_id'] ?? $payment->gateway_transaction_id;

            if (empty($checkoutSessionId) || strpos($checkoutSessionId, 'cs_') !== 0) {
                Log::warning('Invalid Stripe checkout session ID', [
                    'payment_id' => $payment->id,
                    'payment_uid' => $payment->uid,
                    'checkout_session_id' => $checkoutSessionId,
                ]);
                return 'unknown';
            }

            // Retrieve the checkout session from Stripe
            $checkoutSession = Session::retrieve($checkoutSessionId);

            // Determine status based on Stripe's payment status
            $stripeStatus = $checkoutSession->payment_status ?? 'unknown';
            
            // Map Stripe status to our status
            $verifiedStatus = 'unknown';
            if ($stripeStatus === 'paid') {
                $verifiedStatus = 'success';
                
                // Update payment record if it's not already completed
                if ($payment->status !== 'completed') {
                    DB::transaction(function () use ($payment, $checkoutSession) {
                        $payment->refresh();
                        
                        if ($payment->status !== 'completed') {
                            $payment->update([
                                'status' => 'completed',
                                'gateway_transaction_id' => $checkoutSession->payment_intent ?? $checkoutSession->id,
                                'paid_at' => now(),
                                'gateway_response' => array_merge(
                                    is_array($payment->gateway_response) ? $payment->gateway_response : [],
                                    [
                                        'session_id' => $checkoutSession->id,
                                        'payment_intent' => $checkoutSession->payment_intent ?? null,
                                        'payment_status' => $checkoutSession->payment_status,
                                        'verified_at' => now()->toIso8601String(),
                                    ]
                                ),
                            ]);

                            // Update subscription status if exists
                            $subscription = $payment->userSubscription;
                            if ($subscription && $subscription->status !== 'active') {
                                // IMPORTANT: Cancel existing active subscriptions ONLY after payment is confirmed
                                Auth::user()->subscriptions()
                                    ->where('status', 'active')
                                    ->where('id', '!=', $subscription->id)
                                    ->update([
                                        'status' => 'cancelled',
                                        'cancelled_at' => now(),
                                    ]);
                                
                                $subscription->update(['status' => 'active']);
                                
                                // Update user's subscription plan
                                Auth::user()->update([
                                    'subscription_plan_id' => $subscription->subscription_plan_id,
                                ]);
                            }
                        }
                    });
                }
            } elseif ($stripeStatus === 'unpaid' || $stripeStatus === 'no_payment_required') {
                // Payment not completed
                if ($payment->status === 'pending') {
                    $verifiedStatus = 'failed';
                    
                    // Update payment status if still pending
                    $payment->update([
                        'status' => 'failed',
                        'gateway_response' => array_merge(
                            is_array($payment->gateway_response) ? $payment->gateway_response : [],
                            [
                                'session_id' => $checkoutSession->id,
                                'payment_status' => $checkoutSession->payment_status,
                                'verified_at' => now()->toIso8601String(),
                                'error' => 'Payment not completed on Stripe',
                            ]
                        ),
                    ]);
                } else {
                    $verifiedStatus = 'failed';
                }
            } else {
                // Unknown status from Stripe
                Log::warning('Unknown Stripe payment status', [
                    'payment_id' => $payment->id,
                    'payment_uid' => $payment->uid,
                    'stripe_status' => $stripeStatus,
                    'checkout_session_id' => $checkoutSessionId,
                ]);
                return 'unknown';
            }

            return $verifiedStatus;

        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error during payment verification: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'payment_uid' => $payment->uid,
                'error' => $e->getError(),
            ]);
            return 'unknown';
        } catch (\Exception $e) {
            Log::error('Stripe Payment Verification Error: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'payment_uid' => $payment->uid,
                'trace' => $e->getTraceAsString(),
            ]);
            return 'unknown';
        }
    }

    /**
     * Verify PayPal payment status by retrieving payment from PayPal API.
     */
    private function verifyPayPalPaymentStatus(Payment $payment): string
    {
        try {
            // Get PayPal credentials
            $settings = Setting::getAllCached();
            $paypalClientId = $settings['paypal_client_id'] ?? '';
            $paypalClientSecret = $settings['paypal_client_secret'] ?? '';
            $paypalMode = $settings['paypal_mode'] ?? 'sandbox';
            
            if (empty($paypalClientId) || empty($paypalClientSecret)) {
                Log::warning('PayPal credentials not configured for payment verification', [
                    'payment_id' => $payment->id,
                    'payment_uid' => $payment->uid,
                ]);
                return 'unknown';
            }
            
            try {
                $paypalClientSecret = Crypt::decryptString($paypalClientSecret);
            } catch (\Exception $e) {
                // If decryption fails, assume it's not encrypted
            }

            // Determine PayPal API base URL
            $paypalBaseUrl = $paypalMode === 'live' 
                ? 'https://api-m.paypal.com' 
                : 'https://api-m.sandbox.paypal.com';

            // Get PayPal payment ID from payment record
            $paymentMetadata = is_array($payment->metadata) ? $payment->metadata : [];
            $paypalPaymentId = $paymentMetadata['paypal_payment_id'] ?? $payment->gateway_transaction_id;

            if (empty($paypalPaymentId)) {
                Log::warning('Invalid PayPal payment ID', [
                    'payment_id' => $payment->id,
                    'payment_uid' => $payment->uid,
                    'paypal_payment_id' => $paypalPaymentId,
                ]);
                return 'unknown';
            }

            // Get OAuth access token with retry logic
            $maxRetries = 3;
            $retryDelay = 2;
            $tokenResponse = null;
            
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $tokenResponse = \Illuminate\Support\Facades\Http::timeout(30)
                        ->retry(2, 1000)
                        ->withBasicAuth($paypalClientId, $paypalClientSecret)
                        ->asForm()
                        ->post($paypalBaseUrl . '/v1/oauth2/token', [
                            'grant_type' => 'client_credentials',
                        ]);

                    if ($tokenResponse->successful()) {
                        break;
                    }
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    if ($attempt < $maxRetries) {
                        sleep($retryDelay);
                        continue;
                    }
                } catch (\Exception $e) {
                    break;
                }
            }

            if (!$tokenResponse || !$tokenResponse->successful()) {
                Log::error('PayPal OAuth Token Error in Verification', [
                    'status' => $tokenResponse->status() ?? 'N/A',
                    'body' => $tokenResponse->body() ?? 'N/A',
                ]);
                return 'unknown';
            }

            $accessToken = $tokenResponse->json()['access_token'] ?? null;
            
            if (empty($accessToken)) {
                return 'unknown';
            }

            // Retrieve the payment from PayPal with retry logic
            $paymentResponse = null;
            
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $paymentResponse = \Illuminate\Support\Facades\Http::timeout(30)
                        ->retry(2, 1000)
                        ->withToken($accessToken)
                        ->withHeaders([
                            'Content-Type' => 'application/json',
                        ])
                        ->get($paypalBaseUrl . '/v1/payments/payment/' . $paypalPaymentId);
                        
                    if ($paymentResponse->successful()) {
                        break;
                    }
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    if ($attempt < $maxRetries) {
                        sleep($retryDelay);
                        continue;
                    }
                } catch (\Exception $e) {
                    break;
                }
            }
            
            if (!$paymentResponse) {
                Log::error('PayPal Payment Retrieval Connection Timeout', [
                    'paypal_payment_id' => $paypalPaymentId,
                ]);
                return 'unknown';
            }

            if (!$paymentResponse->successful()) {
                Log::error('PayPal Payment Retrieval Error', [
                    'status' => $paymentResponse->status(),
                    'body' => $paymentResponse->body(),
                    'paypal_payment_id' => $paypalPaymentId,
                ]);
                return 'unknown';
            }

            $paypalPaymentData = $paymentResponse->json();

            // Determine status based on PayPal's payment state
            $paypalState = $paypalPaymentData['state'] ?? 'unknown';
            
            // Map PayPal state to our status
            $verifiedStatus = 'unknown';
            if ($paypalState === 'approved') {
                $verifiedStatus = 'success';
                
                // Update payment record if it's not already completed
                if ($payment->status !== 'completed') {
                    DB::transaction(function () use ($payment, $paypalPayment, $paypalPaymentId) {
                        $payment->refresh();
                        
                        if ($payment->status !== 'completed') {
                            $payment->update([
                                'status' => 'completed',
                                'gateway_transaction_id' => $paypalPaymentId,
                                'paid_at' => now(),
                                'gateway_response' => array_merge(
                                    is_array($payment->gateway_response) ? $payment->gateway_response : [],
                                    [
                                        'paypal_payment_id' => $paypalPaymentId,
                                        'state' => $paypalPayment->getState(),
                                        'verified_at' => now()->toIso8601String(),
                                    ]
                                ),
                            ]);

                            // Update subscription status if exists
                            $subscription = $payment->userSubscription;
                            if ($subscription && $subscription->status !== 'active') {
                                // IMPORTANT: Cancel existing active subscriptions ONLY after payment is confirmed
                                Auth::user()->subscriptions()
                                    ->where('status', 'active')
                                    ->where('id', '!=', $subscription->id)
                                    ->update([
                                        'status' => 'cancelled',
                                        'cancelled_at' => now(),
                                    ]);
                                
                                $subscription->update(['status' => 'active']);
                                
                                // Update user's subscription plan
                                Auth::user()->update([
                                    'subscription_plan_id' => $subscription->subscription_plan_id,
                                ]);
                            }
                        }
                    });
                }
            } elseif ($paypalState === 'failed' || $paypalState === 'canceled') {
                // Payment failed or cancelled
                if ($payment->status === 'pending') {
                    $verifiedStatus = 'failed';
                    
                    // Update payment status if still pending
                    $payment->update([
                        'status' => 'failed',
                        'gateway_response' => array_merge(
                            is_array($payment->gateway_response) ? $payment->gateway_response : [],
                            [
                                'paypal_payment_id' => $paypalPaymentId,
                                'state' => $paypalState,
                                'verified_at' => now()->toIso8601String(),
                                'error' => 'Payment not completed on PayPal',
                            ]
                        ),
                    ]);
                } else {
                    $verifiedStatus = 'failed';
                }
            } else {
                // Unknown status from PayPal
                Log::warning('Unknown PayPal payment state', [
                    'payment_id' => $payment->id,
                    'payment_uid' => $payment->uid,
                    'paypal_state' => $paypalState,
                    'paypal_payment_id' => $paypalPaymentId,
                ]);
                return 'unknown';
            }

            return $verifiedStatus;

        } catch (PayPalConnectionException $e) {
            Log::error('PayPal API Error during payment verification: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'payment_uid' => $payment->uid,
                'error' => $e->getData(),
            ]);
            return 'unknown';
        } catch (\Exception $e) {
            Log::error('PayPal Payment Verification Error: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'payment_uid' => $payment->uid,
                'trace' => $e->getTraceAsString(),
            ]);
            return 'unknown';
        }
    }
    /**
     * Get the highest tier active subscription plan for a user.
     */
    private function getUserHighestActivePlan($user): ?SubscriptionPlan
    {
        $activePlans = $user->subscriptions()
            ->where('status', 'active')
            ->with('subscriptionPlan')
            ->get()
            ->pluck('subscriptionPlan')
            ->filter();

        if ($activePlans->isEmpty()) {
            return null;
        }

        $sorted = $activePlans->sort(function ($a, $b) {
            if ($a->isHigherTierThan($b)) {
                return -1;
            }
            if ($a->isLowerTierThan($b)) {
                return 1;
            }
            return 0;
        });

        return $sorted->first();
    }
}

