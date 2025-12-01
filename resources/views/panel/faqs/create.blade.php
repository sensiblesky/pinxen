@extends('layouts.master')

@section('styles')
    <!-- Quill Editor CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/quill/quill.snow.css')}}">
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Create FAQ</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                <li class="breadcrumb-item"><a href="{{ route('panel.faqs.index') }}">FAQs</a></li>
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
                    <div class="card-title">
                        FAQ Information
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('panel.faqs.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="question" class="form-label">Question <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('question') is-invalid @enderror" 
                                       id="question" name="question" value="{{ old('question') }}" 
                                       placeholder="Enter FAQ question" required>
                                @error('question')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="answer" class="form-label">Answer <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('answer') is-invalid @enderror" 
                                          id="answer" name="answer" rows="8" 
                                          placeholder="Enter FAQ answer" required>{{ old('answer') }}</textarea>
                                @error('answer')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-control @error('category') is-invalid @enderror" 
                                        id="category" name="category">
                                    <option value="General" {{ old('category', 'General') == 'General' ? 'selected' : '' }}>General</option>
                                    <option value="Account & Login" {{ old('category') == 'Account & Login' ? 'selected' : '' }}>Account & Login</option>
                                    <option value="Payments & Billing" {{ old('category') == 'Payments & Billing' ? 'selected' : '' }}>Payments & Billing</option>
                                    <option value="Website Usage & Features" {{ old('category') == 'Website Usage & Features' ? 'selected' : '' }}>Website Usage & Features</option>
                                    <option value="Technical Support" {{ old('category') == 'Technical Support' ? 'selected' : '' }}>Technical Support</option>
                                    <option value="Privacy & Security" {{ old('category') == 'Privacy & Security' ? 'selected' : '' }}>Privacy & Security</option>
                                </select>
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Select a category for this FAQ</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="icon" class="form-label">Icon Class</label>
                                <input type="text" class="form-control @error('icon') is-invalid @enderror" 
                                       id="icon" name="icon" value="{{ old('icon', 'ri-question-line') }}" 
                                       placeholder="ri-question-line">
                                @error('icon')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">RemixIcon class (e.g., ri-question-line, ri-user-line, ri-lock-line)</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="order" class="form-label">Order</label>
                                <input type="number" class="form-control @error('order') is-invalid @enderror" 
                                       id="order" name="order" value="{{ old('order', 0) }}" 
                                       min="0" placeholder="Display order">
                                @error('order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Lower numbers appear first</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" 
                                           id="is_active" name="is_active" value="1" 
                                           {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                                <small class="text-muted">Only active FAQs will be displayed on the support page</small>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-wave">
                                <i class="ri-save-line me-1"></i>Create FAQ
                            </button>
                            <a href="{{ route('panel.faqs.index') }}" class="btn btn-secondary btn-wave">
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

