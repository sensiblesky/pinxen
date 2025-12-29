@extends('layouts.master')

@section('title', 'Server Monitoring - PingXeno')

@section('styles')
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .swal2-container {
        z-index: 99999 !important;
        position: fixed !important;
    }

    /* Circular Progress Indicators */
    .circular-progress {
        cursor: pointer;
        transition: transform 0.2s;
    }
    .circular-progress:hover {
        transform: scale(1.1);
    }
    .circular-progress[data-color="primary"] {
        color: #0d6efd;
    }
    .circular-progress[data-color="info"] {
        color: #0dcaf0;
    }
    .circular-progress[data-color="warning"] {
        color: #ffc107;
    }
    .circular-progress[data-color="danger"] {
        color: #dc3545;
    }
    .circular-progress[data-color="success"] {
        color: #198754;
    }
    .metric-circle {
        display: inline-block;
        flex-shrink: 0;
    }
    
    /* Metrics column styling */
    #servers-table td:nth-child(2) {
        width: auto;
        max-width: 220px;
        white-space: nowrap;
        padding: 8px 12px;
    }
    
    /* Network column styling */
    #servers-table td:nth-child(3) {
        width: auto;
        min-width: 90px;
        white-space: nowrap;
        padding: 8px 12px;
    }
    
    /* Prevent table from expanding unnecessarily */
    #servers-table {
        width: 100% !important;
    }
    
    /* Ensure table cells don't expand beyond content */
    #servers-table td {
        white-space: normal;
    }
    
    #servers-table td:nth-child(2),
    #servers-table td:nth-child(3) {
        white-space: nowrap;
    }

    /* Custom Tooltip */
    .tooltip-custom {
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        margin-bottom: 10px;
        z-index: 1000;
        pointer-events: none;
    }
    .tooltip-content {
        background: #333;
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        white-space: normal;
        min-width: 150px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    .tooltip-content div {
        margin: 2px 0;
    }
    .tooltip-content::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 6px solid transparent;
        border-top-color: #333;
    }
    .tooltip-custom.show {
        display: block !important;
    }
