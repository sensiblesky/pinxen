			
			<header class="app-header sticky" id="header">

				<!-- Start::main-header-container -->
				<div class="main-header-container container-fluid">

					<!-- Start::header-content-left -->
					<div class="header-content-left">

						<!-- Start::header-element -->
						<div class="header-element">
							<div class="horizontal-logo">
								<a href="{{url('index')}}" class="header-logo">
									<img src="{{asset('build/assets/images/brand-logos/desktop-logo.png')}}" alt="logo" class="desktop-logo">
									<img src="{{asset('build/assets/images/brand-logos/toggle-logo.png')}}" alt="logo" class="toggle-logo">
									<img src="{{asset('build/assets/images/brand-logos/desktop-dark.png')}}" alt="logo" class="desktop-dark">
									<img src="{{asset('build/assets/images/brand-logos/toggle-dark.png')}}" alt="logo" class="toggle-dark">
								</a>
							</div>
						</div>
						<!-- End::header-element -->

						<!-- Start::header-element -->
						<div class="header-element mx-lg-0 mx-2">
							<a aria-label="Hide Sidebar" class="sidemenu-toggle header-link animated-arrow hor-toggle horizontal-navtoggle" data-bs-toggle="sidebar" href="javascript:void(0);"><span></span></a>
						</div>
						<!-- End::header-element -->

						<div class="header-element header-search d-md-block d-none">
							<!-- Start::header-link -->
							<input type="text" class="header-search-bar form-control bg-white" id="header-search" placeholder="Search" spellcheck=false autocomplete="off" autocapitalize="off">
							<a href="javascript:void(0);" class="header-search-icon border-0">
								<i class="bi bi-search fs-12"></i>
							</a>
							<!-- End::header-link -->
						</div>

					</div>
					<!-- End::header-content-left -->

					<!-- Start::header-content-right -->
					<ul class="header-content-right">

						<!-- Start::header-element -->
						<li class="header-element d-md-none d-block">
							<a href="javascript:void(0);" class="header-link" data-bs-toggle="modal" data-bs-target="#header-responsive-search">
								<!-- Start::header-link-icon -->
								<svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="112" cy="112" r="80" opacity="0.2"/><circle cx="112" cy="112" r="80" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="168.57" y1="168.57" x2="224" y2="224" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								<!-- End::header-link-icon -->
							</a>  
						</li>
						<!-- End::header-element -->

						<!-- Start::header-element -->
						<li class="header-element country-selector dropdown d-sm-block d-none">
							<!-- Start::header-link|dropdown-toggle -->
							<a href="javascript:void(0);" class="header-link dropdown-toggle" data-bs-auto-close="outside" data-bs-toggle="dropdown">
								<svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M215,168.71a96.42,96.42,0,0,1-30.54,37l-9.36-9.37a8,8,0,0,0-3.63-2.09L150,188.59a8,8,0,0,1-5.88-8.9l2.38-16.2a8,8,0,0,1,4.85-6.22l30.45-12.66a8,8,0,0,1,8.47,1.49Z" opacity="0.2"/><path d="M184,74a8,8,0,0,1-1.94,5.22L159.89,105a8,8,0,0,1-5,2.71l-31.46,4.26a8.06,8.06,0,0,1-5.77-1.45l-19.81-13a8,8,0,0,0-11.34,2l-20.94,31.3a8.06,8.06,0,0,0-1.35,4.41L64,171.49a8,8,0,0,1-3.61,6.64l-9.92,6.52A96,96,0,0,1,184,50Z" opacity="0.2"/><circle cx="128" cy="128" r="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M184.42,205.68l-9.36-9.37a8,8,0,0,0-3.63-2.09L150,188.59a8,8,0,0,1-5.88-8.9l2.38-16.2a8,8,0,0,1,4.85-6.22l30.45-12.66a8,8,0,0,1,8.47,1.49L215,168.71" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M50.49,184.65l9.92-6.52A8,8,0,0,0,64,171.49l.21-36.23a8.06,8.06,0,0,1,1.35-4.41l20.94-31.3a8,8,0,0,1,11.34-2l19.81,13a8.06,8.06,0,0,0,5.77,1.45l31.46-4.26a8,8,0,0,0,5-2.71L182.06,79.2A8,8,0,0,0,184,74V50" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
							</a>
							<!-- End::header-link|dropdown-toggle -->
							<ul class="main-header-dropdown dropdown-menu dropdown-menu-end" data-popper-placement="none">
								<li>
									<a class="dropdown-item d-flex align-items-center" href="javascript:void(0);">
										<span class="avatar avatar-rounded avatar-xs lh-1 me-2">
											<img src="{{asset('build/assets/images/flags/us_flag.jpg')}}" alt="img">
										</span>
										English
									</a>
								</li>
								<li>
									<a class="dropdown-item d-flex align-items-center" href="javascript:void(0);">
										<span class="avatar avatar-rounded avatar-xs lh-1 me-2">
											<img src="{{asset('build/assets/images/flags/spain_flag.jpg')}}" alt="img" >
										</span>
										español
									</a>
								</li>
								<li>
									<a class="dropdown-item d-flex align-items-center" href="javascript:void(0);">
										<span class="avatar avatar-rounded avatar-xs lh-1 me-2">
											<img src="{{asset('build/assets/images/flags/french_flag.jpg')}}" alt="img" >
										</span>
										français
									</a>
								</li>
								<li>
									<a class="dropdown-item d-flex align-items-center" href="javascript:void(0);">
										<span class="avatar avatar-rounded avatar-xs lh-1 me-2">
											<img src="{{asset('build/assets/images/flags/uae_flag.jpg')}}" alt="img" >
										</span>
										عربي
									</a>
								</li>
								<li>
									<a class="dropdown-item d-flex align-items-center" href="javascript:void(0);">
										<span class="avatar avatar-rounded avatar-xs lh-1 me-2">
											<img src="{{asset('build/assets/images/flags/germany_flag.jpg')}}" alt="img" >
										</span>
										Deutsch
									</a>
								</li>
								<li>
									<a class="dropdown-item d-flex align-items-center" href="javascript:void(0);">
										<span class="avatar avatar-rounded avatar-xs lh-1 me-2">
											<img src="{{asset('build/assets/images/flags/china_flag.jpg')}}" alt="img" >
										</span>
										中国人
									</a>
								</li>
								<li>
									<a class="dropdown-item d-flex align-items-center" href="javascript:void(0);">
										<span class="avatar avatar-rounded avatar-xs lh-1 me-2">
											<img src="{{asset('build/assets/images/flags/italy_flag.jpg')}}" alt="img" >
										</span>
										Italiano
									</a>
								</li>
								<li>
									<a class="dropdown-item d-flex align-items-center" href="javascript:void(0);">
										<span class="avatar avatar-rounded avatar-xs lh-1 me-2">
											<img src="{{asset('build/assets/images/flags/russia_flag.jpg')}}" alt="img" >
										</span>
										Русский
									</a>
								</li>
							</ul>
						</li>
						<!-- End::header-element -->

						<!-- Start::header-element -->
						<li class="header-element header-theme-mode">
							<!-- Start::header-link|layout-setting -->
							<a href="javascript:void(0);" class="header-link layout-setting">
								<span class="light-layout">
									<!-- Start::header-link-icon -->
									<svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M108.11,28.11A96.09,96.09,0,0,0,227.89,147.89,96,96,0,1,1,108.11,28.11Z" opacity="0.2"/><path d="M108.11,28.11A96.09,96.09,0,0,0,227.89,147.89,96,96,0,1,1,108.11,28.11Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
									<!-- End::header-link-icon -->
								</span>
								<span class="dark-layout">
									<!-- Start::header-link-icon -->
									<svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="128" r="56" opacity="0.2"/><line x1="128" y1="40" x2="128" y2="32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="128" r="56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="64" y1="64" x2="56" y2="56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="64" y1="192" x2="56" y2="200" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="192" y1="64" x2="200" y2="56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="192" y1="192" x2="200" y2="200" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="40" y1="128" x2="32" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="216" x2="128" y2="224" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="216" y1="128" x2="224" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
									<!-- End::header-link-icon -->
								</span>
							</a>
							<!-- End::header-link|layout-setting -->
						</li>
						<!-- End::header-element -->

						<!-- Start::header-element -->
						<li class="header-element notifications-dropdown d-xl-block d-none dropdown">
							<!-- Start::header-link|dropdown-toggle -->
							<a href="javascript:void(0);" class="header-link dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" id="messageDropdown" aria-expanded="false">
								<svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M56,104a72,72,0,0,1,144,0c0,35.82,8.3,64.6,14.9,76A8,8,0,0,1,208,192H48a8,8,0,0,1-6.88-12C47.71,168.6,56,139.81,56,104Z" opacity="0.2"/><path d="M96,192a32,32,0,0,0,64,0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M56,104a72,72,0,0,1,144,0c0,35.82,8.3,64.6,14.9,76A8,8,0,0,1,208,192H48a8,8,0,0,1-6.88-12C47.71,168.6,56,139.81,56,104Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								<span class="header-icon-pulse bg-secondary rounded pulse pulse-secondary"></span>
							</a>
							<!-- End::header-link|dropdown-toggle -->
							<!-- Start::main-header-dropdown -->
							<div class="main-header-dropdown dropdown-menu dropdown-menu-end" data-popper-placement="none">
								<div class="p-3 bg-primary text-fixed-white">
									<div class="d-flex align-items-center justify-content-between">
										<p class="mb-0 fs-16">Notifications</p>
										<a href="javascript:void(0);" class="badge bg-light text-default border">Clear All</a>
									</div>
								</div>
								<div class="dropdown-divider"></div>
								<ul class="list-unstyled mb-0" id="header-notification-scroll">
									<li class="dropdown-item position-relative">
										<a href="{{url('chat')}}" class="stretched-link"></a>
										<div class="d-flex align-items-start gap-3">
											<div class="lh-1">
												<span class="avatar avatar-sm avatar-rounded bg-primary-transparent">
													<img src="{{asset('build/assets/images/faces/1.jpg')}}" alt="">
												</span>
											</div>
											<div class="flex-fill">
												<span class="d-block fw-semibold">New Message</span>
												<span class="d-block text-muted fs-12">You have received a new message from John Doe</span>
											</div>
											<div class="text-end">
												<span class="d-block mb-1 fs-12 text-muted">11:45am</span>
												<span class="d-block text-primary d-none"><i class="ri-circle-fill fs-9"></i></span>
											</div>
										</div>
									</li>
									<li class="dropdown-item position-relative">
										<a href="{{url('chat')}}" class="stretched-link"></a>
										<div class="d-flex align-items-start gap-3">
											<div class="lh-1">
												<span class="avatar avatar-sm avatar-rounded bg-primary-transparent">
													<i class="ri-notification-line fs-16"></i>
												</span>
											</div>
											<div class="flex-fill">
												<span class="d-block fw-semibold">Task Reminder</span>
												<span class="d-block text-muted fs-12">Don't forget to submit your report by 3 PM today</span>
											</div>
											<div class="text-end">
												<span class="d-block mb-1 fs-12 text-muted">02:16pm</span>
												<span class="d-block text-primary d-none"><i class="ri-circle-fill fs-9"></i></span>
											</div>
										</div>
									</li>
									<li class="dropdown-item position-relative">
										<a href="{{url('chat')}}" class="stretched-link"></a>
										<div class="d-flex align-items-start gap-3">
											<div class="lh-1">
												<span class="avatar avatar-sm avatar-rounded bg-primary-transparent fs-5">
													<img src="{{asset('build/assets/images/faces/12.jpg')}}" alt="">
												</span>
											</div>
											<div class="flex-fill">
												<span class="d-block fw-semibold">Friend Request</span>
												<span class="d-block text-muted fs-12">Jane Smith sent you a friend request</span>
											</div>
											<div class="text-end">
												<span class="d-block mb-1 fs-12 text-muted">10:04am</span>
												<span class="d-block text-primary"><i class="ri-circle-fill fs-9"></i></span>
											</div>
										</div>
									</li>
									<li class="dropdown-item position-relative">
										<a href="{{url('chat')}}" class="stretched-link"></a>
										<div class="d-flex align-items-start gap-3">
											<div class="lh-1">
												<span class="avatar avatar-sm avatar-rounded bg-primary-transparent fs-5">
													<i class="ri-notification-line fs-16"></i>
												</span>
											</div>
											<div class="flex-fill">
												<span class="d-block fw-semibold">Event Reminder</span>
												<span class="d-block text-muted fs-12">You have an upcoming event: Team Meeting on October 25 at 10 AM.</span>
											</div>
											<div class="text-end">
												<span class="d-block mb-1 fs-12 text-muted">12:58pm</span>
												<span class="d-block text-primary"><i class="ri-circle-fill fs-9"></i></span>
											</div>
										</div>
									</li>
									<li class="dropdown-item position-relative">
										<a href="{{url('chat')}}" class="stretched-link"></a>
										<div class="d-flex align-items-start gap-3">
											<div class="lh-1">
												<span class="avatar avatar-sm avatar-rounded bg-primary-transparent fs-5">
													<i class="ri-notification-line fs-16"></i>
												</span>
											</div>
											<div class="flex-fill">
												<span class="d-block fw-semibold">File Uploaded</span>
												<span class="d-block text-muted fs-12">The file "Project_Proposal.pdf" has been uploaded successfully</span>
											</div>
											<div class="text-end">
												<span class="d-block mb-1 fs-12 text-muted">05:13pm</span>
												<span class="d-block text-primary"><i class="ri-circle-fill fs-9"></i></span>
											</div>
										</div>
									</li>
								</ul>
								<div class="p-5 empty-item1 d-none">
									<div class="text-center">
										<span class="avatar avatar-xl avatar-rounded bg-secondary-transparent">
											<i class="ri-notification-off-line fs-2"></i>
										</span>
										<h6 class="fw-medium mt-3">No New Notifications</h6>
									</div>
								</div>
							</div>
							<!-- End::main-header-dropdown -->
						</li>
						<!-- End::header-element -->

						<!-- Start::header-element -->
						<li class="header-element header-fullscreen">
							<!-- Start::header-link -->
							<a onclick="openFullscreen();" href="javascript:void(0);" class="header-link">
								<svg xmlns="http://www.w3.org/2000/svg" class="full-screen-open header-link-icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><rect x="48" y="48" width="160" height="160" opacity="0.2"/><polyline points="168 48 208 48 208 88" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="88 208 48 208 48 168" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="208 168 208 208 168 208" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="48 88 48 48 88 48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								<svg xmlns="http://www.w3.org/2000/svg" class="full-screen-close header-link-icon d-none" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><rect x="32" y="32" width="192" height="192" rx="16" opacity="0.2"/><polyline points="160 48 208 48 208 96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="144" y1="112" x2="208" y2="48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="96 208 48 208 48 160" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="112" y1="144" x2="48" y2="208" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
							</a>
							<!-- End::header-link -->
						</li>
						<!-- End::header-element -->

						<!-- Start::header-element -->
						<li class="header-element dropdown">
							<!-- Start::header-link|dropdown-toggle -->
							<a href="javascript:void(0);" class="header-link dropdown-toggle" id="mainHeaderProfile" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
								<div>
									@auth
										@if(auth()->user()->avatar)
											<img src="{{ auth()->user()->secure_avatar_url }}" alt="img" class="header-link-icon">
										@else
											<img src="{{asset('build/assets/images/faces/12.jpg')}}" alt="img" class="header-link-icon">
										@endif
									@else
										<img src="{{asset('build/assets/images/faces/12.jpg')}}" alt="img" class="header-link-icon">
									@endauth
								</div>
							</a>
							<!-- End::header-link|dropdown-toggle -->
							<!-- Start::main-header-dropdown -->
							<div class="main-header-dropdown dropdown-menu dropdown-menu-end" data-popper-placement="none">
								<div class="p-3 bg-primary text-fixed-white">
									<div class="d-flex align-items-center justify-content-between">
										<p class="mb-0 fs-16">Profile<span class="badge bg-warning ms-1 fs-12 rounded-circle" id="profile-badge">@auth{{ auth()->user()->role == 1 ? 'Admin' : 'User' }}@endauth</span></p>
										<a href="@auth{{ route('panel.users.show', auth()->user()->uid) }}@else{{url('profile')}}@endauth" class="text-fixed-white text-decoration-underline fs-12">View Profile <i class="ti ti-arrow-narrow-right"></i></a>
									</div>
								</div>
								<div class="dropdown-divider"></div>
								<ul class="list-unstyled mb-0" id="header-profile-items-scroll">
									<li class="dropdown-item">
										<div class="d-flex align-items-start profile-dropdown-item gap-3">
											<div class="lh-1">
												<span class="avatar avatar-xl bg-gray-300">
													@auth
														@if(auth()->user()->avatar)
															<img src="{{ auth()->user()->secure_avatar_url }}" alt="img">
														@else
															<span class="avatar-initial">{{ substr(auth()->user()->name ?? 'U', 0, 1) }}</span>
														@endif
													@else
														<img src="{{asset('build/assets/images/faces/12.jpg')}}" alt="img">
													@endauth
												</span>
											</div>
											<div class="flex-fill w-75">
												<div class="d-flex align-items-start justify-content-between mb-3">
													<div class="fs-14 fw-medium w-75">
														<div class="text-truncate">
															<a href="@auth{{ route('panel.users.show', auth()->user()->uid) }}@else{{url('profile')}}@endauth">@auth{{ auth()->user()->name ?? 'User' }}@else Guest @endauth</a>
														</div>
														<div class="fs-11 text-muted text-truncate">
															<span>@auth{{ auth()->user()->email ?? 'No email' }}@else Not logged in @endauth</span>
														</div>
													</div>
													<div class="text-end">
														<a href="@auth{{ route('panel.users.show', auth()->user()->uid) }}@else{{url('profile')}}@endauth" class="header-profile-edit dropdown-item-close"><i class="ri-edit-line"></i></a>
													</div>
												</div>
												<div class="d-flex align-items-center justify-content-between">
													<div class="flex-fill">
														<div class="lh-1 fs-12 mb-1">
															<span class="text-muted fw-normal">Role:</span><span class="text-primary ms-1">@auth{{ auth()->user()->role == 1 ? 'Administrator' : 'User' }}@else Guest @endauth</span>
														</div>
														<h6 class="fw-medium mb-0">@auth{{ auth()->user()->phone ?? 'No phone' }}@else - @endauth</h6>
													</div>
													<div class="d-flex rounded align-items-center flex-nowrap gap-2"> 
														<a href="@auth{{ route('panel.users.show', auth()->user()->uid) }}@else{{url('profile')}}@endauth" class="badge bg-primary p-1 border-0 text-white fs-13">
															<i class="ri-user-settings-line"></i> 
														</a> 
													</div>
												</div>
											</div>
										</div>
									</li>
									<li class="dropdown-item">
										<div class="d-flex align-items-start profile-dropdown-item gap-3">
											<div class="lh-1">
												<span class="avatar avatar-xl bg-primary-transparent">
													<i class="ri-key-line fs-20 text-primary"></i>
												</span>
											</div>
											<div class="flex-fill w-75">
												<div class="d-flex align-items-start justify-content-between mb-3">
													<div class="fs-14 fw-medium w-75">
														<div class="text-truncate">
															<a href="{{ route('account.security.password') }}">Change Password</a>
														</div>
														<div class="fs-11 text-muted text-truncate">
															<span>Update your account password</span>
														</div>
													</div>
													<div class="text-end">
														<a href="{{ route('account.security.password') }}" class="header-profile-link"><i class="ri-arrow-right-line"></i></a>
													</div>
												</div>
											</div>
										</div>
									</li>
									<li class="dropdown-item">
										<div class="d-flex align-items-start profile-dropdown-item gap-3">
											<div class="lh-1">
												<span class="avatar avatar-xl bg-success-transparent">
													<i class="ri-shield-check-line fs-20 text-success"></i>
												</span>
											</div>
											<div class="flex-fill w-75">
												<div class="d-flex align-items-start justify-content-between mb-3">
													<div class="fs-14 fw-medium w-75">
														<div class="text-truncate">
															<a href="{{ route('account.security.two-factor') }}">Two-Factor Authentication</a>
														</div>
														<div class="fs-11 text-muted text-truncate">
															<span>Enable or disable 2FA for your account</span>
														</div>
													</div>
													<div class="text-end">
														<a href="{{ route('account.security.two-factor') }}" class="header-profile-link"><i class="ri-arrow-right-line"></i></a>
													</div>
												</div>
											</div>
										</div>
									</li>
									<li class="dropdown-item">
										<div class="d-flex align-items-start profile-dropdown-item gap-3">
											<div class="lh-1">
												<span class="avatar avatar-xl bg-info-transparent">
													<i class="ri-lifebuoy-line fs-20 text-info"></i>
												</span>
											</div>
											<div class="flex-fill w-75">
												<div class="d-flex align-items-start justify-content-between mb-3">
													<div class="fs-14 fw-medium w-75">
														<div class="text-truncate">
															<a href="{{ route('support.index') }}">Support & Help</a>
														</div>
														<div class="fs-11 text-muted text-truncate">
															<span>Get help and contact support</span>
														</div>
													</div>
													<div class="text-end">
														<a href="{{ route('support.index') }}" class="header-profile-link"><i class="ri-arrow-right-line"></i></a>
													</div>
												</div>
											</div>
										</div>
									</li>
								</ul>
								<div class="p-3 empty-header-item border-top">
									<div class="text-center d-grid">
										@auth
											<form method="POST" action="{{ route('logout') }}" class="d-inline">
												@csrf
												<button type="submit" class="btn btn-danger btn-wave w-100">
													<i class="ri-logout-box-line me-1"></i>Log Out
												</button>
											</form>
										@else
											<a href="{{url('sign-in-cover')}}" class="btn btn-primary btn-wave">Sign In</a>
										@endauth
									</div>
								</div>
								<div class="p-5 empty-item d-none">
									<div class="text-center">
										<span class="avatar avatar-xl avatar-rounded bg-success-transparent">
											<i class="ti ti-user fs-2"></i>
										</span>
										<h6 class="fw-medium mb-1 mt-3">No Profile Information</h6>
										<span class="mb-3 fw-normal fs-13 d-block">Please update your profile to see details here! :)</span>
									</div>
								</div>
							</div>
							<!-- End::main-header-dropdown -->
						</li>  
						<!-- End::header-element -->

					</ul>
					<!-- End::header-content-right -->

				</div>
				<!-- End::main-header-container -->

			</header>
					