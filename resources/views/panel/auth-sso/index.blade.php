@extends('layouts.master')

@section('styles')
    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.css')}}">
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Auth & Single Sign On</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                <li class="breadcrumb-item active" aria-current="page">Auth & SSO</li>
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
    <form action="{{ route('panel.auth-sso.update') }}" method="POST" id="auth-sso-form">
        @csrf
        @method('PUT')
        
        <div class="row">
            <!-- Single Sign On Providers -->
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">Single Sign On (SSO) Providers</div>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3 border-0" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" role="tab" href="#google-tab" aria-selected="true">
                                    <i class="ri-google-line me-1"></i>Google
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#linkedin-tab" aria-selected="false">
                                    <i class="ri-linkedin-line me-1"></i>LinkedIn
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#twitter-tab" aria-selected="false">
                                    <i class="ri-twitter-line me-1"></i>Twitter
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#facebook-tab" aria-selected="false">
                                    <i class="ri-facebook-line me-1"></i>Facebook
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#github-tab" aria-selected="false">
                                    <i class="ri-github-line me-1"></i>GitHub
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <!-- Google Tab -->
                            <div class="tab-pane fade show active" id="google-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="google_client_id" class="form-label">Client ID</label>
                                        <input type="text" class="form-control @error('google_client_id') is-invalid @enderror" id="google_client_id" name="google_client_id" value="{{ old('google_client_id', $settings['google_client_id'] ?? '') }}" placeholder="Your Google Client ID">
                                        @error('google_client_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="google_client_secret" class="form-label">Client Secret</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('google_client_secret') is-invalid @enderror" id="google_client_secret" name="google_client_secret" value="{{ old('google_client_secret', $settings['google_client_secret'] ?? '') }}" placeholder="Your Google Client Secret">
                                            <button class="btn btn-light" type="button" id="toggle_google_secret">
                                                <i class="ri-eye-line" id="google_secret_icon"></i>
                                            </button>
                                        </div>
                                        @error('google_client_secret')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current secret</span>
                                    </div>
                                    <div class="col-xl-12">
                                        <label for="google_redirect_url" class="form-label">Redirect URL</label>
                                        <input type="url" class="form-control @error('google_redirect_url') is-invalid @enderror" id="google_redirect_url" name="google_redirect_url" value="{{ old('google_redirect_url', $settings['google_redirect_url'] ?? '') }}" placeholder="https://yourdomain.com/auth/google/callback">
                                        @error('google_redirect_url')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- LinkedIn Tab -->
                            <div class="tab-pane fade" id="linkedin-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="linkedin_client_id" class="form-label">Client ID</label>
                                        <input type="text" class="form-control @error('linkedin_client_id') is-invalid @enderror" id="linkedin_client_id" name="linkedin_client_id" value="{{ old('linkedin_client_id', $settings['linkedin_client_id'] ?? '') }}" placeholder="Your LinkedIn Client ID">
                                        @error('linkedin_client_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="linkedin_client_secret" class="form-label">Client Secret</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('linkedin_client_secret') is-invalid @enderror" id="linkedin_client_secret" name="linkedin_client_secret" value="{{ old('linkedin_client_secret', $settings['linkedin_client_secret'] ?? '') }}" placeholder="Your LinkedIn Client Secret">
                                            <button class="btn btn-light" type="button" id="toggle_linkedin_secret">
                                                <i class="ri-eye-line" id="linkedin_secret_icon"></i>
                                            </button>
                                        </div>
                                        @error('linkedin_client_secret')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current secret</span>
                                    </div>
                                    <div class="col-xl-12">
                                        <label for="linkedin_redirect_url" class="form-label">Redirect URL</label>
                                        <input type="url" class="form-control @error('linkedin_redirect_url') is-invalid @enderror" id="linkedin_redirect_url" name="linkedin_redirect_url" value="{{ old('linkedin_redirect_url', $settings['linkedin_redirect_url'] ?? '') }}" placeholder="https://yourdomain.com/auth/linkedin/callback">
                                        @error('linkedin_redirect_url')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Twitter Tab -->
                            <div class="tab-pane fade" id="twitter-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="twitter_client_id" class="form-label">Client ID</label>
                                        <input type="text" class="form-control @error('twitter_client_id') is-invalid @enderror" id="twitter_client_id" name="twitter_client_id" value="{{ old('twitter_client_id', $settings['twitter_client_id'] ?? '') }}" placeholder="Your Twitter Client ID">
                                        @error('twitter_client_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="twitter_client_secret" class="form-label">Client Secret</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('twitter_client_secret') is-invalid @enderror" id="twitter_client_secret" name="twitter_client_secret" value="{{ old('twitter_client_secret', $settings['twitter_client_secret'] ?? '') }}" placeholder="Your Twitter Client Secret">
                                            <button class="btn btn-light" type="button" id="toggle_twitter_secret">
                                                <i class="ri-eye-line" id="twitter_secret_icon"></i>
                                            </button>
                                        </div>
                                        @error('twitter_client_secret')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current secret</span>
                                    </div>
                                    <div class="col-xl-12">
                                        <label for="twitter_redirect_url" class="form-label">Redirect URL</label>
                                        <input type="url" class="form-control @error('twitter_redirect_url') is-invalid @enderror" id="twitter_redirect_url" name="twitter_redirect_url" value="{{ old('twitter_redirect_url', $settings['twitter_redirect_url'] ?? '') }}" placeholder="https://yourdomain.com/auth/twitter/callback">
                                        @error('twitter_redirect_url')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Facebook Tab -->
                            <div class="tab-pane fade" id="facebook-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="facebook_client_id" class="form-label">Client ID</label>
                                        <input type="text" class="form-control @error('facebook_client_id') is-invalid @enderror" id="facebook_client_id" name="facebook_client_id" value="{{ old('facebook_client_id', $settings['facebook_client_id'] ?? '') }}" placeholder="Your Facebook Client ID">
                                        @error('facebook_client_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="facebook_client_secret" class="form-label">Client Secret</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('facebook_client_secret') is-invalid @enderror" id="facebook_client_secret" name="facebook_client_secret" value="{{ old('facebook_client_secret', $settings['facebook_client_secret'] ?? '') }}" placeholder="Your Facebook Client Secret">
                                            <button class="btn btn-light" type="button" id="toggle_facebook_secret">
                                                <i class="ri-eye-line" id="facebook_secret_icon"></i>
                                            </button>
                                        </div>
                                        @error('facebook_client_secret')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current secret</span>
                                    </div>
                                    <div class="col-xl-12">
                                        <label for="facebook_redirect_url" class="form-label">Redirect URL</label>
                                        <input type="url" class="form-control @error('facebook_redirect_url') is-invalid @enderror" id="facebook_redirect_url" name="facebook_redirect_url" value="{{ old('facebook_redirect_url', $settings['facebook_redirect_url'] ?? '') }}" placeholder="https://yourdomain.com/auth/facebook/callback">
                                        @error('facebook_redirect_url')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- GitHub Tab -->
                            <div class="tab-pane fade" id="github-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="github_client_id" class="form-label">Client ID</label>
                                        <input type="text" class="form-control @error('github_client_id') is-invalid @enderror" id="github_client_id" name="github_client_id" value="{{ old('github_client_id', $settings['github_client_id'] ?? '') }}" placeholder="Your GitHub Client ID">
                                        @error('github_client_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="github_client_secret" class="form-label">Client Secret</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('github_client_secret') is-invalid @enderror" id="github_client_secret" name="github_client_secret" value="{{ old('github_client_secret', $settings['github_client_secret'] ?? '') }}" placeholder="Your GitHub Client Secret">
                                            <button class="btn btn-light" type="button" id="toggle_github_secret">
                                                <i class="ri-eye-line" id="github_secret_icon"></i>
                                            </button>
                                        </div>
                                        @error('github_client_secret')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current secret</span>
                                    </div>
                                    <div class="col-xl-12">
                                        <label for="github_redirect_url" class="form-label">Redirect URL</label>
                                        <input type="url" class="form-control @error('github_redirect_url') is-invalid @enderror" id="github_redirect_url" name="github_redirect_url" value="{{ old('github_redirect_url', $settings['github_redirect_url'] ?? '') }}" placeholder="https://yourdomain.com/auth/github/callback">
                                        @error('github_redirect_url')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Authentication Settings -->
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">Authentication Settings</div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row gy-3">
                            <div class="col-xl-6">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <label class="form-label mb-1">User Registration</label>
                                        <p class="fs-12 text-muted mb-0">Allow new users to register accounts</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="user_registration_enabled" name="user_registration_enabled" value="1" {{ old('user_registration_enabled', $settings['user_registration_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="user_registration_enabled"></label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <label class="form-label mb-1">User Login</label>
                                        <p class="fs-12 text-muted mb-0">Allow users to log in to their accounts</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="user_login_enabled" name="user_login_enabled" value="1" {{ old('user_login_enabled', $settings['user_login_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="user_login_enabled"></label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <label class="form-label mb-1">Force Email Verification</label>
                                        <p class="fs-12 text-muted mb-0">Require users to verify their email address</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="force_email_verification" name="force_email_verification" value="1" {{ old('force_email_verification', $settings['force_email_verification'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="force_email_verification"></label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <label class="form-label mb-1">Force Two Factor Authentication</label>
                                        <p class="fs-12 text-muted mb-0">Require 2FA for all user accounts</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="force_two_factor_authentication" name="force_two_factor_authentication" value="1" {{ old('force_two_factor_authentication', $settings['force_two_factor_authentication'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="force_two_factor_authentication"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="row mt-3">
            <div class="col-xl-12">
                <div class="card custom-card">
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

        // Google Secret toggle
        document.getElementById('toggle_google_secret')?.addEventListener('click', function() {
            togglePasswordVisibility('google_client_secret', 'google_secret_icon');
        });

        // LinkedIn Secret toggle
        document.getElementById('toggle_linkedin_secret')?.addEventListener('click', function() {
            togglePasswordVisibility('linkedin_client_secret', 'linkedin_secret_icon');
        });

        // Twitter Secret toggle
        document.getElementById('toggle_twitter_secret')?.addEventListener('click', function() {
            togglePasswordVisibility('twitter_client_secret', 'twitter_secret_icon');
        });

        // Facebook Secret toggle
        document.getElementById('toggle_facebook_secret')?.addEventListener('click', function() {
            togglePasswordVisibility('facebook_client_secret', 'facebook_secret_icon');
        });

        // GitHub Secret toggle
        document.getElementById('toggle_github_secret')?.addEventListener('click', function() {
            togglePasswordVisibility('github_client_secret', 'github_secret_icon');
        });
    </script>
@endsection


