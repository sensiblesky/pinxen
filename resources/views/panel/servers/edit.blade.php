@extends('layouts.master')

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Edit Server</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('panel.servers.index') }}">Server Monitoring</a></li>
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
                    <form action="{{ route('panel.servers.update', $server) }}" method="POST">
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
                                <div class="input-group">
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
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createApiKeyModal">
                                        <i class="ri-add-line me-1"></i>Create New
                                    </button>
                                </div>
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
                            <a href="{{ route('panel.servers.index') }}" class="btn btn-light btn-wave">
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

    <!-- Create API Key Modal -->
    <div class="modal fade" id="createApiKeyModal" tabindex="-1" aria-labelledby="createApiKeyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createApiKeyModalLabel">Create New API Key</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="createApiKeyForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="api_key_name" class="form-label">API Key Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="api_key_name" name="name" required 
                                   placeholder="e.g., Server Monitoring Key">
                            <small class="text-muted">Give your API key a descriptive name</small>
                        </div>

                        <div class="mb-3">
                            <label for="api_key_description" class="form-label">Description</label>
                            <textarea class="form-control" id="api_key_description" name="description" rows="2" 
                                      placeholder="Optional description"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Scopes (Permissions) <span class="text-danger">*</span></label>
                            <div class="card border">
                                <div class="card-body">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="modal_scope_create" name="scopes[]" value="create" checked>
                                        <label class="form-check-label" for="modal_scope_create">
                                            <strong>Create</strong> - Required for server monitoring
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="modal_scope_view" name="scopes[]" value="view" checked>
                                        <label class="form-check-label" for="modal_scope_view">
                                            <strong>View</strong> - Read-only access
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="modal_scope_update" name="scopes[]" value="update">
                                        <label class="form-check-label" for="modal_scope_update">
                                            <strong>Update</strong> - Modify resources
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="modal_scope_all" name="scopes[]" value="*">
                                        <label class="form-check-label" for="modal_scope_all">
                                            <strong>All (*)</strong> - Full access
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted">At least one scope is required. "Create" is recommended for server monitoring.</small>
                        </div>

                        <div id="apiKeyFormError" class="alert alert-danger d-none"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i>Create API Key
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const createApiKeyForm = document.getElementById('createApiKeyForm');
        const apiKeySelect = document.getElementById('api_key_id');
        const modal = new bootstrap.Modal(document.getElementById('createApiKeyModal'));

        // Handle "All" scope checkbox
        const scopeAll = document.getElementById('modal_scope_all');
        const otherScopes = ['modal_scope_create', 'modal_scope_view', 'modal_scope_update'];
        
        scopeAll.addEventListener('change', function() {
            if (this.checked) {
                otherScopes.forEach(id => {
                    document.getElementById(id).checked = false;
                    document.getElementById(id).disabled = true;
                });
            } else {
                otherScopes.forEach(id => {
                    document.getElementById(id).disabled = false;
                });
            }
        });
        
        otherScopes.forEach(id => {
            document.getElementById(id).addEventListener('change', function() {
                if (this.checked) {
                    scopeAll.checked = false;
                }
            });
        });

        // Handle form submission
        createApiKeyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const errorDiv = document.getElementById('apiKeyFormError');
            errorDiv.classList.add('d-none');
            
            // Validate at least one scope is selected
            const checkedScopes = document.querySelectorAll('#createApiKeyForm input[name="scopes[]"]:checked');
            if (checkedScopes.length === 0) {
                errorDiv.textContent = 'Please select at least one scope.';
                errorDiv.classList.remove('d-none');
                return;
            }

            // Get form data
            const formData = new FormData(this);
            formData.append('_token', '{{ csrf_token() }}');

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="ri-loader-4-line me-1 spin"></i>Creating...';

            // Submit via AJAX
            fetch('{{ route("api-keys.store") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw data;
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Add new option to select
                    const option = document.createElement('option');
                    option.value = data.api_key.id;
                    option.textContent = data.api_key.name + ' (' + data.api_key.key_prefix + '...) - Scopes: ' + data.api_key.scopes.join(', ');
                    option.selected = true;
                    apiKeySelect.appendChild(option);

                    // Close modal and reset form
                    modal.hide();
                    createApiKeyForm.reset();
                    document.getElementById('modal_scope_create').checked = true;
                    document.getElementById('modal_scope_view').checked = true;

                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'API Key Created',
                        text: 'The API key has been created and selected.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    throw new Error(data.message || 'Failed to create API key');
                }
            })
            .catch(error => {
                let errorMessage = error.message || 'An error occurred while creating the API key.';
                if (error.errors) {
                    // Handle validation errors
                    const errorList = Object.values(error.errors).flat().join('<br>');
                    errorMessage = errorList;
                }
                errorDiv.innerHTML = errorMessage;
                errorDiv.classList.remove('d-none');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });

        // Reset form when modal is closed
        document.getElementById('createApiKeyModal').addEventListener('hidden.bs.modal', function() {
            createApiKeyForm.reset();
            document.getElementById('apiKeyFormError').classList.add('d-none');
            document.getElementById('modal_scope_create').checked = true;
            document.getElementById('modal_scope_view').checked = true;
            otherScopes.forEach(id => {
                document.getElementById(id).disabled = false;
            });
            scopeAll.checked = false;
        });
    });
</script>
<style>
    .spin {
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
</style>
@endsection

