<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\PlanFeature;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class PlanFeatureController extends Controller
{
    /**
     * Display a listing of plan features.
     */
    public function index(): View
    {
        $features = PlanFeature::with('subscriptionPlans')->ordered()->get();
        return view('panel.plan-features.index', compact('features'));
    }

    /**
     * Show the form for creating a new plan feature.
     */
    public function create(): View
    {
        return view('panel.plan-features.create');
    }

    /**
     * Store a newly created plan feature.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        
        // Ensure slug is unique
        $slug = $validated['slug'];
        $counter = 1;
        while (PlanFeature::where('slug', $slug)->exists()) {
            $slug = $validated['slug'] . '-' . $counter;
            $counter++;
        }
        $validated['slug'] = $slug;

        PlanFeature::create($validated);

        return redirect()->route('panel.plan-features.index')
            ->with('success', 'Plan feature created successfully.');
    }

    /**
     * Display the specified plan feature.
     */
    public function show(PlanFeature $planFeature): View
    {
        $planFeature->load('subscriptionPlans');
        return view('panel.plan-features.show', compact('planFeature'));
    }

    /**
     * Show the form for editing the specified plan feature.
     */
    public function edit(PlanFeature $planFeature): View
    {
        return view('panel.plan-features.edit', compact('planFeature'));
    }

    /**
     * Update the specified plan feature.
     */
    public function update(Request $request, PlanFeature $planFeature): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $planFeature->update($validated);

        return redirect()->route('panel.plan-features.index')
            ->with('success', 'Plan feature updated successfully.');
    }

    /**
     * Remove the specified plan feature.
     */
    public function destroy(PlanFeature $planFeature): RedirectResponse
    {
        // Check if feature is used in any plans
        $plansCount = $planFeature->subscriptionPlans()->count();

        if ($plansCount > 0) {
            return redirect()->route('panel.plan-features.index')
                ->with('error', 'Cannot delete feature that is assigned to subscription plans.');
        }

        $planFeature->delete();

        return redirect()->route('panel.plan-features.index')
            ->with('success', 'Plan feature deleted successfully.');
    }
}

