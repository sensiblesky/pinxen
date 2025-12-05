@extends('layouts.master')

@section('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">User Reports</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                <li class="breadcrumb-item"><a href="{{ route('panel.reports.users') }}">Reports</a></li>
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
                <div class="card-header">
                    <div class="card-title">Export User Report</div>
                </div>
                <div class="card-body">
                    <form action="{{ route('panel.reports.users.export') }}" method="GET" id="export-form">
                        <div class="row gy-3">
                            <div class="col-md-3">
                                <label for="role" class="form-label">Filter by Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="">All Roles</option>
                                    <option value="1" {{ request('role') == '1' ? 'selected' : '' }}>Admin</option>
                                    <option value="2" {{ request('role') == '2' ? 'selected' : '' }}>Client</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="status" class="form-label">Filter by Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="deleted" {{ request('status') == 'deleted' ? 'selected' : '' }}>Deleted</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="email_verified" class="form-label">Filter by Email Verification</label>
                                <select class="form-select" id="email_verified" name="email_verified">
                                    <option value="">All</option>
                                    <option value="verified" {{ request('email_verified') == 'verified' ? 'selected' : '' }}>Verified</option>
                                    <option value="unverified" {{ request('email_verified') == 'unverified' ? 'selected' : '' }}>Unverified</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="subscription_plan" class="form-label">Filter by Subscription Plan</label>
                                <select class="form-select" id="subscription_plan" name="subscription_plan">
                                    <option value="">All Plans</option>
                                    @foreach($subscriptionPlans as $plan)
                                        <option value="{{ $plan->id }}" {{ request('subscription_plan') == $plan->id ? 'selected' : '' }}>
                                            {{ $plan->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="language" class="form-label">Filter by Language</label>
                                <select class="form-select" id="language" name="language">
                                    <option value="">All Languages</option>
                                    @foreach($languages as $language)
                                        <option value="{{ $language->id }}" {{ request('language') == $language->id ? 'selected' : '' }}>
                                            {{ $language->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="timezone" class="form-label">Filter by Timezone</label>
                                <select class="form-select" id="timezone" name="timezone">
                                    <option value="">All Timezones</option>
                                    @foreach($timezones as $timezone)
                                        <option value="{{ $timezone->id }}" {{ request('timezone') == $timezone->id ? 'selected' : '' }}>
                                            {{ $timezone->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="two_factor" class="form-label">Filter by Two Factor</label>
                                <select class="form-select" id="two_factor" name="two_factor">
                                    <option value="">All</option>
                                    <option value="enabled" {{ request('two_factor') == 'enabled' ? 'selected' : '' }}>Enabled</option>
                                    <option value="disabled" {{ request('two_factor') == 'disabled' ? 'selected' : '' }}>Disabled</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}">
                                <small class="text-muted">Filter users created from this date</small>
                            </div>

                            <div class="col-md-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
                                <small class="text-muted">Filter users created until this date</small>
                            </div>

                            <div class="col-md-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-wave" formaction="{{ route('panel.reports.users.export') }}">
                                        <i class="ri-file-download-line me-1"></i>Export as CSV
                                    </button>
                                    <button type="submit" class="btn btn-success btn-wave" formaction="{{ route('panel.reports.users.export-excel') }}">
                                        <i class="ri-file-excel-line me-1"></i>Export as Excel
                                    </button>
                                    <a href="{{ route('panel.reports.users') }}" class="btn btn-secondary btn-wave">
                                        <i class="ri-refresh-line me-1"></i>Reset Filters
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <hr class="my-4">

                    <!-- Summary Cards - Row 1 -->
                    <div class="row mb-3" id="summary-cards">
                        <div class="col-md-3 col-lg-2">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar avatar-md bg-primary text-white rounded">
                                                <i class="ri-user-line fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 text-muted small">Total Users</h6>
                                            <h5 class="mb-0" id="total-users">0</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <div class="card border-success">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar avatar-md bg-success text-white rounded">
                                                <i class="ri-checkbox-circle-line fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 text-muted small">Active Users</h6>
                                            <h5 class="mb-0 text-success" id="active-users">0</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <div class="card border-warning">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar avatar-md bg-warning text-white rounded">
                                                <i class="ri-pause-circle-line fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 text-muted small">Inactive Users</h6>
                                            <h5 class="mb-0 text-warning" id="inactive-users">0</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <div class="card border-danger">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar avatar-md bg-danger text-white rounded">
                                                <i class="ri-delete-bin-line fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 text-muted small">Deleted Users</h6>
                                            <h5 class="mb-0 text-danger" id="deleted-users">0</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <div class="card border-info">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar avatar-md bg-info text-white rounded">
                                                <i class="ri-mail-check-line fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 text-muted small">Verified</h6>
                                            <h5 class="mb-0 text-info" id="verified-users">0</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <div class="card border-secondary">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar avatar-md bg-secondary text-white rounded">
                                                <i class="ri-mail-close-line fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 text-muted small">Unverified</h6>
                                            <h5 class="mb-0" id="unverified-users">0</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards - Row 2 -->
                    <div class="row mb-4">
                        <div class="col-md-3 col-lg-2">
                            <div class="card border-danger">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar avatar-md bg-danger text-white rounded">
                                                <i class="ri-shield-user-line fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 text-muted small">Admins</h6>
                                            <h5 class="mb-0 text-danger" id="admin-users">0</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar avatar-md bg-primary text-white rounded">
                                                <i class="ri-user-3-line fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 text-muted small">Clients</h6>
                                            <h5 class="mb-0 text-primary" id="client-users">0</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <div class="card border-info">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar avatar-md bg-info text-white rounded">
                                                <i class="ri-lock-password-line fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 text-muted small">2FA Enabled</h6>
                                            <h5 class="mb-0 text-info" id="two-factor-enabled">0</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <div class="card border-success">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar avatar-md bg-success text-white rounded">
                                                <i class="ri-vip-crown-line fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 text-muted small">With Subscription</h6>
                                            <h5 class="mb-0 text-success" id="users-with-subscription">0</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-light">
                                <div class="card-body">
                                    <h6 class="mb-2 text-muted small">Users by Role</h6>
                                    <div id="users-by-role" class="small">
                                        <span class="text-muted">No data available</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DataTable -->
                    <div class="table-responsive">
                        <table id="users-report-datatable" class="table table-bordered text-nowrap w-100">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Email Verified</th>
                                    <th>Subscription Plan</th>
                                    <th>Language</th>
                                    <th>Timezone</th>
                                    <th>Two Factor</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTables will populate this -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Email Verified</th>
                                    <th>Subscription Plan</th>
                                    <th>Language</th>
                                    <th>Timezone</th>
                                    <th>Two Factor</th>
                                    <th>Created At</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <hr class="my-4">

                    <div class="alert alert-info">
                        <h6 class="mb-2"><i class="ri-information-line me-1"></i>Report Information</h6>
                        <p class="mb-0">
                            Export user data with filters. The report will include:
                        </p>
                        <ul class="mb-0 mt-2">
                            <li>User Name and Email</li>
                            <li>Phone Number</li>
                            <li>Role (Admin/Client)</li>
                            <li>Status (Active/Inactive/Deleted)</li>
                            <li>Email Verification Status</li>
                            <li>Subscription Plan</li>
                            <li>Language and Timezone Preferences</li>
                            <li>Two Factor Authentication Status</li>
                            <li>Email Verified At Date</li>
                            <li>Created At Date</li>
                            <li>Last Login Date</li>
                        </ul>
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

    <script>
        $(document).ready(function() {
            var table = $('#users-report-datatable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: '{{ route("panel.reports.users.data") }}',
                    type: 'GET',
                    data: function(d) {
                        d.filter_role = $('#role').val();
                        d.filter_status = $('#status').val();
                        d.filter_email_verified = $('#email_verified').val();
                        d.filter_subscription_plan = $('#subscription_plan').val();
                        d.filter_language = $('#language').val();
                        d.filter_timezone = $('#timezone').val();
                        d.filter_two_factor = $('#two_factor').val();
                        d.filter_start_date = $('#start_date').val();
                        d.filter_end_date = $('#end_date').val();
                    },
                    dataSrc: function(json) {
                        // Update summary cards with data from server
                        if (json.summary) {
                            $('#total-users').text(json.summary.total_users);
                            $('#active-users').text(json.summary.active_users);
                            $('#inactive-users').text(json.summary.inactive_users);
                            $('#deleted-users').text(json.summary.deleted_users);
                            $('#verified-users').text(json.summary.verified_users);
                            $('#unverified-users').text(json.summary.unverified_users);
                            $('#admin-users').text(json.summary.admin_users);
                            $('#client-users').text(json.summary.client_users);
                            $('#two-factor-enabled').text(json.summary.two_factor_enabled);
                            $('#users-with-subscription').text(json.summary.users_with_subscription);
                            
                            // Update users by role
                            if (json.summary.users_by_role && Object.keys(json.summary.users_by_role).length > 0) {
                                let roleHtml = '';
                                for (const [role, count] of Object.entries(json.summary.users_by_role)) {
                                    const roleName = role === 'admin' ? 'Admin' : 'Client';
                                    roleHtml += '<div class="mb-1"><strong>' + roleName + ':</strong> ' + count + '</div>';
                                }
                                $('#users-by-role').html(roleHtml);
                            } else {
                                $('#users-by-role').html('<span class="text-muted">No data available</span>');
                            }
                        }
                        return json.data;
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables error:', error);
                    }
                },
                order: [[11, 'desc']], // Order by Created At column (index 11)
                pageLength: 25,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                columnDefs: [
                    {
                        targets: [0], // Row number column
                        orderable: false,
                        searchable: false
                    },
                    {
                        targets: [4, 5, 6, 7, 8, 9, 10], // Role, Status, Email Verified, Subscription Plan, Language, Timezone, Two Factor columns
                        orderable: false,
                        searchable: false
                    }
                ],
                columns: [
                    { data: 'row_number', name: 'row_number', orderable: false, searchable: false },
                    { data: 'name', name: 'name' },
                    { data: 'email', name: 'email' },
                    { data: 'phone', name: 'phone' },
                    { data: 'role', name: 'role', orderable: false, searchable: false },
                    { data: 'status', name: 'status', orderable: false, searchable: false },
                    { data: 'email_verified', name: 'email_verified', orderable: false, searchable: false },
                    { data: 'subscription_plan', name: 'subscription_plan', orderable: false, searchable: false },
                    { data: 'language', name: 'language', orderable: false, searchable: false },
                    { data: 'timezone', name: 'timezone', orderable: false, searchable: false },
                    { data: 'two_factor', name: 'two_factor', orderable: false, searchable: false },
                    { data: 'created_at', name: 'created_at' }
                ],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                    searchPlaceholder: 'Search globally...',
                    sSearch: '',
                }
            });

            // Apply filters on change
            $('#role, #status, #email_verified, #subscription_plan, #language, #timezone, #two_factor, #start_date, #end_date').on('change', function() {
                table.ajax.reload();
            });

            // Update export form with current filter values
            $('#export-form').on('submit', function() {
                // Remove existing hidden inputs
                $('#export-form input[type="hidden"]').remove();
                
                // Add filter values as hidden inputs
                if ($('#role').val()) {
                    $(this).append('<input type="hidden" name="role" value="' + $('#role').val() + '">');
                }
                if ($('#status').val()) {
                    $(this).append('<input type="hidden" name="status" value="' + $('#status').val() + '">');
                }
                if ($('#email_verified').val()) {
                    $(this).append('<input type="hidden" name="email_verified" value="' + $('#email_verified').val() + '">');
                }
                if ($('#subscription_plan').val()) {
                    $(this).append('<input type="hidden" name="subscription_plan" value="' + $('#subscription_plan').val() + '">');
                }
                if ($('#language').val()) {
                    $(this).append('<input type="hidden" name="language" value="' + $('#language').val() + '">');
                }
                if ($('#timezone').val()) {
                    $(this).append('<input type="hidden" name="timezone" value="' + $('#timezone').val() + '">');
                }
                if ($('#two_factor').val()) {
                    $(this).append('<input type="hidden" name="two_factor" value="' + $('#two_factor').val() + '">');
                }
                if ($('#start_date').val()) {
                    $(this).append('<input type="hidden" name="start_date" value="' + $('#start_date').val() + '">');
                }
                if ($('#end_date').val()) {
                    $(this).append('<input type="hidden" name="end_date" value="' + $('#end_date').val() + '">');
                }
            });
        });
    </script>
@endsection


