<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OtpService
{
    /**
     * OTP types constants
     */
    public const TYPE_EMAIL_VERIFICATION = 'email_verification';
    public const TYPE_PASSWORD_RESET = 'password_reset';
    public const TYPE_PHONE_VERIFICATION = 'phone_verification';
    public const TYPE_TWO_FACTOR_AUTH = 'two_factor_auth';
    public const TYPE_ACCOUNT_RECOVERY = 'account_recovery';

    /**
     * OTP expiration time in minutes
     */
    private const EXPIRATION_MINUTES = 5;

    /**
     * Cooldown period in seconds (prevent resending too quickly)
     */
    private const COOLDOWN_SECONDS = 30;

    /**
     * Generate and store OTP for a user.
     *
     * @param User|null $user
     * @param string $type
     * @param string|null $identifier
     * @param Request|null $request
     * @return Otp|null
     */
    public function generateOtp(?User $user, string $type, ?string $identifier = null, ?Request $request = null): ?Otp
    {
        // Check if there's a valid OTP already
        $existingOtp = $this->getValidOtp($user, $type, $identifier);
        
        if ($existingOtp) {
            // Check if we're in cooldown period (recently sent)
            $cooldownEnd = $existingOtp->created_at->copy()->addSeconds(self::COOLDOWN_SECONDS);
            if ($cooldownEnd->isFuture()) {
                // Return existing OTP without generating new one
                return $existingOtp;
            }
            
            // If cooldown passed but OTP still valid, invalidate old one
            $existingOtp->update(['is_used' => true]);
        }

        // Generate 6-digit OTP
        $otp = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Create new OTP record
        $otpRecord = Otp::create([
            'user_id' => $user?->id,
            'type' => $type,
            'identifier' => $identifier ?? $user?->email ?? $user?->phone,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(self::EXPIRATION_MINUTES),
            'is_used' => false,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);

        return $otpRecord;
    }

    /**
     * Verify OTP code.
     *
     * @param User|null $user
     * @param string $type
     * @param string $otpCode
     * @param string|null $identifier
     * @return Otp|null
     */
    public function verifyOtp(?User $user, string $type, string $otpCode, ?string $identifier = null): ?Otp
    {
        $query = Otp::ofType($type)
            ->where('otp', $otpCode)
            ->where('is_used', false)
            ->where('expires_at', '>', now());

        if ($user) {
            $query->where('user_id', $user->id);
        }

        if ($identifier) {
            $query->where('identifier', $identifier);
        }

        $otp = $query->latest()->first();

        if (!$otp || !$otp->isValid()) {
            return null;
        }

        // Mark as used
        $otp->markAsUsed();

        return $otp;
    }

    /**
     * Get valid OTP for user and type.
     *
     * @param User|null $user
     * @param string $type
     * @param string|null $identifier
     * @return Otp|null
     */
    public function getValidOtp(?User $user, string $type, ?string $identifier = null): ?Otp
    {
        $query = Otp::ofType($type)
            ->valid()
            ->latest();

        if ($user) {
            $query->where('user_id', $user->id);
        }

        if ($identifier) {
            $query->where('identifier', $identifier);
        }

        return $query->first();
    }

    /**
     * Check if user can request new OTP (no valid OTP exists or cooldown passed).
     *
     * @param User|null $user
     * @param string $type
     * @param string|null $identifier
     * @return array ['can_request' => bool, 'message' => string, 'cooldown_ends_at' => Carbon|null]
     */
    public function canRequestOtp(?User $user, string $type, ?string $identifier = null): array
    {
        $existingOtp = $this->getValidOtp($user, $type, $identifier);

        if (!$existingOtp) {
            return [
                'can_request' => true,
                'message' => null,
                'cooldown_ends_at' => null,
            ];
        }

        $cooldownEnd = $existingOtp->created_at->addSeconds(self::COOLDOWN_SECONDS);

        if ($cooldownEnd->isFuture()) {
            $secondsRemaining = now()->diffInSeconds($cooldownEnd, false);
            return [
                'can_request' => false,
                'message' => "Please wait {$secondsRemaining} seconds before requesting a new OTP.",
                'cooldown_ends_at' => $cooldownEnd,
            ];
        }

        return [
            'can_request' => true,
            'message' => null,
            'cooldown_ends_at' => null,
        ];
    }

    /**
     * Invalidate all OTPs of a specific type for a user.
     *
     * @param User|null $user
     * @param string $type
     * @param string|null $identifier
     * @return int Number of invalidated OTPs
     */
    public function invalidateOtps(?User $user, string $type, ?string $identifier = null): int
    {
        $query = Otp::ofType($type)
            ->where('is_used', false);

        if ($user) {
            $query->where('user_id', $user->id);
        }

        if ($identifier) {
            $query->where('identifier', $identifier);
        }

        return $query->update(['is_used' => true]);
    }

    /**
     * Clean up expired OTPs (can be called by a scheduled job).
     *
     * @return int Number of cleaned OTPs
     */
    public function cleanupExpiredOtps(): int
    {
        return Otp::where('expires_at', '<', now())
            ->orWhere(function ($query) {
                $query->where('is_used', true)
                      ->where('used_at', '<', now()->subDays(7)); // Keep used OTPs for 7 days
            })
            ->delete();
    }

    /**
     * Get OTP expiration time in minutes.
     *
     * @return int
     */
    public function getExpirationMinutes(): int
    {
        return self::EXPIRATION_MINUTES;
    }

    /**
     * Get cooldown period in seconds.
     *
     * @return int
     */
    public static function getCooldownSeconds(): int
    {
        return self::COOLDOWN_SECONDS;
    }
}

