@extends('layouts.master')

@section('styles')
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css" rel="stylesheet">
    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.css')}}">
    <!-- Apex Charts CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/apexcharts/apexcharts.css')}}">
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="d-flex align-items-center justify-content-between mb-3 page-header-breadcrumb flex-wrap gap-2">
        <div>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('uptime-monitors.index') }}">Uptime Monitoring</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $monitor->name }}</li>
            </ol>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="form-group">
                <input type="text" class="form-control breadcrumb-input" id="daterange" placeholder="Search By Date Range">
            </div>
            <div class="btn-list">
                <button type="button" class="btn btn-icon btn-primary btn-wave" id="toggle-autorefresh" title="Toggle auto-refresh">
                    <i class="ri-refresh-line"></i>
                </button>
                <button type="button" class="btn btn-icon btn-primary btn-wave me-0" onclick="openCustomRangeModal(event)" title="Custom Range">
                    <i class="ri-filter-3-line"></i>
                </button>
            </div>
        </div>
    </div>
    <!-- End::page-header -->

    <!-- Custom Date Range Modal -->
    <div class="modal fade" id="customRangeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Custom Date Range</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="custom-start-date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" id="custom-end-date" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="applyCustomRange()">Apply</button>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Start::row-1 - Summary Statistics -->
    <div class="row">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card custom-card dashboard-main-card overflow-hidden {{ $monitor->status === 'up' ? 'success' : ($monitor->status === 'down' ? 'danger' : 'secondary') }}">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-fill">
                            <span class="fs-13 fw-medium">Current Status</span>
                            <h4 class="fw-semibold my-2 lh-1">
                                @if($monitor->status === 'up')
                                    <span class="text-success">UP</span>
                                @elseif($monitor->status === 'down')
                                    <span class="text-danger">DOWN</span>
                                @else
                                    <span class="text-secondary">UNKNOWN</span>
                                @endif
                            </h4>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fs-12 d-block text-muted">
                                    @if($monitor->last_checked_at)
                                        Last checked {{ $monitor->last_checked_at->diffForHumans() }}
                                    @else
                                        Never checked
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="avatar avatar-md bg-{{ $monitor->status === 'up' ? 'success' : ($monitor->status === 'down' ? 'danger' : 'secondary') }}-transparent svg-{{ $monitor->status === 'up' ? 'success' : ($monitor->status === 'down' ? 'danger' : 'secondary') }}">
                                @if($monitor->status === 'up')
                                    <i class="ri-checkbox-circle-line fs-24"></i>
                                @elseif($monitor->status === 'down')
                                    <i class="ri-close-circle-line fs-24"></i>
                                @else
                                    <i class="ri-question-line fs-24"></i>
                                @endif
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
                            <span class="fs-13 fw-medium">Uptime</span>
                            <h4 class="fw-semibold my-2 lh-1">{{ number_format($uptimePercentage, 2) }}%</h4>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fs-12 d-block text-muted">
                                    <span class="text-success me-1 d-inline-flex align-items-center fw-semibold">
                                        <i class="ri-checkbox-circle-line me-1 fw-semibold align-middle"></i>{{ $upChecks }}
                                    </span>up / {{ $downChecks }} down
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="avatar avatar-md bg-primary-transparent svg-primary">
                                <i class="ri-bar-chart-line fs-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card custom-card dashboard-main-card overflow-hidden warning">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-fill">
                            <span class="fs-13 fw-medium">Avg Response Time</span>
                            <h4 class="fw-semibold my-2 lh-1">{{ number_format($avgResponseTime, 0) }}ms</h4>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fs-12 d-block text-muted">
                                    @if($minResponseTime && $maxResponseTime)
                                        Min: {{ $minResponseTime }}ms / Max: {{ $maxResponseTime }}ms
                                    @else
                                        No data available
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="avatar avatar-md bg-warning-transparent svg-warning">
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
                            <span class="fs-13 fw-medium">Total Checks</span>
                            <h4 class="fw-semibold my-2 lh-1">{{ number_format($totalChecks) }}</h4>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fs-12 d-block text-muted">
                                    Check interval: {{ $monitor->check_interval }} min
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="avatar avatar-md bg-secondary-transparent svg-secondary">
                                <i class="ri-database-line fs-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->

    <!-- Start::row-1.5 - Status Timeline -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center gap-2">
                                @if($monitor->status === 'up')
                                    <span class="badge bg-success-transparent text-success rounded-circle" style="width: 8px; height: 8px; padding: 0;"></span>
                                @elseif($monitor->status === 'down')
                                    <span class="badge bg-danger-transparent text-danger rounded-circle" style="width: 8px; height: 8px; padding: 0;"></span>
                                @else
                                    <span class="badge bg-secondary-transparent text-secondary rounded-circle" style="width: 8px; height: 8px; padding: 0;"></span>
                                @endif
                                <h5 class="mb-0 fw-semibold">{{ $monitor->name }}</h5>
                            </div>
                            @if($monitor->check_ssl)
                                <i class="ri-lock-line text-success fs-16" title="SSL Enabled"></i>
                            @endif
                            <i class="ri-global-line text-success fs-16" title="Web Service"></i>
                        </div>
                        <div class="text-end">
                            <span class="text-muted fs-13">{{ number_format($overallUptime90Days, 2) }}% uptime</span>
                        </div>
                    </div>
                    <div class="status-timeline-container" style="position: relative;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted fs-12">Last 90 days</span>
                            <span class="text-muted fs-12">Today</span>
                        </div>
                        <div class="status-timeline" id="status-timeline" style="display: flex; gap: 2px; height: 30px; position: relative;">
                            @foreach($dailyStatusData as $day)
                                @php
                                    $statusClass = 'bg-success';
                                    $tooltipContent = '';
                                    if ($day['status'] === 'down' || ($day['uptime_percent'] !== null && $day['uptime_percent'] < 99.9)) {
                                        $statusClass = 'bg-danger';
                                    } elseif ($day['status'] === 'unknown') {
                                        $statusClass = 'bg-secondary';
                                    }
                                    
                                    $dateFormatted = \Carbon\Carbon::parse($day['date'])->format('Y-m-d');
                                    $uptimeDisplay = $day['uptime_percent'] !== null ? number_format($day['uptime_percent'], 2) . '%' : 'N/A';
                                    $incidentsText = $day['incidents_count'] > 0 
                                        ? $day['incidents_count'] . ' incident' . ($day['incidents_count'] > 1 ? 's' : '')
                                        : 'No incidents';
                                    $downtimeText = '';
                                    if ($day['downtime_duration'] > 0) {
                                        $hours = floor($day['downtime_duration'] / 3600);
                                        $minutes = floor(($day['downtime_duration'] % 3600) / 60);
                                        $seconds = $day['downtime_duration'] % 60;
                                        if ($hours > 0) {
                                            $downtimeText = " ({$hours} hour" . ($hours > 1 ? 's' : '') . ($minutes > 0 ? " {$minutes} minute" . ($minutes > 1 ? 's' : '') : '') . ($seconds > 0 ? " {$seconds} second" . ($seconds > 1 ? 's' : '') : '') . ")";
                                        } elseif ($minutes > 0) {
                                            $downtimeText = " ({$minutes} minute" . ($minutes > 1 ? 's' : '') . ($seconds > 0 ? " {$seconds} second" . ($seconds > 1 ? 's' : '') : '') . ")";
                                        } else {
                                            $downtimeText = " ({$seconds} second" . ($seconds > 1 ? 's' : '') . ")";
                                        }
                                    }
                                @endphp
                                <div 
                                    class="status-bar {{ $statusClass }}" 
                                    style="flex: 1; height: 100%; cursor: pointer; border-radius: 2px; transition: all 0.2s;"
                                    data-date="{{ $dateFormatted }}"
                                    data-uptime="{{ $day['uptime_percent'] ?? 'N/A' }}"
                                    data-incidents="{{ $day['incidents_count'] }}"
                                    data-downtime="{{ $day['downtime_duration'] }}"
                                    data-total-checks="{{ $day['total_count'] }}"
                                    title="{{ $dateFormatted }}&#10;Uptime: {{ $uptimeDisplay }}&#10;{{ $incidentsText }}{{ $downtimeText }}"
                                    onmouseover="showTimelineTooltip(event, this)"
                                    onmouseout="hideTimelineTooltip()"
                                ></div>
                            @endforeach
                        </div>
                        <div id="timeline-tooltip" style="position: absolute; background: #000; color: #fff; padding: 8px 12px; border-radius: 4px; font-size: 12px; pointer-events: none; z-index: 1000; display: none; white-space: pre-line; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                            <div id="tooltip-date" class="fw-semibold mb-1"></div>
                            <div id="tooltip-uptime" class="mb-1"></div>
                            <div id="tooltip-incidents"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1.5 -->

    <!-- Start::row-2 - Charts -->
    <div class="row">
        <!-- Response Time Chart (reduced width) -->
        <div class="col-xl-6 col-lg-7">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">Performance Overview</div>
                </div>
                <div class="card-body pb-0 pt-5">
                    <div id="performance-overview-chart"></div>
                </div>
            </div>
        </div>
        <!-- Status Distribution Chart -->
        <div class="col-xl-6 col-lg-5">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Status Distribution</div>
                </div>
                <div class="card-body">
                    <div id="status-distribution-chart"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-2 -->

    <!-- Start::row-4 - Monitor Details & History -->
    <div class="row">
        <!-- Monitor Details -->
        <div class="col-xl-4">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="ri-information-line me-2"></i>Monitor Details
                    </div>
                </div>
                <div class="card-body">
                    <!-- Monitor Status Badge (Compact) -->
                    <div class="text-center mb-3 pb-2 border-bottom">
                        <div class="mb-2">
                            @if($monitor->status === 'up')
                                <span class="badge bg-success-transparent text-success fs-13 px-2 py-1">
                                    <i class="ri-checkbox-circle-line me-1"></i>Operational
                                </span>
                            @elseif($monitor->status === 'down')
                                <span class="badge bg-danger-transparent text-danger fs-13 px-2 py-1">
                                    <i class="ri-close-circle-line me-1"></i>Down
                                </span>
                            @else
                                <span class="badge bg-secondary-transparent text-secondary fs-13 px-2 py-1">
                                    <i class="ri-question-line me-1"></i>Unknown
                                </span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center justify-content-center gap-2">
                            @if($monitor->check_ssl)
                                <span class="badge bg-info-transparent text-info fs-11" title="SSL Enabled">
                                    <i class="ri-lock-line me-1"></i>SSL
                                </span>
                            @endif
                            @if($monitor->is_active)
                                <span class="badge bg-success-transparent text-success fs-11" title="Active">
                                    <i class="ri-pulse-line me-1"></i>Active
                                </span>
                            @else
                                <span class="badge bg-secondary-transparent text-secondary fs-11" title="Inactive">
                                    <i class="ri-pause-line me-1"></i>Inactive
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Basic Info - Stacked Vertically -->
                    <div class="mb-4">
                        <h6 class="mb-3 fw-semibold fs-15">
                            <i class="ri-global-line me-1 text-primary"></i>Basic Info
                        </h6>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Name</label>
                                <div class="fw-semibold fs-14">{{ Str::limit($monitor->name, 25) }}</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Service Type</label>
                                <div class="fw-semibold fs-14">Uptime / HTTP</div>
                            </div>
                            @if($monitor->url)
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">URL</label>
                                <div>
                                    <a href="{{ $monitor->url }}" target="_blank" class="text-primary fw-semibold text-break fs-14" title="{{ $monitor->url }}">
                                        {{ Str::limit($monitor->url, 35) }}
                                        <i class="ri-external-link-line ms-1"></i>
                                    </a>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Advanced - Stacked Vertically -->
                    @if($monitor->request_method !== 'GET' || $monitor->basic_auth_username || $monitor->custom_headers || $monitor->cache_buster || $monitor->maintenance_start_time)
                    <div class="mb-4">
                        <h6 class="mb-3 fw-semibold fs-15">
                            <i class="ri-tools-line me-1 text-primary"></i>Advanced
                        </h6>
                        <div class="row g-3">
                            @if($monitor->request_method !== 'GET')
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Method</label>
                                <div class="fw-semibold">
                                    <span class="badge bg-info-transparent text-info fs-13">{{ $monitor->request_method }}</span>
                                </div>
                            </div>
                            @endif

                            @if($monitor->basic_auth_username)
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Basic Auth</label>
                                <div class="fw-semibold">
                                    <span class="badge bg-success-transparent text-success fs-13">
                                        <i class="ri-user-line me-1"></i>{{ Str::limit($monitor->basic_auth_username, 15) }}
                                    </span>
                                </div>
                            </div>
                            @endif

                            @if($monitor->custom_headers && is_array($monitor->custom_headers) && count($monitor->custom_headers) > 0)
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Headers</label>
                                <div class="fw-semibold">
                                    <span class="badge bg-primary-transparent text-primary fs-13">
                                        <i class="ri-file-list-3-line me-1"></i>{{ count($monitor->custom_headers) }}
                                    </span>
                                </div>
                            </div>
                            @endif

                            @if($monitor->cache_buster)
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Cache Buster</label>
                                <div>
                                    <span class="badge bg-warning-transparent text-warning fs-13">
                                        <i class="ri-refresh-line me-1"></i>On
                                    </span>
                                </div>
                            </div>
                            @endif

                            @if($monitor->maintenance_start_time && $monitor->maintenance_end_time)
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Maintenance</label>
                                <div class="fw-semibold fs-13">
                                    <div>
                                        <i class="ri-calendar-line me-1 text-info"></i>
                                        {{ \Carbon\Carbon::parse($monitor->maintenance_start_time)->format('M d, H:i') }}
                                    </div>
                                    <div class="text-muted">
                                        <i class="ri-arrow-down-line me-1"></i>
                                        {{ \Carbon\Carbon::parse($monitor->maintenance_end_time)->format('M d, H:i') }}
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Configuration - Stacked Vertically -->
                    <div class="mb-4">
                        <h6 class="mb-3 fw-semibold fs-15">
                            <i class="ri-settings-3-line me-1 text-primary"></i>Configuration
                        </h6>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Interval</label>
                                <div class="fw-semibold fs-14">
                                    <i class="ri-time-line me-1 text-info"></i>{{ $monitor->check_interval }}m
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Timeout</label>
                                <div class="fw-semibold fs-14">
                                    <i class="ri-timer-line me-1 text-warning"></i>{{ $monitor->timeout }}s
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Status Code</label>
                                <div class="fw-semibold">
                                    <span class="badge bg-info-transparent text-info fs-13">{{ $monitor->expected_status_code }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">SSL</label>
                                <div>
                                    @if($monitor->check_ssl)
                                        <span class="badge bg-success-transparent text-success fs-13">
                                            <i class="ri-check-line me-1"></i>On
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-transparent text-secondary fs-13">
                                            <i class="ri-close-line me-1"></i>Off
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @if($monitor->keyword_present)
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Keyword Present</label>
                                <div class="fw-semibold">
                                    <span class="badge bg-primary-transparent text-primary fs-13">
                                        <i class="ri-search-line me-1"></i>{{ Str::limit($monitor->keyword_present, 18) }}
                                    </span>
                                </div>
                            </div>
                            @endif
                            @if($monitor->keyword_absent)
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Keyword Absent</label>
                                <div class="fw-semibold">
                                    <span class="badge bg-warning-transparent text-warning fs-13">
                                        <i class="ri-forbid-line me-1"></i>{{ Str::limit($monitor->keyword_absent, 18) }}
                                    </span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Statistics - Stacked Vertically -->
                    <div class="mb-4">
                        <h6 class="mb-3 fw-semibold fs-15">
                            <i class="ri-bar-chart-box-line me-1 text-primary"></i>Statistics
                        </h6>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Checks</label>
                                <div class="fw-semibold fs-14">
                                    <i class="ri-database-line me-1 text-info"></i>{{ number_format($totalChecks) }}
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Uptime</label>
                                <div class="fw-semibold fs-14">
                                    <i class="ri-line-chart-line me-1 text-success"></i>{{ number_format($uptimePercentage, 1) }}%
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Last Checked</label>
                                <div class="fw-semibold fs-14">
                                    @if($monitor->last_checked_at)
                                        <i class="ri-time-line me-1 text-primary"></i>{{ $monitor->last_checked_at->diffForHumans() }}
                                    @else
                                        <span class="text-muted"><i class="ri-time-line me-1"></i>Never</span>
                                    @endif
                                </div>
                            </div>
                            @if($monitor->next_check_at)
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Next Check</label>
                                <div class="fw-semibold fs-14">
                                    <i class="ri-calendar-check-line me-1 text-info"></i>{{ $monitor->next_check_at->diffForHumans() }}
                                </div>
                            </div>
                            @endif
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Created</label>
                                <div class="fw-semibold fs-14">
                                    <i class="ri-calendar-line me-1 text-secondary"></i>{{ $monitor->created_at->format('M d, Y') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions (Compact) -->
                    <div class="border-top pt-2 mt-2">
                        <div class="d-grid gap-2">
                            <a href="{{ route('uptime-monitors.edit', $monitor->uid) }}" class="btn btn-primary btn-wave btn-sm">
                                <i class="ri-edit-line me-1"></i>Edit Monitor
                            </a>
                            <button type="button" class="btn btn-info btn-wave btn-sm" onclick="window.location.reload()">
                                <i class="ri-refresh-line me-1"></i>Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Check History & Alerts -->
        <div class="col-xl-8">
            <!-- Check History -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Check History</div>
                </div>
                <div class="card-body">
                        <div class="table-responsive">
                            <table id="checks-table" class="table table-bordered text-nowrap w-100">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Status</th>
                                        <th>Response Time</th>
                                        <th>Status Code</th>
                                        <th>Response</th>
                                        <th>Checked At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <!-- Data will be loaded via server-side processing -->
                                </tbody>
                            </table>
                        </div>
                </div>
            </div>

            <!-- Alert History -->
            <div class="card custom-card mt-3">
                <div class="card-header">
                    <div class="card-title">Alert History</div>
                </div>
                <div class="card-body">
                    @if($monitor->alerts->isEmpty())
                        <div class="text-center py-5">
                            <div class="avatar avatar-xl avatar-rounded bg-secondary-transparent mb-3">
                                <i class="ri-notification-line fs-36"></i>
                            </div>
                            <h5 class="mb-2">No Alerts Yet</h5>
                            <p class="text-muted mb-0">No alerts have been sent for this monitor.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table id="alerts-table" class="table table-bordered text-nowrap w-100">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Type</th>
                                        <th>Channel</th>
                                        <th>Status</th>
                                        <th>Message</th>
                                        <th>Sent At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($monitor->alerts as $index => $alert)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                @if($alert->alert_type === 'down')
                                                    <span class="badge bg-danger-transparent text-danger">
                                                        <i class="ri-arrow-down-line me-1"></i>Down
                                                    </span>
                                                @elseif($alert->alert_type === 'up')
                                                    <span class="badge bg-success-transparent text-success">
                                                        <i class="ri-arrow-up-line me-1"></i>Up
                                                    </span>
                                                @else
                                                    <span class="badge bg-info-transparent text-info">
                                                        <i class="ri-refresh-line me-1"></i>Recovery
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="text-capitalize">{{ $alert->communication_channel }}</span>
                                            </td>
                                            <td>
                                                @if($alert->status === 'sent')
                                                    <span class="badge bg-success-transparent text-success">
                                                        <i class="ri-check-line me-1"></i>Sent
                                                    </span>
                                                @elseif($alert->status === 'failed')
                                                    <span class="badge bg-danger-transparent text-danger">
                                                        <i class="ri-close-line me-1"></i>Failed
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning-transparent text-warning">
                                                        <i class="ri-time-line me-1"></i>Pending
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <span title="{{ $alert->message }}">
                                                    {{ Str::limit($alert->message, 50) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($alert->sent_at)
                                                    <span class="text-muted">{{ $alert->sent_at->format('Y-m-d H:i:s') }}</span>
                                                    <small class="d-block text-muted">{{ $alert->sent_at->diffForHumans() }}</small>
                                                @else
                                                    <span class="text-muted">-</span>
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
@endsection

@section('scripts')
    <!-- Jquery Cdn -->
    <script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
    <!-- Datatables Cdn -->
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
    <!-- Sweetalerts JS -->
    <script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>
    <!-- Apex Charts JS -->
    <script src="{{asset('build/assets/libs/apexcharts/apexcharts.min.js')}}"></script>

    <script>
        // Chart data from backend
        const responseTimeData = @json($responseTimeData);
        const uptimeData = @json($uptimeData);
        const statusDistribution = @json($statusDistribution);
        
        // Timeline tooltip functions
        function showTimelineTooltip(event, element) {
            const tooltip = document.getElementById('timeline-tooltip');
            const date = element.getAttribute('data-date');
            const uptime = element.getAttribute('data-uptime');
            const incidents = parseInt(element.getAttribute('data-incidents'));
            const downtime = parseInt(element.getAttribute('data-downtime'));
            const totalChecks = parseInt(element.getAttribute('data-total-checks'));
            
            // Format date
            const dateObj = new Date(date);
            const formattedDate = dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            
            // Format uptime
            const uptimeDisplay = uptime === 'N/A' ? 'N/A' : parseFloat(uptime).toFixed(2) + '%';
            
            // Format downtime
            let downtimeText = '';
            if (downtime > 0) {
                const hours = Math.floor(downtime / 3600);
                const minutes = Math.floor((downtime % 3600) / 60);
                const seconds = downtime % 60;
                
                const parts = [];
                if (hours > 0) parts.push(hours + ' hour' + (hours > 1 ? 's' : ''));
                if (minutes > 0) parts.push(minutes + ' minute' + (minutes > 1 ? 's' : ''));
                if (seconds > 0) parts.push(seconds + ' second' + (seconds > 1 ? 's' : ''));
                
                downtimeText = ' (' + parts.join(' ') + ')';
            }
            
            // Build tooltip content
            let tooltipHtml = '<div class="fw-semibold mb-1">' + formattedDate + '</div>';
            tooltipHtml += '<div class="mb-1"><i class="ri-bar-chart-line me-1"></i>Uptime: ' + uptimeDisplay + '</div>';
            
            if (incidents > 0) {
                tooltipHtml += '<div><i class="ri-error-warning-line me-1 text-danger"></i>Incidents: ' + incidents + downtimeText + '</div>';
            } else {
                tooltipHtml += '<div><i class="ri-checkbox-circle-line me-1 text-success"></i>No incidents</div>';
            }
            
            tooltip.innerHTML = tooltipHtml;
            tooltip.style.display = 'block';
            
            // Position tooltip
            const rect = element.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();
            const containerRect = element.closest('.status-timeline-container').getBoundingClientRect();
            
            let left = rect.left - containerRect.left + (rect.width / 2) - (tooltipRect.width / 2);
            let top = rect.top - containerRect.top - tooltipRect.height - 10;
            
            // Adjust if tooltip goes outside container
            if (left < 0) left = 0;
            if (left + tooltipRect.width > containerRect.width) {
                left = containerRect.width - tooltipRect.width;
            }
            
            // If tooltip would go above container, show below instead
            if (top < 0) {
                top = rect.bottom - containerRect.top + 10;
            }
            
            tooltip.style.left = left + 'px';
            tooltip.style.top = top + 'px';
            
            // Add arrow pointing down
            tooltip.style.position = 'absolute';
        }
        
        function hideTimelineTooltip() {
            const tooltip = document.getElementById('timeline-tooltip');
            tooltip.style.display = 'none';
        }

        // Combined Performance Overview Chart (Response Time + Uptime)
        let performanceOverviewChart;
        function initPerformanceOverviewChart() {
            // Get current date range from URL to determine date format
            const urlParams = new URLSearchParams(window.location.search);
            const range = urlParams.get('range') || '24h';
            const startDateParam = urlParams.get('start_date');
            const endDateParam = urlParams.get('end_date');
            
            // Determine date format based on range
            let dateFormat = 'MMM dd HH:mm';
            let tooltipDateFormat = 'MMM dd, yyyy HH:mm';
            if (range === '30d' || (startDateParam && endDateParam)) {
                const daysDiff = startDateParam && endDateParam 
                    ? Math.abs((new Date(endDateParam) - new Date(startDateParam)) / (1000 * 60 * 60 * 24))
                    : 30;
                if (daysDiff > 30) {
                    dateFormat = 'MMM dd';
                    tooltipDateFormat = 'MMM dd, yyyy';
                }
            }
            
            // Format Response Time data
            const responseTimeChartData = (responseTimeData || []).map(item => {
                try {
                    const timestamp = new Date(item.x).getTime();
                    if (isNaN(timestamp)) {
                        console.warn('Invalid timestamp:', item.x);
                        return null;
                    }
                    return {
                        x: timestamp,
                        y: item.y !== null && item.y !== undefined ? parseFloat(item.y) : null
                    };
                } catch (e) {
                    console.error('Error parsing response time data point:', item, e);
                    return null;
                }
            }).filter(item => item !== null && !isNaN(item.x) && (item.y === null || !isNaN(item.y)));
            
            // Check if we have any data
            if (responseTimeChartData.length === 0) {
                document.querySelector("#performance-overview-chart").innerHTML = 
                    '<div class="text-center py-5"><p class="text-muted">No performance data available for the selected date range</p></div>';
                return;
            }
            
            // Single-series chart (Response Time only)
            const options = {
                series: [{
                    name: 'Response Time',
                    type: 'line',
                    data: responseTimeChartData
                }],
                chart: {
                    toolbar: {
                        show: false
                    },
                    type: 'line',
                    height: 300,
                    fontFamily: 'Poppins, Arial, sans-serif',
                    background: 'transparent',
                    zoom: {
                        enabled: false
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: "smooth",
                    width: 3,
                    lineCap: "round",
                    colors: ['#5d87ff']
                },
                colors: ['#5d87ff'],
                markers: {
                    size: 3,
                    strokeWidth: 2,
                    strokeColors: ['#5d87ff'],
                    fillColors: ['#ffffff'],
                    hover: {
                        size: 5,
                        sizeOffset: 2
                    }
                },
                grid: {
                    show: true,
                    borderColor: 'rgba(119, 119, 142, 0.1)',
                    strokeDashArray: 3,
                    xaxis: {
                        lines: {
                            show: true
                        }
                    },
                    yaxis: {
                        lines: {
                            show: false
                        }
                    },
                    padding: {
                        top: 10,
                        right: 10,
                        bottom: 0,
                        left: 0
                    }
                },
                legend: {
                    show: false
                },
                xaxis: {
                    type: 'datetime',
                    axisBorder: {
                        show: true,
                        color: "rgba(119, 119, 142, 0.1)",
                        offsetX: 0,
                        offsetY: 0,
                        strokeWidth: 1
                    },
                    axisTicks: {
                        show: true,
                        color: "rgba(119, 119, 142, 0.1)",
                        height: 6
                    },
                    labels: {
                        style: {
                            colors: '#8c9097',
                            fontSize: '11px',
                            fontFamily: 'Poppins, sans-serif',
                            fontWeight: 500
                        },
                        datetimeUTC: false,
                        format: dateFormat,
                        rotate: 0,
                        rotateAlways: false
                    },
                    tooltip: {
                        enabled: false
                    }
                },
                yaxis: [{
                    title: {
                        text: 'Response Time (ms)',
                        style: {
                            color: '#5d87ff',
                            fontSize: '12px',
                            fontFamily: 'Poppins, sans-serif',
                            fontWeight: 600,
                        },
                        offsetX: 0,
                        offsetY: 0,
                        rotate: -90
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    },
                    labels: {
                        show: true,
                        style: {
                            colors: '#5d87ff',
                            fontSize: '11px',
                            fontFamily: 'Poppins, sans-serif',
                            fontWeight: 500
                        },
                        formatter: function (val) {
                            return val ? val.toFixed(0) : "";
                        },
                        offsetX: -5
                    }
                }],
                tooltip: {
                    enabled: true,
                    shared: true,
                    intersect: false,
                    followCursor: true,
                    fillSeriesColor: false,
                    theme: 'light',
                    style: {
                        fontSize: '12px',
                        fontFamily: 'Poppins, sans-serif'
                    },
                    x: {
                        show: true,
                        format: tooltipDateFormat,
                        formatter: undefined
                    },
                    y: {
                        formatter: function (val, { seriesIndex }) {
                            if (seriesIndex === 0) {
                            return val ? val.toFixed(2) + " ms" : "N/A";
                            } else {
                                return val ? val.toFixed(2) + "%" : "N/A";
                            }
                        },
                        title: {
                            formatter: function (seriesName) {
                                return seriesName + ": ";
                            }
                        }
                    },
                    marker: {
                        show: true
                    },
                    fixed: {
                        enabled: false,
                        position: 'topRight',
                        offsetX: 0,
                        offsetY: 0
                    }
                }
            };
            performanceOverviewChart = new ApexCharts(document.querySelector("#performance-overview-chart"), options);
            performanceOverviewChart.render();
        }

        // Status Distribution Chart
        let statusDistributionChart;
        function initStatusDistributionChart() {
            const options = {
                series: statusDistribution.map(item => item.value),
                chart: {
                    type: 'donut',
                    height: 300,
                    fontFamily: 'Poppins, Arial, sans-serif'
                },
                colors: statusDistribution.map(item => item.color),
                labels: statusDistribution.map(item => item.label),
                legend: {
                    position: 'bottom'
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return val.toFixed(1) + "%";
                    }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%'
                        }
                    }
                }
            };
            statusDistributionChart = new ApexCharts(document.querySelector("#status-distribution-chart"), options);
            statusDistributionChart.render();
        }


        // Update chart time range
        function updateChartTimeRange(range, event) {
            // Update button states
            $('.btn-group button').removeClass('btn-primary').addClass('btn-primary-light');
            if (event && event.target) {
                $(event.target).removeClass('btn-primary-light').addClass('btn-primary');
            } else {
                // Fallback: find button by range
                $('.btn-group button').each(function() {
                    if ($(this).text().trim() === range.toUpperCase()) {
                        $(this).removeClass('btn-primary-light').addClass('btn-primary');
                    }
                });
            }
            
            // Show loading state
            const chartContainer = document.querySelector("#performance-overview-chart");
            chartContainer.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            
        // Fetch new chart data
            $.ajax({
                url: '{{ route("uptime-monitors.chart-data", $monitor->uid) }}',
                method: 'GET',
                data: { range: range },
                dataType: 'json',
                success: function(data) {
                    // Format Response Time data
                    const responseTimeChartData = data.responseTimeData.map(item => {
                        const timestamp = new Date(item.x).getTime();
                        return {
                            x: timestamp,
                            y: item.y
                        };
                    }).filter(item => item.y !== null && item.y !== undefined && !isNaN(item.x));
                    
                    // Update chart data and x-axis format
                    if (performanceOverviewChart) {
                        // Determine date format based on range
                        let dateFormat = 'MMM dd HH:mm';
                        if (range === '30d') {
                            dateFormat = 'MMM dd';
                        }
                        
                        performanceOverviewChart.updateSeries([{
                            name: 'Response Time',
                            type: 'line',
                            data: responseTimeChartData
                        }]);
                        
                        // Update x-axis format
                        performanceOverviewChart.updateOptions({
                xaxis: {
                    labels: {
                                    format: dateFormat
                                }
                            }
                        });
                    } else {
                        // Reinitialize chart if it doesn't exist
                        initPerformanceOverviewChart();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Failed to fetch chart data:', error);
                    chartContainer.innerHTML = '<div class="text-center py-5"><p class="text-danger">Failed to load chart data. Please try again.</p></div>';
                }
            });
        }

        // Show loading overlay (global function)
        window.showLoadingOverlay = function() {
            const overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;';
            overlay.innerHTML = '<div class="text-center text-white"><div class="spinner-border mb-3" role="status"></div><div>Loading data...</div></div>';
            document.body.appendChild(overlay);
        };

        // Open custom range modal (global function)
        window.openCustomRangeModal = function(event) {
            if (event) {
                event.preventDefault();
            }
            
            // Set default dates based on current selection
            const urlParams = new URLSearchParams(window.location.search);
            const startDateParam = urlParams.get('start_date');
            const endDateParam = urlParams.get('end_date');
            
            const endDate = endDateParam ? new Date(endDateParam) : new Date();
            const startDate = startDateParam ? new Date(startDateParam) : new Date();
            if (!startDateParam) {
                startDate.setDate(startDate.getDate() - 7);
            }
            
            document.getElementById('custom-start-date').value = startDate.toISOString().split('T')[0];
            document.getElementById('custom-end-date').value = endDate.toISOString().split('T')[0];
            
            // Show modal
            const modalElement = document.getElementById('customRangeModal');
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        };

        // Apply custom date range (global function)
        window.applyCustomRange = function() {
            const startDate = document.getElementById('custom-start-date').value;
            const endDate = document.getElementById('custom-end-date').value;
            
            if (!startDate || !endDate) {
                alert('Please select both start and end dates');
                return;
            }
            
            if (new Date(startDate) > new Date(endDate)) {
                alert('Start date must be before end date');
                return;
            }
            
            // Close modal
            const modalElement = document.getElementById('customRangeModal');
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
            }
            
            // Show loading overlay
            window.showLoadingOverlay();
            
            // Build URL with custom range
            let url = '{{ route("uptime-monitors.show", $monitor->uid) }}';
            url += '?range=custom&start_date=' + startDate + '&end_date=' + endDate;
            
            // Reload page with custom range
            window.location.href = url;
        };

        // Update page with date range from flatpickr (global function)
        window.updatePageWithDateRange = function(startDate, endDate) {
            if (!startDate || !endDate) {
                return; // Don't update if dates are not selected
            }
            
            // Show loading overlay
            window.showLoadingOverlay();
            
            // Build URL with custom range
            let url = '{{ route("uptime-monitors.show", $monitor->uid) }}';
            url += '?range=custom&start_date=' + startDate + '&end_date=' + endDate;
            
            // Reload page with custom range
            window.location.href = url;
        };

        // Auto-refresh functionality
        let autoRefreshEnabled = true; // Enabled by default
        let autoRefreshTimer = null;
        const refreshInterval = 60000; // 1 minute in milliseconds

        // Check localStorage for user preference
        const storedPreference = localStorage.getItem('uptimeMonitorAutoRefresh');
        if (storedPreference !== null) {
            autoRefreshEnabled = storedPreference === 'true';
        }

        // Function to refresh the page
        function refreshPage() {
            if (autoRefreshEnabled) {
                window.location.reload();
            }
        }

        // Start auto-refresh timer
        function startAutoRefresh() {
            if (autoRefreshTimer) {
                clearInterval(autoRefreshTimer);
            }
            if (autoRefreshEnabled) {
                autoRefreshTimer = setInterval(refreshPage, refreshInterval);
            }
        }

        // Stop auto-refresh timer
        function stopAutoRefresh() {
            if (autoRefreshTimer) {
                clearInterval(autoRefreshTimer);
                autoRefreshTimer = null;
            }
        }

        // Update button state
        function updateButtonState() {
            if (autoRefreshEnabled) {
                $('#toggle-autorefresh').removeClass('btn-light').addClass('btn-primary');
                $('#toggle-autorefresh').attr('title', 'Disable Auto-Refresh');
                startAutoRefresh();
            } else {
                $('#toggle-autorefresh').removeClass('btn-primary').addClass('btn-light');
                $('#toggle-autorefresh').attr('title', 'Enable Auto-Refresh');
                stopAutoRefresh();
            }
        }

        // Toggle auto-refresh
        $('#toggle-autorefresh').on('click', function() {
            autoRefreshEnabled = !autoRefreshEnabled;
            localStorage.setItem('uptimeMonitorAutoRefresh', autoRefreshEnabled.toString());
            updateButtonState();
        });

        $(document).ready(function() {
            // Initialize auto-refresh
            updateButtonState();

            // Initialize charts (always initialize, even with empty data)
            initPerformanceOverviewChart();
            initStatusDistributionChart();

            // Get current date range from URL or default to 24h
            const urlParams = new URLSearchParams(window.location.search);
            const currentRange = urlParams.get('range') || '24h';
            
            // If no range parameter in URL, update URL to reflect 24h default
            if (!urlParams.has('range')) {
                urlParams.set('range', '24h');
                const newUrl = window.location.pathname + '?' + urlParams.toString();
                window.history.replaceState({}, '', newUrl);
            }
            const startDate = urlParams.get('start_date') || null;
            const endDate = urlParams.get('end_date') || null;
            
            // Initialize DataTables with server-side processing for Checks
            $('#checks-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: '{{ route("uptime-monitors.checks-data", $monitor->uid) }}',
                    type: 'GET',
                    data: function(d) {
                        // Add date range parameters to DataTables request
                        if (startDate) {
                            d.start_date = startDate;
                        }
                        if (endDate) {
                            d.end_date = endDate;
                        }
                    }
                },
                columns: [
                    { data: 'row_number', name: 'row_number', orderable: false, searchable: false },
                    { 
                        data: 'status', 
                        name: 'status',
                        render: function(data) {
                            if (data === 'up') {
                                return '<span class="badge bg-success-transparent text-success"><i class="ri-checkbox-circle-line me-1"></i>Up</span>';
                            } else {
                                return '<span class="badge bg-danger-transparent text-danger"><i class="ri-close-circle-line me-1"></i>Down</span>';
                            }
                        }
                    },
                    { 
                        data: 'response_time', 
                        name: 'response_time',
                        render: function(data) {
                            return data ? '<span class="fw-semibold">' + data + 'ms</span>' : '<span class="text-muted">N/A</span>';
                        }
                    },
                    { 
                        data: 'status_code', 
                        name: 'status_code',
                        render: function(data) {
                            return data ? '<span class="badge bg-info-transparent text-info">' + data + '</span>' : '<span class="text-muted">N/A</span>';
                        }
                    },
                    { 
                        data: 'error_message', 
                        name: 'error_message',
                        render: function(data) {
                            if (data) {
                                const truncated = data.length > 50 ? data.substring(0, 50) + '...' : data;
                                return '<span class="text-danger" title="' + data + '">' + truncated + '</span>';
                            }
                            return '<span class="text-muted">-</span>';
                        }
                    },
                    { 
                        data: 'checked_at', 
                        name: 'checked_at',
                        render: function(data, type, row) {
                            return '<span class="text-muted">' + row.checked_at_human + '</span>';
                        }
                    }
                ],
                order: [[5, 'desc']],
                pageLength: 5,
                lengthMenu: [[5], [5]], // Only show 5 records option
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
                }
            });

            // Initialize DataTables with server-side processing for Alerts
            $('#alerts-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: '{{ route("uptime-monitors.alerts-data", $monitor->uid) }}',
                    type: 'GET',
                    data: function(d) {
                        // Add date range parameters to DataTables request
                        if (startDate) {
                            d.start_date = startDate;
                        }
                        if (endDate) {
                            d.end_date = endDate;
                        }
                    }
                },
                columns: [
                    { data: 'row_number', name: 'row_number', orderable: false, searchable: false },
                    { 
                        data: 'alert_type', 
                        name: 'alert_type',
                        render: function(data) {
                            if (data === 'down') {
                                return '<span class="badge bg-danger-transparent text-danger"><i class="ri-arrow-down-line me-1"></i>Down</span>';
                            } else if (data === 'up') {
                                return '<span class="badge bg-success-transparent text-success"><i class="ri-arrow-up-line me-1"></i>Up</span>';
                            } else {
                                return '<span class="badge bg-info-transparent text-info"><i class="ri-refresh-line me-1"></i>Recovery</span>';
                            }
                        }
                    },
                    { 
                        data: 'communication_channel', 
                        name: 'communication_channel',
                        render: function(data) {
                            return '<span class="text-capitalize">' + data + '</span>';
                        }
                    },
                    { 
                        data: 'status', 
                        name: 'status',
                        render: function(data) {
                            if (data === 'sent') {
                                return '<span class="badge bg-success-transparent text-success"><i class="ri-check-line me-1"></i>Sent</span>';
                            } else if (data === 'failed') {
                                return '<span class="badge bg-danger-transparent text-danger"><i class="ri-close-line me-1"></i>Failed</span>';
                            } else {
                                return '<span class="badge bg-warning-transparent text-warning"><i class="ri-time-line me-1"></i>Pending</span>';
                            }
                        }
                    },
                    { 
                        data: 'message', 
                        name: 'message',
                        render: function(data) {
                            if (data) {
                                const truncated = data.length > 50 ? data.substring(0, 50) + '...' : data;
                                return '<span title="' + data + '">' + truncated + '</span>';
                            }
                            return '<span class="text-muted">-</span>';
                        }
                    },
                    { 
                        data: 'sent_at', 
                        name: 'sent_at',
                        render: function(data, type, row) {
                            if (data) {
                                return '<span class="text-muted">' + data + '</span><small class="d-block text-muted">' + (row.sent_at_human || '') + '</small>';
                            }
                            return '<span class="text-muted">-</span>';
                        }
                    }
                ],
                order: [[5, 'desc']],
                pageLength: 5,
                lengthMenu: [[5], [5]], // Only show 5 records option
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
                }
            });

            // Initialize flatpickr date range picker
            if (typeof flatpickr !== 'undefined') {
                const urlParams = new URLSearchParams(window.location.search);
                const range = urlParams.get('range') || '24h';
                const startDateParam = urlParams.get('start_date');
                const endDateParam = urlParams.get('end_date');
                
                let defaultStartDate, defaultEndDate;
                
                // Set default dates based on current range
                if (range === 'custom' && startDateParam && endDateParam) {
                    defaultStartDate = startDateParam;
                    defaultEndDate = endDateParam;
                } else if (range === '24h') {
                    const now = new Date();
                    const endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                    const startDate = new Date(endDate);
                    startDate.setDate(startDate.getDate() - 1);
                    defaultStartDate = startDate.toISOString().split('T')[0];
                    defaultEndDate = endDate.toISOString().split('T')[0];
                } else if (range === '7d') {
                    const now = new Date();
                    const endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                    const startDate = new Date(endDate);
                    startDate.setDate(startDate.getDate() - 7);
                    defaultStartDate = startDate.toISOString().split('T')[0];
                    defaultEndDate = endDate.toISOString().split('T')[0];
                } else if (range === '30d') {
                    const now = new Date();
                    const endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                    const startDate = new Date(endDate);
                    startDate.setDate(startDate.getDate() - 30);
                    defaultStartDate = startDate.toISOString().split('T')[0];
                    defaultEndDate = endDate.toISOString().split('T')[0];
                } else {
                    // All time - set to last 30 days as default display
                    const now = new Date();
                    const endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                    const startDate = new Date(endDate);
                    startDate.setDate(startDate.getDate() - 30);
                    defaultStartDate = startDate.toISOString().split('T')[0];
                    defaultEndDate = endDate.toISOString().split('T')[0];
                }

                // Function to update the input display with formatted date range
                function updateInputDisplay(dates, instance) {
                    if (dates && dates.length === 2) {
                        const startDateFormatted = formatDate(dates[0]);
                        const endDateFormatted = formatDate(dates[1]);
                        instance.input.value = `${startDateFormatted} to ${endDateFormatted}`;
                    } else {
                        instance.input.value = ''; // Clear value if less than 2 dates
                    }
                }

                function formatDate(date) {
                    if (!date) return '';
                    const d = new Date(date);
                    if (isNaN(d.getTime())) return '';
                    const day = String(d.getDate()).padStart(2, '0');
                    const month = d.toLocaleString('default', { month: 'short' });
                    const year = d.getFullYear();
                    return `${day}, ${month} ${year}`;
                }

                flatpickr("#daterange", {
                    mode: "range",
                    dateFormat: "Y-m-d",
                    defaultDate: [defaultStartDate, defaultEndDate],
                    onReady: function (selectedDates, dateStr, instance) {
                        // Use the default dates for initial display
                        const defaultDates = [new Date(defaultStartDate), new Date(defaultEndDate)];
                        updateInputDisplay(defaultDates, instance);
                    },
                    onChange: function (selectedDates, dateStr, instance) {
                        updateInputDisplay(selectedDates, instance);
                        // Don't auto-reload on change - let user confirm selection
                        // The page will reload when they click outside or close the picker
                    },
                    onClose: function (selectedDates, dateStr, instance) {
                        // Reload page when picker is closed with a valid date range
                        if (selectedDates.length === 2) {
                            const startDate = selectedDates[0].toISOString().split('T')[0];
                            const endDate = selectedDates[1].toISOString().split('T')[0];
                            window.updatePageWithDateRange(startDate, endDate);
                        }
                    }
                });
            }
        });
    </script>
@endsection
