@extends('layouts.master')

@section('styles')
<link rel="stylesheet" href="{{asset('build/assets/libs/swiper/swiper-bundle.min.css')}}">
<link rel="stylesheet" href="{{asset('build/assets/libs/apexcharts/apexcharts.css')}}">
<style>
    /* Professional Dashboard Enhancements */
    :root {
        --card-shadow: 0 2px 8px rgba(0,0,0,0.08);
        --card-shadow-hover: 0 8px 24px rgba(0,0,0,0.12);
        --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Enhanced Card Styling */
    .dashboard-main-card {
        border-radius: 16px;
        transition: var(--transition-smooth);
        border: 1px solid rgba(0,0,0,0.05);
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    }
    
    .dashboard-main-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--card-gradient-start, #5d87ff), var(--card-gradient-end, #49beff));
        opacity: 0;
        transition: var(--transition-smooth);
    }
    
    .dashboard-main-card:hover::before {
        opacity: 1;
    }
    
    .dashboard-main-card:hover {
        transform: translateY(-6px);
        box-shadow: var(--card-shadow-hover);
        border-color: rgba(93, 135, 255, 0.2);
    }
    
    /* Status Pulse Animation */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    
    .status-indicator {
        position: relative;
        display: inline-block;
    }
    
    .status-indicator.pulse::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: currentColor;
        animation: pulse 2s ease-in-out infinite;
    }
    
    /* Avatar Enhancements */
    .avatar {
        transition: var(--transition-smooth);
        position: relative;
    }
    
    .dashboard-main-card:hover .avatar {
        transform: scale(1.1) rotate(5deg);
    }
    
    .avatar img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 50%;
        transition: var(--transition-smooth);
    }
    
    /* List Item Enhancements */
    .top-customers-list li {
        padding: 0;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        transition: var(--transition-smooth);
        border-radius: 8px;
        margin-bottom: 4px;
    }
    
    .top-customers-list li a {
        display: block;
        padding: 14px 0;
        text-decoration: none;
        color: inherit;
        cursor: pointer;
        transition: var(--transition-smooth);
        border-radius: 8px;
    }
    
    .top-customers-list li a:hover {
        background: rgba(93, 135, 255, 0.05);
        transform: translateX(4px);
        padding-left: 8px;
        color: inherit;
    }
    
    .top-customers-list li:last-child {
        border-bottom: none;
    }
    
    /* Text Truncation - Fixed Width to Prevent Layout Breaking */
    .monitor-name {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 140px;
        min-width: 0;
    }
    
    .monitor-url, .monitor-domain, .monitor-hostname {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 140px;
        min-width: 0;
        font-size: 12px;
    }
    
    /* Flex Layout Fixes for Recent Monitors Cards */
    .top-customers-list .d-flex {
        flex-wrap: nowrap !important;
        align-items: center;
    }
    
    .top-customers-list .flex-fill {
        min-width: 0;
        overflow: hidden;
        max-width: 140px;
        flex: 1 1 auto;
    }
    
    .top-customers-list .text-end {
        flex-shrink: 0;
        min-width: 70px;
        max-width: 70px;
        text-align: right;
        white-space: nowrap;
    }
    
    .top-customers-list .text-end > div {
        white-space: nowrap;
        line-height: 1.2;
    }
    
    .top-customers-list .text-end .fs-12 {
        display: block;
        white-space: nowrap;
    }
    
    /* Ensure cards don't break layout */
    .boxed-col-3 {
        min-width: 0;
    }
    
    .boxed-col-3 .card {
        height: 100%;
        overflow: hidden;
    }
    
    .boxed-col-3 .card-body {
        overflow: hidden;
    }
    
    /* Prevent list items from breaking */
    .top-customers-list li {
        min-width: 0;
    }
    
    .top-customers-list li a {
        min-width: 0;
        overflow: hidden;
    }
    
    /* Swiper Enhancements */
    .uptime-monitors-swiper .swiper-slide {
        height: auto;
    }
    
    .uptime-monitors-swiper .card {
        height: 100%;
        min-height: 140px;
        transition: var(--transition-smooth);
        border: 1px solid rgba(0,0,0,0.05);
    }
    
    .uptime-monitors-swiper .card:hover {
        transform: translateY(-4px);
        box-shadow: var(--card-shadow-hover);
    }
    
    .uptime-monitors-swiper .card-body {
        height: 100%;
        display: flex;
        flex-direction: column;
        padding: 1rem;
    }
    
    .uptime-monitors-swiper .card-body > div:first-child {
        flex: 0 0 auto;
    }
    
    .uptime-monitors-swiper .card-body > div:last-child {
        flex: 1 1 auto;
        display: flex;
        align-items: flex-end;
    }
    
    .uptime-monitors-swiper .url-text {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100%;
        font-size: 11px;
    }
    
    /* Slider Card Text Truncation */
    .uptime-monitors-swiper .card-body {
        overflow: hidden;
    }
    
    .uptime-monitors-swiper .monitor-name {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100%;
    }
    
    .uptime-monitors-swiper .d-flex.flex-fill {
        min-width: 0;
        overflow: hidden;
    }
    
    .uptime-monitors-swiper .d-flex.flex-fill .flex-fill {
        min-width: 0;
        overflow: hidden;
    }
    
    .uptime-monitors-swiper .d-flex.align-items-end {
        min-width: 0;
    }
    
    .uptime-monitors-swiper .d-flex.align-items-end .flex-fill {
        min-width: 0;
        overflow: hidden;
        max-width: calc(100% - 80px); /* Reserve space for badge */
    }
    
    .uptime-monitors-swiper .flex-shrink-0 {
        flex-shrink: 0 !important;
        min-width: auto;
    }
    
    /* Badge Enhancements */
    .badge {
        transition: var(--transition-smooth);
        font-weight: 500;
        letter-spacing: 0.3px;
    }
    
    .badge:hover {
        transform: scale(1.05);
    }
    
    /* Refresh Button Animation */
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .refresh-btn.refreshing i {
        animation: spin 1s linear infinite;
    }
    
    /* Loading Skeleton */
    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s ease-in-out infinite;
    }
    
    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    
    /* Trend Indicators */
    .trend-up {
        color: #10b981;
        animation: slideUp 0.5s ease-out;
    }
    
    .trend-down {
        color: #ef4444;
        animation: slideDown 0.5s ease-out;
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Card Header Enhancements */
    .card-header {
        border-bottom: 1px solid rgba(0,0,0,0.06);
        padding: 1rem 1.25rem;
    }
    
    .card-title {
        font-weight: 600;
        letter-spacing: -0.3px;
    }
    
    /* Number Counter Animation */
    .counter-number {
        font-variant-numeric: tabular-nums;
        transition: var(--transition-smooth);
    }
    
    /* Empty State Styling */
    .empty-state {
        padding: 3rem 1rem;
        text-align: center;
    }
    
    .empty-state i {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }
    
    /* Smooth Scroll */
    html {
        scroll-behavior: smooth;
    }
    
    /* Professional Typography */
    .dashboard-main-card h4 {
        font-weight: 700;
        letter-spacing: -0.5px;
        font-size: 1.75rem;
    }
    
    /* Status Badge Pulse */
    .status-badge.pulse {
        animation: pulse 2s ease-in-out infinite;
    }
    
    /* Swiper Navigation Enhancements */
    .swiper {
        padding-bottom: 20px;
    }
    
    /* Responsive Improvements */
    @media (max-width: 768px) {
        .dashboard-main-card {
            margin-bottom: 1rem;
        }
        
        .dashboard-main-card:hover {
            transform: translateY(-2px);
        }
    }
    
    /* Gradient Overlays for Cards */
    .dashboard-main-card.primary::before {
        --card-gradient-start: #5d87ff;
        --card-gradient-end: #49beff;
    }
    
    .dashboard-main-card.secondary::before {
        --card-gradient-start: #6c757d;
        --card-gradient-end: #495057;
    }
    
    .dashboard-main-card.success::before {
        --card-gradient-start: #10b981;
        --card-gradient-end: #059669;
    }
    
    .dashboard-main-card.warning::before {
        --card-gradient-start: #f59e0b;
        --card-gradient-end: #d97706;
    }
    
    .dashboard-main-card.danger::before {
        --card-gradient-start: #ef4444;
        --card-gradient-end: #dc2626;
    }
    
    .dashboard-main-card.info::before {
        --card-gradient-start: #3b82f6;
        --card-gradient-end: #2563eb;
    }
    
    /* Recent Users List Styling */
    .hrm-employee-list {
        margin: 0;
        padding: 0;
    }
    
    .hrm-employee-list li {
        padding: 0;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        transition: var(--transition-smooth);
    }
    
    .hrm-employee-list li:last-child {
        border-bottom: none;
    }
    
    .hrm-employee-list li a {
        display: block;
        padding: 1rem 0;
        text-decoration: none;
        color: inherit;
        cursor: pointer;
        transition: var(--transition-smooth);
        border-radius: 8px;
    }
    
    .hrm-employee-list li a:hover {
        background: rgba(93, 135, 255, 0.05);
        transform: translateX(4px);
        padding-left: 0.5rem;
        padding-right: 0.5rem;
        color: inherit;
    }
    
    .avatar-initial {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        font-weight: 600;
        font-size: 0.875rem;
    }
    
    /* Container and Layout Fixes */
    .main-body-container {
        padding-top: 1.5rem;
        padding-bottom: 2rem;
        position: relative;
        z-index: 1;
    }
    
    /* Prevent row overlap with container */
    .main-body-container .row {
        margin-left: 0;
        margin-right: 0;
    }
    
    .main-body-container .row > [class*="col-"] {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    
    /* Ensure rows don't intercept */
    .main-body-container .row.mb-4 {
        margin-bottom: 1.5rem !important;
    }
    
    /* Dark Mode Support */
    [data-theme-mode="dark"] .dashboard-main-card {
        background: linear-gradient(135deg, var(--default-body-bg-color, #1a1d29) 0%, var(--default-body-bg-color, #1a1d29) 100%);
        border-color: rgba(255, 255, 255, 0.1);
    }
    
    [data-theme-mode="dark"] .card.custom-card {
        background-color: var(--default-body-bg-color, #1a1d29);
        border-color: rgba(255, 255, 255, 0.1);
        color: var(--default-text-color, #e9ecef);
    }
    
    [data-theme-mode="dark"] .card-header {
        border-bottom-color: rgba(255, 255, 255, 0.1);
        background-color: transparent;
    }
    
    [data-theme-mode="dark"] .card-body {
        background-color: transparent;
        color: var(--default-text-color, #e9ecef);
    }
    
    [data-theme-mode="dark"] .card-footer {
        background-color: rgba(255, 255, 255, 0.05);
        border-top-color: rgba(255, 255, 255, 0.1);
    }
    
    [data-theme-mode="dark"] .hrm-employee-list li {
        border-bottom-color: rgba(255, 255, 255, 0.1);
    }
    
    [data-theme-mode="dark"] .hrm-employee-list li a:hover {
        background: rgba(93, 135, 255, 0.1);
    }
    
    [data-theme-mode="dark"] .top-customers-list li {
        border-bottom-color: rgba(255, 255, 255, 0.1);
    }
    
    [data-theme-mode="dark"] .top-customers-list li a:hover {
        background: rgba(93, 135, 255, 0.1);
    }
    
    [data-theme-mode="dark"] .text-muted {
        color: rgba(255, 255, 255, 0.6) !important;
    }
    
    /* Ensure proper spacing between sections */
    .main-body-container > .row {
        margin-bottom: 1.5rem;
    }
    
    .main-body-container > .row:last-child {
        margin-bottom: 0;
    }
    
    /* Sales Overview Chart Legend - Inline Display */
    #sales-overview .apexcharts-legend {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        justify-content: flex-end !important;
        align-items: center !important;
    }
    
    #sales-overview .apexcharts-legend-series {
        display: inline-flex !important;
        margin: 0 10px !important;
        flex-direction: row !important;
        align-items: center !important;
    }
    
    #sales-overview .apexcharts-legend-marker {
        margin-right: 6px !important;
    }
    
    /* Responsive text truncation for smaller screens */
    @media (max-width: 1400px) {
        .monitor-name, .monitor-url, .monitor-domain, .monitor-hostname {
            max-width: 120px;
        }
        
        .top-customers-list .flex-fill {
            max-width: 120px;
        }
        
        .top-customers-list .text-end {
            min-width: 65px;
            max-width: 65px;
        }
    }
    
    @media (max-width: 1200px) {
        .monitor-name, .monitor-url, .monitor-domain, .monitor-hostname {
            max-width: 100px;
        }
        
        .top-customers-list .flex-fill {
            max-width: 100px;
        }
        
        .top-customers-list .text-end {
            min-width: 60px;
            max-width: 60px;
        }
    }
    
    @media (max-width: 992px) {
        .monitor-name, .monitor-url, .monitor-domain, .monitor-hostname {
            max-width: 140px;
        }
        
        .top-customers-list .flex-fill {
            max-width: 140px;
        }
        
        .top-customers-list .text-end {
            min-width: 70px;
            max-width: 70px;
        }
    }
    
    @media (max-width: 768px) {
        .monitor-name, .monitor-url, .monitor-domain, .monitor-hostname {
            max-width: 100px;
        }
        
        .top-customers-list .flex-fill {
            max-width: 100px;
        }
        
        .top-customers-list .text-end {
            min-width: 60px;
            max-width: 60px;
        }
    }
</style>
@endsection

@section('content')
    @php
        // Safety check - ensure stats variable exists
        $stats = $stats ?? [];
    @endphp
	
                    <!-- Start::page-header -->
    <div class="d-flex align-items-center justify-content-between mb-4 page-header-breadcrumb flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-20 mb-0">Admin Dashboard</h1>
            <p class="text-muted mb-0">Comprehensive monitoring overview (All Users)</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="btn-list">
                <button id="refresh-dashboard" class="btn btn-icon btn-primary btn-wave refresh-btn" title="Refresh Dashboard">
                    <i class="ri-refresh-line"></i>
                </button>
                <span class="badge bg-primary-transparent text-primary ms-2" id="last-updated" style="display: none;">
                    <i class="ri-time-line me-1"></i><span id="update-time">Just now</span>
                </span>
            </div>
        </div>
    </div>
                    <!-- End::page-header -->

    <!-- Start:: Uptime Monitors Swiper Slider -->
    @if($sliderUptimeMonitors->count() > 0)
    <div class="row mb-4">
        <div class="col-xl-12">
            <h6 class="fw-semibold mb-3">Uptime Monitors</h6>
            <div class="card custom-card">
                                                <div class="card-body">
                    <div class="swiper uptime-monitors-swiper">
                        <div class="swiper-wrapper">
                            @foreach($sliderUptimeMonitors as $monitor)
                            <div class="swiper-slide">
                                <a href="{{ route('panel.uptime-monitors.show', $monitor) }}" class="text-decoration-none">
                                    <div class="card custom-card mb-0 h-100">
                                        <div class="card-body">
                                            <div class="d-flex gap-2 flex-wrap align-items-center justify-content-between mb-3">
                                                <div class="d-flex flex-fill align-items-center min-w-0">
                                                    <div class="me-2 lh-1 flex-shrink-0">
                                                        <span class="avatar avatar-md {{ $monitor->status === 'up' ? 'bg-success-transparent' : ($monitor->status === 'down' ? 'bg-danger-transparent' : 'bg-warning-transparent') }}">
                                                            @if(isset($uptimeMonitorFavicons[$monitor->id]) && $uptimeMonitorFavicons[$monitor->id])
                                                                <img src="{{ $uptimeMonitorFavicons[$monitor->id] }}" alt="{{ $monitor->name }}" onerror="this.onerror=null; this.src='{{ asset('build/assets/images/brand-logos/favicon.ico') }}';">
                                                            @else
                                                                <img src="{{ asset('build/assets/images/brand-logos/favicon.ico') }}" alt="{{ $monitor->name }}" onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'ri-global-line\'></i>';">
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <div class="lh-1 min-w-0 flex-fill">
                                                        <span class="d-block text-default fw-medium monitor-name" title="{{ $monitor->name }}">{{ $monitor->name }}</span>
                                                    </div>
                                                </div>
                                                <div class="fs-12 text-end flex-shrink-0">
                                                    <span class="{{ $monitor->status === 'up' ? 'text-success' : ($monitor->status === 'down' ? 'text-danger' : 'text-warning') }} d-block">
                                                        {{ ucfirst($monitor->status) }}
                                                        @if($monitor->status === 'up')
                                                            <i class="ri-arrow-up-line"></i>
                                                        @elseif($monitor->status === 'down')
                                                            <i class="ri-arrow-down-line"></i>
                                                        @else
                                                            <i class="ri-alert-line"></i>
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-end justify-content-between">
                                                <div class="flex-fill min-w-0 me-2">
                                                    <span class="d-block text-muted fs-12 mb-1">Status Trend</span>
                                                    <div id="sparkline-{{ $monitor->id }}" style="height: 35px; width: 100%;"></div>
                                                </div>
                                                <div class="flex-shrink-0">
                                                    <span class="badge {{ $monitor->status === 'up' ? 'bg-success' : ($monitor->status === 'down' ? 'bg-danger' : 'bg-warning') }}">
                                                        {{ $monitor->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                            </div>
                                        </div>
                                                            </div>
                                                        </div>
                                </a>
                                                        </div>
                            @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
        </div>
    </div>
    @endif
    <!-- End:: Uptime Monitors Swiper Slider -->

    <!-- Start:: row-1 - Summary Cards -->
    <div class="row mb-4">
        <!-- Uptime Monitors -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 mb-3">
            <a href="{{ route('panel.uptime-monitors.index') }}" class="text-decoration-none">
                <div class="card custom-card dashboard-main-card primary overflow-hidden h-100">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-start gap-3">
                                                        <div class="flex-fill">
                                <span class="fs-13 fw-medium">Uptime Monitors</span>
                                <h4 class="fw-semibold my-2 lh-1 counter-number">{{ $stats['uptime']['total'] ?? 0 }}</h4>
                                                            <div class="d-flex align-items-center justify-content-between">
                                    <span class="fs-12 d-block text-muted">
                                        <span class="text-success me-1 d-inline-flex align-items-center fw-semibold">
                                            <i class="ri-arrow-up-line me-1"></i>{{ $stats['uptime']['up'] ?? 0 }} Up
                                        </span>
                                        <span class="text-muted">| {{ $stats['uptime']['down'] ?? 0 }} Down</span>
                                    </span>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <span class="avatar avatar-md bg-primary-transparent svg-primary">
                                    <i class="ri-global-line fs-20"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
            </a>
                                        </div>

        <!-- Domain Monitors -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 mb-3">
            <a href="{{ route('panel.domain-monitors.index') }}" class="text-decoration-none">
                <div class="card custom-card dashboard-main-card secondary overflow-hidden h-100">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-start gap-3">
                                                        <div class="flex-fill">
                                <span class="fs-13 fw-medium">Domain Monitors</span>
                                <h4 class="fw-semibold my-2 lh-1 counter-number">{{ $stats['domain']['total'] ?? 0 }}</h4>
                                                            <div class="d-flex align-items-center justify-content-between">
                                    <span class="fs-12 d-block text-muted">
                                        <span class="text-warning me-1 d-inline-flex align-items-center fw-semibold">
                                            <i class="ri-alert-line me-1"></i>{{ $stats['domain']['expiring_soon'] ?? 0 }} Expiring
                                        </span>
                                    </span>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <span class="avatar avatar-md bg-secondary-transparent svg-secondary">
                                    <i class="ri-calendar-close-line fs-20"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
            </a>
                                        </div>

        <!-- SSL Monitors -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 mb-3">
            <a href="{{ route('panel.ssl-monitors.index') }}" class="text-decoration-none">
                <div class="card custom-card dashboard-main-card warning overflow-hidden h-100">
                                        <div class="card-body">
                                                    <div class="d-flex align-items-start gap-3">
                                                        <div class="flex-fill">
                                <span class="fs-13 fw-medium">SSL Monitors</span>
                                <h4 class="fw-semibold my-2 lh-1 counter-number">{{ $stats['ssl']['total'] ?? 0 }}</h4>
                                                            <div class="d-flex align-items-center justify-content-between">
                                    <span class="fs-12 d-block text-muted">
                                        <span class="text-success me-1 d-inline-flex align-items-center fw-semibold">
                                            <i class="ri-shield-check-line me-1"></i>{{ $stats['ssl']['valid'] ?? 0 }} Valid
                                        </span>
                                        <span class="text-muted">| {{ $stats['ssl']['expired'] ?? 0 }} Expired</span>
                                    </span>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <span class="avatar avatar-md bg-warning-transparent svg-warning">
                                    <i class="ri-lock-password-line fs-20"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
            </a>
                                        </div>

        <!-- DNS Monitors -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 mb-3">
            <a href="{{ route('panel.dns-monitors.index') }}" class="text-decoration-none">
                <div class="card custom-card dashboard-main-card success overflow-hidden h-100">
                    <div class="card-body">
                                                    <div class="d-flex align-items-start gap-3">
                                                        <div class="flex-fill">
                                <span class="fs-13 fw-medium">DNS Monitors</span>
                                <h4 class="fw-semibold my-2 lh-1 counter-number">{{ $stats['dns']['total'] ?? 0 }}</h4>
                                                            <div class="d-flex align-items-center justify-content-between">
                                    <span class="fs-12 d-block text-muted">
                                        <span class="text-success me-1 d-inline-flex align-items-center fw-semibold">
                                            <i class="ri-checkbox-circle-line me-1"></i>{{ $stats['dns']['healthy'] ?? 0 }} Healthy
                                        </span>
                                                    </span>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <span class="avatar avatar-md bg-success-transparent svg-success">
                                    <i class="ri-router-line fs-20"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
            </a>
                                        </div> 

        <!-- API Monitors -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 mb-3">
            <a href="{{ route('panel.api-monitors.index') }}" class="text-decoration-none">
                <div class="card custom-card dashboard-main-card info overflow-hidden h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-fill">
                                <span class="fs-13 fw-medium">API Monitors</span>
                                <h4 class="fw-semibold my-2 lh-1 counter-number">{{ $stats['api']['total'] ?? 0 }}</h4>
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="fs-12 d-block text-muted">
                                        <span class="text-success me-1 d-inline-flex align-items-center fw-semibold">
                                            <i class="ri-code-s-slash-line me-1"></i>{{ $stats['api']['up'] ?? 0 }} Up
                                        </span>
                                        <span class="text-muted">| {{ $stats['api']['down'] ?? 0 }} Down</span>
                                                    </span>
                                    </div>
                                </div>
                            <div>
                                <span class="avatar avatar-md bg-info-transparent svg-info">
                                    <i class="ri-code-s-slash-line fs-20"></i>
                                                    </span>
                                            </div>
                                            </div>
                                        </div>
                                        </div>
            </a>
                                                    </div>

        <!-- Servers -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 mb-3">
            <a href="{{ route('panel.servers.index') }}" class="text-decoration-none">
                <div class="card custom-card dashboard-main-card danger overflow-hidden h-100">
                                        <div class="card-body">
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-fill">
                                <span class="fs-13 fw-medium">Servers</span>
                                <h4 class="fw-semibold my-2 lh-1 counter-number">{{ $stats['servers']['total'] ?? 0 }}</h4>
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="fs-12 d-block text-muted">
                                        <span class="text-success me-1 d-inline-flex align-items-center fw-semibold">
                                            <i class="ri-server-line me-1"></i>{{ $stats['servers']['online'] ?? 0 }} Online
                                                </span>
                                        <span class="text-muted">| {{ $stats['servers']['offline'] ?? 0 }} Offline</span>
                                                </span>
                                            </div>
                                            </div>
                                                <div>
                                <span class="avatar avatar-md bg-danger-transparent svg-danger">
                                    <i class="ri-server-line fs-20"></i>
                                                </span>
                                                </div>
                                                </div>
                                            </div>
                                        </div>
            </a>
                            </div>
                        </div>
                    </div>
                    <!-- End:: row-1 -->

    <br>
                    <!-- Start:: Sales Overview -->
    <div class="row mb-4">
        <div class="col-xxl-4">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">
                        Recent Users
                    </div>
                    <a href="{{ route('panel.users.index') }}" class="text-muted fs-13">View All<i class="ti ti-arrow-narrow-right ms-1"></i></a>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled hrm-employee-list">
                        @forelse($recentUsers ?? [] as $user)
                        <li>
                            <a href="{{ route('panel.users.show', $user->uid) }}" class="text-decoration-none">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <div class="lh-1">
                                        <span class="avatar avatar-md avatar-rounded">
                                            @if($user->avatar)
                                                <img src="{{ $user->secure_avatar_url }}" alt="{{ $user->name }}">
                                            @else
                                                <span class="avatar-initial bg-primary-transparent text-primary">{{ substr($user->name ?? 'U', 0, 1) }}</span>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex-fill">
                                        <span class="d-block fw-semibold">
                                            {{ $user->name ?? 'Unknown User' }}
                                            @if($user->role)
                                                <span class="badge bg-primary-transparent ms-2">{{ ucfirst($user->role) }}</span>
                                            @endif
                                        </span>
                                        <span class="text-muted fs-13">
                                            {{ $user->email ?? 'No email' }}
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <span class="fw-medium">{{ $user->created_at->format('M d, Y') }}</span>
                                        <span class="d-block fs-12 mt-1 text-muted">
                                            Joined
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </li>
                        @empty
                        <li>
                            <div class="text-center py-4">
                                <span class="text-muted">No users found</span>
                            </div>
                        </li>
                        @endforelse
                    </ul>
                    @if(($recentUsers ?? collect())->count() > 0)
                        <div class="text-center mt-3">
                            <a href="{{ route('panel.users.index') }}" class="btn btn-sm btn-outline-primary">View More</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">
                        Sales Overview
                    </div>
                    <div id="sales-overview-legend" class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="legend-dot" style="width: 12px; height: 12px; border-radius: 50%; background-color: #10b981; display: inline-block;"></span>
                            <span class="fs-12 fw-medium">Subscriptions</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="legend-dot" style="width: 12px; height: 12px; border-radius: 50%; background-color: #5d87ff; display: inline-block;"></span>
                            <span class="fs-12 fw-medium">Sales</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="legend-dot" style="width: 12px; height: 12px; border-radius: 50%; background-color: #49beff; display: inline-block;"></span>
                            <span class="fs-12 fw-medium">Revenue</span>
                        </div>
                    </div>
                </div>
                <div class="card-body pb-0 pt-5">
                    <div id="sales-overview"></div>
                </div>
                <div class="card-footer bg-light p-0">
                    <div class="row g-0 w-100">
                        <div class="col-sm-4 border-sm-end">
                            <div class="p-3 text-center">
                                <span class="d-block text-muted mb-1">Total Subscriptions</span>
                                <h6 class="fw-semibold mb-0">{{ number_format($totalSubscriptions ?? 0) }}</h6>
                            </div>
                        </div>
                        <div class="col-sm-4 border-sm-end">
                            <div class="p-3 text-center">
                                <span class="d-block text-muted mb-1">Total Sales</span>
                                <h6 class="fw-semibold mb-0">{{ number_format($totalSales ?? 0) }}</h6>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 text-center">
                                <span class="d-block text-muted mb-1">Revenue Earned</span>
                                <h6 class="fw-semibold mb-0">${{ number_format($totalRevenue ?? 0, 2) }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End:: Sales Overview -->

    <!-- Start:: row-2 - Recent Monitors -->
    <div class="row mb-4">
        <!-- Recent Uptime Monitors -->
        <div class="col-xxl-3 col-xl-6 boxed-col-3">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">
                        Recent Uptime Monitors
                    </div>
                    <a href="{{ route('panel.uptime-monitors.index') }}" class="text-muted fs-12 text-decoration-underline">View All<i class="ri-arrow-right-line"></i></a>
                </div>
                <div class="card-body">
                    @if($recentUptime->count() > 0)
                        <ul class="list-unstyled top-customers-list">
                            @foreach($recentUptime as $monitor)
                                <li>
                                    <a href="{{ route('panel.uptime-monitors.show', $monitor) }}" class="text-decoration-none">
                                        <div class="d-flex align-items-center gap-3 flex-wrap">
                                            <div class="lh-1 flex-shrink-0">
                                                <span class="avatar avatar-md {{ $monitor->status === 'up' ? 'bg-success-transparent' : ($monitor->status === 'down' ? 'bg-danger-transparent' : 'bg-warning-transparent') }}">
                                                    @if(isset($recentUptimeFavicons[$monitor->id]) && $recentUptimeFavicons[$monitor->id])
                                                        <img src="{{ $recentUptimeFavicons[$monitor->id] }}" alt="{{ $monitor->name }}" onerror="this.onerror=null; this.src='{{ asset('build/assets/images/brand-logos/favicon.ico') }}';">
                                                    @else
                                                        <img src="{{ asset('build/assets/images/brand-logos/favicon.ico') }}" alt="{{ $monitor->name }}" onerror="this.style.display='none'; this.parentElement.innerHTML='{{ strtoupper(substr($monitor->name, 0, 2)) }}';">
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="flex-fill min-w-0">
                                                <span class="d-block fw-semibold monitor-name" title="{{ $monitor->name }}">{{ $monitor->name }}</span>
                                                <span class="fs-12 text-muted monitor-url" title="{{ $monitor->url }}">{{ $monitor->url }}</span>
                                            </div>
                                            <div class="text-end flex-shrink-0">
                                                <div class="fw-semibold {{ $monitor->status === 'up' ? 'text-success' : ($monitor->status === 'down' ? 'text-danger' : 'text-warning') }} mb-0">{{ ucfirst($monitor->status) }}</div>
                                                <span class="fs-12 text-muted">Status</span>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="text-center mt-3">
                            <a href="{{ route('panel.uptime-monitors.index') }}" class="btn btn-sm btn-outline-primary">View More</a>
                        </div>
                    @else
                        <div class="p-4 text-center">
                            <p class="text-muted mb-0">No uptime monitors yet</p>
                            <a href="{{ route('panel.uptime-monitors.create') }}" class="btn btn-sm btn-primary mt-2">Create One</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Domain Monitors -->
        <div class="col-xxl-3 col-xl-6 boxed-col-3">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">
                        Recent Domain Monitors
                    </div>
                    <a href="{{ route('panel.domain-monitors.index') }}" class="text-muted fs-12 text-decoration-underline">View All<i class="ri-arrow-right-line"></i></a>
                </div>
                <div class="card-body">
                    @if($recentDomain->count() > 0)
                        <ul class="list-unstyled top-customers-list">
                            @foreach($recentDomain as $monitor)
                                <li>
                                    <a href="{{ route('panel.domain-monitors.show', $monitor) }}" class="text-decoration-none">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="lh-1 flex-shrink-0">
                                                <span class="avatar avatar-md {{ $monitor->days_until_expiration !== null && $monitor->days_until_expiration <= 30 ? 'bg-warning-transparent' : 'bg-success-transparent' }}">
                                                    @if(isset($domainMonitorFavicons[$monitor->id]) && $domainMonitorFavicons[$monitor->id])
                                                        <img src="{{ $domainMonitorFavicons[$monitor->id] }}" alt="{{ $monitor->name }}" onerror="this.onerror=null; this.src='{{ asset('build/assets/images/brand-logos/favicon.ico') }}';">
                                                    @else
                                                        <img src="{{ asset('build/assets/images/brand-logos/favicon.ico') }}" alt="{{ $monitor->name }}" onerror="this.style.display='none'; this.parentElement.innerHTML='{{ strtoupper(substr($monitor->name, 0, 2)) }}';">
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="flex-fill min-w-0">
                                                <span class="d-block fw-semibold monitor-name" title="{{ $monitor->name }}">{{ $monitor->name }}</span>
                                                <span class="fs-12 text-muted monitor-domain" title="{{ $monitor->domain }}">{{ $monitor->domain }}</span>
                                            </div>
                                            <div class="text-end flex-shrink-0">
                                                @if($monitor->days_until_expiration !== null)
                                                    <div class="fw-semibold {{ $monitor->days_until_expiration <= 30 ? 'text-warning' : 'text-success' }} mb-0" style="white-space: nowrap;">{{ $monitor->days_until_expiration }} days</div>
                                                    <span class="fs-12 text-muted" style="white-space: nowrap;">Remaining</span>
                                                @else
                                                    <div class="fw-semibold text-muted mb-0" style="white-space: nowrap;">-</div>
                                                    <span class="fs-12 text-muted" style="white-space: nowrap;">N/A</span>
                                                @endif
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="text-center mt-3">
                            <a href="{{ route('panel.domain-monitors.index') }}" class="btn btn-sm btn-outline-primary">View More</a>
                        </div>
                    @else
                        <div class="p-4 text-center">
                            <p class="text-muted mb-0">No domain monitors yet</p>
                            <a href="{{ route('panel.domain-monitors.create') }}" class="btn btn-sm btn-primary mt-2">Create One</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent SSL Monitors -->
        <div class="col-xxl-3 col-xl-6 boxed-col-3">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">
                        Recent SSL Monitors
                    </div>
                    <a href="{{ route('panel.ssl-monitors.index') }}" class="text-muted fs-12 text-decoration-underline">View All<i class="ri-arrow-right-line"></i></a>
                </div>
                <div class="card-body">
                    @if($recentSSL->count() > 0)
                        <ul class="list-unstyled top-customers-list">
                            @foreach($recentSSL as $monitor)
                                <li>
                                    <a href="{{ route('panel.ssl-monitors.show', $monitor) }}" class="text-decoration-none">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="lh-1 flex-shrink-0">
                                                <span class="avatar avatar-md {{ $monitor->status === 'valid' ? 'bg-success-transparent' : ($monitor->status === 'expired' ? 'bg-danger-transparent' : 'bg-warning-transparent') }}">
                                                    @if(isset($sslMonitorFavicons[$monitor->id]) && $sslMonitorFavicons[$monitor->id])
                                                        <img src="{{ $sslMonitorFavicons[$monitor->id] }}" alt="{{ $monitor->name }}" onerror="this.onerror=null; this.src='{{ asset('build/assets/images/brand-logos/favicon.ico') }}';">
                                                    @else
                                                        <img src="{{ asset('build/assets/images/brand-logos/favicon.ico') }}" alt="{{ $monitor->name }}" onerror="this.style.display='none'; this.parentElement.innerHTML='{{ strtoupper(substr($monitor->name, 0, 2)) }}';">
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="flex-fill min-w-0">
                                                <span class="d-block fw-semibold monitor-name" title="{{ $monitor->name }}">{{ $monitor->name }}</span>
                                                <span class="fs-12 text-muted monitor-domain" title="{{ $monitor->domain }}">{{ $monitor->domain }}</span>
                                            </div>
                                            <div class="text-end flex-shrink-0">
                                                <div class="fw-semibold {{ $monitor->status === 'valid' ? 'text-success' : ($monitor->status === 'expired' ? 'text-danger' : 'text-warning') }} mb-0" style="white-space: nowrap;">{{ ucfirst($monitor->status) }}</div>
                                                <span class="fs-12 text-muted" style="white-space: nowrap;">Status</span>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="text-center mt-3">
                            <a href="{{ route('panel.ssl-monitors.index') }}" class="btn btn-sm btn-outline-primary">View More</a>
                        </div>
                    @else
                        <div class="p-4 text-center">
                            <p class="text-muted mb-0">No SSL monitors yet</p>
                            <a href="{{ route('panel.ssl-monitors.create') }}" class="btn btn-sm btn-primary mt-2">Create One</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent DNS Monitors -->
        <div class="col-xxl-3 col-xl-6 boxed-col-3">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">
                        Recent DNS Monitors
                    </div>
                    <a href="{{ route('panel.dns-monitors.index') }}" class="text-muted fs-12 text-decoration-underline">View All<i class="ri-arrow-right-line"></i></a>
                </div>
                <div class="card-body">
                    @if($recentDNS->count() > 0)
                        <ul class="list-unstyled top-customers-list">
                            @foreach($recentDNS as $monitor)
                                <li>
                                    <a href="{{ route('panel.dns-monitors.show', $monitor) }}" class="text-decoration-none">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="lh-1 flex-shrink-0">
                                                <span class="avatar avatar-md {{ $monitor->status === 'healthy' ? 'bg-success-transparent' : 'bg-danger-transparent' }}">
                                                    @if(isset($dnsMonitorFavicons[$monitor->id]) && $dnsMonitorFavicons[$monitor->id])
                                                        <img src="{{ $dnsMonitorFavicons[$monitor->id] }}" alt="{{ $monitor->name }}" onerror="this.onerror=null; this.src='{{ asset('build/assets/images/brand-logos/favicon.ico') }}';">
                                                    @else
                                                        <img src="{{ asset('build/assets/images/brand-logos/favicon.ico') }}" alt="{{ $monitor->name }}" onerror="this.style.display='none'; this.parentElement.innerHTML='{{ strtoupper(substr($monitor->name, 0, 2)) }}';">
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="flex-fill min-w-0">
                                                <span class="d-block fw-semibold monitor-name" title="{{ $monitor->name }}">{{ $monitor->name }}</span>
                                                <span class="fs-12 text-muted monitor-domain" title="{{ $monitor->domain }}">{{ $monitor->domain }}</span>
                                            </div>
                                            <div class="text-end flex-shrink-0">
                                                <div class="fw-semibold {{ $monitor->status === 'healthy' ? 'text-success' : 'text-danger' }} mb-0" style="white-space: nowrap;">{{ ucfirst($monitor->status) }}</div>
                                                <span class="fs-12 text-muted" style="white-space: nowrap;">Status</span>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="text-center mt-3">
                            <a href="{{ route('panel.dns-monitors.index') }}" class="btn btn-sm btn-outline-primary">View More</a>
                        </div>
                    @else
                        <div class="p-4 text-center">
                            <p class="text-muted mb-0">No DNS monitors yet</p>
                            <a href="{{ route('panel.dns-monitors.create') }}" class="btn btn-sm btn-primary mt-2">Create One</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- End:: row-2 -->    

    <!-- Start:: row-3 - More Recent Monitors -->
    <div class="row mb-4">
        <!-- Recent API Monitors -->
        <div class="col-xxl-3 col-xl-6 boxed-col-3">
            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">
                        Recent API Monitors
                                    </div>
                    <a href="{{ route('panel.api-monitors.index') }}" class="text-muted fs-12 text-decoration-underline">View All<i class="ri-arrow-right-line"></i></a>
                                        </div> 
                <div class="card-body">
                    @if($recentAPI->count() > 0)
                        <ul class="list-unstyled top-customers-list">
                            @foreach($recentAPI as $monitor)
                                <li>
                                    <a href="{{ route('panel.api-monitors.show', $monitor) }}" class="text-decoration-none">
                                        <div class="d-flex align-items-center gap-2">
                                        <div class="lh-1 flex-shrink-0">
                                            <span class="avatar avatar-md {{ $monitor->status === 'up' ? 'bg-success-transparent' : 'bg-danger-transparent' }}">
                                                @if(isset($apiMonitorFavicons[$monitor->id]) && $apiMonitorFavicons[$monitor->id])
                                                    <img src="{{ $apiMonitorFavicons[$monitor->id] }}" alt="{{ $monitor->name }}" onerror="this.onerror=null; this.src='{{ asset('build/assets/images/brand-logos/favicon.ico') }}';">
                                                @else
                                                    <img src="{{ asset('build/assets/images/brand-logos/favicon.ico') }}" alt="{{ $monitor->name }}" onerror="this.style.display='none'; this.parentElement.innerHTML='{{ strtoupper(substr($monitor->name, 0, 2)) }}';">
                                                @endif
                                            </span>
                                        </div>
                                        <div class="flex-fill min-w-0">
                                            <span class="d-block fw-semibold monitor-name" title="{{ $monitor->name }}">{{ $monitor->name }}</span>
                                            <span class="fs-12 text-muted monitor-url" title="{{ $monitor->url }}">{{ $monitor->url }}</span>
                                        </div>
                                        <div class="text-end flex-shrink-0">
                                            <div class="fw-semibold {{ $monitor->status === 'up' ? 'text-success' : 'text-danger' }} mb-0" style="white-space: nowrap;">{{ ucfirst($monitor->status) }}</div>
                                            <span class="fs-12 text-muted" style="white-space: nowrap;">Status</span>
                                        </div>
                                    </div>
                                                        </a>
                                                    </li>
                            @endforeach
                                                </ul>
                        <div class="text-center mt-3">
                            <a href="{{ route('panel.api-monitors.index') }}" class="btn btn-sm btn-outline-primary">View More</a>
                                        </div> 
                    @else
                        <div class="p-4 text-center">
                            <p class="text-muted mb-0">No API monitors yet</p>
                            <a href="{{ route('panel.api-monitors.create') }}" class="btn btn-sm btn-primary mt-2">Create One</a>
                                    </div> 
                    @endif
                                </div>
                            </div>
                        </div>

        <!-- Recent Servers -->
        <div class="col-xxl-3 col-xl-6 boxed-col-3">
            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">
                        Recent Servers
                                    </div>
                    <a href="{{ route('panel.servers.index') }}" class="text-muted fs-12 text-decoration-underline">View All<i class="ri-arrow-right-line"></i></a>
                                    </div>
                <div class="card-body">
                    @if($recentServers->count() > 0)
                        <ul class="list-unstyled top-customers-list">
                            @foreach($recentServers as $server)
                                @php
                                    // Use pre-calculated status from controller (uses ServerStatusService with server's thresholds)
                                    $serverStatus = $server->calculated_status ?? $server->getStatus();
                                    // Try to get favicon from hostname if available
                                    $serverFavicon = null;
                                    if ($server->hostname) {
                                        $domain = strtolower(trim($server->hostname));
                                        $serverFavicon = "https://www.google.com/s2/favicons?domain={$domain}&sz=32";
                                    }
                                @endphp
                                <li>
                                    <a href="{{ route('panel.servers.show', $server) }}" class="text-decoration-none">
                                        <div class="d-flex align-items-center gap-2">
                                        <div class="lh-1 flex-shrink-0">
                                            <span class="avatar avatar-md {{ $serverStatus['status'] === 'online' ? 'bg-success-transparent' : ($serverStatus['status'] === 'offline' ? 'bg-danger-transparent' : 'bg-warning-transparent') }}">
                                                @if($serverFavicon)
                                                    <img src="{{ $serverFavicon }}" alt="{{ $server->name }}" onerror="this.onerror=null; this.src='{{ asset('build/assets/images/brand-logos/favicon.ico') }}';">
                                                @else
                                                    <img src="{{ asset('build/assets/images/brand-logos/favicon.ico') }}" alt="{{ $server->name }}" onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'ri-server-line\'></i>';">
                                                @endif
                                            </span>
                                        </div>
                                        <div class="flex-fill min-w-0">
                                            <span class="d-block fw-semibold monitor-name" title="{{ $server->name }}">{{ $server->name }}</span>
                                            <span class="fs-12 text-muted monitor-hostname" title="{{ $server->hostname ?? 'N/A' }}">{{ $server->hostname ?? 'N/A' }}</span>
                                        </div>
                                        <div class="text-end flex-shrink-0">
                                            <div class="fw-semibold {{ $serverStatus['status'] === 'online' ? 'text-success' : ($serverStatus['status'] === 'offline' ? 'text-danger' : 'text-warning') }} mb-0" style="white-space: nowrap;">{{ ucfirst($serverStatus['status']) }}</div>
                                            <span class="fs-12 text-muted" style="white-space: nowrap;">Status</span>
                                        </div>
                                    </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="text-center mt-3">
                            <a href="{{ route('panel.servers.index') }}" class="btn btn-sm btn-outline-primary">View More</a>
                                                        </div>
                    @else
                        <div class="p-4 text-center">
                            <p class="text-muted mb-0">No servers yet</p>
                            <a href="{{ route('panel.servers.create') }}" class="btn btn-sm btn-primary mt-2">Add Server</a>
                                                            </div>
                    @endif
                                                        </div>
                                                        </div>
                        </div>
                    </div>
                    <!-- End:: row-3 -->

@endsection

@section('scripts')
<!-- Swiper JS -->
<script src="{{asset('build/assets/libs/swiper/swiper-bundle.min.js')}}"></script>
<!-- ApexCharts JS -->
        <script src="{{asset('build/assets/libs/apexcharts/apexcharts.min.js')}}"></script>

<script>
    // Sparkline data (pre-loaded from server)
    const sparklineData = @json($uptimeMonitorSparklines ?? []);
    
    // Initialize Sparkline Charts (lightweight, only for visible slides)
    function initializeSparklines() {
        @foreach($sliderUptimeMonitors as $monitor)
            @if(isset($uptimeMonitorSparklines[$monitor->id]))
                const data{{ $monitor->id }} = @json($uptimeMonitorSparklines[$monitor->id]);
                const element{{ $monitor->id }} = document.getElementById('sparkline-{{ $monitor->id }}');
                
                if (element{{ $monitor->id }} && data{{ $monitor->id }}.length > 0) {
                    // Destroy existing chart if it exists
                    if (window.sparkChart{{ $monitor->id }}) {
                        window.sparkChart{{ $monitor->id }}.destroy();
                    }
                    
                    const color = '{{ $monitor->status === "up" ? "#10b981" : ($monitor->status === "down" ? "#ef4444" : "#f59e0b") }}';
                    
                    // Calculate dynamic min/max based on data for better visualization
                    const dataArray{{ $monitor->id }} = data{{ $monitor->id }};
                    const minVal{{ $monitor->id }} = Math.min(...dataArray{{ $monitor->id }}) * 0.9;
                    const maxVal{{ $monitor->id }} = Math.max(...dataArray{{ $monitor->id }}) * 1.1;
                    const yMin{{ $monitor->id }} = Math.max(0, minVal{{ $monitor->id }});
                    const yMax{{ $monitor->id }} = Math.min(100, Math.max(100, maxVal{{ $monitor->id }}));
                    
                    window.sparkChart{{ $monitor->id }} = new ApexCharts(element{{ $monitor->id }}, {
                        series: [{ 
                            name: 'Status',
                            data: data{{ $monitor->id }}
                        }],
                        chart: {
                            type: 'area',
                            height: 35,
                            sparkline: { enabled: true },
                            toolbar: { show: false },
                            animations: {
                                enabled: true,
                                easing: 'easeinout',
                                speed: 800
                            }
                        },
                        stroke: { 
                            curve: 'smooth', 
                            width: 2, 
                            colors: [color],
                            lineCap: 'round'
                        },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                opacityFrom: 0.4,
                                opacityTo: 0.1,
                                stops: [0, 50, 100],
                                colorStops: [
                                    { offset: 0, color: color, opacity: 0.4 },
                                    { offset: 50, color: color, opacity: 0.2 },
                                    { offset: 100, color: color, opacity: 0.05 }
                                ]
                            }
                        },
                        colors: [color],
                        xaxis: { 
                            labels: { show: false }, 
                            axisBorder: { show: false }, 
                            axisTicks: { show: false },
                            type: 'numeric'
                        },
                        yaxis: { 
                            min: yMin{{ $monitor->id }},
                            max: yMax{{ $monitor->id }},
                            labels: { show: false }
                        },
                        grid: { 
                            show: false, 
                            padding: { top: 0, right: 0, bottom: 0, left: 0 } 
                        },
                        tooltip: { 
                            enabled: false 
                        },
                        markers: { 
                            size: 0,
                            hover: { size: 0 }
                        },
                        dataLabels: {
                            enabled: false
                        }
                    });
                    
                    window.sparkChart{{ $monitor->id }}.render();
                }
            @endif
        @endforeach
    }
    
    // Initialize Uptime Monitors Swiper
    @if($sliderUptimeMonitors->count() > 0)
    var uptimeMonitorsSwiper = new Swiper('.uptime-monitors-swiper', {
        slidesPerView: 1,
        spaceBetween: 20,
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
        loop: {{ $sliderUptimeMonitors->count() > 3 ? 'true' : 'false' }},
        breakpoints: {
            640: {
                slidesPerView: 2,
                spaceBetween: 20,
            },
            768: {
                slidesPerView: 3,
                spaceBetween: 20,
            },
            1024: {
                slidesPerView: 4,
                spaceBetween: 20,
            },
            1280: {
                slidesPerView: 5,
                spaceBetween: 20,
            },
        },
        on: {
            init: function() {
                setTimeout(initializeSparklines, 200);
            },
            slideChange: function() {
                setTimeout(initializeSparklines, 100);
            }
        }
    });
    @endif

    // Professional Dashboard Enhancements
    
    // Refresh Button Handler
    const refreshBtn = document.getElementById('refresh-dashboard');
    const lastUpdated = document.getElementById('last-updated');
    const updateTime = document.getElementById('update-time');
    
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            this.classList.add('refreshing');
            lastUpdated.style.display = 'none';
            
            // Reload after animation
            setTimeout(() => {
                window.location.reload();
            }, 500);
        });
    }
    
    // Sales Overview Chart
    const salesChartDates = @json($salesChartDates ?? []);
    const salesChartCounts = @json($salesChartCounts ?? []);
    const salesChartRevenue = @json($salesChartRevenue ?? []);
    const subscriptionsChartCounts = @json($subscriptionsChartCounts ?? []);
    
    let salesOverviewChart;
    
    function initSalesOverviewChart(period = 'month') {
        const chartElement = document.getElementById('sales-overview');
        if (!chartElement) return;
        
        // Destroy existing chart if it exists
        if (salesOverviewChart) {
            salesOverviewChart.destroy();
        }
        
        // Prepare data based on period
        let dates = salesChartDates;
        let sales = salesChartCounts;
        let revenue = salesChartRevenue;
        let subscriptions = subscriptionsChartCounts;
        
        // For now, we'll use the 30-day data. In a full implementation, 
        // you'd fetch different data based on the period via AJAX
        
        const options = {
            series: [
                {
                    name: 'Sales',
                    type: 'column',
                    data: sales,
                    color: '#5d87ff'
                },
                {
                    name: 'Revenue',
                    type: 'line',
                    data: revenue,
                    color: '#49beff'
                },
                {
                    name: 'Subscriptions',
                    type: 'area',
                    data: subscriptions,
                    color: '#10b981'
                }
            ],
            chart: {
                height: 350,
                type: 'line',
                toolbar: {
                    show: false
                },
                zoom: {
                    enabled: false
                }
            },
            stroke: {
                width: [0, 3, 2],
                curve: 'smooth'
            },
            plotOptions: {
                bar: {
                    columnWidth: '50%',
                    borderRadius: 4
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: 'vertical',
                    shadeIntensity: 0.3,
                    gradientToColors: ['#49beff', '#10b981'],
                    inverseColors: false,
                    opacityFrom: 0.8,
                    opacityTo: 0.1,
                    stops: [0, 100]
                }
            },
            labels: dates,
            markers: {
                size: [0, 4, 0],
                hover: {
                    size: [0, 6, 0]
                }
            },
            xaxis: {
                type: 'category',
                labels: {
                    rotate: -45,
                    rotateAlways: false,
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: [
                {
                    title: {
                        text: 'Count',
                        style: {
                            color: '#5d87ff'
                        }
                    },
                    labels: {
                        style: {
                            colors: '#5d87ff'
                        }
                    }
                },
                {
                    opposite: true,
                    title: {
                        text: 'Revenue ($)',
                        style: {
                            color: '#49beff'
                        }
                    },
                    labels: {
                        style: {
                            colors: '#49beff'
                        },
                        formatter: function(val) {
                            return '$' + val.toFixed(0);
                        }
                    }
                }
            ],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(y, { seriesIndex }) {
                        if (seriesIndex === 1) {
                            return '$' + y.toFixed(2);
                        }
                        return y;
                    }
                }
            },
            legend: {
                show: false
            },
            grid: {
                borderColor: '#f1f1f1',
                strokeDashArray: 3
            }
        };
        
        salesOverviewChart = new ApexCharts(chartElement, options);
        salesOverviewChart.render();
    }
    
    // Initialize Sales Overview Chart
    if (document.getElementById('sales-overview')) {
        initSalesOverviewChart('month');
    }
    
    // Period button handlers removed - buttons are no longer displayed
    
    // Update Last Updated Time
    function updateLastUpdatedTime() {
        if (lastUpdated && updateTime) {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit'
            });
            updateTime.textContent = timeString;
            lastUpdated.style.display = 'inline-flex';
        }
    }
    
    // Show last updated time on load
    updateLastUpdatedTime();
    
    // Animate numbers on load
    function animateNumbers() {
        const counters = document.querySelectorAll('.counter-number');
        counters.forEach(counter => {
            const target = parseInt(counter.textContent);
            if (isNaN(target)) return;
            
            let current = 0;
            const increment = target / 30;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    counter.textContent = target;
                    clearInterval(timer);
                } else {
                    counter.textContent = Math.floor(current);
                }
            }, 30);
        });
    }
    
    // Run animations after page load
    window.addEventListener('load', () => {
        setTimeout(animateNumbers, 300);
        setTimeout(initializeSparklines, 400);
    });
    
    // Auto-refresh dashboard every 60 seconds with visual feedback
    let refreshInterval = setInterval(() => {
        if (refreshBtn) {
            refreshBtn.classList.add('refreshing');
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }
    }, 60000);
    
    // Add pulse animation to status indicators
    document.querySelectorAll('.status-indicator').forEach(el => {
        el.classList.add('pulse');
    });
    
    // Enhanced hover effects for cards
    document.querySelectorAll('.dashboard-main-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-6px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href.startsWith('#')) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
</script>
@endsection
