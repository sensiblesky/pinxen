<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'uid',
        'avatar',
        'phone',
        'language_id',
        'timezone_id',
        'subscription_plan_id',
        'notify_in_app',
        'notify_email',
        'notify_push',
        'notify_sms',
        'require_password_verification',
        'is_active',
        'is_deleted',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notify_in_app' => 'boolean',
            'notify_email' => 'boolean',
            'notify_push' => 'boolean',
            'notify_sms' => 'boolean',
            'require_password_verification' => 'boolean',
            'is_active' => 'boolean',
            'is_deleted' => 'boolean',
            'two_factor_enabled' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->uid)) {
                $user->uid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the OTPs for the user.
     */
    public function otps()
    {
        return $this->hasMany(\App\Models\Otp::class);
    }

    /**
     * Get the language that owns the user.
     */
    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * Get the timezone that owns the user.
     */
    public function timezone()
    {
        return $this->belongsTo(Timezone::class);
    }

    /**
     * Get the login activities for the user.
     */
    public function loginActivities()
    {
        return $this->hasMany(LoginActivity::class);
    }

    /**
     * Get the subscription plan for the user.
     */
    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Get the user subscriptions.
     */
    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Get the active subscription.
     */
    public function activeSubscription()
    {
        return $this->hasOne(UserSubscription::class)->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest();
    }

    /**
     * Get the monitors for the user.
     */
    public function monitors()
    {
        return $this->hasMany(Monitor::class);
    }

    /**
     * Get the uptime monitors for the user.
     */
    public function uptimeMonitors()
    {
        return $this->hasMany(UptimeMonitor::class);
    }

    /**
     * Get the domain monitors for the user.
     */
    public function domainMonitors()
    {
        return $this->hasMany(DomainMonitor::class);
    }

    /**
     * Get the SSL monitors for the user.
     */
    public function sslMonitors()
    {
        return $this->hasMany(SSLMonitor::class);
    }

    /**
     * Get the DNS monitors for the user.
     */
    public function dnsMonitors()
    {
        return $this->hasMany(DNSMonitor::class);
    }

    /**
     * Get the API monitors for the user.
     */
    public function apiMonitors()
    {
        return $this->hasMany(ApiMonitor::class);
    }

    /**
     * Get the API keys for the user.
     */
    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    /**
     * Get the servers for the user.
     */
    public function servers()
    {
        return $this->hasMany(Server::class);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'uid';
    }

    /**
     * Scope a query to only include non-deleted users.
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Get secure avatar URL (encrypted path).
     */
    public function getSecureAvatarUrlAttribute()
    {
        if (!$this->avatar) {
            return asset('build/assets/images/faces/9.jpg');
        }
        
        try {
            $encryptedPath = \Illuminate\Support\Facades\Crypt::encryptString($this->avatar);
            // URL encode to handle special characters in encrypted string
            $encryptedPath = urlencode($encryptedPath);
            return route('panel.images.avatar', ['encryptedPath' => $encryptedPath]);
        } catch (\Exception $e) {
            \Log::error('Failed to generate secure avatar URL: ' . $e->getMessage());
            return asset('build/assets/images/faces/9.jpg');
        }
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        try {
            $settings = \App\Models\Setting::getAllCached();
            $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
            
            // Build the password reset URL (Laravel standard format)
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $this->getEmailForPasswordReset(),
            ], false));

            // Format current time as HH:MM for subject
            $currentTime = now()->format('H:i');
            $subject = "Password Reset Request : {$currentTime}";

            // Send password reset email via queue (non-blocking)
            \App\Jobs\SendMailJob::dispatch(
                $this->email,
                $subject,
                'emails.password-reset',
                [
                    'url' => $url,
                    'userName' => $this->name ?? 'User',
                    'expire' => $expire,
                ],
                \App\Mail\PasswordReset::class,
                [
                $url,
                $this->name ?? 'User',
                $expire
                ]
            );
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Failed to queue password reset email: ' . $e->getMessage(), [
                'user_id' => $this->id,
                'email' => $this->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-throw the exception so Laravel can handle it properly
            throw $e;
        }
    }
}
