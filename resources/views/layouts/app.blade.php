<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ config('app.name', 'Consultancy HRIMS') }} | @yield('title', 'Login')</title>

    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="">
    <meta name="MobileOptimized" content="320">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="https://www.vnrseeds.com/wp-content/uploads/2018/12/vnr-logo-69x90.png">
    
    <!-- VNR Seeds CSS Files -->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/frontend/fonts.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/frontend/bootstrap.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/frontend/auth.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/frontend/custom-style.css') }}">
    
    <!-- Custom styles for login page -->
    <style>
        /* Additional styles if needed */
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeeba;
            color: #856404;
        }
        
        .alert ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        
        .is-invalid {
            border-color: #dc3545 !important;
        }
        
        .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
    
    @stack('styles')
</head>

<body class="login-bg-b">
    @yield('content')
    
    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Custom login script -->
    <script>
    $(document).ready(function () {
        // Form submission handler
        $('form').on('submit', function() {
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true);
            btn.html('<i class="fa fa-spinner fa-spin me-2"></i>Logging in...');
        });
        
        // Show/hide password
        $('.toggle-password').on('click', function() {
            const input = $(this).closest('.ad-auth-feilds').find('input');
            const icon = $(this).find('i');
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
    </script>
    
    @stack('scripts')
</body>

</html>