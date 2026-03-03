<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function getDistricts(Request $request)
    {
        $districts = DB::table('core_district')
            ->where('state_id', $request->state_id)
            ->where('is_active', 1)
            ->orderBy('district_name')
            ->get(['id', 'district_name']);

        return response()->json($districts);
    }

    public function getCities(Request $request)
    {
        $cities = DB::table('core_city_village')
            ->where('district_id', $request->district_id)
            ->where('is_active', 1)
            ->orderBy('city_village_name')
            ->get(['id', 'city_village_name']);

        return response()->json($cities);
    }
}