@extends('layouts.custom-master')

@php
// Passing the bodyClass variable from the view to the layout
$bodyClass = 'bg-white';
@endphp

@section('styles')

@endsection

@section('content')
	
        <div class="row authentication authentication-cover-main mx-0">
            <div class="col-xxl-9 col-xl-9">
                <div class="row justify-content-center align-items-center h-100">
                    <div class="col-xxl-4 col-xl-5 col-lg-6 col-md-6 col-sm-8 col-12">
                        <div class="card custom-card border-0 shadow-none my-4">
                            <div class="card-body p-5">
                                <div>
                                    <h4 class="mb-1 fw-semibold">Two-Factor Authentication</h4>
                                    <p class="mb-4 text-muted fw-normal">Enter the verification code from your authenticator app</p>
                                </div>

                                <!-- Session Status -->
                                @if (session('status'))
                                    <div class="alert alert-success mb-4" role="alert">
                                        {{ session('status') }}
                                    </div>
                                @endif

                                @if (session('error'))
                                    <div class="alert alert-danger mb-4" role="alert">
                                        {{ session('error') }}
                                    </div>
                                @endif

                                <!-- Validation Errors -->
                                @if ($errors->any())
                                    <div class="alert alert-danger mb-4" role="alert">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="alert alert-info mb-4" role="alert">
                                    <i class="ri-information-line me-1"></i>
                                    <strong>Account:</strong> {{ $user->email }}
                                </div>

                                <form method="POST" action="{{ route('two-factor.verify') }}" id="two-factor-form">
                                    @csrf
                                    <div class="row gy-3">
                                        <div class="col-xl-12">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label for="code" class="form-label text-default mb-0">
                                                    <span id="code-label">Verification Code</span>
                                                </label>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="use-recovery-code" onchange="toggleCodeType()">
                                                    <label class="form-check-label fs-12" for="use-recovery-code">
                                                        Use Recovery Code
                                                    </label>
                                                </div>
                                            </div>
                                            <input type="text" 
                                                   class="form-control text-center fs-20 fw-bold letter-spacing-2 @error('code') is-invalid @enderror" 
                                                   id="code" 
                                                   name="code"
                                                   placeholder="000000" 
                                                   maxlength="8" 
                                                   required 
                                                   autofocus 
                                                   autocomplete="off"
                                                   style="letter-spacing: 0.5em; font-size: 24px;">
                                            @error('code')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted d-block mt-2" id="code-help">
                                                <i class="ri-information-line me-1"></i>
                                                Enter the 6-digit code from your authenticator app.
                                            </small>
                                        </div>
                                    </div>
                                    <div class="d-grid mt-4">
                                        <button type="submit" class="btn btn-primary">Verify & Continue</button>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="{{ route('login') }}" class="text-muted fw-medium fs-12">
                                            <i class="ri-arrow-left-line me-1"></i>Back to Login
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-3 col-lg-12 d-xl-block d-none px-0">
                <div class="authentication-cover overflow-hidden">
                    <div class="authentication-cover-logo">
                        <a href="{{ url('/') }}">
                            <img src="{{ asset('build/assets/images/brand-logos/toggle-logo.png') }}" alt="logo" class="desktop-dark"> 
                        </a>
                    </div>
                    <div class="authentication-cover-background">
                        <img src="{{ asset('build/assets/images/media/backgrounds/9.png') }}" alt="">
                    </div>
                    <div class="authentication-cover-content">
                        <div class="p-5">
                            <h3 class="fw-semibold lh-base">Two-Factor Security</h3>
                            <p class="mb-0 text-muted fw-medium">An extra layer of security to protect your account from unauthorized access.</p>
                        </div>
                        <div>
                            <img src="{{ asset('/build/assets/images/custom/media-72.png') }}" alt="" class="img-fluid">
                        </div>
                    </div>
                </div>
            </div>
        </div>

@endsection

@section('scripts')
	
        <script>
            // Toggle between authenticator code and recovery code
            function toggleCodeType() {
                const useRecoveryCode = document.getElementById('use-recovery-code').checked;
                const codeInput = document.getElementById('code');
                const codeLabel = document.getElementById('code-label');
                const codeHelp = document.getElementById('code-help');
                
                if (useRecoveryCode) {
                    // Recovery code mode
                    codeInput.placeholder = 'XXXXXXXX';
                    codeInput.maxLength = 8;
                    codeInput.style.letterSpacing = '0.3em';
                    codeInput.value = '';
                    codeLabel.textContent = 'Recovery Code';
                    codeHelp.innerHTML = '<i class="ri-information-line me-1"></i>Enter one of your 8-character recovery codes. Each code can only be used once.';
                } else {
                    // Authenticator code mode
                    codeInput.placeholder = '000000';
                    codeInput.maxLength = 6;
                    codeInput.style.letterSpacing = '0.5em';
                    codeInput.value = '';
                    codeLabel.textContent = 'Verification Code';
                    codeHelp.innerHTML = '<i class="ri-information-line me-1"></i>Enter the 6-digit code from your authenticator app.';
                }
                codeInput.focus();
            }

            // Auto-format input based on mode
            document.addEventListener('DOMContentLoaded', function() {
                const codeInput = document.getElementById('code');
                const useRecoveryCode = document.getElementById('use-recovery-code');
                
                if (codeInput) {
                    codeInput.addEventListener('input', function(e) {
                        if (useRecoveryCode.checked) {
                            // Recovery code: alphanumeric, uppercase
                            this.value = this.value.replace(/[^A-Z0-9]/gi, '').toUpperCase();
                        } else {
                            // Authenticator code: numbers only
                            this.value = this.value.replace(/[^0-9]/g, '');
                        }
                    });

                    // Auto-submit when max length is reached
                    codeInput.addEventListener('keypress', function(e) {
                        const maxLength = useRecoveryCode.checked ? 8 : 6;
                        if (this.value.length >= maxLength) {
                            e.preventDefault();
                        }
                    });

                    // Focus on input
                    codeInput.focus();
                }
            });
        </script>

@endsection

