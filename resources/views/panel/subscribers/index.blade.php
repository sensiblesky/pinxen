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
            <h1 class="page-title fw-medium fs-18 mb-0">Subscribers</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                <li class="breadcrumb-item"><a href="{{ route('panel.subscription-plans.index') }}">Subscription</a></li>
                <li class="breadcrumb-item active" aria-current="page">Subscribers</li>
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
                        All Subscribers
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Filter by Status</label>
                            <select id="filter_status" class="form-select">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="expired">Expired</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" id="clear_filters" class="btn btn-secondary btn-wave">
                                <i class="ri-refresh-line me-1"></i>Clear Filters
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="subscribers-datatable" class="table table-bordered text-nowrap w-100">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Plan</th>
                                    <th>Billing Period</th>
                                    <th>Price</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Assigned By</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTables will populate this -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Plan</th>
                                    <th>Billing Period</th>
                                    <th>Price</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Assigned By</th>
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
            var table = $('#subscribers-datatable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: '{{ route("panel.subscribers.data") }}',
                    type: 'GET',
                    data: function(d) {
                        d.filter_status = $('#filter_status').val();
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables error:', error);
                    }
                },
                order: [[9, 'desc']], // Order by Created At column (index 9)
                pageLength: 25,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                columnDefs: [
                    {
                        targets: [0, 10], // Row number and Actions columns
                        orderable: false,
                        searchable: false
                    }
                ],
                columns: [
                    { data: 'row_number', name: 'row_number', orderable: false, searchable: false },
                    { data: 'user', name: 'user.name' },
                    { data: 'plan', name: 'subscriptionPlan.name' },
                    { data: 'billing_period', name: 'billing_period' },
                    { data: 'price', name: 'price' },
                    { data: 'starts_at', name: 'starts_at' },
                    { data: 'ends_at', name: 'ends_at' },
                    { data: 'status', name: 'status' },
                    { data: 'assigned_by', name: 'assignedBy.name' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                    searchPlaceholder: 'Search globally...',
                    sSearch: '',
                }
            });

            // Filter by status
            $('#filter_status').on('change', function() {
                table.ajax.reload();
            });

            // Clear filters
            $('#clear_filters').on('click', function() {
                $('#filter_status').val('');
                table.ajax.reload();
            });

            // Cancel subscription confirmation
            $(document).on('click', '.cancel-subscription-btn', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                const userName = form.data('user-name') || 'this user';
                const planName = form.data('plan-name') || 'this subscription';
                
                Swal.fire({
                    title: 'Cancel Subscription?',
                    html: `You are about to <strong>cancel</strong> the subscription for <strong>${userName}</strong> on plan <strong>${planName}</strong>.<br><br>This action will mark the subscription as cancelled.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="ri-close-circle-line me-1"></i>Yes, cancel it!',
                    cancelButtonText: '<i class="ri-close-line me-1"></i>Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Expire subscription confirmation
            $(document).on('click', '.expire-subscription-btn', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                const userName = form.data('user-name') || 'this user';
                const planName = form.data('plan-name') || 'this subscription';
                
                Swal.fire({
                    title: 'Mark as Expired?',
                    html: `You are about to mark the subscription for <strong>${userName}</strong> on plan <strong>${planName}</strong> as <strong>expired</strong>.<br><br>This action will immediately expire the subscription.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="ri-time-line me-1"></i>Yes, expire it!',
                    cancelButtonText: '<i class="ri-close-line me-1"></i>Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection

