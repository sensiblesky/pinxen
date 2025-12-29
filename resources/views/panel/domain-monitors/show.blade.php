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
    <div class="d-flex align-items-center justify-content-between mb-3 page-header-breadcrumb flex-wrap gap-2">
        <div>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('panel.domain-monitors.index') }}">Domain Monitoring</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $monitor->name }}</li>
            </ol>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('panel.domain-monitors.edit', $monitor->uid) }}" class="btn btn-sm btn-primary btn-wave">
                <i class="ri-pencil-line me-1"></i>Edit
            </a>
        </div>
    </div>
    <!-- End::page-header -->

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Info Message about TLD Support -->
    <div class="alert alert-info alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
        <div class="me-3">
            <i class="ri-information-line fs-20"></i>
        </div>
        <div class="flex-fill">
            <strong>Domain TLD Support Notice:</strong> Some domain TLDs (Top-Level Domains) are currently not supported, including specific country domains like <code>.go.tz</code>, <code>.ac.tz</code>, and other regional TLDs. We are actively working to expand our territories and add support for more TLDs. If you encounter issues with a specific domain, please contact support.
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <!-- Start::row-1 - Summary Statistics -->
    <div class="row">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card custom-card dashboard-main-card overflow-hidden {{ $monitor->days_until_expiration !== null && $monitor->days_until_expiration <= 30 ? ($monitor->days_until_expiration <= 5 ? 'danger' : 'warning') : 'success' }}">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-fill">
                            <span class="fs-13 fw-medium">Days Until Expiration</span>
                            <h4 class="fw-semibold my-2 lh-1">
                                @if($monitor->days_until_expiration !== null)
                                    @if($monitor->days_until_expiration < 0)
                                        <span class="text-danger">Expired</span>
                                    @else
                                        <span>{{ $monitor->days_until_expiration }}</span>
                                    @endif
                                @else
                                    <span class="text-secondary">Unknown</span>
                                @endif
                            </h4>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fs-12 d-block text-muted">
                                    @if($monitor->expiration_date)
                                        Expires: {{ $monitor->expiration_date->format('M d, Y') }}
                                    @else
                                        Expiration date not set
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="avatar avatar-md bg-{{ $monitor->days_until_expiration !== null && $monitor->days_until_expiration <= 30 ? ($monitor->days_until_expiration <= 5 ? 'danger' : 'warning') : 'success' }}-transparent svg-{{ $monitor->days_until_expiration !== null && $monitor->days_until_expiration <= 30 ? ($monitor->days_until_expiration <= 5 ? 'danger' : 'warning') : 'success' }}">
                                <i class="ri-calendar-close-line fs-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card custom-card dashboard-main-card overflow-hidden primary">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-fill">
                            <span class="fs-13 fw-medium">Domain</span>
                            <h4 class="fw-semibold my-2 lh-1">{{ $monitor->domain }}</h4>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fs-12 d-block text-muted">
                                    @if($monitor->is_active)
                                        <span class="text-success">Active</span>
                                    @else
                                        <span class="text-muted">Inactive</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="avatar avatar-md bg-primary-transparent svg-primary">
                                <i class="ri-global-line fs-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card custom-card dashboard-main-card overflow-hidden info">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-fill">
                            <span class="fs-13 fw-medium">Last Checked</span>
                            <h4 class="fw-semibold my-2 lh-1">
                                @if($monitor->last_checked_at)
                                    {{ $monitor->last_checked_at->diffForHumans() }}
                                @else
                                    <span class="text-muted">Never</span>
                                @endif
                            </h4>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fs-12 d-block text-muted">
                                    @if($monitor->last_checked_at)
                                        {{ $monitor->last_checked_at->format('M d, Y H:i') }}
                                    @else
                                        No checks performed yet
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="avatar avatar-md bg-info-transparent svg-info">
                                <i class="ri-time-line fs-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card custom-card dashboard-main-card overflow-hidden secondary">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-fill">
                            <span class="fs-13 fw-medium">Total Alerts</span>
                            <h4 class="fw-semibold my-2 lh-1">{{ $monitor->alerts->count() }}</h4>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fs-12 d-block text-muted">
                                    Sent: {{ $monitor->alerts->where('status', 'sent')->count() }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="avatar avatar-md bg-secondary-transparent svg-secondary">
                                <i class="ri-notification-line fs-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->

    <!-- Start::row-2 - Monitor Details -->
    <div class="row">
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Monitor Details</div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Monitor Name</label>
                            <p class="fw-semibold">{{ $monitor->name }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Domain</label>
                            <p class="fw-semibold">{{ $monitor->domain }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Expiration Date</label>
                            <p class="fw-semibold">
                                @if($monitor->expiration_date)
                                    {{ $monitor->expiration_date->format('F d, Y') }}
                                @else
                                    <span class="text-muted">Not set</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Status</label>
                            <p>
                                @if($monitor->is_active)
                                    <span class="badge bg-success-transparent text-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-transparent text-secondary">Inactive</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <hr>

                    <h6 class="mb-3">Alert Settings</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                @if($monitor->alert_30_days)
                                    <i class="ri-checkbox-circle-line text-success me-2"></i>
                                @else
                                    <i class="ri-close-circle-line text-muted me-2"></i>
                                @endif
                                <span>Alert 30 days before</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                @if($monitor->alert_5_days)
                                    <i class="ri-checkbox-circle-line text-success me-2"></i>
                                @else
                                    <i class="ri-close-circle-line text-muted me-2"></i>
                                @endif
                                <span>Alert 5 days before</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                @if($monitor->alert_daily_under_30)
                                    <i class="ri-checkbox-circle-line text-success me-2"></i>
                                @else
                                    <i class="ri-close-circle-line text-muted me-2"></i>
                                @endif
                                <span>Daily alerts (≤30 days)</span>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h6 class="mb-3">Communication Channels</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @forelse($communicationPreferences as $pref)
                            <span class="badge bg-primary-transparent text-primary">
                                {{ ucfirst($pref->communication_channel) }}
                            </span>
                        @empty
                            <span class="text-muted">No communication channels configured</span>
                        @endforelse
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
                        <form action="{{ route('panel.domain-monitors.recheck', $monitor->uid) }}" method="POST" id="recheck-form">
                            @csrf
                            <button type="submit" class="btn btn-info btn-wave w-100">
                                <i class="ri-refresh-line me-1"></i>Recheck WHOIS
                            </button>
                        </form>
                        <a href="{{ route('panel.domain-monitors.edit', $monitor->uid) }}" class="btn btn-primary btn-wave">
                            <i class="ri-pencil-line me-1"></i>Edit Monitor
                        </a>
                        <form action="{{ route('panel.domain-monitors.destroy', $monitor->uid) }}" method="POST" id="delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="btn btn-danger btn-wave w-100" onclick="confirmDelete()">
                                <i class="ri-delete-bin-line me-1"></i>Delete Monitor
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-2 -->

    <!-- Start::row-3 - Alert History -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Alert History</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="alerts-table" class="table table-bordered text-nowrap w-100">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Alert Type</th>
                                    <th>Message</th>
                                    <th>Channel</th>
                                    <th>Status</th>
                                    <th>Sent At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($monitor->alerts as $index => $alert)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            @if($alert->alert_type === '30_days')
                                                <span class="badge bg-warning-transparent text-warning">30 Days</span>
                                            @elseif($alert->alert_type === '5_days')
                                                <span class="badge bg-danger-transparent text-danger">5 Days</span>
                                            @elseif($alert->alert_type === 'daily')
                                                <span class="badge bg-info-transparent text-info">Daily</span>
                                            @else
                                                <span class="badge bg-danger-transparent text-danger">Expired</span>
                                            @endif
                                        </td>
                                        <td>{{ $alert->message }}</td>
                                        <td>
                                            <span class="text-capitalize">{{ $alert->communication_channel }}</span>
                                        </td>
                                        <td>
                                            @if($alert->status === 'sent')
                                                <span class="badge bg-success-transparent text-success">Sent</span>
                                            @elseif($alert->status === 'failed')
                                                <span class="badge bg-danger-transparent text-danger">Failed</span>
                                            @else
                                                <span class="badge bg-warning-transparent text-warning">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($alert->sent_at)
                                                {{ $alert->sent_at->format('M d, Y H:i') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <span class="text-muted">No alerts sent yet</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-3 -->
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
            $('#alerts-table').DataTable({
                responsive: true,
                order: [[5, 'desc']],
                pageLength: 10,
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
                }
            });
        });

        function confirmDelete() {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You are about to delete this domain monitor. This action cannot be undone.',
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

