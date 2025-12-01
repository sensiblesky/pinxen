@extends('layouts.master')

@section('styles')



@endsection

@section('content')
	
                    <!-- Start::page-header -->
                    <div class="page-header-breadcrumb mb-3">
                        <div class="d-flex align-center justify-content-between flex-wrap">
                            <h1 class="page-title fw-medium fs-18 mb-0">Create New User</h1>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('panel.users.index') }}">Users</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Create</li>
                            </ol>
                        </div>
                    </div>
                    <!-- End::page-header -->

                    <!-- Start::row-1 -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">
                                        User Information
                                    </div>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('panel.users.store') }}" method="POST">
                                        @csrf
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                                @error('name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                                                @error('email')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                                                @error('password')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="phone" class="form-label">Phone Number</label>
                                                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                                                @error('phone')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                                <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                                    <option value="">Select Role</option>
                                                    <option value="1" {{ old('role') == 1 ? 'selected' : '' }}>Admin</option>
                                                    <option value="2" {{ old('role') == 2 ? 'selected' : '' }}>User</option>
                                                </select>
                                                @error('role')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="language_id" class="form-label">Language</label>
                                                <select class="form-select" id="language_id" name="language_id" data-trigger>
                                                    <option value="">Select Language</option>
                                                    @foreach($languages as $language)
                                                        <option value="{{ $language->id }}" {{ old('language_id') == $language->id ? 'selected' : '' }}>{{ $language->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="timezone_id" class="form-label">Timezone</label>
                                                <select class="form-select" id="timezone_id" name="timezone_id" data-trigger>
                                                    <option value="">Select Timezone</option>
                                                    @foreach($timezones as $timezone)
                                                        <option value="{{ $timezone->id }}" {{ old('timezone_id') == $timezone->id ? 'selected' : '' }}>{{ $timezone->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary btn-wave">
                                                <i class="ri-save-line me-1"></i>Create User
                                            </button>
                                            <a href="{{ route('panel.users.index') }}" class="btn btn-secondary btn-wave">
                                                <i class="ri-close-line me-1"></i>Cancel
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End::row-1 -->

@endsection

@section('scripts')

        <!-- Choices JS -->
        <script src="{{asset('build/assets/libs/choices.js/public/assets/scripts/choices.min.js')}}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const languageSelect = document.querySelector('#language_id');
                const timezoneSelect = document.querySelector('#timezone_id');
                
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
        </script>

@endsection

