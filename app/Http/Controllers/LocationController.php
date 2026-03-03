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

    public function focusIndex()
    {
        $states = DB::table('core_state')
            ->where('is_active', 1)
            ->orderBy('state_name')
            ->get(['id', 'state_name']);

        return view('focus.index', compact('states'));
    }

    public function getCitiesWithFocus(Request $request)
    {
        $cities = DB::table('core_city_village')
            ->where('district_id', $request->district_id)
            ->where('is_active', 1)
            ->orderBy('city_village_name')
            ->get([
                'id',
                'division_name',
                'city_village_name',
                'city_village_code',
                'focus_code'
            ]);

        return response()->json($cities);
    }

    public function updateFocusCode(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'focus_code' => 'nullable|string|max:50'
        ]);

        DB::table('core_city_village')
            ->where('id', $request->id)
            ->update([
                'focus_code' => $request->focus_code
            ]);

        return response()->json(['success' => true]);
    }
}
