<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SentEmail;


class EmailLogController extends Controller
{
    public function index(Request $request)
    {
        $emails = SentEmail::latest()
            ->paginate(20);

        return view('hr-admin.emails.index', compact('emails'));
    }
}