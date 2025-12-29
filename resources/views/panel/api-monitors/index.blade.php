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
        /* User link hover effect */
        a.text-primary.text-decoration-none:hover {
            text-decoration: underline !important;
        }
        a.text-primary.text-decoration-none:hover .fw-semibold {
            color: #0d6efd !important;
        }
    </style>
@endsection

@section('content')
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">API Monitoring</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Dashboard</a></li>
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
            <!-- Advanced Search Filter -->
            <div class="card custom-card mb-3">
                <div class="card-header">
                    <div class="card-title">
                        <i class="ri-search-line me-2"></i>Advanced Search
                    </div>
                    <div class="card-options">
                        <button type="button" class="btn btn-sm btn-light" id="toggle-filter">
                            <i class="ri-arrow-down-s-line" id="filter-icon"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body" id="filter-panel">
                    <form method="GET" action="{{ route('panel.api-monitors.index') }}" id="search-form">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="name" class="form-label">Monitor Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="{{ request('name') }}" placeholder="Search by name...">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="url" class="form-label">URL</label>
                                <input type="text" class="form-control" id="url" name="url" 
                                       value="{{ request('url') }}" placeholder="Search by URL...">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="up" {{ request('status') == 'up' ? 'selected' : '' }}>Up</option>
                                    <option value="down" {{ request('status') == 'down' ? 'selected' : '' }}>Down</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="request_method" class="form-label">Request Method</label>
                                <select class="form-select" id="request_method" name="request_method">
                                    <option value="">All Methods</option>
                                    <option value="GET" {{ request('request_method') == 'GET' ? 'selected' : '' }}>GET</option>
                                    <option value="POST" {{ request('request_method') == 'POST' ? 'selected' : '' }}>POST</option>
                                    <option value="PUT" {{ request('request_method') == 'PUT' ? 'selected' : '' }}>PUT</option>
                                    <option value="PATCH" {{ request('request_method') == 'PATCH' ? 'selected' : '' }}>PATCH</option>
                                    <option value="DELETE" {{ request('request_method') == 'DELETE' ? 'selected' : '' }}>DELETE</option>
                                    <option value="HEAD" {{ request('request_method') == 'HEAD' ? 'selected' : '' }}>HEAD</option>
                                    <option value="OPTIONS" {{ request('request_method') == 'OPTIONS' ? 'selected' : '' }}>OPTIONS</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="created_from" class="form-label">Created From</label>
                                <input type="date" class="form-control" id="created_from" name="created_from" 
                                       value="{{ request('created_from') }}">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="created_to" class="form-label">Created To</label>
                                <input type="date" class="form-control" id="created_to" name="created_to" 
                                       value="{{ request('created_to') }}">
                            </div>
                            
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-wave me-2">
                                    <i class="ri-search-line me-1"></i>Search
                                </button>
                                <a href="{{ route('panel.api-monitors.index') }}" class="btn btn-light btn-wave">
                                    <i class="ri-refresh-line me-1"></i>Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
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
                        <a href="{{ route('panel.api-monitors.create') }}" class="btn btn-primary btn-wave btn-sm">
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
                            <a href="{{ route('panel.api-monitors.create') }}" class="btn btn-primary btn-wave">
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
                                        <th>User</th>
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
                                                @if($monitor->user)
                                                    <a href="{{ route('panel.users.show', $monitor->user->uid) }}" class="text-primary text-decoration-none">
                                                        <span class="fw-semibold">{{ $monitor->user->name }}</span>
                                                        <br>
                                                        <small class="text-muted">{{ $monitor->user->email }}</small>
                                                    </a>
                                                @else
                                                    <span class="fw-semibold">N/A</span>
                                                @endif
                                            </td>
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
                                                    <a href="{{ route('panel.api-monitors.show', $monitor) }}" class="btn btn-sm btn-info btn-wave" title="View">
                                                        <i class="ri-eye-line"></i>
                                                    </a>
                                                    <a href="{{ route('panel.api-monitors.edit', $monitor) }}" class="btn btn-sm btn-primary btn-wave" title="Edit">
                                                        <i class="ri-edit-line"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger btn-wave delete-monitor-btn" 
                                                            data-monitor-id="{{ $monitor->id }}" 
                                                            data-monitor-name="{{ $monitor->name }}"
                                                            data-delete-url="{{ route('panel.api-monitors.destroy', $monitor) }}"
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
            // Toggle filter panel
            $('#toggle-filter').on('click', function() {
                $('#filter-panel').slideToggle();
                var icon = $('#filter-icon');
                if (icon.hasClass('ri-arrow-down-s-line')) {
                    icon.removeClass('ri-arrow-down-s-line').addClass('ri-arrow-up-s-line');
                } else {
                    icon.removeClass('ri-arrow-up-s-line').addClass('ri-arrow-down-s-line');
                }
            });
            
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
                        url: '{{ route("panel.api-monitors.bulk-action") }}',
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


