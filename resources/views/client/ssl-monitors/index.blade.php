@extends('layouts.master')

@section('styles')
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css" rel="stylesheet">
    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.css')}}">
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">SSL Monitoring</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">SSL Monitoring</li>
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

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">SSL Monitors</div>
                    <div class="card-options">
                        <a href="{{ route('ssl-monitors.create') }}" class="btn btn-primary btn-wave btn-sm">
                            <i class="ri-add-line me-1"></i>Add Monitor
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($monitors->isEmpty())
                        <div class="text-center py-5">
                            <div class="avatar avatar-xl avatar-rounded bg-secondary-transparent mb-3">
                                <i class="ri-shield-check-line fs-36"></i>
                            </div>
                            <h5 class="mb-2">No SSL Monitors</h5>
                            <p class="text-muted mb-4">Get started by creating your first SSL certificate monitor.</p>
                            <a href="{{ route('ssl-monitors.create') }}" class="btn btn-primary btn-wave">
                                <i class="ri-add-line me-1"></i>Create Monitor
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table id="monitors-table" class="table table-bordered text-nowrap w-100">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Domain</th>
                                        <th>Status</th>
                                        <th>Expiration Date</th>
                                        <th>Days Until Expiration</th>
                                        <th>Check Interval</th>
                                        <th>Last Checked</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($monitors as $index => $monitor)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="fw-semibold">{{ $monitor->name }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $monitor->domain }}</span>
                                            </td>
                                            <td>
                                                @if($monitor->status === 'valid')
                                                    <span class="badge bg-success-transparent text-success">
                                                        <i class="ri-checkbox-circle-line me-1"></i>Valid
                                                    </span>
                                                @elseif($monitor->status === 'expired')
                                                    <span class="badge bg-danger-transparent text-danger">
                                                        <i class="ri-close-circle-line me-1"></i>Expired
                                                    </span>
                                                @elseif($monitor->status === 'invalid')
                                                    <span class="badge bg-danger-transparent text-danger">
                                                        <i class="ri-error-warning-line me-1"></i>Invalid
                                                    </span>
                                                @elseif($monitor->status === 'expiring_soon')
                                                    <span class="badge bg-warning-transparent text-warning">
                                                        <i class="ri-alert-line me-1"></i>Expiring Soon
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary-transparent text-secondary">
                                                        <i class="ri-question-line me-1"></i>Unknown
                                                    </span>
                                                @endif
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
                                                <span class="text-muted">{{ $monitor->check_interval }} min</span>
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
                                                    <a href="{{ route('ssl-monitors.show', $monitor->uid) }}" class="btn btn-sm btn-info btn-wave">
                                                        <i class="ri-eye-line"></i>
                                                    </a>
                                                    <a href="{{ route('ssl-monitors.edit', $monitor->uid) }}" class="btn btn-sm btn-primary btn-wave">
                                                        <i class="ri-pencil-line"></i>
                                                    </a>
                                                    <form id="delete-form-{{ $monitor->uid }}" action="{{ route('ssl-monitors.destroy', $monitor->uid) }}" method="POST" style="display: none;">
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




