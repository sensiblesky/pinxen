@extends('layouts.master')

@section('styles')
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Edit Domain Monitor</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('panel.domain-monitors.index') }}">Domain Monitoring</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
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

    <!-- Info Message about TLD Support -->
    <div class="alert alert-info alert-dismissible fade show d-flex align-items-center" role="alert">
        <div class="me-3">
            <i class="ri-information-line fs-20"></i>
        </div>
        <div class="flex-fill">
            <strong>Domain TLD Support Notice:</strong> Some domain TLDs (Top-Level Domains) are currently not supported, including specific country domains like <code>.go.tz</code>, <code>.ac.tz</code>, and other regional TLDs. We are actively working to expand our territories and add support for more TLDs. If you encounter issues with a specific domain, please contact support.
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Monitor Information</div>
                </div>
                <div class="card-body">
                    <form action="{{ route('panel.domain-monitors.update', $monitor->uid) }}" method="POST" id="monitor-form">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Owner (User)</label>
                                <div class="form-control bg-light" readonly>
                                    <strong>{{ $monitor->user->name }}</strong> ({{ $monitor->user->email }})
                                </div>
                                <small class="text-muted">This monitor is locked to the original owner and cannot be changed</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="name" class="form-label">Monitor Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $monitor->name) }}" 
                                       placeholder="e.g., My Domain, Production Domain" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="domain" class="form-label">Domain Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('domain') is-invalid @enderror" 
                                       id="domain" name="domain" value="{{ old('domain', $monitor->domain) }}" 
                                       placeholder="example.com" required>
                                @error('domain')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Enter the domain name without http:// or https:// (e.g., example.com)</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Alert Settings</label>
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="alert_30_days" name="alert_30_days" value="1" 
                                                   {{ old('alert_30_days', $monitor->alert_30_days) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="alert_30_days">
                                                <strong>Alert 30 days before expiration</strong>
                                            </label>
                                            <small class="d-block text-muted">Receive an alert when the domain is 30 days away from expiring</small>
                                        </div>

                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="alert_5_days" name="alert_5_days" value="1" 
                                                   {{ old('alert_5_days', $monitor->alert_5_days) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="alert_5_days">
                                                <strong>Alert 5 days before expiration</strong>
                                            </label>
                                            <small class="d-block text-muted">Receive an alert when the domain is 5 days away from expiring</small>
                                        </div>

                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="alert_daily_under_30" name="alert_daily_under_30" value="1" 
                                                   {{ old('alert_daily_under_30', $monitor->alert_daily_under_30) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="alert_daily_under_30">
                                                <strong>Daily alerts when 30 days or less remain</strong>
                                            </label>
                                            <small class="d-block text-muted">Receive daily alerts when the domain has 30 days or less until expiration</small>
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
                                                   {{ in_array('email', old('communication_channels', $communicationPreferences)) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_email">
                                                <i class="ri-mail-line me-1"></i>Email
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="channel_sms" name="communication_channels[]" value="sms" 
                                                   {{ in_array('sms', old('communication_channels', $communicationPreferences)) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_sms">
                                                <i class="ri-message-3-line me-1"></i>SMS
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="channel_whatsapp" name="communication_channels[]" value="whatsapp" 
                                                   {{ in_array('whatsapp', old('communication_channels', $communicationPreferences)) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_whatsapp">
                                                <i class="ri-whatsapp-line me-1"></i>WhatsApp
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="channel_telegram" name="communication_channels[]" value="telegram" 
                                                   {{ in_array('telegram', old('communication_channels', $communicationPreferences)) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_telegram">
                                                <i class="ri-telegram-line me-1"></i>Telegram
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="channel_discord" name="communication_channels[]" value="discord" 
                                                   {{ in_array('discord', old('communication_channels', $communicationPreferences)) ? 'checked' : '' }}>
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

                            <div class="col-md-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" 
                                           id="is_active" name="is_active" value="1" 
                                           {{ old('is_active', $monitor->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        <strong>Active</strong>
                                    </label>
                                    <small class="d-block text-muted">Enable or disable this monitor</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('panel.domain-monitors.index') }}" class="btn btn-light btn-wave">
                                <i class="ri-close-line me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-wave">
                                <i class="ri-save-line me-1"></i>Update Monitor
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

