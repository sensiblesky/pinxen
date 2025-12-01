@extends('layouts.master')

@section('styles')
    <!-- Choices CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/choices.js/public/assets/styles/choices.min.css')}}">
    
    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.css')}}">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css">
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">My Profile</h1>
        </div>
        <ol class="breadcrumb mb-0 mt-2">
            <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
            <li class="breadcrumb-item active" aria-current="page">Profile</li>
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
                    <div class="card-title">
                        Profile Settings
                    </div>
                </div>
                <div class="card-body">
                    <!-- Nav Tabs -->
                    <ul class="nav nav-tabs mb-3 nav-justified nav-style-1 d-sm-flex d-block" role="tablist">
                        <li class="nav-item active" role="presentation">
                            <a class="nav-link active" id="account-tab" data-bs-toggle="tab" data-bs-target="#account" role="tab" aria-controls="account" aria-selected="true" href="#account">
                                <i class="ri-user-settings-line me-1"></i>Account Information
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="sessions-tab" data-bs-toggle="tab" data-bs-target="#sessions" role="tab" aria-controls="sessions" aria-selected="false" href="#sessions">
                                <i class="ri-computer-line me-1"></i>Active Sessions
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="activities-tab" data-bs-toggle="tab" data-bs-target="#activities" role="tab" aria-controls="activities" aria-selected="false" href="#activities">
                                <i class="ri-history-line me-1"></i>Login Activities
                            </a>
                        </li>
                    </ul>
                    
                    <!-- Tab Content -->
                    <div class="tab-content">
                        <!-- Account Information Tab -->
                        <div class="tab-pane fade show active" id="account" role="tabpanel" aria-labelledby="account-tab">
                            <div class="p-4 pb-0">
                                <form action="{{ route('profile.update') }}" method="POST" id="profile-update-form" enctype="multipart/form-data">
                                @csrf
                                @method('PATCH')
                                
                                <div class="row gy-3">
                                    <div class="col-xl-12">
                                        <div class="d-flex align-items-start flex-wrap gap-3">
                                            <div>
                                                <span class="avatar avatar-xxl" id="avatar-preview">
                                                    @if($user->avatar)
                                                        <img src="{{ $user->secure_avatar_url }}" alt="Profile Picture" id="avatar-img">
                                                    @else
                                                        <span class="avatar-initial" id="avatar-initial">{{ substr($user->name ?? 'U', 0, 1) }}</span>
                                                    @endif
                                                </span>
                                            </div>
                                            <div>
                                                <span class="fw-medium d-block mb-2">Profile Picture</span>
                                                <div class="btn-list mb-1">
                                                    <label for="avatar-upload" class="btn btn-sm btn-primary btn-wave" style="cursor: pointer;">
                                                        <i class="ri-upload-2-line me-1"></i>Change Image
                                                    </label>
                                                    <input type="file" id="avatar-upload" name="avatar" accept="image/jpeg,image/png,image/jpg,image/gif" style="display: none;" onchange="previewAvatar(this)">
                                                    @if($user->avatar)
                                                        <button type="button" class="btn btn-sm btn-light btn-wave" onclick="removeAvatar()">
                                                            <i class="ri-delete-bin-line me-1"></i>Remove
                                                        </button>
                                                    @endif
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
                                        <div class="input-group">
                                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="profile-email" name="email" value="{{ old('email', $user->email) }}" required>
                                            <span class="input-group-text p-0 border-0">
                                                @if($user->email_verified_at)
                                                    <span class="badge bg-success-transparent text-success ms-2" title="Email Verified">
                                                        <i class="ri-checkbox-circle-line me-1"></i>Verified
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger-transparent text-danger ms-2" title="Email Not Verified">
                                                        <i class="ri-error-warning-line me-1"></i>Unverified
                                                    </span>
                                                @endif
                                            </span>
                                        </div>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        @if(!$user->email_verified_at)
                                            <div class="mt-2">
                                                <a href="{{ route('email.verification.show') }}" class="btn btn-sm btn-primary btn-wave">
                                                    <i class="ri-mail-check-line me-1"></i>Verify Email
                                                </a>
                                                <small class="text-muted d-block mt-1">Please verify your email address to secure your account.</small>
                                            </div>
                                        @endif
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
                                        <input type="text" class="form-control" value="{{ $user->role == 1 ? 'Administrator' : 'User' }}" disabled>
                                        <small class="text-muted">Role cannot be changed</small>
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

                                <!-- Save Changes Button -->
                                <div class="row mt-4">
                                    <div class="col-xl-12">
                                        <div class="btn-list float-end">
                                            <button type="submit" class="btn btn-primary btn-wave">
                                                <i class="ri-save-line me-1"></i>Save Changes
                                            </button>
                                            <a href="{{ route('panel') }}" class="btn btn-secondary btn-wave">
                                                <i class="ri-arrow-left-line me-1"></i>Go Back
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                </form>
                            </div>
                        </div>

                        <!-- Active Sessions Tab -->
                        <div class="tab-pane fade" id="sessions" role="tabpanel" aria-labelledby="sessions-tab">
                            <div class="p-4 pb-0">
                                <!-- Current Session Information -->
                                <div class="mb-3">
                                    <h6 class="mb-3">
                                        <i class="ri-computer-line me-1"></i>Current Session
                                    </h6>
                                    <div class="row gx-3 gx-md-5 gy-3">
                                        <div class="col-12 col-md-6">
                                                    <div class="d-flex align-items-center gap-3 mb-3">
                                                        <div class="lh-1">
                                                            <span class="avatar avatar-md bg-primary-transparent avatar-rounded">
                                                                <i class="ri-computer-line fs-18 text-primary"></i>
                                                            </span>
                                                        </div>
                                                        <div class="flex-fill">
                                                            <p class="fs-14 mb-0 fw-medium">{{ $currentSession['device_type'] ?? 'Desktop' }}</p>
                                                            <p class="fs-12 mb-0 text-muted">{{ $currentSession['platform'] ?? 'Unknown' }} • {{ $currentSession['browser'] ?? 'Unknown Browser' }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-3 mb-3">
                                                        <div class="lh-1">
                                                            <span class="avatar avatar-md bg-success-transparent avatar-rounded">
                                                                <i class="ri-map-pin-line fs-18 text-success"></i>
                                                            </span>
                                                        </div>
                                                        <div class="flex-fill">
                                                            <p class="fs-14 mb-0 fw-medium">IP Address</p>
                                                            <p class="fs-12 mb-0 text-muted">{{ $currentSession['ip_address'] ?? 'Unknown' }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="lh-1">
                                                            <span class="avatar avatar-md bg-info-transparent avatar-rounded">
                                                                <i class="ri-time-line fs-18 text-info"></i>
                                                            </span>
                                                        </div>
                                                        <div class="flex-fill">
                                                            <p class="fs-14 mb-0 fw-medium">Last Activity</p>
                                                            <p class="fs-12 mb-0 text-muted">{{ $currentSession['last_activity'] ?? 'Unknown' }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <div class="d-flex align-items-center gap-3 mb-3">
                                                        <div class="lh-1">
                                                            <span class="avatar avatar-md bg-warning-transparent avatar-rounded">
                                                                <i class="ri-login-circle-line fs-18 text-warning"></i>
                                                            </span>
                                                        </div>
                                                        <div class="flex-fill">
                                                            <p class="fs-14 mb-0 fw-medium">Logged In At</p>
                                                            <p class="fs-12 mb-0 text-muted">{{ $currentSession['logged_in_at'] ?? 'Unknown' }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-3 mb-3">
                                                        <div class="lh-1">
                                                            <span class="avatar avatar-md bg-secondary-transparent avatar-rounded">
                                                                <i class="ri-fingerprint-line fs-18 text-secondary"></i>
                                                            </span>
                                                        </div>
                                                        <div class="flex-fill">
                                                            <p class="fs-14 mb-0 fw-medium">Session ID</p>
                                                            <p class="fs-12 mb-0 text-muted text-break" title="{{ $currentSession['session_id'] ?? 'Unknown' }}">
                                                                <span class="d-inline-block" style="max-width: 100%; word-break: break-all;">{{ \Illuminate\Support\Str::limit($currentSession['session_id'] ?? 'Unknown', 30) }}</span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="lh-1">
                                                            <span class="avatar avatar-md bg-danger-transparent avatar-rounded">
                                                                <i class="ri-shield-check-line fs-18 text-danger"></i>
                                                            </span>
                                                        </div>
                                                        <div class="flex-fill">
                                                            <p class="fs-14 mb-0 fw-medium">Status</p>
                                                            <p class="fs-12 mb-0">
                                                                <span class="badge bg-success-transparent text-success">Active</span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                    </div>
                                </div>

                                <!-- Active Sessions Table -->
                                <div id="active-sessions-wrapper">
                                    <h6 class="mb-3">All Active Sessions</h6>
                                    <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                                        <table id="active-sessions-table" class="table table-bordered text-nowrap w-100" style="width: 100% !important; display: table;">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Device</th>
                                                    <th>Browser</th>
                                                    <th>Platform</th>
                                                    <th>IP Address</th>
                                                    <th>Logged In At</th>
                                                    <th>Last Activity</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($activeSessions as $session)
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center gap-2">
                                                                @if($session['device_type'] === 'mobile')
                                                                    <i class="ri-smartphone-line text-primary fs-18"></i>
                                                                @elseif($session['device_type'] === 'tablet')
                                                                    <i class="ri-tablet-line text-info fs-18"></i>
                                                                @else
                                                                    <i class="ri-computer-line text-success fs-18"></i>
                                                                @endif
                                                                <span class="text-capitalize">{{ $session['device_type'] ?? 'Desktop' }}</span>
                                                                @if($session['is_current'])
                                                                    <span class="badge bg-primary-transparent text-primary">Current</span>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td>{{ $session['browser'] ?? 'Unknown' }}</td>
                                                        <td>{{ $session['platform'] ?? 'Unknown' }}</td>
                                                        <td>
                                                            <span class="badge bg-primary-transparent">{{ $session['ip_address'] ?? 'Unknown' }}</span>
                                                        </td>
                                                        <td>
                                                            <div>
                                                                <span class="fw-medium">{{ \Carbon\Carbon::parse($session['logged_in_at'])->format('M d, Y') }}</span>
                                                                <br>
                                                                <span class="text-muted fs-12">{{ \Carbon\Carbon::parse($session['logged_in_at'])->format('H:i:s') }}</span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div>
                                                                <span class="fw-medium">{{ \Carbon\Carbon::parse($session['last_activity'])->format('M d, Y') }}</span>
                                                                <br>
                                                                <span class="text-muted fs-12">{{ \Carbon\Carbon::parse($session['last_activity'])->format('H:i:s') }}</span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            @if($session['is_current'])
                                                                <span class="badge bg-success">Current Session</span>
                                                            @else
                                                                <span class="badge bg-info">Active</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($session['is_current'])
                                                                <button type="button" class="btn btn-sm btn-light btn-wave" disabled title="This is your current session">
                                                                    <i class="ri-lock-line me-1"></i>Current
                                                                </button>
                                                            @else
                                                                <form action="{{ route('profile.sessions.terminate', $session['session_id']) }}" method="POST" class="d-inline" onsubmit="return confirmTerminateSession(event, '{{ $session['device_type'] }}', '{{ $session['platform'] }}', '{{ $session['browser'] }}')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-danger btn-wave">
                                                                        <i class="ri-close-circle-line me-1"></i>Terminate
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="8" class="text-center py-4">
                                                            <div class="d-flex flex-column align-items-center">
                                                                <span class="avatar avatar-xl avatar-rounded bg-secondary-transparent mb-2">
                                                                    <i class="ri-computer-line fs-2"></i>
                                                                </span>
                                                                <p class="text-muted mb-0">No active sessions found</p>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Login Activities Tab -->
                        <div class="tab-pane fade" id="activities" role="tabpanel" aria-labelledby="activities-tab">
                            <div class="p-4 pb-0">
                                <div id="login-activities-wrapper">
                                    <h6 class="mb-3">Login Activities History</h6>
                                    <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                                        <table id="login-activities-table" class="table table-bordered text-nowrap w-100" style="width: 100% !important; display: table;">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Date & Time</th>
                                                    <th>IP Address</th>
                                                    <th>Device</th>
                                                    <th>Browser</th>
                                                    <th>Platform</th>
                                                    <th>Action</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                        <tbody>
                                            @forelse($loginActivities as $activity)
                                                <tr>
                                                    <td>
                                                        <div>
                                                            <span class="fw-medium">{{ $activity->logged_in_at ? $activity->logged_in_at->format('M d, Y') : '-' }}</span>
                                                            <br>
                                                            <span class="text-muted fs-12">{{ $activity->logged_in_at ? $activity->logged_in_at->format('H:i:s') : '-' }}</span>
                                                            @if($activity->logged_out_at)
                                                                <br>
                                                                <span class="text-muted fs-11">Out: {{ $activity->logged_out_at->format('M d, H:i') }}</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary-transparent">{{ $activity->ip_address ?? 'Unknown' }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            @if($activity->device_type === 'mobile')
                                                                <i class="ri-smartphone-line text-primary"></i>
                                                            @elseif($activity->device_type === 'tablet')
                                                                <i class="ri-tablet-line text-info"></i>
                                                            @else
                                                                <i class="ri-computer-line text-success"></i>
                                                            @endif
                                                            <span class="text-capitalize">{{ $activity->device_type ?? 'Desktop' }}</span>
                                                        </div>
                                                    </td>
                                                    <td>{{ $activity->browser ?? 'Unknown' }}</td>
                                                    <td>{{ $activity->platform ?? 'Unknown' }}</td>
                                                    <td>
                                                        @if($activity->action === 'login')
                                                            <span class="badge bg-success-transparent text-success">
                                                                <i class="ri-login-circle-line me-1"></i>Login
                                                            </span>
                                                        @elseif($activity->action === 'logout')
                                                            <span class="badge bg-danger-transparent text-danger">
                                                                <i class="ri-logout-circle-line me-1"></i>Logout
                                                            </span>
                                                        @else
                                                            <span class="badge bg-warning-transparent text-warning">
                                                                <i class="ri-error-warning-line me-1"></i>Failed
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($activity->is_active && $activity->logged_out_at === null)
                                                            <span class="badge bg-success">Active</span>
                                                        @else
                                                            <span class="badge bg-secondary">Inactive</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center py-4">
                                                        <div class="d-flex flex-column align-items-center">
                                                            <span class="avatar avatar-xl avatar-rounded bg-secondary-transparent mb-2">
                                                                <i class="ri-history-line fs-2"></i>
                                                            </span>
                                                            <p class="text-muted mb-0">No login activities found</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
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
    
    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
    
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
            
            // Initialize DataTables for Active Sessions (using same style as Login Activities)
            // Initialize when tab is shown to ensure proper rendering
            const sessionsTab = document.getElementById('sessions-tab');
            const sessionsTable = $('#active-sessions-table');
            let activeSessionsTableInitialized = false;
            
            function initActiveSessionsTable() {
                if (activeSessionsTableInitialized) return;
                
                if (typeof $.fn.DataTable !== 'undefined' && sessionsTable.length) {
                    // Check if already initialized
                    if ($.fn.DataTable.isDataTable('#active-sessions-table')) {
                        activeSessionsTableInitialized = true;
                        return;
                    }
                    
                    try {
                        const activeSessionsDT = sessionsTable.DataTable({
                            responsive: true,
                            autoWidth: false,
                            scrollX: false,
                            scrollCollapse: false,
                            language: {
                                searchPlaceholder: 'Search sessions...',
                                sSearch: '',
                            },
                            pageLength: 10,
                            order: [[4, 'desc']], // Sort by "Logged In At" descending
                            columnDefs: [
                                { orderable: false, targets: [7] } // Disable sorting on Action column
                            ],
                            initComplete: function() {
                                // Force column width recalculation after initialization
                                const api = this.api();
                                api.columns.adjust();
                                // Recalculate after a small delay to ensure DOM is ready
                                setTimeout(function() {
                                    api.columns.adjust().draw(false);
                                }, 100);
                            }
                        });
                        activeSessionsTableInitialized = true;
                    } catch (e) {
                        console.error('Error initializing active sessions DataTable:', e);
                    }
                }
            }
            
            // Initialize on page load if tab is already active
            if (sessionsTab && sessionsTab.classList.contains('active')) {
                setTimeout(initActiveSessionsTable, 300);
            }
            
            // Initialize when tab is shown
            if (sessionsTab) {
                sessionsTab.addEventListener('shown.bs.tab', function() {
                    setTimeout(function() {
                        initActiveSessionsTable();
                        // Recalculate column widths after tab is shown
                        if ($.fn.DataTable.isDataTable('#active-sessions-table')) {
                            $('#active-sessions-table').DataTable().columns.adjust().draw(false);
                        }
                    }, 300);
                });
            }
            
            // Fallback: Initialize after a delay if not already done
            setTimeout(function() {
                if (!activeSessionsTableInitialized && sessionsTable.length) {
                    initActiveSessionsTable();
                }
            }, 1000);
            
            // Initialize DataTables for Login Activities (using same style as reference)
            // Initialize when tab is shown to ensure proper rendering
            const activitiesTab = document.getElementById('activities-tab');
            const activitiesTable = $('#login-activities-table');
            let loginActivitiesTableInitialized = false;
            
            function initLoginActivitiesTable() {
                if (loginActivitiesTableInitialized) return;
                
                if (typeof $.fn.DataTable !== 'undefined' && activitiesTable.length) {
                    // Check if already initialized
                    if ($.fn.DataTable.isDataTable('#login-activities-table')) {
                        loginActivitiesTableInitialized = true;
                        return;
                    }
                    
                    try {
                        const loginActivitiesDT = activitiesTable.DataTable({
                            responsive: true,
                            autoWidth: false,
                            scrollX: false,
                            scrollCollapse: false,
                            language: {
                                searchPlaceholder: 'Search activities...',
                                sSearch: '',
                            },
                            pageLength: 10,
                            order: [[0, 'desc']], // Sort by "Date & Time" descending
                            columnDefs: [
                                { orderable: false, targets: [5, 6] } // Disable sorting on Action and Status columns
                            ],
                            initComplete: function() {
                                // Force column width recalculation after initialization
                                const api = this.api();
                                api.columns.adjust();
                                // Recalculate after a small delay to ensure DOM is ready
                                setTimeout(function() {
                                    api.columns.adjust().draw(false);
                                }, 100);
                            }
                        });
                        loginActivitiesTableInitialized = true;
                    } catch (e) {
                        console.error('Error initializing login activities DataTable:', e);
                    }
                }
            }
            
            // Initialize on page load if tab is already active
            if (activitiesTab && activitiesTab.classList.contains('active')) {
                setTimeout(initLoginActivitiesTable, 300);
            }
            
            // Initialize when tab is shown
            if (activitiesTab) {
                activitiesTab.addEventListener('shown.bs.tab', function() {
                    setTimeout(function() {
                        initLoginActivitiesTable();
                        // Recalculate column widths after tab is shown
                        if ($.fn.DataTable.isDataTable('#login-activities-table')) {
                            $('#login-activities-table').DataTable().columns.adjust().draw(false);
                        }
                    }, 300);
                });
            }
            
            // Fallback: Initialize after a delay if not already done
            setTimeout(function() {
                if (!loginActivitiesTableInitialized && activitiesTable.length) {
                    initLoginActivitiesTable();
                }
            }, 1000);
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
                    const avatarPreview = document.getElementById('avatar-preview');
                    const avatarImg = document.getElementById('avatar-img');
                    const avatarInitial = document.getElementById('avatar-initial');
                    
                    if (avatarImg) {
                        avatarImg.src = e.target.result;
                    } else {
                        // Create img element if it doesn't exist
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Profile Picture';
                        img.id = 'avatar-img';
                        if (avatarInitial) {
                            avatarInitial.remove();
                        }
                        avatarPreview.appendChild(img);
                    }
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
                    confirmButtonText: '<i class="ri-delete-bin-line me-1"></i>Yes, remove it!',
                    cancelButtonText: '<i class="ri-close-line me-1"></i>Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const avatarPreview = document.getElementById('avatar-preview');
                        const avatarImg = document.getElementById('avatar-img');
                        const avatarInitial = document.getElementById('avatar-initial');
                        const userName = '{{ $user->name ?? "U" }}';
                        
                        // Remove image and show initial
                        if (avatarImg) {
                            avatarImg.remove();
                        }
                        
                        if (!avatarInitial) {
                            const initial = document.createElement('span');
                            initial.className = 'avatar-initial';
                            initial.id = 'avatar-initial';
                            initial.textContent = userName.charAt(0).toUpperCase();
                            avatarPreview.appendChild(initial);
                        }
                        
                        document.getElementById('remove-avatar-flag').value = '1';
                        document.getElementById('avatar-upload').value = '';
                    }
                });
            } else {
                if (confirm('Are you sure you want to remove the profile picture?')) {
                    document.getElementById('remove-avatar-flag').value = '1';
                    document.getElementById('avatar-upload').value = '';
                }
            }
        }

        function confirmTerminateSession(event, deviceType, platform, browser) {
            if (typeof Swal !== 'undefined') {
                event.preventDefault();
                Swal.fire({
                    title: 'Terminate Session?',
                    html: `Are you sure you want to terminate this session?<br><br><strong>Device:</strong> ${deviceType}<br><strong>Platform:</strong> ${platform}<br><strong>Browser:</strong> ${browser}`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="ri-close-circle-line me-1"></i>Yes, terminate!',
                    cancelButtonText: '<i class="ri-close-line me-1"></i>Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        event.target.closest('form').submit();
                    }
                });
                return false;
            }
            return confirm(`Are you sure you want to terminate this session?\n\nDevice: ${deviceType}\nPlatform: ${platform}\nBrowser: ${browser}`);
        }
    </script>
@endsection
