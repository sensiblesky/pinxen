@extends('layouts.master')

@section('styles')
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css" rel="stylesheet">
    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.css')}}">
    <style>
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
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Domain Monitoring</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Domain Monitoring</li>
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

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Info Message about TLD Support -->
    <div class="alert alert-info alert-dismissible fade show d-flex align-items-center" role="alert">
        <div class="me-3">
            <i class="ri-information-line fs-20"></i>
        </div>
        <div class="flex-fill">
            <strong>Domain TLD Support Notice:</strong> Some domain TLDs (Top-Level Domains) are currently not supported, including specific country domains like <code>.go.tz</code>, <code>.ac.tz</code>, and other regional TLDs. We are actively working to expand our territories and add support for more TLDs. If you encounter issues with a specific domain, please contact support.
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <!-- Start::row-1 -->
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
                    <form method="GET" action="{{ route('panel.domain-monitors.index') }}" id="search-form">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="name" class="form-label">Monitor Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="{{ request('name') }}" placeholder="Search by name...">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="domain" class="form-label">Domain</label>
                                <input type="text" class="form-control" id="domain" name="domain" 
                                       value="{{ request('domain') }}" placeholder="Search by domain...">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="is_active" class="form-label">Status</label>
                                <select class="form-select" id="is_active" name="is_active">
                                    <option value="">All Status</option>
                                    <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="expiration_from" class="form-label">Expiration From</label>
                                <input type="date" class="form-control" id="expiration_from" name="expiration_from" 
                                       value="{{ request('expiration_from') }}">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="expiration_to" class="form-label">Expiration To</label>
                                <input type="date" class="form-control" id="expiration_to" name="expiration_to" 
                                       value="{{ request('expiration_to') }}">
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
                                <a href="{{ route('panel.domain-monitors.index') }}" class="btn btn-light btn-wave">
                                    <i class="ri-refresh-line me-1"></i>Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Domain Monitors</div>
                    <div class="card-options">
                        <a href="{{ route('panel.domain-monitors.create') }}" class="btn btn-primary btn-wave btn-sm">
                            <i class="ri-add-line me-1"></i>Add Monitor
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($monitors->isEmpty())
                        <div class="text-center py-5">
                            <div class="avatar avatar-xl avatar-rounded bg-secondary-transparent mb-3">
                                <i class="ri-calendar-close-line fs-36"></i>
                            </div>
                            <h5 class="mb-2">No Domain Monitors</h5>
                            <p class="text-muted mb-4">Get started by creating your first domain expiration monitor.</p>
                            <a href="{{ route('panel.domain-monitors.create') }}" class="btn btn-primary btn-wave">
                                <i class="ri-add-line me-1"></i>Create Monitor
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table id="monitors-table" class="table table-bordered text-nowrap w-100">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>User</th>
                                        <th>Name</th>
                                        <th>Domain</th>
                                        <th>Expiration Date</th>
                                        <th>Days Until Expiration</th>
                                        <th>Status</th>
                                        <th>Last Checked</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($monitors as $index => $monitor)
                                        <tr>
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
                                                <div class="d-flex align-items-center">
                                                    <span class="fw-semibold">{{ $monitor->name }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $monitor->domain }}</span>
                                            </td>
                                            <td>
                                                @if($monitor->expiration_date)
                                                    <span class="fw-semibold">{{ $monitor->expiration_date->format('M d, Y') }}</span>
                                                @else
                                                    <span class="text-muted">Not set</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($monitor->days_until_expiration !== null)
                                                    @if($monitor->days_until_expiration < 0)
                                                        <span class="badge bg-danger-transparent text-danger">
                                                            Expired {{ abs($monitor->days_until_expiration) }} days ago
                                                        </span>
                                                    @elseif($monitor->days_until_expiration <= 5)
                                                        <span class="badge bg-danger-transparent text-danger">
                                                            {{ $monitor->days_until_expiration }} days
                                                        </span>
                                                    @elseif($monitor->days_until_expiration <= 30)
                                                        <span class="badge bg-warning-transparent text-warning">
                                                            {{ $monitor->days_until_expiration }} days
                                                        </span>
                                                    @else
                                                        <span class="badge bg-success-transparent text-success">
                                                            {{ $monitor->days_until_expiration }} days
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">Unknown</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($monitor->is_active)
                                                    <span class="badge bg-success-transparent text-success">
                                                        <i class="ri-checkbox-circle-line me-1"></i>Active
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary-transparent text-secondary">
                                                        <i class="ri-pause-circle-line me-1"></i>Inactive
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($monitor->last_checked_at)
                                                    <span class="text-muted">{{ $monitor->last_checked_at->diffForHumans() }}</span>
                                                @else
                                                    <span class="text-muted">Never</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-list">
                                                    <a href="{{ route('panel.domain-monitors.show', $monitor->uid) }}" class="btn btn-sm btn-info btn-wave">
                                                        <i class="ri-eye-line"></i>
                                                    </a>
                                                    <a href="{{ route('panel.domain-monitors.edit', $monitor->uid) }}" class="btn btn-sm btn-primary btn-wave">
                                                        <i class="ri-pencil-line"></i>
                                                    </a>
                                                    <form id="delete-form-{{ $monitor->uid }}" action="{{ route('panel.domain-monitors.destroy', $monitor->uid) }}" method="POST" style="display: none;">
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>
                                                    <button type="button" class="btn btn-sm btn-danger btn-wave" 
                                                            onclick="confirmDelete('{{ $monitor->uid }}', '{{ $monitor->name }}')">
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
    <!-- End::row-1 -->
@endsection

@section('scripts')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>

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
            
            $('#monitors-table').DataTable({
                responsive: true,
                order: [[0, 'asc']],
                pageLength: 10,
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
                }
            });
        });

        function confirmDelete(uid, name) {
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete "${name}". This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + uid).submit();
                }
            });
        }
    </script>
@endsection

