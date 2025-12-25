@extends('layouts.master')

@section('styles')
    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.css')}}">
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Change Password</h1>
        </div>
        <ol class="breadcrumb mb-0 mt-2">
            <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
            <li class="breadcrumb-item"><a href="javascript:void(0);">Account Management</a></li>
            <li class="breadcrumb-item active" aria-current="page">Change Password</li>
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

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="ri-lock-password-line me-2"></i>Change Password
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('account.security.password.update') }}" method="POST" id="password-update-form">
                        @csrf
                        
                        <div class="row gy-3">
                            <div class="col-xl-12">
                                <div class="alert alert-info" role="alert">
                                    <i class="ri-information-line me-1"></i>
                                    <strong>Password Requirements:</strong> Your password must be at least 8 characters long and should include a mix of letters, numbers, and symbols.
                                </div>
                            </div>
                            <div class="col-xl-12">
                                <label for="current-password" class="form-label">Current Password :</label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('current_password') is-invalid @enderror" 
                                           id="current-password" name="current_password" required>
                                    <button class="btn btn-light" type="button" onclick="togglePasswordVisibility('current-password', this)">
                                        <i class="ri-eye-line" id="current-password-icon"></i>
                                    </button>
                                </div>
                                @error('current_password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-xl-12">
                                <label for="new-password" class="form-label">New Password :</label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                           id="new-password" name="password" required>
                                    <button class="btn btn-light" type="button" onclick="togglePasswordVisibility('new-password', this)">
                                        <i class="ri-eye-line" id="new-password-icon"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-xl-12">
                                <label for="confirm-password" class="form-label">Confirm New Password :</label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" 
                                           id="confirm-password" name="password_confirmation" required>
                                    <button class="btn btn-light" type="button" onclick="togglePasswordVisibility('confirm-password', this)">
                                        <i class="ri-eye-line" id="confirm-password-icon"></i>
                                    </button>
                                </div>
                                @error('password_confirmation')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Save Changes Button -->
                        <div class="row mt-4">
                            <div class="col-xl-12">
                                <div class="btn-list float-end">
                                    <button type="submit" class="btn btn-primary btn-wave">
                                        <i class="ri-save-line me-1"></i>Update Password
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->
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
    </script>
@endsection

