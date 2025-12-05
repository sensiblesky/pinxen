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
            <h1 class="page-title fw-medium fs-18 mb-0">Subscription Reports</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                <li class="breadcrumb-item"><a href="{{ route('panel.reports.subscriptions') }}">Reports</a></li>
                <li class="breadcrumb-item active" aria-current="page">Subscription</li>
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
                    <div class="card-title">Export Subscription Report</div>
                </div>
                <div class="card-body">
                    <form action="{{ route('panel.reports.subscriptions.export') }}" method="GET" id="export-form">
                        <div class="row gy-3">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Filter by Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="payment_status" class="form-label">Filter by Payment Status</label>
                                <select class="form-select" id="payment_status" name="payment_status">
                                    <option value="">All Payments</option>
                                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                    <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="payment_gateway" class="form-label">Filter by Payment Gateway</label>
                                <select class="form-select" id="payment_gateway" name="payment_gateway">
                                    <option value="">All Gateways</option>
                                    @foreach($paymentGateways as $gateway)
                                        <option value="{{ $gateway }}" {{ request('payment_gateway') == $gateway ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $gateway)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="subscription_plan" class="form-label">Filter by Plan</label>
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
                                <label for="billing_period" class="form-label">Filter by Billing Period</label>
                                <select class="form-select" id="billing_period" name="billing_period">
                                    <option value="">All Periods</option>
                                    <option value="monthly" {{ request('billing_period') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="yearly" {{ request('billing_period') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}">
                                <small class="text-muted">Filter subscriptions created from this date</small>
                            </div>

                            <div class="col-md-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
                                <small class="text-muted">Filter subscriptions created until this date</small>
                            </div>

                            <div class="col-md-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-wave" formaction="{{ route('panel.reports.subscriptions.export') }}">
                                        <i class="ri-file-download-line me-1"></i>Export as CSV
                                    </button>
                                    <button type="submit" class="btn btn-success btn-wave" formaction="{{ route('panel.reports.subscriptions.export-excel') }}">
                                        <i class="ri-file-excel-line me-1"></i>Export as Excel
                                    </button>
                                    <a href="{{ route('panel.reports.subscriptions') }}" class="btn btn-secondary btn-wave">
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
                                                <i class="ri-money-dollar-circle-line fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 text-muted small">Total Required</h6>
                                            <h5 class="mb-0" id="total-amount-required">$0.00</h5>
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
                                            <h6 class="mb-1 text-muted small">Total Paid</h6>
                                            <h5 class="mb-0 text-success" id="total-amount-paid">$0.00</h5>
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
                                                <i class="ri-close-circle-line fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 text-muted small">Total Unpaid</h6>
                                            <h5 class="mb-0 text-danger" id="total-amount-unpaid">$0.00</h5>
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
                                                <i class="ri-refund-line fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 text-muted small">Total Refunded</h6>
                                            <h5 class="mb-0 text-warning" id="total-amount-refunded">$0.00</h5>
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
                                                <i class="ri-error-warning-line fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 text-muted small">Total Failed</h6>
                                            <h5 class="mb-0 text-danger" id="total-amount-failed">$0.00</h5>
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
                                                <i class="ri-percent-line fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 text-muted small">Success Rate</h6>
                                            <h5 class="mb-0 text-info" id="payment-success-rate">0%</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards - Row 2 -->
                    <div class="row mb-4">
                        <div class="col-md-3 col-lg-2">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar avatar-md bg-primary text-white rounded">
                                                <i class="ri-calendar-line fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 text-muted small">MRR</h6>
                                            <h5 class="mb-0" id="mrr">$0.00</h5>
                                            <small class="text-muted">Monthly Recurring</small>
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
                                                <i class="ri-calendar-2-line fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 text-muted small">ARR</h6>
                                            <h5 class="mb-0 text-success" id="arr">$0.00</h5>
                                            <small class="text-muted">Annual Recurring</small>
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
                                                <i class="ri-calculator-line fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 text-muted small">Avg Transaction</h6>
                                            <h5 class="mb-0 text-info" id="avg-transaction-value">$0.00</h5>
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
                                                <i class="ri-check-double-line fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 text-muted small">Successful</h6>
                                            <h5 class="mb-0" id="successful-payments">0</h5>
                                            <small class="text-muted">of <span id="total-payments">0</span> payments</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-light">
                                <div class="card-body">
                                    <h6 class="mb-2 text-muted small">Revenue by Gateway</h6>
                                    <div id="revenue-by-gateway" class="small">
                                        <span class="text-muted">No data available</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DataTable -->
                    <div class="table-responsive">
                        <table id="subscriptions-report-datatable" class="table table-bordered text-nowrap w-100">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Plan</th>
                                    <th>Billing Period</th>
                                    <th>Price</th>
                                    <th>Payment Status</th>
                                    <th>Payment Gateway</th>
                                    <th>Transaction ID</th>
                                    <th>Payment Date</th>
                                    <th>Status</th>
                                    <th>Created At</th>
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
                                    <th>Payment Status</th>
                                    <th>Payment Gateway</th>
                                    <th>Transaction ID</th>
                                    <th>Payment Date</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <hr class="my-4">

                    <div class="alert alert-info">
                        <h6 class="mb-2"><i class="ri-information-line me-1"></i>Report Information</h6>
                        <p class="mb-0">
                            Export subscription data with filters. The report will include:
                        </p>
                        <ul class="mb-0 mt-2">
                            <li>User Name and Email</li>
                            <li>Subscription Plan Name</li>
                            <li>Billing Period (Monthly/Yearly)</li>
                            <li>Price</li>
                            <li>Payment Status (Paid/Unpaid)</li>
                            <li>Payment Gateway</li>
                            <li>Transaction ID</li>
                            <li>Start and End Dates</li>
                            <li>Status</li>
                            <li>Assigned By (Admin who assigned the subscription)</li>
                            <li>Created At</li>
                        </ul>
                        <p class="mb-0 mt-2">
                            <strong>Note:</strong> Unpaid subscriptions are highlighted in red in the preview table above.
                        </p>
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
            var table = $('#subscriptions-report-datatable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: '{{ route("panel.reports.subscriptions.data") }}',
                    type: 'GET',
                    data: function(d) {
                        d.filter_status = $('#status').val();
                        d.filter_payment_status = $('#payment_status').val();
                        d.filter_payment_gateway = $('#payment_gateway').val();
                        d.filter_subscription_plan = $('#subscription_plan').val();
                        d.filter_billing_period = $('#billing_period').val();
                        d.filter_start_date = $('#start_date').val();
                        d.filter_end_date = $('#end_date').val();
                    },
                    dataSrc: function(json) {
                        // Update summary cards with data from server
                        if (json.summary) {
                            $('#total-amount-required').text('$' + json.summary.total_amount_required);
                            $('#total-amount-paid').text('$' + json.summary.total_amount_paid);
                            $('#total-amount-unpaid').text('$' + json.summary.total_amount_unpaid);
                            $('#total-amount-refunded').text('$' + json.summary.total_amount_refunded);
                            $('#total-amount-failed').text('$' + json.summary.total_amount_failed);
                            $('#payment-success-rate').text(json.summary.payment_success_rate + '%');
                            $('#mrr').text('$' + json.summary.mrr);
                            $('#arr').text('$' + json.summary.arr);
                            $('#avg-transaction-value').text('$' + json.summary.avg_transaction_value);
                            $('#successful-payments').text(json.summary.successful_payments);
                            $('#total-payments').text(json.summary.total_payments);
                            
                            // Update revenue by gateway
                            if (json.summary.revenue_by_gateway && Object.keys(json.summary.revenue_by_gateway).length > 0) {
                                let gatewayHtml = '';
                                for (const [gateway, amount] of Object.entries(json.summary.revenue_by_gateway)) {
                                    gatewayHtml += '<div class="mb-1"><strong>' + gateway.charAt(0).toUpperCase() + gateway.slice(1).replace('_', ' ') + ':</strong> $' + parseFloat(amount).toFixed(2) + '</div>';
                                }
                                $('#revenue-by-gateway').html(gatewayHtml);
                            } else {
                                $('#revenue-by-gateway').html('<span class="text-muted">No data available</span>');
                            }
                        }
                        return json.data;
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables error:', error);
                    }
                },
                order: [[10, 'desc']], // Order by Created At column (index 10)
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
                        targets: [5, 6, 7, 8], // Payment Status, Payment Gateway, Transaction ID, Payment Date columns
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
                    { data: 'payment_status', name: 'payment_status', orderable: false, searchable: false },
                    { data: 'payment_gateway', name: 'payment_gateway', orderable: false, searchable: false },
                    { data: 'transaction_id', name: 'transaction_id', orderable: false, searchable: false },
                    { data: 'payment_date', name: 'payment_date', orderable: false, searchable: false },
                    { data: 'status', name: 'status' },
                    { data: 'created_at', name: 'created_at' }
                ],
                createdRow: function(row, data, dataIndex) {
                    // Apply red background to unpaid subscriptions
                    if (data.row_class === 'table-danger') {
                        $(row).addClass('table-danger');
                    }
                },
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                    searchPlaceholder: 'Search globally...',
                    sSearch: '',
                }
            });

            // Apply filters on change
            $('#status, #payment_status, #payment_gateway, #subscription_plan, #billing_period, #start_date, #end_date').on('change', function() {
                table.ajax.reload();
            });

            // Update export form with current filter values
            $('#export-form').on('submit', function() {
                // Remove existing hidden inputs
                $('#export-form input[type="hidden"]').remove();
                
                // Add filter values as hidden inputs
                if ($('#status').val()) {
                    $(this).append('<input type="hidden" name="status" value="' + $('#status').val() + '">');
                }
                if ($('#payment_status').val()) {
                    $(this).append('<input type="hidden" name="payment_status" value="' + $('#payment_status').val() + '">');
                }
                if ($('#payment_gateway').val()) {
                    $(this).append('<input type="hidden" name="payment_gateway" value="' + $('#payment_gateway').val() + '">');
                }
                if ($('#subscription_plan').val()) {
                    $(this).append('<input type="hidden" name="subscription_plan" value="' + $('#subscription_plan').val() + '">');
                }
                if ($('#billing_period').val()) {
                    $(this).append('<input type="hidden" name="billing_period" value="' + $('#billing_period').val() + '">');
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

