@extends('layouts.master')

@section('title', 'Edit DNS Monitor - PingXeno')

@section('styles')
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Edit DNS Monitor</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dns-monitors.index') }}">DNS Monitoring</a></li>
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

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Monitor Information</div>
                </div>
                <div class="card-body">
                    <form action="{{ route('dns-monitors.update', $monitor->uid) }}" method="POST" id="monitor-form">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="name" class="form-label">Monitor Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $monitor->name) }}" 
                                       placeholder="e.g., My DNS Monitor, Production DNS" required>
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
                                <label class="form-label">DNS Record Types to Monitor <span class="text-danger">*</span></label>
                                <div class="card border">
                                    <div class="card-body">
                                        <p class="text-muted mb-3">Select which DNS record types you want to monitor:</p>
                                        @php
                                            $recordTypes = ['A', 'AAAA', 'CNAME', 'MX', 'NS', 'TXT', 'SOA'];
                                            $oldTypes = old('record_types', $monitor->record_types ?? []);
                                        @endphp
                                        @foreach($recordTypes as $type)
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="record_type_{{ $type }}" name="record_types[]" value="{{ $type }}" 
                                                       {{ in_array($type, $oldTypes) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="record_type_{{ $type }}">
                                                    <code>{{ $type }}</code> - 
                                                    @if($type === 'A')
                                                        IPv4 Address
                                                    @elseif($type === 'AAAA')
                                                        IPv6 Address
                                                    @elseif($type === 'CNAME')
                                                        Canonical Name
                                                    @elseif($type === 'MX')
                                                        Mail Exchange
                                                    @elseif($type === 'NS')
                                                        Name Server
                                                    @elseif($type === 'TXT')
                                                        Text Record
                                                    @elseif($type === 'SOA')
                                                        Start of Authority
                                                    @endif
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                @error('record_types')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">At least one record type must be selected</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="check_interval" class="form-label">Check Interval (minutes) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('check_interval') is-invalid @enderror" 
                                       id="check_interval" name="check_interval" value="{{ old('check_interval', $monitor->check_interval) }}" 
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
                                                   id="alert_on_change" name="alert_on_change" value="1" 
                                                   {{ old('alert_on_change', $monitor->alert_on_change) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="alert_on_change">
                                                <strong>Alert when DNS records change</strong>
                                            </label>
                                            <small class="d-block text-muted">Receive an alert when any monitored DNS records change</small>
                                        </div>

                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="alert_on_missing" name="alert_on_missing" value="1" 
                                                   {{ old('alert_on_missing', $monitor->alert_on_missing) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="alert_on_missing">
                                                <strong>Alert when records are missing</strong>
                                            </label>
                                            <small class="d-block text-muted">Receive an alert when expected DNS records are missing</small>
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
                            <a href="{{ route('dns-monitors.index') }}" class="btn btn-light btn-wave">
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
            const recordTypes = document.querySelectorAll('input[name="record_types[]"]:checked');
            const channels = document.querySelectorAll('input[name="communication_channels[]"]:checked');
            
            if (recordTypes.length === 0) {
                e.preventDefault();
                alert('Please select at least one DNS record type to monitor.');
                return false;
            }
            
            if (channels.length === 0) {
                e.preventDefault();
                alert('Please select at least one communication channel.');
                return false;
            }
        });
    </script>
@endsection





