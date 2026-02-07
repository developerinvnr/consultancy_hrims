<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Auth;

class ExtLoginController extends Controller
{
    public function login(Request $request)
    {
       $token = $request->query('token');
       $secretKey ='v7n90l9uvy';

        try {
            // Decode the JWT token
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));

            // Extract employee ID and email from token
            $employeeId = $decoded->sub;


            // Find the user in OJAS by employee ID
            $user = User::where('emp_id', $employeeId)->where('status','A')->first();

            if ($user) {
                // Log the user in
                 Auth::login($user);
                // Check if user is successfully logged in
                if (Auth::check()) {

                    // Redirect to the dashboard or authenticated page
                    return redirect()->route('home');
                } else {
                    return redirect('/login')->withErrors(['Unable to log in']);
                }
            } else {
                return redirect('/login')->withErrors(['User not found in OJAS']);
            }

        } catch (\Exception $e) {
            // Invalid token or other error
            return redirect('/login')->withErrors(['Invalid token or session expired']);
        }
    }
}
