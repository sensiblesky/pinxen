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
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">{{ $monitor->name }}</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('monitors.index', ['category' => $monitor->serviceCategory->slug]) }}">Monitoring</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $monitor->name }}</li>
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

    <!-- Start::row-2 - Charts -->
    <div class="row">
        <!-- Response Time Chart -->
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">Response Time (Last 24 Hours)</div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-primary btn-wave" onclick="updateChartTimeRange('24h')">24H</button>
                        <button type="button" class="btn btn-primary-light btn-wave" onclick="updateChartTimeRange('7d')">7D</button>
                        <button type="button" class="btn btn-primary-light btn-wave" onclick="updateChartTimeRange('30d')">30D</button>
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
    <!-- End::row-2 -->

    <!-- Start::row-3 - Uptime Chart -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">Uptime Percentage (Last 24 Hours)</div>
                </div>
                <div class="card-body pb-0 pt-5">
                    <div id="uptime-chart"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-3 -->

    <!-- Start::row-4 - Monitor Details & History -->
    <div class="row">
        <!-- Monitor Details -->
        <div class="col-xl-4">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Monitor Details</div>
                    <div class="card-options">
                        <a href="{{ route('monitors.edit', $monitor->uid) }}" class="btn btn-sm btn-primary btn-wave">
                            <i class="ri-edit-line me-1"></i>Edit
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted">Name</label>
                        <div class="fw-semibold">{{ $monitor->name }}</div>
                    </div>

                    @if($monitor->url)
                    <div class="mb-3">
                        <label class="form-label text-muted">URL</label>
                        <div>
                            <a href="{{ $monitor->url }}" target="_blank" class="text-primary">
                                {{ $monitor->url }}
                                <i class="ri-external-link-line ms-1"></i>
                            </a>
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label text-muted">Service Type</label>
                        <div class="fw-semibold">{{ $monitor->monitoringService->name ?? 'N/A' }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Category</label>
                        <div class="fw-semibold">{{ $monitor->serviceCategory->name ?? 'N/A' }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Check Interval</label>
                        <div class="fw-semibold">{{ $monitor->check_interval }} minutes</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Timeout</label>
                        <div class="fw-semibold">{{ $monitor->timeout }} seconds</div>
                    </div>

                    @if($monitor->expected_status_code)
                    <div class="mb-3">
                        <label class="form-label text-muted">Expected Status Code</label>
                        <div class="fw-semibold">{{ $monitor->expected_status_code }}</div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label text-muted">Last Checked</label>
                        <div class="fw-semibold">
                            @if($monitor->last_checked_at)
                                {{ $monitor->last_checked_at->diffForHumans() }}
                                <small class="text-muted d-block">{{ $monitor->last_checked_at->format('Y-m-d H:i:s') }}</small>
                            @else
                                <span class="text-muted">Never</span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Created At</label>
                        <div class="fw-semibold">{{ $monitor->created_at->format('Y-m-d H:i:s') }}</div>
                    </div>
                </div>
            </div>

            <!-- Communication Channels -->
            <div class="card custom-card mt-3">
                <div class="card-header">
                    <div class="card-title">Alert Channels</div>
                </div>
                <div class="card-body">
                    @if($monitor->communicationPreferences->isEmpty())
                        <p class="text-muted mb-0">No communication channels configured.</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach($monitor->communicationPreferences as $pref)
                                <li class="mb-2">
                                    <div class="d-flex align-items-center">
                                        @if($pref->communication_channel === 'email')
                                            <i class="ri-mail-line me-2 text-primary"></i>
                                        @elseif($pref->communication_channel === 'sms')
                                            <i class="ri-message-3-line me-2 text-success"></i>
                                        @elseif($pref->communication_channel === 'whatsapp')
                                            <i class="ri-whatsapp-line me-2 text-success"></i>
                                        @elseif($pref->communication_channel === 'telegram')
                                            <i class="ri-telegram-line me-2 text-info"></i>
                                        @elseif($pref->communication_channel === 'discord')
                                            <i class="ri-discord-line me-2 text-primary"></i>
                                        @endif
                                        <span class="text-capitalize">{{ $pref->communication_channel }}</span>
                                        @if(!$pref->is_enabled)
                                            <span class="badge bg-secondary-transparent text-secondary ms-2">Disabled</span>
                                        @endif
                                    </div>
                                    <small class="text-muted ms-4">{{ Str::limit($pref->channel_value, 40) }}</small>
                                </li>
                            @endforeach
                        </ul>
                    @endif
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
                    @if($recentChecks->isEmpty())
                        <div class="text-center py-5">
                            <div class="avatar avatar-xl avatar-rounded bg-secondary-transparent mb-3">
                                <i class="ri-time-line fs-36"></i>
                            </div>
                            <h5 class="mb-2">No Checks Yet</h5>
                            <p class="text-muted mb-0">This monitor hasn't been checked yet.</p>
                        </div>
                    @else
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
                                    @foreach($recentChecks as $index => $check)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                @if($check->status === 'up')
                                                    <span class="badge bg-success-transparent text-success">
                                                        <i class="ri-checkbox-circle-line me-1"></i>Up
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger-transparent text-danger">
                                                        <i class="ri-close-circle-line me-1"></i>Down
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($check->response_time)
                                                    <span class="fw-semibold">{{ $check->response_time }}ms</span>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($check->status_code)
                                                    <span class="badge bg-info-transparent text-info">{{ $check->status_code }}</span>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($check->error_message)
                                                    <span class="text-danger" title="{{ $check->error_message }}">
                                                        {{ Str::limit($check->error_message, 50) }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $check->checked_at->diffForHumans() }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
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

        // Response Time Chart
        let responseTimeChart;
        function initResponseTimeChart() {
            // Format data for ApexCharts (handle null values)
            const chartData = responseTimeData.map(item => ({
                x: new Date(item.x).getTime(), // Convert to timestamp for time series
                y: item.y
            })).filter(item => item.y !== null && item.y !== undefined); // Remove null values
            
            // If no data, show empty state
            if (chartData.length === 0) {
                document.querySelector("#response-time-chart").innerHTML = 
                    '<div class="text-center py-5"><p class="text-muted">No response time data available</p></div>';
                return;
            }
            
            const options = {
                series: [{
                    name: 'Response Time (ms)',
                    data: chartData
                }],
                chart: {
                    type: 'line',
                    height: 350,
                    fontFamily: 'Poppins, Arial, sans-serif',
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: true,
                            zoom: true,
                            zoomin: true,
                            zoomout: true,
                            pan: true,
                            reset: true
                        }
                    },
                    zoom: {
                        enabled: true
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                colors: ['#5d87ff'],
                grid: {
                    borderColor: '#f2f6f7',
                },
                xaxis: {
                    type: 'datetime',
                    labels: {
                        style: {
                            colors: '#8c9097'
                        },
                        datetimeUTC: false,
                        format: 'MMM dd HH:mm'
                    }
                },
                yaxis: {
                    title: {
                        text: 'Response Time (ms)'
                    },
                    labels: {
                        style: {
                            colors: '#8c9097'
                        }
                    }
                },
                tooltip: {
                    x: {
                        format: 'MMM dd, yyyy HH:mm'
                    },
                    y: {
                        formatter: function (val) {
                            return val ? val.toFixed(2) + " ms" : "N/A";
                        }
                    }
                }
            };
            responseTimeChart = new ApexCharts(document.querySelector("#response-time-chart"), options);
            responseTimeChart.render();
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

        // Uptime Chart
        let uptimeChart;
        function initUptimeChart() {
            // Format data for ApexCharts
            const chartData = uptimeData.map(item => ({
                x: new Date(item.x).getTime(),
                y: item.y
            }));
            
            // If no data, show empty state
            if (chartData.length === 0) {
                document.querySelector("#uptime-chart").innerHTML = 
                    '<div class="text-center py-5"><p class="text-muted">No uptime data available</p></div>';
                return;
            }
            
            const options = {
                series: [{
                    name: 'Uptime %',
                    data: chartData
                }],
                chart: {
                    type: 'area',
                    height: 350,
                    fontFamily: 'Poppins, Arial, sans-serif',
                    toolbar: {
                        show: true
                    },
                    zoom: {
                        enabled: true
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3,
                        stops: [0, 90, 100]
                    }
                },
                colors: ['#28a745'],
                grid: {
                    borderColor: '#f2f6f7',
                },
                xaxis: {
                    type: 'datetime',
                    labels: {
                        style: {
                            colors: '#8c9097'
                        },
                        datetimeUTC: false,
                        format: 'MMM dd HH:mm'
                    }
                },
                yaxis: {
                    min: 0,
                    max: 100,
                    title: {
                        text: 'Uptime %'
                    },
                    labels: {
                        style: {
                            colors: '#8c9097'
                        },
                        formatter: function (val) {
                            return val.toFixed(0) + "%";
                        }
                    }
                },
                tooltip: {
                    x: {
                        format: 'MMM dd, yyyy HH:mm'
                    },
                    y: {
                        formatter: function (val) {
                            return val.toFixed(2) + "%";
                        }
                    }
                }
            };
            uptimeChart = new ApexCharts(document.querySelector("#uptime-chart"), options);
            uptimeChart.render();
        }

        // Update chart time range (placeholder for future implementation)
        function updateChartTimeRange(range) {
            // TODO: Implement AJAX call to fetch data for different time ranges
            console.log('Time range changed to:', range);
        }

        $(document).ready(function() {
            // Initialize charts
            if (responseTimeData.length > 0) {
                initResponseTimeChart();
            }
            if (statusDistribution.length > 0) {
                initStatusDistributionChart();
            }
            if (uptimeData.length > 0) {
                initUptimeChart();
            }

            // Initialize DataTables
            @if($recentChecks->isNotEmpty())
            $('#checks-table').DataTable({
                responsive: true,
                order: [[5, 'desc']],
                pageLength: 10,
            });
            @endif

            @if($monitor->alerts->isNotEmpty())
            $('#alerts-table').DataTable({
                responsive: true,
                order: [[5, 'desc']],
                pageLength: 10,
            });
            @endif
        });
    </script>
@endsection
