@extends('layouts.master')

@section('styles')
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Edit Uptime Monitor</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('uptime-monitors.index') }}">Uptime Monitoring</a></li>
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
                    <form action="{{ route('uptime-monitors.update', $monitor->uid) }}" method="POST" id="monitor-form">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="name" class="form-label">Monitor Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $monitor->name) }}" 
                                       placeholder="e.g., My Website, Production Site" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="url" class="form-label">Website URL <span class="text-danger">*</span></label>
                                <input type="url" class="form-control @error('url') is-invalid @enderror" 
                                       id="url" name="url" value="{{ old('url', $monitor->url) }}" 
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
                                    <option value="1" {{ old('check_interval', $monitor->check_interval) == 1 ? 'selected' : '' }}>1 minute</option>
                                    <option value="3" {{ old('check_interval', $monitor->check_interval) == 3 ? 'selected' : '' }}>3 minutes</option>
                                    <option value="5" {{ old('check_interval', $monitor->check_interval) == 5 ? 'selected' : '' }}>5 minutes</option>
                                    <option value="10" {{ old('check_interval', $monitor->check_interval) == 10 ? 'selected' : '' }}>10 minutes</option>
                                    <option value="30" {{ old('check_interval', $monitor->check_interval) == 30 ? 'selected' : '' }}>30 minutes</option>
                                    <option value="60" {{ old('check_interval', $monitor->check_interval) == 60 ? 'selected' : '' }}>1 hour</option>
                                </select>
                                @error('check_interval')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">How often to check the website</small>
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
                                @php
                                    $currentStatusCode = old('expected_status_code', $monitor->expected_status_code);
                                    $isCustom = !in_array($currentStatusCode, [200, 201, 202, 204, 301, 302, 303, 307, 308, 400, 401, 403, 404, 405, 408, 410, 429, 500, 502, 503, 504]);
                                @endphp
                                <select class="form-select @error('expected_status_code') is-invalid @enderror" 
                                        id="expected_status_code" name="expected_status_code" required>
                                    <optgroup label="Success (2xx)">
                                        <option value="200" {{ $currentStatusCode == 200 ? 'selected' : '' }}>200 - OK</option>
                                        <option value="201" {{ $currentStatusCode == 201 ? 'selected' : '' }}>201 - Created</option>
                                        <option value="202" {{ $currentStatusCode == 202 ? 'selected' : '' }}>202 - Accepted</option>
                                        <option value="204" {{ $currentStatusCode == 204 ? 'selected' : '' }}>204 - No Content</option>
                                    </optgroup>
                                    <optgroup label="Redirection (3xx)">
                                        <option value="301" {{ $currentStatusCode == 301 ? 'selected' : '' }}>301 - Moved Permanently</option>
                                        <option value="302" {{ $currentStatusCode == 302 ? 'selected' : '' }}>302 - Found</option>
                                        <option value="303" {{ $currentStatusCode == 303 ? 'selected' : '' }}>303 - See Other</option>
                                        <option value="307" {{ $currentStatusCode == 307 ? 'selected' : '' }}>307 - Temporary Redirect</option>
                                        <option value="308" {{ $currentStatusCode == 308 ? 'selected' : '' }}>308 - Permanent Redirect</option>
                                    </optgroup>
                                    <optgroup label="Client Error (4xx)">
                                        <option value="400" {{ $currentStatusCode == 400 ? 'selected' : '' }}>400 - Bad Request</option>
                                        <option value="401" {{ $currentStatusCode == 401 ? 'selected' : '' }}>401 - Unauthorized</option>
                                        <option value="403" {{ $currentStatusCode == 403 ? 'selected' : '' }}>403 - Forbidden</option>
                                        <option value="404" {{ $currentStatusCode == 404 ? 'selected' : '' }}>404 - Not Found</option>
                                        <option value="405" {{ $currentStatusCode == 405 ? 'selected' : '' }}>405 - Method Not Allowed</option>
                                        <option value="408" {{ $currentStatusCode == 408 ? 'selected' : '' }}>408 - Request Timeout</option>
                                        <option value="429" {{ $currentStatusCode == 429 ? 'selected' : '' }}>429 - Too Many Requests</option>
                                        <option value="410" {{ $currentStatusCode == 410 ? 'selected' : '' }}>410 - Request Header Fields Too Large</option>
                                    </optgroup>
                                    <optgroup label="Server Error (5xx)">
                                        <option value="500" {{ $currentStatusCode == 500 ? 'selected' : '' }}>500 - Internal Server Error</option>
                                        <option value="502" {{ $currentStatusCode == 502 ? 'selected' : '' }}>502 - Bad Gateway</option>
                                        <option value="503" {{ $currentStatusCode == 503 ? 'selected' : '' }}>503 - Service Unavailable</option>
                                        <option value="504" {{ $currentStatusCode == 504 ? 'selected' : '' }}>504 - Gateway Timeout</option>
                                    </optgroup>
                                    <option value="custom" {{ $isCustom ? 'selected' : '' }}>Custom Status Code</option>
                                </select>
                                <input type="number" 
                                       class="form-control mt-2 @error('expected_status_code_custom') is-invalid @enderror" 
                                       id="expected_status_code_custom" 
                                       name="expected_status_code_custom" 
                                       value="{{ old('expected_status_code_custom', $isCustom ? $currentStatusCode : '') }}" 
                                       placeholder="Enter custom status code (e.g., 418, 451)"
                                       min="100" 
                                       max="599"
                                       style="display: {{ $isCustom ? 'block' : 'none' }};">
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
                                       id="keyword_present" name="keyword_present" value="{{ old('keyword_present', $monitor->keyword_present) }}" 
                                       placeholder="e.g., Welcome, Online">
                                @error('keyword_present')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Monitor will fail if this keyword is not found in the response</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="keyword_absent" class="form-label">Keyword Must Be Absent (Optional)</label>
                                <input type="text" class="form-control @error('keyword_absent') is-invalid @enderror" 
                                       id="keyword_absent" name="keyword_absent" value="{{ old('keyword_absent', $monitor->keyword_absent) }}" 
                                       placeholder="e.g., Error, Maintenance">
                                @error('keyword_absent')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Monitor will fail if this keyword is found in the response</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" 
                                           id="check_ssl" name="check_ssl" value="1" 
                                           {{ old('check_ssl', $monitor->check_ssl) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="check_ssl">
                                        Check SSL Certificate Validity
                                    </label>
                                </div>
                                <small class="text-muted">Verify that the SSL certificate is valid and not expired</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" 
                                           id="is_active" name="is_active" value="1" 
                                           {{ old('is_active', $monitor->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                                <small class="text-muted">Enable or disable this monitor</small>
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
                                                <option value="GET" {{ old('request_method', $monitor->request_method ?? 'GET') == 'GET' ? 'selected' : '' }}>GET</option>
                                                <option value="POST" {{ old('request_method', $monitor->request_method ?? 'GET') == 'POST' ? 'selected' : '' }}>POST</option>
                                                <option value="PUT" {{ old('request_method', $monitor->request_method ?? 'GET') == 'PUT' ? 'selected' : '' }}>PUT</option>
                                                <option value="PATCH" {{ old('request_method', $monitor->request_method ?? 'GET') == 'PATCH' ? 'selected' : '' }}>PATCH</option>
                                                <option value="DELETE" {{ old('request_method', $monitor->request_method ?? 'GET') == 'DELETE' ? 'selected' : '' }}>DELETE</option>
                                                <option value="HEAD" {{ old('request_method', $monitor->request_method ?? 'GET') == 'HEAD' ? 'selected' : '' }}>HEAD</option>
                                                <option value="OPTIONS" {{ old('request_method', $monitor->request_method ?? 'GET') == 'OPTIONS' ? 'selected' : '' }}>OPTIONS</option>
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
                                                       {{ old('cache_buster', $monitor->cache_buster ?? false) ? 'checked' : '' }}>
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
                                                   id="basic_auth_username" name="basic_auth_username" value="{{ old('basic_auth_username', $monitor->basic_auth_username) }}" 
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
                                                   id="basic_auth_password" name="basic_auth_password" value="{{ old('basic_auth_password', $monitor->basic_auth_password) }}" 
                                                   placeholder="password">
                                            @error('basic_auth_password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Password for HTTP Basic Authentication (leave blank to keep current)</small>
                                        </div>

                                        <!-- Custom Headers -->
                                        <div class="col-md-12 mb-3">
                                            <label for="custom_headers" class="form-label">Custom Request Headers (Optional)</label>
                                            <textarea class="form-control @error('custom_headers') is-invalid @enderror" 
                                                      id="custom_headers" name="custom_headers" 
                                                      rows="4" 
                                                      placeholder="X-API-Key: your-api-key&#10;Authorization: Bearer token&#10;Custom-Header: value">@if(old('custom_headers'))
{{ old('custom_headers') }}
@elseif($monitor->custom_headers && is_array($monitor->custom_headers))
@foreach($monitor->custom_headers as $key => $value)
{{ $key }}: {{ $value }}
@endforeach
@endif</textarea>
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
                                                           value="{{ old('maintenance_start_time', $monitor->maintenance_start_time ? (is_string($monitor->maintenance_start_time) ? date('Y-m-d\TH:i', strtotime($monitor->maintenance_start_time)) : $monitor->maintenance_start_time->format('Y-m-d\TH:i')) : '') }}">
                                                    @error('maintenance_start_time')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label for="maintenance_end_time" class="form-label small">End Date & Time</label>
                                                    <input type="datetime-local" class="form-control @error('maintenance_end_time') is-invalid @enderror" 
                                                           id="maintenance_end_time" name="maintenance_end_time" 
                                                           value="{{ old('maintenance_end_time', $monitor->maintenance_end_time ? (is_string($monitor->maintenance_end_time) ? date('Y-m-d\TH:i', strtotime($monitor->maintenance_end_time)) : $monitor->maintenance_end_time->format('Y-m-d\TH:i')) : '') }}">
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

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('uptime-monitors.index') }}" class="btn btn-light btn-wave">
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
    });
</script>
@endsection


