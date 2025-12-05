<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'slug',
        'uid',
        'description',
        'price_monthly',
        'price_yearly',
        'icon',
        'color',
        'is_recommended',
        'order',
        'is_active',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'is_recommended' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the features for the subscription plan.
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(PlanFeature::class, 'plan_feature')
            ->withPivot('limit', 'limit_type', 'value')
            ->withTimestamps();
    }

    /**
     * Get the user subscriptions for this plan.
     */
    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Get the users subscribed to this plan.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Scope to get only active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order plans.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }

    /**
     * Get price based on billing period.
     */
    public function getPrice(string $period = 'monthly'): float
    {
        return $period === 'yearly' ? (float) $this->price_yearly : (float) $this->price_monthly;
    }

    /**
     * Check if this plan is higher tier than another plan.
     * Tier is determined by price - higher price = higher tier.
     */
    public function isHigherTierThan(SubscriptionPlan $otherPlan): bool
    {
        // Primary comparison: monthly price (higher price = higher tier)
        if ($this->price_monthly > $otherPlan->price_monthly) {
            return true;
        }
        if ($this->price_monthly < $otherPlan->price_monthly) {
            return false;
        }
        // If monthly prices are equal, compare yearly price
        if ($this->price_yearly > $otherPlan->price_yearly) {
            return true;
        }
        if ($this->price_yearly < $otherPlan->price_yearly) {
            return false;
        }
        // If prices are equal, use order as fallback (lower order = higher tier for display)
        return $this->order < $otherPlan->order;
    }

    /**
     * Check if this plan is lower tier than another plan.
     */
    public function isLowerTierThan(SubscriptionPlan $otherPlan): bool
    {
        return $otherPlan->isHigherTierThan($this);
    }

    /**
     * Get the highest tier plan (by price).
     */
    public static function getHighestTierPlan()
    {
        return static::active()->orderBy('price_monthly', 'desc')->orderBy('price_yearly', 'desc')->first();
    }

    /**
     * Get the lowest tier plan (by price).
     */
    public static function getLowestTierPlan()
    {
        return static::active()->orderBy('price_monthly', 'asc')->orderBy('price_yearly', 'asc')->first();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->uid)) {
                $plan->uid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'uid';
    }
}

