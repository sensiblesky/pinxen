@extends('layouts.master')

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Edit Server</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('servers.index') }}">Server Monitoring</a></li>
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
                    <div class="card-title">Edit Server: {{ $server->name }}</div>
                </div>
                <div class="card-body">
                    <form action="{{ route('servers.update', $server) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <!-- Basic Information -->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="name" class="form-label">Server Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $server->name) }}" 
                                       placeholder="e.g., Production Server, Web Server 01" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="api_key_id" class="form-label">API Key <span class="text-danger">*</span></label>
                                <select class="form-select @error('api_key_id') is-invalid @enderror" 
                                        id="api_key_id" name="api_key_id" required>
                                    <option value="">Select an API Key</option>
                                    @foreach($apiKeys as $apiKey)
                                        <option value="{{ $apiKey->id }}" {{ old('api_key_id', $server->api_key_id) == $apiKey->id ? 'selected' : '' }}>
                                            {{ $apiKey->name }} ({{ $apiKey->key_prefix }}...)
                                            @if($apiKey->scopes)
                                                - Scopes: {{ implode(', ', $apiKey->scopes) }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('api_key_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" name="description" rows="3" 
                                          placeholder="Optional description for this server">{{ old('description', $server->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Server Details -->
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <div class="card-title mb-0">
                                            <i class="ri-information-line me-2 text-primary"></i>Server Details
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="hostname" class="form-label">Hostname</label>
                                                <input type="text" class="form-control @error('hostname') is-invalid @enderror" 
                                                       id="hostname" name="hostname" 
                                                       value="{{ old('hostname', $server->hostname) }}" 
                                                       placeholder="e.g., server01.example.com">
                                                @error('hostname')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="ip_address" class="form-label">IP Address</label>
                                                <input type="text" class="form-control @error('ip_address') is-invalid @enderror" 
                                                       id="ip_address" name="ip_address" 
                                                       value="{{ old('ip_address', $server->ip_address) }}" 
                                                       placeholder="e.g., 192.168.1.100">
                                                @error('ip_address')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="os_type" class="form-label">OS Type</label>
                                                <select class="form-select @error('os_type') is-invalid @enderror" 
                                                        id="os_type" name="os_type">
                                                    <option value="">Select OS Type</option>
                                                    <option value="linux" {{ old('os_type', $server->os_type) == 'linux' ? 'selected' : '' }}>Linux</option>
                                                    <option value="windows" {{ old('os_type', $server->os_type) == 'windows' ? 'selected' : '' }}>Windows</option>
                                                    <option value="macos" {{ old('os_type', $server->os_type) == 'macos' ? 'selected' : '' }}>macOS</option>
                                                    <option value="freebsd" {{ old('os_type', $server->os_type) == 'freebsd' ? 'selected' : '' }}>FreeBSD</option>
                                                    <option value="other" {{ old('os_type', $server->os_type) == 'other' ? 'selected' : '' }}>Other</option>
                                                </select>
                                                @error('os_type')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="os_version" class="form-label">OS Version</label>
                                                <input type="text" class="form-control @error('os_version') is-invalid @enderror" 
                                                       id="os_version" name="os_version" 
                                                       value="{{ old('os_version', $server->os_version) }}" 
                                                       placeholder="e.g., Ubuntu 22.04, Windows Server 2022">
                                                @error('os_version')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <label for="location" class="form-label">Location</label>
                                                <input type="text" class="form-control @error('location') is-invalid @enderror" 
                                                       id="location" name="location" 
                                                       value="{{ old('location', $server->location) }}" 
                                                       placeholder="e.g., Data Center A, AWS us-east-1">
                                                @error('location')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="is_active" name="is_active" value="1"
                                                           {{ old('is_active', $server->is_active) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="is_active">
                                                        <strong>Active</strong> - Enable monitoring for this server
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Alert Thresholds -->
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="card border border-warning">
                                    <div class="card-header bg-warning-transparent">
                                        <div class="card-title mb-0">
                                            <i class="ri-alert-line me-2 text-warning"></i>Alert Thresholds (Optional)
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small mb-3">Set usage thresholds to receive alerts when CPU, Memory, or Disk usage exceeds these percentages. Leave empty to disable alerts for that metric.</p>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="cpu_threshold" class="form-label">CPU Usage Threshold (%)</label>
                                                <input type="number" class="form-control @error('cpu_threshold') is-invalid @enderror" 
                                                       id="cpu_threshold" name="cpu_threshold" 
                                                       value="{{ old('cpu_threshold', $server->cpu_threshold) }}" 
                                                       min="0" max="100" step="0.01"
                                                       placeholder="e.g., 80">
                                                @error('cpu_threshold')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="text-muted">Alert when CPU usage exceeds this percentage</small>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="memory_threshold" class="form-label">Memory Usage Threshold (%)</label>
                                                <input type="number" class="form-control @error('memory_threshold') is-invalid @enderror" 
                                                       id="memory_threshold" name="memory_threshold" 
                                                       value="{{ old('memory_threshold', $server->memory_threshold) }}" 
                                                       min="0" max="100" step="0.01"
                                                       placeholder="e.g., 85">
                                                @error('memory_threshold')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="text-muted">Alert when Memory usage exceeds this percentage</small>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="disk_threshold" class="form-label">Disk Usage Threshold (%)</label>
                                                <input type="number" class="form-control @error('disk_threshold') is-invalid @enderror" 
                                                       id="disk_threshold" name="disk_threshold" 
                                                       value="{{ old('disk_threshold', $server->disk_threshold) }}" 
                                                       min="0" max="100" step="0.01"
                                                       placeholder="e.g., 90">
                                                @error('disk_threshold')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="text-muted">Alert when Disk usage exceeds this percentage</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Detection Thresholds -->
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="card border border-info">
                                    <div class="card-header bg-info-transparent">
                                        <div class="card-title mb-0">
                                            <i class="ri-pulse-line me-2 text-info"></i>Status Detection Thresholds (Optional)
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small mb-3">Configure when the server is considered online, in warning, or offline based on when it was last seen. Leave empty to use system defaults.</p>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="online_threshold_minutes" class="form-label">Online Threshold (minutes)</label>
                                                <input type="number" class="form-control @error('online_threshold_minutes') is-invalid @enderror" 
                                                       id="online_threshold_minutes" name="online_threshold_minutes" 
                                                       value="{{ old('online_threshold_minutes', $server->online_threshold_minutes) }}" 
                                                       min="1" max="1440"
                                                       placeholder="5 (default)">
                                                @error('online_threshold_minutes')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="text-muted">Server is considered <strong>online</strong> if seen within this many minutes (default: 5 minutes)</small>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="warning_threshold_minutes" class="form-label">Warning Threshold (minutes)</label>
                                                <input type="number" class="form-control @error('warning_threshold_minutes') is-invalid @enderror" 
                                                       id="warning_threshold_minutes" name="warning_threshold_minutes" 
                                                       value="{{ old('warning_threshold_minutes', $server->warning_threshold_minutes) }}" 
                                                       min="1" max="1440"
                                                       placeholder="60 (default)">
                                                @error('warning_threshold_minutes')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="text-muted">Server shows <strong>warning</strong> status if seen within this many minutes but exceeds online threshold (default: 60 minutes)</small>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="offline_threshold_minutes" class="form-label">Offline Threshold (minutes)</label>
                                                <input type="number" class="form-control @error('offline_threshold_minutes') is-invalid @enderror" 
                                                       id="offline_threshold_minutes" name="offline_threshold_minutes" 
                                                       value="{{ old('offline_threshold_minutes', $server->offline_threshold_minutes) }}" 
                                                       min="1" max="1440"
                                                       placeholder="120 (default)">
                                                @error('offline_threshold_minutes')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="text-muted">Server is considered <strong>offline</strong> if not seen within this many minutes (default: 120 minutes)</small>
                                            </div>
                                        </div>
                                        <div class="alert alert-info mt-3 mb-0">
                                            <small>
                                                <strong>How it works:</strong><br>
                                                • When the agent sends stats, <code>last_seen_at</code> is updated<br>
                                                • If <code>last_seen_at</code> is within <strong>Online Threshold</strong> → Server is <span class="badge bg-success">Online</span><br>
                                                • If <code>last_seen_at</code> is within <strong>Warning Threshold</strong> → Server is <span class="badge bg-warning">Warning</span><br>
                                                • If <code>last_seen_at</code> exceeds <strong>Offline Threshold</strong> → Server is <span class="badge bg-danger">Offline</span>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('servers.index') }}" class="btn btn-light btn-wave">
                                <i class="ri-close-line me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-wave">
                                <i class="ri-save-line me-1"></i>Update Server
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->
@endsection

