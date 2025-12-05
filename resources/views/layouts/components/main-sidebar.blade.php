
			<aside class="app-sidebar sticky" id="sidebar">

				<!-- Start::main-sidebar-header -->
				<div class="main-sidebar-header">
					<a href="{{url('index')}}" class="header-logo">
						<img src="{{asset('build/assets/images/brand-logos/desktop-logo.png')}}" alt="logo" class="desktop-logo">
						<img src="{{asset('build/assets/images/brand-logos/toggle-dark.png')}}" alt="logo" class="toggle-dark">
						<img src="{{asset('build/assets/images/brand-logos/desktop-dark.png')}}" alt="logo" class="desktop-dark">
						<img src="{{asset('build/assets/images/brand-logos/toggle-logo.png')}}" alt="logo" class="toggle-logo">
					</a>
				</div>
				<!-- End::main-sidebar-header -->

				<!-- Start::main-sidebar -->
				<div class="main-sidebar" id="sidebar-scroll">

					<!-- Start::nav -->
					<nav class="main-menu-container nav nav-pills flex-column sub-open">
						<div class="slide-left" id="slide-left">
							<svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path> </svg>
						</div>
						<ul class="main-menu">
							@auth
								@php
									$user = Auth::user();
									$isAdmin = $user && $user->role == 1;
									$isClient = $user && $user->role == 2;
									
									// Check if any specific menu item is active
									$hasActiveMenu = request()->is('dashboard') || 
													 request()->is('subscriptions*') ||
													 (request()->is('panel') && !request()->is('panel/*')) ||
													 request()->is('panel/users*') ||
													 request()->is('panel/subscription-plans*') ||
													 request()->is('panel/plan-features*') ||
													 request()->is('panel/subscribers*') ||
													 request()->is('panel/reports*') ||
													 request()->is('panel/system-configuration*') ||
													 request()->is('panel/comm-channels*') ||
													 request()->is('panel/payment-gateway*') ||
													 request()->is('panel/auth-sso*') ||
													 request()->is('panel/recaptcha*') ||
													 request()->is('panel/storage*') ||
													 request()->is('panel/cache-management*') ||
													 request()->is('panel/faqs*') ||
													 request()->is('panel/api-management*') ||
													 request()->is('profile*') ||
													 request()->is('account/security*');
									
									// If no menu is active, default to first menu (Home/Dashboard)
									$shouldDefaultToFirst = !$hasActiveMenu;
								@endphp

								<!-- Start::slide__category - Client Menu (Visible to All Users) -->
								<li class="slide__category"><span class="category-name">Client Menu</span></li>
								<!-- End::slide__category -->

								@if($isClient)
									<!-- Start::slide - Client Dashboards (Visible Only to Clients) -->
									@php
										$isClientDashboardActive = request()->is('dashboard') || $shouldDefaultToFirst;
									@endphp
									<li class="slide has-sub {{ $isClientDashboardActive ? 'active open' : '' }}">
										<a href="javascript:void(0);" class="side-menu__item">
											<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M133.66,34.34a8,8,0,0,0-11.32,0L40,116.69V216h64V152h48v64h64V116.69Z" opacity="0.2"/><line x1="16" y1="216" x2="240" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="152 216 152 152 104 152 104 216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="40" y1="116.69" x2="40" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="216" y1="216" x2="216" y2="116.69" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M24,132.69l98.34-98.35a8,8,0,0,1,11.32,0L232,132.69" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
											<span class="side-menu__label">Home</span>
											<i class="ri-arrow-right-s-line side-menu__angle"></i>
										</a>
										<ul class="slide-menu child1">
											<li class="slide side-menu__label1">
												<a href="javascript:void(0)">Home</a>
											</li>
											<li class="slide {{ request()->is('dashboard') || $shouldDefaultToFirst ? 'active' : '' }}">
												<a href="{{ route('dashboard') }}" class="side-menu__item">
													<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M133.66,34.34a8,8,0,0,0-11.32,0L40,116.69V216h64V152h48v64h64V116.69Z" opacity="0.2"/><line x1="16" y1="216" x2="240" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="152 216 152 152 104 152 104 216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="40" y1="116.69" x2="40" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="216" y1="216" x2="216" y2="116.69" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M24,132.69l98.34-98.35a8,8,0,0,1,11.32,0L232,132.69" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
													Dashboard</a>
											</li>
										</ul>
									</li>
									<!-- End::slide - Client Dashboards -->

								@endif

								<!-- Start::slide__category - Admin Panel (Visible Only to Admins) -->
								@if($isAdmin)
									<li class="slide__category"><span class="category-name">Admin Panel</span></li>
									<!-- End::slide__category -->

									<!-- Start::slide - Admin Dashboards -->
									@php
										$isHomeSubmenuActive = (request()->is('panel') && !request()->is('panel/*')) || request()->is('panel/users*') || $shouldDefaultToFirst;
									@endphp
									<li class="slide has-sub {{ $isHomeSubmenuActive ? 'active open' : '' }}">
										<a href="javascript:void(0);" class="side-menu__item">
											<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M133.66,34.34a8,8,0,0,0-11.32,0L40,116.69V216h64V152h48v64h64V116.69Z" opacity="0.2"/><line x1="16" y1="216" x2="240" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="152 216 152 152 104 152 104 216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="40" y1="116.69" x2="40" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="216" y1="216" x2="216" y2="116.69" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M24,132.69l98.34-98.35a8,8,0,0,1,11.32,0L232,132.69" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
											<span class="side-menu__label">Home</span>
											<i class="ri-arrow-right-s-line side-menu__angle"></i>
										</a>
										<ul class="slide-menu child1">
											<li class="slide side-menu__label1">
												<a href="javascript:void(0)">Home</a>
											</li>
											<li class="slide {{ (request()->is('panel') && !request()->is('panel/*')) || $shouldDefaultToFirst ? 'active' : '' }}">
												<a href="{{ route('panel') }}" class="side-menu__item">
													<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M133.66,34.34a8,8,0,0,0-11.32,0L40,116.69V216h64V152h48v64h64V116.69Z" opacity="0.2"/><line x1="16" y1="216" x2="240" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="152 216 152 152 104 152 104 216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="40" y1="116.69" x2="40" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="216" y1="216" x2="216" y2="116.69" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M24,132.69l98.34-98.35a8,8,0,0,1,11.32,0L232,132.69" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
													Dashboard</a>
											</li>
											<li class="slide {{ request()->is('panel/users*') ? 'active' : '' }}">
												<a href="{{ route('panel.users.index') }}" class="side-menu__item">
													<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,32A96,96,0,0,0,63.8,199.38h0A72,72,0,0,1,128,160a40,40,0,1,1,40-40,40,40,0,0,1-40,40,72,72,0,0,1,64.2,39.37A96,96,0,0,0,128,32Z" opacity="0.2"/><path d="M63.8,199.37a72,72,0,0,1,128.4,0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="128" r="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="120" r="40" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
													Users Management</a>
											</li>
										</ul>
									</li>
									<!-- End::slide - Admin Dashboards -->

									<!-- Start::slide__category -->
									<li class="slide__category"><span class="category-name">Subscription</span></li>
									<!-- End::slide__category -->

									<!-- Start::slide - Subscription Management -->
									@php
										$isSubscriptionActive = request()->is('panel/subscription-plans*') || 
																request()->is('panel/plan-features*') ||
																request()->is('panel/subscribers*');
									@endphp
									<li class="slide has-sub {{ $isSubscriptionActive ? 'active open' : '' }}">
										<a href="javascript:void(0);" class="side-menu__item">
											<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z" opacity="0.2"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M128,80a48,48,0,0,1,48,48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M128,80a48,48,0,0,0-48,48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M176,128a48,48,0,0,1-48,48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M80,128a48,48,0,0,1,48,48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
											<span class="side-menu__label">Subscription</span>
											<i class="ri-arrow-right-s-line side-menu__angle"></i>
										</a>
										<ul class="slide-menu child1">
											<li class="slide side-menu__label1">
												<a href="javascript:void(0)">Subscription</a>
											</li>
											<li class="slide {{ request()->is('panel/subscription-plans*') ? 'active' : '' }}">
												<a href="{{ route('panel.subscription-plans.index') }}" class="side-menu__item">
													<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z" opacity="0.2"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M128,80a48,48,0,0,1,48,48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M128,80a48,48,0,0,0-48,48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M176,128a48,48,0,0,1-48,48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M80,128a48,48,0,0,1,48,48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
													Plans
												</a>
											</li>
											<li class="slide {{ request()->is('panel/plan-features*') ? 'active' : '' }}">
												<a href="{{ route('panel.plan-features.index') }}" class="side-menu__item">
													<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z" opacity="0.2"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="128 72 128 128 184 128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
													Features
												</a>
											</li>
											<li class="slide {{ request()->is('panel/subscribers*') ? 'active' : '' }}">
												<a href="{{ route('panel.subscribers.index') }}" class="side-menu__item">
													<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="96" r="64" opacity="0.2"/><circle cx="128" cy="96" r="64" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M32,216c0-48.6,37.4-88,83.3-88h25.4c45.9,0,83.3,39.4,83.3,88" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
													Subscribers
												</a>
											</li>
										</ul>
									</li>
									<!-- End::slide - Subscription Management -->

									<!-- Start::slide__category - Reports -->
									<li class="slide__category"><span class="category-name">Reports</span></li>
									<!-- End::slide__category -->

									<!-- Start::slide - Reports -->
									@php
										$isReportsActive = request()->is('panel/reports*');
									@endphp
									<li class="slide has-sub {{ $isReportsActive ? 'active open' : '' }}">
										<a href="javascript:void(0);" class="side-menu__item">
											<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M48,48H208a8,8,0,0,1,8,8V200a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V56A8,8,0,0,1,48,48Z" opacity="0.2"/><path d="M48,48H208a8,8,0,0,1,8,8V200a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V56A8,8,0,0,1,48,48Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="88" y1="88" x2="168" y2="88" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="88" y1="128" x2="168" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="88" y1="168" x2="136" y2="168" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
											<span class="side-menu__label">Reports</span>
											<i class="ri-arrow-right-s-line side-menu__angle"></i>
										</a>
										<ul class="slide-menu child1">
											<li class="slide side-menu__label1">
												<a href="javascript:void(0)">Reports</a>
											</li>
											<li class="slide {{ request()->is('panel/reports/subscriptions*') ? 'active' : '' }}">
												<a href="{{ route('panel.reports.subscriptions') }}" class="side-menu__item">
													<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z" opacity="0.2"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M128,80a48,48,0,0,1,48,48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M128,80a48,48,0,0,0-48,48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M176,128a48,48,0,0,1-48,48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M80,128a48,48,0,0,1,48,48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
													Subscription
												</a>
											</li>
											<li class="slide {{ request()->is('panel/reports/users*') ? 'active' : '' }}">
												<a href="{{ route('panel.reports.users') }}" class="side-menu__item">
													<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="96" r="64" opacity="0.2"/><path d="M32,216c0-48.6,39.4-88,88-88s88,39.4,88,88" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="96" r="64" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
													Users
												</a>
											</li>
										</ul>
									</li>
									<!-- End::slide - Reports -->

									<!-- Start::slide__category -->
									<li class="slide__category"><span class="category-name">System Configuration</span></li>
									<!-- End::slide__category -->

									<!-- Start::slide -->
									@php
										$isSystemConfigActive = request()->is('panel/system-configuration*') || 
																request()->is('panel/comm-channels*') || 
																request()->is('panel/payment-gateway*') || 
																request()->is('panel/auth-sso*') || 
																request()->is('panel/recaptcha*') || 
																request()->is('panel/storage*') || 
																request()->is('panel/cache-management*') || 
																request()->is('panel/faqs*');
									@endphp
									<li class="slide has-sub {{ $isSystemConfigActive ? 'active open' : '' }}">
										<a href="javascript:void(0);" class="side-menu__item">
											<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="128" r="96" opacity="0.2"/><circle cx="128" cy="128" r="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="128" r="32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="96" x2="128" y2="32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="160" y1="128" x2="224" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="160" x2="128" y2="224" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="96" y1="128" x2="32" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
											<span class="side-menu__label">System Configuration</span>
											<i class="ri-arrow-right-s-line side-menu__angle"></i>
										</a>
										<ul class="slide-menu child1">
											<li class="slide side-menu__label1">
												<a href="javascript:void(0)">System Configuration</a>
											</li>
											<li class="slide {{ request()->is('panel/system-configuration*') ? 'active' : '' }}">
												<a href="{{ route('panel.system-configuration.index') }}" class="side-menu__item">
													<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="128" r="96" opacity="0.2"/><circle cx="128" cy="128" r="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="128" r="32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="96" x2="128" y2="32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="160" y1="128" x2="224" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="160" x2="128" y2="224" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="96" y1="128" x2="32" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
													General Configuration
												</a>
											</li>
											<li class="slide {{ request()->is('panel/comm-channels*') ? 'active' : '' }}">
												<a href="{{ route('panel.comm-channels.index') }}" class="side-menu__item">
													<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M32,48H224a8,8,0,0,1,8,8V200a8,8,0,0,1-8,8H32a8,8,0,0,1-8-8V56A8,8,0,0,1,32,48Z" opacity="0.2"/><path d="M32,48H224a8,8,0,0,1,8,8V200a8,8,0,0,1-8,8H32a8,8,0,0,1-8-8V56A8,8,0,0,1,32,48Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="224 56 128 144 32 56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
													Comm Channels
												</a>
											</li>
											<li class="slide {{ request()->is('panel/payment-gateway*') ? 'active' : '' }}">
												<a href="{{ route('panel.payment-gateway.index') }}" class="side-menu__item">
													<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><rect x="24" y="56" width="208" height="144" rx="8" opacity="0.2"/><rect x="24" y="56" width="208" height="144" rx="8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="56" y1="128" x2="200" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="56" y1="96" x2="200" y2="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="56" y1="160" x2="200" y2="160" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
													Payment Gateway
												</a>
											</li>
											<li class="slide {{ request()->is('panel/auth-sso*') ? 'active' : '' }}">
												<a href="{{ route('panel.auth-sso.index') }}" class="side-menu__item">
													<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><rect x="40" y="88" width="176" height="128" rx="8" opacity="0.2"/><rect x="40" y="88" width="176" height="128" rx="8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M92,88V52a36,36,0,0,1,72,0V88" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="152" r="12" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
													Auth & Single Sign On
												</a>
											</li>
											<li class="slide {{ request()->is('panel/recaptcha*') ? 'active' : '' }}">
												<a href="{{ route('panel.recaptcha.index') }}" class="side-menu__item">
													<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,24A104,104,0,0,0,24,128a104,104,0,0,0,104,104,104,104,0,0,0,104-104A104,104,0,0,0,128,24Z" opacity="0.2"/><path d="M128,24A104,104,0,0,0,24,128a104,104,0,0,0,104,104,104,104,0,0,0,104-104A104,104,0,0,0,128,24Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M176,128a48,48,0,0,1-48,48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M128,80a48,48,0,0,1,48,48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="128" x2="128" y2="176" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
													Recaptcha
												</a>
											</li>
											<li class="slide {{ request()->is('panel/storage*') ? 'active' : '' }}">
												<a href="{{ route('panel.storage.index') }}" class="side-menu__item">
													<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><rect x="32" y="48" width="192" height="160" rx="8" opacity="0.2"/><rect x="32" y="48" width="192" height="160" rx="8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="32" y1="88" x2="224" y2="88" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="80" y1="48" x2="80" y2="88" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
													Storage
												</a>
											</li>
											<li class="slide {{ request()->is('panel/cache-management*') ? 'active' : '' }}">
												<a href="{{ route('panel.cache-management.index') }}" class="side-menu__item">
													<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,32a96,96,0,1,0,96,96A96,96,0,0,0,128,32Z" opacity="0.2"/><circle cx="128" cy="128" r="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="128 72 128 128 184 128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
													Cache Management
												</a>
											</li>
											<li class="slide {{ request()->is('panel/faqs*') ? 'active' : '' }}">
												<a href="{{ route('panel.faqs.index') }}" class="side-menu__item">
													<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M168,224a8,8,0,0,1-8,8H96a8,8,0,0,1-8-8V136H40a8,8,0,0,1-7.4-4.9l-32-64a8,8,0,0,1,0-6.2l32-64A8,8,0,0,1,40,0H216a8,8,0,0,1,7.4,4.9l32,64a8,8,0,0,1,0,6.2l-32,64A8,8,0,0,1,216,136H168Z" opacity="0.2"/><path d="M168,224a8,8,0,0,1-8,8H96a8,8,0,0,1-8-8V136H40a8,8,0,0,1-7.4-4.9l-32-64a8,8,0,0,1,0-6.2l32-64A8,8,0,0,1,40,0H216a8,8,0,0,1,7.4,4.9l32,64a8,8,0,0,1,0,6.2l-32,64A8,8,0,0,1,216,136H168Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
													FAQ Management
												</a>
											</li>
										</ul>
									</li>
									<!-- End::slide -->

								<!-- Start::slide__category -->
								<li class="slide__category"><span class="category-name">Developer Option</span></li>
								<!-- End::slide__category -->

								<!-- Start::slide -->
								@php
									$isApiManagementActive = request()->is('panel/api-management*');
								@endphp
								<li class="slide has-sub {{ $isApiManagementActive ? 'active open' : '' }}">
									<a href="javascript:void(0);" class="side-menu__item">
										<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M48,48H208a8,8,0,0,1,8,8V200a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V56A8,8,0,0,1,48,48Z" opacity="0.2"/><path d="M48,48H208a8,8,0,0,1,8,8V200a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V56A8,8,0,0,1,48,48Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="88" y1="88" x2="168" y2="88" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="88" y1="128" x2="168" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="88" y1="168" x2="136" y2="168" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
										<span class="side-menu__label">API Management</span>
										<i class="ri-arrow-right-s-line side-menu__angle"></i>
									</a>
									<ul class="slide-menu child1">
										<li class="slide side-menu__label1">
											<a href="javascript:void(0)">API Management</a>
										</li>
										<li class="slide {{ request()->is('panel/api-management*') && !request()->is('panel/api-management/create*') && !request()->is('panel/api-management/users*') && !request()->is('panel/api-management/usage*') && !request()->is('panel/api-management/scopes*') && !request()->is('panel/api-management/revoked*') ? 'active' : '' }}">
											<a href="javascript:void(0);" class="side-menu__item">
												<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M48,48H208a8,8,0,0,1,8,8V200a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V56A8,8,0,0,1,48,48Z" opacity="0.2"/><path d="M48,48H208a8,8,0,0,1,8,8V200a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V56A8,8,0,0,1,48,48Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="88" y1="88" x2="168" y2="88" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="88" y1="128" x2="168" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="88" y1="168" x2="136" y2="168" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
												My APIs
											</a>
										</li>
										<li class="slide {{ request()->is('panel/api-management/create*') ? 'active' : '' }}">
											<a href="javascript:void(0);" class="side-menu__item">
												<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="128" r="96" opacity="0.2"/><circle cx="128" cy="128" r="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="32" x2="128" y2="224" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="224" y1="128" x2="32" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
												Create API
											</a>
										</li>
										<li class="slide {{ request()->is('panel/api-management/users*') ? 'active' : '' }}">
											<a href="javascript:void(0);" class="side-menu__item">
												<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="96" r="64" opacity="0.2"/><path d="M32,216c0-48.6,39.4-88,88-88s88,39.4,88,88" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="96" r="64" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
												Users APIs
											</a>
										</li>
										<li class="slide {{ request()->is('panel/api-management/usage*') ? 'active' : '' }}">
											<a href="javascript:void(0);" class="side-menu__item">
												<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,32a96,96,0,1,0,96,96A96,96,0,0,0,128,32Z" opacity="0.2"/><circle cx="128" cy="128" r="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="128 72 128 128 184 128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
												API Usage
											</a>
										</li>
										<li class="slide {{ request()->is('panel/api-management/scopes*') ? 'active' : '' }}">
											<a href="javascript:void(0);" class="side-menu__item">
												<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="128" r="96" opacity="0.2"/><circle cx="128" cy="128" r="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="128" r="32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="96" x2="128" y2="32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="160" y1="128" x2="224" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="160" x2="128" y2="224" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="96" y1="128" x2="32" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
												API Scopes
											</a>
										</li>
										<li class="slide {{ request()->is('panel/api-management/revoked*') ? 'active' : '' }}">
											<a href="javascript:void(0);" class="side-menu__item">
												<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="128" r="96" opacity="0.2"/><circle cx="128" cy="128" r="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="160" y1="96" x2="96" y2="160" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="160" y1="160" x2="96" y2="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
												Revoked APIs
											</a>
										</li>
									</ul>
								</li>
								<!-- End::slide -->
								@endif

								<!-- Start::slide__category - Account Management (Visible to All Users) -->
								<li class="slide__category"><span class="category-name">Account Management</span></li>
								<!-- End::slide__category -->

							<!-- Start::slide -->
							@php
								$isAccountSettingsActive = request()->is('profile*') || 
															request()->is('account/security/password*') || 
															request()->is('account/security/two-factor*') ||
															request()->is('subscriptions*');
							@endphp
							<li class="slide has-sub {{ $isAccountSettingsActive ? 'active open' : '' }}">
								<a href="javascript:void(0);" class="side-menu__item">
									<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="96" r="64" opacity="0.2"/><path d="M32,216c0-48.6,39.4-88,88-88s88,39.4,88,88" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="96" r="64" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
									<span class="side-menu__label">Account Settings</span>
									<i class="ri-arrow-right-s-line side-menu__angle"></i>
								</a>
								<ul class="slide-menu child1">
									<li class="slide side-menu__label1">
										<a href="javascript:void(0)">Account Settings</a>
									</li>
									<li class="slide {{ request()->is('profile*') ? 'active' : '' }}">
										<a href="{{ route('profile.edit') }}" class="side-menu__item">
											<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="120" r="40" opacity="0.2"/><path d="M32,216a92,92,0,0,1,192,0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="120" r="40" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
											My Profile
										</a>
									</li>
									<li class="slide {{ request()->is('account/security/password*') ? 'active' : '' }}">
										<a href="{{ route('account.security.password') }}" class="side-menu__item">
											<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><rect x="40" y="88" width="176" height="128" rx="8" opacity="0.2"/><rect x="40" y="88" width="176" height="128" rx="8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M92,88V52a36,36,0,0,1,72,0V88" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="152" r="12" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
											Change Password
										</a>
									</li>
									<li class="slide {{ request()->is('account/security/two-factor*') ? 'active' : '' }}">
										<a href="{{ route('account.security.two-factor') }}" class="side-menu__item">
											<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><rect x="40" y="88" width="176" height="128" rx="8" opacity="0.2"/><rect x="40" y="88" width="176" height="128" rx="8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M92,88V52a36,36,0,0,1,72,0V88" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="152" r="12" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
											2FA
										</a>
									</li>
									@if(auth()->user()->role == 2)
									<li class="slide {{ request()->is('subscriptions') && !request()->is('subscriptions/*') ? 'active' : '' }}">
										<a href="{{ route('subscriptions.index') }}" class="side-menu__item">
											<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M56,48H200a8,8,0,0,1,8,8V200a8,8,0,0,1-8,8H56a8,8,0,0,1-8-8V56A8,8,0,0,1,56,48Z" opacity="0.2"/><rect x="32" y="48" width="192" height="160" rx="8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="32" y1="96" x2="224" y2="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="32" y1="160" x2="224" y2="160" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
											Pricing Plans
										</a>
									</li>
									<li class="slide {{ request()->is('subscriptions/my-subscription') ? 'active' : '' }}">
										<a href="{{ route('subscriptions.show') }}" class="side-menu__item">
											<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M56,48H200a8,8,0,0,1,8,8V200a8,8,0,0,1-8,8H56a8,8,0,0,1-8-8V56A8,8,0,0,1,56,48Z" opacity="0.2"/><rect x="32" y="48" width="192" height="160" rx="8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="32" y1="96" x2="224" y2="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="32" y1="160" x2="224" y2="160" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
											My Subscription
										</a>
									</li>
									@endif
								</ul>
							</li>
							<!-- End::slide -->
							@endauth

						<li>
							<ul class="slide-menu child1 doublemenu_slide-menu">
								<li class="text-center p-3 text-fixed-white">
									<div class="doublemenu_slide-menu-background">
										<img src="{{asset('build/assets/images/media/backgrounds/13.png')}}" alt="">
									</div>
									<div class="d-flex flex-column align-items-center justify-content-between h-100">
										<div class="fs-15 fw-medium">Dashboard AI Helper</div>
										<div>
											<span class="avatar avatar-lg p-1">
												<img src="{{asset('build/assets/images/media/media-80.png')}}" alt="">
												<span class="top-right"></span>
												<span class="bottom-right"></span>
											</span>
										</div>
										<div class="d-grid w-100">
											<button class="btn btn-light border-0">Try Now</button>
										</div>
									</div>
								</li>
							</ul>
						</li>
						</ul>
						
						<div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path> </svg></div>
					</nav>
					<!-- End::nav -->

				</div>
				<!-- End::main-sidebar -->

			</aside>