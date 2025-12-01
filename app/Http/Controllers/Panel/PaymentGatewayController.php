<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;

class PaymentGatewayController extends Controller
{
    /**
     * Display the payment gateway configuration page.
     */
    public function index(): View
    {
        $settings = Setting::getAllCached();
        
        // Decrypt sensitive fields for display (if they exist and are encrypted)
        $encryptedFields = [
            'stripe_secret_key', 
            'stripe_webhook_secret',
            'paypal_client_secret',
            'razorpay_key_secret',
            'square_access_token',
            'authorize_net_transaction_key',
            'mollie_api_key'
        ];
        
        foreach ($encryptedFields as $field) {
            if (isset($settings[$field]) && !empty($settings[$field])) {
                try {
                    $settings[$field] = Crypt::decryptString($settings[$field]);
                } catch (\Exception $e) {
                    // If decryption fails, it might not be encrypted yet, keep original value
                    $settings[$field] = $settings[$field];
                }
            } else {
                $settings[$field] = '';
            }
        }
        
        return view('panel.payment-gateway.index', compact('settings'));
    }

    /**
     * Update payment gateway configuration.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Stripe Settings
            'stripe_publishable_key' => ['nullable', 'string', 'max:255'],
            'stripe_secret_key' => ['nullable', 'string', 'max:255'],
            'stripe_webhook_secret' => ['nullable', 'string', 'max:255'],
            'stripe_mode' => ['nullable', 'string', 'in:live,sandbox'],
            
            // PayPal Settings
            'paypal_client_id' => ['nullable', 'string', 'max:255'],
            'paypal_client_secret' => ['nullable', 'string', 'max:255'],
            'paypal_mode' => ['nullable', 'string', 'in:live,sandbox'],
            
            // Razorpay Settings
            'razorpay_key_id' => ['nullable', 'string', 'max:255'],
            'razorpay_key_secret' => ['nullable', 'string', 'max:255'],
            'razorpay_mode' => ['nullable', 'string', 'in:live,sandbox'],
            
            // Square Settings
            'square_application_id' => ['nullable', 'string', 'max:255'],
            'square_access_token' => ['nullable', 'string', 'max:255'],
            'square_location_id' => ['nullable', 'string', 'max:255'],
            'square_mode' => ['nullable', 'string', 'in:live,sandbox'],
            
            // Authorize.Net Settings
            'authorize_net_api_login_id' => ['nullable', 'string', 'max:255'],
            'authorize_net_transaction_key' => ['nullable', 'string', 'max:255'],
            'authorize_net_mode' => ['nullable', 'string', 'in:live,sandbox'],
            
            // Mollie Settings
            'mollie_api_key' => ['nullable', 'string', 'max:255'],
            'mollie_mode' => ['nullable', 'string', 'in:live,sandbox'],
        ]);

        // Save Stripe settings
        Setting::set('stripe_publishable_key', $validated['stripe_publishable_key'] ?? '');
        if (!empty($validated['stripe_secret_key'])) {
            Setting::set('stripe_secret_key', Crypt::encryptString($validated['stripe_secret_key']));
        }
        if (!empty($validated['stripe_webhook_secret'])) {
            Setting::set('stripe_webhook_secret', Crypt::encryptString($validated['stripe_webhook_secret']));
        }
        Setting::set('stripe_mode', $validated['stripe_mode'] ?? 'sandbox');

        // Save PayPal settings
        Setting::set('paypal_client_id', $validated['paypal_client_id'] ?? '');
        if (!empty($validated['paypal_client_secret'])) {
            Setting::set('paypal_client_secret', Crypt::encryptString($validated['paypal_client_secret']));
        }
        Setting::set('paypal_mode', $validated['paypal_mode'] ?? 'sandbox');

        // Save Razorpay settings
        Setting::set('razorpay_key_id', $validated['razorpay_key_id'] ?? '');
        if (!empty($validated['razorpay_key_secret'])) {
            Setting::set('razorpay_key_secret', Crypt::encryptString($validated['razorpay_key_secret']));
        }
        Setting::set('razorpay_mode', $validated['razorpay_mode'] ?? 'sandbox');

        // Save Square settings
        Setting::set('square_application_id', $validated['square_application_id'] ?? '');
        if (!empty($validated['square_access_token'])) {
            Setting::set('square_access_token', Crypt::encryptString($validated['square_access_token']));
        }
        Setting::set('square_location_id', $validated['square_location_id'] ?? '');
        Setting::set('square_mode', $validated['square_mode'] ?? 'sandbox');

        // Save Authorize.Net settings
        Setting::set('authorize_net_api_login_id', $validated['authorize_net_api_login_id'] ?? '');
        if (!empty($validated['authorize_net_transaction_key'])) {
            Setting::set('authorize_net_transaction_key', Crypt::encryptString($validated['authorize_net_transaction_key']));
        }
        Setting::set('authorize_net_mode', $validated['authorize_net_mode'] ?? 'sandbox');

        // Save Mollie settings
        if (!empty($validated['mollie_api_key'])) {
            Setting::set('mollie_api_key', Crypt::encryptString($validated['mollie_api_key']));
        }
        Setting::set('mollie_mode', $validated['mollie_mode'] ?? 'sandbox');

        return redirect()->route('panel.payment-gateway.index')
            ->with('success', 'Payment gateway configuration updated successfully.');
    }
}
