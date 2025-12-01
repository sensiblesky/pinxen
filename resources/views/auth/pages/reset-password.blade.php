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
                                    <h4 class="mb-1 fw-semibold">Reset Password</h4>
                                    <p class="mb-4 text-muted fw-normal">Set your new password here.</p>
                                </div>

                                <!-- Session Status -->
                                @if (session('status'))
                                    <div class="alert alert-success mb-4" role="alert">
                                        {{ session('status') }}
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

                                <form method="POST" action="{{ route('password.store') }}">
                                    @csrf

                                    <!-- Password Reset Token -->
                                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                                    <div class="row gy-3">
                                        <div class="col-xl-12">
                                            <label for="email" class="form-label text-default">Email</label>
                                            <input type="email" 
                                                   class="form-control @error('email') is-invalid @enderror" 
                                                   id="email" 
                                                   name="email"
                                                   placeholder="Enter Email" 
                                                   value="{{ old('email', $request->email) }}" 
                                                   required 
                                                   readonly
                                                   autofocus 
                                                   autocomplete="username">
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-xl-12">
                                            <label for="password" class="form-label text-default">New Password</label>
                                            <div class="position-relative">
                                                <input type="password" 
                                                       class="form-control form-control-lg @error('password') is-invalid @enderror" 
                                                       id="password" 
                                                       name="password"
                                                       placeholder="Enter new password" 
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
                                        <div class="col-xl-12">
                                            <label for="password_confirmation" class="form-label text-default">Confirm Password</label>
                                            <div class="position-relative">
                                                <input type="password" 
                                                       class="form-control form-control-lg" 
                                                       id="password_confirmation" 
                                                       name="password_confirmation"
                                                       placeholder="Confirm password" 
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
                                        <button type="submit" class="btn btn-primary">Reset Password</button>
                                    </div>
                                    <div class="text-center mt-3 fw-medium">
                                        Don't want to reset? <a href="{{ route('login') }}" class="text-primary">Login Here</a>
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




