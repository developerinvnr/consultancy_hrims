<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = Auth::user();
           if ($user->hasRole('hr_admin')) {
            return redirect()->route('hr-admin.dashboard');
        }
        //  elseif ($user->hasRole('approver')) {
        //     return redirect()->route('approver.dashboard');
        // } elseif ($user->hasRole('sales_user')) {
        //     return redirect()->route('sales.dashboard');}
        else {
            // Default dashboard for other roles
            return view('home');
        }
        
    }
}
