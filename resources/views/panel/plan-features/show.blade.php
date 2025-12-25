@extends('layouts.master')

@section('styles')
    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.css')}}">
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Plan Feature Details</h1>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('panel.plan-features.edit', $planFeature->uid) }}" class="btn btn-sm btn-warning btn-wave">
                    <i class="ri-edit-line me-1"></i>Edit Feature
                </a>
                <a href="{{ route('panel.plan-features.index') }}" class="btn btn-sm btn-light btn-wave">
                    <i class="ri-arrow-left-line me-1"></i>Back to Features
                </a>
            </div>
        </div>
        <ol class="breadcrumb mb-0 mt-2">
            <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
            <li class="breadcrumb-item"><a href="{{ route('panel.plan-features.index') }}">Plan Features</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $planFeature->name }}</li>
        </ol>
    </div>
    <!-- End::page-header -->

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Feature Information</div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Feature Name</label>
                            <div class="fw-semibold fs-16">{{ $planFeature->name }}</div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Description</label>
                            <div>{{ $planFeature->description ?? 'No description' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Icon</label>
                            <div>
                                @if($planFeature->icon)
                                    <div class="d-inline-block">{!! $planFeature->icon !!}</div>
                                @else
                                    <i class="ri-star-line fs-18"></i>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Display Order</label>
                            <div>{{ $planFeature->order }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <div>
                                <span class="badge {{ $planFeature->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $planFeature->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Assigned Plans</div>
                </div>
                <div class="card-body">
                    @if($planFeature->subscriptionPlans->count() > 0)
                        <ul class="list-unstyled mb-0">
                            @foreach($planFeature->subscriptionPlans as $plan)
                                <li class="mb-2">
                                    <a href="{{ route('panel.subscription-plans.show', $plan->uid) }}" class="text-primary">
                                        <i class="ri-arrow-right-line me-1"></i>{{ $plan->name }}
                                    </a>
                                    @if($plan->pivot->limit)
                                        <span class="badge bg-info ms-2">Limit: {{ $plan->pivot->limit }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0">This feature is not assigned to any plan.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->
@endsection

@section('scripts')
    <!-- SweetAlert JS -->
    <script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>
@endsection






