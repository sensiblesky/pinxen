@extends('layouts.master')

@section('styles')

@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Create Subscription Plan</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                <li class="breadcrumb-item"><a href="{{ route('panel.subscription-plans.index') }}">Subscription Plans</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create</li>
            </ol>
        </div>
    </div>
    <!-- End::page-header -->

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Plan Information</div>
                </div>
                <div class="card-body">
                    <form action="{{ route('panel.subscription-plans.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Plan Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" 
                                       placeholder="e.g., Basic, Pro, Enterprise" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="color" class="form-label">Color Theme <span class="text-danger">*</span></label>
                                <select class="form-control @error('color') is-invalid @enderror" 
                                        id="color" name="color" required>
                                    <option value="primary" {{ old('color', 'primary') == 'primary' ? 'selected' : '' }}>Primary</option>
                                    <option value="success" {{ old('color') == 'success' ? 'selected' : '' }}>Success</option>
                                    <option value="warning" {{ old('color') == 'warning' ? 'selected' : '' }}>Warning</option>
                                </select>
                                @error('color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" name="description" rows="3" 
                                          placeholder="Plan description">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="price_monthly" class="form-label">Monthly Price ($) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control @error('price_monthly') is-invalid @enderror" 
                                       id="price_monthly" name="price_monthly" value="{{ old('price_monthly', 0) }}" 
                                       placeholder="0.00" required>
                                @error('price_monthly')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="price_yearly" class="form-label">Yearly Price ($) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control @error('price_yearly') is-invalid @enderror" 
                                       id="price_yearly" name="price_yearly" value="{{ old('price_yearly', 0) }}" 
                                       placeholder="0.00" required>
                                @error('price_yearly')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="icon" class="form-label">Icon (SVG)</label>
                                <textarea class="form-control @error('icon') is-invalid @enderror" 
                                          id="icon" name="icon" rows="4" 
                                          placeholder="<svg>...</svg>">{{ old('icon') }}</textarea>
                                @error('icon')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Paste SVG icon code here</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="order" class="form-label">Display Order</label>
                                <input type="number" class="form-control @error('order') is-invalid @enderror" 
                                       id="order" name="order" value="{{ old('order', 0) }}" 
                                       min="0" placeholder="0">
                                @error('order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Lower numbers appear first</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" 
                                           id="is_active" name="is_active" value="1" 
                                           {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Recommended</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_recommended" value="0">
                                    <input class="form-check-input" type="checkbox" 
                                           id="is_recommended" name="is_recommended" value="1" 
                                           {{ old('is_recommended', false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_recommended">
                                        Mark as Recommended
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3">Assign Features</h6>
                        <div class="row">
                            @foreach($features as $feature)
                                <div class="col-md-6 mb-3">
                                    <div class="card border">
                                        <div class="card-body">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input feature-checkbox" type="checkbox" 
                                                       id="feature_{{ $feature->id }}" 
                                                       name="features[{{ $feature->id }}][id]" 
                                                       value="{{ $feature->id }}">
                                                <label class="form-check-label fw-semibold" for="feature_{{ $feature->id }}">
                                                    {{ $feature->name }}
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mb-2">{{ $feature->description }}</small>
                                            
                                            <div class="feature-options" style="display: none;">
                                                <div class="mb-2">
                                                    <label class="form-label small">Limit (optional)</label>
                                                    <input type="number" class="form-control form-control-sm" 
                                                           name="features[{{ $feature->id }}][limit]" 
                                                           placeholder="e.g., 10" min="0">
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small">Limit Type (optional)</label>
                                                    <input type="text" class="form-control form-control-sm" 
                                                           name="features[{{ $feature->id }}][limit_type]" 
                                                           placeholder="e.g., count, duration">
                                                </div>
                                                <div>
                                                    <label class="form-label small">Display Value (optional)</label>
                                                    <input type="text" class="form-control form-control-sm" 
                                                           name="features[{{ $feature->id }}][value]" 
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
                                <i class="ri-save-line me-1"></i>Create Plan
                            </button>
                            <a href="{{ route('panel.subscription-plans.index') }}" class="btn btn-secondary btn-wave">
                                <i class="ri-close-line me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
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
                }
            });
        });
    </script>
@endsection

