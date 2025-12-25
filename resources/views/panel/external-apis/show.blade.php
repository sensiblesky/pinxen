@extends('layouts.master')

@section('styles')
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">External API Details</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                <li class="breadcrumb-item"><a href="{{ route('panel.external-apis.index') }}">External API's</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $externalApi->name }}</li>
            </ol>
        </div>
    </div>
    <!-- End::page-header -->

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">API Information</div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">API Name</label>
                            <p class="fw-semibold">{{ $externalApi->name }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Provider</label>
                            <p class="fw-semibold">{{ $externalApi->provider }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Service Type</label>
                            <p>
                                <span class="badge bg-info-transparent text-info">{{ $externalApi->service_type }}</span>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Status</label>
                            <p>
                                @if($externalApi->is_active)
                                    <span class="badge bg-success-transparent text-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-transparent text-secondary">Inactive</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label text-muted">Base URL</label>
                            <p class="fw-semibold">{{ $externalApi->base_url ?? '-' }}</p>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label text-muted">Endpoint</label>
                            <p class="fw-semibold">{{ $externalApi->endpoint ?? '-' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">API Key</label>
                            <p class="fw-semibold">
                                @if($externalApi->api_key)
                                    <code>{{ substr($externalApi->api_key, 0, 8) }}****</code>
                                @else
                                    <span class="text-muted">Not set</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Rate Limit</label>
                            <p class="fw-semibold">{{ $externalApi->rate_limit ? $externalApi->rate_limit . ' requests/minute' : '-' }}</p>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label text-muted">Headers</label>
                            <pre class="bg-light p-3 rounded">{{ $externalApi->headers ? json_encode($externalApi->headers, JSON_PRETTY_PRINT) : '-' }}</pre>
                        </div>
                        @if($externalApi->description)
                        <div class="col-md-12 mb-3">
                            <label class="form-label text-muted">Description</label>
                            <p>{{ $externalApi->description }}</p>
                        </div>
                        @endif
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Created At</label>
                            <p class="fw-semibold">{{ $externalApi->created_at->format('M d, Y H:i') }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Updated At</label>
                            <p class="fw-semibold">{{ $externalApi->updated_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Quick Actions</div>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('panel.external-apis.edit', $externalApi->id) }}" class="btn btn-primary btn-wave">
                            <i class="ri-pencil-line me-1"></i>Edit API
                        </a>
                        <form action="{{ route('panel.external-apis.destroy', $externalApi->id) }}" method="POST" id="delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="btn btn-danger btn-wave w-100" onclick="confirmDelete()">
                                <i class="ri-delete-bin-line me-1"></i>Delete API
                            </button>
                        </form>
                        <a href="{{ route('panel.external-apis.index') }}" class="btn btn-light btn-wave">
                            <i class="ri-arrow-left-line me-1"></i>Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->
@endsection

@section('scripts')
    <!-- SweetAlert2 JS -->
    <script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>

    <script>
        function confirmDelete() {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You are about to delete this external API. This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form').submit();
                }
            });
        }
    </script>
@endsection




