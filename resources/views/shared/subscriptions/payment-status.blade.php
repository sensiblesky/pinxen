@extends('layouts.master')

@section('styles')

@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Payment Status</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('subscriptions.index') }}">Pricing</a></li>
                <li class="breadcrumb-item active" aria-current="page">Payment Status</li>
            </ol>
        </div>
    </div>
    <!-- End::page-header -->

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-8 col-lg-10 mx-auto">
            <div class="card custom-card">
                <div class="card-body text-center p-5">
                    @if($status === 'success')
                        <!-- Success State -->
                        <div class="mb-4">
                            <div class="avatar avatar-xl avatar-rounded bg-success-transparent mx-auto mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="svg-success" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                            </div>
                            <h2 class="fw-semibold mb-2">Payment Successful!</h2>
                            <p class="text-muted mb-4">Your payment has been processed successfully.</p>
                        </div>

                        <div class="card border mb-4">
                            <div class="card-body">
                                <div class="row text-start">
                                    <div class="col-md-6 mb-3">
                                        <span class="text-muted d-block">Plan:</span>
                                        <span class="fw-semibold">{{ $subscription->subscriptionPlan->name ?? 'N/A' }}</span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <span class="text-muted d-block">Billing Period:</span>
                                        <span class="fw-semibold text-capitalize">{{ $subscription->billing_period ?? 'N/A' }}</span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <span class="text-muted d-block">Amount Paid:</span>
                                        <span class="fw-semibold text-success">${{ number_format($payment->amount ?? 0, 2) }}</span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <span class="text-muted d-block">Transaction ID:</span>
                                        <span class="fw-semibold font-monospace small">{{ $payment->gateway_transaction_id ?? 'N/A' }}</span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <span class="text-muted d-block">Payment Gateway:</span>
                                        <span class="badge bg-info">{{ ucfirst($payment->payment_gateway ?? 'N/A') }}</span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <span class="text-muted d-block">Paid At:</span>
                                        <span class="fw-semibold">{{ $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i:s') : 'N/A' }}</span>
                                    </div>
                                    @if($subscription->ends_at)
                                        <div class="col-md-12">
                                            <span class="text-muted d-block">Subscription Valid Until:</span>
                                            <span class="fw-semibold">{{ $subscription->ends_at->format('Y-m-d H:i:s') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">
                                <i class="ri-home-line me-1"></i>Go to Dashboard
                            </a>
                            <a href="{{ route('subscriptions.show') }}" class="btn btn-outline-primary btn-lg">
                                <i class="ri-eye-line me-1"></i>View Subscription
                            </a>
                        </div>
                    @elseif($status === 'failed')
                        <!-- Failed State -->
                        <div class="mb-4">
                            <div class="avatar avatar-xl avatar-rounded bg-danger-transparent mx-auto mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="svg-danger" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                            </div>
                            <h2 class="fw-semibold mb-2">Payment Failed</h2>
                            <p class="text-muted mb-4">{{ $message ?? 'Your payment could not be processed. Please try again.' }}</p>
                        </div>

                        @if($payment)
                            <div class="card border mb-4">
                                <div class="card-body">
                                    <div class="row text-start">
                                        <div class="col-md-6 mb-3">
                                            <span class="text-muted d-block">Plan:</span>
                                            <span class="fw-semibold">{{ $payment->subscriptionPlan->name ?? 'N/A' }}</span>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <span class="text-muted d-block">Amount:</span>
                                            <span class="fw-semibold">${{ number_format($payment->amount ?? 0, 2) }}</span>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <span class="text-muted d-block">Payment Gateway:</span>
                                            <span class="badge bg-info">{{ ucfirst($payment->payment_gateway ?? 'N/A') }}</span>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <span class="text-muted d-block">Status:</span>
                                            <span class="badge bg-danger">{{ ucfirst($payment->status ?? 'Failed') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="{{ route('subscriptions.index') }}" class="btn btn-primary btn-lg">
                                <i class="ri-arrow-left-line me-1"></i>Try Again
                            </a>
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-lg">
                                <i class="ri-home-line me-1"></i>Go to Dashboard
                            </a>
                        </div>
                    @elseif($status === 'cancelled')
                        <!-- Cancelled State -->
                        <div class="mb-4">
                            <div class="avatar avatar-xl avatar-rounded bg-warning-transparent mx-auto mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="svg-warning" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="15" y1="9" x2="9" y2="15"></line>
                                    <line x1="9" y1="9" x2="15" y2="15"></line>
                                </svg>
                            </div>
                            <h2 class="fw-semibold mb-2">Payment Cancelled</h2>
                            <p class="text-muted mb-4">You cancelled the payment process. No charges were made.</p>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="{{ route('subscriptions.index') }}" class="btn btn-primary btn-lg">
                                <i class="ri-arrow-left-line me-1"></i>Back to Pricing
                            </a>
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-lg">
                                <i class="ri-home-line me-1"></i>Go to Dashboard
                            </a>
                        </div>
                    @else
                        <!-- Unknown Status -->
                        <div class="mb-4">
                            <div class="avatar avatar-xl avatar-rounded bg-secondary-transparent mx-auto mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="svg-secondary" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                            </div>
                            <h2 class="fw-semibold mb-2">Payment Status Unknown</h2>
                            <p class="text-muted mb-4">We couldn't determine the payment status. Please contact support if you have any questions.</p>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="{{ route('subscriptions.index') }}" class="btn btn-primary btn-lg">
                                <i class="ri-arrow-left-line me-1"></i>Back to Pricing
                            </a>
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-lg">
                                <i class="ri-home-line me-1"></i>Go to Dashboard
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->
@endsection

@section('scripts')

@endsection



