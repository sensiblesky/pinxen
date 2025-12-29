@extends('layouts.master')

@section('title', 'API Key Details - PingXeno')

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">API Key Details</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('api-keys.index') }}">Developer Options</a></li>
                <li class="breadcrumb-item active" aria-current="page">Details</li>
            </ol>
        </div>
    </div>
    <!-- End::page-header -->

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-12">
            @if($isNewKey && $fullKey)
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <h5 class="alert-heading"><i class="ri-alert-line me-2"></i>Important: Save Your API Key</h5>
                    <p class="mb-2">This is the only time you'll be able to see the full API key. Please copy it now and store it securely.</p>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="api-key-value" value="{{ $fullKey }}" readonly>
                        <button class="btn btn-primary" type="button" onclick="copyApiKey()">
                            <i class="ri-file-copy-line me-1"></i>Copy
                        </button>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">{{ $apiKey->name }}</div>
                    <div class="card-options">
                        <a href="{{ route('api-keys.edit', $apiKey) }}" class="btn btn-primary btn-wave btn-sm">
                            <i class="ri-edit-line me-1"></i>Edit
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <h6 class="mb-3">Basic Information</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td class="text-muted" width="40%">Name:</td>
                                    <td><strong>{{ $apiKey->name }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Key Prefix:</td>
                                    <td><code class="text-primary">{{ $apiKey->key_prefix }}...</code></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Status:</td>
                                    <td>
                                        @if($apiKey->isValid())
                                            <span class="badge bg-success-transparent text-success">
                                                <i class="ri-checkbox-circle-line me-1"></i>Active
                                            </span>
                                        @elseif($apiKey->expires_at && $apiKey->expires_at->isPast())
                                            <span class="badge bg-danger-transparent text-danger">
                                                <i class="ri-time-line me-1"></i>Expired
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-transparent text-secondary">
                                                <i class="ri-close-circle-line me-1"></i>Inactive
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @if($apiKey->description)
                                <tr>
                                    <td class="text-muted">Description:</td>
                                    <td>{{ $apiKey->description }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>

                        <div class="col-md-6 mb-4">
                            <h6 class="mb-3">Usage Information</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td class="text-muted" width="40%">Created:</td>
                                    <td>{{ $apiKey->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Last Used:</td>
                                    <td>
                                        @if($apiKey->last_used_at)
                                            {{ $apiKey->last_used_at->format('Y-m-d H:i:s') }}
                                            <small class="text-muted">({{ $apiKey->last_used_at->diffForHumans() }})</small>
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Expires At:</td>
                                    <td>
                                        @if($apiKey->expires_at)
                                            {{ $apiKey->expires_at->format('Y-m-d H:i:s') }}
                                            @if($apiKey->expires_at->isFuture())
                                                <small class="text-muted">({{ $apiKey->expires_at->diffForHumans() }})</small>
                                            @endif
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Rate Limit:</td>
                                    <td><strong>{{ $apiKey->rate_limit }}</strong> requests/minute</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-12 mb-4">
                            <h6 class="mb-3">Scopes (Permissions)</h6>
                            <div>
                                @if($apiKey->scopes && count($apiKey->scopes) > 0)
                                    @foreach($apiKey->scopes as $scope)
                                        <span class="badge bg-info-transparent text-info me-2 mb-2 fs-14">
                                            {{ $scope == '*' ? 'All (*)' : ucfirst($scope) }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-muted">No scopes assigned</span>
                                @endif
                            </div>
                        </div>

                        @if($apiKey->allowed_ips)
                        <div class="col-md-12 mb-4">
                            <h6 class="mb-3">Allowed IP Addresses</h6>
                            <div>
                                @foreach(explode(',', $apiKey->allowed_ips) as $ip)
                                    <span class="badge bg-secondary-transparent text-secondary me-2 mb-2">
                                        {{ trim($ip) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <div class="col-md-12">
                            <h6 class="mb-3">Actions</h6>
                            <div class="btn-list">
                                <a href="{{ route('api-keys.edit', $apiKey) }}" class="btn btn-primary btn-wave">
                                    <i class="ri-edit-line me-1"></i>Edit
                                </a>
                                <button type="button" 
                                        class="btn btn-warning btn-wave regenerate-key-btn" 
                                        data-uid="{{ $apiKey->uid }}"
                                        data-name="{{ $apiKey->name }}">
                                    <i class="ri-refresh-line me-1"></i>Regenerate Key
                                </button>
                                <button type="button" 
                                        class="btn {{ $apiKey->is_active ? 'btn-warning' : 'btn-success' }} btn-wave toggle-key-btn"
                                        data-uid="{{ $apiKey->uid }}"
                                        data-action="{{ $apiKey->is_active ? 'deactivate' : 'activate' }}"
                                        data-name="{{ $apiKey->name }}">
                                    <i class="ri-{{ $apiKey->is_active ? 'pause' : 'play' }}-line me-1"></i>
                                    {{ $apiKey->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                                <a href="{{ route('api-keys.index') }}" class="btn btn-light btn-wave">
                                    <i class="ri-arrow-left-line me-1"></i>Back to List
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function copyApiKey() {
        const apiKeyInput = document.getElementById('api-key-value');
        apiKeyInput.select();
        apiKeyInput.setSelectionRange(0, 99999); // For mobile devices
        
        try {
            document.execCommand('copy');
            // Show success message
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="ri-check-line me-1"></i>Copied!';
            btn.classList.add('btn-success');
            btn.classList.remove('btn-primary');
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-primary');
            }, 2000);
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Copy Failed',
                text: 'Failed to copy. Please select and copy manually.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Regenerate key confirmation
        const regenerateBtn = document.querySelector('.regenerate-key-btn');
        if (regenerateBtn) {
            regenerateBtn.addEventListener('click', function() {
                const uid = this.getAttribute('data-uid');
                const name = this.getAttribute('data-name');
                
                Swal.fire({
                    title: 'Are you sure?',
                    html: `You are about to regenerate the API key <strong>"${name}"</strong>.<br><br>This will generate a new key and <strong>invalidate the current one</strong>. Make sure to update all applications using this key.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, regenerate it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `/api-keys/${uid}/regenerate`;
                        
                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = '{{ csrf_token() }}';
                        form.appendChild(csrf);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        }

        // Toggle (activate/deactivate) confirmation
        const toggleBtn = document.querySelector('.toggle-key-btn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const uid = this.getAttribute('data-uid');
                const action = this.getAttribute('data-action');
                const name = this.getAttribute('data-name');
                const actionText = action === 'activate' ? 'activate' : 'deactivate';
                
                Swal.fire({
                    title: `Are you sure?`,
                    html: `You are about to <strong>${actionText}</strong> the API key <strong>"${name}"</strong>.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: action === 'activate' ? '#28a745' : '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: `Yes, ${actionText} it!`,
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `/api-keys/${uid}/toggle`;
                        
                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = '{{ csrf_token() }}';
                        form.appendChild(csrf);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        }
    });
</script>
@endsection

