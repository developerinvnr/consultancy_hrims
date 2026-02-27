<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    protected function authenticated($request, $user)
    {
        $employee = DB::table('core_employee')
            ->where('employee_id', $user->emp_id)
            ->orWhere('emp_code', $user->emp_code)
            ->first();

        if ($employee && $employee->department == 15) {

            if (!$user->hasRole('sales')) {
                $user->assignRole('sales');
            }
        } else {

            if (!$user->hasRole('user')) {
                $user->assignRole('user');
            }

            // optional: remove sales role
            if ($user->hasRole('sales')) {
                $user->removeRole('sales');
            }
        }
    }
}
