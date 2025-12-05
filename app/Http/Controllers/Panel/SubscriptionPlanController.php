<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\PlanFeature;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class SubscriptionPlanController extends Controller
{
    /**
     * Display a listing of subscription plans.
     */
    public function index(): View
    {
        $plans = SubscriptionPlan::with('features')->ordered()->get();
        return view('panel.subscription-plans.index', compact('plans'));
    }

    /**
     * Show the form for creating a new subscription plan.
     */
    public function create(): View
    {
        $features = PlanFeature::active()->ordered()->get();
        return view('panel.subscription-plans.create', compact('features'));
    }

    /**
     * Store a newly created subscription plan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'icon' => ['nullable', 'string'],
            'color' => ['required', 'string', 'in:primary,success,warning'],
            'is_recommended' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'features' => ['nullable', 'array'],
            'features.*.id' => ['exists:plan_features,id'],
            'features.*.limit' => ['nullable', 'integer', 'min:0'],
            'features.*.limit_type' => ['nullable', 'string', 'max:50'],
            'features.*.value' => ['nullable', 'string'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        
        // Ensure slug is unique
        $slug = $validated['slug'];
        $counter = 1;
        while (SubscriptionPlan::where('slug', $slug)->exists()) {
            $slug = $validated['slug'] . '-' . $counter;
            $counter++;
        }
        $validated['slug'] = $slug;

        $plan = SubscriptionPlan::create($validated);

        // Attach features
        if ($request->has('features')) {
            foreach ($request->input('features', []) as $featureId => $featureData) {
                // Only attach if the checkbox was checked (id exists in the data)
                if (isset($featureData['id']) && $featureData['id']) {
                    $plan->features()->attach($featureData['id'], [
                        'limit' => $featureData['limit'] ?? null,
                        'limit_type' => $featureData['limit_type'] ?? null,
                        'value' => $featureData['value'] ?? null,
                    ]);
                }
            }
        }

        return redirect()->route('panel.subscription-plans.index')
            ->with('success', 'Subscription plan created successfully.');
    }

    /**
     * Display the specified subscription plan.
     */
    public function show(SubscriptionPlan $subscriptionPlan): View
    {
        $subscriptionPlan->load('features');
        $allFeatures = PlanFeature::active()->ordered()->get();
        return view('panel.subscription-plans.show', compact('subscriptionPlan', 'allFeatures'));
    }

    /**
     * Update the specified subscription plan.
     */
    public function update(Request $request, SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'icon' => ['nullable', 'string'],
            'color' => ['required', 'string', 'in:primary,success,warning'],
            'is_recommended' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'features' => ['nullable', 'array'],
            'features.*.id' => ['exists:plan_features,id'],
            'features.*.limit' => ['nullable', 'integer', 'min:0'],
            'features.*.limit_type' => ['nullable', 'string', 'max:50'],
            'features.*.value' => ['nullable', 'string'],
        ]);

        $subscriptionPlan->update($validated);

        // Sync features
        if ($request->has('features')) {
            $syncData = [];
            foreach ($request->input('features', []) as $featureId => $featureData) {
                // Only sync if the checkbox was checked (id exists in the data)
                if (isset($featureData['id']) && $featureData['id']) {
                    $syncData[$featureData['id']] = [
                        'limit' => $featureData['limit'] ?? null,
                        'limit_type' => $featureData['limit_type'] ?? null,
                        'value' => $featureData['value'] ?? null,
                    ];
                }
            }
            $subscriptionPlan->features()->sync($syncData);
        } else {
            $subscriptionPlan->features()->detach();
        }

        return redirect()->route('panel.subscription-plans.index')
            ->with('success', 'Subscription plan updated successfully.');
    }

    /**
     * Remove the specified subscription plan.
     */
    public function destroy(SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        // Check if plan has active subscriptions
        $activeSubscriptions = $subscriptionPlan->userSubscriptions()
            ->where('status', 'active')
            ->count();

        if ($activeSubscriptions > 0) {
            return redirect()->route('panel.subscription-plans.index')
                ->with('error', 'Cannot delete plan with active subscriptions.');
        }

        // Soft delete the plan
        $subscriptionPlan->delete();

        return redirect()->route('panel.subscription-plans.index')
            ->with('success', 'Subscription plan deleted successfully.');
    }
}

