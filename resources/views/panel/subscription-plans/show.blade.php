@extends('layouts.master')

@section('styles')
    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.css')}}">
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Subscription Plan Details</h1>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('panel.subscription-plans.index') }}" class="btn btn-sm btn-light btn-wave">
                    <i class="ri-arrow-left-line me-1"></i>Back to Plans
                </a>
            </div>
        </div>
        <ol class="breadcrumb mb-0 mt-2">
            <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
            <li class="breadcrumb-item"><a href="{{ route('panel.subscription-plans.index') }}">Subscription Plans</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $subscriptionPlan->name }}</li>
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
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Plan Information</div>
                </div>
                <div class="card-body">
                    <form action="{{ route('panel.subscription-plans.update', $subscriptionPlan->uid) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Plan Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $subscriptionPlan->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="color" class="form-label">Color Theme <span class="text-danger">*</span></label>
                                <select class="form-control @error('color') is-invalid @enderror" 
                                        id="color" name="color" required>
                                    <option value="primary" {{ old('color', $subscriptionPlan->color) == 'primary' ? 'selected' : '' }}>Primary</option>
                                    <option value="success" {{ old('color', $subscriptionPlan->color) == 'success' ? 'selected' : '' }}>Success</option>
                                    <option value="warning" {{ old('color', $subscriptionPlan->color) == 'warning' ? 'selected' : '' }}>Warning</option>
                                </select>
                                @error('color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" name="description" rows="3">{{ old('description', $subscriptionPlan->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="price_monthly" class="form-label">Monthly Price ($) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control @error('price_monthly') is-invalid @enderror" 
                                       id="price_monthly" name="price_monthly" value="{{ old('price_monthly', $subscriptionPlan->price_monthly) }}" required>
                                @error('price_monthly')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="price_yearly" class="form-label">Yearly Price ($) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control @error('price_yearly') is-invalid @enderror" 
                                       id="price_yearly" name="price_yearly" value="{{ old('price_yearly', $subscriptionPlan->price_yearly) }}" required>
                                @error('price_yearly')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="icon" class="form-label">Icon (SVG)</label>
                                <textarea class="form-control @error('icon') is-invalid @enderror" 
                                          id="icon" name="icon" rows="4">{{ old('icon', $subscriptionPlan->icon) }}</textarea>
                                @error('icon')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="order" class="form-label">Display Order</label>
                                <input type="number" class="form-control @error('order') is-invalid @enderror" 
                                       id="order" name="order" value="{{ old('order', $subscriptionPlan->order) }}" min="0">
                                @error('order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" 
                                           id="is_active" name="is_active" value="1" 
                                           {{ old('is_active', $subscriptionPlan->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Recommended</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_recommended" value="0">
                                    <input class="form-check-input" type="checkbox" 
                                           id="is_recommended" name="is_recommended" value="1" 
                                           {{ old('is_recommended', $subscriptionPlan->is_recommended) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_recommended">Mark as Recommended</label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3">Assign Features</h6>
                        <div class="row">
                            @foreach($allFeatures as $feature)
                                @php
                                    $planFeature = $subscriptionPlan->features->firstWhere('id', $feature->id);
                                    $isAssigned = $planFeature !== null;
                                @endphp
                                <div class="col-md-6 mb-3">
                                    <div class="card border {{ $isAssigned ? 'border-success' : '' }}">
                                        <div class="card-body">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input feature-checkbox" type="checkbox" 
                                                       id="feature_{{ $feature->id }}" 
                                                       name="features[{{ $feature->id }}][id]" 
                                                       value="{{ $feature->id }}"
                                                       {{ $isAssigned ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="feature_{{ $feature->id }}">
                                                    {{ $feature->name }}
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mb-2">{{ $feature->description }}</small>
                                            
                                            <div class="feature-options" style="display: {{ $isAssigned ? 'block' : 'none' }};">
                                                <div class="mb-2">
                                                    <label class="form-label small">Limit (optional)</label>
                                                    <input type="number" class="form-control form-control-sm" 
                                                           name="features[{{ $feature->id }}][limit]" 
                                                           value="{{ $planFeature->pivot->limit ?? '' }}"
                                                           placeholder="e.g., 10" min="0">
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small">Limit Type (optional)</label>
                                                    <input type="text" class="form-control form-control-sm" 
                                                           name="features[{{ $feature->id }}][limit_type]" 
                                                           value="{{ $planFeature->pivot->limit_type ?? '' }}"
                                                           placeholder="e.g., count, duration">
                                                </div>
                                                <div>
                                                    <label class="form-label small">Display Value (optional)</label>
                                                    <input type="text" class="form-control form-control-sm" 
                                                           name="features[{{ $feature->id }}][value]" 
                                                           value="{{ $planFeature->pivot->value ?? '' }}"
                                                           placeholder="e.g., 10 Web Monitoring checks">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-wave">
                                <i class="ri-save-line me-1"></i>Update Plan
                            </button>
                            <a href="{{ route('panel.subscription-plans.index') }}" class="btn btn-secondary btn-wave">
                                <i class="ri-close-line me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Plan Statistics</div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Total Features:</span>
                        <span class="fw-semibold">{{ $subscriptionPlan->features->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Active Subscriptions:</span>
                        <span class="fw-semibold">{{ $subscriptionPlan->userSubscriptions()->where('status', 'active')->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Status:</span>
                        <span class="badge {{ $subscriptionPlan->is_active ? 'bg-success' : 'bg-danger' }}">
                            {{ $subscriptionPlan->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    @if($subscriptionPlan->is_recommended)
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Recommended:</span>
                            <span class="badge bg-dark">Yes</span>
                        </div>
                    @endif
                    <hr>
                    <div class="d-flex justify-content-between mb-0">
                        <span class="text-muted">Created:</span>
                        <span class="fw-semibold">{{ $subscriptionPlan->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->
@endsection

@section('scripts')
    <script>
        // Show/hide feature options when checkbox is checked
        document.querySelectorAll('.feature-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const options = this.closest('.card-body').querySelector('.feature-options');
                if (this.checked) {
                    options.style.display = 'block';
                } else {
                    options.style.display = 'none';
                    // Clear values when unchecked
                    options.querySelectorAll('input').forEach(input => {
                        if (input.type !== 'checkbox') {
                            input.value = '';
                        }
                    });
                }
            });
        });
    </script>
@endsection

