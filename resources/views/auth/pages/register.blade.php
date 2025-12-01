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
                                    <h4 class="mb-1 fw-semibold">Create your account</h4>
                                    <p class="mb-4 text-muted fw-normal">Please enter valid credentials</p>
                                </div>

                                <!-- Error Message -->
                                @if (session('error'))
                                    <div class="alert alert-danger mb-4" role="alert">
                                        <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
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

                                @if(isset($registrationDisabled) && $registrationDisabled)
                                    <!-- Registration Disabled Message -->
                                    <div class="text-center py-4">
                                        <div class="mb-3">
                                            <span class="avatar avatar-xl avatar-rounded bg-danger-transparent text-danger">
                                                <i class="ri-lock-line fs-2"></i>
                                            </span>
                                        </div>
                                        <h5 class="mb-2">Registration Temporarily Disabled</h5>
                                        <p class="text-muted mb-4">User registration is currently disabled. Please contact the administrator for assistance.</p>
                                        <a href="{{ route('login') }}" class="btn btn-primary">Go to Login</a>
                                    </div>
                                @else
                                    <!-- Registration Form -->
                                    <form method="POST" action="{{ route('register') }}">
                                        @csrf
                                        <div class="row gy-3">
                                            <div class="col-xl-12"> 
                                                <label for="name" class="form-label text-default">Name</label> 
                                                <input type="text" 
                                                       class="form-control @error('name') is-invalid @enderror" 
                                                       id="name" 
                                                       name="name"
                                                       placeholder="Enter Name" 
                                                       value="{{ old('name') }}" 
                                                       required 
                                                       autofocus 
                                                       autocomplete="name"> 
                                                @error('name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-xl-12">
                                                <label for="email" class="form-label text-default">Email</label>
                                                <input type="email" 
                                                       class="form-control @error('email') is-invalid @enderror" 
                                                       id="email" 
                                                       name="email"
                                                       placeholder="Enter Email" 
                                                       value="{{ old('email') }}" 
                                                       required 
                                                       autocomplete="username">
                                                @error('email')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-xl-12 mb-2">
                                                <label for="password" class="form-label text-default d-block">Password</label>
                                                <div class="position-relative">
                                                    <input type="password" 
                                                           class="form-control @error('password') is-invalid @enderror" 
                                                           id="password" 
                                                           name="password"
                                                           placeholder="Enter Password" 
                                                           required 
                                                           autocomplete="new-password">
                                                    <a href="javascript:void(0);" 
                                                       class="show-password-button text-muted" 
                                                       onclick="createpassword('password',this)" 
                                                       id="button-addon2">
                                                        <i class="ri-eye-off-line align-middle"></i>
                                                    </a>
                                                </div>
                                                @error('password')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-xl-12 mb-2">
                                                <label for="password_confirmation" class="form-label text-default d-block">Confirm Password</label>
                                                <div class="position-relative">
                                                    <input type="password" 
                                                           class="form-control" 
                                                           id="password_confirmation" 
                                                           name="password_confirmation"
                                                           placeholder="Confirm Password" 
                                                           required 
                                                           autocomplete="new-password">
                                                    <a href="javascript:void(0);" 
                                                       class="show-password-button text-muted" 
                                                       onclick="createpassword('password_confirmation',this)" 
                                                       id="button-addon3">
                                                        <i class="ri-eye-off-line align-middle"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-grid mt-3">
                                            <button type="submit" class="btn btn-primary">Create Account</button>
                                        </div>
                                        <div class="text-center my-3 authentication-barrier">
                                            <span class="op-4 fs-13">OR</span>
                                        </div>
                                        <div class="d-grid mb-3">
                                            <button type="button" class="btn btn-white btn-w-lg border d-flex align-items-center justify-content-center flex-fill mb-3">
                                                <span class="avatar avatar-xs">
                                                    <img src="{{ asset('build/assets/images/media/apps/google.png') }}" alt="">
                                                </span>
                                                <span class="lh-1 ms-2 fs-13 text-default fw-medium">Sign up with Google</span>
                                            </button>
                                            <button type="button" class="btn btn-white btn-w-lg border d-flex align-items-center justify-content-center flex-fill">
                                                <span class="avatar avatar-xs flex-shrink-0">
                                                    <img src="{{ asset('build/assets/images/media/apps/facebook.png') }}" alt="">
                                                </span>
                                                <span class="lh-1 ms-2 fs-13 text-default fw-medium">Sign up with Facebook</span>
                                            </button>
                                        </div>
                                        <div class="text-center mt-3 fw-medium">
                                            Already have an account? <a href="{{ route('login') }}" class="text-primary">Login Here</a>
                                        </div>
                                    </form>
                                @endif
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
                            <h3 class="fw-semibold lh-base">Welcome to Dashboard</h3>
                            <p class="mb-0 text-muted fw-medium">Manage your website and content with ease using our powerful admin tools.</p>
                        </div>
                        <div>
                            <img src="{{ asset('build/assets/images/custom/media-72.png') }}" alt="" class="img-fluid">
                        </div>
                    </div>
                </div>
            </div>
        </div>

@endsection

@section('scripts')
        
        <!-- Show Password JS -->
        <script src="{{ asset('build/assets/show-password.js') }}"></script>

@endsection



