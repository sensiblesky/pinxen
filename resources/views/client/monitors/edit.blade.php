@extends('layouts.master')

@section('styles')
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Edit Monitor</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('monitors.index', ['category' => $monitor->serviceCategory->slug]) }}">Monitoring</a></li>
                <li class="breadcrumb-item"><a href="{{ route('monitors.show', $monitor->uid) }}">{{ $monitor->name }}</a></li>
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
                    <form action="{{ route('monitors.update', $monitor->uid) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="name" class="form-label">Monitor Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $monitor->name) }}" 
                                       placeholder="e.g., My Website, Production Server" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if($monitor->type === 'web')
                            <div class="col-md-12 mb-3">
                                <label for="url" class="form-label">Website URL <span class="text-danger">*</span></label>
                                <input type="url" class="form-control @error('url') is-invalid @enderror" 
                                       id="url" name="url" value="{{ old('url', $monitor->url) }}" 
                                       placeholder="https://example.com" required>
                                @error('url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Enter the full URL including https:// or http://</small>
                            </div>
                            @endif

                            <div class="col-md-4 mb-3">
                                <label for="check_interval" class="form-label">Check Interval (minutes) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('check_interval') is-invalid @enderror" 
                                       id="check_interval" name="check_interval" value="{{ old('check_interval', $monitor->check_interval) }}" 
                                       min="1" max="1440" required>
                                @error('check_interval')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">How often to check (1-1440 minutes)</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="timeout" class="form-label">Timeout (seconds) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('timeout') is-invalid @enderror" 
                                       id="timeout" name="timeout" value="{{ old('timeout', $monitor->timeout) }}" 
                                       min="5" max="300" required>
                                @error('timeout')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Request timeout (5-300 seconds)</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="expected_status_code" class="form-label">Expected Status Code <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('expected_status_code') is-invalid @enderror" 
                                       id="expected_status_code" name="expected_status_code" value="{{ old('expected_status_code', $monitor->expected_status_code) }}" 
                                       min="100" max="599" required>
                                @error('expected_status_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">HTTP status code (e.g., 200, 301, 302)</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           {{ old('is_active', $monitor->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Active Monitor
                                    </label>
                                </div>
                                <small class="text-muted">When disabled, the monitor will not be checked</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Communication Channels <span class="text-danger">*</span></label>
                                <p class="text-muted mb-2">Select at least one channel to receive alerts when the monitor goes down:</p>
                                
                                @php
                                    $selectedChannels = $monitor->communicationPreferences->pluck('communication_channel')->toArray();
                                    $oldChannels = old('communication_channels', $selectedChannels);
                                @endphp
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input communication-channel" type="checkbox" 
                                                   id="channel_email" name="communication_channels[]" value="email"
                                                   {{ in_array('email', $oldChannels) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_email">
                                                <i class="ri-mail-line me-1"></i>Email
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input communication-channel" type="checkbox" 
                                                   id="channel_sms" name="communication_channels[]" value="sms"
                                                   {{ in_array('sms', $oldChannels) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_sms">
                                                <i class="ri-message-3-line me-1"></i>SMS
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input communication-channel" type="checkbox" 
                                                   id="channel_whatsapp" name="communication_channels[]" value="whatsapp"
                                                   {{ in_array('whatsapp', $oldChannels) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_whatsapp">
                                                <i class="ri-whatsapp-line me-1"></i>WhatsApp
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input communication-channel" type="checkbox" 
                                                   id="channel_telegram" name="communication_channels[]" value="telegram"
                                                   {{ in_array('telegram', $oldChannels) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_telegram">
                                                <i class="ri-telegram-line me-1"></i>Telegram
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input communication-channel" type="checkbox" 
                                                   id="channel_discord" name="communication_channels[]" value="discord"
                                                   {{ in_array('discord', $oldChannels) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_discord">
                                                <i class="ri-discord-line me-1"></i>Discord
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                @error('communication_channels')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Dynamic fields based on selected channels -->
                            <div id="channel-fields" class="col-md-12">
                                @php
                                    $emailPref = $monitor->communicationPreferences->where('communication_channel', 'email')->first();
                                    $smsPref = $monitor->communicationPreferences->where('communication_channel', 'sms')->first();
                                    $whatsappPref = $monitor->communicationPreferences->where('communication_channel', 'whatsapp')->first();
                                    $telegramPref = $monitor->communicationPreferences->where('communication_channel', 'telegram')->first();
                                    $discordPref = $monitor->communicationPreferences->where('communication_channel', 'discord')->first();
                                @endphp

                                <!-- Email Field -->
                                <div class="mb-3" id="email-field" style="display: none;">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           id="email" name="email" value="{{ old('email', $emailPref->channel_value ?? auth()->user()->email) }}" 
                                           placeholder="your@email.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- SMS Field -->
                                <div class="mb-3" id="sms-field" style="display: none;">
                                    <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                           id="phone" name="phone" value="{{ old('phone', $smsPref->channel_value ?? auth()->user()->phone) }}" 
                                           placeholder="+1234567890">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- WhatsApp Field -->
                                <div class="mb-3" id="whatsapp-field" style="display: none;">
                                    <label for="whatsapp_number" class="form-label">WhatsApp Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('whatsapp_number') is-invalid @enderror" 
                                           id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $whatsappPref->channel_value ?? '') }}" 
                                           placeholder="+1234567890">
                                    @error('whatsapp_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Telegram Field -->
                                <div class="mb-3" id="telegram-field" style="display: none;">
                                    <label for="telegram_chat_id" class="form-label">Telegram Chat ID <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('telegram_chat_id') is-invalid @enderror" 
                                           id="telegram_chat_id" name="telegram_chat_id" value="{{ old('telegram_chat_id', $telegramPref->channel_value ?? '') }}" 
                                           placeholder="123456789">
                                    @error('telegram_chat_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Get your chat ID from @userinfobot on Telegram</small>
                                </div>

                                <!-- Discord Field -->
                                <div class="mb-3" id="discord-field" style="display: none;">
                                    <label for="discord_webhook" class="form-label">Discord Webhook URL <span class="text-danger">*</span></label>
                                    <input type="url" class="form-control @error('discord_webhook') is-invalid @enderror" 
                                           id="discord_webhook" name="discord_webhook" value="{{ old('discord_webhook', $discordPref->channel_value ?? '') }}" 
                                           placeholder="https://discord.com/api/webhooks/...">
                                    @error('discord_webhook')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-wave">
                                        <i class="ri-save-line me-1"></i>Update Monitor
                                    </button>
                                    <a href="{{ route('monitors.show', $monitor->uid) }}" class="btn btn-secondary btn-wave">
                                        <i class="ri-close-line me-1"></i>Cancel
                                    </a>
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
    <script>
        $(document).ready(function() {
            // Show/hide channel fields based on checkbox selection
            $('.communication-channel').on('change', function() {
                const channel = $(this).val();
                const fieldId = channel + '-field';
                
                if ($(this).is(':checked')) {
                    $('#' + fieldId).show();
                    $('#' + fieldId + ' input').prop('required', true);
                } else {
                    $('#' + fieldId).hide();
                    $('#' + fieldId + ' input').prop('required', false).val('');
                }
            });

            // Trigger on page load to show fields for pre-selected channels
            $('.communication-channel:checked').each(function() {
                $(this).trigger('change');
            });
        });
    </script>
@endsection


