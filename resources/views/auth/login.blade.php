@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="ad-auth-wrapper login-bg">
    <div class="ad-auth-box">
        <div class="row align-items-center">
            <!-- Left Column with Logo -->
            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12" style="position: relative;">
                <h1 class="text-white welcome-text"><b>Welcome</b></h1>
                <div class="ad-auth-img">
                    <img src="{{ asset('images/login-logo.png') }}" alt="VNR Seeds Logo">
                </div>
                
                @if(session('status'))
                    <div class="alert alert-warning mt-3 mx-3">
                        {{ session('status') }}
                    </div>
                @endif
            </div>
        
            <!-- Right Column with Login Form -->
            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                <div class="ad-auth-content">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        
                        <img style="width: 150px;" src="{{ asset('images/peepal-logo-final.png') }}" alt="Peepal Logo">
                        
                        <div class="ad-auth-form" style="margin-top:0px;">
                            <!-- Email/Employee ID Field -->
                            <div class="ad-auth-feilds mb-30" style="margin-top:0px;">
                                <input type="text" 
                                       placeholder="Email Address" 
                                       id="email" 
                                       name="email"
                                       value="{{ old('email') }}" 
                                       class="ad-input login-input @error('email') is-invalid @enderror"
                                       required 
                                       autocomplete="email" 
                                       autofocus>
                                <div class="ad-auth-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 483.3 483.3">
                                        <path d="M424.3,57.75H59.1c-32.6,0-59.1,26.5-59.1,59.1v249.6c0,32.6,26.5,59.1,59.1,59.1h365.1c32.6,0,59.1-26.5,59.1-59.1
                                            v-249.5C483.4,84.35,456.9,57.75,424.3,57.75z M456.4,366.45c0,17.7-14.4,32.1-32.1,32.1H59.1c-17.7,0-32.1-14.4-32.1-32.1
                                            v-249.5c0-17.7,14.4-32.1,32.1-32.1h365.1c17.7,0,32.1,14.4,32.1,32.1v249.5H456.4z" fill="#9abeed"></path>
                                        <path d="M304.8,238.55l118.2-106c5.5-5,6-13.5,1-19.1c-5-5.5-13.5-6-19.1-1l-163,146.3l-31.8-28.4c-0.1-0.1-0.2-0.2-0.2-0.3
                                            c-0.7-0.7-1.4-1.3-2.2-1.9L78.3,112.35c-5.6-5-14.1-4.5-19.1,1.1c-5,5.6-4.5,14.1,1.1,19.1l119.6,106.9L60.8,350.95
                                            c-5.4,5.1-5.7,13.6-0.6,19.1c2.7,2.8,6.3,4.3,9.9,4.3c3.3,0,6.6-1.2,9.2-3.6l120.9-113.1l32.8,29.3c2.6,2.3,5.8,3.4,9,3.4
                                            c3.2,0,6.5-1.2,9-3.5l33.7-30.2l120.2,114.2c2.6,2.5,6,3.7,9.3,3.7c3.6,0,7.1-1.4,9.8-4.2c5.1-5.4,4.9-14-0.5-19.1L304.8,238.55z"
                                            fill="#9abeed"></path>
                                    </svg>
                                </div>
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            
                            <!-- Password Field -->
                            <div class="ad-auth-feilds">
                                <input type="password" 
                                       placeholder="Password" 
                                       id="password" 
                                       name="password"
                                       class="ad-input login-input @error('password') is-invalid @enderror" 
                                       required 
                                       autocomplete="current-password">
                                <div class="ad-auth-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 482.8 482.8">
                                        <path d="M395.95,210.4h-7.1v-62.9c0-81.3-66.1-147.5-147.5-147.5c-81.3,0-147.5,66.1-147.5,147.5c0,7.5,6,13.5,13.5,13.5
                                            s13.5-6,13.5-13.5c0-66.4,54-120.5,120.5-120.5c66.4,0,120.5,54,120.5,120.5v62.9h-275c-14.4,0-26.1,11.7-26.1,26.1v168.1
                                            c0,43.1,35.1,78.2,78.2,78.2h204.9c43.1,0,78.2-35.1,78.2-78.2V236.5C422.05,222.1,410.35,210.4,395.95,210.4z M395.05,404.6
                                            c0,28.2-22.9,51.2-51.2,51.2h-204.8c-28.2,0-51.2-22.9-51.2-51.2V237.4h307.2L395.05,404.6L395.05,404.6z" fill="#9abeed"></path>
                                        <path d="M241.45,399.1c27.9,0,50.5-22.7,50.5-50.5c0-27.9-22.7-50.5-50.5-50.5c-27.9,0-50.5,22.7-50.5,50.5
                                            S213.55,399.1,241.45,399.1z M241.45,325c13,0,23.5,10.6,23.5,23.5s-10.5,23.6-23.5,23.6s-23.5-10.6-23.5-23.5
                                            S228.45,325,241.45,325z" fill="#9abeed"></path>
                                    </svg>
                                </div>
                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Remember Me & Forgot Password -->
                        <div class="ad-other-feilds">
                            <div class="ad-checkbox">
                                <label>
                                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                    <span>Remember Me</span>
                                </label>
                            </div>
                            
                            @if (Route::has('password.request'))
                                <a class="forgot-pws-btn" href="{{ route('password.request') }}">
                                    Forgot Password?
                                </a>
                            @endif
                        </div>
                        
                        <!-- Login Button -->
                        <div class="ad-auth-btn" style="margin-top: 40px; margin-bottom: 40px;">
                            <button style="padding: 12px 50px;" type="submit" class="effect-btn btn btn-secondary pl-3 pr-3">
                                Login
                            </button>
                        </div>

                        <!-- Add this below the form buttons -->
                        <div class="text-center mt-3">
                            <p class="mb-0">Don't have an account? 
                                <a href="{{ route('register') }}" class="text-primary"><b>Create Account</b></a>
                            </p>
                        </div>
                        
                        <!-- Links -->
                        <a href=""><b>Terms of Use</b></a> | <a href=""><b>Privacy Policy</b></a><br>
                        <p class="ad-register-text">&#169; Copyright {{ date('Y') }} VNR Seeds Private Limited India</p>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Website Links Section -->
    <div class="web-url-section">
        <div class="web-icon-section"><i class="fa fa-globe"></i></div>
        <div style="float: left; margin-left: 11px;">
            <b>
                <a style="color:#2c4d57;font-size:12px;" title="VNR Seeds Private Limited" target="_blank"
                    href="https://www.vnrseeds.com/">www.vnrseeds.com</a><br>
                <a style="color:#2c4d57;font-size:12px;" title="VNR Nursery" target="_blank" 
                   href="https://www.vnrnursery.in/">www.vnrnursery.in</a>
            </b>
        </div>
    </div>
</div>
@endsection

@push('styles')
<!-- Add password toggle button if not in CSS -->
<style>
    .ad-auth-feilds {
        position: relative;
    }
    
    .toggle-password {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #9abeed;
        cursor: pointer;
    }
    
    .ad-checkbox {
        display: flex;
        align-items: center;
    }
    
    .ad-checkbox input[type="checkbox"] {
        margin-right: 8px;
    }
    
    .ad-checkbox span {
        font-size: 14px;
        color: #666;
    }
    
    .ad-other-feilds {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
    }
    
    .forgot-pws-btn {
        color: #2c4d57;
        text-decoration: none;
        font-size: 14px;
    }
    
    .forgot-pws-btn:hover {
        text-decoration: underline;
    }
</style>
@endpush

@push('scripts')
<script>
    // Add password toggle button dynamically
    $(document).ready(function() {
        if ($('#password').length && !$('.toggle-password').length) {
            $('#password').after(`
                <button type="button" class="toggle-password">
                    <i class="fa fa-eye"></i>
                </button>
            `);
        }
    });
</script>
@endpush