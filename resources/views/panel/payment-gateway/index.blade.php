@extends('layouts.master')

@section('styles')
    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.css')}}">
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Payment Gateway</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                <li class="breadcrumb-item active" aria-current="page">Payment Gateway</li>
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
    <form action="{{ route('panel.payment-gateway.update') }}" method="POST" id="payment-gateway-form">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">Payment Gateway Configuration</div>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3 border-0" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" role="tab" href="#stripe-tab" aria-selected="true">
                                    <i class="ri-bank-card-line me-1"></i>Stripe
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#paypal-tab" aria-selected="false">
                                    <i class="ri-paypal-line me-1"></i>PayPal
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#razorpay-tab" aria-selected="false">
                                    <i class="ri-money-rupee-circle-line me-1"></i>Razorpay
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#square-tab" aria-selected="false">
                                    <i class="ri-square-line me-1"></i>Square
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#authorize-tab" aria-selected="false">
                                    <i class="ri-shield-check-line me-1"></i>Authorize.Net
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#mollie-tab" aria-selected="false">
                                    <i class="ri-global-line me-1"></i>Mollie
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <!-- Stripe Tab -->
                            <div class="tab-pane fade show active" id="stripe-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="stripe_publishable_key" class="form-label">Publishable Key</label>
                                        <input type="text" class="form-control @error('stripe_publishable_key') is-invalid @enderror" id="stripe_publishable_key" name="stripe_publishable_key" value="{{ old('stripe_publishable_key', $settings['stripe_publishable_key'] ?? '') }}" placeholder="pk_test_...">
                                        @error('stripe_publishable_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="stripe_secret_key" class="form-label">Secret Key</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('stripe_secret_key') is-invalid @enderror" id="stripe_secret_key" name="stripe_secret_key" value="{{ old('stripe_secret_key', $settings['stripe_secret_key'] ?? '') }}" placeholder="sk_test_...">
                                            <button class="btn btn-light" type="button" id="toggle_stripe_secret">
                                                <i class="ri-eye-line" id="stripe_secret_icon"></i>
                                            </button>
                                        </div>
                                        @error('stripe_secret_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current key</span>
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="stripe_webhook_secret" class="form-label">Webhook Secret</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('stripe_webhook_secret') is-invalid @enderror" id="stripe_webhook_secret" name="stripe_webhook_secret" value="{{ old('stripe_webhook_secret', $settings['stripe_webhook_secret'] ?? '') }}" placeholder="whsec_...">
                                            <button class="btn btn-light" type="button" id="toggle_stripe_webhook">
                                                <i class="ri-eye-line" id="stripe_webhook_icon"></i>
                                            </button>
                                        </div>
                                        @error('stripe_webhook_secret')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current secret</span>
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="stripe_mode" class="form-label">Mode</label>
                                        <select class="form-control @error('stripe_mode') is-invalid @enderror" id="stripe_mode" name="stripe_mode" data-trigger>
                                            <option value="sandbox" {{ old('stripe_mode', $settings['stripe_mode'] ?? 'sandbox') == 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                                            <option value="live" {{ old('stripe_mode', $settings['stripe_mode'] ?? '') == 'live' ? 'selected' : '' }}>Live</option>
                                        </select>
                                        @error('stripe_mode')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- PayPal Tab -->
                            <div class="tab-pane fade" id="paypal-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="paypal_client_id" class="form-label">Client ID</label>
                                        <input type="text" class="form-control @error('paypal_client_id') is-invalid @enderror" id="paypal_client_id" name="paypal_client_id" value="{{ old('paypal_client_id', $settings['paypal_client_id'] ?? '') }}" placeholder="Your PayPal Client ID">
                                        @error('paypal_client_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="paypal_client_secret" class="form-label">Client Secret</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('paypal_client_secret') is-invalid @enderror" id="paypal_client_secret" name="paypal_client_secret" value="{{ old('paypal_client_secret', $settings['paypal_client_secret'] ?? '') }}" placeholder="Your PayPal Client Secret">
                                            <button class="btn btn-light" type="button" id="toggle_paypal_secret">
                                                <i class="ri-eye-line" id="paypal_secret_icon"></i>
                                            </button>
                                        </div>
                                        @error('paypal_client_secret')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current secret</span>
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="paypal_mode" class="form-label">Mode</label>
                                        <select class="form-control @error('paypal_mode') is-invalid @enderror" id="paypal_mode" name="paypal_mode" data-trigger>
                                            <option value="sandbox" {{ old('paypal_mode', $settings['paypal_mode'] ?? 'sandbox') == 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                                            <option value="live" {{ old('paypal_mode', $settings['paypal_mode'] ?? '') == 'live' ? 'selected' : '' }}>Live</option>
                                        </select>
                                        @error('paypal_mode')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Razorpay Tab -->
                            <div class="tab-pane fade" id="razorpay-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="razorpay_key_id" class="form-label">Key ID</label>
                                        <input type="text" class="form-control @error('razorpay_key_id') is-invalid @enderror" id="razorpay_key_id" name="razorpay_key_id" value="{{ old('razorpay_key_id', $settings['razorpay_key_id'] ?? '') }}" placeholder="rzp_test_...">
                                        @error('razorpay_key_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="razorpay_key_secret" class="form-label">Key Secret</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('razorpay_key_secret') is-invalid @enderror" id="razorpay_key_secret" name="razorpay_key_secret" value="{{ old('razorpay_key_secret', $settings['razorpay_key_secret'] ?? '') }}" placeholder="Your Razorpay Key Secret">
                                            <button class="btn btn-light" type="button" id="toggle_razorpay_secret">
                                                <i class="ri-eye-line" id="razorpay_secret_icon"></i>
                                            </button>
                                        </div>
                                        @error('razorpay_key_secret')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current secret</span>
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="razorpay_mode" class="form-label">Mode</label>
                                        <select class="form-control @error('razorpay_mode') is-invalid @enderror" id="razorpay_mode" name="razorpay_mode" data-trigger>
                                            <option value="sandbox" {{ old('razorpay_mode', $settings['razorpay_mode'] ?? 'sandbox') == 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                                            <option value="live" {{ old('razorpay_mode', $settings['razorpay_mode'] ?? '') == 'live' ? 'selected' : '' }}>Live</option>
                                        </select>
                                        @error('razorpay_mode')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Square Tab -->
                            <div class="tab-pane fade" id="square-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="square_application_id" class="form-label">Application ID</label>
                                        <input type="text" class="form-control @error('square_application_id') is-invalid @enderror" id="square_application_id" name="square_application_id" value="{{ old('square_application_id', $settings['square_application_id'] ?? '') }}" placeholder="Your Square Application ID">
                                        @error('square_application_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="square_access_token" class="form-label">Access Token</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('square_access_token') is-invalid @enderror" id="square_access_token" name="square_access_token" value="{{ old('square_access_token', $settings['square_access_token'] ?? '') }}" placeholder="Your Square Access Token">
                                            <button class="btn btn-light" type="button" id="toggle_square_token">
                                                <i class="ri-eye-line" id="square_token_icon"></i>
                                            </button>
                                        </div>
                                        @error('square_access_token')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current token</span>
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="square_location_id" class="form-label">Location ID</label>
                                        <input type="text" class="form-control @error('square_location_id') is-invalid @enderror" id="square_location_id" name="square_location_id" value="{{ old('square_location_id', $settings['square_location_id'] ?? '') }}" placeholder="Your Square Location ID">
                                        @error('square_location_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="square_mode" class="form-label">Mode</label>
                                        <select class="form-control @error('square_mode') is-invalid @enderror" id="square_mode" name="square_mode" data-trigger>
                                            <option value="sandbox" {{ old('square_mode', $settings['square_mode'] ?? 'sandbox') == 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                                            <option value="live" {{ old('square_mode', $settings['square_mode'] ?? '') == 'live' ? 'selected' : '' }}>Live</option>
                                        </select>
                                        @error('square_mode')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Authorize.Net Tab -->
                            <div class="tab-pane fade" id="authorize-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="authorize_net_api_login_id" class="form-label">API Login ID</label>
                                        <input type="text" class="form-control @error('authorize_net_api_login_id') is-invalid @enderror" id="authorize_net_api_login_id" name="authorize_net_api_login_id" value="{{ old('authorize_net_api_login_id', $settings['authorize_net_api_login_id'] ?? '') }}" placeholder="Your Authorize.Net API Login ID">
                                        @error('authorize_net_api_login_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="authorize_net_transaction_key" class="form-label">Transaction Key</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('authorize_net_transaction_key') is-invalid @enderror" id="authorize_net_transaction_key" name="authorize_net_transaction_key" value="{{ old('authorize_net_transaction_key', $settings['authorize_net_transaction_key'] ?? '') }}" placeholder="Your Authorize.Net Transaction Key">
                                            <button class="btn btn-light" type="button" id="toggle_authorize_key">
                                                <i class="ri-eye-line" id="authorize_key_icon"></i>
                                            </button>
                                        </div>
                                        @error('authorize_net_transaction_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current key</span>
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="authorize_net_mode" class="form-label">Mode</label>
                                        <select class="form-control @error('authorize_net_mode') is-invalid @enderror" id="authorize_net_mode" name="authorize_net_mode" data-trigger>
                                            <option value="sandbox" {{ old('authorize_net_mode', $settings['authorize_net_mode'] ?? 'sandbox') == 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                                            <option value="live" {{ old('authorize_net_mode', $settings['authorize_net_mode'] ?? '') == 'live' ? 'selected' : '' }}>Live</option>
                                        </select>
                                        @error('authorize_net_mode')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Mollie Tab -->
                            <div class="tab-pane fade" id="mollie-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="mollie_api_key" class="form-label">API Key</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('mollie_api_key') is-invalid @enderror" id="mollie_api_key" name="mollie_api_key" value="{{ old('mollie_api_key', $settings['mollie_api_key'] ?? '') }}" placeholder="test_... or live_...">
                                            <button class="btn btn-light" type="button" id="toggle_mollie_key">
                                                <i class="ri-eye-line" id="mollie_key_icon"></i>
                                            </button>
                                        </div>
                                        @error('mollie_api_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current key</span>
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="mollie_mode" class="form-label">Mode</label>
                                        <select class="form-control @error('mollie_mode') is-invalid @enderror" id="mollie_mode" name="mollie_mode" data-trigger>
                                            <option value="sandbox" {{ old('mollie_mode', $settings['mollie_mode'] ?? 'sandbox') == 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                                            <option value="live" {{ old('mollie_mode', $settings['mollie_mode'] ?? '') == 'live' ? 'selected' : '' }}>Live</option>
                                        </select>
                                        @error('mollie_mode')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="btn-list float-end">
                            <button type="submit" class="btn btn-primary btn-wave">
                                <i class="ri-save-line me-1"></i>Save Configuration
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <!-- End::row-1 -->

@endsection

@section('scripts')
    <!-- Choices JS -->
    <script src="{{asset('build/assets/libs/choices.js/public/assets/scripts/choices.min.js')}}"></script>
    
    <!-- Sweetalerts JS -->
    <script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>
    
    <script>
        // Initialize Choices.js for select dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            const selects = document.querySelectorAll('select[data-trigger]');
            selects.forEach(select => {
                new Choices(select, {
                    searchEnabled: false,
                    placeholder: true,
                });
            });
        });

        // Toggle password visibility functions
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('ri-eye-line');
                icon.classList.add('ri-eye-off-line');
            } else {
                input.type = 'password';
                icon.classList.remove('ri-eye-off-line');
                icon.classList.add('ri-eye-line');
            }
        }

        // Stripe Secret toggle
        document.getElementById('toggle_stripe_secret')?.addEventListener('click', function() {
            togglePasswordVisibility('stripe_secret_key', 'stripe_secret_icon');
        });

        // Stripe Webhook toggle
        document.getElementById('toggle_stripe_webhook')?.addEventListener('click', function() {
            togglePasswordVisibility('stripe_webhook_secret', 'stripe_webhook_icon');
        });

        // PayPal Secret toggle
        document.getElementById('toggle_paypal_secret')?.addEventListener('click', function() {
            togglePasswordVisibility('paypal_client_secret', 'paypal_secret_icon');
        });

        // Razorpay Secret toggle
        document.getElementById('toggle_razorpay_secret')?.addEventListener('click', function() {
            togglePasswordVisibility('razorpay_key_secret', 'razorpay_secret_icon');
        });

        // Square Token toggle
        document.getElementById('toggle_square_token')?.addEventListener('click', function() {
            togglePasswordVisibility('square_access_token', 'square_token_icon');
        });

        // Authorize.Net Key toggle
        document.getElementById('toggle_authorize_key')?.addEventListener('click', function() {
            togglePasswordVisibility('authorize_net_transaction_key', 'authorize_key_icon');
        });

        // Mollie Key toggle
        document.getElementById('toggle_mollie_key')?.addEventListener('click', function() {
            togglePasswordVisibility('mollie_api_key', 'mollie_key_icon');
        });
    </script>
@endsection


