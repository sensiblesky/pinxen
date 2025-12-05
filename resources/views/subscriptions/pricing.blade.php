@extends('layouts.master')

@section('styles')



@endsection

@section('content')
	
                    <!-- Start::page-header -->
                    <div class="page-header-breadcrumb mb-3">
                        <div class="d-flex align-center justify-content-between flex-wrap">
                            <h1 class="page-title fw-medium fs-18 mb-0">Pricing Plans</h1>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Pricing</li>
                            </ol>
                        </div>
                    </div>
                    <!-- End::page-header -->

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('info'))
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            {{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(isset($userHighestTierPlan) && $userHighestTierPlan)
                        <div class="alert alert-primary alert-dismissible fade show" role="alert">
                            <i class="ri-information-line me-2"></i>
                            <strong>Current Plan:</strong> You are currently subscribed to <strong>{{ $userHighestTierPlan->name }}</strong>. 
                            You can upgrade to higher tier plans.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Start:: row-1 -->
                    <div class="row d-flex justify-content-center mb-5">
                        <div class="pricing-heading-section text-center mb-5">
                            <span class="badge bg-primary-transparent rounded-pill">
                                Pricing Plans
                            </span>
                            <h2 class="fw-semibold mt-2">Choose the Right Plan for Your Needs</h2>
                            <span class="d-block text-muted fs-16 mb-3">Choose a plan that fits your needs with scalable features and great value.</span>
                            <div class="tab-style-1 border p-1 bg-white rounded-pill d-inline-block"> 
                                <ul class="nav nav-pills" role="tablist"> 
                                    <li class="nav-item" role="presentation"> 
                                        <button type="button" class="nav-link rounded-pill fw-medium active" data-bs-toggle="pill" data-bs-target="#pricing-monthly" aria-selected="true" role="tab">Monthly</button> 
                                    </li> 
                                    <li class="nav-item" role="presentation"> 
                                        <button type="button" class="nav-link rounded-pill fw-medium" data-bs-toggle="pill" data-bs-target="#pricing-yearly" aria-selected="false" role="tab" tabindex="-1">Yearly</button> 
                                    </li> 
                                </ul> 
                            </div>
                        </div>
                        <div class="col-xl-9">
                            <div class="tab-content">
                                <!-- Monthly Plans -->
                                <div class="tab-pane show active p-0 border-0" id="pricing-monthly" role="tabpanel">
                                    <div class="row">
                                        @foreach($plans as $plan)
                                            @php
                                                $availability = $planAvailability[$plan->id] ?? [
                                                    'is_subscribed' => false,
                                                    'can_upgrade' => true,
                                                    'can_downgrade' => false,
                                                    'is_downgrade' => false,
                                                    'is_upgrade' => false,
                                                ];
                                                $isDisabled = $availability['is_downgrade'];
                                            @endphp
                                            <div class="col-xxl-4 col-xl-12">
                                                <div class="card custom-card dashboard-main-card pricing-card pricing-{{ $plan->color }} {{ $plan->is_recommended ? 'pricing-recommended' : '' }} {{ $isDisabled ? 'opacity-75' : '' }}">
                                                    @if($plan->is_recommended)
                                                        <span class="badge bg-dark text-white pricing-recommended-badge">Recommended</span>
                                                    @endif
                                                    @if($isDisabled)
                                                        <span class="badge bg-warning text-dark pricing-recommended-badge" style="right: auto; left: 1rem; top: 1rem;">
                                                            <i class="ri-lock-line me-1"></i>Not Available
                                                        </span>
                                                    @endif
                                                    <div class="card-body p-4">
                                                        <div class="lh-1 mb-3">
                                                            @if($plan->icon)
                                                                <span class="avatar avatar-lg avatar-rounded bg-{{ $plan->color }}-transparent svg-{{ $plan->color }}">
                                                                    {!! $plan->icon !!}
                                                                </span>
                                                            @else
                                                                <span class="avatar avatar-lg avatar-rounded bg-{{ $plan->color }}-transparent svg-{{ $plan->color }}">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M230.93,220a8,8,0,0,1-6.93,4H32a8,8,0,0,1-6.92-12c15.23-26.33,38.7-45.21,66.09-54.16a72,72,0,1,1,73.66,0c27.39,8.95,50.86,27.83,66.09,54.16A8,8,0,0,1,230.93,220Z"/></svg>
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <h5 class="fw-semibold">{{ $plan->name }}</h5>
                                                        <p class="text-muted">{{ $plan->description ?? 'Perfect plan for your monitoring needs.' }}</p>
                                                        <div class="pricing-count">
                                                            <span class="fs-13 d-block mb-1">Start at</span>
                                                            <div class="d-flex align-items-end gap-2">
                                                                <h2 class="fw-semibold mb-0 lh-1">${{ number_format($plan->price_monthly, 2) }}</h2>
                                                                <span class="fs-13">/ Month</span>
                                                            </div>
                                                        </div>
                                                        <hr class="section-devider my-4">
                                                        <ul class="list-unstyled pricing-features-list">
                                                            @foreach($plan->features as $feature)
                                                                <li>
                                                                    @if($feature->pivot->value)
                                                                        <span class="fw-medium">{{ $feature->pivot->value }}</span>
                                                                    @elseif($feature->pivot->limit)
                                                                        <span class="fw-medium">{{ $feature->pivot->limit }}</span> {{ $feature->name }}
                                                                    @else
                                                                        {{ $feature->name }}
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                        <div class="d-grid mt-4">
                                                            @php
                                                                $isSubscribed = isset($subscribedPlanIds) && in_array($plan->id, $subscribedPlanIds);
                                                                $activeSubscription = $activeSubscriptions[$plan->id] ?? null;
                                                                $availability = $planAvailability[$plan->id] ?? [
                                                                    'is_subscribed' => false,
                                                                    'can_upgrade' => true,
                                                                    'can_downgrade' => false,
                                                                    'is_downgrade' => false,
                                                                    'is_upgrade' => false,
                                                                ];
                                                            @endphp
                                                            @if($isSubscribed && $activeSubscription)
                                                                <div class="alert alert-success mb-0 text-center">
                                                                    <i class="ri-checkbox-circle-line me-1"></i>
                                                                    <strong>Active</strong>
                                                                    <div class="small mt-1">
                                                                        Expires: {{ $activeSubscription->ends_at->format('M d, Y') }}
                                                                    </div>
                                                                </div>
                                                                <a href="{{ route('subscriptions.show') }}" class="btn btn-lg btn-outline-success w-100 mt-2">
                                                                    <i class="ri-eye-line me-1"></i>View Subscription
                                                                </a>
                                                            @elseif($availability['is_downgrade'])
                                                                <div class="alert alert-warning mb-0 text-center">
                                                                    <i class="ri-error-warning-line me-1"></i>
                                                                    <strong>Downgrade Not Allowed</strong>
                                                                    <div class="small mt-1">
                                                                        You already have a higher tier plan
                                                                    </div>
                                                                </div>
                                                                <button type="button" class="btn btn-lg btn-outline-secondary w-100 mt-2" disabled>
                                                                    <i class="ri-lock-line me-1"></i>Not Available
                                                                </button>
                                                            @elseif($availability['is_upgrade'])
                                                                
                                                                <form action="{{ route('subscriptions.subscribe', $plan->uid) }}" method="POST" class="w-100">
                                                                    @csrf
                                                                    <input type="hidden" name="billing_period" value="monthly">
                                                                    <button type="submit" class="btn btn-lg w-100 {{ $plan->is_recommended ? 'btn-primary' : 'btn-success' }}">
                                                                        <i class="ri-arrow-up-line me-1"></i>Upgrade Now
                                                                    </button>
                                                                </form>
                                                            @else
                                                                <form action="{{ route('subscriptions.subscribe', $plan->uid) }}" method="POST" class="w-100">
                                                                    @csrf
                                                                    <input type="hidden" name="billing_period" value="monthly">
                                                                    <button type="submit" class="btn btn-lg w-100 {{ $plan->is_recommended ? 'btn-primary' : 'btn-outline-light' }}">
                                                                        Get Started
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                
                                <!-- Yearly Plans -->
                                <div class="tab-pane p-0 border-0" id="pricing-yearly" role="tabpanel">
                                    <div class="row">
                                        @foreach($plans as $plan)
                                            @php
                                                $availability = $planAvailability[$plan->id] ?? [
                                                    'is_subscribed' => false,
                                                    'can_upgrade' => true,
                                                    'can_downgrade' => false,
                                                    'is_downgrade' => false,
                                                    'is_upgrade' => false,
                                                ];
                                                $isDisabled = $availability['is_downgrade'];
                                            @endphp
                                            <div class="col-xxl-4 col-xl-12">
                                                <div class="card custom-card dashboard-main-card pricing-card pricing-{{ $plan->color }} {{ $plan->is_recommended ? 'pricing-recommended' : '' }} {{ $isDisabled ? 'opacity-75' : '' }}">
                                                    @if($plan->is_recommended)
                                                        <span class="badge bg-dark text-white pricing-recommended-badge">Recommended</span>
                                                    @endif
                                                    @if($isDisabled)
                                                        <span class="badge bg-warning text-dark pricing-recommended-badge" style="right: auto; left: 1rem; top: 1rem;">
                                                            <i class="ri-lock-line me-1"></i>Not Available
                                                        </span>
                                                    @endif
                                                    <div class="card-body p-4">
                                                        <div class="lh-1 mb-3">
                                                            @if($plan->icon)
                                                                <span class="avatar avatar-lg avatar-rounded bg-{{ $plan->color }}-transparent svg-{{ $plan->color }}">
                                                                    {!! $plan->icon !!}
                                                                </span>
                                                            @else
                                                                <span class="avatar avatar-lg avatar-rounded bg-{{ $plan->color }}-transparent svg-{{ $plan->color }}">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M230.93,220a8,8,0,0,1-6.93,4H32a8,8,0,0,1-6.92-12c15.23-26.33,38.7-45.21,66.09-54.16a72,72,0,1,1,73.66,0c27.39,8.95,50.86,27.83,66.09,54.16A8,8,0,0,1,230.93,220Z"/></svg>
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <h5 class="fw-semibold">{{ $plan->name }}</h5>
                                                        <p class="text-muted">{{ $plan->description ?? 'Perfect plan for your monitoring needs.' }}</p>
                                                        <div class="pricing-count">
                                                            <span class="fs-13 d-block mb-1">Start at</span>
                                                            <div class="d-flex align-items-end gap-2">
                                                                <h2 class="fw-semibold mb-0 lh-1">${{ number_format($plan->price_yearly, 2) }}</h2>
                                                                <span class="fs-13">/ Year</span>
                                                            </div>
                                                        </div>
                                                        <hr class="section-devider my-4">
                                                        <ul class="list-unstyled pricing-features-list">
                                                            @foreach($plan->features as $feature)
                                                                <li>
                                                                    @if($feature->pivot->value)
                                                                        <span class="fw-medium">{{ $feature->pivot->value }}</span>
                                                                    @elseif($feature->pivot->limit)
                                                                        <span class="fw-medium">{{ $feature->pivot->limit }}</span> {{ $feature->name }}
                                                                    @else
                                                                        {{ $feature->name }}
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                        <div class="d-grid mt-4">
                                                            @php
                                                                $isSubscribed = isset($subscribedPlanIds) && in_array($plan->id, $subscribedPlanIds);
                                                                $activeSubscription = $activeSubscriptions[$plan->id] ?? null;
                                                                $availability = $planAvailability[$plan->id] ?? [
                                                                    'is_subscribed' => false,
                                                                    'can_upgrade' => true,
                                                                    'can_downgrade' => false,
                                                                    'is_downgrade' => false,
                                                                    'is_upgrade' => false,
                                                                ];
                                                            @endphp
                                                            @if($isSubscribed && $activeSubscription)
                                                                <div class="alert alert-success mb-0 text-center">
                                                                    <i class="ri-checkbox-circle-line me-1"></i>
                                                                    <strong>Active</strong>
                                                                    <div class="small mt-1">
                                                                        Expires: {{ $activeSubscription->ends_at->format('M d, Y') }}
                                                                    </div>
                                                                </div>
                                                                <a href="{{ route('subscriptions.show') }}" class="btn btn-lg btn-outline-success w-100 mt-2">
                                                                    <i class="ri-eye-line me-1"></i>View Subscription
                                                                </a>
                                                            @elseif($availability['is_downgrade'])
                                                                <div class="alert alert-warning mb-0 text-center">
                                                                    <i class="ri-error-warning-line me-1"></i>
                                                                    <strong>Downgrade Not Allowed</strong>
                                                                    <div class="small mt-1">
                                                                        You already have a higher tier plan
                                                                    </div>
                                                                </div>
                                                                <button type="button" class="btn btn-lg btn-outline-secondary w-100 mt-2" disabled>
                                                                    <i class="ri-lock-line me-1"></i>Not Available
                                                                </button>
                                                            @elseif($availability['is_upgrade'])
                                                                <form action="{{ route('subscriptions.subscribe', $plan->uid) }}" method="POST" class="w-100">
                                                                    @csrf
                                                                    <input type="hidden" name="billing_period" value="yearly">
                                                                    <button type="submit" class="btn btn-lg w-100 {{ $plan->is_recommended ? 'btn-primary' : 'btn-success' }}">
                                                                        <i class="ri-arrow-up-line me-1"></i>Upgrade Now
                                                                    </button>
                                                                </form>
                                                            @else
                                                                <form action="{{ route('subscriptions.subscribe', $plan->uid) }}" method="POST" class="w-100">
                                                                    @csrf
                                                                    <input type="hidden" name="billing_period" value="yearly">
                                                                    <button type="submit" class="btn btn-lg w-100 {{ $plan->is_recommended ? 'btn-primary' : 'btn-outline-light' }}">
                                                                        Get Started
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End:: row-1 -->

@endsection

@section('scripts')
	


@endsection

