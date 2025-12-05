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
            <h1 class="page-title fw-medium fs-18 mb-0">User Profile Settings</h1>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('panel.users.index') }}" class="btn btn-sm btn-light btn-wave">
                    <i class="ri-arrow-left-line me-1"></i>Go Back to Users
                </a>
            </div>
        </div>
        <ol class="breadcrumb mb-0 mt-2">
            <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
            <li class="breadcrumb-item"><a href="{{ route('panel.users.index') }}">Users</a></li>
            <li class="breadcrumb-item active" aria-current="page">Profile Settings</li>
        </ol>
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
                    <div class="card-title">User Profile Settings</div>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3 border-0" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" role="tab" href="#account-information-tab" aria-selected="true">
                                <i class="ri-user-line me-1"></i>Account Information
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" role="tab" href="#subscription-management-tab" aria-selected="false">
                                <i class="ri-vip-crown-line me-1"></i>Subscription Management
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <!-- Account Information Tab -->
                        <div class="tab-pane fade show active" id="account-information-tab" role="tabpanel">
                            <form action="{{ route('panel.users.update', $user) }}" method="POST" id="user-update-form" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                
                                <div class="row gy-3">
                                    <div class="col-xl-12">
                                        <div class="d-flex align-items-start flex-wrap gap-3">
                                            <div>
                                                <span class="avatar avatar-xxl" id="avatar-preview">
                                                    <img src="{{ $user->secure_avatar_url }}" alt="Profile Picture" id="avatar-img">
                                                </span>
                                            </div>
                                            <div>
                                                <span class="fw-medium d-block mb-2">Profile Picture</span>
                                                <div class="btn-list mb-1">
                                                    <label for="avatar-upload" class="btn btn-sm btn-primary btn-wave" style="cursor: pointer;">
                                                        <i class="ri-upload-2-line me-1"></i>Change Image
                                                    </label>
                                                    <input type="file" id="avatar-upload" name="avatar" accept="image/jpeg,image/png,image/jpg,image/gif" style="display: none;" onchange="previewAvatar(this)">
                                                    <button type="button" class="btn btn-sm btn-light btn-wave" onclick="removeAvatar()">
                                                        <i class="ri-delete-bin-line me-1"></i>Remove
                                                    </button>
                                                </div>
                                                <span class="d-block fs-12 text-muted">Use JPEG, PNG, or GIF. Best size: 200x200 pixels. Keep it under 5MB</span>
                                                <input type="hidden" name="remove_avatar" id="remove-avatar-flag" value="0">
                                                @error('avatar')
                                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="profile-user-name" class="form-label">User Name :</label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="profile-user-name" name="name" value="{{ old('name', $user->name) }}" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="profile-email" class="form-label">Email :</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="profile-email" name="email" value="{{ old('email', $user->email) }}" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="profile-phn-no" class="form-label">Phone No :</label>
                                        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="profile-phn-no" name="phone" value="{{ old('phone', $user->phone) }}">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="profile-role" class="form-label">Role :</label>
                                        <select class="form-select @error('role') is-invalid @enderror" id="profile-role" name="role" required>
                                            <option value="1" {{ old('role', $user->role) == 1 ? 'selected' : '' }}>Admin</option>
                                            <option value="2" {{ old('role', $user->role) == 2 ? 'selected' : '' }}>User</option>
                                        </select>
                                        @error('role')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="profile-language" class="form-label">Language :</label>
                                        <select class="form-control @error('language_id') is-invalid @enderror" id="profile-language" name="language_id" data-trigger>
                                            <option value="">Select Language</option>
                                            @foreach($languages as $language)
                                                <option value="{{ $language->id }}" {{ old('language_id', $user->language_id) == $language->id ? 'selected' : '' }}>{{ $language->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('language_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="profile-timezone" class="form-label">Timezone :</label>
                                        <select class="form-control @error('timezone_id') is-invalid @enderror" id="profile-timezone" name="timezone_id" data-trigger>
                                            <option value="">Select Timezone</option>
                                            @foreach($timezones as $timezone)
                                                <option value="{{ $timezone->id }}" {{ old('timezone_id', $user->timezone_id) == $timezone->id ? 'selected' : '' }}>{{ $timezone->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('timezone_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <hr class="my-4">

                                <!-- Account Status Section -->
                                <h6 class="mb-3"><i class="ri-shield-check-line me-1"></i>Account Status</h6>
                                <div class="alert alert-{{ $user->is_active ? 'success' : 'danger' }} mb-4">
                                    <div class="d-sm-flex d-block align-items-top justify-content-between">
                                        <div class="w-50">
                                            <p class="fs-12 mb-0">
                                                @if($user->is_active)
                                                    This account is currently <strong class="text-success">Active</strong>. Deactivating will prevent the user from logging in.
                                                @else
                                                    This account is currently <strong class="text-danger">Deactivated</strong>. Activating will allow the user to log in again.
                                                @endif
                                            </p>
                                        </div>
                                        <div>
                                            @if($user->id === auth()->id())
                                                <button type="button" class="btn btn-light btn-wave" disabled title="You cannot deactivate your own account">
                                                    <i class="ri-error-warning-line me-1"></i>Cannot Deactivate Own Account
                                                </button>
                                            @else
                                                <button type="button" class="btn {{ $user->is_active ? 'btn-danger' : 'btn-success' }} btn-wave" id="toggle-account-status-btn" data-is-active="{{ $user->is_active ? '1' : '0' }}" data-user-name="{{ addslashes($user->name) }}">
                                                    <i class="ri-{{ $user->is_active ? 'close-circle' : 'check-circle' }}-line me-1"></i>
                                                    {{ $user->is_active ? 'Deactivate Account' : 'Activate Account' }}
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary btn-wave" form="user-update-form">
                                        <i class="ri-save-line me-1"></i>Save Changes
                                    </button>
                                    <a href="{{ route('panel.users.index') }}" class="btn btn-secondary btn-wave">
                                        <i class="ri-arrow-left-line me-1"></i>Go Back
                                    </a>
                                </div>
                            </form>
                            
                            <!-- Toggle Status Form - Moved outside main form to avoid nested form issue -->
                            @if($user->id !== auth()->id())
                                <form action="{{ route('panel.users.toggle-status', $user) }}" method="POST" class="d-none" id="toggle-status-form">
                                    @csrf
                                </form>
                            @endif
                        </div>

                        <!-- Subscription Management Tab -->
                        <div class="tab-pane fade" id="subscription-management-tab" role="tabpanel">
                            <!-- Nested Tabs for Subscription Management -->
                            <ul class="nav nav-tabs mb-3 border-0" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active" data-bs-toggle="tab" role="tab" href="#current-subscription" type="button">
                                        <i class="ri-checkbox-circle-line me-1"></i>Current Subscription
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" role="tab" href="#subscription-history" type="button">
                                        <i class="ri-history-line me-1"></i>History
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" role="tab" href="#assign-subscription" type="button">
                                        <i class="ri-add-circle-line me-1"></i>Assign New
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content">
                                <!-- Current Subscription Tab -->
                                <div class="tab-pane fade show active" id="current-subscription" role="tabpanel">
                                    @if($user->activeSubscription)
                                        <div class="alert alert-info mb-0">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <h6 class="mb-0"><i class="ri-checkbox-circle-line me-1"></i>Active Subscription</h6>
                                                <div class="btn-list">
                                                    <form action="{{ route('panel.users.subscriptions.update-status', [$user, $user->activeSubscription]) }}" method="POST" class="d-inline cancel-subscription-form" data-subscription-id="{{ $user->activeSubscription->id }}" data-plan-name="{{ $user->activeSubscription->subscriptionPlan->name }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="cancelled">
                                                        <button type="button" class="btn btn-sm btn-danger btn-wave cancel-subscription-btn" data-bs-toggle="tooltip" title="Cancel Subscription">
                                                            <i class="ri-close-circle-line me-1"></i>Cancel
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('panel.users.subscriptions.update-status', [$user, $user->activeSubscription]) }}" method="POST" class="d-inline expire-subscription-form" data-subscription-id="{{ $user->activeSubscription->id }}" data-plan-name="{{ $user->activeSubscription->subscriptionPlan->name }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="expired">
                                                        <button type="button" class="btn btn-sm btn-warning btn-wave expire-subscription-btn" data-bs-toggle="tooltip" title="Mark as Expired">
                                                            <i class="ri-time-line me-1"></i>Mark as Expired
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <strong>Plan:</strong> {{ $user->activeSubscription->subscriptionPlan->name }}<br>
                                                    <strong>Billing Period:</strong> {{ ucfirst($user->activeSubscription->billing_period) }}<br>
                                                    <strong>Price:</strong> ${{ number_format($user->activeSubscription->price, 2) }}
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Start Date:</strong> {{ $user->activeSubscription->starts_at->format('Y-m-d H:i') }}<br>
                                                    <strong>End Date:</strong> {{ $user->activeSubscription->ends_at->format('Y-m-d H:i') }}<br>
                                                    <strong>Status:</strong> 
                                                    <span class="badge bg-{{ $user->activeSubscription->status === 'active' ? 'success' : 'warning' }}">
                                                        {{ ucfirst($user->activeSubscription->status) }}
                                                    </span>
                                                    @if($user->activeSubscription->assignedBy)
                                                        <br><strong>Assigned By:</strong> {{ $user->activeSubscription->assignedBy->name }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="alert alert-warning mb-0">
                                            <i class="ri-information-line me-1"></i>No active subscription found.
                                        </div>
                                    @endif
                                </div>

                                <!-- Subscription History Tab -->
                                <div class="tab-pane fade" id="subscription-history" role="tabpanel">
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
                                                    <th>Assigned By</th>
                                                    <th>Created At</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($user->subscriptions->sortByDesc('created_at') as $index => $subscription)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>{{ $subscription->subscriptionPlan->name }}</td>
                                                        <td>{{ ucfirst($subscription->billing_period) }}</td>
                                                        <td>${{ number_format($subscription->price, 2) }}</td>
                                                        <td>{{ $subscription->starts_at ? $subscription->starts_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                                        <td>{{ $subscription->ends_at ? $subscription->ends_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                                        <td>
                                                            <span class="badge bg-{{ $subscription->status === 'active' ? 'success' : ($subscription->status === 'expired' ? 'danger' : ($subscription->status === 'cancelled' ? 'secondary' : 'warning')) }}">
                                                                {{ ucfirst($subscription->status) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            @if($subscription->assignedBy)
                                                                {{ $subscription->assignedBy->name }}
                                                            @else
                                                                <span class="text-muted">System</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $subscription->created_at->format('Y-m-d H:i') }}</td>
                                                        <td>
                                                            <div class="btn-list">
                                                                @if($subscription->payment_id && $subscription->payment)
                                                                    <button type="button" class="btn btn-sm btn-info btn-wave view-payment-details-btn" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#paymentDetailsModal"
                                                                            data-payment-id="{{ $subscription->payment->id }}"
                                                                            data-payment-gateway="{{ $subscription->payment->payment_gateway }}"
                                                                            data-payment-amount="{{ number_format($subscription->payment->amount, 2) }}"
                                                                            data-payment-currency="{{ $subscription->payment->currency }}"
                                                                            data-payment-status="{{ $subscription->payment->status }}"
                                                                            data-payment-transaction-id="{{ $subscription->payment->gateway_transaction_id }}"
                                                                            data-payment-paid-at="{{ $subscription->payment->paid_at ? $subscription->payment->paid_at->format('Y-m-d H:i:s') : 'N/A' }}"
                                                                            data-payment-refunded-at="{{ $subscription->payment->refunded_at ? $subscription->payment->refunded_at->format('Y-m-d H:i:s') : 'N/A' }}"
                                                                            data-payment-gateway-response="{{ htmlspecialchars(json_encode($subscription->payment->gateway_response, JSON_PRETTY_PRINT)) }}"
                                                                            data-payment-metadata="{{ htmlspecialchars(json_encode($subscription->payment->metadata, JSON_PRETTY_PRINT)) }}"
                                                                            data-bs-toggle="tooltip" title="View Payment Details">
                                                                        <i class="ri-money-dollar-circle-line"></i>
                                                                    </button>
                                                                @endif
                                                                @if($subscription->status === 'active')
                                                                    <form action="{{ route('panel.users.subscriptions.update-status', [$user, $subscription]) }}" method="POST" class="d-inline cancel-subscription-form" data-subscription-id="{{ $subscription->id }}" data-plan-name="{{ $subscription->subscriptionPlan->name }}">
                                                                        @csrf
                                                                        <input type="hidden" name="status" value="cancelled">
                                                                        <button type="button" class="btn btn-sm btn-danger btn-wave cancel-subscription-btn" data-bs-toggle="tooltip" title="Cancel Subscription">
                                                                            <i class="ri-close-circle-line"></i>
                                                                        </button>
                                                                    </form>
                                                                    <form action="{{ route('panel.users.subscriptions.update-status', [$user, $subscription]) }}" method="POST" class="d-inline expire-subscription-form" data-subscription-id="{{ $subscription->id }}" data-plan-name="{{ $subscription->subscriptionPlan->name }}">
                                                                        @csrf
                                                                        <input type="hidden" name="status" value="expired">
                                                                        <button type="button" class="btn btn-sm btn-warning btn-wave expire-subscription-btn" data-bs-toggle="tooltip" title="Mark as Expired">
                                                                            <i class="ri-time-line"></i>
                                                                        </button>
                                                                    </form>
                                                                @else
                                                                    @if(!$subscription->payment_id)
                                                                        <span class="text-muted">-</span>
                                                                    @endif
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
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
                                                    <th>Assigned By</th>
                                                    <th>Created At</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>

                                <!-- Assign New Subscription Tab -->
                                <div class="tab-pane fade" id="assign-subscription" role="tabpanel">
                                    <form action="{{ route('panel.users.assign-subscription', $user) }}" method="POST" id="assign-subscription-form">
                                        @csrf
                                        <div class="row gy-3">
                                            <div class="col-md-6">
                                                <label for="subscription_plan_id" class="form-label">Subscription Plan <span class="text-danger">*</span></label>
                                                <select class="form-control @error('subscription_plan_id') is-invalid @enderror" 
                                                        id="subscription_plan_id" name="subscription_plan_id" required>
                                                    <option value="">Select a plan</option>
                                                    @foreach($subscriptionPlans as $plan)
                                                        @php
                                                            $isAlreadyAssigned = in_array($plan->id, $activePlanIds ?? []);
                                                        @endphp
                                                        <option value="{{ $plan->id }}" 
                                                                data-monthly="{{ $plan->price_monthly }}" 
                                                                data-yearly="{{ $plan->price_yearly }}"
                                                                {{ old('subscription_plan_id') == $plan->id ? 'selected' : '' }}
                                                                {{ $isAlreadyAssigned ? 'disabled' : '' }}>
                                                            {{ $plan->name }} - Monthly: ${{ number_format($plan->price_monthly, 2) }} / Yearly: ${{ number_format($plan->price_yearly, 2) }}
                                                            @if($isAlreadyAssigned)
                                                                (Already Assigned)
                                                            @endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('subscription_plan_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                @if(isset($activePlanIds) && count($activePlanIds) > 0)
                                                    <small class="text-warning d-block mt-1">
                                                        <i class="ri-alert-line me-1"></i>Plans marked as "Already Assigned" cannot be selected. Cancel or expire the existing subscription first.
                                                    </small>
                                                @endif
                                            </div>

                                            <div class="col-md-6">
                                                <label for="billing_period" class="form-label">Billing Period <span class="text-danger">*</span></label>
                                                <select class="form-control @error('billing_period') is-invalid @enderror" 
                                                        id="billing_period" name="billing_period" required>
                                                    <option value="monthly" {{ old('billing_period', 'monthly') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                                    <option value="yearly" {{ old('billing_period') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                                </select>
                                                @error('billing_period')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="text-muted">Subscription will start from now and end after the selected period</small>
                                            </div>

                                            <div class="col-md-12">
                                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                                <select class="form-control @error('status') is-invalid @enderror" 
                                                        id="status" name="status" required>
                                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                                    <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                                    <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                                </select>
                                                @error('status')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-12">
                                                <button type="submit" class="btn btn-primary btn-wave">
                                                    <i class="ri-check-line me-1"></i>Assign Subscription
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--End::row-1 -->

@endsection

@section('scripts')
	
        <!-- Choices JS -->
        <script src="{{asset('build/assets/libs/choices.js/public/assets/scripts/choices.min.js')}}"></script>
        
        <!-- Sweetalerts JS -->
        <script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>
        
        <script>
            // Initialize Choices.js for language and timezone selects
            document.addEventListener('DOMContentLoaded', function() {
                const languageSelect = document.querySelector('#profile-language');
                const timezoneSelect = document.querySelector('#profile-timezone');
                
                if (languageSelect) {
                    new Choices(languageSelect, {
                        searchEnabled: true,
                        placeholder: true,
                        placeholderValue: 'Select Language',
                    });
                }
                
                if (timezoneSelect) {
                    new Choices(timezoneSelect, {
                        searchEnabled: true,
                        placeholder: true,
                        placeholderValue: 'Select Timezone',
                    });
                }
            });

            // Image preview functionality
            function previewAvatar(input) {
                if (input.files && input.files[0]) {
                    const file = input.files[0];
                    
                    // Validate file size (5MB max)
                    if (file.size > 5 * 1024 * 1024) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'File Too Large',
                                text: 'File size must be less than 5MB'
                            });
                        } else {
                            alert('File size must be less than 5MB');
                        }
                        input.value = '';
                        return;
                    }
                    
                    // Validate file type
                    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                    if (!validTypes.includes(file.type)) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Invalid File Type',
                                text: 'Please select a valid image file (JPEG, PNG, or GIF)'
                            });
                        } else {
                            alert('Please select a valid image file (JPEG, PNG, or GIF)');
                        }
                        input.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('avatar-img').src = e.target.result;
                        document.getElementById('remove-avatar-flag').value = '0';
                    };
                    reader.readAsDataURL(file);
                }
            }

            function removeAvatar() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Remove Profile Picture?',
                        text: 'Are you sure you want to remove the profile picture?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, remove it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('avatar-img').src = '{{ asset("build/assets/images/faces/9.jpg") }}';
                            document.getElementById('avatar-upload').value = '';
                            document.getElementById('remove-avatar-flag').value = '1';
                        }
                    });
                } else {
                    if (confirm('Are you sure you want to remove the profile picture?')) {
                        document.getElementById('avatar-img').src = '{{ asset("build/assets/images/faces/9.jpg") }}';
                        document.getElementById('avatar-upload').value = '';
                        document.getElementById('remove-avatar-flag').value = '1';
                    }
                }
            }
            
            // Account status toggle button event listener
            document.addEventListener('DOMContentLoaded', function() {
                // Use event delegation to handle clicks on toggle button
                document.addEventListener('click', function(e) {
                    const toggleBtn = e.target.closest('#toggle-account-status-btn');
                    if (!toggleBtn) return;
                    
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Find the form
                    const form = document.getElementById('toggle-status-form');
                    
                    if (!form) {
                        console.error('Toggle status form not found');
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Unable to find the form. Please refresh the page and try again.',
                                confirmButtonColor: '#3085d6',
                            });
                        } else {
                            alert('Form not found. Please refresh the page.');
                        }
                        return;
                    }
                    
                    // Get data from button instead of form
                    const isActive = toggleBtn.dataset.isActive === '1';
                    const userName = toggleBtn.dataset.userName || 'this user';
                    
                    submitToggleForm(form, isActive, userName);
                });
            });
            
            // Helper function to submit the form with confirmation
            function submitToggleForm(form, isActive, userName) {
                // Check if SweetAlert2 is available
                if (typeof Swal !== 'undefined') {
                    if (isActive) {
                        // Deactivate confirmation
                        Swal.fire({
                            title: 'Are you sure?',
                            html: `You are about to <strong>deactivate</strong> the account for <strong>${userName}</strong>.<br><br>This will prevent the user from logging in.`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: '<i class="ri-close-circle-line me-1"></i>Yes, deactivate it!',
                            cancelButtonText: '<i class="ri-close-line me-1"></i>Cancel',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    } else {
                        // Activate confirmation
                        Swal.fire({
                            title: 'Activate Account?',
                            html: `You are about to <strong>activate</strong> the account for <strong>${userName}</strong>.<br><br>This will allow the user to log in again.`,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: '<i class="ri-check-circle-line me-1"></i>Yes, activate it!',
                            cancelButtonText: '<i class="ri-close-line me-1"></i>Cancel',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    }
                } else {
                    // Fallback to native confirm
                    const action = isActive ? 'deactivate' : 'activate';
                    if (confirm(`Are you sure you want to ${action} the account for ${userName}?`)) {
                        form.submit();
                    }
                }
            }
        </script>

        <!-- Payment Details Modal -->
        <div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-labelledby="paymentDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentDetailsModalLabel">
                            <i class="ri-money-dollar-circle-line me-2"></i>Payment Details
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row gy-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted">Payment Gateway</label>
                                <div class="mb-3">
                                    <span class="badge bg-info fs-12" id="modal-payment-gateway">-</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted">Amount</label>
                                <div class="mb-3">
                                    <span class="fw-semibold" id="modal-payment-amount">-</span>
                                    <span class="text-muted ms-1" id="modal-payment-currency">-</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted">Status</label>
                                <div class="mb-3">
                                    <span class="badge" id="modal-payment-status">-</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted">Transaction ID</label>
                                <div class="mb-3">
                                    <code class="font-monospace" id="modal-payment-transaction-id">-</code>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted">Paid At</label>
                                <div class="mb-3">
                                    <span id="modal-payment-paid-at">-</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted">Refunded At</label>
                                <div class="mb-3">
                                    <span id="modal-payment-refunded-at">-</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold text-muted">Gateway Response</label>
                                <div class="mb-3">
                                    <pre class="bg-light p-3 rounded border" style="max-height: 200px; overflow-y: auto;" id="modal-payment-gateway-response"><code class="text-muted">No data available</code></pre>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold text-muted">Metadata</label>
                                <div class="mb-3">
                                    <pre class="bg-light p-3 rounded border" style="max-height: 200px; overflow-y: auto;" id="modal-payment-metadata"><code class="text-muted">No data available</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-wave" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i>Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Payment Details Modal Handler
            document.addEventListener('DOMContentLoaded', function() {
                const paymentModal = document.getElementById('paymentDetailsModal');
                if (paymentModal) {
                    paymentModal.addEventListener('show.bs.modal', function(event) {
                        const button = event.relatedTarget; // Button that triggered the modal
                        
                        // Extract data from data attributes
                        const paymentGateway = button.getAttribute('data-payment-gateway') || '-';
                        const paymentAmount = button.getAttribute('data-payment-amount') || '-';
                        const paymentCurrency = button.getAttribute('data-payment-currency') || 'USD';
                        const paymentStatus = button.getAttribute('data-payment-status') || '-';
                        const paymentTransactionId = button.getAttribute('data-payment-transaction-id') || '-';
                        const paymentPaidAt = button.getAttribute('data-payment-paid-at') || '-';
                        const paymentRefundedAt = button.getAttribute('data-payment-refunded-at') || '-';
                        const paymentGatewayResponse = button.getAttribute('data-payment-gateway-response') || 'null';
                        const paymentMetadata = button.getAttribute('data-payment-metadata') || 'null';
                        
                        // Update modal content
                        document.getElementById('modal-payment-gateway').textContent = paymentGateway.charAt(0).toUpperCase() + paymentGateway.slice(1).replace('_', ' ');
                        document.getElementById('modal-payment-amount').textContent = '$' + paymentAmount;
                        document.getElementById('modal-payment-currency').textContent = paymentCurrency;
                        
                        // Status badge
                        const statusBadge = document.getElementById('modal-payment-status');
                        statusBadge.textContent = paymentStatus.charAt(0).toUpperCase() + paymentStatus.slice(1);
                        statusBadge.className = 'badge bg-' + (
                            paymentStatus === 'completed' ? 'success' :
                            paymentStatus === 'pending' ? 'warning text-dark' :
                            paymentStatus === 'failed' ? 'danger' :
                            paymentStatus === 'refunded' ? 'info' :
                            'secondary'
                        );
                        
                        document.getElementById('modal-payment-transaction-id').textContent = paymentTransactionId !== '-' ? paymentTransactionId : 'N/A';
                        document.getElementById('modal-payment-paid-at').textContent = paymentPaidAt !== '-' ? paymentPaidAt : 'N/A';
                        document.getElementById('modal-payment-refunded-at').textContent = paymentRefundedAt !== '-' ? paymentRefundedAt : 'N/A';
                        
                        // Gateway Response
                        const gatewayResponseEl = document.getElementById('modal-payment-gateway-response');
                        try {
                            if (paymentGatewayResponse && paymentGatewayResponse !== 'null' && paymentGatewayResponse !== '-') {
                                const parsed = JSON.parse(paymentGatewayResponse);
                                gatewayResponseEl.innerHTML = '<code>' + JSON.stringify(parsed, null, 2) + '</code>';
                            } else {
                                gatewayResponseEl.innerHTML = '<code class="text-muted">No data available</code>';
                            }
                        } catch (e) {
                            gatewayResponseEl.innerHTML = '<code class="text-muted">Invalid JSON data</code>';
                        }
                        
                        // Metadata
                        const metadataEl = document.getElementById('modal-payment-metadata');
                        try {
                            if (paymentMetadata && paymentMetadata !== 'null' && paymentMetadata !== '-') {
                                const parsed = JSON.parse(paymentMetadata);
                                metadataEl.innerHTML = '<code>' + JSON.stringify(parsed, null, 2) + '</code>';
                            } else {
                                metadataEl.innerHTML = '<code class="text-muted">No data available</code>';
                            }
                        } catch (e) {
                            metadataEl.innerHTML = '<code class="text-muted">Invalid JSON data</code>';
                        }
                    });
                }
            });
        </script>

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
                // Initialize DataTable for subscription history
                var table = $('#subscription-history-datatable').DataTable({
                    responsive: true,
                    order: [[8, 'desc']], // Order by Created At column (index 8)
                    pageLength: 25,
                    dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ],
                    columnDefs: [
                        {
                            targets: [0, 9], // Row number and Actions columns
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

                // Validate assign subscription form to prevent submission with disabled plan
                $('#assign-subscription-form').on('submit', function(e) {
                    const planSelect = $('#subscription_plan_id');
                    const selectedOption = planSelect.find('option:selected');
                    
                    if (selectedOption.prop('disabled')) {
                        e.preventDefault();
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Invalid Selection',
                                text: 'You cannot assign a plan that is already assigned to this user. Please select a different plan or cancel the existing subscription first.',
                                confirmButtonColor: '#3085d6',
                            });
                        } else {
                            alert('You cannot assign a plan that is already assigned to this user. Please select a different plan or cancel the existing subscription first.');
                        }
                        return false;
                    }
                });

                // Cancel subscription confirmation
                $(document).on('click', '.cancel-subscription-btn', function(e) {
                    e.preventDefault();
                    const form = $(this).closest('form');
                    const planName = form.data('plan-name') || 'this subscription';
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Cancel Subscription?',
                            html: `You are about to <strong>cancel</strong> the subscription for <strong>${planName}</strong>.<br><br>This action will mark the subscription as cancelled.`,
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
                    } else {
                        if (confirm(`Are you sure you want to cancel the subscription for ${planName}?`)) {
                            form.submit();
                        }
                    }
                });

                // Expire subscription confirmation
                $(document).on('click', '.expire-subscription-btn', function(e) {
                    e.preventDefault();
                    const form = $(this).closest('form');
                    const planName = form.data('plan-name') || 'this subscription';
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Mark as Expired?',
                            html: `You are about to mark the subscription for <strong>${planName}</strong> as <strong>expired</strong>.<br><br>This action will immediately expire the subscription.`,
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
                    } else {
                        if (confirm(`Are you sure you want to mark the subscription for ${planName} as expired?`)) {
                            form.submit();
                        }
                    }
                });
            });
        </script>

@endsection
