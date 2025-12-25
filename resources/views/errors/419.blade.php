
@extends('layouts.custom-master')

@php
// Passing the bodyClass variable from the view to the layout
$bodyClass = '';
@endphp

@section('styles')



@endsection

@section('content')
	
        <div class="page error-bg">
            <div class="error-page-background">
                <img src="{{asset('build/assets/images/media/backgrounds/10.svg')}}" alt="">
            </div>
            <!-- Start::error-page -->
            <div class="row align-items-center justify-content-center h-100 g-0">
                <div class="col-xl-7 col-lg-7 col-md-7 col-12">
                    <div class="text-center px-2">
                        <div class="text-center mb-5">
                            <img src="{{asset('build/assets/images/media/backgrounds/11.png')}}" alt="" class="w-sm-auto w-100 h-100">
                        </div>
                        <span class="d-block fs-4 text-primary fw-semibold">Oops! Page Expired</span>
                        <p class="error-text mb-0">419</p>
                        <p class="fs-5 fw-normal mb-0">The page has expired. Please refresh the page.</p><br>
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">Refresh Page</a>
                    </div>
                </div>
            </div>
        </div>

@endsection

@section('scripts')
	


@endsection
