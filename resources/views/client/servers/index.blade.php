@extends('layouts.master')

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
            <h1 class="page-title fw-medium fs-18 mb-0">Server Monitoring</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Server Monitoring</li>
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
                    <div class="card-title">Servers Management</div>
                    <div class="card-options">
                        <a href="{{ route('servers.create') }}" class="btn btn-primary btn-wave btn-sm">
                            <i class="ri-add-line me-1"></i>Add Server
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($servers->count() > 0)
                        <div class="table-responsive">
                            <table id="servers-table" class="table table-bordered text-nowrap w-100">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Name</th>
                                        <th>Hostname</th>
                                        <th>Status</th>
                                        <th>OS</th>
                                        <th>API Key</th>
                                        <th>Last Seen</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($servers as $server)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h6 class="mb-0 fw-semibold">{{ $server->name }}</h6>
                                                        @if($server->description)
                                                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($server->description, 50) }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($server->hostname)
                                                    <code class="text-primary">{{ $server->hostname }}</code>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $server->getStatusBadgeClass() }}">
                                                    <i class="ri-{{ $server->isOnline() ? 'checkbox-circle-line' : 'close-circle-line' }} me-1"></i>
                                                    {{ $server->getStatusText() }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($server->os_type)
                                                    <span class="badge bg-info-transparent text-info">
                                                        {{ ucfirst($server->os_type) }}
                                                        @if($server->os_version)
                                                            {{ $server->os_version }}
                                                        @endif
                                                    </span>
                                                @else
                                                    <span class="text-muted">Unknown</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($server->apiKey)
                                                    <code class="text-secondary">{{ $server->apiKey->key_prefix }}...</code>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td data-sort="{{ $server->last_seen_at ? $server->last_seen_at->timestamp : 0 }}">
                                                @if($server->last_seen_at)
                                                    <span class="text-muted">{{ $server->last_seen_at->diffForHumans() }}</span>
                                                @else
                                                    <span class="text-muted">Never</span>
                                                @endif
                                            </td>
                                            <td data-sort="{{ $server->created_at->timestamp }}">
                                                <span class="text-muted">{{ $server->created_at->format('Y-m-d H:i') }}</span>
                                            </td>
                                            <td>
                                                <div class="btn-list">
                                                    <a href="{{ route('servers.show', $server) }}" class="btn btn-sm btn-info btn-wave" title="View">
                                                        <i class="ri-eye-line"></i>
                                                    </a>
                                                    <a href="{{ route('servers.edit', $server) }}" class="btn btn-sm btn-primary btn-wave" title="Edit">
                                                        <i class="ri-edit-line"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger btn-wave delete-server-btn" 
                                                            title="Delete"
                                                            data-uid="{{ $server->uid }}"
                                                            data-name="{{ $server->name }}">
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
                            <i class="ri-server-line fs-48 text-muted mb-3 d-block"></i>
                            <h5 class="text-muted">No Servers Found</h5>
                            <p class="text-muted">Add your first server to start monitoring its performance.</p>
                            <a href="{{ route('servers.create') }}" class="btn btn-primary btn-wave">
                                <i class="ri-add-line me-1"></i>Add Server
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
        @if($servers->count() > 0)
        // Initialize DataTables
        const table = $('#servers-table').DataTable({
            order: [[6, 'desc']], // Sort by created date (newest first)
            pageLength: 10,
            responsive: true,
            columnDefs: [
                { orderable: false, targets: [7] }, // Actions column not sortable
                { type: 'num', targets: [5, 6] } // Numeric sorting for dates
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
        @endif

        // Delete confirmation
        $(document).on('click', '.delete-server-btn', function() {
            const uid = $(this).data('uid');
            const name = $(this).data('name');
            
            Swal.fire({
                title: 'Are you sure?',
                html: `You are about to delete the server <strong>"${name}"</strong>. This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = $('<form>', {
                        method: 'POST',
                        action: `/servers/${uid}`
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
    });
</script>
@endsection

