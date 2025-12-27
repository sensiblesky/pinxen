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
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('ssl-monitors.index') }}">SSL Monitoring</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $monitor->name }}</li>
            </ol>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('ssl-monitors.edit', $monitor->uid) }}" class="btn btn-sm btn-primary btn-wave">
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

    <!-- Start::row-1 - Summary Statistics -->
    <div class="row">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card custom-card dashboard-main-card overflow-hidden {{ $monitor->status === 'valid' ? 'success' : ($monitor->status === 'expired' || $monitor->status === 'invalid' ? 'danger' : ($monitor->status === 'expiring_soon' ? 'warning' : 'secondary')) }}">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-fill">
                            <span class="fs-13 fw-medium">Certificate Status</span>
                            <h4 class="fw-semibold my-2 lh-1">
                                @if($monitor->status === 'valid')
                                    <span class="text-success">Valid</span>
                                @elseif($monitor->status === 'expired')
                                    <span class="text-danger">Expired</span>
                                @elseif($monitor->status === 'invalid')
                                    <span class="text-danger">Invalid</span>
                                @elseif($monitor->status === 'expiring_soon')
                                    <span class="text-warning">Expiring Soon</span>
                                @else
                                    <span class="text-secondary">Unknown</span>
                                @endif
                            </h4>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fs-12 d-block text-muted">
                                    @if($monitor->last_checked_at)
                                        Last checked: {{ $monitor->last_checked_at->diffForHumans() }}
                                    @else
                                        Never checked
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="avatar avatar-md bg-{{ $monitor->status === 'valid' ? 'success' : ($monitor->status === 'expired' || $monitor->status === 'invalid' ? 'danger' : ($monitor->status === 'expiring_soon' ? 'warning' : 'secondary')) }}-transparent svg-{{ $monitor->status === 'valid' ? 'success' : ($monitor->status === 'expired' || $monitor->status === 'invalid' ? 'danger' : ($monitor->status === 'expiring_soon' ? 'warning' : 'secondary')) }}">
                                <i class="ri-shield-check-line fs-24"></i>
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
            <div class="card custom-card dashboard-main-card overflow-hidden {{ $monitor->days_until_expiration !== null && $monitor->days_until_expiration <= 30 ? ($monitor->days_until_expiration <= 0 ? 'danger' : 'warning') : 'success' }}">
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
                            <span class="avatar avatar-md bg-{{ $monitor->days_until_expiration !== null && $monitor->days_until_expiration <= 30 ? ($monitor->days_until_expiration <= 0 ? 'danger' : 'warning') : 'success' }}-transparent svg-{{ $monitor->days_until_expiration !== null && $monitor->days_until_expiration <= 30 ? ($monitor->days_until_expiration <= 0 ? 'danger' : 'warning') : 'success' }}">
                                <i class="ri-calendar-close-line fs-24"></i>
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
                            <span class="fs-13 fw-medium">Check Interval</span>
                            <h4 class="fw-semibold my-2 lh-1">{{ $monitor->check_interval }} min</h4>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fs-12 d-block text-muted">
                                    Next check: {{ $monitor->last_checked_at ? $monitor->last_checked_at->addMinutes($monitor->check_interval)->diffForHumans() : 'Not scheduled' }}
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
    </div>
    <!-- End::row-1 -->

    <!-- Start::row-2 - Quick Actions -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Quick Actions</div>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2 flex-wrap">
                        <form action="{{ route('ssl-monitors.recheck', $monitor->uid) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-wave">
                                <i class="ri-refresh-line me-1"></i>Recheck SSL Certificate
                            </button>
                        </form>
                        <a href="{{ route('ssl-monitors.edit', $monitor->uid) }}" class="btn btn-info btn-wave">
                            <i class="ri-pencil-line me-1"></i>Edit Monitor
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-2 -->

    <!-- Start::row-3 - Certificate Details -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Certificate Details</div>
                </div>
                <div class="card-body">
                    @php
                        $latestCheck = $monitor->checks->first();
                    @endphp
                    @if($latestCheck)
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Issued To</label>
                                <p class="fw-semibold">{{ $latestCheck->issued_to ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Issuer</label>
                                <p class="fw-semibold">{{ $latestCheck->issuer_cn ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Algorithm</label>
                                <p class="fw-semibold">{{ $latestCheck->cert_alg ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Resolved IP</label>
                                <p class="fw-semibold">{{ $latestCheck->resolved_ip ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Valid From</label>
                                <p class="fw-semibold">{{ $latestCheck->valid_from ? $latestCheck->valid_from->format('M d, Y') : 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Valid Till</label>
                                <p class="fw-semibold">{{ $latestCheck->valid_till ? $latestCheck->valid_till->format('M d, Y') : 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">HSTS Enabled</label>
                                <p class="fw-semibold">
                                    @if($latestCheck->hsts_header_enabled)
                                        <span class="badge bg-success-transparent text-success">Yes</span>
                                    @else
                                        <span class="badge bg-secondary-transparent text-secondary">No</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Response Time</label>
                                <p class="fw-semibold">{{ $latestCheck->response_time_sec ? number_format($latestCheck->response_time_sec, 2) . 's' : 'N/A' }}</p>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <p class="text-muted">No certificate data available. Click "Recheck SSL Certificate" to fetch certificate information.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-3 -->

    <!-- Start::row-4 - Communication Preferences -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Communication Preferences</div>
                </div>
                <div class="card-body">
                    @if($communicationPreferences->isEmpty())
                        <p class="text-muted">No communication channels configured. <a href="{{ route('ssl-monitors.edit', $monitor->uid) }}">Edit monitor</a> to add communication channels.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Channel</th>
                                        <th>Value</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($communicationPreferences as $pref)
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary-transparent text-primary">
                                                    {{ ucfirst($pref->communication_channel) }}
                                                </span>
                                            </td>
                                            <td>{{ $pref->channel_value }}</td>
                                            <td>
                                                @if($pref->is_enabled)
                                                    <span class="badge bg-success-transparent text-success">Enabled</span>
                                                @else
                                                    <span class="badge bg-secondary-transparent text-secondary">Disabled</span>
                                                @endif
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
    <!-- End::row-4 -->

    <!-- Start::row-5 - Alert History -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Alert History</div>
                </div>
                <div class="card-body">
                    @if($monitor->alerts->isEmpty())
                        <p class="text-muted">No alerts sent yet.</p>
                    @else
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
                                    @foreach($monitor->alerts as $index => $alert)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                @if($alert->alert_type === 'expired')
                                                    <span class="badge bg-danger-transparent text-danger">Expired</span>
                                                @elseif($alert->alert_type === 'invalid')
                                                    <span class="badge bg-danger-transparent text-danger">Invalid</span>
                                                @elseif($alert->alert_type === 'expiring_soon')
                                                    <span class="badge bg-warning-transparent text-warning">Expiring Soon</span>
                                                @else
                                                    <span class="badge bg-success-transparent text-success">Recovered</span>
                                                @endif
                                            </td>
                                            <td>{{ Str::limit($alert->message, 100) }}</td>
                                            <td>
                                                <span class="badge bg-primary-transparent text-primary">
                                                    {{ ucfirst($alert->communication_channel) }}
                                                </span>
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
                                                    <span class="text-muted">Not sent</span>
                                                @endif
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
    <!-- End::row-5 -->
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
                order: [[0, 'desc']],
                pageLength: 10,
            });
        });
    </script>
@endsection





