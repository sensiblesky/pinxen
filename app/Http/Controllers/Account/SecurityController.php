<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FAQRCode\QRCode\Bacon;

class SecurityController extends Controller
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Show the change password page.
     */
    public function showPassword(Request $request): View
    {
        return view('shared.account.security.password', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Show the two-factor authentication page.
     */
    public function showTwoFactor(Request $request): View
    {
        $user = $request->user();
        
        // Generate QR code for 2FA if not enabled
        $qrCodeSvg = null;
        $secret = null;
        
        // Get remaining recovery codes count
        $remainingRecoveryCodes = 0;
        if ($user->two_factor_enabled && $user->two_factor_recovery_codes) {
            $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            $remainingRecoveryCodes = is_array($recoveryCodes) ? count($recoveryCodes) : 0;
        }
        
        if (!$user->two_factor_enabled) {
            if (!$user->two_factor_secret) {
                // Generate new secret and QR code
                $secret = $this->google2fa->generateSecretKey();
                // Store secret temporarily (will be saved when user enables 2FA)
                $user->two_factor_secret = encrypt($secret);
                $user->save();
            } else {
                // User has secret but hasn't enabled it yet
                $secret = decrypt($user->two_factor_secret);
            }
            // Generate QR Code SVG
            $qrCodeSvg = $this->generateQRCodeSvg($user->email, $secret);
        }

        return view('shared.account.security.two-factor', [
            'user' => $user,
            'qrCodeSvg' => $qrCodeSvg,
            'secret' => $secret,
            'remainingRecoveryCodes' => $remainingRecoveryCodes,
        ]);
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'current_password' => ['required', 'string'],
                'password' => ['required', Password::defaults(), 'confirmed'],
            ], [
                'current_password.required' => 'The current password field is required.',
                'password.required' => 'The new password field is required.',
                'password.confirmed' => 'The new password confirmation does not match.',
            ]);

            // Manually verify current password
            if (!Hash::check($validated['current_password'], $request->user()->password)) {
                return redirect()->route('account.security.password')
                    ->withErrors(['current_password' => 'The current password is incorrect.'])
                    ->withInput();
            }

            $request->user()->update([
                'password' => Hash::make($validated['password']),
            ]);

            return redirect()->route('account.security.password')
                ->with('success', 'Password updated successfully.');
        } catch (ValidationException $e) {
            return redirect()->route('account.security.password')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->route('account.security.password')
                ->with('error', 'An error occurred while updating your password. Please try again.')
                ->withInput();
        }
    }

    /**
     * Enable two-factor authentication.
     */
    public function enableTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'verification_code' => ['required', 'string', 'size:6'],
        ]);

        // Get the secret (should already exist from the showTwoFactor page)
        if (!$user->two_factor_secret) {
            $secret = $this->google2fa->generateSecretKey();
            $user->two_factor_secret = encrypt($secret);
            $user->save();
        } else {
            $secret = decrypt($user->two_factor_secret);
        }

        // Verify the code
        $valid = $this->google2fa->verifyKey($secret, $validated['verification_code']);

        if (!$valid) {
            throw ValidationException::withMessages([
                'verification_code' => ['The verification code is invalid.'],
            ]);
        }

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        return redirect()->route('account.security.two-factor')
            ->with('success', 'Two-factor authentication enabled successfully.')
            ->with('recovery_codes', $recoveryCodes);
    }

    /**
     * Disable two-factor authentication.
     */
    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'password' => ['required', 'current_password'],
            'verification_code' => ['required', 'string', 'size:6'],
        ]);

        // Verify the 2FA code before disabling
        if (!$user->two_factor_secret) {
            throw ValidationException::withMessages([
                'verification_code' => ['Two-factor authentication is not properly configured.'],
            ]);
        }

        $secret = decrypt($user->two_factor_secret);
        $valid = $this->google2fa->verifyKey($secret, $validated['verification_code']);

        if (!$valid) {
            throw ValidationException::withMessages([
                'verification_code' => ['The verification code is invalid. Please enter the current code from your authenticator app.'],
            ]);
        }

        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);

        return redirect()->route('account.security.two-factor')
            ->with('success', 'Two-factor authentication disabled successfully.');
    }

    /**
     * Regenerate recovery codes.
     */
    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        // Security check: User must have 2FA enabled
        if (!$user->two_factor_enabled) {
            return redirect()->route('account.security.two-factor')
                ->with('error', 'Two-factor authentication must be enabled to regenerate recovery codes.');
        }

        try {
            $validated = $request->validate([
                'password' => ['required', 'current_password'],
                'verification_code' => ['required', 'string', 'size:6'],
            ]);
        } catch (ValidationException $e) {
            return redirect()->route('account.security.two-factor')
                ->withErrors($e->errors())
                ->with('regenerate_form_errors', true)
                ->withInput();
        }

        // Verify the 2FA code
        if (!$user->two_factor_secret) {
            return redirect()->route('account.security.two-factor')
                ->withErrors(['verification_code' => 'Two-factor authentication is not properly configured.'])
                ->with('regenerate_form_errors', true)
                ->withInput();
        }

        $secret = decrypt($user->two_factor_secret);
        $valid = $this->google2fa->verifyKey($secret, $validated['verification_code']);

        if (!$valid) {
            return redirect()->route('account.security.two-factor')
                ->withErrors(['verification_code' => 'The verification code is invalid. Please enter the current code from your authenticator app.'])
                ->with('regenerate_form_errors', true)
                ->withInput();
        }

        // Generate new recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        return redirect()->route('account.security.two-factor')
            ->with('success', 'Recovery codes regenerated successfully. Please save the new codes in a safe place.')
            ->with('recovery_codes', $recoveryCodes);
    }

    /**
     * Generate recovery codes.
     */
    protected function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }

    /**
     * Generate QR Code SVG.
     */
    protected function generateQRCodeSvg(string $email, string $secret): string
    {
        try {
            $qrCodeUrl = $this->google2fa->getQRCodeUrl(
                config('app.name'),
                $email,
                $secret
            );
            
            $qrCode = new Bacon();
            return $qrCode->getQRCodeInline($qrCodeUrl, 300);
        } catch (\Exception $e) {
            \Log::error('QR Code generation failed: ' . $e->getMessage());
            return '';
        }
    }
}
