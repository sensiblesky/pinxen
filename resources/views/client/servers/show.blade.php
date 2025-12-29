@extends('layouts.master')

@section('title')
{{ $server->name }} - Server - PingXeno
@endsection

@section('styles')
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<style>
    /* Ensure tables fit within their containers */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* DataTables wrapper styling */
    #disk-partitions-table_wrapper,
    #network-interfaces-table_wrapper,
    #processes-table_wrapper {
        width: 100% !important;
    }
    
    /* Ensure DataTables scroll container uses full width */
    .dataTables_wrapper {
        width: 100% !important;
    }
    
    .dataTables_wrapper .dataTables_scroll {
        width: 100% !important;
    }
    
    .dataTables_wrapper .dataTables_scrollHead {
        width: 100% !important;
    }
    
    .dataTables_wrapper .dataTables_scrollHead table {
        width: 100% !important;
        margin-bottom: 0 !important;
    }
    
    .dataTables_wrapper .dataTables_scrollBody {
        width: 100% !important;
    }
    
    .dataTables_wrapper .dataTables_scrollBody table {
        width: 100% !important;
    }
    
    /* Ensure the actual table elements are full width */
    #disk-partitions-table,
    #network-interfaces-table,
    #processes-table {
        width: 100% !important;
        table-layout: auto;
    }
    
    /* Fix table width on mobile */
    @media (max-width: 768px) {
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
        }
        
        #disk-partitions-table,
        #network-interfaces-table,
        #processes-table {
            width: 100% !important;
            min-width: 600px;
        }
    }
    
    /* Remove border line below nav tabs */
    .nav-tabs {
        border-bottom: none !important;
    }
    
    .nav-tabs .nav-link {
        border-bottom: none !important;
    }
    
    .nav-tabs .nav-link.active {
        border-bottom: none !important;
    }
    
    /* Performance Score Circle */
    .performance-score-circle {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        background: conic-gradient(
            from 0deg,
            var(--score-color, #28a745) 0%,
            var(--score-color, #28a745) var(--score-percent, 0%),
            #e9ecef var(--score-percent, 0%),
            #e9ecef 100%
        );
        padding: 8px;
    }

    .performance-score-circle::before {
        content: '';
        position: absolute;
        width: calc(100% - 16px);
        height: calc(100% - 16px);
        border-radius: 50%;
        background: white;
        z-index: 1;
    }

    .performance-score-circle .score-value,
    .performance-score-circle .score-grade {
        position: relative;
        z-index: 2;
    }

    .performance-score-circle .score-value {
        font-size: 2rem;
        font-weight: bold;
        color: var(--score-color, #28a745);
        line-height: 1;
    }

    .performance-score-circle .score-grade {
        font-size: 1rem;
        font-weight: 600;
        color: var(--score-color, #28a745);
        margin-top: 4px;
    }

    .performance-score-circle[data-color="success"] {
        --score-color: #28a745;
    }

    .performance-score-circle[data-color="warning"] {
        --score-color: #ffc107;
    }

    .performance-score-circle[data-color="danger"] {
        --score-color: #dc3545;
    }
</style>
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="d-flex align-items-center justify-content-between mb-3 page-header-breadcrumb flex-wrap gap-2">
        <div>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('servers.index') }}">Server Monitoring</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $server->name }}</li>
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

    @if($latestStat)
        @php
            $cpuExceeded = $server->cpu_threshold !== null && ($latestStat->cpu_usage_percent ?? 0) > $server->cpu_threshold;
            $memoryExceeded = $server->memory_threshold !== null && ($latestStat->memory_usage_percent ?? 0) > $server->memory_threshold;
            $diskExceeded = $server->disk_threshold !== null && ($latestStat->disk_usage_percent ?? 0) > $server->disk_threshold;
            $hasAlerts = $cpuExceeded || $memoryExceeded || $diskExceeded;
        @endphp

        @if($hasAlerts)
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h5 class="alert-heading"><i class="ri-error-warning-line me-2"></i>Danger Zone - Threshold Exceeded!</h5>
                <p class="mb-2">The following metrics have exceeded their configured thresholds:</p>
                <ul class="mb-0">
                    @if($cpuExceeded)
                        <li>
                            <strong>CPU Usage:</strong> {{ number_format($latestStat->cpu_usage_percent ?? 0, 2) }}% 
                            (Threshold: {{ $server->cpu_threshold }}%)
                            <span class="badge bg-danger ms-2">EXCEEDED</span>
                        </li>
                    @endif
                    @if($memoryExceeded)
                        <li>
                            <strong>Memory Usage:</strong> {{ number_format($latestStat->memory_usage_percent ?? 0, 2) }}% 
                            (Threshold: {{ $server->memory_threshold }}%)
                            <span class="badge bg-danger ms-2">EXCEEDED</span>
                        </li>
                    @endif
                    @if($diskExceeded)
                        <li>
                            <strong>Disk Usage:</strong> {{ number_format($latestStat->disk_usage_percent ?? 0, 2) }}% 
                            (Threshold: {{ $server->disk_threshold }}%)
                            <span class="badge bg-danger ms-2">EXCEEDED</span>
                        </li>
                    @endif
                </ul>
                <hr>
                <p class="mb-0">
                    <small>You can adjust these thresholds in the <a href="{{ route('servers.edit', $server) }}" class="alert-link">server settings</a>.</small>
                </p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    @endif

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">{{ $server->name }}</div>
                    <div class="card-options">
                        <a href="{{ route('servers.edit', $server) }}" class="btn btn-primary btn-wave btn-sm">
                            <i class="ri-edit-line me-1"></i>Edit
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Performance Score Card -->
                    @if($latestStat && isset($performanceScore))
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card border border-{{ $performanceScore['color'] }} bg-{{ $performanceScore['color'] }}-transparent">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-3 text-center">
                                            <div class="performance-score-circle mx-auto" data-score="{{ $performanceScore['score'] }}" data-color="{{ $performanceScore['color'] }}">
                                                <div class="score-value">{{ $performanceScore['score'] }}</div>
                                                <div class="score-grade">{{ $performanceScore['grade'] }}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-9">
                                            <h5 class="mb-2">
                                                <i class="ri-bar-chart-line me-2"></i>Performance Score
                                                <span class="badge bg-{{ $performanceScore['color'] }} ms-2">{{ $performanceScore['grade'] }}</span>
                                            </h5>
                                            <p class="text-muted mb-3">{{ $performanceScore['message'] }}</p>
                                            <div class="row">
                                                @foreach($performanceScore['breakdown'] as $metric => $data)
                                                <div class="col-md-3 mb-2">
                                                    <small class="text-muted d-block text-uppercase">{{ ucfirst(str_replace('_', ' ', $metric)) }}</small>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                            <div class="progress-bar bg-{{ $data['score'] >= 80 ? 'success' : ($data['score'] >= 60 ? 'warning' : 'danger') }}" 
                                                                 role="progressbar" 
                                                                 style="width: {{ $data['score'] }}%"
                                                                 aria-valuenow="{{ $data['score'] }}" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100"></div>
                                                        </div>
                                                        <small class="fw-semibold">{{ $data['score'] }}</small>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="row">
                        @if($latestStat)
                        <!-- Performance Metrics Cards -->
                        <div class="col-md-12 mb-4">
                            <div class="row">
                                <div class="col-xl-3 col-md-6 mb-3">
                                    <div class="card custom-card dashboard-main-card overflow-hidden primary {{ ($server->cpu_threshold !== null && ($latestStat->cpu_usage_percent ?? 0) > $server->cpu_threshold) ? 'border-danger border-2' : '' }}">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="flex-fill">
                                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                                        <span class="fs-13 fw-medium">CPU Usage</span>
                                                        @if($server->cpu_threshold !== null && ($latestStat->cpu_usage_percent ?? 0) > $server->cpu_threshold)
                                                            <span class="badge bg-danger">
                                                                <i class="ri-alert-line me-1"></i>Threshold Exceeded
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <h4 class="fw-semibold my-2 lh-1 {{ ($server->cpu_threshold !== null && ($latestStat->cpu_usage_percent ?? 0) > $server->cpu_threshold) ? 'text-danger' : '' }}">
                                                        {{ number_format($latestStat->cpu_usage_percent ?? 0, 1) }}%
                                                        @if($server->cpu_threshold !== null)
                                                            <small class="text-muted">/ {{ $server->cpu_threshold }}%</small>
                                                        @endif
                                                    </h4>
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <span class="fs-12 d-block text-muted">
                                                            <span class="text-muted me-1 d-inline-flex align-items-center fw-semibold">
                                                                <i class="ri-cpu-line me-1 fw-semibold align-middle"></i>{{ $latestStat->cpu_cores ?? 'N/A' }} Cores
                                                            </span>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="avatar avatar-md bg-primary-transparent svg-primary">
                                                        <i class="ri-cpu-line fs-20"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-3">
                                    <div class="card custom-card dashboard-main-card overflow-hidden secondary {{ ($server->memory_threshold !== null && ($latestStat->memory_usage_percent ?? 0) > $server->memory_threshold) ? 'border-danger border-2' : '' }}">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="flex-fill">
                                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                                        <span class="fs-13 fw-medium">Memory Usage</span>
                                                        @if($server->memory_threshold !== null && ($latestStat->memory_usage_percent ?? 0) > $server->memory_threshold)
                                                            <span class="badge bg-danger">
                                                                <i class="ri-alert-line me-1"></i>Threshold Exceeded
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <h4 class="fw-semibold my-2 lh-1 {{ ($server->memory_threshold !== null && ($latestStat->memory_usage_percent ?? 0) > $server->memory_threshold) ? 'text-danger' : '' }}">
                                                        {{ number_format($latestStat->memory_usage_percent ?? 0, 1) }}%
                                                        @if($server->memory_threshold !== null)
                                                            <small class="text-muted">/ {{ $server->memory_threshold }}%</small>
                                                        @endif
                                                    </h4>
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <span class="fs-12 d-block text-muted">
                                                            <span class="text-muted me-1 d-inline-flex align-items-center fw-semibold">
                                                                <i class="ri-database-2-line me-1 fw-semibold align-middle"></i>{{ $latestStat->memory_total_bytes ? \App\Models\ServerStat::formatBytes($latestStat->memory_total_bytes) : 'N/A' }}
                                                            </span>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="avatar avatar-md bg-secondary-transparent svg-secondary">
                                                        <i class="ri-database-2-line fs-20"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-3">
                                    <div class="card custom-card dashboard-main-card overflow-hidden warning {{ ($server->disk_threshold !== null && ($latestStat->disk_usage_percent ?? 0) > $server->disk_threshold) ? 'border-danger border-2' : '' }}">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="flex-fill">
                                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                                        <span class="fs-13 fw-medium">Disk Usage</span>
                                                        @if($server->disk_threshold !== null && ($latestStat->disk_usage_percent ?? 0) > $server->disk_threshold)
                                                            <span class="badge bg-danger">
                                                                <i class="ri-alert-line me-1"></i>Threshold Exceeded
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <h4 class="fw-semibold my-2 lh-1 {{ ($server->disk_threshold !== null && ($latestStat->disk_usage_percent ?? 0) > $server->disk_threshold) ? 'text-danger' : '' }}">
                                                        {{ number_format($latestStat->disk_usage_percent ?? 0, 1) }}%
                                                        @if($server->disk_threshold !== null)
                                                            <small class="text-muted">/ {{ $server->disk_threshold }}%</small>
                                                        @endif
                                                    </h4>
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <span class="fs-12 d-block text-muted">
                                                            <span class="text-muted me-1 d-inline-flex align-items-center fw-semibold">
                                                                <i class="ri-hard-drive-line me-1 fw-semibold align-middle"></i>{{ $latestStat->disk_total_bytes ? \App\Models\ServerStat::formatBytes($latestStat->disk_total_bytes) : 'N/A' }}
                                                            </span>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="avatar avatar-md bg-warning-transparent svg-warning">
                                                        <i class="ri-hard-drive-line fs-20"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-3">
                                    <div class="card custom-card dashboard-main-card overflow-hidden success">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="flex-fill">
                                                    <span class="fs-13 fw-medium">System Uptime</span>
                                                    <h4 class="fw-semibold my-2 lh-1">{{ $latestStat->uptime_seconds ? \App\Models\ServerStat::formatUptime($latestStat->uptime_seconds) : 'N/A' }}</h4>
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <span class="fs-12 d-block text-muted">
                                                            <span class="text-muted me-1 d-inline-flex align-items-center fw-semibold">
                                                                <i class="ri-time-line me-1 fw-semibold align-middle"></i>{{ $latestStat->processes_total ?? 0 }} Processes
                                                            </span>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="avatar avatar-md bg-success-transparent svg-success">
                                                        <i class="ri-time-line fs-20"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Last updated: {{ $latestStat->recorded_at->format('Y-m-d H:i:s') }} ({{ $latestStat->recorded_at->diffForHumans() }})</small>
                            </div>
                        </div>

                        <!-- Performance Charts -->
                        <div class="col-md-12 mb-4">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">
                                        <i class="ri-line-chart-line me-2"></i>Performance Overview
                                    </div>
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Time range">
                                        <button type="button" class="btn btn-primary btn-wave waves-effect waves-light chart-range-btn {{ ($range ?? '24h') === '24h' ? 'active' : '' }}" data-range="24h">24H</button>
                                        <button type="button" class="btn btn-primary-light btn-wave waves-effect waves-light chart-range-btn {{ ($range ?? '24h') === '7d' ? 'active' : '' }}" data-range="7d">7D</button>
                                        <button type="button" class="btn btn-primary-light btn-wave waves-effect waves-light chart-range-btn {{ ($range ?? '24h') === '30d' ? 'active' : '' }}" data-range="30d">30D</button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="performance-overview-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Metrics by Category -->
                        <div class="col-md-12">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Detailed Metrics</div>
                                </div>
                                <div class="card-body">
                                    <!-- Nav Tabs -->
                                    <ul class="nav nav-tabs mb-3" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="cpu-tab" data-bs-toggle="tab" data-bs-target="#cpu-tab-pane" type="button" role="tab" aria-controls="cpu-tab-pane" aria-selected="true">
                                                <i class="ri-cpu-line me-1"></i> CPU
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="memory-tab" data-bs-toggle="tab" data-bs-target="#memory-tab-pane" type="button" role="tab" aria-controls="memory-tab-pane" aria-selected="false">
                                                <i class="ri-database-2-line me-1"></i> Memory
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="disk-tab" data-bs-toggle="tab" data-bs-target="#disk-tab-pane" type="button" role="tab" aria-controls="disk-tab-pane" aria-selected="false">
                                                <i class="ri-hard-drive-line me-1"></i> Disk
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="network-tab" data-bs-toggle="tab" data-bs-target="#network-tab-pane" type="button" role="tab" aria-controls="network-tab-pane" aria-selected="false">
                                                <i class="ri-wifi-line me-1"></i> Network
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="process-tab" data-bs-toggle="tab" data-bs-target="#process-tab-pane" type="button" role="tab" aria-controls="process-tab-pane" aria-selected="false">
                                                <i class="ri-file-list-3-line me-1"></i> Process
                                            </button>
                                        </li>
                                    </ul>

                                    <!-- Tab Content -->
                                    <div class="tab-content" id="metricsTabContent">
                                        <!-- CPU Metrics Tab -->
                                        <div class="tab-pane fade show active" id="cpu-tab-pane" role="tabpanel" aria-labelledby="cpu-tab" tabindex="0">
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <div id="cpu-chart" style="min-height: 250px;"></div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <strong>CPU Usage:</strong> 
                                                    <span class="{{ ($server->cpu_threshold !== null && ($latestStat->cpu_usage_percent ?? 0) > $server->cpu_threshold) ? 'text-danger fw-bold' : '' }}">
                                                        {{ number_format($latestStat->cpu_usage_percent ?? 0, 2) }}%
                                                    </span>
                                                    @if($server->cpu_threshold !== null)
                                                        <span class="text-muted">(Threshold: {{ $server->cpu_threshold }}%)</span>
                                                        @if(($latestStat->cpu_usage_percent ?? 0) > $server->cpu_threshold)
                                                            <span class="badge bg-danger ms-2">
                                                                <i class="ri-alert-line me-1"></i>Exceeded
                                                            </span>
                                                        @endif
                                                    @endif
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <strong>CPU Cores:</strong> {{ $latestStat->cpu_cores ?? 'N/A' }}
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <strong>Load Average (1min):</strong> {{ $latestStat->cpu_load_1min ? number_format($latestStat->cpu_load_1min, 2) : 'N/A' }}
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <strong>Load Average (5min):</strong> {{ $latestStat->cpu_load_5min ? number_format($latestStat->cpu_load_5min, 2) : 'N/A' }}
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <strong>Load Average (15min):</strong> {{ $latestStat->cpu_load_15min ? number_format($latestStat->cpu_load_15min, 2) : 'N/A' }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Memory Metrics Tab -->
                                        <div class="tab-pane fade" id="memory-tab-pane" role="tabpanel" aria-labelledby="memory-tab" tabindex="0">
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <div id="memory-chart" style="min-height: 250px;"></div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <strong>Memory Usage:</strong> 
                                                    <span class="{{ ($server->memory_threshold !== null && ($latestStat->memory_usage_percent ?? 0) > $server->memory_threshold) ? 'text-danger fw-bold' : '' }}">
                                                        {{ number_format($latestStat->memory_usage_percent ?? 0, 2) }}%
                                                    </span>
                                                    @if($server->memory_threshold !== null)
                                                        <span class="text-muted">(Threshold: {{ $server->memory_threshold }}%)</span>
                                                        @if(($latestStat->memory_usage_percent ?? 0) > $server->memory_threshold)
                                                            <span class="badge bg-danger ms-2">
                                                                <i class="ri-alert-line me-1"></i>Exceeded
                                                            </span>
                                                        @endif
                                                    @endif
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <strong>Total Memory:</strong> {{ $latestStat->memory_total_bytes ? \App\Models\ServerStat::formatBytes($latestStat->memory_total_bytes) : 'N/A' }}
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <strong>Used Memory:</strong> {{ $latestStat->memory_used_bytes ? \App\Models\ServerStat::formatBytes($latestStat->memory_used_bytes) : 'N/A' }}
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <strong>Free Memory:</strong> {{ $latestStat->memory_free_bytes ? \App\Models\ServerStat::formatBytes($latestStat->memory_free_bytes) : 'N/A' }}
                                                </div>
                                                @if($latestStat->swap_total_bytes)
                                                <div class="col-md-12 mt-3">
                                                    <h6 class="mb-2">Swap Memory</h6>
                                                    <div class="row">
                                                        <div class="col-md-4 mb-2">
                                                            <strong>Swap Usage:</strong> {{ number_format($latestStat->swap_usage_percent ?? 0, 2) }}%
                                                        </div>
                                                        <div class="col-md-4 mb-2">
                                                            <strong>Total Swap:</strong> {{ \App\Models\ServerStat::formatBytes($latestStat->swap_total_bytes) }}
                                                        </div>
                                                        <div class="col-md-4 mb-2">
                                                            <strong>Used Swap:</strong> {{ $latestStat->swap_used_bytes ? \App\Models\ServerStat::formatBytes($latestStat->swap_used_bytes) : 'N/A' }}
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Disk Metrics Tab -->
                                        <div class="tab-pane fade" id="disk-tab-pane" role="tabpanel" aria-labelledby="disk-tab" tabindex="0">
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <div id="disk-chart" style="min-height: 250px;"></div>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <strong>Total Disk (Physical):</strong> {{ $latestStat->disk_total_bytes ? \App\Models\ServerStat::formatBytes($latestStat->disk_total_bytes) : 'N/A' }}
                                                    <small class="text-muted d-block">Excludes virtual filesystems</small>
                                                </div>
                                                <div class="col-md-4">
                                                    <strong>Used Disk:</strong> {{ $latestStat->disk_used_bytes ? \App\Models\ServerStat::formatBytes($latestStat->disk_used_bytes) : 'N/A' }}
                                                </div>
                                                <div class="col-md-4">
                                                    <strong>Disk Usage:</strong> 
                                                    <span class="{{ ($server->disk_threshold !== null && ($latestStat->disk_usage_percent ?? 0) > $server->disk_threshold) ? 'text-danger fw-bold' : '' }}">
                                                        {{ number_format($latestStat->disk_usage_percent ?? 0, 2) }}%
                                                    </span>
                                                    @if($server->disk_threshold !== null)
                                                        <span class="text-muted">(Threshold: {{ $server->disk_threshold }}%)</span>
                                                        @if(($latestStat->disk_usage_percent ?? 0) > $server->disk_threshold)
                                                            <span class="badge bg-danger ms-2">
                                                                <i class="ri-alert-line me-1"></i>Exceeded
                                                            </span>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                            @if($latestStat->disk_usage && is_array($latestStat->disk_usage) && count($latestStat->disk_usage) > 0)
                                            <h6 class="mb-2">Disk Partitions</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered w-100" id="disk-partitions-table" width="100%">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Device</th>
                                                            <th>Mount Point</th>
                                                            <th>File System</th>
                                                            <th>Total</th>
                                                            <th>Used</th>
                                                            <th>Free</th>
                                                            <th>Usage %</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- DataTables will populate this -->
                                                    </tbody>
                                                </table>
                                            </div>
                                            @else
                                            <div class="alert alert-info">
                                                <i class="ri-information-line me-2"></i>
                                                No disk partition data available.
                                            </div>
                                            @endif
                                        </div>

                                        <!-- Network Metrics Tab -->
                                        <div class="tab-pane fade" id="network-tab-pane" role="tabpanel" aria-labelledby="network-tab" tabindex="0">
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <div id="network-chart" style="min-height: 250px;"></div>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-3">
                                                    <strong>Bytes Sent:</strong> {{ $latestStat->network_bytes_sent ? \App\Models\ServerStat::formatBytes($latestStat->network_bytes_sent) : 'N/A' }}
                                                </div>
                                                <div class="col-md-3">
                                                    <strong>Bytes Received:</strong> {{ $latestStat->network_bytes_received ? \App\Models\ServerStat::formatBytes($latestStat->network_bytes_received) : 'N/A' }}
                                                </div>
                                                <div class="col-md-3">
                                                    <strong>Packets Sent:</strong> {{ $latestStat->network_packets_sent ? number_format($latestStat->network_packets_sent) : 'N/A' }}
                                                </div>
                                                <div class="col-md-3">
                                                    <strong>Packets Received:</strong> {{ $latestStat->network_packets_received ? number_format($latestStat->network_packets_received) : 'N/A' }}
                                                </div>
                                            </div>
                                            @if($latestStat->network_interfaces && is_array($latestStat->network_interfaces) && count($latestStat->network_interfaces) > 0)
                                            <h6 class="mb-2">Network Interfaces</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered w-100" id="network-interfaces-table" width="100%">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Interface</th>
                                                            <th>Bytes Sent</th>
                                                            <th>Bytes Received</th>
                                                            <th>Packets Sent</th>
                                                            <th>Packets Received</th>
                                                            <th>Errors In</th>
                                                            <th>Errors Out</th>
                                                            <th>Drops In</th>
                                                            <th>Drops Out</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- DataTables will populate this -->
                                                    </tbody>
                                                </table>
                                            </div>
                                            @else
                                            <div class="alert alert-info">
                                                <i class="ri-information-line me-2"></i>
                                                No network interface data available.
                                            </div>
                                            @endif
                                        </div>

                                        <!-- Process Metrics Tab -->
                                        <div class="tab-pane fade" id="process-tab-pane" role="tabpanel" aria-labelledby="process-tab" tabindex="0">
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <div id="process-chart" style="min-height: 250px;"></div>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <strong>Total Processes:</strong> {{ $latestStat->processes_total ?? 'N/A' }}
                                                </div>
                                                <div class="col-md-4">
                                                    <strong>Running:</strong> {{ $latestStat->processes_running ?? 'N/A' }}
                                                </div>
                                                <div class="col-md-4">
                                                    <strong>Sleeping:</strong> {{ $latestStat->processes_sleeping ?? 'N/A' }}
                                                </div>
                                            </div>
                                            @if($latestStat->processes && is_array($latestStat->processes) && count($latestStat->processes) > 0)
                                            <h6 class="mb-2">Process List</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered w-100" id="processes-table" width="100%">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>PID</th>
                                                            <th>Name</th>
                                                            <th>Status</th>
                                                            <th>CPU %</th>
                                                            <th>Memory %</th>
                                                            <th>Memory</th>
                                                            <th>User</th>
                                                            <th>Command</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- DataTables will populate this -->
                                                    </tbody>
                                                </table>
                                            </div>
                                            @else
                                            <div class="alert alert-info">
                                                <i class="ri-information-line me-2"></i>
                                                No detailed process information available. The agent may need to be updated to collect process details.
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="col-md-12 mb-4">
                            <div class="alert alert-warning">
                                <i class="ri-alert-line me-2"></i>
                                No performance data available yet. Install and configure the agent to start receiving metrics.
                            </div>
                        </div>
                        @endif

                        <!-- Basic Information -->
                        <div class="col-md-6 mb-4">
                            <h6 class="mb-3">Basic Information</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td class="text-muted" width="40%">Name:</td>
                                    <td><strong>{{ $server->name }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Status:</td>
                                    <td>
                                        @php
                                            $status = $server->getStatus();
                                            // Use status from getStatus() to ensure consistency
                                            $statusBadgeClass = match($status['status']) {
                                                'online' => 'bg-success-transparent text-success',
                                                'warning' => 'bg-warning-transparent text-warning',
                                                'offline' => 'bg-danger-transparent text-danger',
                                                default => 'bg-secondary-transparent text-secondary'
                                            };
                                            $statusText = ucfirst($status['status']);
                                        @endphp
                                        <span class="badge {{ $statusBadgeClass }}">
                                            <i class="ri-{{ $status['status'] === 'online' ? 'checkbox-circle-line' : ($status['status'] === 'warning' ? 'alert-line' : 'close-circle-line') }} me-1"></i>
                                            {{ $statusText }}
                                        </span>
                                        @if($status['minutes_ago'] !== null)
                                            <small class="text-muted d-block mt-1">
                                                Last seen: {{ $status['last_seen']->diffForHumans() }}
                                                @if($status['minutes_ago'] > 0)
                                                    <span class="text-muted">({{ number_format($status['minutes_ago'], 1) }} min ago)</span>
                                                @endif
                                            </small>
                                        @else
                                            <small class="text-muted d-block mt-1">Never seen</small>
                                        @endif
                                    </td>
                                </tr>
                                @if($server->online_threshold_minutes || $server->warning_threshold_minutes || $server->offline_threshold_minutes)
                                <tr>
                                    <td class="text-muted">Status Thresholds:</td>
                                    <td>
                                        <small>
                                            Online: {{ $server->online_threshold_minutes ?? 5 }} min,
                                            Warning: {{ $server->warning_threshold_minutes ?? 60 }} min,
                                            Offline: {{ $server->offline_threshold_minutes ?? 120 }} min
                                        </small>
                                    </td>
                                </tr>
                                @endif
                                @if($server->description)
                                <tr>
                                    <td class="text-muted">Description:</td>
                                    <td>{{ $server->description }}</td>
                                </tr>
                                @endif
                                @if($server->hostname)
                                <tr>
                                    <td class="text-muted">Hostname:</td>
                                    <td><code>{{ $server->hostname }}</code></td>
                                </tr>
                                @endif
                                @if($server->ip_address)
                                <tr>
                                    <td class="text-muted">IP Address:</td>
                                    <td><code>{{ $server->ip_address }}</code></td>
                                </tr>
                                @endif
                                @if($server->location)
                                <tr>
                                    <td class="text-muted">Location:</td>
                                    <td>{{ $server->location }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>

                        <div class="col-md-6 mb-4">
                            <h6 class="mb-3">System Information</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td class="text-muted" width="40%">OS Type:</td>
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
                                </tr>
                                <tr>
                                    <td class="text-muted">API Key:</td>
                                    <td>
                                        @if($server->apiKey)
                                            <code class="text-secondary">{{ $server->apiKey->name }} ({{ $server->apiKey->key_prefix }}...)</code>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Agent Version:</td>
                                    <td>
                                        @if($server->agent_version)
                                            <span class="badge bg-success-transparent text-success">{{ $server->agent_version }}</span>
                                        @else
                                            <span class="text-muted">Not installed</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Agent Installed:</td>
                                    <td>
                                        @if($server->agent_installed_at)
                                            {{ $server->agent_installed_at->format('Y-m-d H:i:s') }}
                                            <small class="text-muted">({{ $server->agent_installed_at->diffForHumans() }})</small>
                                        @else
                                            <span class="text-muted">Not installed</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($server->agent_id || $server->machine_id || $server->system_uuid || $server->disk_uuid)
                                <tr>
                                    <td class="text-muted">Agent Identifiers:</td>
                                    <td>
                                        <div class="small">
                                            @if($server->agent_id)
                                                <div class="mb-1">
                                                    <strong>Agent ID:</strong> 
                                                    <code class="text-primary">{{ $server->agent_id }}</code>
                                                    <button class="btn btn-sm btn-link p-0 ms-1" onclick="copyToClipboard('{{ $server->agent_id }}', 'Agent ID')" title="Copy Agent ID">
                                                        <i class="ri-file-copy-line"></i>
                                                    </button>
                                                </div>
                                            @endif
                                            @if($server->machine_id)
                                                <div class="mb-1">
                                                    <strong>Machine ID:</strong> 
                                                    <code class="text-info">{{ $server->machine_id }}</code>
                                                </div>
                                            @endif
                                            @if($server->system_uuid)
                                                <div class="mb-1">
                                                    <strong>System UUID:</strong> 
                                                    <code class="text-info">{{ $server->system_uuid }}</code>
                                                </div>
                                            @endif
                                            @if($server->disk_uuid)
                                                <div class="mb-1">
                                                    <strong>Disk UUID:</strong> 
                                                    <code class="text-info">{{ $server->disk_uuid }}</code>
                                                </div>
                                            @endif
                                            <small class="text-muted d-block mt-2">
                                                <i class="ri-information-line"></i> These identifiers help uniquely identify this server even if hostname changes.
                                            </small>
                                        </div>
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="text-muted">Last Seen:</td>
                                    <td>
                                        @if($server->last_seen_at)
                                            {{ $server->last_seen_at->format('Y-m-d H:i:s') }}
                                            <small class="text-muted">({{ $server->last_seen_at->diffForHumans() }})</small>
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Created:</td>
                                    <td>{{ $server->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                            </table>
                        </div>

                        <!-- Agent Installation -->
                        <div class="col-md-12 mb-4">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0"><i class="ri-download-cloud-2-line me-2"></i>Agent Installation</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Installation Method Tabs -->
                                    <ul class="nav nav-tabs mb-3" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#download-tab" type="button" role="tab">
                                                <i class="ri-download-line me-1"></i>Download
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#script-tab" type="button" role="tab">
                                                <i class="ri-terminal-box-line me-1"></i>Install Script
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ssh-tab" type="button" role="tab">
                                                <i class="ri-terminal-line me-1"></i>SSH Auto-Install
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content">
                                        <!-- Download Tab -->
                                        <div class="tab-pane fade show active" id="download-tab" role="tabpanel">
                                            <p class="text-muted mb-3">Download the agent binary for your operating system:</p>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Operating System</label>
                                                    <select class="form-select" id="download-os">
                                                        <option value="linux">Linux</option>
                                                        <option value="windows">Windows</option>
                                                        <option value="darwin">macOS</option>
                                                        <option value="freebsd">FreeBSD</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Architecture</label>
                                                    <select class="form-select" id="download-arch">
                                                        <option value="amd64">AMD64 (x86_64)</option>
                                                        <option value="arm64">ARM64</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <a href="#" id="download-link" class="btn btn-primary" onclick="downloadAgent(event)">
                                                <i class="ri-download-line me-1"></i>Download Agent
                                            </a>
                                            <p class="text-muted mt-3 small">
                                                After downloading, extract and configure the agent with your server key and API key.
                                            </p>
                                        </div>

                                        <!-- Install Script Tab -->
                                        <div class="tab-pane fade" id="script-tab" role="tabpanel">
                                            <p class="text-muted mb-3">Copy and paste this command on your server to automatically install the agent:</p>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Operating System</label>
                                                    <select class="form-select" id="script-os">
                                                        <option value="linux">Linux</option>
                                                        <option value="windows">Windows</option>
                                                        <option value="darwin">macOS</option>
                                                        <option value="freebsd">FreeBSD</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Architecture</label>
                                                    <select class="form-select" id="script-arch">
                                                        <option value="amd64">AMD64 (x86_64)</option>
                                                        <option value="arm64">ARM64</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="input-group mb-2">
                                                <input type="text" class="form-control" id="install-oneliner" readonly>
                                                <button class="btn btn-primary" type="button" onclick="copyInstallCommand()">
                                                    <i class="ri-file-copy-line me-1"></i>Copy
                                                </button>
                                            </div>
                                            <button class="btn btn-secondary btn-sm" onclick="loadInstallScript()">
                                                <i class="ri-refresh-line me-1"></i>Refresh Command
                                            </button>
                                            <p class="text-muted mt-3 small">
                                                <strong>Note:</strong> This command will download, install, and configure the agent automatically. Run it with sudo/administrator privileges.
                                            </p>
                                        </div>

                                        <!-- SSH Auto-Install Tab -->
                                        <div class="tab-pane fade" id="ssh-tab" role="tabpanel">
                                            <p class="text-muted mb-3">Automatically install the agent via SSH (server must be publicly accessible):</p>
                                            <form id="ssh-install-form">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Host (IP or Hostname) <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="host" value="{{ $server->ip_address ?? $server->hostname ?? '' }}" placeholder="192.168.1.100 or example.com" required>
                                                        <small class="text-muted">Leave empty to use server's IP/hostname</small>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">SSH Port</label>
                                                        <input type="number" class="form-control" name="port" value="22" min="1" max="65535">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Username <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="username" placeholder="root" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Authentication Method</label>
                                                        <select class="form-select" id="auth-method" onchange="toggleAuthMethod()">
                                                            <option value="password">Password</option>
                                                            <option value="key">SSH Key</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-3" id="password-field">
                                                        <label class="form-label">Password</label>
                                                        <input type="password" class="form-control" name="password" placeholder="Enter SSH password">
                                                    </div>
                                                    <div class="col-md-12 mb-3" id="key-field" style="display: none;">
                                                        <label class="form-label">Private Key</label>
                                                        <textarea class="form-control" name="private_key" rows="5" placeholder="-----BEGIN RSA PRIVATE KEY-----&#10;..."></textarea>
                                                        <small class="text-muted">Paste your SSH private key content</small>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Operating System</label>
                                                        <select class="form-select" name="os">
                                                            <option value="auto">Auto-detect</option>
                                                            <option value="linux">Linux</option>
                                                            <option value="darwin">macOS</option>
                                                            <option value="freebsd">FreeBSD</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Architecture</label>
                                                        <select class="form-select" name="arch">
                                                            <option value="amd64">AMD64 (x86_64)</option>
                                                            <option value="arm64">ARM64</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-info" onclick="testSSHConnection()">
                                                        <i class="ri-check-line me-1"></i>Test Connection
                                                    </button>
                                                    <button type="button" class="btn btn-primary" onclick="installViaSSH()">
                                                        <i class="ri-download-cloud-2-line me-1"></i>Install Agent
                                                    </button>
                                                </div>
                                                <div id="ssh-result" class="mt-3"></div>
                                            </form>
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <!-- Server Configuration Info -->
                                    <div class="alert alert-light">
                                        <h6 class="alert-heading"><i class="ri-key-line me-2"></i>Server Configuration</h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <strong>Server Key:</strong>
                                                <div class="input-group mt-1">
                                                    <input type="text" class="form-control" id="server-key-value" value="{{ $server->server_key }}" readonly>
                                                    <button class="btn btn-primary" type="button" onclick="copyServerKey()">
                                                        <i class="ri-file-copy-line me-1"></i>Copy
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <strong>API Endpoint:</strong>
                                                <div class="input-group mt-1">
                                                    <input type="text" class="form-control" value="{{ url('/api/v1/server-stats') }}" readonly>
                                                    <button class="btn btn-primary" type="button" onclick="copyToClipboard('{{ url('/api/v1/server-stats') }}')">
                                                        <i class="ri-file-copy-line me-1"></i>Copy
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-warning btn-sm regenerate-key-btn" 
                                                    data-uid="{{ $server->uid }}"
                                                    data-name="{{ $server->name }}">
                                                <i class="ri-refresh-line me-1"></i>Regenerate Server Key
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <h6 class="mb-3">Actions</h6>
                            <div class="btn-list">
                                <a href="{{ route('servers.edit', $server) }}" class="btn btn-primary btn-wave">
                                    <i class="ri-edit-line me-1"></i>Edit
                                </a>
                                <a href="{{ route('servers.index') }}" class="btn btn-light btn-wave">
                                    <i class="ri-arrow-left-line me-1"></i>Back to List
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->
@endsection

@section('styles')
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
    .dashboard-main-card {
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    .dashboard-main-card.primary {
        background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--primary-rgb), 0.05) 100%);
    }
    .dashboard-main-card.secondary {
        background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.05) 100%);
    }
    .dashboard-main-card.warning {
        background: linear-gradient(135deg, rgba(var(--warning-rgb), 0.1) 0%, rgba(var(--warning-rgb), 0.05) 100%);
    }
    .dashboard-main-card.success {
        background: linear-gradient(135deg, rgba(var(--success-rgb), 0.1) 0%, rgba(var(--success-rgb), 0.05) 100%);
    }
    .svg-primary { color: rgb(var(--primary-rgb)); }
    .svg-secondary { color: rgb(var(--secondary-rgb)); }
    .svg-warning { color: rgb(var(--warning-rgb)); }
    .svg-success { color: rgb(var(--success-rgb)); }
</style>
@endsection

@section('scripts')
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<!-- Apex Charts JS -->
<script src="{{asset('build/assets/libs/apexcharts/apexcharts.min.js')}}"></script>
<script>
    // Chart data from backend
    const chartData = @json($chartData);
    const currentRange = '{{ $range ?? "24h" }}';
    let performanceChart, cpuChart, memoryChart, diskChart, networkChart, processChart;
    
    // Debug: Log chart data on load
    console.log('Chart Data Loaded:', {
        range: currentRange,
        cpuCount: chartData.cpu ? chartData.cpu.length : 0,
        memoryCount: chartData.memory ? chartData.memory.length : 0,
        diskCount: chartData.disk ? chartData.disk.length : 0,
        timestampsCount: chartData.timestamps ? chartData.timestamps.length : 0,
        sampleData: {
            firstCpu: chartData.cpu && chartData.cpu.length > 0 ? chartData.cpu[0] : null,
            firstTimestamp: chartData.timestamps && chartData.timestamps.length > 0 ? chartData.timestamps[0] : null,
            lastTimestamp: chartData.timestamps && chartData.timestamps.length > 0 ? chartData.timestamps[chartData.timestamps.length - 1] : null
        }
    });
    
    // Copy functions (called from onclick)
    function copyToClipboard(text, label) {
        navigator.clipboard.writeText(text).then(function() {
            // Show success message
            const toast = document.createElement('div');
            toast.className = 'toast align-items-center text-white bg-success border-0';
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${label} copied to clipboard!</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        }).catch(function(err) {
            console.error('Failed to copy:', err);
        });
    }

    function copyServerKey() {
        const input = document.getElementById('server-key-value');
        input.select();
        input.setSelectionRange(0, 99999);
        
        try {
            document.execCommand('copy');
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="ri-check-line me-1"></i>Copied!';
            btn.classList.add('btn-success');
            btn.classList.remove('btn-primary');
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-primary');
            }, 2000);
        } catch (err) {
            alert('Failed to copy. Please select and copy manually.');
        }
    }

    function copyApiKey() {
        @if($server->apiKey)
        Swal.fire({
            icon: 'info',
            title: 'API Key Hidden',
            html: 'For security reasons, the full API key is not displayed here.<br><br>Please view it in the <a href="{{ route('api-keys.show', $server->apiKey) }}">API Keys section</a>.',
            confirmButtonText: 'OK'
        });
        @else
        Swal.fire({
            icon: 'warning',
            title: 'No API Key',
            text: 'No API key is associated with this server.',
            confirmButtonText: 'OK'
        });
        @endif
    }
    
    (function($) {
        $(document).ready(function() {

            // Regenerate key button
            $('.regenerate-key-btn').on('click', function() {
                const uid = $(this).data('uid');
                const name = $(this).data('name');
                
                Swal.fire({
                    title: 'Are you sure?',
                    html: `You are about to regenerate the server key for <strong>"${name}"</strong>.<br><br>This will invalidate the current server key. Make sure to update your agent configuration with the new key.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, regenerate it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = $('<form>', {
                            method: 'POST',
                            action: `/servers/${uid}/regenerate-key`
                        });
                        form.append($('<input>', {
                            type: 'hidden',
                            name: '_token',
                            value: '{{ csrf_token() }}'
                        }));
                        $('body').append(form);
                        form.submit();
                    }
                });
            });

    // Initialize Performance Score Circle
    initPerformanceScoreCircle();

    // Agent Installation Functions
    function downloadAgent(e) {
        e.preventDefault();
        const os = document.getElementById('download-os').value;
        const arch = document.getElementById('download-arch').value;
        const url = '{{ route("agents.download", ["server" => $server->uid, "os" => ":os", "arch" => ":arch"]) }}'
            .replace(':os', os)
            .replace(':arch', arch);
        window.location.href = url;
    }

    function loadInstallScript() {
        const os = document.getElementById('script-os').value;
        const arch = document.getElementById('script-arch').value;
        // Use public route with server key for one-liner (so it works without authentication)
        const baseUrl = '{{ route("agents.install-oneliner.public", ["server" => $server->uid, "os" => ":os", "arch" => ":arch"]) }}'
            .replace(':os', os)
            .replace(':arch', arch);
        const url = baseUrl + '?key=' + encodeURIComponent('{{ $server->server_key }}');
        
        fetch(url, {
            headers: {
                'Accept': 'text/plain'
            }
        })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(text || 'Failed to load script');
                    });
                }
                return response.text();
            })
            .then(text => {
                // Check if response is HTML (error page)
                if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
                    throw new Error('Received HTML instead of script. Please check the URL.');
                }
                document.getElementById('install-oneliner').value = text;
            })
            .catch(error => {
                console.error('Error loading install script:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Failed to load installation script. Make sure you are logged in or use the server key.',
                    html: '<pre style="text-align: left; font-size: 12px;">' + error.message + '</pre>'
                });
            });
    }

    function copyInstallCommand() {
        const input = document.getElementById('install-oneliner');
        const text = input.value;
        
        if (!text || text.trim() === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Nothing to copy',
                text: 'Please wait for the installation command to load, or click "Refresh Command"',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }
        
        // Use modern Clipboard API
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Copied!',
                    text: 'Installation command copied to clipboard',
                    timer: 2000,
                    showConfirmButton: false
                });
            }).catch(function(err) {
                console.error('Failed to copy:', err);
                // Fallback to old method
                fallbackCopyTextToClipboard(text);
            });
        } else {
            // Fallback for older browsers
            fallbackCopyTextToClipboard(text);
        }
    }
    
    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        textArea.style.top = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                Swal.fire({
                    icon: 'success',
                    title: 'Copied!',
                    text: 'Installation command copied to clipboard',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Copy failed',
                    text: 'Please manually select and copy the command',
                    timer: 3000,
                    showConfirmButton: false
                });
            }
        } catch (err) {
            console.error('Fallback copy failed:', err);
            Swal.fire({
                icon: 'error',
                title: 'Copy failed',
                text: 'Please manually select and copy the command',
                timer: 3000,
                showConfirmButton: false
            });
        } finally {
            document.body.removeChild(textArea);
        }
    }

    function toggleAuthMethod() {
        const method = document.getElementById('auth-method').value;
        if (method === 'password') {
            document.getElementById('password-field').style.display = 'block';
            document.getElementById('key-field').style.display = 'none';
            document.querySelector('[name="password"]').required = true;
            document.querySelector('[name="private_key"]').required = false;
        } else {
            document.getElementById('password-field').style.display = 'none';
            document.getElementById('key-field').style.display = 'block';
            document.querySelector('[name="password"]').required = false;
            document.querySelector('[name="private_key"]').required = true;
        }
    }

    function testSSHConnection() {
        const form = document.getElementById('ssh-install-form');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        // Add server info if host is empty
        if (!data.host) {
            data.host = '{{ $server->ip_address ?? $server->hostname ?? "" }}';
        }

        Swal.fire({
            title: 'Testing Connection...',
            text: 'Please wait',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('{{ route("servers.test-ssh", $server->uid) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            Swal.close();
            const resultDiv = document.getElementById('ssh-result');
            if (result.success) {
                resultDiv.innerHTML = '<div class="alert alert-success"><i class="ri-check-line me-2"></i>' + result.message + '<br><small>' + (result.system_info || '') + '</small></div>';
            } else {
                resultDiv.innerHTML = '<div class="alert alert-danger"><i class="ri-close-line me-2"></i>' + result.message + '</div>';
            }
        })
        .catch(error => {
            Swal.close();
            Swal.fire('Error', 'Failed to test SSH connection', 'error');
            console.error('Error:', error);
        });
    }

    function installViaSSH() {
        const form = document.getElementById('ssh-install-form');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        // Add server info if host is empty
        if (!data.host) {
            data.host = '{{ $server->ip_address ?? $server->hostname ?? "" }}';
        }

        Swal.fire({
            title: 'Installing Agent...',
            text: 'This may take a few minutes. Please wait.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('{{ route("servers.install-via-ssh", $server->uid) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            Swal.close();
            const resultDiv = document.getElementById('ssh-result');
            if (result.success) {
                resultDiv.innerHTML = '<div class="alert alert-success"><i class="ri-check-line me-2"></i>' + result.message + '</div>';
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Agent installed successfully via SSH',
                    timer: 3000,
                    showConfirmButton: false
                });
                // Reload page after 2 seconds to show updated status
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                resultDiv.innerHTML = '<div class="alert alert-danger"><i class="ri-close-line me-2"></i>' + result.message + '</div>';
                Swal.fire({
                    icon: 'error',
                    title: 'Installation Failed',
                    text: result.message
                });
            }
        })
        .catch(error => {
            Swal.close();
            Swal.fire('Error', 'Failed to install agent via SSH', 'error');
            console.error('Error:', error);
        });
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Copied!',
                text: 'Copied to clipboard',
                timer: 2000,
                showConfirmButton: false
            });
        });
    }

    // Update download link when OS/Arch changes
    document.getElementById('download-os')?.addEventListener('change', function() {
        updateDownloadLink();
    });
    document.getElementById('download-arch')?.addEventListener('change', function() {
        updateDownloadLink();
    });

    function updateDownloadLink() {
        const os = document.getElementById('download-os').value;
        const arch = document.getElementById('download-arch').value;
        // Use public route with server key
        const baseUrl = '{{ route("agents.download.public", ["server" => $server->uid, "os" => ":os", "arch" => ":arch"]) }}'
            .replace(':os', os)
            .replace(':arch', arch);
        const url = baseUrl + '?key=' + encodeURIComponent('{{ $server->server_key }}');
        document.getElementById('download-link').href = url;
    }

    // Load install script on tab change
    document.getElementById('script-os')?.addEventListener('change', loadInstallScript);
    document.getElementById('script-arch')?.addEventListener('change', loadInstallScript);

    // Load install script on page load if script tab is active
    if (document.querySelector('#script-tab.active')) {
        loadInstallScript();
    }

    // Initialize download link
    updateDownloadLink();

            // Initialize Charts - ensure they're initialized after DOM is ready
            // Small delay to ensure all data is loaded
            setTimeout(function() {
                console.log('Initializing all charts with range:', currentRange);
                initPerformanceChart();
                initCPUChart();
                initMemoryChart();
                initDiskChart();
                initNetworkChart();
                initProcessChart();
            }, 100);

            // Initialize DataTables with server-side processing
            @if($latestStat && $latestStat->disk_usage && is_array($latestStat->disk_usage) && count($latestStat->disk_usage) > 0)
            // Disk Partitions DataTable
            $('#disk-partitions-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: {
                    details: {
                        type: 'column',
                        target: 'tr'
                    }
                },
                autoWidth: false,
                scrollX: false,
                scrollCollapse: false,
                ajax: {
                    url: '{{ route("servers.disk-data", $server->uid) }}',
                    type: 'GET'
                },
                order: [[0, 'asc']],
                pageLength: 5,
                lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
                columns: [
                    { data: 'row_number', name: 'row_number', orderable: false, searchable: false },
                    { data: 'device', name: 'device', orderable: false, searchable: true },
                    { data: 'mount_point', name: 'mount_point', orderable: false, searchable: true },
                    { data: 'fs_type', name: 'fs_type', orderable: false, searchable: true },
                    { data: 'total', name: 'total', orderable: false, searchable: false },
                    { data: 'used', name: 'used', orderable: false, searchable: false },
                    { data: 'free', name: 'free', orderable: false, searchable: false },
                    { 
                        data: 'usage_percent', 
                        name: 'usage_percent', 
                        orderable: true,
                        type: 'num',
                        searchable: false,
                        render: function(data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.usage_percent_raw || 0;
                            }
                            if (type === 'display') {
                                const percent = parseFloat(row.usage_percent_raw || 0);
                                const badgeClass = percent > 90 ? 'bg-danger' : (percent > 75 ? 'bg-warning' : 'bg-success');
                                return '<span class="badge ' + badgeClass + '">' + data + '</span>';
                            }
                            return data;
                        }
                    }
                ],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                    search: "Search:",
                    lengthMenu: "Show _MENU_ partitions per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ partitions",
                    infoEmpty: "No partitions available",
                    infoFiltered: "(filtered from _MAX_ total partitions)"
                }
            });
            @endif

            @if($latestStat && $latestStat->network_interfaces && is_array($latestStat->network_interfaces) && count($latestStat->network_interfaces) > 0)
            // Network Interfaces DataTable
            $('#network-interfaces-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: {
                    details: {
                        type: 'column',
                        target: 'tr'
                    }
                },
                autoWidth: false,
                scrollX: false,
                scrollCollapse: false,
                ajax: {
                    url: '{{ route("servers.network-data", $server->uid) }}',
                    type: 'GET'
                },
                order: [[0, 'asc']],
                pageLength: 5,
                lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
                columns: [
                    { data: 'row_number', name: 'row_number', orderable: false, searchable: false },
                    { data: 'name', name: 'name', orderable: false, searchable: true },
                    { data: 'bytes_sent', name: 'bytes_sent', orderable: false, searchable: false },
                    { data: 'bytes_received', name: 'bytes_received', orderable: false, searchable: false },
                    { data: 'packets_sent', name: 'packets_sent', orderable: false, searchable: false },
                    { data: 'packets_received', name: 'packets_received', orderable: false, searchable: false },
                    { data: 'errors_in', name: 'errors_in', orderable: false, searchable: false },
                    { data: 'errors_out', name: 'errors_out', orderable: false, searchable: false },
                    { data: 'drop_in', name: 'drop_in', orderable: false, searchable: false },
                    { data: 'drop_out', name: 'drop_out', orderable: false, searchable: false }
                ],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                    search: "Search:",
                    lengthMenu: "Show _MENU_ interfaces per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ interfaces",
                    infoEmpty: "No interfaces available",
                    infoFiltered: "(filtered from _MAX_ total interfaces)"
                }
            });
            @endif

            @if($latestStat && $latestStat->processes && is_array($latestStat->processes) && count($latestStat->processes) > 0)
            // Processes DataTable
            $('#processes-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: {
                    details: {
                        type: 'column',
                        target: 'tr'
                    }
                },
                autoWidth: false,
                scrollX: false,
                scrollCollapse: false,
                ajax: {
                    url: '{{ route("servers.processes-data", $server->uid) }}',
                    type: 'GET'
                },
                order: [[4, 'desc']], // Sort by CPU % descending
                pageLength: 5,
                lengthMenu: [[5, 10, 25, 50, 100], [5, 10, 25, 50, 100]],
                columns: [
                    { data: 'row_number', name: 'row_number', orderable: false, searchable: false },
                    { data: 'pid', name: 'pid', orderable: true, searchable: true },
                    { data: 'name', name: 'name', orderable: false, searchable: true },
                    { data: 'status', name: 'status', orderable: false, searchable: false },
                    { 
                        data: 'cpu_percent', 
                        name: 'cpu_percent', 
                        orderable: true,
                        type: 'num',
                        render: function(data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.cpu_percent_raw || 0;
                            }
                            return data;
                        }
                    },
                    { 
                        data: 'memory_percent', 
                        name: 'memory_percent', 
                        orderable: true,
                        type: 'num',
                        render: function(data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.memory_percent_raw || 0;
                            }
                            return data;
                        }
                    },
                    { 
                        data: 'memory_bytes', 
                        name: 'memory_bytes', 
                        orderable: true,
                        type: 'num',
                        render: function(data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.memory_bytes_raw || 0;
                            }
                            return data;
                        }
                    },
                    { data: 'user', name: 'user', orderable: false, searchable: true },
                    { data: 'command', name: 'command', orderable: false, searchable: true }
                ],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                    search: "Search processes:",
                    lengthMenu: "Show _MENU_ processes per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ processes",
                    infoEmpty: "No processes available",
                    infoFiltered: "(filtered from _MAX_ total processes)"
                }
            });
            @endif

            // Chart range buttons
            $('.chart-range-btn').on('click', function() {
                const range = $(this).data('range');
                if (range) {
                    // Reload page with new range
                    window.location.href = '{{ route("servers.show", $server->uid) }}?range=' + range;
                }
            });

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
                
                // Build URL with custom range
                let url = '{{ route("servers.show", $server->uid) }}';
                url += '?range=custom&start_date=' + startDate + '&end_date=' + endDate;
                
                // Reload page with custom range
                window.location.href = url;
            };

            // Update page with date range from flatpickr (global function)
            window.updatePageWithDateRange = function(startDate, endDate) {
                if (!startDate || !endDate) {
                    return;
                }
                
                // Build URL with custom range
                let url = '{{ route("servers.show", $server->uid) }}';
                url += '?range=custom&start_date=' + startDate + '&end_date=' + endDate;
                
                // Reload page with custom range
                window.location.href = url;
            };

            // Auto-refresh functionality
            let autoRefreshEnabled = true;
            let autoRefreshInterval = null;
            
            function startAutoRefresh() {
                if (autoRefreshInterval) {
                    clearInterval(autoRefreshInterval);
                }
                
                if (autoRefreshEnabled) {
                    autoRefreshInterval = setInterval(function() {
                        window.location.reload();
                    }, 60000); // Refresh every 60 seconds
                }
            }
            
            function stopAutoRefresh() {
                if (autoRefreshInterval) {
                    clearInterval(autoRefreshInterval);
                    autoRefreshInterval = null;
                }
            }
            
            // Toggle auto-refresh button
            $('#toggle-autorefresh').on('click', function() {
                autoRefreshEnabled = !autoRefreshEnabled;
                
                if (autoRefreshEnabled) {
                    startAutoRefresh();
                    $(this).removeClass('btn-light').addClass('btn-primary');
                    $(this).attr('title', 'Disable Auto-Refresh');
                } else {
                    stopAutoRefresh();
                    $(this).removeClass('btn-primary').addClass('btn-light');
                    $(this).attr('title', 'Enable Auto-Refresh');
                }
            });
            
            // Start auto-refresh on page load
            startAutoRefresh();

            // Initialize flatpickr date range picker
            if (typeof flatpickr !== 'undefined') {
                const urlParams = new URLSearchParams(window.location.search);
                const range = urlParams.get('range') || '24h';
                const startDateParam = urlParams.get('start_date');
                const endDateParam = urlParams.get('end_date');
                
                let defaultStartDate, defaultEndDate;
                
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
                    const now = new Date();
                    const endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                    const startDate = new Date(endDate);
                    startDate.setDate(startDate.getDate() - 1);
                    defaultStartDate = startDate.toISOString().split('T')[0];
                    defaultEndDate = endDate.toISOString().split('T')[0];
                }

                function updateInputDisplay(selectedDates, instance) {
                    if (selectedDates.length === 2) {
                        const startDate = selectedDates[0];
                        const endDate = selectedDates[1];
                        const formattedStart = formatDate(startDate);
                        const formattedEnd = formatDate(endDate);
                        instance.input.value = formattedStart + ' to ' + formattedEnd;
                    } else if (selectedDates.length === 1) {
                        const date = selectedDates[0];
                        instance.input.value = formatDate(date) + ' to ...';
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
                        const defaultDates = [new Date(defaultStartDate), new Date(defaultEndDate)];
                        updateInputDisplay(defaultDates, instance);
                    },
                    onChange: function (selectedDates, dateStr, instance) {
                        updateInputDisplay(selectedDates, instance);
                    },
                    onClose: function (selectedDates, dateStr, instance) {
                        if (selectedDates.length === 2) {
                            const startDate = selectedDates[0].toISOString().split('T')[0];
                            const endDate = selectedDates[1].toISOString().split('T')[0];
                            window.updatePageWithDateRange(startDate, endDate);
                        }
                    }
                });
            }
        });

    // Initialize Performance Score Circle
    function initPerformanceScoreCircle() {
        const scoreCircle = document.querySelector('.performance-score-circle');
        if (!scoreCircle) return;

        const score = parseInt(scoreCircle.getAttribute('data-score')) || 0;
        const color = scoreCircle.getAttribute('data-color') || 'success';
        
        // Set CSS variable for score percentage
        scoreCircle.style.setProperty('--score-percent', score + '%');
        scoreCircle.setAttribute('data-color', color);
    }

    // Initialize Performance Overview Chart
    function initPerformanceChart() {
        const chartElement = document.querySelector("#performance-overview-chart");
        if (!chartElement) {
            console.warn('Performance Overview Chart element not found');
            return;
        }

        const cpuData = chartData.cpu && chartData.cpu.length > 0 ? chartData.cpu : [];
        const memoryData = chartData.memory && chartData.memory.length > 0 ? chartData.memory : [];
        const diskData = chartData.disk && chartData.disk.length > 0 ? chartData.disk : [];
        const timestamps = chartData.timestamps && chartData.timestamps.length > 0 ? chartData.timestamps : [];

        console.log('Initializing Performance Chart with data:', {
            cpuDataLength: cpuData.length,
            memoryDataLength: memoryData.length,
            diskDataLength: diskData.length,
            timestampsLength: timestamps.length,
            range: currentRange,
            urlRange: new URLSearchParams(window.location.search).get('range') || '24h',
            sampleCpu: cpuData.slice(0, 3),
            sampleTimestamps: timestamps.slice(0, 3)
        });

        if (cpuData.length === 0 && memoryData.length === 0 && diskData.length === 0) {
            console.warn('No chart data available for range:', currentRange);
            chartElement.innerHTML = '<div class="text-center py-5"><p class="text-muted">No performance data available for the selected time range. Try selecting a different range or ensure the agent is sending data.</p></div>';
            return;
        }
        
        // Destroy existing chart if it exists
        if (performanceChart) {
            try {
                performanceChart.destroy();
            } catch (e) {
                console.warn('Error destroying existing chart:', e);
            }
            performanceChart = null;
        }
        
        const options = {
            series: [{
                name: 'CPU Usage',
                type: 'line',
                data: cpuData
            }, {
                name: 'Memory Usage',
                type: 'line',
                data: memoryData
            }, {
                name: 'Disk Usage',
                type: 'line',
                data: diskData
            }],
            chart: {
                height: 350,
                type: 'line',
                fontFamily: 'Poppins, Arial, sans-serif',
                toolbar: { show: true },
                zoom: { enabled: true }
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            colors: ['#5d87ff', '#49beff', '#ffae1f'],
            xaxis: {
                categories: timestamps,
                type: 'category',
                labels: {
                    rotate: -45,
                    rotateAlways: false
                }
            },
            yaxis: {
                max: 100,
                labels: {
                    formatter: function(val) {
                        return val.toFixed(0) + '%';
                    }
                }
            },
            legend: {
                show: true,
                position: 'top'
            },
            tooltip: {
                shared: true,
                intersect: false
            }
        };
        
        performanceChart = new ApexCharts(chartElement, options);
        performanceChart.render().then(() => {
            console.log('Performance Chart rendered successfully with', timestamps.length, 'data points for range', currentRange);
        }).catch((error) => {
            console.error('Error rendering Performance Chart:', error);
        });
    }

    // Initialize CPU Chart
    function initCPUChart() {
        const chartElement = document.querySelector("#cpu-chart");
        if (!chartElement) return;

        // Ensure data is in chronological order (oldest to newest, left to right)
        const cpuData = chartData.cpu && chartData.cpu.length > 0 ? [...chartData.cpu] : [];
        const timestamps = chartData.timestamps && chartData.timestamps.length > 0 ? [...chartData.timestamps] : [];

        // Verify data order - first timestamp should be oldest, last should be newest
        if (timestamps.length > 1) {
            console.log('CPU Chart - First timestamp:', timestamps[0], 'Last timestamp:', timestamps[timestamps.length - 1]);
        }

        if (cpuData.length === 0) {
            chartElement.innerHTML = '<div class="text-center py-3"><p class="text-muted small">No CPU data available</p></div>';
            return;
        }

        if (cpuChart) {
            cpuChart.destroy();
        }
        const options = {
            series: [{
                name: 'CPU Usage',
                data: cpuData
            }],
                chart: {
                    height: 250,
                    type: 'area',
                    fontFamily: 'Poppins, Arial, sans-serif',
                    toolbar: { show: false }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                colors: ['#5d87ff'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3
                    }
                },
            xaxis: {
                categories: timestamps,
                type: 'category',
                labels: {
                    rotate: -45,
                    rotateAlways: false
                }
            },
                yaxis: {
                    max: 100,
                    labels: {
                        formatter: function(val) {
                            return val.toFixed(0) + '%';
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val.toFixed(2) + '%';
                        }
                    }
                }
        };

        cpuChart = new ApexCharts(chartElement, options);
        cpuChart.render();
    }

    // Initialize Memory Chart
    function initMemoryChart() {
        const chartElement = document.querySelector("#memory-chart");
        if (!chartElement) return;

        // Ensure data is in chronological order (oldest to newest, left to right)
        const memoryData = chartData.memory && chartData.memory.length > 0 ? [...chartData.memory] : [];
        const timestamps = chartData.timestamps && chartData.timestamps.length > 0 ? [...chartData.timestamps] : [];

        if (memoryData.length === 0) {
            chartElement.innerHTML = '<div class="text-center py-3"><p class="text-muted small">No memory data available</p></div>';
            return;
        }

        if (memoryChart) {
            memoryChart.destroy();
        }

        const options = {
            series: [{
                name: 'Memory Usage',
                data: memoryData
            }],
                chart: {
                    height: 250,
                    type: 'area',
                    fontFamily: 'Poppins, Arial, sans-serif',
                    toolbar: { show: false }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                colors: ['#49beff'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3
                    }
                },
            xaxis: {
                categories: timestamps,
                type: 'category',
                labels: {
                    rotate: -45,
                    rotateAlways: false
                }
            },
                yaxis: {
                    max: 100,
                    labels: {
                        formatter: function(val) {
                            return val.toFixed(0) + '%';
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val.toFixed(2) + '%';
                        }
                    }
                }
        };

        memoryChart = new ApexCharts(chartElement, options);
        memoryChart.render();
    }

    // Initialize Disk Chart
    function initDiskChart() {
        const chartElement = document.querySelector("#disk-chart");
        if (!chartElement) return;

        // Ensure data is in chronological order (oldest to newest, left to right)
        const diskData = chartData.disk && chartData.disk.length > 0 ? [...chartData.disk] : [];
        const timestamps = chartData.timestamps && chartData.timestamps.length > 0 ? [...chartData.timestamps] : [];

        if (diskData.length === 0) {
            chartElement.innerHTML = '<div class="text-center py-3"><p class="text-muted small">No disk data available</p></div>';
            return;
        }

        if (diskChart) {
            diskChart.destroy();
        }

        const options = {
            series: [{
                name: 'Disk Usage',
                data: diskData
            }],
                chart: {
                    height: 250,
                    type: 'area',
                    fontFamily: 'Poppins, Arial, sans-serif',
                    toolbar: { show: false }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                colors: ['#ffae1f'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3
                    }
                },
            xaxis: {
                categories: timestamps,
                type: 'category',
                labels: {
                    rotate: -45,
                    rotateAlways: false
                }
            },
                yaxis: {
                    max: 100,
                    labels: {
                        formatter: function(val) {
                            return val.toFixed(0) + '%';
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val.toFixed(2) + '%';
                        }
                    }
                }
        };

        diskChart = new ApexCharts(chartElement, options);
        diskChart.render();
    }

    // Initialize Network Chart
    function initNetworkChart() {
        const chartElement = document.querySelector("#network-chart");
        if (!chartElement) return;

        if (!chartData.network || chartData.network.length === 0) {
            chartElement.innerHTML = '<div class="text-center py-3"><p class="text-muted small">No network data available</p></div>';
            return;
        }
        // Ensure data is in chronological order (oldest to newest, left to right)
        const networkSent = chartData.network.map(n => n.sent || 0);
        const networkReceived = chartData.network.map(n => n.received || 0);
        const timestamps = chartData.timestamps && chartData.timestamps.length > 0 ? [...chartData.timestamps] : [];

        if (networkChart) {
            networkChart.destroy();
        }

        const options = {
                series: [{
                    name: 'Bytes Sent',
                    data: networkSent
                }, {
                    name: 'Bytes Received',
                    data: networkReceived
                }],
            chart: {
                height: 250,
                type: 'line',
                fontFamily: 'Poppins, Arial, sans-serif',
                toolbar: { show: false }
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            colors: ['#13deb9', '#fa896b'],
            xaxis: {
                categories: timestamps,
                type: 'category',
                labels: {
                    rotate: -45,
                    rotateAlways: false
                }
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        if (val >= 1000000000) return (val / 1000000000).toFixed(2) + ' GB';
                        if (val >= 1000000) return (val / 1000000).toFixed(2) + ' MB';
                        if (val >= 1000) return (val / 1000).toFixed(2) + ' KB';
                        return val.toFixed(0) + ' B';
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        if (val >= 1000000000) return (val / 1000000000).toFixed(2) + ' GB';
                        if (val >= 1000000) return (val / 1000000).toFixed(2) + ' MB';
                        if (val >= 1000) return (val / 1000).toFixed(2) + ' KB';
                        return val.toFixed(0) + ' B';
                    }
                }
            },
            legend: {
                show: true,
                position: 'top'
            }
        };

        networkChart = new ApexCharts(chartElement, options);
        networkChart.render();
    }

    // Initialize Process Chart
    function initProcessChart() {
        const chartElement = document.querySelector("#process-chart");
        if (!chartElement) return;

        if (!chartData.processes || chartData.processes.length === 0) {
            chartElement.innerHTML = '<div class="text-center py-3"><p class="text-muted small">No process data available</p></div>';
            return;
        }

        // Ensure data is in chronological order (oldest to newest, left to right)
        const processTotal = chartData.processes.map(p => p.total || 0);
        const processRunning = chartData.processes.map(p => p.running || 0);
        const processSleeping = chartData.processes.map(p => p.sleeping || 0);
        const timestamps = chartData.timestamps && chartData.timestamps.length > 0 ? [...chartData.timestamps] : [];

        if (processChart) {
            processChart.destroy();
        }

        const options = {
            series: [{
                name: 'Total',
                data: processTotal
            }, {
                name: 'Running',
                data: processRunning
            }, {
                name: 'Sleeping',
                data: processSleeping
            }],
            chart: {
                height: 250,
                type: 'line',
                fontFamily: 'Poppins, Arial, sans-serif',
                toolbar: { show: false }
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            colors: ['#5d87ff', '#13deb9', '#fa896b'],
            xaxis: {
                categories: timestamps,
                type: 'category',
                labels: {
                    rotate: -45,
                    rotateAlways: false
                }
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return val.toFixed(0);
                    }
                }
            },
            tooltip: {
                shared: true,
                intersect: false
            },
            legend: {
                show: true,
                position: 'top'
            }
        };

        processChart = new ApexCharts(chartElement, options);
        processChart.render();
    }
    })(jQuery);
</script>
@endsection

