@extends('layouts.master')

@section('styles')
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        /* Status Timeline Styles */
        .status-timeline-container {
            position: relative;
        }
        
        .status-timeline {
            display: flex;
            gap: 2px;
            height: 30px;
            position: relative;
        }
        
        .status-bar {
            flex: 1;
            height: 100%;
            cursor: pointer;
            border-radius: 2px;
            transition: all 0.2s;
        }
        
        .status-bar:hover {
            opacity: 0.8;
        }
        
        #timeline-tooltip {
            line-height: 1.4;
        }
        
        #timeline-tooltip .mb-1 {
            margin-bottom: 4px !important;
        }
        
        #timeline-tooltip > div:last-child {
            margin-bottom: 0 !important;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .status-timeline {
                gap: 2px !important;
                height: 24px !important;
            }
        }
        
        @media (max-width: 576px) {
            .status-timeline {
                gap: 1px !important;
                height: 20px !important;
            }
        }
        
        /* SweetAlert2 Modal Fix - Ensure modals appear on top */
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
            <h1 class="page-title fw-medium fs-18 mb-0">{{ $monitor->name }}</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('panel.api-monitors.index') }}">API Monitoring</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $monitor->name }}</li>
            </ol>
        </div>
        <div class="btn-list mt-2">
            <button type="button" id="test-now-btn" class="btn btn-success btn-wave btn-sm">
                <i class="ri-play-line me-1"></i>Test Now
            </button>
            <a href="{{ route('panel.api-monitors.edit', $monitor) }}" class="btn btn-primary btn-wave btn-sm">
                <i class="ri-edit-line me-1"></i>Edit
            </a>
            <div class="btn-group btn-sm">
                <button type="button" class="btn btn-info btn-wave dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="ri-download-line me-1"></i>Export
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('panel.api-monitors.export-checks', $monitor) }}?format=csv"><i class="ri-file-excel-line me-2"></i>Export Checks (CSV)</a></li>
                    <li><a class="dropdown-item" href="{{ route('panel.api-monitors.export-checks', $monitor) }}?format=json"><i class="ri-file-code-line me-2"></i>Export Checks (JSON)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('panel.api-monitors.export-alerts', $monitor) }}?format=csv"><i class="ri-file-excel-line me-2"></i>Export Alerts (CSV)</a></li>
                    <li><a class="dropdown-item" href="{{ route('panel.api-monitors.export-alerts', $monitor) }}?format=json"><i class="ri-file-code-line me-2"></i>Export Alerts (JSON)</a></li>
                </ul>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Status Cards -->
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
                            <span class="fs-12 d-block text-muted">
                                @if($monitor->last_checked_at)
                                    Last checked {{ $monitor->last_checked_at->diffForHumans() }}
                                @else
                                    Never checked
                                @endif
                            </span>
                        </div>
                        <div>
                            <span class="avatar avatar-md bg-{{ $monitor->status === 'up' ? 'success' : ($monitor->status === 'down' ? 'danger' : 'secondary') }}-transparent">
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
                            <span class="fs-13 fw-medium">Total Checks</span>
                            <h4 class="fw-semibold my-2 lh-1">{{ $totalChecks }}</h4>
                            <span class="fs-12 d-block text-muted">
                                {{ $upChecks }} up / {{ $downChecks }} down
                            </span>
                        </div>
                        <div>
                            <span class="avatar avatar-md bg-primary-transparent">
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
                            <h4 class="fw-semibold my-2 lh-1">
                                {{ $avgResponseTime ? number_format($avgResponseTime, 0) : 'N/A' }}ms
                            </h4>
                            <span class="fs-12 d-block text-muted">
                                @if($monitor->max_latency_ms)
                                    Max allowed: {{ $monitor->max_latency_ms }}ms
                                @else
                                    No limit set
                                @endif
                            </span>
                        </div>
                        <div>
                            <span class="avatar avatar-md bg-warning-transparent">
                                <i class="ri-time-line fs-24"></i>
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
                            <span class="fs-13 fw-medium">Alerts</span>
                            <h4 class="fw-semibold my-2 lh-1">{{ $totalAlerts }}</h4>
                            <span class="fs-12 d-block text-muted">
                                Total alerts sent
                            </span>
                        </div>
                        <div>
                            <span class="avatar avatar-md bg-info-transparent">
                                <i class="ri-notification-line fs-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Uptime Status Visual -->
    <div class="row mt-3">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-{{ $monitor->status === 'up' ? 'success' : ($monitor->status === 'down' ? 'danger' : 'secondary') }}-transparent text-{{ $monitor->status === 'up' ? 'success' : ($monitor->status === 'down' ? 'danger' : 'secondary') }} rounded-circle" style="width: 8px; height: 8px; padding: 0;"></span>
                                <h5 class="mb-0 fw-semibold">{{ $monitor->name }}</h5>
                            </div>
                            <i class="ri-global-line text-{{ $monitor->status === 'up' ? 'success' : ($monitor->status === 'down' ? 'danger' : 'secondary') }} fs-16" title="API Monitor"></i>
                        </div>
                        <div class="text-end">
                            <span class="text-muted fs-13">{{ number_format($uptimeData['uptime_percentage'], 2) }}% uptime</span>
                        </div>
                    </div>
                    <div class="status-timeline-container" style="position: relative;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted fs-12">Last {{ $uptimeData['period_days'] }} days</span>
                            <span class="text-muted fs-12">Today</span>
                        </div>
                        <div class="status-timeline" id="status-timeline" style="display: flex; gap: 2px; height: 30px; position: relative;">
                            @foreach($uptimeData['daily_status'] as $day)
                                @php
                                    $statusClass = match($day['status']) {
                                        'up' => 'bg-success',
                                        'down' => 'bg-danger',
                                        'partial' => 'bg-warning',
                                        default => 'bg-secondary'
                                    };
                                    $isToday = $day['date'] === now()->format('Y-m-d');
                                    $uptimePercent = $day['status'] === 'unknown' ? 'N/A' : ($day['total_count'] > 0 ? number_format(($day['up_count'] / $day['total_count']) * 100, 2) : '0.00');
                                    $incidents = $day['down_count'] > 0 ? 1 : 0;
                                    $downtime = 0; // We don't track downtime in minutes for API monitors
                                @endphp
                                <div 
                                    class="status-bar {{ $statusClass }}" 
                                    style="flex: 1; height: 100%; cursor: pointer; border-radius: 2px; transition: all 0.2s;{{ $isToday ? ' border: 2px solid #000; box-sizing: border-box;' : '' }}"
                                    data-date="{{ $day['date'] }}"
                                    data-uptime="{{ $uptimePercent }}"
                                    data-incidents="{{ $incidents }}"
                                    data-downtime="{{ $downtime }}"
                                    data-total-checks="{{ $day['total_count'] }}"
                                    data-up-count="{{ $day['up_count'] }}"
                                    data-down-count="{{ $day['down_count'] }}"
                                    onmouseover="showTimelineTooltip(event, this)"
                                    onmouseout="hideTimelineTooltip()"
                                ></div>
                            @endforeach
                        </div>
                        <div id="timeline-tooltip" style="position: absolute; background: rgb(0, 0, 0); color: rgb(255, 255, 255); padding: 8px 12px; border-radius: 4px; font-size: 12px; pointer-events: none; z-index: 1000; display: none; white-space: pre-line; box-shadow: rgba(0, 0, 0, 0.2) 0px 2px 8px;"></div>
                    </div>
                    <div class="d-flex align-items-center gap-3 mt-3 pt-3 border-top">
                        <div class="d-flex align-items-center gap-1">
                            <div class="bg-success rounded-circle" style="width: 12px; height: 12px;"></div>
                            <span class="text-muted fs-12">Up</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <div class="bg-warning rounded-circle" style="width: 12px; height: 12px;"></div>
                            <span class="text-muted fs-12">Partial</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <div class="bg-danger rounded-circle" style="width: 12px; height: 12px;"></div>
                            <span class="text-muted fs-12">Down</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <div class="bg-secondary rounded-circle" style="width: 12px; height: 12px;"></div>
                            <span class="text-muted fs-12">No Data</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    @if($responseTimeStats && $responseTimeStats->total_checks > 0)
    <div class="row mt-3">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Performance Metrics</div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <div class="text-center">
                                <span class="fs-12 text-muted d-block mb-1">Min Response</span>
                                <h5 class="fw-semibold mb-0">{{ number_format($responseTimeStats->min_response_time, 0) }}ms</h5>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <div class="text-center">
                                <span class="fs-12 text-muted d-block mb-1">Max Response</span>
                                <h5 class="fw-semibold mb-0">{{ number_format($responseTimeStats->max_response_time, 0) }}ms</h5>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <div class="text-center">
                                <span class="fs-12 text-muted d-block mb-1">Average</span>
                                <h5 class="fw-semibold mb-0">{{ number_format($responseTimeStats->avg_response_time, 0) }}ms</h5>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <div class="text-center">
                                <span class="fs-12 text-muted d-block mb-1">P50 (Median)</span>
                                <h5 class="fw-semibold mb-0">{{ $percentiles['p50'] ? number_format($percentiles['p50'], 0) . 'ms' : 'N/A' }}</h5>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <div class="text-center">
                                <span class="fs-12 text-muted d-block mb-1">P95</span>
                                <h5 class="fw-semibold mb-0">{{ $percentiles['p95'] ? number_format($percentiles['p95'], 0) . 'ms' : 'N/A' }}</h5>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <div class="text-center">
                                <span class="fs-12 text-muted d-block mb-1">P99</span>
                                <h5 class="fw-semibold mb-0">{{ $percentiles['p99'] ? number_format($percentiles['p99'], 0) . 'ms' : 'N/A' }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Charts -->
    <div class="row mt-3">
        <!-- Response Time Chart -->
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">Response Time Trend</div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-primary btn-wave chart-range-btn" data-range="24h">24H</button>
                        <button type="button" class="btn btn-primary-light btn-wave chart-range-btn" data-range="7d">7D</button>
                        <button type="button" class="btn btn-primary-light btn-wave chart-range-btn" data-range="30d">30D</button>
                    </div>
                </div>
                <div class="card-body pb-0 pt-5">
                    <div id="response-time-chart"></div>
                </div>
            </div>
        </div>
        <!-- Status Distribution Chart -->
        <div class="col-xl-4">
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

    <!-- Dependency Mapping & Root Cause Analysis -->
    @if(!empty($dependencyTree['dependencies']) || !empty($dependencyTree['dependents']))
    <div class="row mt-3">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="ri-node-tree me-2"></i>Dependency Mapping (Auto-Discovered)
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary" onclick="refreshDependencyTree()">
                            <i class="ri-refresh-line me-1"></i>Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Root Cause Analysis -->
                    @if($rootCause['root'] && $monitor->status === 'down')
                    <div class="alert alert-warning mb-4">
                        <h6 class="alert-heading"><i class="ri-alert-line me-1"></i>Root Cause Analysis</h6>
                        <p class="mb-2"><strong>Root Cause:</strong> {{ $rootCause['root']->name }}</p>
                        @if(!empty($rootCause['chain']))
                        <p class="mb-2"><strong>Failure Chain:</strong></p>
                        <ul class="mb-0">
                            @foreach($rootCause['chain'] as $link)
                            <li>{{ $link['monitor'] }} → depends on → {{ $link['depends_on'] }} ({{ $link['status'] }})</li>
                            @endforeach
                        </ul>
                        @endif
                        @if(!empty($rootCause['affected_services']))
                        <p class="mb-0 mt-2"><strong>Affected Services:</strong> {{ count($rootCause['affected_services']) }} service(s) depend on this monitor</p>
                        @endif
                    </div>
                    @endif

                    <!-- Dependency Tree Visualization -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3"><i class="ri-arrow-down-line me-1"></i>Dependencies (This API depends on)</h6>
                            @if(!empty($dependencyTree['dependencies']))
                                <div id="dependencies-tree">
                                    {!! renderDependencyTree($dependencyTree['dependencies'], 0) !!}
                                </div>
                            @else
                                <p class="text-muted">No dependencies discovered yet. Dependencies are auto-discovered from API responses and error messages.</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3"><i class="ri-arrow-up-line me-1"></i>Dependents (Services that depend on this API)</h6>
                            @if(!empty($dependencyTree['dependents']))
                                <ul class="list-group">
                                    @foreach($dependencyTree['dependents'] as $dependent)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $dependent['name'] }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $dependent['url'] }}</small>
                                        </div>
                                        <span class="badge bg-{{ $dependent['status'] === 'up' ? 'success' : ($dependent['status'] === 'down' ? 'danger' : 'secondary') }}-transparent">
                                            {{ strtoupper($dependent['status']) }}
                                        </span>
                                    </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-muted">No other services depend on this API.</p>
                            @endif
                        </div>
                    </div>

                    <!-- Dependency Details Table -->
                    @php
                        $dependencies = $monitor->dependencies()->with('dependsOnMonitor')->where('is_confirmed', true)->get();
                    @endphp
                    @if($dependencies->count() > 0)
                    <div class="mt-4">
                        <h6 class="mb-3">Dependency Details</h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Dependency</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Confidence</th>
                                        <th>Suppress Alerts</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dependencies as $dep)
                                    <tr>
                                        <td>
                                            <strong>{{ $dep->dependency_name }}</strong>
                                            @if($dep->dependsOnMonitor)
                                                <br><small class="text-muted">{{ $dep->dependsOnMonitor->url }}</small>
                                            @elseif($dep->dependency_url)
                                                <br><small class="text-muted">{{ $dep->dependency_url }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $dep->dependency_type === 'api' ? 'primary' : ($dep->dependency_type === 'database' ? 'info' : 'secondary') }}-transparent">
                                                {{ ucfirst($dep->dependency_type) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($dep->dependsOnMonitor)
                                                <span class="badge bg-{{ $dep->dependsOnMonitor->status === 'up' ? 'success' : ($dep->dependsOnMonitor->status === 'down' ? 'danger' : 'secondary') }}-transparent">
                                                    {{ strtoupper($dep->dependsOnMonitor->status) }}
                                                </span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-{{ $dep->confidence_score >= 80 ? 'success' : ($dep->confidence_score >= 60 ? 'warning' : 'danger') }}" 
                                                     role="progressbar" 
                                                     style="width: {{ $dep->confidence_score }}%"
                                                     aria-valuenow="{{ $dep->confidence_score }}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    {{ $dep->confidence_score }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" 
                                                       {{ $dep->suppress_child_alerts ? 'checked' : '' }}
                                                       onchange="toggleSuppressAlerts({{ $dep->id }}, this.checked)">
                                            </div>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="removeDependency({{ $dep->id }})">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- Unconfirmed Dependencies -->
                    @php
                        $unconfirmedDeps = $monitor->dependencies()->where('is_confirmed', false)->get();
                    @endphp
                    @if($unconfirmedDeps->count() > 0)
                    <div class="mt-4">
                        <h6 class="mb-3">Suggested Dependencies (Auto-Discovered)</h6>
                        <div class="alert alert-info">
                            <p class="mb-2">The following dependencies were auto-discovered but need confirmation:</p>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Dependency</th>
                                            <th>Type</th>
                                            <th>Confidence</th>
                                            <th>Evidence</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($unconfirmedDeps as $dep)
                                        <tr>
                                            <td><strong>{{ $dep->dependency_name }}</strong></td>
                                            <td>
                                                <span class="badge bg-{{ $dep->dependency_type === 'api' ? 'primary' : 'info' }}-transparent">
                                                    {{ ucfirst($dep->dependency_type) }}
                                                </span>
                                            </td>
                                            <td>{{ $dep->confidence_score }}%</td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ implode(', ', array_slice($dep->discovery_evidence ?? [], 0, 2)) }}
                                                </small>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-success" onclick="confirmDependency({{ $dep->id }})">
                                                    <i class="ri-check-line me-1"></i>Confirm
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="removeDependency({{ $dep->id }})">
                                                    <i class="ri-close-line"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Start::row-4 - Monitor Details & History -->
    <div class="row mt-3">
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
                            @if($monitor->is_stateful)
                                <span class="badge bg-primary-transparent text-primary fs-11" title="Stateful Monitoring">
                                    <i class="ri-flow-chart me-1"></i>Stateful
                                </span>
                            @endif
                            @if($monitor->auto_auth_enabled)
                                <span class="badge bg-warning-transparent text-warning fs-11" title="Auto-Auth Enabled">
                                    <i class="ri-key-line me-1"></i>Auto-Auth
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
                                <div class="fw-semibold fs-14">API / REST</div>
                            </div>
                            @if($monitor->url)
                            <div class="col-12">
                                <label class="form-label text-muted mb-1">URL</label>
                                <div>
                                    <a href="{{ $monitor->url }}" target="_blank" class="text-primary fw-semibold text-break fs-14" title="{{ $monitor->url }}">
                                        {{ Str::limit($monitor->url, 50) }}
                                        <i class="ri-external-link-line ms-1"></i>
                                    </a>
                                </div>
                            </div>
                            @endif
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Method</label>
                                <div class="fw-semibold">
                                    <span class="badge bg-info-transparent text-info fs-13">{{ $monitor->request_method ?? 'GET' }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Auth Type</label>
                                <div class="fw-semibold">
                                    <span class="badge bg-secondary-transparent text-secondary fs-13">
                                        <i class="ri-shield-line me-1"></i>{{ ucfirst($monitor->auth_type ?? 'None') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced - Stacked Vertically -->
                    @if($monitor->request_headers || $monitor->request_body || $monitor->validate_response_body || $monitor->is_stateful || $monitor->auto_auth_enabled || $monitor->schema_drift_enabled)
                    <div class="mb-4">
                        <h6 class="mb-3 fw-semibold fs-15">
                            <i class="ri-tools-line me-1 text-primary"></i>Advanced
                        </h6>
                        <div class="row g-3">
                            @if($monitor->request_headers && is_array($monitor->request_headers) && count($monitor->request_headers) > 0)
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Custom Headers</label>
                                <div class="fw-semibold">
                                    <span class="badge bg-primary-transparent text-primary fs-13">
                                        <i class="ri-file-list-3-line me-1"></i>{{ count($monitor->request_headers) }}
                                    </span>
                                </div>
                            </div>
                            @endif

                            @if($monitor->request_body)
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Request Body</label>
                                <div>
                                    <span class="badge bg-success-transparent text-success fs-13">
                                        <i class="ri-file-text-line me-1"></i>Configured
                                    </span>
                                </div>
                            </div>
                            @endif

                            @if($monitor->validate_response_body)
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Response Validation</label>
                                <div>
                                    <span class="badge bg-success-transparent text-success fs-13">
                                        <i class="ri-checkbox-circle-line me-1"></i>Enabled
                                    </span>
                                </div>
                            </div>
                            @endif

                            @if($monitor->is_stateful)
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Stateful Flow</label>
                                <div>
                                    <span class="badge bg-primary-transparent text-primary fs-13">
                                        <i class="ri-flow-chart me-1"></i>{{ count($monitor->monitoring_steps ?? []) }} steps
                                    </span>
                                </div>
                            </div>
                            @endif

                            @if($monitor->auto_auth_enabled)
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Auto-Auth</label>
                                <div>
                                    <span class="badge bg-warning-transparent text-warning fs-13">
                                        <i class="ri-key-line me-1"></i>{{ ucfirst($monitor->auto_auth_type ?? 'OAuth2') }}
                                    </span>
                                </div>
                            </div>
                            @endif

                            @if($monitor->schema_drift_enabled)
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Schema Drift</label>
                                <div>
                                    <span class="badge bg-info-transparent text-info fs-13">
                                        <i class="ri-file-code-line me-1"></i>Enabled
                                    </span>
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
                                    <span class="badge bg-info-transparent text-info fs-13">{{ $monitor->expected_status_code ?? 200 }}</span>
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
                            @if($monitor->max_latency_ms)
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Max Latency</label>
                                <div class="fw-semibold fs-14">
                                    <i class="ri-speed-line me-1 text-danger"></i>{{ $monitor->max_latency_ms }}ms
                                </div>
                            </div>
                            @endif
                            @if($monitor->content_type)
                            <div class="col-6">
                                <label class="form-label text-muted mb-1">Content Type</label>
                                <div class="fw-semibold">
                                    <span class="badge bg-primary-transparent text-primary fs-13">
                                        <i class="ri-file-type-line me-1"></i>{{ Str::limit($monitor->content_type, 15) }}
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
                                <label class="form-label text-muted mb-1">Success Rate</label>
                                <div class="fw-semibold fs-14">
                                    <i class="ri-line-chart-line me-1 text-success"></i>{{ $totalChecks > 0 ? number_format(($upChecks / $totalChecks) * 100, 1) : 0 }}%
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

                    <!-- Action Buttons -->
                    <div class="border-top pt-2 mt-2">
                        <div class="d-grid gap-2">
                            <a href="{{ route('panel.api-monitors.edit', $monitor) }}" class="btn btn-primary btn-wave btn-sm">
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
                                    <th>Actions</th>
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
                    <div class="table-responsive">
                        <table id="alerts-table" class="table table-bordered text-nowrap w-100">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Message</th>
                                    <th>Sent</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via server-side processing -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Response Body Viewer Modal -->
    <div class="modal fade" id="responseBodyModal" tabindex="-1" aria-labelledby="responseBodyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="responseBodyModalLabel">Response Body</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="copy-response-btn">
                            <i class="ri-file-copy-line me-1"></i>Copy
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="format-response-btn">
                            <i class="ri-code-s-slash-line me-1"></i>Format JSON
                        </button>
                    </div>
                    <pre id="response-body-content" class="bg-light p-3 rounded" style="max-height: 500px; overflow-y: auto; font-size: 12px;"></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Result Modal -->
    <div class="modal fade" id="testResultModal" tabindex="-1" aria-labelledby="testResultModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testResultModalLabel">Test Result</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="test-result-content">
                    <!-- Test result will be loaded here -->
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
    <!-- Apex Charts JS -->
    <script src="{{asset('build/assets/libs/apexcharts/apexcharts.min.js')}}"></script>
    <!-- SweetAlert2 JS -->
    <script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>
    <script>
        // Ensure CSRF token meta tag exists
        if (!$('meta[name="csrf-token"]').length) {
            $('head').append('<meta name="csrf-token" content="{{ csrf_token() }}">');
        }

        // Setup CSRF token for all AJAX requests
        const csrfToken = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });

        // Chart data from backend
        const chartData = @json($chartData);
        let currentChartRange = '24h';
        let responseTimeChart;
        let statusDistributionChart;
        (function($) {
            $(document).ready(function() {
                // Timeline tooltip functions are defined globally below

                // Initialize Check History DataTable with server-side processing
                $('#checks-table').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: '{{ route("panel.api-monitors.checks-data", $monitor->uid) }}',
                        type: 'GET'
                    },
                    order: [[6, 'desc']],
                    pageLength: 5,
                    lengthMenu: [[5, 10, 25, 50, 100], [5, 10, 25, 50, 100]],
                    columns: [
                        { data: 'row_number', name: 'row_number', orderable: false, searchable: false },
                        { data: 'status', name: 'status', orderable: true, searchable: true },
                        { data: 'response_time', name: 'response_time', orderable: true, searchable: true },
                        { data: 'status_code', name: 'status_code', orderable: true, searchable: true },
                        { data: 'response', name: 'response', orderable: false, searchable: true },
                        { data: 'actions', name: 'actions', orderable: false, searchable: false },
                        { data: 'checked_at', name: 'checked_at', orderable: true, searchable: false }
                    ],
                    language: {
                        processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ checks",
                        infoEmpty: "Showing 0 to 0 of 0 checks",
                        infoFiltered: "(filtered from _MAX_ total checks)",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    }
                });
                
                // Initialize Alert History DataTable with server-side processing
                $('#alerts-table').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: '{{ route("panel.api-monitors.alerts-data", $monitor->uid) }}',
                        type: 'GET'
                    },
                    order: [[4, 'desc']],
                    pageLength: 5,
                    lengthMenu: [[5, 10, 25, 50, 100], [5, 10, 25, 50, 100]],
                    columns: [
                        { data: 'row_number', name: 'row_number', orderable: false, searchable: false },
                        { data: 'alert_type', name: 'alert_type', orderable: true, searchable: true },
                        { data: 'message', name: 'message', orderable: false, searchable: true },
                        { data: 'is_sent', name: 'is_sent', orderable: true, searchable: false },
                        { data: 'created_at', name: 'created_at', orderable: true, searchable: false }
                    ],
                    language: {
                        processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ alerts",
                        infoEmpty: "Showing 0 to 0 of 0 alerts",
                        infoFiltered: "(filtered from _MAX_ total alerts)",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    }
                });

                // Initialize Charts
                initResponseTimeChart();
                initStatusDistributionChart();

                // Chart range buttons
                $('.chart-range-btn').on('click', function() {
                    $('.chart-range-btn').removeClass('btn-primary').addClass('btn-primary-light');
                    $(this).removeClass('btn-primary-light').addClass('btn-primary');
                    currentChartRange = $(this).data('range');
                    updateChartRange(currentChartRange);
                });

                // Test Now button
                $('#test-now-btn').on('click', function() {
                    const btn = $(this);
                    const originalHtml = btn.html();
                    btn.prop('disabled', true).html('<i class="ri-loader-4-line me-1"></i>Testing...');

                    $.ajax({
                        url: '{{ route("panel.api-monitors.test-now", $monitor->uid) }}',
                        type: 'POST',
                        success: function(response) {
                            if (response.success) {
                                showTestResult(response.check);
                                // Reload DataTables
                                $('#checks-table').DataTable().ajax.reload(null, false);
                                // Reload page after 2 seconds to update stats
                                setTimeout(() => location.reload(), 2000);
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Test Failed',
                                    text: response.message || 'An error occurred while testing the API.',
                                    toast: true,
                                    position: 'top-end',
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                                btn.prop('disabled', false).html(originalHtml);
                            }
                        },
                        error: function(xhr) {
                            let errorMessage = 'An error occurred while testing the API.';
                            
                            if (xhr.status === 419) {
                                errorMessage = 'CSRF token mismatch. Please refresh the page and try again.';
                            } else if (xhr.responseJSON?.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseText) {
                                try {
                                    const error = JSON.parse(xhr.responseText);
                                    errorMessage = error.message || errorMessage;
                                } catch (e) {
                                    errorMessage = xhr.responseText.substring(0, 200);
                                }
                            }
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Test Failed',
                                text: errorMessage,
                                toast: true,
                                position: 'top-end',
                                timer: 4000,
                                showConfirmButton: false
                            });
                            btn.prop('disabled', false).html(originalHtml);
                        },
                        complete: function() {
                            // Reset button if not already reset
                            if (btn.prop('disabled')) {
                                btn.prop('disabled', false).html(originalHtml);
                            }
                        }
                    });
                });

                // Response body viewer
                $(document).on('click', '.view-response-btn', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Get base64 encoded response body
                    const encodedBody = $(this).attr('data-response-encoded');
                    if (!encodedBody) {
                        $('#response-body-content').text('No response body available');
                        $('#responseBodyModal').modal('show');
                        return;
                    }
                    
                    // Decode from base64
                    let responseBody;
                    try {
                        responseBody = atob(encodedBody);
                    } catch (e) {
                        $('#response-body-content').text('Error decoding response body');
                        $('#responseBodyModal').modal('show');
                        return;
                    }
                    
                    // Display the response body
                    $('#response-body-content').text(responseBody || 'No response body');
                    $('#responseBodyModal').modal('show');
                });

                // Copy response button - use event delegation within modal
                $(document).on('click', '#copy-response-btn', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const text = $('#response-body-content').text();
                    if (!text || text.trim() === '') {
                        return;
                    }
                    navigator.clipboard.writeText(text).then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Copied!',
                            text: 'Response body copied to clipboard',
                            timer: 1500,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    }).catch(() => {
                        // Fallback for older browsers
                        const textarea = document.createElement('textarea');
                        textarea.value = text;
                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textarea);
                        Swal.fire({
                            icon: 'success',
                            title: 'Copied!',
                            text: 'Response body copied to clipboard',
                            timer: 1500,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    });
                });

                // Format JSON button - use event delegation within modal
                $(document).on('click', '#format-response-btn', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    let text = $('#response-body-content').text();
                    if (!text || text.trim() === '') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Empty Response',
                            text: 'The response body is empty.',
                            toast: true,
                            position: 'top-end',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        return;
                    }
                    
                    // Trim whitespace
                    text = text.trim();
                    
                    try {
                        // Try to parse as JSON
                        const json = JSON.parse(text);
                        // Format with indentation
                        $('#response-body-content').text(JSON.stringify(json, null, 2));
                        Swal.fire({
                            icon: 'success',
                            title: 'Formatted!',
                            text: 'JSON has been formatted',
                            toast: true,
                            position: 'top-end',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } catch (e) {
                        // Check if it might be HTML-encoded JSON
                        try {
                            const decoded = $('<textarea>').html(text).text();
                            const json = JSON.parse(decoded);
                            $('#response-body-content').text(JSON.stringify(json, null, 2));
                            Swal.fire({
                                icon: 'success',
                                title: 'Formatted!',
                                text: 'JSON has been formatted',
                                toast: true,
                                position: 'top-end',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } catch (e2) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Invalid JSON',
                                text: 'The response body is not valid JSON. ' + (e.message || ''),
                                toast: true,
                                position: 'top-end',
                                timer: 3000,
                                showConfirmButton: false
                            });
                        }
                    }
                });
            });

            // Initialize Response Time Chart
            function initResponseTimeChart() {
                const data = (chartData.responseTime || []).map(item => ({
                    x: item.x,
                    y: item.y
                })).filter(item => item.y !== null);

                if (data.length === 0) {
                    document.querySelector("#response-time-chart").innerHTML = 
                        '<div class="text-center py-5"><p class="text-muted">No response time data available</p></div>';
                    return;
                }

                const options = {
                    series: [{
                        name: 'Response Time',
                        type: 'line',
                        data: data
                    }],
                    chart: {
                        type: 'line',
                        height: 300,
                        fontFamily: 'Poppins, Arial, sans-serif',
                        toolbar: { show: true },
                        zoom: { enabled: true }
                    },
                    stroke: {
                        curve: "smooth",
                        width: 3,
                        colors: ['#5d87ff']
                    },
                    colors: ['#5d87ff'],
                    markers: {
                        size: 3,
                        strokeWidth: 2,
                        strokeColors: ['#5d87ff'],
                        fillColors: ['#ffffff']
                    },
                    grid: {
                        borderColor: 'rgba(119, 119, 142, 0.1)',
                    },
                    xaxis: {
                        type: 'datetime',
                        labels: {
                            style: { colors: '#8c9097' }
                        }
                    },
                    yaxis: {
                        title: { text: 'Response Time (ms)' },
                        labels: {
                            style: { colors: '#8c9097' }
                        }
                    },
                    tooltip: {
                        x: { format: 'MMM dd, yyyy HH:mm' },
                        y: { formatter: (val) => val ? val.toFixed(0) + 'ms' : 'N/A' }
                    }
                };

                responseTimeChart = new ApexCharts(document.querySelector("#response-time-chart"), options);
                responseTimeChart.render();
            }

            // Initialize Status Distribution Chart
            function initStatusDistributionChart() {
                const distribution = chartData.statusDistribution || { up: 0, down: 0 };
                const total = distribution.up + distribution.down;

                if (total === 0) {
                    document.querySelector("#status-distribution-chart").innerHTML = 
                        '<div class="text-center py-5"><p class="text-muted">No status data available</p></div>';
                    return;
                }

                const options = {
                    series: [distribution.up, distribution.down],
                    chart: {
                        type: 'donut',
                        height: 300,
                        fontFamily: 'Poppins, Arial, sans-serif'
                    },
                    labels: ['Up', 'Down'],
                    colors: ['#28a745', '#dc3545'],
                    legend: {
                        position: 'bottom'
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '70%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: 'Total',
                                        formatter: () => total.toString()
                                    }
                                }
                            }
                        }
                    }
                };

                statusDistributionChart = new ApexCharts(document.querySelector("#status-distribution-chart"), options);
                statusDistributionChart.render();
            }

            // Update chart range
            function updateChartRange(range) {
                $.ajax({
                    url: '{{ route("panel.api-monitors.chart-data", $monitor->uid) }}',
                    type: 'GET',
                    data: { range: range },
                    success: function(data) {
                        chartData.responseTime = data.responseTime;
                        chartData.statusDistribution = data.statusDistribution;
                        if (responseTimeChart) {
                            responseTimeChart.destroy();
                        }
                        initResponseTimeChart();
                    }
                });
            }

            // Show test result
            function showTestResult(check) {
                const statusBadge = check.status === 'up' 
                    ? '<span class="badge bg-success">UP</span>'
                    : '<span class="badge bg-danger">DOWN</span>';

                let html = `
                    <div class="mb-3">
                        <strong>Status:</strong> ${statusBadge}
                    </div>
                    <div class="mb-3">
                        <strong>Response Time:</strong> ${check.response_time ? check.response_time + 'ms' : 'N/A'}
                    </div>
                    <div class="mb-3">
                        <strong>Status Code:</strong> ${check.status_code || 'N/A'}
                    </div>
                    <div class="mb-3">
                        <strong>Checked At:</strong> ${check.checked_at}
                    </div>
                `;

                if (check.error_message) {
                    html += `<div class="mb-3"><strong>Error:</strong> <span class="text-danger">${check.error_message}</span></div>`;
                }

                if (check.validation_errors && check.validation_errors.length > 0) {
                    html += `<div class="mb-3"><strong>Validation Errors:</strong><ul>`;
                    check.validation_errors.forEach(err => {
                        html += `<li class="text-warning">${err}</li>`;
                    });
                    html += `</ul></div>`;
                }

                if (check.response_body) {
                    html += `
                        <div class="mb-3">
                            <strong>Response Body:</strong>
                            <button type="button" class="btn btn-sm btn-outline-primary ms-2 view-response-btn" data-response="${escapeHtml(check.response_body)}">
                                <i class="ri-eye-line me-1"></i>View
                            </button>
                        </div>
                    `;
                }

                $('#test-result-content').html(html);
                $('#testResultModal').modal('show');
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        })(jQuery);

    // Dependency Management Functions
    function confirmDependency(depId) {
        $.ajax({
            url: '/api-monitors/dependencies/' + depId + '/confirm',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Dependency confirmed!',
                    showConfirmButton: false,
                    timer: 1500
                });
                setTimeout(() => location.reload(), 1500);
            },
            error: function() {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Failed to confirm dependency',
                    showConfirmButton: false,
                    timer: 2000
                });
            }
        });
    }

    function removeDependency(depId) {
        Swal.fire({
            title: 'Remove Dependency?',
            text: 'This will remove the dependency mapping. Are you sure?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, remove it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/api-monitors/dependencies/' + depId,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Dependency removed!',
                            showConfirmButton: false,
                            timer: 1500
                        });
                        setTimeout(() => location.reload(), 1500);
                    },
                    error: function() {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: 'Failed to remove dependency',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    }
                });
            }
        });
    }

    function toggleSuppressAlerts(depId, suppress) {
        $.ajax({
            url: '/api-monitors/dependencies/' + depId + '/toggle-suppress',
            method: 'POST',
            data: { suppress: suppress ? 1 : 0 },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Setting updated!',
                    showConfirmButton: false,
                    timer: 1500
                });
            },
            error: function() {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Failed to update setting',
                    showConfirmButton: false,
                    timer: 2000
                });
            }
        });
    }

    function refreshDependencyTree() {
        location.reload();
    }

    // Timeline tooltip functions
    function showTimelineTooltip(event, element) {
        const tooltip = document.getElementById('timeline-tooltip');
        const date = element.getAttribute('data-date');
        const uptime = element.getAttribute('data-uptime');
        const incidents = parseInt(element.getAttribute('data-incidents'));
        const downtime = parseInt(element.getAttribute('data-downtime'));
        const totalChecks = parseInt(element.getAttribute('data-total-checks'));
        const downCount = parseInt(element.getAttribute('data-down-count'));
        
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
        
        // Build tooltip content - match exact structure from uptime monitor
        let tooltipHtml = '<div class="fw-semibold mb-1">' + formattedDate + '</div>';
        tooltipHtml += '<div class="mb-1"><i class="ri-bar-chart-line me-1"></i>Uptime: ' + uptimeDisplay + '</div>';
        
        if (incidents > 0 || downCount > 0) {
            tooltipHtml += '<div><i class="ri-error-warning-line me-1 text-danger"></i>' + downCount + ' incident' + (downCount !== 1 ? 's' : '') + downtimeText + '</div>';
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
        tooltip.style.position = 'absolute';
    }

    function hideTimelineTooltip() {
        const tooltip = document.getElementById('timeline-tooltip');
        tooltip.style.display = 'none';
    }

    // Replay Failed Request
    function replayCheck(checkId) {
        Swal.fire({
            title: 'Replay Request?',
            text: 'This will replay the exact same request that failed. Continue?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, replay it!',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch(`/api-monitors/checks/${checkId}/replay`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to replay request');
                    }
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error.message}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Request Replayed!',
                    html: `
                        <p><strong>Status:</strong> <span class="badge bg-${result.value.check.status === 'up' ? 'success' : 'danger'}-transparent">${result.value.check.status.toUpperCase()}</span></p>
                        <p><strong>Status Code:</strong> ${result.value.check.status_code || 'N/A'}</p>
                        <p><strong>Response Time:</strong> ${result.value.check.response_time || 'N/A'}ms</p>
                        ${result.value.check.error_message ? `<p><strong>Error:</strong> ${result.value.check.error_message}</p>` : ''}
                        <p class="mt-3"><small>Check ID: ${result.value.check.id}</small></p>
                    `,
                    confirmButtonText: 'View Details',
                    showCancelButton: true,
                    cancelButtonText: 'Close'
                }).then((viewResult) => {
                    if (viewResult.isConfirmed) {
                        viewCheckDetails(result.value.check.id);
                    } else {
                        $('#checks-table').DataTable().ajax.reload(null, false);
                    }
                });
            }
        });
    }

    // View Check Details (Debug Mode)
    function viewCheckDetails(checkId) {
        fetch(`/api-monitors/checks/${checkId}/details`)
            .then(response => response.json())
            .then(data => {
                const check = data.check;
                
                let requestHeadersHtml = '<pre class="mb-0">';
                if (check.request_headers && Object.keys(check.request_headers).length > 0) {
                    requestHeadersHtml += JSON.stringify(check.request_headers, null, 2);
                } else {
                    requestHeadersHtml += 'No headers';
                }
                requestHeadersHtml += '</pre>';

                let responseHeadersHtml = '<pre class="mb-0">';
                if (check.response_headers && Object.keys(check.response_headers).length > 0) {
                    responseHeadersHtml += JSON.stringify(check.response_headers, null, 2);
                } else {
                    responseHeadersHtml += 'No headers';
                }
                responseHeadersHtml += '</pre>';

                let requestBodyHtml = '<pre class="mb-0">';
                if (check.request_body) {
                    try {
                        const parsed = JSON.parse(check.request_body);
                        requestBodyHtml += JSON.stringify(parsed, null, 2);
                    } catch (e) {
                        requestBodyHtml += check.request_body;
                    }
                } else {
                    requestBodyHtml += 'No request body';
                }
                requestBodyHtml += '</pre>';

                let responseBodyHtml = '<pre class="mb-0">';
                if (check.response_body) {
                    try {
                        const parsed = JSON.parse(check.response_body);
                        responseBodyHtml += JSON.stringify(parsed, null, 2);
                    } catch (e) {
                        responseBodyHtml += check.response_body;
                    }
                } else {
                    responseBodyHtml += 'No response body';
                }
                responseBodyHtml += '</pre>';

                Swal.fire({
                    title: 'Debug: Request & Response Details',
                    html: `
                        <div class="text-start">
                            <div class="mb-3">
                                <h6 class="fw-bold">Request</h6>
                                <p class="mb-1"><strong>Method:</strong> <code>${check.request_method || 'N/A'}</code></p>
                                <p class="mb-1"><strong>URL:</strong> <code>${check.request_url || 'N/A'}</code></p>
                                <p class="mb-1"><strong>Content-Type:</strong> <code>${check.request_content_type || 'N/A'}</code></p>
                                <p class="mb-1"><strong>Headers:</strong></p>
                                <div class="bg-light p-2 rounded mb-2" style="max-height: 150px; overflow-y: auto;">
                                    ${requestHeadersHtml}
                                </div>
                                <p class="mb-1"><strong>Body:</strong></p>
                                <div class="bg-light p-2 rounded mb-2" style="max-height: 200px; overflow-y: auto;">
                                    ${requestBodyHtml}
                                </div>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <h6 class="fw-bold">Response</h6>
                                <p class="mb-1"><strong>Status:</strong> <span class="badge bg-${check.status === 'up' ? 'success' : 'danger'}-transparent">${check.status.toUpperCase()}</span></p>
                                <p class="mb-1"><strong>Status Code:</strong> <code>${check.status_code || 'N/A'}</code></p>
                                <p class="mb-1"><strong>Response Time:</strong> <code>${check.response_time || 'N/A'}ms</code></p>
                                <p class="mb-1"><strong>Error:</strong> <span class="text-danger">${check.error_message || 'None'}</span></p>
                                <p class="mb-1"><strong>Headers:</strong></p>
                                <div class="bg-light p-2 rounded mb-2" style="max-height: 150px; overflow-y: auto;">
                                    ${responseHeadersHtml}
                                </div>
                                <p class="mb-1"><strong>Body:</strong></p>
                                <div class="bg-light p-2 rounded mb-2" style="max-height: 200px; overflow-y: auto;">
                                    ${responseBodyHtml}
                                </div>
                            </div>
                            ${check.validation_errors && check.validation_errors.length > 0 ? `
                                <hr>
                                <div>
                                    <h6 class="fw-bold text-warning">Validation Errors</h6>
                                    <ul class="mb-0">
                                        ${check.validation_errors.map(err => `<li>${err}</li>`).join('')}
                                    </ul>
                                </div>
                            ` : ''}
                        </div>
                    `,
                    width: '800px',
                    showCancelButton: true,
                    cancelButtonText: 'Close',
                    confirmButtonText: check.status === 'down' && check.request_method ? '<i class="ri-repeat-line me-1"></i>Replay Request' : 'OK',
                    confirmButtonColor: check.status === 'down' && check.request_method ? '#f59e0b' : '#3085d6',
                }).then((result) => {
                    if (result.isConfirmed && check.status === 'down' && check.request_method) {
                        replayCheck(checkId);
                    }
                });
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load check details: ' + error.message,
                    toast: true,
                    position: 'top-end',
                    timer: 3000,
                });
            });
    }
    </script>
@endsection
