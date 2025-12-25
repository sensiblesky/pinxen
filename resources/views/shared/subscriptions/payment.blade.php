@extends('layouts.master')

@section('styles')



@endsection

@section('content')
	
                    <!-- Start::page-header -->
                    <div class="page-header-breadcrumb mb-3">
                        <div class="d-flex align-center justify-content-between flex-wrap">
                            <h1 class="page-title fw-medium fs-18 mb-0">Complete Payment</h1>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('subscriptions.index') }}">Pricing</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Payment</li>
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

                    <!-- Start::row-1 -->
                    <div class="row">
                        <!-- Order Summary - Left Side (First on Mobile) -->
                        <div class="col-xl-4 order-xl-1 order-1">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Order Summary</div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="text-muted">Plan:</span>
                                        <span class="fw-semibold">{{ $plan->name }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="text-muted">Billing Period:</span>
                                        <span class="fw-semibold text-capitalize">{{ $billingPeriod }}</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between mb-0">
                                        <span class="fw-semibold fs-16">Total:</span>
                                        <span class="fw-semibold fs-18 text-primary">${{ number_format($price, 2) }}</span>
                                    </div>
                                    @if($billingPeriod === 'yearly')
                                        <span class="d-block fs-12 text-muted mt-1">Billed annually</span>
                                    @else
                                        <span class="d-block fs-12 text-muted mt-1">Billed monthly</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Method Selection - Right Side (Second on Mobile) -->
                        <div class="col-xl-8 order-xl-2 order-2">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Select Payment Method</div>
                                </div>
                                <div class="card-body">
                                    <form action="{{ url('/subscriptions/' . $plan->uid . '/payment/process') }}" method="POST" id="payment-form">
                                        @csrf
                                        <input type="hidden" name="billing_period" value="{{ $billingPeriod }}">
                                        
                                        <div class="row g-3">
                                            @foreach($enabledGateways as $key => $gateway)
                                                <div class="col-md-6">
                                                    <div class="form-check custom-radio">
                                                        <input class="form-check-input" type="radio" name="payment_gateway" id="gateway_{{ $key }}" value="{{ $key }}" required>
                                                        <label class="form-check-label w-100" for="gateway_{{ $key }}">
                                                            <div class="card border">
                                                                <div class="card-body text-center p-4">
                                                                    <i class="{{ $gateway['icon'] }} fs-48 text-primary mb-3 d-block"></i>
                                                                    <h6 class="fw-semibold mb-0">{{ $gateway['name'] }}</h6>
                                                                </div>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        @error('payment_gateway')
                                            <div class="text-danger mt-2">{{ $message }}</div>
                                        @enderror

                                        <div class="d-grid gap-2 mt-4">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="ri-lock-line me-1"></i>Proceed to Payment
                                            </button>
                                            <a href="{{ route('subscriptions.index') }}" class="btn btn-light">
                                                Cancel
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End::row-1 -->

@endsection

@section('scripts')
	


@endsection

