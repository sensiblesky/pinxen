@extends('layouts.master')

@section('styles')
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Add External API</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                <li class="breadcrumb-item"><a href="{{ route('panel.external-apis.index') }}">External API's</a></li>
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
                    <div class="card-title">API Information</div>
                </div>
                <div class="card-body">
                    <form action="{{ route('panel.external-apis.store') }}" method="POST" id="api-form">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">API Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" 
                                       placeholder="e.g., WHOIS API" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="provider" class="form-label">Provider <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('provider') is-invalid @enderror" 
                                       id="provider" name="provider" value="{{ old('provider') }}" 
                                       placeholder="e.g., apilayer" required>
                                @error('provider')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="service_type" class="form-label">Service Type <span class="text-danger">*</span></label>
                                <select class="form-control @error('service_type') is-invalid @enderror" 
                                        id="service_type" name="service_type" required>
                                    <option value="">Select Service Type</option>
                                    <option value="whois" {{ old('service_type') == 'whois' ? 'selected' : '' }}>WHOIS</option>
                                    <option value="dns" {{ old('service_type') == 'dns' ? 'selected' : '' }}>DNS</option>
                                    <option value="ssl" {{ old('service_type') == 'ssl' ? 'selected' : '' }}>SSL</option>
                                    <option value="geolocation" {{ old('service_type') == 'geolocation' ? 'selected' : '' }}>Geolocation</option>
                                    <option value="other" {{ old('service_type') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('service_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="base_url" class="form-label">Base URL</label>
                                <input type="url" class="form-control @error('base_url') is-invalid @enderror" 
                                       id="base_url" name="base_url" value="{{ old('base_url', 'https://api.apilayer.com') }}" 
                                       placeholder="https://api.apilayer.com">
                                @error('base_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="endpoint" class="form-label">Endpoint</label>
                                <input type="text" class="form-control @error('endpoint') is-invalid @enderror" 
                                       id="endpoint" name="endpoint" value="{{ old('endpoint', '/whois/query') }}" 
                                       placeholder="/whois/query">
                                @error('endpoint')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="api_key" class="form-label">API Key</label>
                                <input type="text" class="form-control @error('api_key') is-invalid @enderror" 
                                       id="api_key" name="api_key" value="{{ old('api_key') }}" 
                                       placeholder="Enter API key">
                                @error('api_key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">API key will be encrypted when stored</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="api_secret" class="form-label">API Secret</label>
                                <input type="text" class="form-control @error('api_secret') is-invalid @enderror" 
                                       id="api_secret" name="api_secret" value="{{ old('api_secret') }}" 
                                       placeholder="Enter API secret (if required)">
                                @error('api_secret')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">API secret will be encrypted when stored</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="headers" class="form-label">Headers (JSON)</label>
                                <textarea class="form-control @error('headers') is-invalid @enderror" 
                                          id="headers" name="headers" rows="4" 
                                          placeholder='{"apikey": "YOUR_API_KEY"}'>{{ old('headers', '{"apikey": ""}') }}</textarea>
                                @error('headers')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Enter headers as JSON object. For apilayer WHOIS: {"apikey": "YOUR_API_KEY"}</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="rate_limit" class="form-label">Rate Limit (per minute)</label>
                                <input type="number" class="form-control @error('rate_limit') is-invalid @enderror" 
                                       id="rate_limit" name="rate_limit" value="{{ old('rate_limit') }}" 
                                       min="1" placeholder="e.g., 100">
                                @error('rate_limit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" 
                                           id="is_active" name="is_active" value="1" 
                                           {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        <strong>Active</strong>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" name="description" rows="3" 
                                          placeholder="Optional description">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('panel.external-apis.index') }}" class="btn btn-light btn-wave">
                                <i class="ri-close-line me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-wave">
                                <i class="ri-save-line me-1"></i>Create API
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
        // Auto-fill headers with API key when API key is entered
        document.getElementById('api_key').addEventListener('input', function() {
            const apiKey = this.value;
            const headersField = document.getElementById('headers');
            if (apiKey && headersField.value.includes('"apikey"')) {
                try {
                    const headers = JSON.parse(headersField.value);
                    headers.apikey = apiKey;
                    headersField.value = JSON.stringify(headers, null, 2);
                } catch (e) {
                    // If JSON is invalid, create new one
                    headersField.value = JSON.stringify({apikey: apiKey}, null, 2);
                }
            }
        });
    </script>
@endsection





