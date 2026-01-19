<div class="app-menu navbar-menu">
    <div class="navbar-brand-box">
        {{-- Logo --}}
        <a href="#" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{ asset('assets/images/mini-logo.png') }}" height="50" alt="Mini Logo">
            </span>
            <span class="logo-lg">
                <img src="{{ asset('assets/images/login-logo.png') }}" height="60" alt="Main Logo">
            </span>
        </a>
        <a href="#" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ asset('assets/images/mini-logo.png') }}" height="50" alt="Mini Logo">
            </span>
            <span class="logo-lg">
                <img src="{{ asset('assets/images/login-logo.png') }}" height="60" alt="Main Logo">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar" data-simplebar class="h-100">
        <div class="container-fluid">
            <ul class="navbar-nav" id="navbar-nav">

                {{-- Dashboard --}}
                <li class="nav-item active">
                    <a class="nav-link menu-link" href="{{ route('dashboard') }}">
                        <i class="las la-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                @if(auth()->user()->hasAnyRole(['Admin', 'hr_admin']))
                {{-- Core API --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('core_api.index')  }}">
                        <i class="ri-pages-line"></i>
                        <span>Core Api</span>
                    </a>
                </li>


                {{-- User Access --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('users.index') }}">
                        <i class="ri-pages-line"></i>
                        <span>User</span>
                    </a>
                </li>

                {{-- Roles & Permission --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('roles.index') }}">
                        <i class="ri-pages-line"></i>
                        <span>Roles & Permission</span>
                    </a>
                </li>

                {{-- Leave --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#">
                        <i class="ri-pages-line"></i>
                        <span>Leave</span>
                    </a>
                </li>
                @endif



                {{-- Consultancy --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('requisitions.index') }}">
                        <i class="ri-file-list-3-line"></i>
                        <span>Requisitions</span>
                    </a>
                </li>


                {{-- Attendance --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('attendance.index') }}">
                        <i class="ri-calendar-check-line"></i>
                        <span>Attendance</span>
                    </a>
                </li>

                <!-- My Team -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('my-team*') ? 'active' : '' }}" href="{{ route('my-team.index') }}">
                        <i class="ri-team-line"></i>
                        <span>My Team</span>
                    </a>
                </li>








            </ul>
        </div>
    </div>
</div>