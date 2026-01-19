<header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- LOGO for horizontal-->
                <div class="navbar-brand-box horizontal-logo">
                    <a href="#" class="logo logo-dark">
                        <span class="logo-sm">
                            <img src="{{ asset('assets/images/mini-logo.png') }}" alt="Mini Logo" height="50">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ asset('assets/images/login-logo.png') }}" alt="Main Logo" height="60">
                        </span>
                    </a>

                    <a href="#" class="logo logo-light">
                        <span class="logo-sm">
                            <img src="{{ asset('assets/images/mini-logo.png') }}" alt="Mini Logo" height="50">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ asset('assets/images/login-logo.png') }}" alt="Main Logo" height="60">
                        </span>
                    </a>
                </div>
                <div class="ms-1 header-item d-none d-sm-flex align-items-center gap-1">
                    <i class="bi bi-building text-muted"></i>
                    <span class="fw-semibold">Consultancy HRIMS</span>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <!-- Fullscreen Toggle -->
                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button"
                        class="btn btn-icon btn-topbar material-shadow-none btn-ghost-secondary rounded-circle"
                        id="toggle-fullscreen">
                        <i class='bx bx-fullscreen fs-22'></i>
                    </button>
                </div>

                <!-- Dark/Light Mode Toggle -->
                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button"
                        class="btn btn-icon btn-topbar material-shadow-none btn-ghost-secondary rounded-circle light-dark-mode"
                        id="light-dark-mode">
                        <i class='bx bx-moon fs-22'></i>
                    </button>
                </div>

                <!-- Notifications -->
                <div class="dropdown topbar-head-dropdown ms-1 header-item" id="notificationDropdown">
                    <button type="button"
                        class="btn btn-icon btn-topbar material-shadow-none btn-ghost-secondary rounded-circle"
                        id="page-header-notifications-dropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                        aria-haspopup="true" aria-expanded="false">
                        <i class='bx bx-bell fs-22'></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                        aria-labelledby="page-header-notifications-dropdown">
                        <div class="dropdown-head bg-primary bg-pattern rounded-top">
                            <div class="p-3">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="m-0 fs-16 fw-semibold text-white">Notifications</h6>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-content position-relative" id="notificationItemsTabContent">
                            <div class="tab-pane fade show active py-2 ps-2" id="all-noti-tab" role="tabpanel">
                                <div data-simplebar style="max-height: 300px;" class="pe-2">
                                    <div class="text-center py-4">
                                        <i class="bx bx-bell-off fs-48 text-muted"></i>
                                        <h6 class="mt-2">No notifications</h6>
                                        <p class="text-muted mb-0">You're all caught up!</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

				<div class="modal fade" id="removeNotificationModal" tabindex="-1" aria-labelledby="removeNotificationModalLabel" aria-hidden="true">
						<div class="modal-dialog modal-dialog-centered">
							<div class="modal-content">
								<div class="modal-header">
									<h5 class="modal-title" id="removeNotificationModalLabel">Remove Notifications</h5>
									<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
								</div>
								<div class="modal-body">
									<p class="mb-0">Are you sure you want to remove the selected notifications?</p>
								</div>
								<div class="modal-footer">
									<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="NotificationModalbtn-close">Cancel</button>
									<button type="button" class="btn btn-danger" id="delete-notification">Remove</button>
								</div>
							</div>
						</div>
					</div>
               
                <!-- User Profile -->
                <div class="dropdown ms-sm-3 header-item topbar-user">
                    <button type="button" class="btn material-shadow-none" id="page-header-user-dropdown"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            @auth
                                <img class="rounded-circle header-profile-user"
                                    src="{{ Auth::user()->avatar ?? asset('images/default-avatar.png') }}" 
                                    alt="User Avatar">
                                <span class="text-start ms-xl-2">
                                    <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">
                                        {{ Auth::user()->name }}
                                    </span>
                                    <span class="d-none d-xl-block ms-1 fs-12 user-name-sub-text">
                                        {{ Auth::user()->role ?? 'User' }}
                                    </span>
                                </span>
                            @endauth
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        @auth
                            <a class="dropdown-item" href="#">
                                <i class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i> 
                                <span class="align-middle">Profile</span>
                            </a>
                            
                            <a class="dropdown-item" href="#">
                                <i class="mdi mdi-cog text-muted fs-16 align-middle me-1"></i> 
                                <span class="align-middle">Settings</span>
                            </a>
                            
                            <div class="dropdown-divider"></div>
                            
                            <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();
                                document.getElementById('logout-form').submit();">
                                <i class="mdi mdi-logout text-muted fs-16 align-middle me-1"></i> 
                                <span class="align-middle">{{ __('Logout') }}</span>
                            </a>
                            
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>