@extends('layouts.master')

@section('styles')

        <!-- Sweetalerts CSS -->
        <link rel="stylesheet" href="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.css')}}">

@endsection

@section('content')
	
                    <!-- Start::page-header -->
                    <div class="page-header-breadcrumb mb-3">
                        <div class="d-flex align-center justify-content-between flex-wrap">
                            <h1 class="page-title fw-medium fs-18 mb-0">User Profile Settings</h1>
                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ route('panel.users.index') }}" class="btn btn-sm btn-light btn-wave">
                                    <i class="ri-arrow-left-line me-1"></i>Go Back to Users
                                </a>
                            </div>
                        </div>
                        <ol class="breadcrumb mb-0 mt-2">
                            <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('panel.users.index') }}">Users</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Profile Settings</li>
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

                    <!-- Start::row-1 -->
                    <form action="{{ route('panel.users.update', $user) }}" method="POST" id="user-update-form" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="card custom-card">
                                    <div class="card-header">
                                        <div class="card-title">
                                            Account Information
                                        </div>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="row gy-3">
                                            <div class="col-xl-12">
                                                <div class="d-flex align-items-start flex-wrap gap-3">
                                                    <div>
                                                        <span class="avatar avatar-xxl" id="avatar-preview">
                                                            <img src="{{ $user->secure_avatar_url }}" alt="Profile Picture" id="avatar-img">
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="fw-medium d-block mb-2">Profile Picture</span>
                                                        <div class="btn-list mb-1">
                                                            <label for="avatar-upload" class="btn btn-sm btn-primary btn-wave" style="cursor: pointer;">
                                                                <i class="ri-upload-2-line me-1"></i>Change Image
                                                            </label>
                                                            <input type="file" id="avatar-upload" name="avatar" accept="image/jpeg,image/png,image/jpg,image/gif" style="display: none;" onchange="previewAvatar(this)">
                                                            <button type="button" class="btn btn-sm btn-light btn-wave" onclick="removeAvatar()">
                                                                <i class="ri-delete-bin-line me-1"></i>Remove
                                                            </button>
                                                        </div>
                                                        <span class="d-block fs-12 text-muted">Use JPEG, PNG, or GIF. Best size: 200x200 pixels. Keep it under 5MB</span>
                                                        <input type="hidden" name="remove_avatar" id="remove-avatar-flag" value="0">
                                                        @error('avatar')
                                                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-6">
                                                <label for="profile-user-name" class="form-label">User Name :</label>
                                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="profile-user-name" name="name" value="{{ old('name', $user->name) }}" required>
                                                @error('name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-xl-6">
                                                <label for="profile-email" class="form-label">Email :</label>
                                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="profile-email" name="email" value="{{ old('email', $user->email) }}" required>
                                                @error('email')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-xl-6">
                                                <label for="profile-phn-no" class="form-label">Phone No :</label>
                                                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="profile-phn-no" name="phone" value="{{ old('phone', $user->phone) }}">
                                                @error('phone')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-xl-6">
                                                <label for="profile-role" class="form-label">Role :</label>
                                                <select class="form-select @error('role') is-invalid @enderror" id="profile-role" name="role" required>
                                                    <option value="1" {{ old('role', $user->role) == 1 ? 'selected' : '' }}>Admin</option>
                                                    <option value="2" {{ old('role', $user->role) == 2 ? 'selected' : '' }}>User</option>
                                                </select>
                                                @error('role')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-xl-6">
                                                <label for="profile-language" class="form-label">Language :</label>
                                                <select class="form-control @error('language_id') is-invalid @enderror" id="profile-language" name="language_id" data-trigger>
                                                    <option value="">Select Language</option>
                                                    @foreach($languages as $language)
                                                        <option value="{{ $language->id }}" {{ old('language_id', $user->language_id) == $language->id ? 'selected' : '' }}>{{ $language->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('language_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-xl-6">
                                                <label for="profile-timezone" class="form-label">Timezone :</label>
                                                <select class="form-control @error('timezone_id') is-invalid @enderror" id="profile-timezone" name="timezone_id" data-trigger>
                                                    <option value="">Select Timezone</option>
                                                    @foreach($timezones as $timezone)
                                                        <option value="{{ $timezone->id }}" {{ old('timezone_id', $user->timezone_id) == $timezone->id ? 'selected' : '' }}>{{ $timezone->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('timezone_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--End::row-1 -->
                        
                        <!-- Save Changes Button -->
                        <div class="row mt-3">
                            <div class="col-xl-12">
                                <div class="card custom-card">
                                    <div class="card-footer">
                                        <div class="btn-list float-end">
                                            <button type="submit" class="btn btn-primary btn-wave">
                                                <i class="ri-save-line me-1"></i>Save Changes
                                            </button>
                                            <a href="{{ route('panel.users.index') }}" class="btn btn-secondary btn-wave">
                                                <i class="ri-arrow-left-line me-1"></i>Go Back
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Account Status Section - Independent from main form -->
                    <div class="row mt-3">
                        <div class="col-xl-12">
                            <div class="card custom-card border-danger">
                                <div class="card-header bg-danger-transparent">
                                    <div class="card-title text-danger">
                                        Account Status
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-sm-flex d-block align-items-top justify-content-between">
                                        <div class="w-50">
                                            <p class="fs-14 mb-1 fw-medium">Account Status</p>
                                            <p class="fs-12 mb-0 text-muted">
                                                @if($user->is_active)
                                                    This account is currently <strong class="text-success">Active</strong>. Deactivating will prevent the user from logging in.
                                                @else
                                                    This account is currently <strong class="text-danger">Deactivated</strong>. Activating will allow the user to log in again.
                                                @endif
                                            </p>
                                        </div>
                                        <div>
                                            @if($user->id === auth()->id())
                                                <button type="button" class="btn btn-light btn-wave" disabled title="You cannot deactivate your own account">
                                                    <i class="ri-error-warning-line me-1"></i>Cannot Deactivate Own Account
                                                </button>
                                            @else
                                                <form action="{{ route('panel.users.toggle-status', $user) }}" method="POST" class="d-inline" id="toggle-status-form">
                                                    @csrf
                                                    <button type="button" class="btn {{ $user->is_active ? 'btn-danger' : 'btn-success' }} btn-wave" id="toggle-account-status-btn" onclick="confirmToggleStatus(event, {{ $user->is_active ? 'true' : 'false' }}, '{{ addslashes($user->name) }}')">
                                                        <i class="ri-{{ $user->is_active ? 'close-circle' : 'check-circle' }}-line me-1"></i>
                                                        {{ $user->is_active ? 'Deactivate Account' : 'Activate Account' }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

@endsection

@section('scripts')
	
        <!-- Choices JS -->
        <script src="{{asset('build/assets/libs/choices.js/public/assets/scripts/choices.min.js')}}"></script>
        
        <!-- Sweetalerts JS -->
        <script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>
        
        <script>
            // Initialize Choices.js for language and timezone selects
            document.addEventListener('DOMContentLoaded', function() {
                const languageSelect = document.querySelector('#profile-language');
                const timezoneSelect = document.querySelector('#profile-timezone');
                
                if (languageSelect) {
                    new Choices(languageSelect, {
                        searchEnabled: true,
                        placeholder: true,
                        placeholderValue: 'Select Language',
                    });
                }
                
                if (timezoneSelect) {
                    new Choices(timezoneSelect, {
                        searchEnabled: true,
                        placeholder: true,
                        placeholderValue: 'Select Timezone',
                    });
                }
            });

            // Image preview functionality
            function previewAvatar(input) {
                if (input.files && input.files[0]) {
                    const file = input.files[0];
                    
                    // Validate file size (5MB max)
                    if (file.size > 5 * 1024 * 1024) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'File Too Large',
                                text: 'File size must be less than 5MB'
                            });
                        } else {
                            alert('File size must be less than 5MB');
                        }
                        input.value = '';
                        return;
                    }
                    
                    // Validate file type
                    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                    if (!validTypes.includes(file.type)) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Invalid File Type',
                                text: 'Please select a valid image file (JPEG, PNG, or GIF)'
                            });
                        } else {
                            alert('Please select a valid image file (JPEG, PNG, or GIF)');
                        }
                        input.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('avatar-img').src = e.target.result;
                        document.getElementById('remove-avatar-flag').value = '0';
                    };
                    reader.readAsDataURL(file);
                }
            }

            function removeAvatar() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Remove Profile Picture?',
                        text: 'Are you sure you want to remove the profile picture?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, remove it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('avatar-img').src = '{{ asset("build/assets/images/faces/9.jpg") }}';
                            document.getElementById('avatar-upload').value = '';
                            document.getElementById('remove-avatar-flag').value = '1';
                        }
                    });
                } else {
                    if (confirm('Are you sure you want to remove the profile picture?')) {
                        document.getElementById('avatar-img').src = '{{ asset("build/assets/images/faces/9.jpg") }}';
                        document.getElementById('avatar-upload').value = '';
                        document.getElementById('remove-avatar-flag').value = '1';
                    }
                }
            }
            
            // Account status toggle confirmation function
            function confirmToggleStatus(event, isActive, userName) {
                event.preventDefault();
                event.stopPropagation();
                
                // Find the form - it's now standalone outside the main form
                const form = document.getElementById('toggle-status-form');
                
                if (!form) {
                    console.error('Toggle status form not found');
                    // Try alternative method
                    const altForm = document.querySelector('form[action*="toggle-status"]');
                    if (altForm) {
                        submitToggleForm(altForm, isActive, userName);
                    } else {
                        alert('Form not found. Please refresh the page.');
                    }
                    return;
                }
                
                submitToggleForm(form, isActive, userName);
            }
            
            // Helper function to submit the form with confirmation
            function submitToggleForm(form, isActive, userName) {
                // Check if SweetAlert2 is available
                if (typeof Swal !== 'undefined') {
                    if (isActive) {
                        // Deactivate confirmation
                        Swal.fire({
                            title: 'Are you sure?',
                            html: `You are about to <strong>deactivate</strong> the account for <strong>${userName}</strong>.<br><br>This will prevent the user from logging in.`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: '<i class="ri-close-circle-line me-1"></i>Yes, deactivate it!',
                            cancelButtonText: '<i class="ri-close-line me-1"></i>Cancel',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    } else {
                        // Activate confirmation
                        Swal.fire({
                            title: 'Activate Account?',
                            html: `You are about to <strong>activate</strong> the account for <strong>${userName}</strong>.<br><br>This will allow the user to log in again.`,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: '<i class="ri-check-circle-line me-1"></i>Yes, activate it!',
                            cancelButtonText: '<i class="ri-close-line me-1"></i>Cancel',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    }
                } else {
                    // Fallback to native confirm
                    const action = isActive ? 'deactivate' : 'activate';
                    if (confirm(`Are you sure you want to ${action} the account for ${userName}?`)) {
                        form.submit();
                    }
                }
            }
        </script>

@endsection
