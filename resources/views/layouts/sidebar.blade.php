<!-- My Team - Only show if user has emp_id AND has team members -->
@php
$user = Auth::user();
$hasTeam = $user->emp_id &&
App\Models\CandidateMaster::where('reporting_manager_employee_id', $user->emp_id)->exists();
@endphp
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
                <li class="nav-item">
                    <a class="nav-link menu-link @activeRoute('dashboard')" href="{{ route('dashboard') }}">
                        <i class="las la-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                @if(auth()->user()->hasAnyRole(['Admin', 'hr_admin']))
                {{-- Core API --}}
                <li class="nav-item">
                    <a class="nav-link menu-link @activeRoute('core_api.*')" href="{{ route('core_api.index') }}">
                        <i class="ri-pages-line"></i>
                        <span>Core Api</span>
                    </a>
                </li>



                {{-- User Access --}}
                <li class="nav-item">
                    <a class="nav-link menu-link @activeRoute('users.*')" href="{{ route('users.index') }}">
                        <i class="ri-user-3-line"></i>
                        <span>User</span>
                    </a>
                </li>


                {{-- Roles & Permission --}}
                <li class="nav-item">
                    <a class="nav-link menu-link @activeRoute('roles.*')" href="{{ route('roles.index') }}">
                        <i class="ri-shield-user-line"></i>
                        <span>Roles & Permission</span>
                    </a>
                </li>


                {{-- Leave --}}
                {{--<li class="nav-item">
                    <a class="nav-link menu-link" href="#">
                        <i class="ri-pages-line"></i>
                        <span>Leave</span>
                    </a>
                </li>--}}
                @endif



                {{-- Consultancy --}}
                @if(!auth()->user()->hasAnyRole(['hr_admin']))
                <li class="nav-item">
                    <a class="nav-link menu-link @activeRoute('requisitions.*')" href="{{ route('requisitions.index') }}">
                        <i class="ri-file-list-3-line"></i>
                        <span>Requisitions</span>
                    </a>
                </li>
                @endif



                {{-- Attendance --}}
                <li class="nav-item">
                    <a class="nav-link menu-link @activeRoute('attendance.*')" href="{{ route('attendance.index') }}">
                        <i class="ri-calendar-check-line"></i>
                        <span>Attendance</span>
                    </a>
                </li>


                @if($hasTeam)
                @if($hasTeam)
                <li class="nav-item">
                    <a class="nav-link menu-link @activeRoute('my-team.*')" href="{{ route('my-team.index') }}">
                        <i class="ri-team-line"></i>
                        <span>My Team</span>
                    </a>
                </li>
                @endif

                @endif

                @if(auth()->user()->hasAnyRole(['hr_admin']))
                <li class="nav-item">
                    <a class="nav-link menu-link @activeRoute('salary.*')" href="{{ route('salary.index') }}">
                        <i class="ri-money-rupee-circle-line"></i>
                        <span>Remuneration  Processing</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link @activeRoute('master')" href="{{ route('master') }}">
                        <i class="ri-database-2-line"></i>
                        <span>Master Report</span>
                    </a>
                </li>

               <li class="nav-item">
                    <a class="nav-link menu-link @activeRoute('salary.detailed.report.*')"
                    href="{{ route('salary.detailed.report.view') }}">
                        <i class="ri-money-rupee-circle-line"></i>
                        <span>Remuneration Report</span>
                    </a>
                </li>


                <li class="nav-item">
                    <a href="{{ route('salary.management.report') }}" class="nav-link">
                        <i class="ri-bar-chart-line"></i>
                        <span>Management Report</span>
                    </a>
                </li>  

                <li class="nav-item">
                    <a class="nav-link menu-link @activeRoute('communication.*')" href="{{ route('communication.index') }}">
                        <i class="ri-message-2-line"></i>
                        <span>Control</span>
                    </a>
                </li>                
                @endif

            </ul>
        </div>
    </div>
</div>