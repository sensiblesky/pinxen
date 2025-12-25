@extends('layouts.master')

@section('styles')
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.css')}}">
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">{{ $category->name }} Monitoring</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('monitors.index', ['category' => 'web']) }}">Monitoring</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
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
                    <div class="card-title">{{ $category->name }} Monitors</div>
                    <div class="card-options">
                        <a href="{{ route('monitors.create', ['category' => $category->slug]) }}" class="btn btn-primary btn-wave btn-sm">
                            <i class="ri-add-line me-1"></i>Add Monitor
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Category Tabs -->
                    <ul class="nav nav-tabs mb-3 border-0" role="tablist">
                        @foreach($categories as $cat)
                            <li class="nav-item">
                                <a class="nav-link {{ $category->id === $cat->id ? 'active' : '' }}" 
                                   href="{{ route('monitors.index', ['category' => $cat->slug]) }}">
                                    <i class="{{ $cat->icon }} me-1"></i>{{ $cat->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Service Type Filters (for Web category) -->
                    @if($category->slug === 'web' && $availableServices->isNotEmpty())
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <label class="form-label text-muted mb-1">Filter by Service Type:</label>
                                </div>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('monitors.index', ['category' => $category->slug]) }}" 
                                       class="btn {{ !$selectedService ? 'btn-primary' : 'btn-outline-primary' }} btn-wave">
                                        All Services
                                    </a>
                                    @foreach($availableServices->flatten() as $service)
                                        <a href="{{ route('monitors.index', ['category' => $category->slug, 'service' => $service->key]) }}" 
                                           class="btn {{ $selectedService === $service->key ? 'btn-primary' : 'btn-outline-primary' }} btn-wave"
                                           title="{{ $service->description }}">
                                            <i class="{{ $service->icon }} me-1"></i>{{ $service->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($monitors->isEmpty())
                        <div class="text-center py-5">
                            <div class="avatar avatar-xl avatar-rounded bg-secondary-transparent mb-3">
                                <i class="{{ $category->icon }} fs-36"></i>
                            </div>
                            <h5 class="mb-2">No {{ $category->name }} Monitors</h5>
                            <p class="text-muted mb-4">Get started by creating your first {{ strtolower($category->name) }} monitor.</p>
                            <a href="{{ route('monitors.create', ['category' => $category->slug]) }}" class="btn btn-primary btn-wave">
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
                                        <th>Service Type</th>
                                        <th>URL/Endpoint</th>
                                        <th>Status</th>
                                        <th>Last Check</th>
                                        <th>Check Interval</th>
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
                                                    @if(!$monitor->is_active)
                                                        <span class="badge bg-secondary-transparent text-secondary ms-2">Inactive</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($monitor->monitoringService)
                                                    <span class="badge bg-info-transparent text-info" title="{{ $monitor->monitoringService->description }}">
                                                        <i class="{{ $monitor->monitoringService->icon }} me-1"></i>{{ $monitor->monitoringService->name }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($monitor->url)
                                                    <a href="{{ $monitor->url }}" target="_blank" class="text-primary">
                                                        {{ Str::limit($monitor->url, 50) }}
                                                        <i class="ri-external-link-line ms-1"></i>
                                                    </a>
                                                @elseif($monitor->service_config && isset($monitor->service_config['domain']))
                                                    <span class="text-muted">{{ $monitor->service_config['domain'] }}</span>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($monitor->status === 'up')
                                                    <span class="badge bg-success-transparent text-success">
                                                        <i class="ri-checkbox-circle-line me-1"></i>Up
                                                    </span>
                                                @elseif($monitor->status === 'down')
                                                    <span class="badge bg-danger-transparent text-danger">
                                                        <i class="ri-close-circle-line me-1"></i>Down
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary-transparent text-secondary">
                                                        <i class="ri-question-line me-1"></i>Unknown
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
                                                <span class="badge bg-info-transparent text-info">{{ $monitor->check_interval }} min</span>
                                            </td>
                                            <td>
                                                <div class="btn-list">
                                                    <a href="{{ route('monitors.show', $monitor->uid) }}" class="btn btn-sm btn-info btn-wave">
                                                        <i class="ri-eye-line me-1"></i>View
                                                    </a>
                                                    <a href="{{ route('monitors.edit', $monitor->uid) }}" class="btn btn-sm btn-primary btn-wave">
                                                        <i class="ri-edit-line me-1"></i>Edit
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger btn-wave" onclick="deleteMonitor('{{ $monitor->uid }}', '{{ $monitor->name }}')">
                                                        <i class="ri-delete-bin-line me-1"></i>Delete
                                                    </button>
                                                </div>
                                                <form id="delete-form-{{ $monitor->uid }}" action="{{ route('monitors.destroy', $monitor->uid) }}" method="POST" style="display: none;">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
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
    <!-- Jquery Cdn -->
    <script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
    <!-- Datatables Cdn -->
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.6/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <!-- Sweetalerts JS -->
    <script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>

    <script>
        $(document).ready(function() {
            @if($monitors->isNotEmpty())
            var monitorsTable = $('#monitors-table').DataTable({
                responsive: true,
                order: [[0, 'asc']],
                pageLength: 10,
                dom: 'lBfrtip',
                buttons: [
                    { extend: 'copy', className: 'btn btn-sm btn-light' },
                    { extend: 'csv', className: 'btn btn-sm btn-light' },
                    { extend: 'excel', className: 'btn btn-sm btn-light' },
                    { extend: 'pdf', className: 'btn btn-sm btn-light' },
                    { extend: 'print', className: 'btn btn-sm btn-light' }
                ],
            });
            @endif
        });

        function deleteMonitor(uid, name) {
            Swal.fire({
                title: 'Delete Monitor?',
                html: `Are you sure you want to delete <strong>${name}</strong>?<br><br>This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="ri-delete-bin-line me-1"></i>Yes, delete it!',
                cancelButtonText: '<i class="ri-close-line me-1"></i>Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + uid).submit();
                }
            });
        }
    </script>
@endsection

