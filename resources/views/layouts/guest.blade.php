<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-layout="vertical"
    data-topbar="light"
    data-sidebar="dark"
    data-sidebar-size="md"
    data-sidebar-image="none"
    data-preloader="disable"
    data-theme="default"
    data-theme-colors="default">
@include('layouts.header')
<body>
	 <!-- Begin page -->
    <div id="layout-wrapper">
        @include('layouts.topbar')
        @include('layouts.sidebar')
        <!-- Vertical Overlay-->
        <div class="vertical-overlay"></div>
        
        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                @yield('content') <!-- ONLY ONE HERE -->
            </div>
            @include('layouts.footer')
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->
    @include('layouts.script')
    @yield('script_section')
</body>
</html>