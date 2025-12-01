@extends('layouts.master')

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Verify Email Address</h1>
        </div>
        <ol class="breadcrumb mb-0 mt-2">
            <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
            <li class="breadcrumb-item"><a href="{{ route('profile.edit') }}">Profile</a></li>
            <li class="breadcrumb-item active" aria-current="page">Verify Email</li>
        </ol>
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

    @if($user->email_verified_at)
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>
            <strong>Email Already Verified!</strong> Your email address <strong>{{ $user->email }}</strong> has already been verified.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <div class="text-center mb-4">
            <a href="{{ route('profile.edit') }}" class="btn btn-primary btn-wave">
                <i class="ri-arrow-left-line me-1"></i>Back to Profile
            </a>
        </div>
    @else

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="ri-mail-check-line me-2"></i>Email Verification
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="alert alert-info" role="alert">
                                <h6 class="alert-heading mb-2">
                                    <i class="ri-information-line me-1"></i>Verify Your Email Address
                                </h6>
                                <p class="mb-0">
                                    To verify your email address <strong>{{ $user->email }}</strong>, we'll send a 6-digit OTP code to your email. 
                                    Please enter the code below to complete the verification process.
                                </p>
                                @if(isset($hasExistingOtp) && $hasExistingOtp)
                                    <hr class="my-2">
                                    <p class="mb-0">
                                        <i class="ri-information-line me-1"></i>
                                        <strong>Note:</strong> You have an active OTP. You can enter it below or request a new one after the cooldown period.
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <br>
                    
                    @if(isset($cooldownMessage) && $cooldownMessage)
                        <div class="row mb-3">
                            <div class="col-xl-12">
                                <div class="alert alert-warning" role="alert">
                                    <i class="ri-time-line me-1"></i>
                                    {{ $cooldownMessage }}
                                    @if(isset($cooldownEndsAt) && $cooldownEndsAt)
                                        <span id="cooldown-timer" data-ends-at="{{ $cooldownEndsAt->timestamp }}"></span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Step 1: Confirm Email and Send OTP -->
                    <div class="row mb-4" id="step-1" @if((session('success') && str_contains(session('success'), 'OTP has been sent')) || (isset($hasExistingOtp) && $hasExistingOtp)) style="display: none;" @endif>
                        <div class="col-xl-12">
                            <div class="card custom-card border-primary">
                                <div class="card-header bg-primary-transparent">
                                    <div class="card-title text-primary">
                                        <i class="ri-mail-send-line me-1"></i>Step 1: Confirm Email & Request OTP
                                    </div>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('email.verification.send') }}" method="POST" id="send-otp-form">
                                        @csrf
                                        <div class="row">
                                            <div class="col-xl-12 mb-3">
                                                <label for="confirm-email" class="form-label">Confirm Your Email Address:</label>
                                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                                       id="confirm-email" name="email" 
                                                       value="{{ old('email', $user->email) }}" 
                                                       required 
                                                       placeholder="Enter your email address">
                                                @error('email')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="text-muted">We'll send a 6-digit OTP code to this email address.</small>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-xl-12">
                                                <button type="submit" class="btn btn-primary btn-wave" @if(isset($canRequest) && !$canRequest) disabled @endif>
                                                    <i class="ri-mail-send-line me-1"></i>Send OTP Code
                                                </button>
                                                <a href="{{ route('profile.edit') }}" class="btn btn-secondary btn-wave">
                                                    <i class="ri-arrow-left-line me-1"></i>Back to Profile
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Enter OTP -->
                    <div class="row" id="step-2" @if((!session('success') || !str_contains(session('success'), 'OTP has been sent')) && (!isset($hasExistingOtp) || !$hasExistingOtp)) style="display: none;" @endif>
                        <div class="col-xl-12">
                            <div class="card custom-card border-success">
                                <div class="card-header bg-success-transparent">
                                    <div class="card-title text-success">
                                        <i class="ri-shield-check-line me-1"></i>Step 2: Enter OTP Code
                                    </div>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('email.verification.verify') }}" method="POST" id="verify-otp-form">
                                        @csrf
                                        <div class="row">
                                            <div class="col-xl-12 mb-3">
                                                <label for="otp-code" class="form-label">Enter 6-Digit OTP Code:</label>
                                                <input type="text" 
                                                       class="form-control text-center fs-20 fw-bold letter-spacing-2 @error('otp') is-invalid @enderror" 
                                                       id="otp-code" 
                                                       name="otp" 
                                                       maxlength="6" 
                                                       pattern="[0-9]{6}" 
                                                       placeholder="000000"
                                                       required
                                                       autocomplete="off"
                                                       style="letter-spacing: 0.5em; font-size: 24px;">
                                                @error('otp')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="text-muted">Check your email inbox for the 6-digit code. The code expires in 10 minutes.</small>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-xl-12">
                                                <button type="submit" class="btn btn-success btn-wave">
                                                    <i class="ri-checkbox-circle-line me-1"></i>Verify Email
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-wave" onclick="resendOTP()">
                                                    <i class="ri-refresh-line me-1"></i>Resend OTP
                                                </button>
                                                <a href="{{ route('profile.edit') }}" class="btn btn-light btn-wave">
                                                    <i class="ri-arrow-left-line me-1"></i>Cancel
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--End::row-1 -->
    @endif

@endsection

@section('scripts')
    <script>
        // Auto-format OTP input (numbers only)
        document.addEventListener('DOMContentLoaded', function() {
            const otpInput = document.getElementById('otp-code');
            if (otpInput) {
                otpInput.addEventListener('input', function(e) {
                    // Remove non-numeric characters
                    this.value = this.value.replace(/[^0-9]/g, '');
                });

                // Auto-focus and move to next input if needed
                otpInput.addEventListener('keypress', function(e) {
                    if (this.value.length >= 6) {
                        e.preventDefault();
                    }
                });
            }

            // Show step 2 if OTP was sent successfully or if existing OTP exists
            @if((session('success') && str_contains(session('success'), 'OTP has been sent')) || (isset($hasExistingOtp) && $hasExistingOtp))
                setTimeout(function() {
                    if (document.getElementById('step-1')) {
                        document.getElementById('step-1').style.display = 'none';
                    }
                    if (document.getElementById('step-2')) {
                        document.getElementById('step-2').style.display = 'block';
                    }
                    if (document.getElementById('otp-code')) {
                        document.getElementById('otp-code').focus();
                    }
                }, 100);
            @endif

            // Cooldown timer
            const cooldownTimer = document.getElementById('cooldown-timer');
            if (cooldownTimer) {
                const endsAt = parseInt(cooldownTimer.getAttribute('data-ends-at'));
                function updateTimer() {
                    const now = Math.floor(Date.now() / 1000);
                    const remaining = endsAt - now;
                    if (remaining > 0) {
                        const minutes = Math.floor(remaining / 60);
                        const seconds = remaining % 60;
                        cooldownTimer.textContent = ` (${minutes}m ${seconds}s remaining)`;
                        setTimeout(updateTimer, 1000);
                    } else {
                        cooldownTimer.textContent = '';
                        // Enable send button if it was disabled
                        const sendBtn = document.querySelector('#send-otp-form button[type="submit"]');
                        if (sendBtn) {
                            sendBtn.disabled = false;
                        }
                    }
                }
                updateTimer();
            }
        });

        function resendOTP() {
            // Resubmit the send OTP form
            document.getElementById('send-otp-form').submit();
        }
    </script>
@endsection

