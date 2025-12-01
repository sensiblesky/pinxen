@extends('layouts.master')

@section('styles')
    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.css')}}">
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Storage Configuration</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                <li class="breadcrumb-item active" aria-current="page">Storage</li>
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
    <form action="{{ route('panel.storage.update') }}" method="POST" id="storage-form">
        @csrf
        @method('PUT')
        
        <div class="row">
            <!-- Default Storage Disk Selection -->
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">Default Storage Disk</div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-xl-12">
                                <label for="default_storage_disk" class="form-label">Default Storage Disk <span class="text-danger">*</span></label>
                                <select class="form-control @error('default_storage_disk') is-invalid @enderror" id="default_storage_disk" name="default_storage_disk" data-trigger required>
                                    <option value="local" {{ old('default_storage_disk', $settings['default_storage_disk'] ?? 'local') == 'local' ? 'selected' : '' }}>Local</option>
                                    <option value="s3" {{ old('default_storage_disk', $settings['default_storage_disk'] ?? '') == 's3' ? 'selected' : '' }}>Amazon S3</option>
                                    <option value="wasabi" {{ old('default_storage_disk', $settings['default_storage_disk'] ?? '') == 'wasabi' ? 'selected' : '' }}>Wasabi</option>
                                </select>
                                @error('default_storage_disk')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="alert alert-info mt-3 mb-0">
                                    <i class="ri-information-line me-1"></i>
                                    <strong>Note:</strong> Amazon S3 and Wasabi are only available for public files. If you want to use them for private files, you need to set the disk in the file model. They will not work unless you have the credentials set and passed the test connection.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Storage Providers -->
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">Storage Providers Configuration</div>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3 border-0" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" role="tab" href="#local-tab" aria-selected="true">
                                    <i class="ri-folder-line me-1"></i>Local & Default Storage
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#s3-tab" aria-selected="false">
                                    <i class="ri-cloud-line me-1"></i>Amazon S3 Storage
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#wasabi-tab" aria-selected="false">
                                    <i class="ri-cloud-fill me-1"></i>Wasabi Storage
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <!-- Local Storage Tab -->
                            <div class="tab-pane fade show active" id="local-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-12">
                                        <div class="alert alert-info mb-0">
                                            <i class="ri-information-line me-1"></i>
                                            <strong>Local Storage:</strong> Files are stored on the local server filesystem. This is the default storage option and requires no additional configuration.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Amazon S3 Tab -->
                            <div class="tab-pane fade" id="s3-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="s3_key" class="form-label">Access Key ID</label>
                                        <input type="text" class="form-control @error('s3_key') is-invalid @enderror" id="s3_key" name="s3_key" value="{{ old('s3_key', $settings['s3_key'] ?? '') }}" placeholder="Your AWS Access Key ID">
                                        @error('s3_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="s3_secret_key" class="form-label">Secret Access Key</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('s3_secret_key') is-invalid @enderror" id="s3_secret_key" name="s3_secret_key" value="{{ old('s3_secret_key', $settings['s3_secret_key'] ?? '') }}" placeholder="Your AWS Secret Access Key">
                                            <button class="btn btn-light" type="button" id="toggle_s3_secret">
                                                <i class="ri-eye-line" id="s3_secret_icon"></i>
                                            </button>
                                        </div>
                                        @error('s3_secret_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current secret</span>
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="s3_region" class="form-label">Region</label>
                                        <input type="text" class="form-control @error('s3_region') is-invalid @enderror" id="s3_region" name="s3_region" value="{{ old('s3_region', $settings['s3_region'] ?? '') }}" placeholder="us-east-1">
                                        @error('s3_region')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="s3_bucket" class="form-label">Bucket Name</label>
                                        <input type="text" class="form-control @error('s3_bucket') is-invalid @enderror" id="s3_bucket" name="s3_bucket" value="{{ old('s3_bucket', $settings['s3_bucket'] ?? '') }}" placeholder="your-bucket-name">
                                        @error('s3_bucket')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-12">
                                        <label for="s3_endpoint" class="form-label">Endpoint (Optional)</label>
                                        <input type="url" class="form-control @error('s3_endpoint') is-invalid @enderror" id="s3_endpoint" name="s3_endpoint" value="{{ old('s3_endpoint', $settings['s3_endpoint'] ?? '') }}" placeholder="https://s3.amazonaws.com">
                                        @error('s3_endpoint')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave empty to use default AWS endpoint</span>
                                    </div>
                                    <div class="col-xl-12">
                                        <button type="button" class="btn btn-info btn-wave" onclick="testConnection('s3')">
                                            <i class="ri-plug-line me-1"></i>Test Connection
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Wasabi Tab -->
                            <div class="tab-pane fade" id="wasabi-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="wasabi_key" class="form-label">Access Key ID</label>
                                        <input type="text" class="form-control @error('wasabi_key') is-invalid @enderror" id="wasabi_key" name="wasabi_key" value="{{ old('wasabi_key', $settings['wasabi_key'] ?? '') }}" placeholder="Your Wasabi Access Key ID">
                                        @error('wasabi_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="wasabi_secret_key" class="form-label">Secret Access Key</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('wasabi_secret_key') is-invalid @enderror" id="wasabi_secret_key" name="wasabi_secret_key" value="{{ old('wasabi_secret_key', $settings['wasabi_secret_key'] ?? '') }}" placeholder="Your Wasabi Secret Access Key">
                                            <button class="btn btn-light" type="button" id="toggle_wasabi_secret">
                                                <i class="ri-eye-line" id="wasabi_secret_icon"></i>
                                            </button>
                                        </div>
                                        @error('wasabi_secret_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current secret</span>
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="wasabi_region" class="form-label">Region</label>
                                        <input type="text" class="form-control @error('wasabi_region') is-invalid @enderror" id="wasabi_region" name="wasabi_region" value="{{ old('wasabi_region', $settings['wasabi_region'] ?? '') }}" placeholder="us-east-1">
                                        @error('wasabi_region')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="wasabi_bucket" class="form-label">Bucket Name</label>
                                        <input type="text" class="form-control @error('wasabi_bucket') is-invalid @enderror" id="wasabi_bucket" name="wasabi_bucket" value="{{ old('wasabi_bucket', $settings['wasabi_bucket'] ?? '') }}" placeholder="your-bucket-name">
                                        @error('wasabi_bucket')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-12">
                                        <label for="wasabi_endpoint" class="form-label">Endpoint (Optional)</label>
                                        <input type="url" class="form-control @error('wasabi_endpoint') is-invalid @enderror" id="wasabi_endpoint" name="wasabi_endpoint" value="{{ old('wasabi_endpoint', $settings['wasabi_endpoint'] ?? '') }}" placeholder="https://s3.wasabisys.com">
                                        @error('wasabi_endpoint')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave empty to use default Wasabi endpoint</span>
                                    </div>
                                    <div class="col-xl-12">
                                        <button type="button" class="btn btn-info btn-wave" onclick="testConnection('wasabi')">
                                            <i class="ri-plug-line me-1"></i>Test Connection
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="btn-list float-end">
                            <button type="submit" class="btn btn-primary btn-wave">
                                <i class="ri-save-line me-1"></i>Save Configuration
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <!-- End::row-1 -->

    <!-- Test Connection Form (Hidden) -->
    <form id="test-connection-form" method="POST" action="{{ route('panel.storage.test-connection') }}" style="display: none;">
        @csrf
        <input type="hidden" name="provider" id="test-provider">
    </form>

@endsection

@section('scripts')
    <!-- Choices JS -->
    <script src="{{asset('build/assets/libs/choices.js/public/assets/scripts/choices.min.js')}}"></script>
    
    <!-- Sweetalerts JS -->
    <script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>
    
    <script>
        // Initialize Choices.js for select dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            const selects = document.querySelectorAll('select[data-trigger]');
            selects.forEach(select => {
                new Choices(select, {
                    searchEnabled: false,
                    placeholder: true,
                });
            });
        });

        // Toggle password visibility functions
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('ri-eye-line');
                icon.classList.add('ri-eye-off-line');
            } else {
                input.type = 'password';
                icon.classList.remove('ri-eye-off-line');
                icon.classList.add('ri-eye-line');
            }
        }

        // S3 Secret toggle
        document.getElementById('toggle_s3_secret')?.addEventListener('click', function() {
            togglePasswordVisibility('s3_secret_key', 's3_secret_icon');
        });

        // Wasabi Secret toggle
        document.getElementById('toggle_wasabi_secret')?.addEventListener('click', function() {
            togglePasswordVisibility('wasabi_secret_key', 'wasabi_secret_icon');
        });

        // Test connection function
        function testConnection(provider) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Testing Connection',
                    text: 'Please wait while we test the connection...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }
            
            document.getElementById('test-provider').value = provider;
            document.getElementById('test-connection-form').submit();
        }
    </script>
@endsection



