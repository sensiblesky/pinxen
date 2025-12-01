@extends('layouts.master')

@section('styles')
    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.css')}}">
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">System Configuration</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                <li class="breadcrumb-item active" aria-current="page">System Configuration</li>
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
    <form action="{{ route('panel.system-configuration.update') }}" method="POST" enctype="multipart/form-data" id="system-config-form">
        @csrf
        @method('PUT')
        
        <div class="row">
            <!-- General Settings -->
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">General Settings</div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row gy-3">
                            <div class="col-xl-6">
                                <label for="app_name" class="form-label">APP Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('app_name') is-invalid @enderror" id="app_name" name="app_name" value="{{ old('app_name', $settings['app_name'] ?? '') }}" required>
                                @error('app_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-xl-6">
                                <label for="app_author" class="form-label">APP Author</label>
                                <input type="text" class="form-control @error('app_author') is-invalid @enderror" id="app_author" name="app_author" value="{{ old('app_author', $settings['app_author'] ?? '') }}">
                                @error('app_author')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-xl-6">
                                <label for="app_phone" class="form-label">APP Phone</label>
                                <input type="text" class="form-control @error('app_phone') is-invalid @enderror" id="app_phone" name="app_phone" value="{{ old('app_phone', $settings['app_phone'] ?? '') }}">
                                @error('app_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-xl-6">
                                <label for="app_email" class="form-label">APP Email</label>
                                <input type="email" class="form-control @error('app_email') is-invalid @enderror" id="app_email" name="app_email" value="{{ old('app_email', $settings['app_email'] ?? '') }}">
                                @error('app_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-xl-12">
                                <label for="app_address" class="form-label">APP Address</label>
                                <textarea class="form-control @error('app_address') is-invalid @enderror" id="app_address" name="app_address" rows="3">{{ old('app_address', $settings['app_address'] ?? '') }}</textarea>
                                @error('app_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-xl-6">
                                <label for="currency" class="form-label">Currency</label>
                                <input type="text" class="form-control @error('currency') is-invalid @enderror" id="currency" name="currency" value="{{ old('currency', $settings['currency'] ?? 'USD') }}" placeholder="USD, EUR, etc.">
                                @error('currency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-xl-6">
                                <label for="timezone_id" class="form-label">Timezone</label>
                                <select class="form-control @error('timezone_id') is-invalid @enderror" id="timezone_id" name="timezone_id" data-trigger>
                                    <option value="">Select Timezone</option>
                                    @foreach($timezones as $timezone)
                                        <option value="{{ $timezone->id }}" {{ old('timezone_id', $settings['timezone_id'] ?? '') == $timezone->id ? 'selected' : '' }}>{{ $timezone->name }}</option>
                                    @endforeach
                                </select>
                                @error('timezone_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-xl-12">
                                <label for="app_footer_text" class="form-label">APP Footer Text</label>
                                <textarea class="form-control @error('app_footer_text') is-invalid @enderror" id="app_footer_text" name="app_footer_text" rows="2">{{ old('app_footer_text', $settings['app_footer_text'] ?? '') }}</textarea>
                                @error('app_footer_text')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logo & Favicon Settings -->
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">Logo & Favicon Settings</div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row gy-3">
                            <div class="col-xl-6">
                                <label for="desktop_logo" class="form-label">Desktop Logo (150x35)</label>
                                <input type="file" class="form-control @error('desktop_logo') is-invalid @enderror" id="desktop_logo" name="desktop_logo" accept="image/jpeg,image/png,image/jpg,image/gif" onchange="previewImage(this, 'desktop-logo-preview')">
                                @error('desktop_logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="mt-2">
                                    <img src="{{ asset('build/assets/images/brand-logos/desktop-logo.png') }}" alt="Desktop Logo" id="desktop-logo-preview" class="img-thumbnail" style="max-width: 200px; max-height: 50px;" onerror="this.style.display='none'">
                                </div>
                                <span class="d-block fs-12 text-muted mt-1">Exact size required: 150x35 pixels. Will replace desktop-logo.png</span>
                            </div>
                            <div class="col-xl-6">
                                <label for="desktop_dark_logo" class="form-label">Desktop Dark Logo (150x35)</label>
                                <input type="file" class="form-control @error('desktop_dark_logo') is-invalid @enderror" id="desktop_dark_logo" name="desktop_dark_logo" accept="image/jpeg,image/png,image/jpg,image/gif" onchange="previewImage(this, 'desktop-dark-logo-preview')">
                                @error('desktop_dark_logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="mt-2">
                                    <img src="{{ asset('build/assets/images/brand-logos/desktop-dark.png') }}" alt="Desktop Dark Logo" id="desktop-dark-logo-preview" class="img-thumbnail" style="max-width: 200px; max-height: 50px;" onerror="this.style.display='none'">
                                </div>
                                <span class="d-block fs-12 text-muted mt-1">Exact size required: 150x35 pixels. Will replace desktop-dark.png</span>
                            </div>
                            <div class="col-xl-6">
                                <label for="toggle_logo" class="form-label">Toggle Logo (36x41)</label>
                                <input type="file" class="form-control @error('toggle_logo') is-invalid @enderror" id="toggle_logo" name="toggle_logo" accept="image/jpeg,image/png,image/jpg,image/gif" onchange="previewImage(this, 'toggle-logo-preview')">
                                @error('toggle_logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="mt-2">
                                    <img src="{{ asset('build/assets/images/brand-logos/toggle-logo.png') }}" alt="Toggle Logo" id="toggle-logo-preview" class="img-thumbnail" style="max-width: 50px; max-height: 60px;" onerror="this.style.display='none'">
                                </div>
                                <span class="d-block fs-12 text-muted mt-1">Exact size required: 36x41 pixels. Will replace toggle-logo.png</span>
                            </div>
                            <div class="col-xl-6">
                                <label for="toggle_dark_logo" class="form-label">Toggle Dark Logo (36x41)</label>
                                <input type="file" class="form-control @error('toggle_dark_logo') is-invalid @enderror" id="toggle_dark_logo" name="toggle_dark_logo" accept="image/jpeg,image/png,image/jpg,image/gif" onchange="previewImage(this, 'toggle-dark-logo-preview')">
                                @error('toggle_dark_logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="mt-2">
                                    <img src="{{ asset('build/assets/images/brand-logos/toggle-dark.png') }}" alt="Toggle Dark Logo" id="toggle-dark-logo-preview" class="img-thumbnail" style="max-width: 50px; max-height: 60px;" onerror="this.style.display='none'">
                                </div>
                                <span class="d-block fs-12 text-muted mt-1">Exact size required: 36x41 pixels. Will replace toggle-dark.png</span>
                            </div>
                            <div class="col-xl-6">
                                <label for="favicon" class="form-label">Favicon (32x32 to 50x50)</label>
                                <input type="file" class="form-control @error('favicon') is-invalid @enderror" id="favicon" name="favicon" accept=".ico,image/x-icon" onchange="previewImage(this, 'favicon-preview')">
                                @error('favicon')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="mt-2">
                                    <img src="{{ asset('build/assets/images/brand-logos/favicon.ico') }}" alt="Favicon" id="favicon-preview" class="img-thumbnail" style="max-width: 50px; max-height: 50px;" onerror="this.style.display='none';">
                                </div>
                                <span class="d-block fs-12 text-muted mt-1">Size required: 32x32 to 50x50 pixels. .ico format only. Will replace favicon.ico</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security & System Settings -->
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">Security & System Settings</div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row gy-3">
                            <div class="col-xl-6">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <label class="form-label mb-1">Force HTTPS</label>
                                        <p class="fs-12 text-muted mb-0">Redirect all HTTP requests to HTTPS</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="force_https" name="force_https" value="1" {{ old('force_https', $settings['force_https'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="force_https"></label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <label class="form-label mb-1">Maintenance Mode</label>
                                        <p class="fs-12 text-muted mb-0">Enable maintenance mode to restrict access</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="maintenance_mode" name="maintenance_mode" value="1" {{ old('maintenance_mode', $settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="maintenance_mode"></label>
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
    <!-- Choices JS -->
    <script src="{{asset('build/assets/libs/choices.js/public/assets/scripts/choices.min.js')}}"></script>
    
    <!-- Sweetalerts JS -->
    <script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>
    
    <script>
        // Initialize Choices.js for timezone select
        document.addEventListener('DOMContentLoaded', function() {
            const timezoneSelect = document.querySelector('#timezone_id');
            
            if (timezoneSelect) {
                new Choices(timezoneSelect, {
                    searchEnabled: true,
                    placeholder: true,
                    placeholderValue: 'Select Timezone',
                });
            }
        });

        // Image preview functionality
        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validate file size
                const maxSize = input.id === 'favicon' ? 1024 * 1024 : 5 * 1024 * 1024; // 1MB for favicon, 5MB for logos
                if (file.size > maxSize) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'File Too Large',
                            text: `File size must be less than ${maxSize / (1024 * 1024)}MB`
                        });
                    } else {
                        alert(`File size must be less than ${maxSize / (1024 * 1024)}MB`);
                    }
                    input.value = '';
                    return;
                }
                
                // Validate file type - special handling for favicon
                if (input.id === 'favicon') {
                    // For favicon, only allow .ico files
                    const validExtensions = ['ico'];
                    const fileExtension = file.name.split('.').pop().toLowerCase();
                    if (!validExtensions.includes(fileExtension)) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Invalid File Type',
                                text: 'Favicon must be a .ico file'
                            });
                        } else {
                            alert('Favicon must be a .ico file');
                        }
                        input.value = '';
                        return;
                    }
                } else {
                    // For other images, allow standard image types
                    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                    if (!validTypes.includes(file.type)) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Invalid File Type',
                                text: 'Please select a valid image file (JPEG, PNG, or GIF)'
                            });
                        } else {
                            alert('Please select a valid image file');
                        }
                        input.value = '';
                        return;
                    }
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById(previewId);
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }
    </script>
@endsection

