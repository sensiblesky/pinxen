@extends('layouts.master')

@section('styles')
    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.css')}}">
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Recaptcha Configuration</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                <li class="breadcrumb-item active" aria-current="page">Recaptcha</li>
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
    <form action="{{ route('panel.recaptcha.update') }}" method="POST" id="recaptcha-form">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">Recaptcha Configuration</div>
                        <div class="card-subtitle text-muted">We recommend using Google V2, Hcaptcha & Friendly Captcha as they are stable on this version</div>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3 border-0" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" role="tab" href="#google-v2-tab" aria-selected="true">
                                    <i class="ri-shield-check-line me-1"></i>Google V2
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#google-v3-tab" aria-selected="false">
                                    <i class="ri-shield-check-line me-1"></i>Google V3
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#hcaptcha-tab" aria-selected="false">
                                    <i class="ri-shield-star-line me-1"></i>Hcaptcha
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#friendly-tab" aria-selected="false">
                                    <i class="ri-shield-user-line me-1"></i>Friendly Captcha
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <!-- Google V2 Tab -->
                            <div class="tab-pane fade show active" id="google-v2-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="google_v2_site_key" class="form-label">Site Key</label>
                                        <input type="text" class="form-control @error('google_v2_site_key') is-invalid @enderror" id="google_v2_site_key" name="google_v2_site_key" value="{{ old('google_v2_site_key', $settings['google_v2_site_key'] ?? '') }}" placeholder="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI">
                                        @error('google_v2_site_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="google_v2_secret_key" class="form-label">Secret Key</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('google_v2_secret_key') is-invalid @enderror" id="google_v2_secret_key" name="google_v2_secret_key" value="{{ old('google_v2_secret_key', $settings['google_v2_secret_key'] ?? '') }}" placeholder="6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe">
                                            <button class="btn btn-light" type="button" id="toggle_google_v2_secret">
                                                <i class="ri-eye-line" id="google_v2_secret_icon"></i>
                                            </button>
                                        </div>
                                        @error('google_v2_secret_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current secret</span>
                                    </div>
                                    <div class="col-xl-12">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <label class="form-label mb-1">Is Default?</label>
                                                <p class="fs-12 text-muted mb-0">Set this as the default captcha provider</p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="google_v2_is_default" name="google_v2_is_default" value="1" {{ old('google_v2_is_default', $settings['google_v2_is_default'] ?? '0') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="google_v2_is_default"></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Google V3 Tab -->
                            <div class="tab-pane fade" id="google-v3-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="google_v3_site_key" class="form-label">Site Key</label>
                                        <input type="text" class="form-control @error('google_v3_site_key') is-invalid @enderror" id="google_v3_site_key" name="google_v3_site_key" value="{{ old('google_v3_site_key', $settings['google_v3_site_key'] ?? '') }}" placeholder="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI">
                                        @error('google_v3_site_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="google_v3_secret_key" class="form-label">Secret Key</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('google_v3_secret_key') is-invalid @enderror" id="google_v3_secret_key" name="google_v3_secret_key" value="{{ old('google_v3_secret_key', $settings['google_v3_secret_key'] ?? '') }}" placeholder="6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe">
                                            <button class="btn btn-light" type="button" id="toggle_google_v3_secret">
                                                <i class="ri-eye-line" id="google_v3_secret_icon"></i>
                                            </button>
                                        </div>
                                        @error('google_v3_secret_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current secret</span>
                                    </div>
                                    <div class="col-xl-12">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <label class="form-label mb-1">Is Default?</label>
                                                <p class="fs-12 text-muted mb-0">Set this as the default captcha provider</p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="google_v3_is_default" name="google_v3_is_default" value="1" {{ old('google_v3_is_default', $settings['google_v3_is_default'] ?? '0') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="google_v3_is_default"></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hcaptcha Tab -->
                            <div class="tab-pane fade" id="hcaptcha-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="hcaptcha_site_key" class="form-label">Site Key</label>
                                        <input type="text" class="form-control @error('hcaptcha_site_key') is-invalid @enderror" id="hcaptcha_site_key" name="hcaptcha_site_key" value="{{ old('hcaptcha_site_key', $settings['hcaptcha_site_key'] ?? '') }}" placeholder="10000000-ffff-ffff-ffff-000000000001">
                                        @error('hcaptcha_site_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="hcaptcha_secret_key" class="form-label">Secret Key</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('hcaptcha_secret_key') is-invalid @enderror" id="hcaptcha_secret_key" name="hcaptcha_secret_key" value="{{ old('hcaptcha_secret_key', $settings['hcaptcha_secret_key'] ?? '') }}" placeholder="0x0000000000000000000000000000000000000000">
                                            <button class="btn btn-light" type="button" id="toggle_hcaptcha_secret">
                                                <i class="ri-eye-line" id="hcaptcha_secret_icon"></i>
                                            </button>
                                        </div>
                                        @error('hcaptcha_secret_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current secret</span>
                                    </div>
                                    <div class="col-xl-12">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <label class="form-label mb-1">Is Default?</label>
                                                <p class="fs-12 text-muted mb-0">Set this as the default captcha provider</p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="hcaptcha_is_default" name="hcaptcha_is_default" value="1" {{ old('hcaptcha_is_default', $settings['hcaptcha_is_default'] ?? '0') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="hcaptcha_is_default"></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Friendly Captcha Tab -->
                            <div class="tab-pane fade" id="friendly-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="friendly_captcha_site_key" class="form-label">Site Key</label>
                                        <input type="text" class="form-control @error('friendly_captcha_site_key') is-invalid @enderror" id="friendly_captcha_site_key" name="friendly_captcha_site_key" value="{{ old('friendly_captcha_site_key', $settings['friendly_captcha_site_key'] ?? '') }}" placeholder="Your Friendly Captcha Site Key">
                                        @error('friendly_captcha_site_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="friendly_captcha_secret_key" class="form-label">Secret Key</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('friendly_captcha_secret_key') is-invalid @enderror" id="friendly_captcha_secret_key" name="friendly_captcha_secret_key" value="{{ old('friendly_captcha_secret_key', $settings['friendly_captcha_secret_key'] ?? '') }}" placeholder="Your Friendly Captcha Secret Key">
                                            <button class="btn btn-light" type="button" id="toggle_friendly_secret">
                                                <i class="ri-eye-line" id="friendly_secret_icon"></i>
                                            </button>
                                        </div>
                                        @error('friendly_captcha_secret_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current secret</span>
                                    </div>
                                    <div class="col-xl-12">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <label class="form-label mb-1">Is Default?</label>
                                                <p class="fs-12 text-muted mb-0">Set this as the default captcha provider</p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="friendly_captcha_is_default" name="friendly_captcha_is_default" value="1" {{ old('friendly_captcha_is_default', $settings['friendly_captcha_is_default'] ?? '0') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="friendly_captcha_is_default"></label>
                                            </div>
                                        </div>
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
    <!-- Sweetalerts JS -->
    <script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>
    
    <script>
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

        // Google V2 Secret toggle
        document.getElementById('toggle_google_v2_secret')?.addEventListener('click', function() {
            togglePasswordVisibility('google_v2_secret_key', 'google_v2_secret_icon');
        });

        // Google V3 Secret toggle
        document.getElementById('toggle_google_v3_secret')?.addEventListener('click', function() {
            togglePasswordVisibility('google_v3_secret_key', 'google_v3_secret_icon');
        });

        // Hcaptcha Secret toggle
        document.getElementById('toggle_hcaptcha_secret')?.addEventListener('click', function() {
            togglePasswordVisibility('hcaptcha_secret_key', 'hcaptcha_secret_icon');
        });

        // Friendly Captcha Secret toggle
        document.getElementById('toggle_friendly_secret')?.addEventListener('click', function() {
            togglePasswordVisibility('friendly_captcha_secret_key', 'friendly_secret_icon');
        });

        // Ensure only one default is selected
        document.addEventListener('DOMContentLoaded', function() {
            const defaultCheckboxes = [
                'google_v2_is_default',
                'google_v3_is_default',
                'hcaptcha_is_default',
                'friendly_captcha_is_default'
            ];

            defaultCheckboxes.forEach(checkboxId => {
                const checkbox = document.getElementById(checkboxId);
                if (checkbox) {
                    checkbox.addEventListener('change', function() {
                        if (this.checked) {
                            // Uncheck all other checkboxes
                            defaultCheckboxes.forEach(otherId => {
                                if (otherId !== checkboxId) {
                                    const otherCheckbox = document.getElementById(otherId);
                                    if (otherCheckbox) {
                                        otherCheckbox.checked = false;
                                    }
                                }
                            });
                        }
                    });
                }
            });
        });
    </script>
@endsection


