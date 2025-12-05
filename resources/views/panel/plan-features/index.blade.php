@extends('layouts.master')

@section('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">
    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.css')}}">
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Plan Features</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                <li class="breadcrumb-item"><a href="{{ route('panel.subscription-plans.index') }}">Subscription</a></li>
                <li class="breadcrumb-item active" aria-current="page">Plan Features</li>
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
                <div class="card-header justify-content-between">
                    <div class="card-title">
                        All Plan Features
                    </div>
                    <a href="{{ route('panel.plan-features.create') }}" class="btn btn-primary btn-wave">
                        <i class="ri-add-line me-1"></i>Add New Feature
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="plan-features-datatable" class="table table-bordered text-nowrap w-100">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Icon</th>
                                    <th>Plans Count</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($features as $index => $feature)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td><strong>{{ $feature->name }}</strong></td>
                                        <td>{{ Str::limit($feature->description, 60) }}</td>
                                        <td>
                                            @if($feature->icon)
                                                <div class="d-inline-block">{!! $feature->icon !!}</div>
                                            @else
                                                <i class="ri-star-line fs-18"></i>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $feature->subscriptionPlans->count() }} plans</span>
                                        </td>
                                        <td>{{ $feature->order }}</td>
                                        <td>
                                            @if($feature->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>{{ $feature->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <div class="btn-list">
                                                <a href="{{ route('panel.plan-features.show', $feature->uid) }}" class="btn btn-sm btn-info btn-wave" data-bs-toggle="tooltip" title="View">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                <a href="{{ route('panel.plan-features.edit', $feature->uid) }}" class="btn btn-sm btn-warning btn-wave" data-bs-toggle="tooltip" title="Edit">
                                                    <i class="ri-edit-line"></i>
                                                </a>
                                                <form action="{{ route('panel.plan-features.destroy', $feature->uid) }}" method="POST" class="d-inline delete-feature-form" data-feature-name="{{ $feature->name }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-sm btn-danger btn-wave delete-feature-btn" data-bs-toggle="tooltip" title="Delete">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Icon</th>
                                    <th>Plans Count</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->
@endsection

@section('scripts')
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
    
    <!-- SweetAlert JS -->
    <script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>

    <script>
        $(document).ready(function() {
            var table = $('#plan-features-datatable').DataTable({
                responsive: true,
                order: [[7, 'desc']], // Order by Created At column (index 7)
                pageLength: 25,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                columnDefs: [
                    {
                        targets: [0, 8], // Row number and Actions columns
                        orderable: false,
                        searchable: false
                    }
                ],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                    searchPlaceholder: 'Search globally...',
                    sSearch: '',
                }
            });

            // Delete feature confirmation with SweetAlert
            $(document).on('click', '.delete-feature-btn', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                const featureName = form.data('feature-name') || 'this feature';
                
                Swal.fire({
                    title: 'Are you sure?',
                    html: `You are about to delete <strong>${featureName}</strong>. This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection

