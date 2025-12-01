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
                            <h1 class="page-title fw-medium fs-18 mb-0">Users Management</h1>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Users</li>
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
                                        All Users
                                    </div>
                                    <a href="{{ route('panel.users.create') }}" class="btn btn-primary btn-wave">
                                        <i class="ri-add-line me-1"></i>Add New User
                                    </a>
                                </div>
                                <div class="card-body">
                                    <!-- Filters -->
                                    <div class="row mb-3">
                                        <div class="col-md-2">
                                            <label class="form-label">Filter by Email</label>
                                            <input type="text" id="filter_email" class="form-control" placeholder="Search email...">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Filter by Phone</label>
                                            <input type="text" id="filter_phone" class="form-control" placeholder="Search phone...">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Filter by Role</label>
                                            <select id="filter_role" class="form-select">
                                                <option value="">All Roles</option>
                                                <option value="1">Admin</option>
                                                <option value="2">User</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Account Status</label>
                                            <select id="filter_status" class="form-select">
                                                <option value="">All Status</option>
                                                <option value="1">Active</option>
                                                <option value="0">Inactive</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Deleted Status</label>
                                            <select id="filter_deleted" class="form-select">
                                                <option value="">All Users</option>
                                                <option value="0">Not Deleted</option>
                                                <option value="1">Deleted</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="button" id="clear_filters" class="btn btn-secondary btn-wave me-2">
                                                <i class="ri-refresh-line me-1"></i>Clear Filters
                                            </button>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table id="users-datatable" class="table table-bordered text-nowrap w-100">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Role</th>
                                                    <th>Status</th>
                                                    <th>Created At</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded via AJAX -->
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Role</th>
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
                    <!-- End:: row-1 -->

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
        
        <!-- Sweetalerts JS -->
        <script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>

        <script>
            $(document).ready(function() {
                var table = $('#users-datatable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('panel.users.data') }}",
                        type: 'GET',
                        data: function(d) {
                            d.filter_email = $('#filter_email').val();
                            d.filter_phone = $('#filter_phone').val();
                            d.filter_role = $('#filter_role').val();
                            d.filter_status = $('#filter_status').val();
                            d.filter_deleted = $('#filter_deleted').val();
                        },
                        error: function(xhr, error, thrown) {
                            console.error('DataTables AJAX Error:', {
                                xhr: xhr,
                                error: error,
                                thrown: thrown,
                                responseText: xhr.responseText
                            });
                            
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: 'Error Loading Data',
                                    html: 'An error occurred while loading the table data.<br><br>Please check the console for details or refresh the page.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            } else {
                                alert('Error loading data. Please check the console for details.');
                            }
                        }
                    },
                    columns: [
                        { data: 'row_number', name: 'row_number', orderable: false, searchable: false },
                        { data: 'name', name: 'name' },
                        { data: 'email', name: 'email' },
                        { data: 'phone', name: 'phone' },
                        { data: 'role_badge', name: 'role', orderable: true, searchable: false },
                        { data: 'status_badge', name: 'status', orderable: true, searchable: false },
                        { data: 'created_at', name: 'created_at' },
                        { data: 'actions', name: 'actions', orderable: false, searchable: false }
                    ],
                    responsive: true,
                    order: [[6, 'desc']], // Order by Created At column (index 6)
                    pageLength: 100,
                    dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ],
                    columnDefs: [
                        {
                            targets: [0, 7], // Row number and Actions columns
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

                // Apply filters on change
                $('#filter_email, #filter_phone, #filter_role, #filter_status, #filter_deleted').on('keyup change', function() {
                    table.draw();
                });

                // Clear filters
                $('#clear_filters').on('click', function() {
                    $('#filter_email').val('');
                    $('#filter_phone').val('');
                    $('#filter_role').val('');
                    $('#filter_status').val('');
                    $('#filter_deleted').val('');
                    table.draw();
                });
                
                // Delete user confirmation with SweetAlert
                $(document).on('click', '.delete-user-btn', function(e) {
                    e.preventDefault();
                    const form = $(this).closest('form');
                    const userName = form.data('user-name') || 'this user';
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Are you sure?',
                            html: `You are about to <strong>delete</strong> the account for <strong>${userName}</strong>.<br><br>This action cannot be undone!`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: '<i class="ri-delete-bin-line me-1"></i>Yes, delete it!',
                            cancelButtonText: '<i class="ri-close-line me-1"></i>Cancel',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    } else {
                        // Fallback to native confirm
                        if (confirm(`Are you sure you want to delete ${userName}? This action cannot be undone!`)) {
                            form.submit();
                        }
                    }
                });

                // Restore user confirmation with SweetAlert
                $(document).on('click', '.restore-user-btn', function(e) {
                    e.preventDefault();
                    const form = $(this).closest('form');
                    const userName = form.data('user-name') || 'this user';
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Restore User?',
                            html: `You are about to <strong>restore</strong> the account for <strong>${userName}</strong>.<br><br>The user will be able to access their account again.`,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: '<i class="ri-restart-line me-1"></i>Yes, restore it!',
                            cancelButtonText: '<i class="ri-close-line me-1"></i>Cancel',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    } else {
                        // Fallback to native confirm
                        if (confirm(`Are you sure you want to restore ${userName}?`)) {
                            form.submit();
                        }
                    }
                });
            });
        </script>

@endsection
