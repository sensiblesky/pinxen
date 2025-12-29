@extends('layouts.master')

@section('title')
{{ $monitor->name }} - DNS Monitor - PingXeno
@endsection

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
                <li class="breadcrumb-item"><a href="{{ route('dns-monitors.index') }}">DNS Monitoring</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $monitor->name }}</li>
            </ol>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('dns-monitors.edit', $monitor->uid) }}" class="btn btn-sm btn-primary btn-wave">
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
            <div class="card custom-card dashboard-main-card overflow-hidden {{ $monitor->status === 'healthy' ? 'success' : ($monitor->status === 'changed' ? 'warning' : ($monitor->status === 'missing' || $monitor->status === 'error' ? 'danger' : 'secondary')) }}">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-fill">
                            <span class="fs-13 fw-medium">DNS Status</span>
                            <h4 class="fw-semibold my-2 lh-1">
                                @if($monitor->status === 'healthy')
                                    <span class="text-success">Healthy</span>
                                @elseif($monitor->status === 'changed')
                                    <span class="text-warning">Changed</span>
                                @elseif($monitor->status === 'missing')
                                    <span class="text-danger">Missing</span>
                                @elseif($monitor->status === 'error')
                                    <span class="text-danger">Error</span>
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
                            <span class="avatar avatar-md bg-{{ $monitor->status === 'healthy' ? 'success' : ($monitor->status === 'changed' ? 'warning' : ($monitor->status === 'missing' || $monitor->status === 'error' ? 'danger' : 'secondary')) }}-transparent svg-{{ $monitor->status === 'healthy' ? 'success' : ($monitor->status === 'changed' ? 'warning' : ($monitor->status === 'missing' || $monitor->status === 'error' ? 'danger' : 'secondary')) }}">
                                <i class="ri-dns-line fs-24"></i>
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
                            <span class="fs-13 fw-medium">Record Types</span>
                            <h4 class="fw-semibold my-2 lh-1">{{ count($monitor->record_types) }} types</h4>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fs-12 d-block text-muted">
                                    @foreach($monitor->record_types as $type)
                                        <code>{{ $type }}</code>@if(!$loop->last), @endif
                                    @endforeach
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="avatar avatar-md bg-info-transparent svg-info">
                                <i class="ri-file-list-line fs-24"></i>
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
                            <span class="fs-13 fw-medium">Check Interval</span>
                            <h4 class="fw-semibold my-2 lh-1">{{ $monitor->check_interval }} min</h4>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fs-12 d-block text-muted">
                                    Total checks: {{ $monitor->checks->count() }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="avatar avatar-md bg-secondary-transparent svg-secondary">
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
                        <form action="{{ route('dns-monitors.recheck', $monitor->uid) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-wave">
                                <i class="ri-refresh-line me-1"></i>Recheck DNS Records
                            </button>
                        </form>
                        <a href="{{ route('dns-monitors.edit', $monitor->uid) }}" class="btn btn-info btn-wave">
                            <i class="ri-pencil-line me-1"></i>Edit Monitor
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-2 -->

    <!-- Start::row-3 - DNS Records by Type -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Current DNS Records</div>
                </div>
                <div class="card-body">
                    @if($checksByType->isEmpty())
                        <div class="text-center py-5">
                            <p class="text-muted">No DNS records checked yet. Click "Recheck DNS Records" to fetch current records.</p>
                        </div>
                    @else
                        @foreach($monitor->record_types as $recordType)
                            @php
                                $latestCheck = $checksByType->get($recordType)?->first();
                            @endphp
                            <div class="mb-4">
                                <h5 class="mb-3">
                                    <code>{{ $recordType }}</code> Records
                                    @if($latestCheck && $latestCheck->has_changes)
                                        <span class="badge bg-warning-transparent text-warning ms-2">Changed</span>
                                    @endif
                                    @if($latestCheck && $latestCheck->is_missing)
                                        <span class="badge bg-danger-transparent text-danger ms-2">Missing</span>
                                    @endif
                                </h5>
                                @if($latestCheck && !empty($latestCheck->records))
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Host</th>
                                                    <th>Value</th>
                                                    <th>TTL</th>
                                                    <th>Checked At</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($latestCheck->records as $record)
                                                    <tr>
                                                        <td>{{ $record['host'] ?? 'N/A' }}</td>
                                                        <td>
                                                            @if($recordType === 'A')
                                                                {{ $record['ip'] ?? 'N/A' }}
                                                            @elseif($recordType === 'AAAA')
                                                                {{ $record['ipv6'] ?? 'N/A' }}
                                                            @elseif($recordType === 'CNAME' || $recordType === 'NS' || $recordType === 'MX')
                                                                {{ $record['target'] ?? 'N/A' }}
                                                            @elseif($recordType === 'TXT')
                                                                {{ Str::limit($record['txt'] ?? 'N/A', 100) }}
                                                            @else
                                                                {{ json_encode($record) }}
                                                            @endif
                                                        </td>
                                                        <td>{{ $record['ttl'] ?? 'N/A' }}</td>
                                                        <td>{{ $latestCheck->checked_at->format('M d, Y H:i') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-muted">No {{ $recordType }} records found.</p>
                                @endif
                            </div>
                            <hr>
                        @endforeach
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
                        <p class="text-muted">No communication channels configured. <a href="{{ route('dns-monitors.edit', $monitor->uid) }}">Edit monitor</a> to add communication channels.</p>
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
                                        <th>Record Type</th>
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
                                                @if($alert->alert_type === 'changed')
                                                    <span class="badge bg-warning-transparent text-warning">Changed</span>
                                                @elseif($alert->alert_type === 'missing')
                                                    <span class="badge bg-danger-transparent text-danger">Missing</span>
                                                @elseif($alert->alert_type === 'error')
                                                    <span class="badge bg-danger-transparent text-danger">Error</span>
                                                @else
                                                    <span class="badge bg-success-transparent text-success">Recovered</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($alert->record_type)
                                                    <code>{{ $alert->record_type }}</code>
                                                @else
                                                    <span class="text-muted">N/A</span>
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





