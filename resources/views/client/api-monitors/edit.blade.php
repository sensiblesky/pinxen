@extends('layouts.master')

@section('title', 'Edit API Monitor - PingXeno')

@section('styles')
@endsection

@section('content')
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Edit API Monitor</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('api-monitors.index') }}">API Monitoring</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </div>
    </div>

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

    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">API Monitor Information</div>
                </div>
                <div class="card-body">
                    <form action="{{ route('api-monitors.update', $monitor->uid) }}" method="POST" id="api-monitor-form">
                        @csrf
                        @method('PUT')
                        
                        <!-- Basic Information -->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="name" class="form-label">Monitor Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $monitor->name) }}" 
                                       placeholder="e.g., User API, Payment Gateway" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-8 mb-3">
                                <label for="url" class="form-label">API Endpoint URL <span class="text-danger">*</span></label>
                                <input type="url" class="form-control @error('url') is-invalid @enderror" 
                                       id="url" name="url" value="{{ old('url', $monitor->url) }}" 
                                       placeholder="https://api.example.com/v1/users" required>
                                @error('url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="request_method" class="form-label">Request Method <span class="text-danger">*</span></label>
                                <select class="form-select @error('request_method') is-invalid @enderror" 
                                        id="request_method" name="request_method" required>
                                    <option value="GET" {{ old('request_method', $monitor->request_method) == 'GET' ? 'selected' : '' }}>GET</option>
                                    <option value="POST" {{ old('request_method', $monitor->request_method) == 'POST' ? 'selected' : '' }}>POST</option>
                                    <option value="PUT" {{ old('request_method', $monitor->request_method) == 'PUT' ? 'selected' : '' }}>PUT</option>
                                    <option value="PATCH" {{ old('request_method', $monitor->request_method) == 'PATCH' ? 'selected' : '' }}>PATCH</option>
                                    <option value="DELETE" {{ old('request_method', $monitor->request_method) == 'DELETE' ? 'selected' : '' }}>DELETE</option>
                                    <option value="HEAD" {{ old('request_method', $monitor->request_method) == 'HEAD' ? 'selected' : '' }}>HEAD</option>
                                    <option value="OPTIONS" {{ old('request_method', $monitor->request_method) == 'OPTIONS' ? 'selected' : '' }}>OPTIONS</option>
                                </select>
                                @error('request_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="timeout" class="form-label">Timeout (seconds) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('timeout') is-invalid @enderror" 
                                       id="timeout" name="timeout" value="{{ old('timeout', $monitor->timeout) }}" 
                                       min="5" max="300" required>
                                @error('timeout')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="expected_status_code" class="form-label">Expected Status Code <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('expected_status_code') is-invalid @enderror" 
                                       id="expected_status_code" name="expected_status_code" value="{{ old('expected_status_code', $monitor->expected_status_code) }}" 
                                       min="100" max="599" required>
                                @error('expected_status_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">e.g., 200, 201, 204</small>
                            </div>
                        </div>

                        <!-- Authentication -->
                        <div class="card custom-card mt-3">
                            <div class="card-header">
                                <div class="card-title">Authentication</div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="auth_type" class="form-label">Authentication Type <span class="text-danger">*</span></label>
                                        <select class="form-select @error('auth_type') is-invalid @enderror" 
                                                id="auth_type" name="auth_type" required onchange="toggleAuthFields()">
                                            <option value="none" {{ old('auth_type', $monitor->auth_type) == 'none' ? 'selected' : '' }}>None</option>
                                            <option value="bearer" {{ old('auth_type', $monitor->auth_type) == 'bearer' ? 'selected' : '' }}>Bearer Token</option>
                                            <option value="basic" {{ old('auth_type', $monitor->auth_type) == 'basic' ? 'selected' : '' }}>Basic Auth</option>
                                            <option value="apikey" {{ old('auth_type', $monitor->auth_type) == 'apikey' ? 'selected' : '' }}>API Key</option>
                                        </select>
                                        @error('auth_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Bearer Token -->
                                    <div class="col-md-12 mb-3" id="auth_bearer_fields" style="display: {{ old('auth_type', $monitor->auth_type) == 'bearer' ? 'block' : 'none' }};">
                                        <label for="auth_token" class="form-label">Bearer Token</label>
                                        <input type="text" class="form-control @error('auth_token') is-invalid @enderror" 
                                               id="auth_token" name="auth_token" value="{{ old('auth_token', $monitor->auth_token) }}" 
                                               placeholder="your-bearer-token">
                                        @error('auth_token')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Basic Auth -->
                                    <div id="auth_basic_fields" style="display: {{ old('auth_type', $monitor->auth_type) == 'basic' ? 'block' : 'none' }};">
                                        <div class="col-md-6 mb-3">
                                            <label for="auth_username" class="form-label">Username</label>
                                            <input type="text" class="form-control @error('auth_username') is-invalid @enderror" 
                                                   id="auth_username" name="auth_username" value="{{ old('auth_username', $monitor->auth_username) }}" 
                                                   placeholder="username">
                                            @error('auth_username')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="auth_password" class="form-label">Password</label>
                                            <input type="password" class="form-control @error('auth_password') is-invalid @enderror" 
                                                   id="auth_password" name="auth_password" value="{{ old('auth_password', $monitor->auth_password) }}" 
                                                   placeholder="password">
                                            @error('auth_password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- API Key -->
                                    <div id="auth_apikey_fields" style="display: {{ old('auth_type', $monitor->auth_type) == 'apikey' ? 'block' : 'none' }};">
                                        <div class="col-md-6 mb-3">
                                            <label for="auth_header_name" class="form-label">Header Name</label>
                                            <input type="text" class="form-control @error('auth_header_name') is-invalid @enderror" 
                                                   id="auth_header_name" name="auth_header_name" value="{{ old('auth_header_name', $monitor->auth_header_name ?? 'X-API-Key') }}" 
                                                   placeholder="X-API-Key">
                                            @error('auth_header_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="auth_token" class="form-label">API Key</label>
                                            <input type="text" class="form-control @error('auth_token') is-invalid @enderror" 
                                                   id="auth_token_apikey" name="auth_token" value="{{ old('auth_token', $monitor->auth_token) }}" 
                                                   placeholder="your-api-key">
                                            @error('auth_token')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Request Configuration -->
                        <div class="card custom-card mt-3">
                            <div class="card-header">
                                <div class="card-title">Request Configuration</div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="content_type" class="form-label">Content Type</label>
                                        <select class="form-select @error('content_type') is-invalid @enderror" 
                                                id="content_type" name="content_type">
                                            <option value="application/json" {{ old('content_type', $monitor->content_type ?? 'application/json') == 'application/json' ? 'selected' : '' }}>application/json</option>
                                            <option value="application/xml" {{ old('content_type', $monitor->content_type) == 'application/xml' ? 'selected' : '' }}>application/xml</option>
                                            <option value="application/x-www-form-urlencoded" {{ old('content_type', $monitor->content_type) == 'application/x-www-form-urlencoded' ? 'selected' : '' }}>application/x-www-form-urlencoded</option>
                                            <option value="text/plain" {{ old('content_type', $monitor->content_type) == 'text/plain' ? 'selected' : '' }}>text/plain</option>
                                        </select>
                                        @error('content_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label for="request_headers" class="form-label">Custom Request Headers (Optional)</label>
                                        <textarea class="form-control @error('request_headers') is-invalid @enderror" 
                                                  id="request_headers" name="request_headers" 
                                                  rows="4" 
                                                  placeholder="X-Custom-Header: value&#10;Accept: application/json">@if($monitor->request_headers && is_array($monitor->request_headers))
@foreach($monitor->request_headers as $header)
{{ $header['name'] ?? '' }}: {{ $header['value'] ?? '' }}
@endforeach
@endif{{ old('request_headers') }}</textarea>
                                        @error('request_headers')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Enter one header per line in format: Header-Name: Header-Value</small>
                                    </div>

                                    <div class="col-md-12 mb-3" id="request_body_section" style="display: {{ in_array(old('request_method', $monitor->request_method), ['POST', 'PUT', 'PATCH']) ? 'block' : 'none' }};">
                                        <label for="request_body" class="form-label">Request Body (Optional)</label>
                                        <textarea class="form-control @error('request_body') is-invalid @enderror" 
                                                  id="request_body" name="request_body" 
                                                  rows="6" 
                                                  placeholder='{"key": "value"}'>{{ old('request_body', $monitor->request_body) }}</textarea>
                                        @error('request_body')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">JSON or XML body for POST/PUT/PATCH requests</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Response Validation -->
                        <div class="card custom-card mt-3">
                            <div class="card-header">
                                <div class="card-title">Response Validation</div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="max_latency_ms" class="form-label">Max Latency (milliseconds)</label>
                                        <input type="number" class="form-control @error('max_latency_ms') is-invalid @enderror" 
                                               id="max_latency_ms" name="max_latency_ms" value="{{ old('max_latency_ms', $monitor->max_latency_ms) }}" 
                                               min="1" placeholder="e.g., 1000">
                                        @error('max_latency_ms')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Alert if response time exceeds this value</small>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="validate_response_body" name="validate_response_body" value="1" 
                                                   {{ old('validate_response_body', $monitor->validate_response_body) ? 'checked' : '' }}
                                                   onchange="toggleResponseAssertions()">
                                            <label class="form-check-label" for="validate_response_body">
                                                Validate Response Body
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-12 mb-3" id="response_assertions_section" style="display: {{ old('validate_response_body', $monitor->validate_response_body) ? 'block' : 'none' }};">
                                        <label class="form-label">Smart Response Assertions</label>
                                        
                                        <!-- Visual Assertion Builder -->
                                        <div class="card border mb-3">
                                            <div class="card-body">
                                                <div id="assertions-list">
                                                    <!-- Assertions will be added here -->
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-assertion-btn">
                                                    <i class="ri-add-line me-1"></i>Add Assertion
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info" id="add-conditional-btn">
                                                    <i class="ri-code-s-slash-line me-1"></i>Add Conditional Rule
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Hidden JSON input for form submission -->
                                        <input type="hidden" id="response_assertions" name="response_assertions" value="{{ old('response_assertions', $monitor->response_assertions ? json_encode($monitor->response_assertions) : '') }}">
                                        
                                        <!-- JSON Editor (Advanced) -->
                                        <div class="mb-2">
                                            <button type="button" class="btn btn-sm btn-link p-0" id="toggle-json-editor">
                                                <i class="ri-code-line me-1"></i>Advanced: Edit as JSON
                                            </button>
                                        </div>
                                        <div id="json-editor-section" style="display: none;">
                                            <textarea class="form-control @error('response_assertions') is-invalid @enderror" 
                                                      id="response_assertions_json" 
                                                      rows="8" 
                                                      placeholder='[{"type": "json_path", "path": "$.data.user.active", "operator": "==", "value": true}]'>{{ old('response_assertions', $monitor->response_assertions ? json_encode($monitor->response_assertions, JSON_PRETTY_PRINT) : '') }}</textarea>
                                            @error('response_assertions')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <!-- Examples and Help -->
                                        <div class="alert alert-info mt-3">
                                            <h6 class="alert-heading"><i class="ri-information-line me-1"></i>Smart Assertion Examples:</h6>
                                            <ul class="mb-0 small">
                                                <li><strong>JSON Path:</strong> <code>$.data.user.active == true</code></li>
                                                <li><strong>Version Check:</strong> <code>$.meta.version >= 3</code></li>
                                                <li><strong>Regex:</strong> <code>regex: /^success$/i</code> on path <code>$.status</code></li>
                                                <li><strong>Conditional:</strong> If <code>$.status == "pending"</code> → Retry after 5s</li>
                                                <li><strong>Auth Expiry:</strong> If <code>$.error.code == "TOKEN_EXPIRED"</code> → Re-authenticate</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stateful Monitoring (Multi-Step Flows) -->
                        <div class="card custom-card mt-3">
                            <div class="card-header">
                                <div class="card-title">Stateful API Monitoring (Session-Aware)</div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="is_stateful" name="is_stateful" value="1" 
                                                   {{ old('is_stateful', $monitor->is_stateful) ? 'checked' : '' }}
                                                   onchange="toggleStatefulMonitoring()">
                                            <label class="form-check-label" for="is_stateful">
                                                Enable Stateful Monitoring (Multi-Step Flows)
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            Enable to create multi-step API flows with variable extraction and session management.
                                            Example: POST /login → extract token → GET /orders?token=@{{token}} → POST /checkout
                                        </small>
                                    </div>
                                </div>

                                <div id="stateful_monitoring_section" style="display: {{ old('is_stateful', $monitor->is_stateful) ? 'block' : 'none' }};">
                                    <!-- Monitoring Steps -->
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Monitoring Steps</label>
                                        <div id="monitoring-steps-list" class="mb-3">
                                            <!-- Steps will be added here -->
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-step-btn">
                                            <i class="ri-add-line me-1"></i>Add Step
                                        </button>
                                        <input type="hidden" id="monitoring_steps" name="monitoring_steps" value="{{ old('monitoring_steps', $monitor->monitoring_steps ? json_encode($monitor->monitoring_steps) : '') }}">
                                    </div>

                                    <!-- Variable Extraction Rules -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Variable Extraction Rules</label>
                                        <div id="variable-extraction-list" class="mb-3">
                                            <!-- Rules will be added here -->
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-info" id="add-variable-rule-btn">
                                            <i class="ri-add-line me-1"></i>Add Variable Rule
                                        </button>
                                        <input type="hidden" id="variable_extraction_rules" name="variable_extraction_rules" value="{{ old('variable_extraction_rules', $monitor->variable_extraction_rules ? json_encode($monitor->variable_extraction_rules) : '') }}">
                                    </div>

                                    <div class="alert alert-info">
                                        <h6 class="alert-heading"><i class="ri-information-line me-1"></i>How Stateful Monitoring Works:</h6>
                                        <ol class="mb-0 small">
                                            <li><strong>Define Steps:</strong> Create multiple API calls that execute in sequence</li>
                                            <li><strong>Extract Variables:</strong> Extract values from responses using JSON paths (e.g., <code>$.data.token</code>)</li>
                                            <li><strong>Use Variables:</strong> Reference variables in subsequent steps using <code>@{{variable_name}}</code> syntax</li>
                                            <li><strong>Example Flow:</strong>
                                                <ul>
                                                    <li>Step 1: POST /login → Extract <code>token</code> from <code>$.token</code></li>
                                                    <li>Step 2: GET /orders?token=@{{token}} → Extract <code>order_id</code> from <code>$.orders[0].id</code></li>
                                                    <li>Step 3: POST /checkout with body <code>{"order_id": "@{{order_id}}"}</code></li>
                                                </ul>
                                            </li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Auto-Auth & Token Lifecycle -->
                        <div class="card custom-card mt-3">
                            <div class="card-header">
                                <div class="card-title">Auto-Auth & Token Lifecycle Handling</div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="auto_auth_enabled" name="auto_auth_enabled" value="1" 
                                                   {{ old('auto_auth_enabled', $monitor->auto_auth_enabled) ? 'checked' : '' }}
                                                   onchange="toggleAutoAuth()">
                                            <label class="form-check-label" for="auto_auth_enabled">
                                                Enable Auto-Auth (Automatic Token Refresh)
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            Automatically refresh tokens before expiration. Supports OAuth2, JWT, and API key rotation reminders.
                                        </small>
                                    </div>
                                </div>

                                <div id="auto_auth_section" style="display: {{ old('auto_auth_enabled', $monitor->auto_auth_enabled) ? 'block' : 'none' }};">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="auto_auth_type" class="form-label">Auth Type</label>
                                            <select class="form-select" id="auto_auth_type" name="auto_auth_type" onchange="toggleAuthTypeFields()">
                                                <option value="">Select...</option>
                                                <option value="oauth2_client_credentials" {{ old('auto_auth_type', $monitor->auto_auth_type) == 'oauth2_client_credentials' ? 'selected' : '' }}>OAuth2 Client Credentials</option>
                                                <option value="oauth2_password" {{ old('auto_auth_type', $monitor->auto_auth_type) == 'oauth2_password' ? 'selected' : '' }}>OAuth2 Password Grant</option>
                                                <option value="oauth2_refresh_token" {{ old('auto_auth_type', $monitor->auto_auth_type) == 'oauth2_refresh_token' ? 'selected' : '' }}>OAuth2 Refresh Token</option>
                                                <option value="jwt" {{ old('auto_auth_type', $monitor->auto_auth_type) == 'jwt' ? 'selected' : '' }}>JWT (Extract from Response)</option>
                                                <option value="apikey_rotation" {{ old('auto_auth_type', $monitor->auto_auth_type) == 'apikey_rotation' ? 'selected' : '' }}>API Key Rotation Reminder</option>
                                            </select>
                                        </div>

                                        <!-- OAuth2 Common Fields -->
                                        <div id="oauth2_common_fields" style="display: none;">
                                            <div class="col-md-12 mb-3">
                                                <label for="oauth2_token_url" class="form-label">Token URL <span class="text-danger">*</span></label>
                                                <input type="url" class="form-control" 
                                                       id="oauth2_token_url" name="oauth2_token_url" 
                                                       value="{{ old('oauth2_token_url', $monitor->oauth2_token_url) }}"
                                                       placeholder="https://api.example.com/oauth/token">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="oauth2_client_id" class="form-label">Client ID</label>
                                                <input type="text" class="form-control" 
                                                       id="oauth2_client_id" name="oauth2_client_id" 
                                                       value="{{ old('oauth2_client_id', $monitor->oauth2_client_id) }}">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="oauth2_client_secret" class="form-label">Client Secret</label>
                                                <input type="password" class="form-control" 
                                                       id="oauth2_client_secret" name="oauth2_client_secret" 
                                                       value="{{ old('oauth2_client_secret', $monitor->oauth2_client_secret) }}">
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label for="oauth2_scope" class="form-label">Scope (optional)</label>
                                                <input type="text" class="form-control" 
                                                       id="oauth2_scope" name="oauth2_scope" 
                                                       value="{{ old('oauth2_scope', $monitor->oauth2_scope) }}"
                                                       placeholder="read write">
                                            </div>
                                        </div>

                                        <!-- OAuth2 Password Fields -->
                                        <div id="oauth2_password_fields" style="display: none;">
                                            <div class="col-md-6 mb-3">
                                                <label for="oauth2_username" class="form-label">Username</label>
                                                <input type="text" class="form-control" 
                                                       id="oauth2_username" name="oauth2_username" 
                                                       value="{{ old('oauth2_username', $monitor->oauth2_username) }}">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="oauth2_password" class="form-label">Password</label>
                                                <input type="password" class="form-control" 
                                                       id="oauth2_password" name="oauth2_password" 
                                                       value="{{ old('oauth2_password', $monitor->oauth2_password) }}">
                                            </div>
                                        </div>

                                        <!-- JWT Fields -->
                                        <div id="jwt_fields" style="display: none;">
                                            <div class="col-md-12 mb-3">
                                                <label for="jwt_token_path" class="form-label">JWT Token Path (JSON Path)</label>
                                                <input type="text" class="form-control" 
                                                       id="jwt_token_path" name="jwt_token_path" 
                                                       value="{{ old('jwt_token_path', $monitor->jwt_token_path) }}"
                                                       placeholder="$.access_token or $.data.token">
                                                <small class="text-muted">JSON path to extract JWT token from API response</small>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="jwt_expiration_buffer_seconds" class="form-label">Expiration Buffer (seconds)</label>
                                                <input type="number" class="form-control" 
                                                       id="jwt_expiration_buffer_seconds" name="jwt_expiration_buffer_seconds" 
                                                       value="{{ old('jwt_expiration_buffer_seconds', $monitor->jwt_expiration_buffer_seconds ?? 300) }}"
                                                       min="0">
                                                <small class="text-muted">Refresh token this many seconds before expiration</small>
                                            </div>
                                        </div>

                                        <!-- API Key Rotation Fields -->
                                        <div id="apikey_rotation_fields" style="display: none;">
                                            <div class="col-md-6 mb-3">
                                                <label for="apikey_rotation_days" class="form-label">Rotation Reminder (days)</label>
                                                <input type="number" class="form-control" 
                                                       id="apikey_rotation_days" name="apikey_rotation_days" 
                                                       value="{{ old('apikey_rotation_days', $monitor->apikey_rotation_days) }}"
                                                       min="1">
                                                <small class="text-muted">Alert this many days before API key should be rotated</small>
                                            </div>
                                        </div>

                                        <!-- Auto-Refresh Settings -->
                                        <div class="col-md-12 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="auto_refresh_on_expiry" name="auto_refresh_on_expiry" value="1" 
                                                       {{ old('auto_refresh_on_expiry', $monitor->auto_refresh_on_expiry ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="auto_refresh_on_expiry">
                                                    Auto-refresh on expiry
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="retry_after_refresh" name="retry_after_refresh" value="1" 
                                                       {{ old('retry_after_refresh', $monitor->retry_after_refresh ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="retry_after_refresh">
                                                    Retry failed checks after refresh
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="max_refresh_attempts" class="form-label">Max Refresh Attempts</label>
                                            <input type="number" class="form-control" 
                                                   id="max_refresh_attempts" name="max_refresh_attempts" 
                                                   value="{{ old('max_refresh_attempts', $monitor->max_refresh_attempts ?? 3) }}"
                                                   min="1" max="10">
                                        </div>
                                    </div>

                                    <div class="alert alert-info mt-3">
                                        <h6 class="alert-heading"><i class="ri-information-line me-1"></i>How Auto-Auth Works:</h6>
                                        <ul class="mb-0 small">
                                            <li><strong>OAuth2:</strong> Automatically refreshes tokens using configured grant type</li>
                                            <li><strong>JWT:</strong> Extracts JWT from responses and tracks expiration</li>
                                            <li><strong>Auto-Refresh:</strong> Refreshes tokens before expiration (configurable buffer)</li>
                                            <li><strong>Retry:</strong> Automatically retries failed checks after successful token refresh</li>
                                            <li><strong>Alerts:</strong> Only alerts if token refresh fails</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contract / Schema Drift Detection -->
                        <div class="card custom-card mt-3">
                            <div class="card-header">
                                <div class="card-title">Contract / Schema Drift Detection</div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="schema_drift_enabled" name="schema_drift_enabled" value="1" 
                                                   {{ old('schema_drift_enabled', $monitor->schema_drift_enabled) ? 'checked' : '' }}
                                                   onchange="toggleSchemaDrift()">
                                            <label class="form-check-label" for="schema_drift_enabled">
                                                Enable Schema Drift Detection
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            Compare live API responses against OpenAPI/Swagger specification. Alerts when fields disappear, types change, or breaking changes occur.
                                        </small>
                                    </div>
                                </div>

                                <div id="schema_drift_section" style="display: {{ old('schema_drift_enabled', $monitor->schema_drift_enabled) ? 'block' : 'none' }};">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="schema_source_type" class="form-label">Schema Source</label>
                                            <select class="form-select" id="schema_source_type" name="schema_source_type" onchange="toggleSchemaSource()">
                                                <option value="">Select...</option>
                                                <option value="upload" {{ old('schema_source_type', $monitor->schema_source_type) == 'upload' ? 'selected' : '' }}>Upload OpenAPI/Swagger File</option>
                                                <option value="url" {{ old('schema_source_type', $monitor->schema_source_type) == 'url' ? 'selected' : '' }}>Link to OpenAPI/Swagger URL</option>
                                            </select>
                                        </div>

                                        <!-- Upload Schema -->
                                        <div id="schema_upload_fields" style="display: {{ old('schema_source_type', $monitor->schema_source_type) == 'upload' ? 'block' : 'none' }};">
                                            <div class="col-md-12 mb-3">
                                                <label for="schema_file" class="form-label">OpenAPI/Swagger File (JSON/YAML)</label>
                                                <input type="file" class="form-control" 
                                                       id="schema_file" name="schema_file" 
                                                       accept=".json,.yaml,.yml"
                                                       onchange="handleSchemaFileUpload(this)">
                                                <small class="text-muted">Upload OpenAPI 3.0 or Swagger 2.0 specification file</small>
                                                @if($monitor->schema_content)
                                                    <div class="mt-2">
                                                        <small class="text-success">Current schema is loaded. Upload a new file to replace it.</small>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- URL Schema -->
                                        <div id="schema_url_fields" style="display: {{ old('schema_source_type', $monitor->schema_source_type) == 'url' ? 'block' : 'none' }};">
                                            <div class="col-md-12 mb-3">
                                                <label for="schema_url" class="form-label">Schema URL</label>
                                                <input type="url" class="form-control" 
                                                       id="schema_url" name="schema_url" 
                                                       value="{{ old('schema_url', $monitor->schema_url) }}"
                                                       placeholder="https://api.example.com/openapi.json">
                                                <small class="text-muted">URL to fetch OpenAPI/Swagger specification</small>
                                            </div>
                                        </div>

                                        <!-- Schema Content (hidden, populated from upload/URL) -->
                                        <input type="hidden" id="schema_content" name="schema_content" value="{{ old('schema_content', $monitor->schema_content) }}">

                                        <!-- Drift Detection Settings -->
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Detection Rules</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="detect_missing_fields" name="detect_missing_fields" value="1" 
                                                       {{ old('detect_missing_fields', $monitor->detect_missing_fields ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="detect_missing_fields">
                                                    Alert when required fields disappear
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="detect_type_changes" name="detect_type_changes" value="1" 
                                                       {{ old('detect_type_changes', $monitor->detect_type_changes ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="detect_type_changes">
                                                    Alert when field types change (e.g., string → number)
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="detect_breaking_changes" name="detect_breaking_changes" value="1" 
                                                       {{ old('detect_breaking_changes', $monitor->detect_breaking_changes ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="detect_breaking_changes">
                                                    Alert when new breaking fields appear
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="detect_enum_violations" name="detect_enum_violations" value="1" 
                                                       {{ old('detect_enum_violations', $monitor->detect_enum_violations ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="detect_enum_violations">
                                                    Alert when enum values are violated
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Schema Preview -->
                                        @if($monitor->schema_parsed)
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Current Schema Info</label>
                                            <div class="alert alert-info mb-0">
                                                <strong>Version:</strong> {{ $monitor->schema_parsed['version'] ?? 'Unknown' }}<br>
                                                <strong>Paths:</strong> {{ count($monitor->schema_parsed['paths'] ?? []) }} endpoints defined<br>
                                                @if($monitor->schema_last_validated_at)
                                                    <strong>Last Validated:</strong> {{ $monitor->schema_last_validated_at->format('Y-m-d H:i:s') }}
                                                @endif
                                            </div>
                                        </div>
                                        @endif
                                    </div>

                                    <div class="alert alert-info mt-3">
                                        <h6 class="alert-heading"><i class="ri-information-line me-1"></i>Schema Drift Detection:</h6>
                                        <ul class="mb-0 small">
                                            <li><strong>Upload or Link:</strong> Provide OpenAPI 3.0 or Swagger 2.0 specification</li>
                                            <li><strong>Automatic Comparison:</strong> System compares live responses vs spec on each check</li>
                                            <li><strong>Breaking Changes:</strong> Alerts when fields disappear, types change, or new breaking fields appear</li>
                                            <li><strong>Enterprise-Grade:</strong> Catch API contract violations before they impact consumers</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Monitoring Settings -->
                        <div class="card custom-card mt-3">
                            <div class="card-header">
                                <div class="card-title">Monitoring Settings</div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="check_ssl" name="check_ssl" value="1" 
                                                   {{ old('check_ssl', $monitor->check_ssl) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="check_ssl">
                                                Enable SSL Verification
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            Disable to monitor APIs with invalid/self-signed certificates
                                        </small>
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
                                        <small class="text-muted d-block mt-1">
                                            Enable or disable this monitor
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Communication Channels -->
                        <div class="card custom-card mt-3">
                            <div class="card-header">
                                <div class="card-title">Communication Channels <span class="text-danger">*</span></div>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">Select how you want to receive alerts:</p>
                                @php
                                    $selectedChannels = old('communication_channels', $communicationPreferences->pluck('communication_channel')->toArray());
                                @endphp
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           id="channel_email" name="communication_channels[]" value="email" 
                                           {{ in_array('email', $selectedChannels) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="channel_email">
                                        <i class="ri-mail-line me-1"></i>Email
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           id="channel_sms" name="communication_channels[]" value="sms" 
                                           {{ in_array('sms', $selectedChannels) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="channel_sms">
                                        <i class="ri-message-3-line me-1"></i>SMS
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           id="channel_whatsapp" name="communication_channels[]" value="whatsapp" 
                                           {{ in_array('whatsapp', $selectedChannels) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="channel_whatsapp">
                                        <i class="ri-whatsapp-line me-1"></i>WhatsApp
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           id="channel_telegram" name="communication_channels[]" value="telegram" 
                                           {{ in_array('telegram', $selectedChannels) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="channel_telegram">
                                        <i class="ri-telegram-line me-1"></i>Telegram
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           id="channel_discord" name="communication_channels[]" value="discord" 
                                           {{ in_array('discord', $selectedChannels) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="channel_discord">
                                        <i class="ri-discord-line me-1"></i>Discord
                                    </label>
                                </div>
                                @error('communication_channels')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('api-monitors.show', $monitor) }}" class="btn btn-light btn-wave">
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
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle auth fields based on auth type
        toggleAuthFields();
        
        // Toggle request body based on method
        document.getElementById('request_method').addEventListener('change', function() {
            const method = this.value;
            const bodySection = document.getElementById('request_body_section');
            bodySection.style.display = ['POST', 'PUT', 'PATCH'].includes(method) ? 'block' : 'none';
        });
        
        // Validate communication channels
        document.getElementById('api-monitor-form').addEventListener('submit', function(e) {
            const channels = document.querySelectorAll('input[name="communication_channels[]"]:checked');
            if (channels.length === 0) {
                e.preventDefault();
                alert('Please select at least one communication channel.');
                return false;
            }
        });
    });
    
    function toggleAuthFields() {
        const authType = document.getElementById('auth_type').value;
        document.getElementById('auth_bearer_fields').style.display = authType === 'bearer' ? 'block' : 'none';
        document.getElementById('auth_basic_fields').style.display = authType === 'basic' ? 'block' : 'none';
        document.getElementById('auth_apikey_fields').style.display = authType === 'apikey' ? 'block' : 'none';
    }
    
    function toggleResponseAssertions() {
        const validate = document.getElementById('validate_response_body').checked;
        document.getElementById('response_assertions_section').style.display = validate ? 'block' : 'none';
    }

    // Smart Assertions Builder (same as create form)
    let assertionCounter = 0;
    let assertions = [];

    // Load existing assertions if any
    document.addEventListener('DOMContentLoaded', function() {
        const existingAssertions = document.getElementById('response_assertions').value;
        if (existingAssertions) {
            try {
                assertions = JSON.parse(existingAssertions);
                renderAssertions();
            } catch (e) {
                console.error('Error parsing existing assertions:', e);
            }
        }

        // Add assertion button
        document.getElementById('add-assertion-btn').addEventListener('click', function() {
            addAssertion();
        });

        // Add conditional rule button
        document.getElementById('add-conditional-btn').addEventListener('click', function() {
            addConditionalRule();
        });

        // Toggle JSON editor
        document.getElementById('toggle-json-editor').addEventListener('click', function() {
            const editor = document.getElementById('json-editor-section');
            editor.style.display = editor.style.display === 'none' ? 'block' : 'none';
        });

        // Sync JSON editor with visual builder
        document.getElementById('response_assertions_json').addEventListener('blur', function() {
            try {
                const json = JSON.parse(this.value);
                assertions = Array.isArray(json) ? json : [];
                renderAssertions();
                updateHiddenInput();
            } catch (e) {
                alert('Invalid JSON: ' + e.message);
            }
        });
    });

    function addAssertion(type = 'json_path') {
        const assertion = {
            id: 'assertion_' + (assertionCounter++),
            type: type,
            path: '',
            operator: 'equals',
            value: '',
            pattern: ''
        };
        assertions.push(assertion);
        renderAssertions();
        updateHiddenInput();
    }

    function addConditionalRule() {
        const assertion = {
            id: 'conditional_' + (assertionCounter++),
            type: 'conditional',
            condition: {
                path: '',
                operator: 'equals',
                value: ''
            },
            action: {
                type: 'retry',
                value: 5,
                message: ''
            }
        };
        assertions.push(assertion);
        renderAssertions();
        updateHiddenInput();
    }

    function renderAssertions() {
        const container = document.getElementById('assertions-list');
        container.innerHTML = '';

        if (assertions.length === 0) {
            container.innerHTML = '<p class="text-muted mb-0">No assertions added. Click "Add Assertion" to get started.</p>';
            return;
        }

        assertions.forEach((assertion, index) => {
            const card = document.createElement('div');
            card.className = 'card border mb-2';
            card.innerHTML = buildAssertionHTML(assertion, index);
            container.appendChild(card);
        });
    }

    function buildAssertionHTML(assertion, index) {
        if (assertion.type === 'conditional') {
            return `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0"><i class="ri-code-s-slash-line me-1"></i>Conditional Rule #${index + 1}</h6>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeAssertion('${assertion.id}')">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small">If Path</label>
                            <input type="text" class="form-control form-control-sm" 
                                   value="${assertion.condition?.path || ''}" 
                                   placeholder="$.status"
                                   onchange="updateAssertion('${assertion.id}', 'condition.path', this.value)">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Operator</label>
                            <select class="form-select form-select-sm" 
                                    onchange="updateAssertion('${assertion.id}', 'condition.operator', this.value)">
                                <option value="equals" ${(assertion.condition?.operator || 'equals') === 'equals' ? 'selected' : ''}>==</option>
                                <option value="!=" ${assertion.condition?.operator === '!=' ? 'selected' : ''}>!=</option>
                                <option value="contains" ${assertion.condition?.operator === 'contains' ? 'selected' : ''}>contains</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Value</label>
                            <input type="text" class="form-control form-control-sm" 
                                   value="${assertion.condition?.value || ''}" 
                                   placeholder="pending"
                                   onchange="updateAssertion('${assertion.id}', 'condition.value', this.value)">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Then</label>
                            <select class="form-select form-select-sm" 
                                    onchange="updateAssertion('${assertion.id}', 'action.type', this.value)">
                                <option value="retry" ${(assertion.action?.type || 'retry') === 'retry' ? 'selected' : ''}>Retry</option>
                                <option value="re_auth" ${assertion.action?.type === 're_auth' ? 'selected' : ''}>Re-Auth</option>
                                <option value="alert" ${assertion.action?.type === 'alert' ? 'selected' : ''}>Alert</option>
                            </select>
                        </div>
                        ${(assertion.action?.type || 'retry') === 'retry' ? `
                        <div class="col-md-2">
                            <label class="form-label small">After (seconds)</label>
                            <input type="number" class="form-control form-control-sm" 
                                   value="${assertion.action?.value || 5}" 
                                   min="1"
                                   onchange="updateAssertion('${assertion.id}', 'action.value', parseInt(this.value))">
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        const isRegex = assertion.type === 'regex';
        return `
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0"><i class="ri-checkbox-circle-line me-1"></i>Assertion #${index + 1}</h6>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeAssertion('${assertion.id}')">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small">Type</label>
                        <select class="form-select form-select-sm" 
                                onchange="updateAssertionType('${assertion.id}', this.value)">
                            <option value="json_path" ${assertion.type === 'json_path' ? 'selected' : ''}>JSON Path</option>
                            <option value="regex" ${assertion.type === 'regex' ? 'selected' : ''}>Regex</option>
                        </select>
                    </div>
                    ${isRegex ? `
                    <div class="col-md-4">
                        <label class="form-label small">Path (optional)</label>
                        <input type="text" class="form-control form-control-sm" 
                               value="${assertion.path || ''}" 
                               placeholder="$.status (or leave empty for full body)"
                               onchange="updateAssertion('${assertion.id}', 'path', this.value)">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small">Regex Pattern</label>
                        <input type="text" class="form-control form-control-sm" 
                               value="${assertion.pattern || assertion.value || ''}" 
                               placeholder="/^success$/i"
                               onchange="updateAssertion('${assertion.id}', 'pattern', this.value)">
                    </div>
                    ` : `
                    <div class="col-md-4">
                        <label class="form-label small">JSON Path</label>
                        <input type="text" class="form-control form-control-sm" 
                               value="${assertion.path || ''}" 
                               placeholder="$.data.user.active"
                               onchange="updateAssertion('${assertion.id}', 'path', this.value)">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Operator</label>
                        <select class="form-select form-select-sm" 
                                onchange="updateAssertion('${assertion.id}', 'operator', this.value)">
                            <option value="equals" ${(assertion.operator || 'equals') === 'equals' ? 'selected' : ''}>==</option>
                            <option value="!=" ${assertion.operator === '!=' ? 'selected' : ''}>!=</option>
                            <option value=">=" ${assertion.operator === '>=' ? 'selected' : ''}>>=</option>
                            <option value="<=" ${assertion.operator === '<=' ? 'selected' : ''}><=</option>
                            <option value=">" ${assertion.operator === '>' ? 'selected' : ''}>></option>
                            <option value="<" ${assertion.operator === '<' ? 'selected' : ''}><</option>
                            <option value="contains" ${assertion.operator === 'contains' ? 'selected' : ''}>contains</option>
                            <option value="exists" ${assertion.operator === 'exists' ? 'selected' : ''}>exists</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Expected Value</label>
                        <input type="text" class="form-control form-control-sm" 
                               value="${assertion.value || ''}" 
                               placeholder="true, 3, 'success'"
                               onchange="updateAssertion('${assertion.id}', 'value', this.value)">
                    </div>
                    `}
                </div>
            </div>
        `;
    }

    function updateAssertion(id, path, value) {
        const assertion = assertions.find(a => a.id === id);
        if (!assertion) return;

        const keys = path.split('.');
        let target = assertion;
        for (let i = 0; i < keys.length - 1; i++) {
            if (!target[keys[i]]) {
                target[keys[i]] = {};
            }
            target = target[keys[i]];
        }
        target[keys[keys.length - 1]] = value;

        // Try to parse value as number or boolean
        if (value === 'true') target[keys[keys.length - 1]] = true;
        else if (value === 'false') target[keys[keys.length - 1]] = false;
        else if (!isNaN(value) && value !== '') target[keys[keys.length - 1]] = parseFloat(value);

        updateHiddenInput();
        renderAssertions();
    }

    function updateAssertionType(id, type) {
        const assertion = assertions.find(a => a.id === id);
        if (assertion) {
            assertion.type = type;
            if (type === 'regex' && !assertion.pattern) {
                assertion.pattern = assertion.value || '';
            }
            updateHiddenInput();
            renderAssertions();
        }
    }

    function removeAssertion(id) {
        assertions = assertions.filter(a => a.id !== id);
        renderAssertions();
        updateHiddenInput();
    }

    function updateHiddenInput() {
        const json = JSON.stringify(assertions, null, 2);
        document.getElementById('response_assertions').value = json;
        document.getElementById('response_assertions_json').value = json;
    }

    // Stateful Monitoring
    let stepCounter = 0;
    let variableRuleCounter = 0;
    let monitoringSteps = [];
    let variableExtractionRules = [];

    // Load existing data if any
    document.addEventListener('DOMContentLoaded', function() {
        const existingSteps = document.getElementById('monitoring_steps').value;
        if (existingSteps) {
            try {
                monitoringSteps = JSON.parse(existingSteps);
                renderSteps();
            } catch (e) {
                console.error('Error parsing existing steps:', e);
            }
        }

        const existingRules = document.getElementById('variable_extraction_rules').value;
        if (existingRules) {
            try {
                variableExtractionRules = JSON.parse(existingRules);
                renderVariableRules();
            } catch (e) {
                console.error('Error parsing existing rules:', e);
            }
        }

        document.getElementById('add-step-btn').addEventListener('click', addStep);
        document.getElementById('add-variable-rule-btn').addEventListener('click', addVariableRule);
        
        // Initialize auth type fields visibility
        toggleAuthTypeFields();
    });

    function toggleStatefulMonitoring() {
        const enabled = document.getElementById('is_stateful').checked;
        document.getElementById('stateful_monitoring_section').style.display = enabled ? 'block' : 'none';
    }

    function toggleAutoAuth() {
        const enabled = document.getElementById('auto_auth_enabled').checked;
        document.getElementById('auto_auth_section').style.display = enabled ? 'block' : 'none';
        if (!enabled) {
            // Hide all auth type fields
            document.getElementById('oauth2_common_fields').style.display = 'none';
            document.getElementById('oauth2_password_fields').style.display = 'none';
            document.getElementById('jwt_fields').style.display = 'none';
            document.getElementById('apikey_rotation_fields').style.display = 'none';
        } else {
            toggleAuthTypeFields();
        }
    }

    function toggleAuthTypeFields() {
        const authType = document.getElementById('auto_auth_type').value;
        
        // Hide all fields first
        document.getElementById('oauth2_common_fields').style.display = 'none';
        document.getElementById('oauth2_password_fields').style.display = 'none';
        document.getElementById('jwt_fields').style.display = 'none';
        document.getElementById('apikey_rotation_fields').style.display = 'none';
        
        // Show relevant fields based on auth type
        if (authType === 'oauth2_client_credentials' || authType === 'oauth2_refresh_token') {
            document.getElementById('oauth2_common_fields').style.display = 'block';
        } else if (authType === 'oauth2_password') {
            document.getElementById('oauth2_common_fields').style.display = 'block';
            document.getElementById('oauth2_password_fields').style.display = 'block';
        } else if (authType === 'jwt') {
            document.getElementById('jwt_fields').style.display = 'block';
        } else if (authType === 'apikey_rotation') {
            document.getElementById('apikey_rotation_fields').style.display = 'block';
        }
    }

    function addStep() {
        const step = {
            id: 'step_' + (stepCounter++),
            step: monitoringSteps.length + 1,
            name: '',
            url: '',
            method: 'GET',
            headers: [],
            body: '',
            expected_status_code: 200,
            extract_variables: [],
            response_assertions: [],
            break_on_failure: false
        };
        monitoringSteps.push(step);
        renderSteps();
        updateStepsInput();
    }

    function removeStep(id) {
        monitoringSteps = monitoringSteps.filter(s => s.id !== id);
        // Renumber steps
        monitoringSteps.forEach((step, index) => {
            step.step = index + 1;
        });
        renderSteps();
        updateStepsInput();
    }

    function renderSteps() {
        const container = document.getElementById('monitoring-steps-list');
        container.innerHTML = '';

        if (monitoringSteps.length === 0) {
            container.innerHTML = '<p class="text-muted mb-0">No steps added. Click "Add Step" to create a multi-step flow.</p>';
            return;
        }

        monitoringSteps.forEach((step, index) => {
            const card = document.createElement('div');
            card.className = 'card border mb-3';
            card.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0"><i class="ri-play-circle-line me-1"></i>Step ${step.step}: ${step.name || 'Unnamed Step'}</h6>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeStep('${step.id}')">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small">Step Name</label>
                            <input type="text" class="form-control form-control-sm" 
                                   value="${step.name || ''}" 
                                   placeholder="Login, Get Orders, etc."
                                   onchange="updateStep('${step.id}', 'name', this.value)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">URL</label>
                            <input type="text" class="form-control form-control-sm" 
                                   value="${step.url || ''}" 
                                   placeholder="/api/login or https://api.example.com/login"
                                   onchange="updateStep('${step.id}', 'url', this.value)">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Method</label>
                            <select class="form-select form-select-sm" 
                                    onchange="updateStep('${step.id}', 'method', this.value)">
                                <option value="GET" ${step.method === 'GET' ? 'selected' : ''}>GET</option>
                                <option value="POST" ${step.method === 'POST' ? 'selected' : ''}>POST</option>
                                <option value="PUT" ${step.method === 'PUT' ? 'selected' : ''}>PUT</option>
                                <option value="PATCH" ${step.method === 'PATCH' ? 'selected' : ''}>PATCH</option>
                                <option value="DELETE" ${step.method === 'DELETE' ? 'selected' : ''}>DELETE</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Expected Status</label>
                            <input type="number" class="form-control form-control-sm" 
                                   value="${step.expected_status_code || 200}" 
                                   min="100" max="599"
                                   onchange="updateStep('${step.id}', 'expected_status_code', parseInt(this.value))">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small">Request Body (JSON, can use @{{variables}})</label>
                            <textarea class="form-control form-control-sm" rows="3" 
                                      placeholder='{"username": "user", "password": "pass"} or {"order_id": "@{{order_id}}"}'
                                      onchange="updateStep('${step.id}', 'body', this.value)">${step.body || ''}</textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small">Extract Variables (JSON Path)</label>
                            <div id="extract-vars-${step.id}">
                                ${renderExtractVariables(step.extract_variables || [], step.id)}
                            </div>
                            <button type="button" class="btn btn-xs btn-outline-info mt-1" onclick="addExtractVariable('${step.id}')">
                                <i class="ri-add-line me-1"></i>Add Variable
                            </button>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" 
                                       ${step.break_on_failure ? 'checked' : ''}
                                       onchange="updateStep('${step.id}', 'break_on_failure', this.checked)">
                                <label class="form-check-label small">Break on failure (stop execution if this step fails)</label>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(card);
        });
    }

    function renderExtractVariables(vars, stepId) {
        if (vars.length === 0) {
            return '<p class="text-muted small mb-0">No variables to extract</p>';
        }
        return vars.map((v, idx) => `
            <div class="d-flex gap-2 mb-2">
                <input type="text" class="form-control form-control-sm" 
                       value="${v.name || ''}" 
                       placeholder="Variable name (e.g., token)"
                       onchange="updateExtractVariable('${stepId}', ${idx}, 'name', this.value)">
                <input type="text" class="form-control form-control-sm" 
                       value="${v.path || ''}" 
                       placeholder="JSON Path (e.g., $.token)"
                       onchange="updateExtractVariable('${stepId}', ${idx}, 'path', this.value)">
                <button type="button" class="btn btn-xs btn-danger" onclick="removeExtractVariable('${stepId}', ${idx})">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        `).join('');
    }

    function addExtractVariable(stepId) {
        const step = monitoringSteps.find(s => s.id === stepId);
        if (step) {
            if (!step.extract_variables) {
                step.extract_variables = [];
            }
            step.extract_variables.push({ name: '', path: '' });
            renderSteps();
            updateStepsInput();
        }
    }

    function updateExtractVariable(stepId, index, field, value) {
        const step = monitoringSteps.find(s => s.id === stepId);
        if (step && step.extract_variables && step.extract_variables[index]) {
            step.extract_variables[index][field] = value;
            updateStepsInput();
        }
    }

    function removeExtractVariable(stepId, index) {
        const step = monitoringSteps.find(s => s.id === stepId);
        if (step && step.extract_variables) {
            step.extract_variables.splice(index, 1);
            renderSteps();
            updateStepsInput();
        }
    }

    function updateStep(id, field, value) {
        const step = monitoringSteps.find(s => s.id === id);
        if (step) {
            step[field] = value;
            updateStepsInput();
            if (field === 'name') {
                renderSteps(); // Re-render to update header
            }
        }
    }

    function updateStepsInput() {
        document.getElementById('monitoring_steps').value = JSON.stringify(monitoringSteps, null, 2);
    }

    function addVariableRule() {
        const rule = {
            id: 'rule_' + (variableRuleCounter++),
            name: '',
            path: '',
            step: null
        };
        variableExtractionRules.push(rule);
        renderVariableRules();
        updateVariableRulesInput();
    }

    function removeVariableRule(id) {
        variableExtractionRules = variableExtractionRules.filter(r => r.id !== id);
        renderVariableRules();
        updateVariableRulesInput();
    }

    function renderVariableRules() {
        const container = document.getElementById('variable-extraction-list');
        container.innerHTML = '';

        if (variableExtractionRules.length === 0) {
            container.innerHTML = '<p class="text-muted mb-0">No variable extraction rules. Variables can also be extracted per step.</p>';
            return;
        }

        variableExtractionRules.forEach((rule, index) => {
            const card = document.createElement('div');
            card.className = 'card border mb-2';
            card.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 small">Variable Rule #${index + 1}</h6>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeVariableRule('${rule.id}')">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small">Variable Name</label>
                            <input type="text" class="form-control form-control-sm" 
                                   value="${rule.name || ''}" 
                                   placeholder="token, order_id, etc."
                                   onchange="updateVariableRule('${rule.id}', 'name', this.value)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">JSON Path</label>
                            <input type="text" class="form-control form-control-sm" 
                                   value="${rule.path || ''}" 
                                   placeholder="$.token, $.data.order.id, etc."
                                   onchange="updateVariableRule('${rule.id}', 'path', this.value)">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Step (optional)</label>
                            <input type="number" class="form-control form-control-sm" 
                                   value="${rule.step || ''}" 
                                   placeholder="1, 2, etc."
                                   onchange="updateVariableRule('${rule.id}', 'step', this.value ? parseInt(this.value) : null)">
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(card);
        });
    }

    function updateVariableRule(id, field, value) {
        const rule = variableExtractionRules.find(r => r.id === id);
        if (rule) {
            rule[field] = value;
            updateVariableRulesInput();
        }
    }

    function updateVariableRulesInput() {
        document.getElementById('variable_extraction_rules').value = JSON.stringify(variableExtractionRules, null, 2);
    }

    // Schema Drift Detection Functions
    function toggleSchemaDrift() {
        const enabled = document.getElementById('schema_drift_enabled')?.checked;
        if (document.getElementById('schema_drift_section')) {
            document.getElementById('schema_drift_section').style.display = enabled ? 'block' : 'none';
        }
    }

    function toggleSchemaSource() {
        const sourceType = document.getElementById('schema_source_type')?.value;
        if (document.getElementById('schema_upload_fields')) {
            document.getElementById('schema_upload_fields').style.display = sourceType === 'upload' ? 'block' : 'none';
        }
        if (document.getElementById('schema_url_fields')) {
            document.getElementById('schema_url_fields').style.display = sourceType === 'url' ? 'block' : 'none';
        }
    }

    function handleSchemaFileUpload(input) {
        const file = input.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            const content = e.target.result;
            if (document.getElementById('schema_content')) {
                document.getElementById('schema_content').value = content;
            }
            
            // Preview schema
            try {
                const json = JSON.parse(content);
                if (document.getElementById('schema_preview_content')) {
                    document.getElementById('schema_preview_content').textContent = JSON.stringify(json, null, 2);
                    document.getElementById('schema_preview').style.display = 'block';
                }
            } catch (e) {
                // Try YAML or just show raw content
                if (document.getElementById('schema_preview_content')) {
                    document.getElementById('schema_preview_content').textContent = content.substring(0, 1000);
                    document.getElementById('schema_preview').style.display = 'block';
                }
            }
        };
        reader.readAsText(file);
    }
</script>
@endsection
