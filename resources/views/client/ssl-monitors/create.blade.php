@extends('layouts.master')

@section('styles')
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Create SSL Monitor</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('ssl-monitors.index') }}">SSL Monitoring</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create</li>
            </ol>
        </div>
    </div>
    <!-- End::page-header -->

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> Please fix the following errors:
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
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
                    <div class="card-title">Monitor Information</div>
                </div>
                <div class="card-body">
                    <form action="{{ route('ssl-monitors.store') }}" method="POST" id="monitor-form">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="name" class="form-label">Monitor Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" 
                                       placeholder="e.g., My SSL Certificate, Production SSL" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="domain" class="form-label">Domain Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('domain') is-invalid @enderror" 
                                       id="domain" name="domain" value="{{ old('domain') }}" 
                                       placeholder="example.com" required>
                                @error('domain')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Enter the domain name without http:// or https:// (e.g., example.com)</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="check_interval" class="form-label">Check Interval (minutes) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('check_interval') is-invalid @enderror" 
                                       id="check_interval" name="check_interval" value="{{ old('check_interval', 60) }}" 
                                       min="1" max="1440" required>
                                @error('check_interval')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">How often to check (1-1440 minutes)</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Alert Settings</label>
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="alert_expiring_soon" name="alert_expiring_soon" value="1" 
                                                   {{ old('alert_expiring_soon', true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="alert_expiring_soon">
                                                <strong>Alert when expiring soon (30 days or less)</strong>
                                            </label>
                                            <small class="d-block text-muted">Receive an alert when the SSL certificate has 30 days or less until expiration</small>
                                        </div>

                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="alert_expired" name="alert_expired" value="1" 
                                                   {{ old('alert_expired', true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="alert_expired">
                                                <strong>Alert when certificate expires</strong>
                                            </label>
                                            <small class="d-block text-muted">Receive an alert when the SSL certificate expires</small>
                                        </div>

                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="alert_invalid" name="alert_invalid" value="1" 
                                                   {{ old('alert_invalid', true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="alert_invalid">
                                                <strong>Alert when certificate is invalid</strong>
                                            </label>
                                            <small class="d-block text-muted">Receive an alert when the SSL certificate is invalid</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Communication Channels <span class="text-danger">*</span></label>
                                <div class="card border">
                                    <div class="card-body">
                                        <p class="text-muted mb-3">Select how you want to receive alerts:</p>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="channel_email" name="communication_channels[]" value="email" 
                                                   {{ in_array('email', old('communication_channels', ['email'])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_email">
                                                <i class="ri-mail-line me-1"></i>Email
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="channel_sms" name="communication_channels[]" value="sms" 
                                                   {{ in_array('sms', old('communication_channels', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_sms">
                                                <i class="ri-message-3-line me-1"></i>SMS
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="channel_whatsapp" name="communication_channels[]" value="whatsapp" 
                                                   {{ in_array('whatsapp', old('communication_channels', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_whatsapp">
                                                <i class="ri-whatsapp-line me-1"></i>WhatsApp
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="channel_telegram" name="communication_channels[]" value="telegram" 
                                                   {{ in_array('telegram', old('communication_channels', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_telegram">
                                                <i class="ri-telegram-line me-1"></i>Telegram
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="channel_discord" name="communication_channels[]" value="discord" 
                                                   {{ in_array('discord', old('communication_channels', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_discord">
                                                <i class="ri-discord-line me-1"></i>Discord
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                @error('communication_channels')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">At least one communication channel must be selected</small>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('ssl-monitors.index') }}" class="btn btn-light btn-wave">
                                <i class="ri-close-line me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-wave">
                                <i class="ri-save-line me-1"></i>Create Monitor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->
@endsection

@section('scripts')
    <script>
        document.getElementById('monitor-form').addEventListener('submit', function(e) {
            const channels = document.querySelectorAll('input[name="communication_channels[]"]:checked');
            if (channels.length === 0) {
                e.preventDefault();
                alert('Please select at least one communication channel.');
                return false;
            }
        });
    </script>
@endsection