</style>
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Server Monitoring</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Server Monitoring</li>
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

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Servers Management</div>
                    <div class="card-options">
                        <a href="{{ route('servers.create') }}" class="btn btn-primary btn-wave btn-sm">
                            <i class="ri-add-line me-1"></i>Add Server
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($servers->count() > 0)
                        <div class="table-responsive">
                            <table id="servers-table" class="table table-bordered w-100" style="table-layout: auto;">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Name</th>
                                        <th>Metrics</th>
                                        <th>Network</th>
                                        <th>Status</th>
                                        <th>Last Seen</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($servers as $server)
                                        <tr>
                                            <td>
                                                <div>
                                                    <h6 class="mb-1 fw-semibold">{{ $server->name }}</h6>
                                                    @if($server->os_type)
                                                        <small class="badge bg-info-transparent text-info">
                                                            {{ ucfirst($server->os_type) }}
                                                            @if($server->os_version)
                                                                {{ $server->os_version }}
                                                            @endif
                                                        </small>
                                                    @else
                                                        <small class="text-muted">Unknown OS</small>
                                                    @endif
                                                    @if($server->description)
                                                        <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($server->description, 50) }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $latestStat = $server->latestStat;
                                                @endphp
                                                @if($latestStat)
                                                    <div class="d-flex align-items-center gap-2" style="flex-wrap: nowrap;">
                                                        <!-- CPU Indicator -->
                                                        <div class="position-relative metric-circle" style="width: 50px; height: 50px;">
                                                            @php
                                                                $cpuPercent = $latestStat->cpu_usage_percent ?? 0;
                                                                $circumference = 2 * pi() * 20;
                                                                $offset = $circumference * (1 - ($cpuPercent / 100));
                                                                $colorClass = $cpuPercent > 80 ? 'danger' : ($cpuPercent > 60 ? 'warning' : 'primary');
                                                            @endphp
                                                            <svg class="circular-progress" width="50" height="50" data-percent="{{ $cpuPercent }}" data-color="{{ $colorClass }}">
                                                                <circle cx="25" cy="25" r="20" fill="none" stroke="#e0e0e0" stroke-width="4"/>
                                                                <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="4" 
                                                                        stroke-dasharray="{{ $circumference }}" 
                                                                        stroke-dashoffset="{{ $offset }}"
                                                                        stroke-linecap="round" transform="rotate(-90 25 25)"/>
                                                            </svg>
                                                            <div class="position-absolute top-50 start-50 translate-middle text-center" style="font-size: 9px; font-weight: bold; color: #666;">
                                                                CPU
                                                            </div>
                                                            <div class="tooltip-custom" style="display: none;">
                                                                <div class="tooltip-content">
                                                                    <div><strong>CPU Usage:</strong> {{ number_format($cpuPercent, 1) }}%</div>
                                                                    @if($latestStat->cpu_cores)
                                                                        <div><strong>Cores:</strong> {{ $latestStat->cpu_cores }}</div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Memory Indicator -->
                                                        <div class="position-relative metric-circle" style="width: 50px; height: 50px;">
                                                            @php
                                                                $memPercent = $latestStat->memory_usage_percent ?? 0;
                                                                $circumference = 2 * pi() * 20;
                                                                $offset = $circumference * (1 - ($memPercent / 100));
                                                                $colorClass = $memPercent > 80 ? 'danger' : ($memPercent > 60 ? 'warning' : 'info');
                                                            @endphp
                                                            <svg class="circular-progress" width="50" height="50" data-percent="{{ $memPercent }}" data-color="{{ $colorClass }}">
                                                                <circle cx="25" cy="25" r="20" fill="none" stroke="#e0e0e0" stroke-width="4"/>
                                                                <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="4" 
                                                                        stroke-dasharray="{{ $circumference }}" 
                                                                        stroke-dashoffset="{{ $offset }}"
                                                                        stroke-linecap="round" transform="rotate(-90 25 25)"/>
                                                            </svg>
                                                            <div class="position-absolute top-50 start-50 translate-middle text-center" style="font-size: 9px; font-weight: bold; color: #666;">
                                                                MEM
                                                            </div>
                                                            <div class="tooltip-custom" style="display: none;">
                                                                <div class="tooltip-content">
                                                                    <div><strong>Memory Usage:</strong> {{ number_format($memPercent, 1) }}%</div>
                                                                    @if($latestStat->memory_total_bytes)
                                                                        <div><strong>Total:</strong> {{ \App\Models\ServerStat::formatBytes($latestStat->memory_total_bytes) }}</div>
                                                                        <div><strong>Used:</strong> {{ \App\Models\ServerStat::formatBytes($latestStat->memory_used_bytes ?? 0) }}</div>
                                                                        <div><strong>Free:</strong> {{ \App\Models\ServerStat::formatBytes($latestStat->memory_free_bytes ?? 0) }}</div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Disk Indicator -->
                                                        <div class="position-relative metric-circle" style="width: 50px; height: 50px;">
                                                            @php
                                                                $diskPercent = $latestStat->disk_usage_percent ?? 0;
                                                                $circumference = 2 * pi() * 20;
                                                                $offset = $circumference * (1 - ($diskPercent / 100));
                                                                $colorClass = $diskPercent > 90 ? 'danger' : ($diskPercent > 75 ? 'warning' : 'success');
                                                            @endphp
                                                            <svg class="circular-progress" width="50" height="50" data-percent="{{ $diskPercent }}" data-color="{{ $colorClass }}">
                                                                <circle cx="25" cy="25" r="20" fill="none" stroke="#e0e0e0" stroke-width="4"/>
                                                                <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="4" 
                                                                        stroke-dasharray="{{ $circumference }}" 
                                                                        stroke-dashoffset="{{ $offset }}"
                                                                        stroke-linecap="round" transform="rotate(-90 25 25)"/>
                                                            </svg>
                                                            <div class="position-absolute top-50 start-50 translate-middle text-center" style="font-size: 9px; font-weight: bold; color: #666;">
                                                                DISK
                                                            </div>
                                                            <div class="tooltip-custom" style="display: none;">
                                                                <div class="tooltip-content">
                                                                    <div><strong>Disk Usage:</strong> {{ number_format($diskPercent, 1) }}%</div>
                                                                    @if($latestStat->disk_total_bytes)
                                                                        <div><strong>Total:</strong> {{ \App\Models\ServerStat::formatBytes($latestStat->disk_total_bytes) }}</div>
                                                                        <div><strong>Used:</strong> {{ \App\Models\ServerStat::formatBytes($latestStat->disk_used_bytes ?? 0) }}</div>
                                                                        <div><strong>Free:</strong> {{ \App\Models\ServerStat::formatBytes($latestStat->disk_free_bytes ?? 0) }}</div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>
                                                @else
                                                    <span class="text-muted small">No metrics available</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $latestStat = $server->latestStat;
                                                @endphp
                                                @if($latestStat && ($latestStat->network_bytes_sent || $latestStat->network_bytes_received))
                                                    @php
                                                        // Get previous stat to calculate speed
                                                        $previousStat = $server->stats()
                                                            ->where('recorded_at', '<', $latestStat->recorded_at)
                                                            ->orderBy('recorded_at', 'desc')
                                                            ->first();
                                                        
                                                        $sentSpeed = 0;
                                                        $receivedSpeed = 0;
                                                        $hasSpeed = false;
                                                        
                                                        if ($previousStat && $latestStat->recorded_at && $previousStat->recorded_at) {
                                                            $timeDiff = $latestStat->recorded_at->diffInSeconds($previousStat->recorded_at);
                                                            if ($timeDiff > 0) {
                                                                $sentDiff = ($latestStat->network_bytes_sent ?? 0) - ($previousStat->network_bytes_sent ?? 0);
                                                                $receivedDiff = ($latestStat->network_bytes_received ?? 0) - ($previousStat->network_bytes_received ?? 0);
                                                                $sentSpeed = round($sentDiff / $timeDiff / 1024, 1); // KB/s
                                                                $receivedSpeed = round($receivedDiff / $timeDiff / 1024, 1); // KB/s
                                                                $hasSpeed = true;
                                                            }
                                                        }
                                                    @endphp
                                                    <div class="d-flex flex-column" style="font-size: 11px; line-height: 1.4;">
                                                        @if($latestStat->network_bytes_received !== null)
                                                            <div class="text-success" style="white-space: nowrap;">
                                                                @if($hasSpeed)
                                                                    {{ number_format($receivedSpeed, 1) }} KB/s ↓
                                                                @else
                                                                    {{ \App\Models\ServerStat::formatBytes($latestStat->network_bytes_received ?? 0) }} ↓
                                                                @endif
                                                            </div>
                                                        @endif
                                                        @if($latestStat->network_bytes_sent !== null)
                                                            <div class="text-primary" style="white-space: nowrap;">
                                                                @if($hasSpeed)
                                                                    {{ number_format($sentSpeed, 1) }} KB/s ↑
                                                                @else
                                                                    {{ \App\Models\ServerStat::formatBytes($latestStat->network_bytes_sent ?? 0) }} ↑
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $server->getStatusBadgeClass() }}">
                                                    <i class="ri-{{ $server->isOnline() ? 'checkbox-circle-line' : 'close-circle-line' }} me-1"></i>
                                                    {{ $server->getStatusText() }}
                                                </span>
                                            </td>
                                            <td data-sort="{{ $server->last_seen_at ? $server->last_seen_at->timestamp : 0 }}">
                                                @if($server->last_seen_at)
                                                    <span class="text-muted">{{ $server->last_seen_at->diffForHumans() }}</span>
                                                @else
                                                    <span class="text-muted">Never</span>
                                                @endif
                                            </td>
                                            <td data-sort="{{ $server->created_at->timestamp }}">
                                                <span class="text-muted">{{ $server->created_at->format('Y-m-d H:i') }}</span>
                                            </td>
                                            <td>
                                                <div class="btn-list">
                                                    <a href="{{ route('servers.show', $server) }}" class="btn btn-sm btn-info btn-wave" title="View">
                                                        <i class="ri-eye-line"></i>
                                                    </a>
                                                    <a href="{{ route('servers.edit', $server) }}" class="btn btn-sm btn-primary btn-wave" title="Edit">
                                                        <i class="ri-edit-line"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger btn-wave delete-server-btn" 
                                                            title="Delete"
                                                            data-uid="{{ $server->uid }}"
                                                            data-name="{{ $server->name }}">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="ri-server-line fs-48 text-muted mb-3 d-block"></i>
                            <h5 class="text-muted">No Servers Found</h5>
                            <p class="text-muted">Add your first server to start monitoring its performance.</p>
                            <a href="{{ route('servers.create') }}" class="btn btn-primary btn-wave">
                                <i class="ri-add-line me-1"></i>Add Server
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        @if($servers->count() > 0)
        // Initialize DataTables
        const table = $('#servers-table').DataTable({
            order: [[5, 'desc']], // Sort by created date (newest first)
            pageLength: 10,
            responsive: true,
            columnDefs: [
                { orderable: false, targets: [1, 2, 6] }, // Metrics, Network, and Actions columns not sortable
                { type: 'num', targets: [4, 5] } // Numeric sorting for dates
            ],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });
        @endif

        // Tooltip functionality for circular progress indicators
        $(document).on('mouseenter', '.circular-progress', function() {
            $(this).closest('.metric-circle').find('.tooltip-custom').addClass('show');
        });
        $(document).on('mouseleave', '.circular-progress', function() {
            $(this).closest('.metric-circle').find('.tooltip-custom').removeClass('show');
        });

        // Delete confirmation
        $(document).on('click', '.delete-server-btn', function() {
            const uid = $(this).data('uid');
            const name = $(this).data('name');
            
            Swal.fire({
                title: 'Are you sure?',
                html: `You are about to delete the server <strong>"${name}"</strong>. This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = $('<form>', {
                        method: 'POST',
                        action: `/servers/${uid}`
                    });
                    
                    form.append($('<input>', {
                        type: 'hidden',
                        name: '_token',
                        value: '{{ csrf_token() }}'
                    }));
                    
                    form.append($('<input>', {
                        type: 'hidden',
                        name: '_method',
                        value: 'DELETE'
                    }));
                    
                    $('body').append(form);
                    form.submit();
                }
            });
        });
    });
</script>
@endsection

