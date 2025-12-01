@extends('layouts.master')

@section('styles')
    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.css')}}">
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Cache Management</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                <li class="breadcrumb-item active" aria-current="page">Cache Management</li>
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
        <!-- Cache Statistics -->
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Cache Statistics</div>
                </div>
                <div class="card-body p-4">
                    <div class="row gy-3">
                        <div class="col-xl-3 col-md-6">
                            <div class="card border border-primary">
                                <div class="card-body text-center">
                                    <div class="mb-2">
                                        <i class="ri-database-2-line fs-32 text-primary"></i>
                                    </div>
                                    <h5 class="mb-1">Cache Driver</h5>
                                    <p class="mb-0 text-muted">{{ ucfirst($cacheStats['cache_driver'] ?? 'file') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border border-success">
                                <div class="card-body text-center">
                                    <div class="mb-2">
                                        <i class="ri-folder-line fs-32 text-success"></i>
                                    </div>
                                    <h5 class="mb-1">Cache Size</h5>
                                    <p class="mb-0 text-muted">{{ $cacheStats['cache_size'] ?? '0 MB' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border border-info">
                                <div class="card-body text-center">
                                    <div class="mb-2">
                                        <i class="ri-file-list-3-line fs-32 text-info"></i>
                                    </div>
                                    <h5 class="mb-1">View Cache Files</h5>
                                    <p class="mb-0 text-muted">{{ $cacheStats['view_cached'] ?? 0 }} files</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border border-warning">
                                <div class="card-body text-center">
                                    <div class="mb-2">
                                        <i class="ri-settings-3-line fs-32 text-warning"></i>
                                    </div>
                                    <h5 class="mb-1">Settings Cache</h5>
                                    <p class="mb-0 text-muted">
                                        <span class="badge bg-{{ $cacheStats['settings_cached'] ? 'success' : 'secondary' }}">
                                            {{ $cacheStats['settings_cached'] ? 'Cached' : 'Not Cached' }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cache Status -->
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Cache Status</div>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Cache Type</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="ri-database-2-line me-2 text-primary"></i>
                                            <span>Application Cache</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">Active</span>
                                    </td>
                                    <td>
                                        <form action="{{ route('panel.cache-management.clear-specific') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="type" value="application">
                                            <button type="submit" class="btn btn-sm btn-danger btn-wave">
                                                <i class="ri-delete-bin-line me-1"></i>Clear
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="ri-settings-3-line me-2 text-warning"></i>
                                            <span>Configuration Cache</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $cacheStats['config_cached'] ? 'success' : 'secondary' }}">
                                            {{ $cacheStats['config_cached'] ? 'Cached' : 'Not Cached' }}
                                        </span>
                                    </td>
                                    <td>
                                        <form action="{{ route('panel.cache-management.clear-specific') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="type" value="config">
                                            <button type="submit" class="btn btn-sm btn-danger btn-wave">
                                                <i class="ri-delete-bin-line me-1"></i>Clear
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="ri-route-line me-2 text-info"></i>
                                            <span>Route Cache</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $cacheStats['route_cached'] ? 'success' : 'secondary' }}">
                                            {{ $cacheStats['route_cached'] ? 'Cached' : 'Not Cached' }}
                                        </span>
                                    </td>
                                    <td>
                                        <form action="{{ route('panel.cache-management.clear-specific') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="type" value="route">
                                            <button type="submit" class="btn btn-sm btn-danger btn-wave">
                                                <i class="ri-delete-bin-line me-1"></i>Clear
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="ri-file-list-3-line me-2 text-success"></i>
                                            <span>View Cache</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $cacheStats['view_cached'] ?? 0 }} files</span>
                                    </td>
                                    <td>
                                        <form action="{{ route('panel.cache-management.clear-specific') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="type" value="view">
                                            <button type="submit" class="btn btn-sm btn-danger btn-wave">
                                                <i class="ri-delete-bin-line me-1"></i>Clear
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="ri-speed-up-line me-2 text-primary"></i>
                                            <span>Optimization Cache</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">Active</span>
                                    </td>
                                    <td>
                                        <form action="{{ route('panel.cache-management.clear-specific') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="type" value="optimize">
                                            <button type="submit" class="btn btn-sm btn-danger btn-wave">
                                                <i class="ri-delete-bin-line me-1"></i>Clear
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="ri-settings-4-line me-2 text-warning"></i>
                                            <span>Settings Cache</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $cacheStats['settings_cached'] ? 'success' : 'secondary' }}">
                                            {{ $cacheStats['settings_cached'] ? 'Cached' : 'Not Cached' }}
                                        </span>
                                    </td>
                                    <td>
                                        <form action="{{ route('panel.cache-management.clear-specific') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="type" value="settings">
                                            <button type="submit" class="btn btn-sm btn-danger btn-wave">
                                                <i class="ri-delete-bin-line me-1"></i>Clear
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cache Actions -->
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Cache Actions</div>
                </div>
                <div class="card-body p-4">
                    <div class="row gy-3">
                        <div class="col-xl-4 col-md-6">
                            <div class="card border border-danger">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="ri-delete-bin-7-line fs-48 text-danger"></i>
                                    </div>
                                    <h5 class="mb-2">Clear All Cache</h5>
                                    <p class="text-muted mb-3">Clear all application, config, route, view, and optimization cache</p>
                                    <form action="{{ route('panel.cache-management.clear-all') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-wave">
                                            <i class="ri-delete-bin-line me-1"></i>Clear All Cache
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card border border-success">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="ri-speed-up-line fs-48 text-success"></i>
                                    </div>
                                    <h5 class="mb-2">Optimize Application</h5>
                                    <p class="text-muted mb-3">Cache configuration, routes, views, and events for better performance</p>
                                    <form action="{{ route('panel.cache-management.optimize') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-wave">
                                            <i class="ri-speed-up-line me-1"></i>Optimize
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card border border-info">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="ri-fire-line fs-48 text-info"></i>
                                    </div>
                                    <h5 class="mb-2">Warm Up Cache</h5>
                                    <p class="text-muted mb-3">Preload settings and rebuild config/route cache for faster response</p>
                                    <form action="{{ route('panel.cache-management.warmup') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-info btn-wave">
                                            <i class="ri-fire-line me-1"></i>Warm Up
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->

