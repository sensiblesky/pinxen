@extends('layouts.master')

@section('title', 'API Keys - PingXeno')

@section('styles')
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .swal2-container {
        z-index: 99999 !important;
        position: fixed !important;
    }
</style>
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">API Keys</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Developer Options</li>
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
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">API Keys Management</div>
                    <div class="card-options">
                        <a href="{{ route('api-keys.create') }}" class="btn btn-primary btn-wave btn-sm">
                            <i class="ri-add-line me-1"></i>Create API Key
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($apiKeys->count() > 0)
                        <div class="table-responsive">
                            <table id="api-keys-table" class="table table-bordered text-nowrap w-100">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Name</th>
                                        <th>Key Prefix</th>
                                        <th>Scopes</th>
                                        <th>Status</th>
                                        <th>Last Used</th>
                                        <th>Expires At</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($apiKeys as $apiKey)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h6 class="mb-0 fw-semibold">{{ $apiKey->name }}</h6>
                                                        @if($apiKey->description)
                                                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($apiKey->description, 50) }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <code class="text-primary">{{ $apiKey->key_prefix }}...</code>
                                            </td>
                                            <td>
                                                @if($apiKey->scopes && count($apiKey->scopes) > 0)
                                                    @foreach($apiKey->scopes as $scope)
                                                        <span class="badge bg-info-transparent text-info me-1 mb-1">{{ $scope == '*' ? 'All' : ucfirst($scope) }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">No scopes</span>
                                                @endif
                                            </td>
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
                                            <td>
                                                @if($apiKey->last_used_at)
                                                    <span class="text-muted" data-sort="{{ $apiKey->last_used_at->timestamp }}">{{ $apiKey->last_used_at->diffForHumans() }}</span>
                                                @else
                                                    <span class="text-muted" data-sort="0">Never</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($apiKey->expires_at)
                                                    <span class="text-muted" data-sort="{{ $apiKey->expires_at->timestamp }}">{{ $apiKey->expires_at->format('Y-m-d H:i') }}</span>
                                                @else
                                                    <span class="text-muted" data-sort="0">Never</span>
                                                @endif
                                            </td>
                                            <td data-sort="{{ $apiKey->created_at->timestamp }}">
                                                <span class="text-muted">{{ $apiKey->created_at->format('Y-m-d H:i') }}</span>
                                            </td>
                                            <td>
                                                <div class="btn-list">
                                                    <a href="{{ route('api-keys.show', $apiKey) }}" class="btn btn-sm btn-info btn-wave" title="View">
                                                        <i class="ri-eye-line"></i>
                                                    </a>
                                                    <a href="{{ route('api-keys.edit', $apiKey) }}" class="btn btn-sm btn-primary btn-wave" title="Edit">
                                                        <i class="ri-edit-line"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm {{ $apiKey->is_active ? 'btn-warning' : 'btn-success' }} btn-wave toggle-key-btn" 
                                                            title="{{ $apiKey->is_active ? 'Deactivate' : 'Activate' }}"
                                                            data-uid="{{ $apiKey->uid }}"
                                                            data-action="{{ $apiKey->is_active ? 'deactivate' : 'activate' }}"
                                                            data-name="{{ $apiKey->name }}">
                                                        <i class="ri-{{ $apiKey->is_active ? 'pause' : 'play' }}-line"></i>
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger btn-wave delete-key-btn" 
                                                            title="Delete"
                                                            data-uid="{{ $apiKey->uid }}"
                                                            data-name="{{ $apiKey->name }}">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="ri-key-line fs-48 text-muted mb-3 d-block"></i>
                            <h5 class="text-muted">No API Keys Found</h5>
                            <p class="text-muted">Create your first API key to start using the API.</p>
                            <a href="{{ route('api-keys.create') }}" class="btn btn-primary btn-wave">
                                <i class="ri-add-line me-1"></i>Create API Key
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTables
        const table = $('#api-keys-table').DataTable({
            order: [[6, 'desc']], // Sort by created date (newest first)
            pageLength: 10,
            responsive: true,
            columnDefs: [
                { orderable: false, targets: [7] }, // Actions column not sortable
                { type: 'num', targets: [4, 5, 6] } // Numeric sorting for dates
            ],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });

        // Delete confirmation - Use event delegation for dynamically added rows
        $(document).on('click', '.delete-key-btn', function() {
            const uid = $(this).data('uid');
            const name = $(this).data('name');
            
            Swal.fire({
                title: 'Are you sure?',
                html: `You are about to delete the API key <strong>"${name}"</strong>. This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create and submit form
                    const form = $('<form>', {
                        method: 'POST',
                        action: `/api-keys/${uid}`
                    });
                    
                    form.append($('<input>', {
                        type: 'hidden',
                        name: '_token',
                        value: '{{ csrf_token() }}'
                    }));
                    
                    form.append($('<input>', {
                        type: 'hidden',
                        name: '_method',
                        value: 'DELETE'
                    }));
                    
                    $('body').append(form);
                    form.submit();
                }
            });
        });

        // Toggle (activate/deactivate) confirmation
        $(document).on('click', '.toggle-key-btn', function() {
            const uid = $(this).data('uid');
            const action = $(this).data('action');
            const name = $(this).data('name');
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
                    // Create and submit form
                    const form = $('<form>', {
                        method: 'POST',
                        action: `/api-keys/${uid}/toggle`
                    });
                    
                    form.append($('<input>', {
                        type: 'hidden',
                        name: '_token',
                        value: '{{ csrf_token() }}'
                    }));
                    
                    $('body').append(form);
                    form.submit();
                }
            });
        });
    });
</script>
@endsection

