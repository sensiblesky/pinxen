@extends('layouts.master')

@section('styles')

@endsection

@section('content')
	
                    <!-- Start::page-header -->
                    <div class="page-header-breadcrumb mb-3">
                        <div class="d-flex align-center justify-content-between flex-wrap">
                            <h1 class="page-title fw-medium fs-18 mb-0">Faq's</h1>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Faq's</li>
                            </ol>
                        </div>
                    </div>
                    <!-- End::page-header -->

                    <!-- Start:: row-1 -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card custom-card faq-banner-card">
                                <div class="card-body text-center">
                                    <div class="faq-banner-background">
                                        <img src="{{asset('build/assets/images/media/media-65.png')}}" alt="">
                                    </div>
                                    <h3 class="fw-semibold text-primary">Frequently Asked Questions</h3>
                                    <span class="d-block text-muted mb-4">Find quick answers to common queries about using the admin panel.</span>
                                    <div class="row justify-content-center">
                                        <div class="col-xl-6">
                                            <input type="text" class="form-control form-control-lg border-0 shadow-none" id="faq-search-input" placeholder="Search Faq's">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End:: row-1 -->

                    <!-- Start:: row-2 -->
                    <div class="row">
                        <div class="col-xl-3">
                            <div class="card custom-card">
                                <div class="card-body">
                                    <ul class="nav nav-tabs flex-column nav-tabs-header faq-nav mb-0 d-sm-flex d-inline-block" role="tablist" id="category-tabs">
                                        @foreach($categories as $index => $category)
                                            <li class="nav-item m-1" role="presentation">
                                                <a class="nav-link {{ $index === 0 ? 'active' : '' }}" 
                                                   data-bs-toggle="tab" 
                                                   role="tab" 
                                                   href="#faq-{{ Str::slug($category) }}" 
                                                   aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                                    @php
                                                        // Get icon for category from first FAQ in category
                                                        $categoryIcon = $faqsByCategory[$category]->first()->icon ?? 'ri-question-line';
                                                    @endphp
                                                    <i class="{{ $categoryIcon }} me-2 fs-16"></i>{{ $category }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-9 my-auto">
                            <div class="row mb-4">
                                <div class="col-xl-12">
                                    <div class="tab-content" id="faq-tab-content">
                                        @foreach($categories as $index => $category)
                                            @php
                                                $categorySlug = Str::slug($category);
                                                $accordionId = 'accordionFAQ' . ($index + 1);
                                            @endphp
                                            <div class="tab-pane {{ $index === 0 ? 'show active' : '' }} border-0 p-0" 
                                                 id="faq-{{ $categorySlug }}" 
                                                 role="tabpanel">
                                                <div class="accordion faq-accordion accordions-items-seperate" id="{{ $accordionId }}">
                                                    @foreach($faqsByCategory[$category] as $faqIndex => $faq)
                                                        @php
                                                            $headingId = 'heading' . $categorySlug . $faqIndex . $faq->id;
                                                            $collapseId = 'collapse' . $categorySlug . $faqIndex . $faq->id;
                                                        @endphp
                                                        <div class="accordion-item faq-item" data-question="{{ strtolower($faq->question) }}" data-answer="{{ strtolower($faq->answer) }}">
                                                            <h2 class="accordion-header" id="{{ $headingId }}">
                                                                <button class="accordion-button {{ $faqIndex === 0 ? '' : 'collapsed' }}" 
                                                                        type="button" 
                                                                        data-bs-toggle="collapse"
                                                                        data-bs-target="#{{ $collapseId }}" 
                                                                        aria-expanded="{{ $faqIndex === 0 ? 'true' : 'false' }}"
                                                                        aria-controls="{{ $collapseId }}">
                                                                    <i class="{{ $faq->icon ?? 'ri-question-line' }} fw-medium avatar avatar-sm avatar-rounded bg-primary-transparent fs-5 me-2 text-primary flex-shrink-0"></i>{{ $faq->question }}
                                                                </button>
                                                            </h2>
                                                            <div id="{{ $collapseId }}" 
                                                                 class="accordion-collapse collapse {{ $faqIndex === 0 ? 'show' : '' }}"
                                                                 aria-labelledby="{{ $headingId }}"
                                                                 data-bs-parent="#{{ $accordionId }}">
                                                                <div class="accordion-body">
                                                                    {!! nl2br(e($faq->answer)) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End:: row-2 -->

                    <!-- Start:: row-3 -->
                    <div class="row">
                        <div class="col-xl-3">
                            <div class="card custom-card bg-primary-transparent border-0">
                                <div class="card-body p-0 text-center">
                                    <img src="{{asset('build/assets/images/media/media-74.png')}}" alt="" class="img-fluid">
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-9">
                            <h5 class="fw-semibold">Still Have Questions? Post them here!</h5>
                            <div class="card custom-card">
                                <div class="card-body">
                                    <div class="row gy-2 mb-3">  
                                        <div class="col-xl-6"> 
                                            <label for="user-name" class="form-label">Your Name</label> 
                                            <input type="text" class="form-control" id="user-name" placeholder="Enter Your Name"> 
                                        </div> 
                                        <div class="col-xl-6"> 
                                            <label for="user-email" class="form-label">Email Id</label> 
                                            <input type="text" class="form-control" id="user-email" placeholder="Enter Email" value="{{ auth()->user()->email ?? '' }}"> 
                                        </div> 
                                        <div class="col-xl-12"> 
                                            <label for="text-area" class="form-label">Post Your Question</label> 
                                            <textarea class="form-control" placeholder="Enter your question here" id="text-area" rows="2"></textarea> 
                                        </div> 
                                    </div>
                                    <a href="mailto:{{ $supportEmail }}" class="btn btn-success float-end">
                                        Send<i class="ri-send-plane-2-line ms-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End:: row-3 -->

@endsection

@section('scripts')
    <script>
        // FAQ Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('faq-search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase().trim();
                    const faqItems = document.querySelectorAll('.faq-item');
                    
                    if (searchTerm === '') {
                        // Show all FAQs
                        faqItems.forEach(item => {
                            item.style.display = '';
                        });
                        // Show all category tabs
                        document.querySelectorAll('.tab-pane').forEach(tab => {
                            tab.style.display = '';
                        });
                    } else {
                        // Filter FAQs
                        faqItems.forEach(item => {
                            const question = item.getAttribute('data-question') || '';
                            const answer = item.getAttribute('data-answer') || '';
                            
                            if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                                item.style.display = '';
                            } else {
                                item.style.display = 'none';
                            }
                        });
                        
                        // Show/hide category tabs based on results
                        document.querySelectorAll('.tab-pane').forEach(tab => {
                            const visibleItems = tab.querySelectorAll('.faq-item[style=""]').length;
                            if (visibleItems > 0) {
                                tab.style.display = '';
                            } else {
                                tab.style.display = 'none';
                            }
                        });
                    }
                });
            }

            // Ensure all accordion buttons work properly
            // Sometimes Bootstrap needs a moment to initialize
            setTimeout(function() {
                const accordionButtons = document.querySelectorAll('.accordion-button');
                accordionButtons.forEach(button => {
                    button.addEventListener('click', function(e) {
                        // Let Bootstrap handle the collapse, but ensure it works
                        const targetId = this.getAttribute('data-bs-target');
                        if (targetId) {
                            const targetElement = document.querySelector(targetId);
                            if (targetElement) {
                                // Bootstrap should handle this automatically
                                // But we can ensure the element exists
                            }
                        }
                    });
                });
            }, 100);
        });
    </script>
@endsection
