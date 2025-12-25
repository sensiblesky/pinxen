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
            <h1 class="page-title fw-medium fs-18 mb-0">My Subscription</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('subscriptions.index') }}">Pricing</a></li>
                <li class="breadcrumb-item active" aria-current="page">My Subscription</li>
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

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-12">
            <!-- Current Active Subscription -->
            @if($activeSubscription)
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-vip-crown-line me-2"></i>Current Active Subscription
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="fw-semibold mb-3">{{ $activeSubscription->subscriptionPlan->name ?? 'N/A' }}</h4>
                                
                                <div class="row gy-3 mb-4">
                                    <div class="col-md-6">
                                        <span class="text-muted d-block">Billing Period</span>
                                        <span class="fw-semibold text-capitalize">{{ $activeSubscription->billing_period }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted d-block">Price</span>
                                        <span class="fw-semibold text-primary">${{ number_format($activeSubscription->price, 2) }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted d-block">Start Date</span>
                                        <span class="fw-semibold">{{ $activeSubscription->starts_at ? $activeSubscription->starts_at->format('M d, Y') : 'N/A' }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted d-block">End Date</span>
                                        <span class="fw-semibold">{{ $activeSubscription->ends_at ? $activeSubscription->ends_at->format('M d, Y') : 'N/A' }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted d-block">Status</span>
                                        <span class="badge bg-success fs-12">{{ ucfirst($activeSubscription->status) }}</span>
                                    </div>
                                    @if($activeSubscription->payment)
                                        <div class="col-md-6">
                                            <span class="text-muted d-block">Payment Gateway</span>
                                            <span class="badge bg-info">{{ ucfirst($activeSubscription->payment->payment_gateway) }}</span>
                                        </div>
                                    @endif
                                </div>

                                @if($activeSubscription->subscriptionPlan && $activeSubscription->subscriptionPlan->features->count() > 0)
                                    <div class="mb-3">
                                        <h6 class="fw-semibold mb-2">Plan Features</h6>
                                        <ul class="list-unstyled">
                                            @foreach($activeSubscription->subscriptionPlan->features as $feature)
                                                <li class="mb-2">
                                                    <i class="ri-check-line text-success me-2"></i>
                                                    @if($feature->pivot->value)
                                                        <span class="fw-medium">{{ $feature->pivot->value }}</span>
                                                    @elseif($feature->pivot->limit)
                                                        <span class="fw-medium">{{ $feature->pivot->limit }}</span> {{ $feature->name }}
                                                    @else
                                                        {{ $feature->name }}
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="card border-primary mb-3">
                                    <div class="card-body">
                                        <div class="avatar avatar-xl avatar-rounded bg-primary-transparent mx-auto mb-3">
                                            <i class="ri-calendar-line fs-32 text-primary"></i>
                                        </div>
                                        <h6 class="text-muted mb-2">Days Remaining</h6>
                                        @if($activeSubscription->ends_at && $activeSubscription->ends_at->isFuture())
                                            <h2 class="fw-semibold text-primary mb-0">
                                                {{ now()->diffInDays($activeSubscription->ends_at) }}
                                            </h2>
                                            <small class="text-muted">days</small>
                                        @else
                                            <span class="text-danger">Expired</span>
                                        @endif
                                    </div>
                                </div>
                                
                                @if($activeSubscription->ends_at && $activeSubscription->ends_at->isFuture())
                                    <a href="{{ route('subscriptions.index') }}" class="btn btn-primary btn-wave">
                                        <i class="ri-refresh-line me-1"></i>Renew Subscription
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="card custom-card mb-4">
                    <div class="card-body text-center p-5">
                        <div class="avatar avatar-xl avatar-rounded bg-light mx-auto mb-3">
                            <i class="ri-subscription-line fs-48 text-muted"></i>
                        </div>
                        <h4 class="fw-semibold mb-2">No Active Subscription</h4>
                        <p class="text-muted mb-4">You don't have an active subscription. Choose a plan to get started.</p>
                        <a href="{{ route('subscriptions.index') }}" class="btn btn-primary btn-lg">
                            <i class="ri-shopping-cart-line me-1"></i>View Plans
                        </a>
                    </div>
                </div>
            @endif

            <!-- Subscription History -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="ri-history-line me-2"></i>Subscription History
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="subscription-history-datatable" class="table table-bordered text-nowrap w-100">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Plan</th>
                                    <th>Billing Period</th>
                                    <th>Price</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTables will populate this via server-side processing -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>#</th>
                                    <th>Plan</th>
                                    <th>Billing Period</th>
                                    <th>Price</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Created At</th>
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

    <script>
        $(document).ready(function() {
            // Initialize DataTable for subscription history with server-side processing
            var table = $('#subscription-history-datatable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: '{{ route("subscriptions.history.data") }}',
                    type: 'GET',
                    error: function(xhr, error, thrown) {
                        console.error('DataTables error:', error);
                        if (xhr.status === 500) {
                            alert('An error occurred while loading subscription history. Please refresh the page.');
                        }
                    }
                },
                order: [[8, 'desc']], // Order by Created At column (index 8)
                pageLength: 10, // Load only 10 items per page
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                columnDefs: [
                    {
                        targets: [0, 7], // Row number and Payment columns
                        orderable: false,
                        searchable: false
                    }
                ],
                columns: [
                    { data: 'row_number', name: 'row_number', orderable: false, searchable: false },
                    { data: 'plan', name: 'subscriptionPlan.name' },
                    { data: 'billing_period', name: 'billing_period' },
                    { data: 'price', name: 'price' },
                    { data: 'starts_at', name: 'starts_at' },
                    { data: 'ends_at', name: 'ends_at' },
                    { data: 'status', name: 'status' },
                    { data: 'payment', name: 'payment', orderable: false, searchable: false },
                    { data: 'created_at', name: 'created_at' },
                ],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                    searchPlaceholder: 'Search...',
                    sSearch: '',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'No entries to show',
                    infoFiltered: '(filtered from _MAX_ total entries)',
                }
            });
        });
    </script>
@endsection

