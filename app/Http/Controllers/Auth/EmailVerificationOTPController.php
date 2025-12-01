<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationOTP;
use App\Services\OtpService;
use App\Services\MailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class EmailVerificationOTPController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Show the email verification form.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        
        // Check if email is already verified
        if ($user->email_verified_at) {
            return redirect()->route('profile.edit')
                ->with('info', 'Your email address is already verified.');
        }
        
        // Check if there's a valid OTP already
        $existingOtp = $this->otpService->getValidOtp($user, OtpService::TYPE_EMAIL_VERIFICATION, $user->email);
        $canRequest = $this->otpService->canRequestOtp($user, OtpService::TYPE_EMAIL_VERIFICATION, $user->email);
        
        return view('auth.verify-email-otp', [
            'user' => $user,
            'hasExistingOtp' => $existingOtp !== null,
            'canRequest' => $canRequest['can_request'],
            'cooldownMessage' => $canRequest['message'],
            'cooldownEndsAt' => $canRequest['cooldown_ends_at'],
        ]);
    }

    /**
     * Send OTP to user's email.
     */
    public function sendOTP(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        // Check if email is already verified
        if ($user->email_verified_at) {
            return redirect()->route('profile.edit')
                ->with('info', 'Your email address is already verified. No need to send OTP.');
        }
        
        // Validate email
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('email.verification.show')
                ->withErrors($validator)
                ->withInput();
        }

        // Check if email matches user's email
        if ($request->email !== $user->email) {
            return redirect()->route('email.verification.show')
                ->with('error', 'The email address does not match your account email.');
        }

        // Check if user can request OTP
        $canRequest = $this->otpService->canRequestOtp($user, OtpService::TYPE_EMAIL_VERIFICATION, $user->email);
        
        if (!$canRequest['can_request']) {
            return redirect()->route('email.verification.show')
                ->with('error', $canRequest['message']);
        }

        // Generate OTP using service
        $otpRecord = $this->otpService->generateOtp(
            $user,
            OtpService::TYPE_EMAIL_VERIFICATION,
            $user->email,
            $request
        );

        if (!$otpRecord) {
            return redirect()->route('email.verification.show')
                ->with('error', 'Failed to generate OTP. Please try again later.');
        }

        // Check if this is a new OTP or existing one (to avoid resending email unnecessarily)
        // If OTP was just created (within last 5 seconds) or created within cooldown period, it's new
        $secondsSinceCreation = $otpRecord->created_at->diffInSeconds(now());
        $isNewOtp = $secondsSinceCreation < 5 || 
                    ($secondsSinceCreation < OtpService::getCooldownSeconds());

        // Send OTP via email only if it's a new OTP
        if ($isNewOtp) {
            try {
                // Use database-configured SMTP settings
                $mailer = MailService::getConfiguredMailer();
                $mailer->to($user->email)->send(new EmailVerificationOTP($otpRecord->otp, $user->name));
            } catch (\Exception $e) {
                // Log the error for debugging
                \Log::error('Failed to send email verification OTP: ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                // Invalidate OTP if email sending fails
                $otpRecord->markAsUsed();
                return redirect()->route('email.verification.show')
                    ->with('error', 'Failed to send OTP. Please check your SMTP configuration or try again later.');
            }
        }

        return redirect()->route('email.verification.show')
            ->with('success', 'OTP has been sent to your email address. Please check your inbox.')
            ->with('otp_sent', true);
    }

    /**
     * Verify OTP and mark email as verified.
     */
    public function verifyOTP(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        // Check if email is already verified
        if ($user->email_verified_at) {
            return redirect()->route('profile.edit')
                ->with('info', 'Your email address is already verified.');
        }
        
        $validator = Validator::make($request->all(), [
            'otp' => ['required', 'string', 'size:6'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('email.verification.show')
                ->withErrors($validator)
                ->withInput();
        }

        // Verify OTP using service
        $otpRecord = $this->otpService->verifyOtp(
            $user,
            OtpService::TYPE_EMAIL_VERIFICATION,
            $request->otp,
            $user->email
        );

        if (!$otpRecord) {
            return redirect()->route('email.verification.show')
                ->with('error', 'Invalid or expired OTP. Please try again.');
        }

        // Mark email as verified
        $user->email_verified_at = now();
        $user->save();

        // Invalidate all other email verification OTPs for this user
        $this->otpService->invalidateOtps($user, OtpService::TYPE_EMAIL_VERIFICATION, $user->email);

        return redirect()->route('profile.edit')
            ->with('success', 'Email verified successfully!');
    }
}
