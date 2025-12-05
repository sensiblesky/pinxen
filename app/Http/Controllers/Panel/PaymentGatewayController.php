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
            'stripe_enabled' => ['boolean'],
            'stripe_publishable_key' => ['nullable', 'string', 'max:255'],
            'stripe_secret_key' => ['nullable', 'string', 'max:255'],
            'stripe_webhook_secret' => ['nullable', 'string', 'max:255'],
            'stripe_mode' => ['nullable', 'string', 'in:live,sandbox'],
            
            // PayPal Settings
            'paypal_enabled' => ['boolean'],
            'paypal_client_id' => ['nullable', 'string', 'max:255'],
            'paypal_client_secret' => ['nullable', 'string', 'max:255'],
            'paypal_mode' => ['nullable', 'string', 'in:live,sandbox'],
            
            // Razorpay, Square, Authorize.Net, and Mollie are coming soon - validation not needed
            // These fields are disabled in the UI and will be ignored
        ]);

        // Save Stripe settings
        Setting::set('stripe_enabled', $request->has('stripe_enabled') ? '1' : '0', 'boolean');
        Setting::set('stripe_publishable_key', $validated['stripe_publishable_key'] ?? '');
        if (!empty($validated['stripe_secret_key'])) {
            Setting::set('stripe_secret_key', Crypt::encryptString($validated['stripe_secret_key']));
        }
        if (!empty($validated['stripe_webhook_secret'])) {
            Setting::set('stripe_webhook_secret', Crypt::encryptString($validated['stripe_webhook_secret']));
        }
        Setting::set('stripe_mode', $validated['stripe_mode'] ?? 'sandbox');

        // Save PayPal settings
        Setting::set('paypal_enabled', $request->has('paypal_enabled') ? '1' : '0', 'boolean');
        Setting::set('paypal_client_id', $validated['paypal_client_id'] ?? '');
        if (!empty($validated['paypal_client_secret'])) {
            Setting::set('paypal_client_secret', Crypt::encryptString($validated['paypal_client_secret']));
        }
        Setting::set('paypal_mode', $validated['paypal_mode'] ?? 'sandbox');

        // Razorpay, Square, Authorize.Net, and Mollie are coming soon - do not save their settings
        // These gateways are disabled in the UI and settings should not be updated

        return redirect()->route('panel.payment-gateway.index')
            ->with('success', 'Payment gateway configuration updated successfully.');
    }
}
