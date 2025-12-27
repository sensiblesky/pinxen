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
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .swal2-popup {
            z-index: 99999 !important;
            position: relative !important;
            margin: auto !important;
        }
        .swal2-backdrop-show {
            background-color: rgba(0, 0, 0, 0.4) !important;
        }
    </style>
@endsection

@section('content')
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">API Monitoring</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">API Monitoring</li>
            </ol>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">API Monitors</div>
                    <div class="card-options">
                        <div class="btn-group me-2" id="bulk-actions-group" style="display: none;">
                            <button type="button" class="btn btn-sm btn-primary btn-wave dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="ri-checkbox-multiple-line me-1"></i>Bulk Actions
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="bulkAction('enable'); return false;"><i class="ri-checkbox-circle-line me-2"></i>Enable</a></li>
                                <li><a class="dropdown-item" href="#" onclick="bulkAction('disable'); return false;"><i class="ri-close-circle-line me-2"></i>Disable</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="bulkAction('delete'); return false;"><i class="ri-delete-bin-line me-2"></i>Delete</a></li>
                            </ul>
                        </div>
                        <a href="{{ route('api-monitors.create') }}" class="btn btn-primary btn-wave btn-sm">
                            <i class="ri-add-line me-1"></i>Add Monitor
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($monitors->isEmpty())
                        <div class="text-center py-5">
                            <div class="avatar avatar-xl avatar-rounded bg-secondary-transparent mb-3">
                                <i class="ri-code-s-slash-line fs-36"></i>
                            </div>
                            <h5 class="mb-2">No API Monitors</h5>
                            <p class="text-muted mb-4">Get started by creating your first API monitor.</p>
                            <a href="{{ route('api-monitors.create') }}" class="btn btn-primary btn-wave">
                                <i class="ri-add-line me-1"></i>Create Monitor
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table id="monitors-table" class="table table-bordered text-nowrap w-100">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="30">
                                            <input type="checkbox" id="select-all">
                                        </th>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>URL</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Last Check</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($monitors as $index => $monitor)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="monitor-checkbox" value="{{ $monitor->id }}">
                                            </td>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <span class="fw-semibold">{{ $monitor->name }}</span>
                                                @if(!$monitor->is_active)
                                                    <span class="badge bg-secondary-transparent text-secondary ms-2">Inactive</span>
                                                @endif
                                            </td>
                                            <td><code>{{ Str::limit($monitor->url, 50) }}</code></td>
                                            <td><span class="badge bg-info-transparent">{{ $monitor->request_method }}</span></td>
                                            <td>
                                                @if($monitor->status === 'up')
                                                    <span class="badge bg-success-transparent text-success">Up</span>
                                                @elseif($monitor->status === 'down')
                                                    <span class="badge bg-danger-transparent text-danger">Down</span>
                                                @else
                                                    <span class="badge bg-secondary-transparent text-secondary">Unknown</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($monitor->last_checked_at)
                                                    {{ $monitor->last_checked_at->diffForHumans() }}
                                                @else
                                                    <span class="text-muted">Never</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-list">
                                                    <a href="{{ route('api-monitors.show', $monitor) }}" class="btn btn-sm btn-info btn-wave" title="View">
                                                        <i class="ri-eye-line"></i>
                                                    </a>
                                                    <a href="{{ route('api-monitors.edit', $monitor) }}" class="btn btn-sm btn-primary btn-wave" title="Edit">
                                                        <i class="ri-edit-line"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger btn-wave delete-monitor-btn" 
                                                            data-monitor-id="{{ $monitor->id }}" 
                                                            data-monitor-name="{{ $monitor->name }}"
                                                            data-delete-url="{{ route('api-monitors.destroy', $monitor) }}"
                                                            title="Delete">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Wait for SweetAlert2 to be available
            if (typeof Swal === 'undefined') {
                console.error('SweetAlert2 is not loaded. Please refresh the page.');
                // Fallback: show alert
                alert('SweetAlert2 library is not loaded. Please refresh the page.');
            }
            $('#monitors-table').DataTable({
                order: [[1, 'asc']],
                pageLength: 10,
                columnDefs: [
                    { orderable: false, targets: [0, 7] }
                ]
            });

            // Select all checkbox
            $('#select-all').on('change', function() {
                $('.monitor-checkbox').prop('checked', $(this).prop('checked'));
                updateBulkActionsVisibility();
            });

            // Individual checkbox change
            $(document).on('change', '.monitor-checkbox', function() {
                const total = $('.monitor-checkbox').length;
                const checked = $('.monitor-checkbox:checked').length;
                $('#select-all').prop('checked', total === checked);
                updateBulkActionsVisibility();
            });

            function updateBulkActionsVisibility() {
                const checked = $('.monitor-checkbox:checked').length;
                if (checked > 0) {
                    $('#bulk-actions-group').show();
                } else {
                    $('#bulk-actions-group').hide();
                }
            }

            // Delete monitor with SweetAlert confirmation
            $(document).on('click', '.delete-monitor-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const btn = $(this);
                const monitorId = btn.data('monitor-id');
                const monitorName = btn.data('monitor-name');
                const deleteUrl = btn.data('delete-url');

                if (typeof Swal === 'undefined') {
                    // Fallback to native confirm
                    if (confirm(`Are you sure you want to delete "${monitorName}"? This action cannot be undone!`)) {
                        const form = $('<form>', {
                            'method': 'POST',
                            'action': deleteUrl
                        });
                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_token',
                            'value': $('meta[name="csrf-token"]').attr('content')
                        }));
                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_method',
                            'value': 'DELETE'
                        }));
                        $('body').append(form);
                        form.submit();
                    }
                    return;
                }

                Swal.fire({
                    title: 'Are you sure?',
                    html: `You are about to delete <strong>"${monitorName}"</strong>.<br><br>This action cannot be undone!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    allowOutsideClick: false,
                    allowEscapeKey: true,
                    focusConfirm: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Create a form and submit it
                        const form = $('<form>', {
                            'method': 'POST',
                            'action': deleteUrl
                        });
                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_token',
                            'value': $('meta[name="csrf-token"]').attr('content')
                        }));
                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_method',
                            'value': 'DELETE'
                        }));
                        $('body').append(form);
                        form.submit();
                    }
                });
            });
        });

        function bulkAction(action) {
            const checked = $('.monitor-checkbox:checked');
            if (checked.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Selection',
                    text: 'Please select at least one monitor.'
                });
                return;
            }

            const actionText = action === 'delete' ? 'delete' : (action === 'enable' ? 'enable' : 'disable');
            const confirmText = action === 'delete' 
                ? 'Are you sure you want to delete the selected monitors? This action cannot be undone.'
                : `Are you sure you want to ${actionText} the selected monitors?`;

            Swal.fire({
                title: 'Confirm Action',
                text: confirmText,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: action === 'delete' ? '#d33' : '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, ' + actionText + ' them!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const monitorIds = checked.map(function() {
                        return $(this).val();
                    }).get();

                    $.ajax({
                        url: '{{ route("api-monitors.bulk-action") }}',
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            action: action,
                            monitor_ids: monitorIds
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message || 'Action completed successfully.'
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message || 'An error occurred.'
                            });
                        }
                    });
                }
            });
        }
    </script>
@endsection


