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

    <div id="layout-wrapper">
        @include('layouts.topbar')
        @include('layouts.sidebar')

        <div class="main-content">
            <div class="page-content">
                @yield('content')
            </div>
            @include('layouts.footer')
        </div>
    </div>

    @include('layouts.modals.help-manual')


    @include('layouts.script')
    @stack('scripts')


</body>
</html>