@endsection

@section('scripts')
    <!-- Sweetalerts JS -->
    <script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>
    
    <script>
        // Wait for DOM to be ready
        (function() {
            function initCacheManagement() {
                // Handle all clear cache buttons
                document.querySelectorAll('form[action*="clear-specific"]').forEach(function(form) {
                    const button = form.querySelector('button[type="submit"]');
                    if (button && !button.hasAttribute('data-listener-attached')) {
                        button.setAttribute('data-listener-attached', 'true');
                        button.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            const type = form.querySelector('input[name="type"]').value;
                            const typeName = button.textContent.trim().replace('Clear', '').trim() || type;
                            
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: 'Clear Cache?',
                                    html: `Are you sure you want to clear <strong>${typeName}</strong>?`,
                                    icon: 'question',
                                    showCancelButton: true,
                                    confirmButtonColor: '#d33',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: '<i class="ri-delete-bin-line me-1"></i>Yes, clear it!',
                                    cancelButtonText: '<i class="ri-close-line me-1"></i>Cancel',
                                    reverseButtons: true
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        form.submit();
                                    }
                                });
                            } else {
                                if (confirm(`Are you sure you want to clear ${typeName}?`)) {
                                    form.submit();
                                }
                            }
                            return false;
                        });
                    }
                });

                // Handle Clear All Cache button
                const clearAllForm = document.querySelector('form[action*="clear-all"]');
                if (clearAllForm) {
                    const clearAllButton = clearAllForm.querySelector('button[type="submit"]');
                    if (clearAllButton && !clearAllButton.hasAttribute('data-listener-attached')) {
                        clearAllButton.setAttribute('data-listener-attached', 'true');
                        clearAllButton.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: 'Clear All Cache?',
                                    html: 'Are you sure you want to clear <strong>ALL</strong> cache?<br><br>This will clear:<br>• Application Cache<br>• Configuration Cache<br>• Route Cache<br>• View Cache<br>• Optimization Cache<br>• Settings Cache',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#d33',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: '<i class="ri-delete-bin-line me-1"></i>Yes, clear all!',
                                    cancelButtonText: '<i class="ri-close-line me-1"></i>Cancel',
                                    reverseButtons: true
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        clearAllForm.submit();
                                    }
                                });
                            } else {
                                if (confirm('Are you sure you want to clear ALL cache?')) {
                                    clearAllForm.submit();
                                }
                            }
                            return false;
                        });
                    }
                }

                // Handle Optimize button
                const optimizeForm = document.querySelector('form[action*="optimize"]');
                if (optimizeForm) {
                    const optimizeButton = optimizeForm.querySelector('button[type="submit"]');
                    if (optimizeButton && !optimizeButton.hasAttribute('data-listener-attached')) {
                        optimizeButton.setAttribute('data-listener-attached', 'true');
                        optimizeButton.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: 'Optimize Application?',
                                    html: 'This will cache configuration, routes, views, and events for better performance.',
                                    icon: 'question',
                                    showCancelButton: true,
                                    confirmButtonColor: '#28a745',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: '<i class="ri-speed-up-line me-1"></i>Yes, optimize!',
                                    cancelButtonText: '<i class="ri-close-line me-1"></i>Cancel',
                                    reverseButtons: true
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        optimizeForm.submit();
                                    }
                                });
                            } else {
                                if (confirm('Are you sure you want to optimize the application?')) {
                                    optimizeForm.submit();
                                }
                            }
                            return false;
                        });
                    }
                }

                // Handle Warm Up button
                const warmupForm = document.querySelector('form[action*="warmup"]');
                if (warmupForm) {
                    const warmupButton = warmupForm.querySelector('button[type="submit"]');
                    if (warmupButton && !warmupButton.hasAttribute('data-listener-attached')) {
                        warmupButton.setAttribute('data-listener-attached', 'true');
                        warmupButton.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: 'Warm Up Cache?',
                                    html: 'This will preload settings and rebuild config/route cache for faster response times.',
                                    icon: 'question',
                                    showCancelButton: true,
                                    confirmButtonColor: '#17a2b8',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: '<i class="ri-fire-line me-1"></i>Yes, warm up!',
                                    cancelButtonText: '<i class="ri-close-line me-1"></i>Cancel',
                                    reverseButtons: true
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        warmupForm.submit();
                                    }
                                });
                            } else {
                                if (confirm('Are you sure you want to warm up the cache?')) {
                                    warmupForm.submit();
                                }
                            }
                            return false;
                        });
                    }
                }
            }

            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initCacheManagement);
            } else {
                initCacheManagement();
            }
        })();
    </script>
@endsection

