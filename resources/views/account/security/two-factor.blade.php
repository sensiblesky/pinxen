@extends('layouts.master')

@section('styles')
    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.css')}}">
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Two-Factor Authentication</h1>
        </div>
        <ol class="breadcrumb mb-0 mt-2">
            <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
            <li class="breadcrumb-item"><a href="javascript:void(0);">Account Management</a></li>
            <li class="breadcrumb-item active" aria-current="page">Two-Factor Authentication</li>
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

    @if(session('recovery_codes'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <h6 class="alert-heading mb-2">
                <i class="ri-alert-line me-1"></i>Save Your Recovery Codes
            </h6>
            <p class="mb-2">Please save these recovery codes in a safe place. You can use them to access your account if you lose your authenticator device.</p>
            <div class="bg-light p-3 rounded mb-2">
                <div class="row g-2">
                    @foreach(session('recovery_codes') as $code)
                        <div class="col-md-3">
                            <code class="d-block text-center p-2 bg-white rounded">{{ $code }}</code>
                        </div>
                    @endforeach
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-primary" onclick="copyRecoveryCodes()">
                <i class="ri-file-copy-line me-1"></i>Copy Codes
            </button>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="ri-shield-user-line me-2"></i>Two-Factor Authentication
                    </div>
                </div>
                <div class="card-body">
                    @if($user->two_factor_enabled)
                        <!-- 2FA Enabled State -->
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="alert alert-success" role="alert">
                                    <div class="d-flex align-items-center">
                                        <i class="ri-checkbox-circle-line fs-20 me-2"></i>
                                        <div>
                                            <strong>Two-Factor Authentication is Enabled</strong>
                                            <p class="mb-0 mt-1">Your account is protected with two-factor authentication.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <br><br>
                        
                        <!-- Recovery Codes Section -->
                        <div class="row mb-4">
                            <div class="col-xl-12">
                                <div class="card custom-card border-warning">
                                    <div class="card-header bg-warning-transparent">
                                        <div class="card-title text-warning">
                                            <i class="ri-key-line me-1"></i>Recovery Codes
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-3">
                                            Recovery codes can be used to access your account if you lose access to your authenticator app. 
                                            <strong>Each code can only be used once.</strong>
                                        </p>
                                        
                                        @if($remainingRecoveryCodes > 0)
                                            <div class="alert alert-info mb-3">
                                                <i class="ri-information-line me-1"></i>
                                                <strong>You have {{ $remainingRecoveryCodes }} recovery code(s) remaining.</strong>
                                                @if($remainingRecoveryCodes <= 2)
                                                    <span class="text-danger d-block mt-1">⚠️ Warning: You have very few recovery codes left. Consider regenerating new ones.</span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="alert alert-danger mb-3">
                                                <i class="ri-error-warning-line me-1"></i>
                                                <strong>You have no recovery codes remaining.</strong> Please regenerate new recovery codes to ensure you can access your account if you lose your authenticator device.
                                            </div>
                                        @endif
                                        
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <p class="mb-0 text-muted fs-12">
                                                    <i class="ri-information-line me-1"></i>
                                                    Regenerating recovery codes will invalidate all existing recovery codes. You'll need to save the new codes.
                                                </p>
                                            </div>
                                            <button type="button" class="btn btn-warning btn-wave" data-bs-toggle="modal" data-bs-target="#regenerateRecoveryCodesModal">
                                                <i class="ri-refresh-line me-1"></i>Regenerate Recovery Codes
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="card custom-card border-danger">
                                    <div class="card-header bg-danger-transparent">
                                        <div class="card-title text-danger">
                                            <i class="ri-error-warning-line me-1"></i>Disable Two-Factor Authentication
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-3">Disabling two-factor authentication will make your account less secure. You'll need to enter your password and a verification code from your authenticator app to confirm.</p>
                                        <form action="{{ route('account.security.two-factor.disable') }}" method="POST" id="disable-2fa-form">
                                            @csrf
                                            <div class="row">
                                                <div class="col-xl-12 mb-3">
                                                    <label for="disable-password" class="form-label">Enter Your Password :</label>
                                                    <div class="input-group">
                                                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                                               id="disable-password" name="password" required>
                                                        <button class="btn btn-light" type="button" onclick="togglePasswordVisibility('disable-password', this)">
                                                            <i class="ri-eye-line" id="disable-password-icon"></i>
                                                        </button>
                                                    </div>
                                                    @error('password')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-xl-12 mb-3">
                                                    <label for="disable-verification-code" class="form-label">Enter Verification Code from Authenticator App :</label>
                                                    <input type="text" 
                                                           class="form-control text-center fs-20 fw-bold letter-spacing-2 @error('verification_code') is-invalid @enderror" 
                                                           id="disable-verification-code" 
                                                           name="verification_code" 
                                                           maxlength="6" 
                                                           pattern="[0-9]{6}" 
                                                           placeholder="000000"
                                                           required
                                                           autocomplete="off"
                                                           style="letter-spacing: 0.5em; font-size: 24px;">
                                                    @error('verification_code')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                    <small class="text-muted">Enter the 6-digit code from your authenticator app.</small>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-xl-12">
                                                    <button type="submit" class="btn btn-danger btn-wave" onclick="return confirmDisable2FA(event)">
                                                        <i class="ri-close-circle-line me-1"></i>Disable Two-Factor Authentication
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- 2FA Disabled State - Setup -->
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="alert alert-warning" role="alert">
                                    <div class="d-flex align-items-center">
                                        <i class="ri-alert-line fs-20 me-2"></i>
                                        <div>
                                            <strong>Two-Factor Authentication is Disabled</strong>
                                            <p class="mb-0 mt-1">Enable two-factor authentication to add an extra layer of security to your account.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <br><br>
                        
                        @if($secret && $qrCodeSvg)
                            <div class="row mb-4">
                                <div class="col-xl-12">
                                    <div class="card custom-card border-primary">
                                        <div class="card-header bg-primary-transparent">
                                            <div class="card-title text-primary">
                                                <i class="ri-qr-scan-line me-1"></i>Step 1: Scan QR Code
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-3">Scan this QR code with your authenticator app (Google Authenticator, Authy, Microsoft Authenticator, etc.)</p>
                                            <div class="text-center mb-3">
                                                <style>
                                                    .qr-code-wrapper {
                                                        max-width: 100%;
                                                        overflow: hidden;
                                                        display: inline-block;
                                                    }
                                                    .qr-code-wrapper svg {
                                                        max-width: 100%;
                                                        height: auto;
                                                        width: 100%;
                                                    }
                                                    @media (max-width: 576px) {
                                                        .qr-code-wrapper {
                                                            max-width: 250px;
                                                        }
                                                    }
                                                </style>
                                                <div class="qr-code-wrapper p-2 p-md-3 bg-white rounded border">
                                                    {!! $qrCodeSvg !!}
                                                </div>
                                            </div>
                                            <div class="alert alert-info">
                                                <strong>Secret Key:</strong> 
                                                <code class="d-block mt-2 p-2 bg-light rounded">{{ $secret }}</code>
                                                <small class="text-muted d-block mt-2">If you can't scan the QR code, enter this secret key manually in your authenticator app.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-xl-12">
                                    <div class="card custom-card border-success">
                                        <div class="card-header bg-success-transparent">
                                            <div class="card-title text-success">
                                                <i class="ri-checkbox-circle-line me-1"></i>Step 2: Verify and Enable
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-3">Enter the 6-digit code from your authenticator app to verify and enable two-factor authentication.</p>
                                            <form action="{{ route('account.security.two-factor.enable') }}" method="POST" id="enable-2fa-form">
                                                @csrf
                                                <div class="row">
                                                    <div class="col-xl-12 mb-3">
                                                        <label for="verification-code" class="form-label">Enter Verification Code :</label>
                                                        <input type="text" 
                                                               class="form-control text-center fs-20 fw-bold letter-spacing-2 @error('verification_code') is-invalid @enderror" 
                                                               id="verification-code" 
                                                               name="verification_code" 
                                                               maxlength="6" 
                                                               pattern="[0-9]{6}" 
                                                               placeholder="000000"
                                                               required
                                                               autocomplete="off"
                                                               style="letter-spacing: 0.5em; font-size: 24px;">
                                                        @error('verification_code')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                        <small class="text-muted">Enter the 6-digit code from your authenticator app.</small>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-xl-12">
                                                        <button type="submit" class="btn btn-success btn-wave">
                                                            <i class="ri-checkbox-circle-line me-1"></i>Enable Two-Factor Authentication
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="row">
                                <div class="col-xl-12">
                                    <div class="card custom-card">
                                        <div class="card-body text-center py-5">
                                            <span class="avatar avatar-xl avatar-rounded bg-primary-transparent mb-3">
                                                <i class="ri-shield-user-line fs-2 text-primary"></i>
                                            </span>
                                            <h5 class="mb-2">Two-Factor Authentication Not Set Up</h5>
                                            <p class="text-muted mb-4">Click the button below to start setting up two-factor authentication.</p>
                                            <a href="{{ route('account.security.two-factor') }}" class="btn btn-primary btn-wave">
                                                <i class="ri-add-line me-1"></i>Set Up Two-Factor Authentication
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->

    <!-- Regenerate Recovery Codes Modal -->
    <div class="modal fade" id="regenerateRecoveryCodesModal" tabindex="-1" aria-labelledby="regenerateRecoveryCodesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="regenerateRecoveryCodesModalLabel">
                        <i class="ri-refresh-line me-1"></i>Regenerate Recovery Codes
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('account.security.two-factor.regenerate-recovery-codes') }}" method="POST" id="regenerate-recovery-codes-form">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-warning mb-3">
                            <i class="ri-alert-line me-1"></i>
                            <strong>Security Verification Required</strong>
                            <p class="mb-0 mt-1">For security reasons, you must verify your password and provide a code from your authenticator app to regenerate recovery codes.</p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="regenerate-password" class="form-label">Enter Your Password :</label>
                            <div class="input-group">
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                       id="regenerate-password" name="password" 
                                       value="{{ old('password') }}"
                                       required>
                                <button class="btn btn-light" type="button" onclick="togglePasswordVisibility('regenerate-password', this)">
                                    <i class="ri-eye-line" id="regenerate-password-icon"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="regenerate-verification-code" class="form-label">Enter Verification Code from Authenticator App :</label>
                            <input type="text" 
                                   class="form-control text-center fs-20 fw-bold letter-spacing-2 @error('verification_code') is-invalid @enderror" 
                                   id="regenerate-verification-code" 
                                   name="verification_code" 
                                   value="{{ old('verification_code') }}"
                                   maxlength="6" 
                                   pattern="[0-9]{6}" 
                                   placeholder="000000"
                                   required
                                   autocomplete="off"
                                   style="letter-spacing: 0.5em; font-size: 24px;">
                            @error('verification_code')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Enter the 6-digit code from your authenticator app.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-wave" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-warning btn-wave">
                            <i class="ri-refresh-line me-1"></i>Regenerate Codes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- Sweetalerts JS -->
    <script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>
    
    <script>
        // Toggle password visibility
        function togglePasswordVisibility(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
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

        // Auto-format OTP input (numbers only)
        document.addEventListener('DOMContentLoaded', function() {
            const otpInput = document.getElementById('verification-code');
            const disableOtpInput = document.getElementById('disable-verification-code');
            const regenerateOtpInput = document.getElementById('regenerate-verification-code');
            
            if (otpInput) {
                otpInput.addEventListener('input', function(e) {
                    // Remove non-numeric characters
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
                
                // Auto-focus on page load
                setTimeout(() => otpInput.focus(), 100);
            }
            
            if (disableOtpInput) {
                disableOtpInput.addEventListener('input', function(e) {
                    // Remove non-numeric characters
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            }
            
            if (regenerateOtpInput) {
                regenerateOtpInput.addEventListener('input', function(e) {
                    // Remove non-numeric characters
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
                
                // Focus on input when modal is shown
                const regenerateModal = document.getElementById('regenerateRecoveryCodesModal');
                if (regenerateModal) {
                    regenerateModal.addEventListener('shown.bs.modal', function() {
                        setTimeout(() => regenerateOtpInput.focus(), 300);
                    });
                }
            }
            
            // Auto-open modal if there are validation errors from regenerate form
            @if(session('regenerate_form_errors') && ($errors->has('password') || $errors->has('verification_code')))
                const regenerateModalEl = document.getElementById('regenerateRecoveryCodesModal');
                if (regenerateModalEl) {
                    const regenerateModal = new bootstrap.Modal(regenerateModalEl);
                    regenerateModal.show();
                }
            @endif
        });

        // Confirm disable 2FA
        function confirmDisable2FA(event) {
            if (typeof Swal !== 'undefined') {
                event.preventDefault();
                Swal.fire({
                    title: 'Disable Two-Factor Authentication?',
                    html: 'Are you sure you want to disable two-factor authentication?<br><br>This will make your account less secure.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="ri-close-circle-line me-1"></i>Yes, disable it!',
                    cancelButtonText: '<i class="ri-close-line me-1"></i>Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('disable-2fa-form').submit();
                    }
                });
                return false;
            }
            return confirm('Are you sure you want to disable two-factor authentication?');
        }

        // Copy recovery codes
        function copyRecoveryCodes() {
            const codes = @json(session('recovery_codes', []));
            const codesText = codes.join('\n');
            
            navigator.clipboard.writeText(codesText).then(function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Copied!',
                        text: 'Recovery codes copied to clipboard.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    alert('Recovery codes copied to clipboard!');
                }
            });
        }
    </script>
@endsection

