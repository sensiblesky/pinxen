@extends('layouts.master')

@section('styles')



@endsection

@section('content')
	
                    <!-- Start::page-header -->
                    <div class="d-flex align-items-center justify-content-between mb-3 page-header-breadcrumb flex-wrap gap-2">
                        <div>
                            <h1 class="page-title fw-medium fs-20 mb-0">Dashboard</h1>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="form-group">
                                <input type="text" class="form-control breadcrumb-input" id="daterange" placeholder="Search By Date Range">
                            </div>
                            <div class="btn-list">
                                <button class="btn btn-icon btn-primary btn-wave">
                                    <i class="ri-refresh-line"></i>
                                </button>
                                <button class="btn btn-icon btn-primary btn-wave me-0">
                                    <i class="ri-filter-3-line"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- End::page-header -->

                    <!-- Start:: row-1 -->
                    <div class="row">
                        <div class="col-xxl-12">
                            <div class="card custom-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h6 class="fw-semibold mb-0">Welcome to Dashboard</h6>
                                    </div>
                                    <div class="p-4 text-center">
                                        <p class="text-muted mb-0">{{ __("You're logged in!") }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End:: row-1 -->

@endsection

@section('scripts')

@endsection
