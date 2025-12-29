@extends('layouts.master')

@section('styles')
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Create Uptime Monitor</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('panel.uptime-monitors.index') }}">Uptime Monitoring</a></li>
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
                    <form action="{{ route('panel.uptime-monitors.store') }}" method="POST" id="monitor-form">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="name" class="form-label">Monitor Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" 
                                       placeholder="e.g., My Website, Production Site" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="url" class="form-label">Website URL <span class="text-danger">*</span></label>
                                <input type="url" class="form-control @error('url') is-invalid @enderror" 
                                       id="url" name="url" value="{{ old('url') }}" 
                                       placeholder="https://example.com" required>
                                @error('url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Enter the full URL including http:// or https://</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="check_interval" class="form-label">Check Interval <span class="text-danger">*</span></label>
                                <select class="form-select @error('check_interval') is-invalid @enderror" 
                                        id="check_interval" name="check_interval" required>
                                    <option value="1" {{ old('check_interval', 5) == 1 ? 'selected' : '' }}>1 minute</option>
                                    <option value="3" {{ old('check_interval', 5) == 3 ? 'selected' : '' }}>3 minutes</option>
                                    <option value="5" {{ old('check_interval', 5) == 5 ? 'selected' : '' }}>5 minutes</option>
                                    <option value="10" {{ old('check_interval', 5) == 10 ? 'selected' : '' }}>10 minutes</option>
                                    <option value="30" {{ old('check_interval', 5) == 30 ? 'selected' : '' }}>30 minutes</option>
                                    <option value="60" {{ old('check_interval', 5) == 60 ? 'selected' : '' }}>1 hour</option>
                                </select>
                                @error('check_interval')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">How often to check the website</small>
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

                            <div class="col-md-4 mb-3">
                                <label for="expected_status_code" class="form-label">Expected Status Code <span class="text-danger">*</span></label>
                                <select class="form-select @error('expected_status_code') is-invalid @enderror" 
                                        id="expected_status_code" name="expected_status_code" required>
                                    <optgroup label="Success (2xx)">
                                        <option value="200" {{ old('expected_status_code', 200) == 200 ? 'selected' : '' }}>200 - OK</option>
                                        <option value="201" {{ old('expected_status_code', 200) == 201 ? 'selected' : '' }}>201 - Created</option>
                                        <option value="202" {{ old('expected_status_code', 200) == 202 ? 'selected' : '' }}>202 - Accepted</option>
                                        <option value="204" {{ old('expected_status_code', 200) == 204 ? 'selected' : '' }}>204 - No Content</option>
                                    </optgroup>
                                    <optgroup label="Redirection (3xx)">
                                        <option value="301" {{ old('expected_status_code', 200) == 301 ? 'selected' : '' }}>301 - Moved Permanently</option>
                                        <option value="302" {{ old('expected_status_code', 200) == 302 ? 'selected' : '' }}>302 - Found</option>
                                        <option value="303" {{ old('expected_status_code', 200) == 303 ? 'selected' : '' }}>303 - See Other</option>
                                        <option value="307" {{ old('expected_status_code', 200) == 307 ? 'selected' : '' }}>307 - Temporary Redirect</option>
                                        <option value="308" {{ old('expected_status_code', 200) == 308 ? 'selected' : '' }}>308 - Permanent Redirect</option>
                                    </optgroup>
                                    <optgroup label="Client Error (4xx)">
                                        <option value="400" {{ old('expected_status_code', 200) == 400 ? 'selected' : '' }}>400 - Bad Request</option>
                                        <option value="401" {{ old('expected_status_code', 200) == 401 ? 'selected' : '' }}>401 - Unauthorized</option>
                                        <option value="403" {{ old('expected_status_code', 200) == 403 ? 'selected' : '' }}>403 - Forbidden</option>
                                        <option value="404" {{ old('expected_status_code', 200) == 404 ? 'selected' : '' }}>404 - Not Found</option>
                                        <option value="405" {{ old('expected_status_code', 200) == 405 ? 'selected' : '' }}>405 - Method Not Allowed</option>
                                        <option value="408" {{ old('expected_status_code', 200) == 408 ? 'selected' : '' }}>408 - Request Timeout</option>
                                        <option value="429" {{ old('expected_status_code', 200) == 429 ? 'selected' : '' }}>429 - Too Many Requests</option>
                                    </optgroup>
                                    <optgroup label="Server Error (5xx)">
                                        <option value="500" {{ old('expected_status_code', 200) == 500 ? 'selected' : '' }}>500 - Internal Server Error</option>
                                        <option value="502" {{ old('expected_status_code', 200) == 502 ? 'selected' : '' }}>502 - Bad Gateway</option>
                                        <option value="503" {{ old('expected_status_code', 200) == 503 ? 'selected' : '' }}>503 - Service Unavailable</option>
                                        <option value="504" {{ old('expected_status_code', 200) == 504 ? 'selected' : '' }}>504 - Gateway Timeout</option>
                                    </optgroup>
                                    <option value="custom" {{ old('expected_status_code') == 'custom' || (!in_array(old('expected_status_code', 200), [200, 201, 202, 204, 301, 302, 303, 307, 308, 400, 401, 403, 404, 405, 408, 429, 500, 502, 503, 504]) && old('expected_status_code')) ? 'selected' : '' }}>Custom Status Code</option>
                                </select>
                                <input type="number" 
                                       class="form-control mt-2 @error('expected_status_code_custom') is-invalid @enderror" 
                                       id="expected_status_code_custom" 
                                       name="expected_status_code_custom" 
                                       value="{{ old('expected_status_code_custom', (!in_array(old('expected_status_code', 200), [200, 201, 202, 204, 301, 302, 303, 307, 308, 400, 401, 403, 404, 405, 408, 429, 500, 502, 503, 504]) && old('expected_status_code')) ? old('expected_status_code') : '') }}" 
                                       placeholder="Enter custom status code (e.g., 418, 451)"
                                       min="100" 
                                       max="599"
                                       style="display: none;">
                                @error('expected_status_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @error('expected_status_code_custom')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Expected HTTP response status code</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="keyword_present" class="form-label">Keyword Must Be Present (Optional)</label>
                                <input type="text" class="form-control @error('keyword_present') is-invalid @enderror" 
                                       id="keyword_present" name="keyword_present" value="{{ old('keyword_present') }}" 
                                       placeholder="e.g., Welcome, Online">
                                @error('keyword_present')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Monitor will fail if this keyword is not found in the response</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="keyword_absent" class="form-label">Keyword Must Be Absent (Optional)</label>
                                <input type="text" class="form-control @error('keyword_absent') is-invalid @enderror" 
                                       id="keyword_absent" name="keyword_absent" value="{{ old('keyword_absent') }}" 
                                       placeholder="e.g., Error, Maintenance">
                                @error('keyword_absent')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Monitor will fail if this keyword is found in the response</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" 
                                           id="check_ssl" name="check_ssl" value="1" 
                                           {{ old('check_ssl', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="check_ssl">
                                        Enable SSL Verification
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <i class="ri-information-line me-1"></i>
                                    When enabled, the monitor will verify SSL certificates. 
                                    <strong>Disable this option</strong> if you're monitoring sites with:
                                    <ul class="mb-0 mt-1 ps-3">
                                        <li>Self-signed certificates</li>
                                        <li>Invalid or expired certificates</li>
                                        <li>Incomplete certificate chains</li>
                                        <li>Internal/development servers</li>
                                    </ul>
                                    Disabling SSL verification will allow the monitor to check if the server is up/down regardless of SSL certificate issues.
                                </small>
                            </div>
                        </div>

                        <!-- SSL Monitor Addon -->
                        <div class="card custom-card mt-3 border-primary">
                            <div class="card-header bg-primary-transparent">
                                <div class="card-title">
                                    <i class="ri-shield-check-line me-2"></i>SSL Monitor (Optional Addon)
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" 
                                           id="create_ssl_monitor" name="create_ssl_monitor" value="1" 
                                           {{ old('create_ssl_monitor') ? 'checked' : '' }}
                                           onclick="document.getElementById('ssl_monitor_options').style.display = this.checked ? 'block' : 'none';">
                                    <label class="form-check-label" for="create_ssl_monitor" 
                                           onclick="setTimeout(function(){ var cb = document.getElementById('create_ssl_monitor'); var opt = document.getElementById('ssl_monitor_options'); if(cb && opt) opt.style.display = cb.checked ? 'block' : 'none'; }, 10);">
                                        <strong>Also create SSL monitor for this domain</strong>
                                    </label>
                                </div>
                                <small class="text-muted d-block mb-3">
                                    <i class="ri-information-line me-1"></i>
                                    Enable this to automatically create an SSL certificate monitor for the domain. 
                                    The SSL monitor will track certificate expiration and validity, and you can manage it separately in the SSL Monitors section.
                                </small>

                                <div id="ssl_monitor_options" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="ssl_check_interval" class="form-label">SSL Check Interval (minutes)</label>
                                            <input type="number" class="form-control @error('ssl_check_interval') is-invalid @enderror" 
                                                   id="ssl_check_interval" name="ssl_check_interval" 
                                                   value="{{ old('ssl_check_interval', 60) }}" 
                                                   min="1" max="1440">
                                            @error('ssl_check_interval')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">How often to check SSL certificate (default: 60 minutes)</small>
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">SSL Alert Settings</label>
                                            <div class="card border">
                                                <div class="card-body">
                                                    <div class="form-check form-switch mb-3">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="ssl_alert_expiring_soon" name="ssl_alert_expiring_soon" value="1" 
                                                               {{ old('ssl_alert_expiring_soon', true) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="ssl_alert_expiring_soon">
                                                            <strong>Alert when expiring soon (30 days or less)</strong>
                                                        </label>
                                                    </div>

                                                    <div class="form-check form-switch mb-3">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="ssl_alert_expired" name="ssl_alert_expired" value="1" 
                                                               {{ old('ssl_alert_expired', true) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="ssl_alert_expired">
                                                            <strong>Alert when certificate expires</strong>
                                                        </label>
                                                    </div>

                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="ssl_alert_invalid" name="ssl_alert_invalid" value="1" 
                                                               {{ old('ssl_alert_invalid', true) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="ssl_alert_invalid">
                                                            <strong>Alert when certificate is invalid</strong>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">SSL Communication Channels</label>
                                            <div class="card border">
                                                <div class="card-body">
                                                    <p class="text-muted mb-3">Select how you want to receive SSL alerts:</p>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="ssl_channel_email" name="ssl_communication_channels[]" value="email" 
                                                               {{ in_array('email', old('ssl_communication_channels', ['email'])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="ssl_channel_email">
                                                            <i class="ri-mail-line me-1"></i>Email
                                                        </label>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="ssl_channel_sms" name="ssl_communication_channels[]" value="sms" 
                                                               {{ in_array('sms', old('ssl_communication_channels', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="ssl_channel_sms">
                                                            <i class="ri-message-3-line me-1"></i>SMS
                                                        </label>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="ssl_channel_whatsapp" name="ssl_communication_channels[]" value="whatsapp" 
                                                               {{ in_array('whatsapp', old('ssl_communication_channels', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="ssl_channel_whatsapp">
                                                            <i class="ri-whatsapp-line me-1"></i>WhatsApp
                                                        </label>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="ssl_channel_telegram" name="ssl_communication_channels[]" value="telegram" 
                                                               {{ in_array('telegram', old('ssl_communication_channels', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="ssl_channel_telegram">
                                                            <i class="ri-telegram-line me-1"></i>Telegram
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="ssl_channel_discord" name="ssl_communication_channels[]" value="discord" 
                                                               {{ in_array('discord', old('ssl_communication_channels', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="ssl_channel_discord">
                                                            <i class="ri-discord-line me-1"></i>Discord
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            @error('ssl_communication_channels')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Domain Monitor Addon -->
                        <div class="card custom-card mt-3 border-success">
                            <div class="card-header bg-success-transparent">
                                <div class="card-title">
                                    <i class="ri-global-line me-2"></i>Domain Monitor (Optional Addon)
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" 
                                           id="create_domain_monitor" name="create_domain_monitor" value="1" 
                                           {{ old('create_domain_monitor') ? 'checked' : '' }}
                                           onclick="document.getElementById('domain_monitor_options').style.display = this.checked ? 'block' : 'none';">
                                    <label class="form-check-label" for="create_domain_monitor"
                                           onclick="setTimeout(function(){ var cb = document.getElementById('create_domain_monitor'); var opt = document.getElementById('domain_monitor_options'); if(cb && opt) opt.style.display = cb.checked ? 'block' : 'none'; }, 10);">
                                        <strong>Also create domain expiration monitor for this domain</strong>
                                    </label>
                                </div>
                                <small class="text-muted d-block mb-3">
                                    <i class="ri-information-line me-1"></i>
                                    Enable this to automatically create a domain expiration monitor for the domain. 
                                    The domain monitor will track expiration dates and send alerts before the domain expires. 
                                    You can manage it separately in the Domain Monitors section.
                                </small>

                                <div id="domain_monitor_options" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Domain Alert Settings</label>
                                            <div class="card border">
                                                <div class="card-body">
                                                    <div class="form-check form-switch mb-3">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="domain_alert_30_days" name="domain_alert_30_days" value="1" 
                                                               {{ old('domain_alert_30_days', true) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="domain_alert_30_days">
                                                            <strong>Alert 30 days before expiration</strong>
                                                        </label>
                                                        <small class="d-block text-muted">Receive an alert when the domain is 30 days away from expiring</small>
                                                    </div>

                                                    <div class="form-check form-switch mb-3">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="domain_alert_5_days" name="domain_alert_5_days" value="1" 
                                                               {{ old('domain_alert_5_days', true) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="domain_alert_5_days">
                                                            <strong>Alert 5 days before expiration</strong>
                                                        </label>
                                                        <small class="d-block text-muted">Receive an alert when the domain is 5 days away from expiring</small>
                                                    </div>

                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="domain_alert_daily_under_30" name="domain_alert_daily_under_30" value="1" 
                                                               {{ old('domain_alert_daily_under_30', true) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="domain_alert_daily_under_30">
                                                            <strong>Daily alerts when 30 days or less remain</strong>
                                                        </label>
                                                        <small class="d-block text-muted">Receive daily alerts when the domain has 30 days or less until expiration</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Domain Communication Channels</label>
                                            <div class="card border">
                                                <div class="card-body">
                                                    <p class="text-muted mb-3">Select how you want to receive domain alerts:</p>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="domain_channel_email" name="domain_communication_channels[]" value="email" 
                                                               {{ in_array('email', old('domain_communication_channels', ['email'])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="domain_channel_email">
                                                            <i class="ri-mail-line me-1"></i>Email
                                                        </label>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="domain_channel_sms" name="domain_communication_channels[]" value="sms" 
                                                               {{ in_array('sms', old('domain_communication_channels', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="domain_channel_sms">
                                                            <i class="ri-message-3-line me-1"></i>SMS
                                                        </label>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="domain_channel_whatsapp" name="domain_communication_channels[]" value="whatsapp" 
                                                               {{ in_array('whatsapp', old('domain_communication_channels', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="domain_channel_whatsapp">
                                                            <i class="ri-whatsapp-line me-1"></i>WhatsApp
                                                        </label>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="domain_channel_telegram" name="domain_communication_channels[]" value="telegram" 
                                                               {{ in_array('telegram', old('domain_communication_channels', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="domain_channel_telegram">
                                                            <i class="ri-telegram-line me-1"></i>Telegram
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="domain_channel_discord" name="domain_communication_channels[]" value="discord" 
                                                               {{ in_array('discord', old('domain_communication_channels', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="domain_channel_discord">
                                                            <i class="ri-discord-line me-1"></i>Discord
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            @error('domain_communication_channels')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Advanced Options -->
                        <div class="card custom-card mt-3">
                            <div class="card-header">
                                <div class="card-title">
                                    <a class="text-default" data-bs-toggle="collapse" href="#advancedOptions" role="button" aria-expanded="false" aria-controls="advancedOptions">
                                        <i class="ri-settings-3-line me-2"></i>Advanced Options
                                        <i class="ri-arrow-down-s-line float-end"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="collapse" id="advancedOptions">
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Request Method -->
                                        <div class="col-md-6 mb-3">
                                            <label for="request_method" class="form-label">Request Method</label>
                                            <select class="form-select @error('request_method') is-invalid @enderror" 
                                                    id="request_method" name="request_method">
                                                <option value="GET" {{ old('request_method', 'GET') == 'GET' ? 'selected' : '' }}>GET</option>
                                                <option value="POST" {{ old('request_method') == 'POST' ? 'selected' : '' }}>POST</option>
                                                <option value="PUT" {{ old('request_method') == 'PUT' ? 'selected' : '' }}>PUT</option>
                                                <option value="PATCH" {{ old('request_method') == 'PATCH' ? 'selected' : '' }}>PATCH</option>
                                                <option value="DELETE" {{ old('request_method') == 'DELETE' ? 'selected' : '' }}>DELETE</option>
                                                <option value="HEAD" {{ old('request_method') == 'HEAD' ? 'selected' : '' }}>HEAD</option>
                                                <option value="OPTIONS" {{ old('request_method') == 'OPTIONS' ? 'selected' : '' }}>OPTIONS</option>
                                            </select>
                                            @error('request_method')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">HTTP method to use for the request</small>
                                        </div>

                                        <!-- Cache Buster -->
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch mt-4">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="cache_buster" name="cache_buster" value="1" 
                                                       {{ old('cache_buster') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="cache_buster">
                                                    Enable Cache Buster
                                                </label>
                                            </div>
                                            <small class="text-muted">Appends a unique string to the URL so every request is unique</small>
                                        </div>

                                        <!-- Basic Auth Username -->
                                        <div class="col-md-6 mb-3">
                                            <label for="basic_auth_username" class="form-label">Basic Auth Username (Optional)</label>
                                            <input type="text" class="form-control @error('basic_auth_username') is-invalid @enderror" 
                                                   id="basic_auth_username" name="basic_auth_username" value="{{ old('basic_auth_username') }}" 
                                                   placeholder="username">
                                            @error('basic_auth_username')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Username for HTTP Basic Authentication</small>
                                        </div>

                                        <!-- Basic Auth Password -->
                                        <div class="col-md-6 mb-3">
                                            <label for="basic_auth_password" class="form-label">Basic Auth Password (Optional)</label>
                                            <input type="password" class="form-control @error('basic_auth_password') is-invalid @enderror" 
                                                   id="basic_auth_password" name="basic_auth_password" value="{{ old('basic_auth_password') }}" 
                                                   placeholder="password">
                                            @error('basic_auth_password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Password for HTTP Basic Authentication</small>
                                        </div>

                                        <!-- Custom Headers -->
                                        <div class="col-md-12 mb-3">
                                            <label for="custom_headers" class="form-label">Custom Request Headers (Optional)</label>
                                            <textarea class="form-control @error('custom_headers') is-invalid @enderror" 
                                                      id="custom_headers" name="custom_headers" 
                                                      rows="4" 
                                                      placeholder="X-API-Key: your-api-key&#10;Authorization: Bearer token&#10;Custom-Header: value">{{ old('custom_headers') }}</textarea>
                                            @error('custom_headers')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Enter one header per line in format: Header-Name: Header-Value</small>
                                        </div>

                                        <!-- Maintenance Period -->
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Maintenance Period</label>
                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <label for="maintenance_start_time" class="form-label small">Start Date & Time</label>
                                                    <input type="datetime-local" class="form-control @error('maintenance_start_time') is-invalid @enderror" 
                                                           id="maintenance_start_time" name="maintenance_start_time" 
                                                           value="{{ old('maintenance_start_time') }}">
                                                    @error('maintenance_start_time')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label for="maintenance_end_time" class="form-label small">End Date & Time</label>
                                                    <input type="datetime-local" class="form-control @error('maintenance_end_time') is-invalid @enderror" 
                                                           id="maintenance_end_time" name="maintenance_end_time" 
                                                           value="{{ old('maintenance_end_time') }}">
                                                    @error('maintenance_end_time')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <small class="text-muted">During this period, no monitor checks or alerts will be performed</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Advanced Options -->

                        <!-- Confirmation Logic (False-Positive Prevention) -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <div class="card-title mb-0">
                                            <i class="ri-shield-check-line me-2 text-primary"></i>Confirmation Logic (False-Positive Prevention)
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="confirmation_enabled" name="confirmation_enabled" value="1" 
                                                       {{ old('confirmation_enabled') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="confirmation_enabled">
                                                    <strong>Enable Multi-Probe Confirmation</strong>
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                Before declaring DOWN, system will recheck from multiple probes with retry logic. 
                                                Only declares incident when X out of Y probes fail. This prevents false positives.
                                            </small>
                                        </div>

                                        <div id="confirmation_settings" style="display: {{ old('confirmation_enabled') ? 'block' : 'none' }};">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="confirmation_probes" class="form-label">Number of Probes</label>
                                                    <input type="number" class="form-control" 
                                                           id="confirmation_probes" name="confirmation_probes" 
                                                           value="{{ old('confirmation_probes', 3) }}" 
                                                           min="2" max="10">
                                                    <small class="text-muted">Total number of probes to use (2-10)</small>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="confirmation_threshold" class="form-label">Failure Threshold</label>
                                                    <input type="number" class="form-control" 
                                                           id="confirmation_threshold" name="confirmation_threshold" 
                                                           value="{{ old('confirmation_threshold', 2) }}" 
                                                           min="1" max="10">
                                                    <small class="text-muted">X out of Y probes must fail to confirm DOWN (e.g., 2 out of 3)</small>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="confirmation_retry_delay" class="form-label">Retry Delay (seconds)</label>
                                                    <input type="number" class="form-control" 
                                                           id="confirmation_retry_delay" name="confirmation_retry_delay" 
                                                           value="{{ old('confirmation_retry_delay', 5) }}" 
                                                           min="1" max="60">
                                                    <small class="text-muted">Delay between probe retries (1-60 seconds)</small>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="confirmation_max_retries" class="form-label">Max Retries</label>
                                                    <input type="number" class="form-control" 
                                                           id="confirmation_max_retries" name="confirmation_max_retries" 
                                                           value="{{ old('confirmation_max_retries', 3) }}" 
                                                           min="1" max="10">
                                                    <small class="text-muted">Maximum retry attempts per probe (1-10)</small>
                                                </div>
                                            </div>
                                            <div class="alert alert-info">
                                                <i class="ri-information-line me-2"></i>
                                                <strong>How it works:</strong> When enabled, the system performs multiple checks from different perspectives (simulated different regions/ISPs) with exponential backoff. 
                                                Only when the failure threshold is met (e.g., 2 out of 3 probes fail) will the monitor be marked as DOWN and alerts sent.
                                                This significantly reduces false positives from temporary network issues.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('panel.uptime-monitors.index') }}" class="btn btn-light btn-wave">
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
    document.addEventListener('DOMContentLoaded', function() {
        const statusCodeSelect = document.getElementById('expected_status_code');
        const statusCodeCustom = document.getElementById('expected_status_code_custom');
        const form = document.getElementById('monitor-form');
        
        // Check if custom should be shown on page load
        if (statusCodeSelect.value === 'custom') {
            statusCodeCustom.style.display = 'block';
            statusCodeCustom.required = true;
            statusCodeSelect.removeAttribute('required');
        }
        
        // Handle select change
        statusCodeSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                statusCodeCustom.style.display = 'block';
                statusCodeCustom.required = true;
                statusCodeSelect.removeAttribute('required');
                statusCodeCustom.focus();
            } else {
                statusCodeCustom.style.display = 'none';
                statusCodeCustom.removeAttribute('required');
                statusCodeSelect.required = true;
                statusCodeCustom.value = '';
            }
        });
        
        // Handle form submission - use custom value if custom is selected
        form.addEventListener('submit', function(e) {
            if (statusCodeSelect.value === 'custom') {
                if (!statusCodeCustom.value || statusCodeCustom.value < 100 || statusCodeCustom.value > 599) {
                    e.preventDefault();
                    alert('Please enter a valid status code between 100 and 599');
                    statusCodeCustom.focus();
                    return false;
                }
                // Create a hidden input with the custom value
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'expected_status_code';
                hiddenInput.value = statusCodeCustom.value;
                form.appendChild(hiddenInput);
                // Remove the select from submission
                statusCodeSelect.disabled = true;
            }
        });

        // Toggle Confirmation settings visibility
        (function() {
            const confirmationCheckbox = document.getElementById('confirmation_enabled');
            const confirmationSettings = document.getElementById('confirmation_settings');
            
            if (confirmationCheckbox && confirmationSettings) {
                function toggleConfirmation() {
                    confirmationSettings.style.display = confirmationCheckbox.checked ? 'block' : 'none';
                    // Make fields required/optional based on checkbox
                    const requiredFields = confirmationSettings.querySelectorAll('input[type="number"]');
                    requiredFields.forEach(field => {
                        field.required = confirmationCheckbox.checked;
                    });
                }
                
                confirmationCheckbox.addEventListener('change', toggleConfirmation);
                toggleConfirmation(); // Set initial state
            }
        })();

        // Toggle SSL monitor options visibility
        (function() {
            const sslCheckbox = document.getElementById('create_ssl_monitor');
            const sslOptions = document.getElementById('ssl_monitor_options');
            
            if (!sslCheckbox || !sslOptions) {
                console.error('SSL Monitor elements not found');
                return;
            }
            
            // Set initial state
            sslOptions.style.display = sslCheckbox.checked ? 'block' : 'none';
            
            // Toggle function
            function toggleSsl() {
                const checkbox = document.getElementById('create_ssl_monitor');
                const options = document.getElementById('ssl_monitor_options');
                if (checkbox && options) {
                    options.style.display = checkbox.checked ? 'block' : 'none';
                }
            }
            
            // Use input event which fires immediately
            sslCheckbox.addEventListener('input', toggleSsl);
            sslCheckbox.addEventListener('change', toggleSsl);
            sslCheckbox.addEventListener('click', function() {
                setTimeout(toggleSsl, 0);
            });
            
            // Handle parent form-switch div clicks
            const sslSwitch = sslCheckbox.closest('.form-check');
            if (sslSwitch) {
                sslSwitch.addEventListener('click', function(e) {
                    if (e.target !== sslCheckbox && e.target.tagName !== 'LABEL') {
                        setTimeout(toggleSsl, 0);
                    }
                });
            }
        })();
        
        // Toggle Domain monitor options visibility
        (function() {
            const domainCheckbox = document.getElementById('create_domain_monitor');
            const domainOptions = document.getElementById('domain_monitor_options');
            
            if (!domainCheckbox || !domainOptions) {
                console.error('Domain Monitor elements not found');
                return;
            }
            
            // Set initial state
            domainOptions.style.display = domainCheckbox.checked ? 'block' : 'none';
            
            // Toggle function
            function toggleDomain() {
                const checkbox = document.getElementById('create_domain_monitor');
                const options = document.getElementById('domain_monitor_options');
                if (checkbox && options) {
                    options.style.display = checkbox.checked ? 'block' : 'none';
                }
            }
            
            // Use input event which fires immediately
            domainCheckbox.addEventListener('input', toggleDomain);
            domainCheckbox.addEventListener('change', toggleDomain);
            domainCheckbox.addEventListener('click', function() {
                setTimeout(toggleDomain, 0);
            });
            
            // Handle parent form-switch div clicks
            const domainSwitch = domainCheckbox.closest('.form-check');
            if (domainSwitch) {
                domainSwitch.addEventListener('click', function(e) {
                    if (e.target !== domainCheckbox && e.target.tagName !== 'LABEL') {
                        setTimeout(toggleDomain, 0);
                    }
                });
            }
        })();

        // Validate SSL and Domain communication channels if monitors are enabled
        document.getElementById('monitor-form').addEventListener('submit', function(e) {
            const createSslMonitor = document.getElementById('create_ssl_monitor');
            if (createSslMonitor && createSslMonitor.checked) {
                const sslChannels = document.querySelectorAll('input[name="ssl_communication_channels[]"]:checked');
                if (sslChannels.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one SSL communication channel.');
                    return false;
                }
            }

            const createDomainMonitor = document.getElementById('create_domain_monitor');
            if (createDomainMonitor && createDomainMonitor.checked) {
                const domainChannels = document.querySelectorAll('input[name="domain_communication_channels[]"]:checked');
                if (domainChannels.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one domain communication channel.');
                    return false;
                }
            }
        });
    </script>
@endsection


