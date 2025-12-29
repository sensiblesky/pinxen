@extends('layouts.master')

@section('title', 'Edit API Key - PingXeno')

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Edit API Key</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('api-keys.index') }}">Developer Options</a></li>
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
                    <div class="card-title">Edit API Key: {{ $apiKey->name }}</div>
                </div>
                <div class="card-body">
                    <form action="{{ route('api-keys.update', $apiKey) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <!-- Basic Information -->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="name" class="form-label">API Key Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $apiKey->name) }}" 
                                       placeholder="e.g., Production API, Development Key" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" name="description" rows="3" 
                                          placeholder="Optional description for this API key">{{ old('description', $apiKey->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Scopes (Permissions) -->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Scopes (Permissions) <span class="text-danger">*</span></label>
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="scope_view" name="scopes[]" value="view"
                                                           {{ in_array('view', old('scopes', $apiKey->scopes ?? [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="scope_view">
                                                        <strong>View</strong> - Read-only access
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="scope_create" name="scopes[]" value="create"
                                                           {{ in_array('create', old('scopes', $apiKey->scopes ?? [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="scope_create">
                                                        <strong>Create</strong> - Create resources
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="scope_update" name="scopes[]" value="update"
                                                           {{ in_array('update', old('scopes', $apiKey->scopes ?? [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="scope_update">
                                                        <strong>Update</strong> - Modify resources
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="scope_delete" name="scopes[]" value="delete"
                                                           {{ in_array('delete', old('scopes', $apiKey->scopes ?? [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="scope_delete">
                                                        <strong>Delete</strong> - Remove resources
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-12 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="scope_all" name="scopes[]" value="*"
                                                           {{ in_array('*', old('scopes', $apiKey->scopes ?? [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="scope_all">
                                                        <strong>All (*)</strong> - Full access
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @error('scopes')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Advanced Settings -->
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <div class="card-title mb-0">
                                            <i class="ri-settings-3-line me-2 text-primary"></i>Advanced Settings
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="expires_at" class="form-label">Expiration Date</label>
                                                <input type="datetime-local" class="form-control @error('expires_at') is-invalid @enderror" 
                                                       id="expires_at" name="expires_at" 
                                                       value="{{ old('expires_at', $apiKey->expires_at ? $apiKey->expires_at->format('Y-m-d\TH:i') : '') }}">
                                                @error('expires_at')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="text-muted">Leave empty for no expiration</small>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="rate_limit" class="form-label">Rate Limit (requests/minute)</label>
                                                <input type="number" class="form-control @error('rate_limit') is-invalid @enderror" 
                                                       id="rate_limit" name="rate_limit" 
                                                       value="{{ old('rate_limit', $apiKey->rate_limit) }}" min="1" max="10000">
                                                @error('rate_limit')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <label for="allowed_ips" class="form-label">Allowed IP Addresses</label>
                                                <input type="text" class="form-control @error('allowed_ips') is-invalid @enderror" 
                                                       id="allowed_ips" name="allowed_ips" 
                                                       value="{{ old('allowed_ips', $apiKey->allowed_ips) }}" 
                                                       placeholder="192.168.1.1, 10.0.0.1">
                                                @error('allowed_ips')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="text-muted">Comma-separated list. Leave empty to allow all IPs.</small>
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="is_active" name="is_active" value="1"
                                                           {{ old('is_active', $apiKey->is_active) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="is_active">
                                                        <strong>Active</strong> - Enable this API key
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('api-keys.index') }}" class="btn btn-light btn-wave">
                                <i class="ri-close-line me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-wave">
                                <i class="ri-save-line me-1"></i>Update API Key
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
        // Handle "All" scope checkbox
        const scopeAll = document.getElementById('scope_all');
        const otherScopes = ['scope_view', 'scope_create', 'scope_update', 'scope_delete'];
        
        // Set initial state
        if (scopeAll.checked) {
            otherScopes.forEach(id => {
                document.getElementById(id).disabled = true;
            });
        }
        
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
        
        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const checkedScopes = document.querySelectorAll('input[name="scopes[]"]:checked');
            if (checkedScopes.length === 0) {
                e.preventDefault();
                alert('Please select at least one scope.');
                return false;
            }
        });
    });
</script>
@endsection

