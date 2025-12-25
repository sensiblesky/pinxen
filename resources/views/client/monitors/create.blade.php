@extends('layouts.master')

@section('styles')
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Create {{ $category->name }} Monitor</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('monitors.index', ['category' => $category->slug]) }}">Monitoring</a></li>
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
                    <form action="{{ route('monitors.store') }}" method="POST" id="monitor-form">
                        @csrf
                        <input type="hidden" name="service_category_id" value="{{ $category->id }}">
                        <input type="hidden" name="type" value="{{ $category->slug === 'web' ? 'web' : 'server' }}">
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="monitoring_service_id" class="form-label">Monitoring Service <span class="text-danger">*</span></label>
                                <select class="form-select @error('monitoring_service_id') is-invalid @enderror" 
                                        id="monitoring_service_id" name="monitoring_service_id" required>
                                    <option value="">Select a monitoring service...</option>
                                    @foreach($monitoringServices as $categoryName => $services)
                                        <optgroup label="{{ ucfirst(str_replace('_', ' ', $categoryName)) }}">
                                            @foreach($services as $service)
                                                <option value="{{ $service->id }}" 
                                                        data-config='@json($service->config_schema)'
                                                        {{ old('monitoring_service_id') == $service->id ? 'selected' : '' }}>
                                                    {{ $service->name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                @error('monitoring_service_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted" id="service-description"></small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="name" class="form-label">Monitor Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" 
                                       placeholder="e.g., My Website, Production Server" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Service-Specific Configuration Fields (Dynamic) -->
                            <div id="service-config-fields" class="col-md-12">
                                <!-- Fields will be dynamically generated here based on selected service -->
                            </div>

                            <!-- Common Fields -->
                            <div class="col-md-4 mb-3">
                                <label for="check_interval" class="form-label">Check Interval (minutes) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('check_interval') is-invalid @enderror" 
                                       id="check_interval" name="check_interval" value="{{ old('check_interval', 5) }}" 
                                       min="1" max="1440" required>
                                @error('check_interval')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">How often to check (1-1440 minutes)</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="timeout" class="form-label">Timeout (seconds) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('timeout') is-invalid @enderror" 
                                       id="timeout" name="timeout" value="{{ old('timeout', 30) }}" 
                                       min="5" max="300" required>
                                @error('timeout')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Request timeout (5-300 seconds)</small>
                            </div>

                            <div class="col-md-4 mb-3" id="expected-status-code-field">
                                <label for="expected_status_code" class="form-label">Expected Status Code</label>
                                <input type="number" class="form-control @error('expected_status_code') is-invalid @enderror" 
                                       id="expected_status_code" name="expected_status_code" value="{{ old('expected_status_code', 200) }}" 
                                       min="100" max="599">
                                @error('expected_status_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">HTTP status code (e.g., 200, 301, 302)</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Communication Channels <span class="text-danger">*</span></label>
                                <p class="text-muted mb-2">Select at least one channel to receive alerts when the monitor goes down:</p>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input communication-channel" type="checkbox" 
                                                   id="channel_email" name="communication_channels[]" value="email"
                                                   {{ in_array('email', old('communication_channels', ['email'])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_email">
                                                <i class="ri-mail-line me-1"></i>Email
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input communication-channel" type="checkbox" 
                                                   id="channel_sms" name="communication_channels[]" value="sms"
                                                   {{ in_array('sms', old('communication_channels', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_sms">
                                                <i class="ri-message-3-line me-1"></i>SMS
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input communication-channel" type="checkbox" 
                                                   id="channel_whatsapp" name="communication_channels[]" value="whatsapp"
                                                   {{ in_array('whatsapp', old('communication_channels', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_whatsapp">
                                                <i class="ri-whatsapp-line me-1"></i>WhatsApp
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input communication-channel" type="checkbox" 
                                                   id="channel_telegram" name="communication_channels[]" value="telegram"
                                                   {{ in_array('telegram', old('communication_channels', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_telegram">
                                                <i class="ri-telegram-line me-1"></i>Telegram
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input communication-channel" type="checkbox" 
                                                   id="channel_discord" name="communication_channels[]" value="discord"
                                                   {{ in_array('discord', old('communication_channels', [])) ? 'checked' : '' }}>
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
                                <!-- Email Field -->
                                <div class="mb-3" id="email-field" style="display: none;">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           id="email" name="email" value="{{ old('email', auth()->user()->email) }}" 
                                           placeholder="your@email.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- SMS Field -->
                                <div class="mb-3" id="sms-field" style="display: none;">
                                    <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                           id="phone" name="phone" value="{{ old('phone', auth()->user()->phone) }}" 
                                           placeholder="+1234567890">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- WhatsApp Field -->
                                <div class="mb-3" id="whatsapp-field" style="display: none;">
                                    <label for="whatsapp_number" class="form-label">WhatsApp Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('whatsapp_number') is-invalid @enderror" 
                                           id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number') }}" 
                                           placeholder="+1234567890">
                                    @error('whatsapp_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Telegram Field -->
                                <div class="mb-3" id="telegram-field" style="display: none;">
                                    <label for="telegram_chat_id" class="form-label">Telegram Chat ID <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('telegram_chat_id') is-invalid @enderror" 
                                           id="telegram_chat_id" name="telegram_chat_id" value="{{ old('telegram_chat_id') }}" 
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
                                           id="discord_webhook" name="discord_webhook" value="{{ old('discord_webhook') }}" 
                                           placeholder="https://discord.com/api/webhooks/...">
                                    @error('discord_webhook')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-wave">
                                        <i class="ri-save-line me-1"></i>Create Monitor
                                    </button>
                                    <a href="{{ route('monitors.index', ['category' => $category->slug]) }}" class="btn btn-secondary btn-wave">
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
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
    <script>
        $(document).ready(function() {
            // Store all services data
            const servicesData = {};
            @foreach($monitoringServices as $categoryName => $services)
                @foreach($services as $service)
                    servicesData[{{ $service->id }}] = {
                        name: @json($service->name),
                        description: @json($service->description),
                        config_schema: @json($service->config_schema)
                    };
                @endforeach
            @endforeach

            // Handle service selection change
            $('#monitoring_service_id').on('change', function() {
                const serviceId = $(this).val();
                const serviceData = servicesData[serviceId];
                
                if (!serviceId || !serviceData) {
                    $('#service-config-fields').html('');
                    $('#service-description').text('');
                    $('#expected-status-code-field').show();
                    return;
                }

                // Update description
                $('#service-description').text(serviceData.description || '');

                // Generate service-specific fields
                let fieldsHtml = '';
                const configSchema = serviceData.config_schema || {};

                // Check if service needs URL field
                let needsUrl = false;
                let needsExpectedStatusCode = false;

                Object.keys(configSchema).forEach(function(key) {
                    const field = configSchema[key];
                    const fieldId = 'config_' + key;
                    const fieldName = key;
                    const fieldValue = '{{ old("' + key + '") }}' || (field.default || '');
                    const isRequired = field.required ? 'required' : '';
                    const requiredStar = field.required ? '<span class="text-danger">*</span>' : '';

                    if (key === 'url') {
                        needsUrl = true;
                    }
                    if (key === 'expected_status_code') {
                        needsExpectedStatusCode = true;
                    }

                    let inputHtml = '';
                    const fieldLabel = field.label || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

                    switch(field.type) {
                        case 'url':
                        case 'text':
                        case 'email':
                            inputHtml = `
                                <div class="col-md-12 mb-3">
                                    <label for="${fieldId}" class="form-label">${fieldLabel} ${requiredStar}</label>
                                    <input type="${field.type === 'email' ? 'email' : field.type === 'url' ? 'url' : 'text'}" 
                                           class="form-control" 
                                           id="${fieldId}" 
                                           name="${fieldName}" 
                                           value="${fieldValue}" 
                                           placeholder="${field.placeholder || ''}"
                                           ${isRequired}>
                                    ${field.placeholder ? `<small class="text-muted">${field.placeholder}</small>` : ''}
                                </div>
                            `;
                            break;
                        case 'number':
                            inputHtml = `
                                <div class="col-md-6 mb-3">
                                    <label for="${fieldId}" class="form-label">${fieldLabel} ${requiredStar}</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="${fieldId}" 
                                           name="${fieldName}" 
                                           value="${fieldValue}" 
                                           min="${field.min || ''}"
                                           max="${field.max || ''}"
                                           ${isRequired}>
                                    ${field.placeholder ? `<small class="text-muted">${field.placeholder}</small>` : ''}
                                </div>
                            `;
                            break;
                        case 'select':
                            const options = field.options || [];
                            let optionsHtml = '<option value="">Select...</option>';
                            options.forEach(function(opt) {
                                const optValue = typeof opt === 'object' ? opt.value : opt;
                                const optLabel = typeof opt === 'object' ? opt.label : opt;
                                optionsHtml += `<option value="${optValue}">${optLabel}</option>`;
                            });
                            inputHtml = `
                                <div class="col-md-6 mb-3">
                                    <label for="${fieldId}" class="form-label">${fieldLabel} ${requiredStar}</label>
                                    <select class="form-select" id="${fieldId}" name="${fieldName}" ${isRequired}>
                                        ${optionsHtml}
                                    </select>
                                </div>
                            `;
                            break;
                        case 'multiselect':
                            const multiOptions = field.options || [];
                            let multiOptionsHtml = '';
                            multiOptions.forEach(function(opt) {
                                const optValue = typeof opt === 'object' ? opt.value : opt;
                                const optLabel = typeof opt === 'object' ? opt.label : opt;
                                multiOptionsHtml += `
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               id="${fieldId}_${optValue}" 
                                               name="${fieldName}[]" 
                                               value="${optValue}">
                                        <label class="form-check-label" for="${fieldId}_${optValue}">
                                            ${optLabel}
                                        </label>
                                    </div>
                                `;
                            });
                            inputHtml = `
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">${fieldLabel} ${requiredStar}</label>
                                    <div class="row g-2">
                                        ${multiOptionsHtml}
                                    </div>
                                </div>
                            `;
                            break;
                        case 'boolean':
                            inputHtml = `
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" 
                                               id="${fieldId}" 
                                               name="${fieldName}" 
                                               value="1"
                                               ${fieldValue === '1' || fieldValue === 'true' || field.default === true ? 'checked' : ''}>
                                        <label class="form-check-label" for="${fieldId}">
                                            ${fieldLabel}
                                        </label>
                                    </div>
                                </div>
                            `;
                            break;
                        case 'textarea':
                            inputHtml = `
                                <div class="col-md-12 mb-3">
                                    <label for="${fieldId}" class="form-label">${fieldLabel} ${requiredStar}</label>
                                    <textarea class="form-control" 
                                              id="${fieldId}" 
                                              name="${fieldName}" 
                                              rows="4"
                                              ${isRequired}>${fieldValue}</textarea>
                                </div>
                            `;
                            break;
                    }
                    fieldsHtml += inputHtml;
                });

                $('#service-config-fields').html(fieldsHtml);

                // Show/hide expected status code field
                if (needsExpectedStatusCode) {
                    $('#expected-status-code-field').show();
                } else {
                    $('#expected-status-code-field').hide();
                }
            });

            // Trigger on page load if service is pre-selected
            if ($('#monitoring_service_id').val()) {
                $('#monitoring_service_id').trigger('change');
            }

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
