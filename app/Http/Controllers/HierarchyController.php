<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HierarchyController extends Controller
{
    public function getZoneByBU(Request $request)
    {
        $zoneList = DB::table('core_bu_zone_mapping')
            ->leftJoin('core_zone', 'core_zone.id', '=', 'core_bu_zone_mapping.zone_id')
            ->where('business_unit_id', $request->bu)
            ->whereNull('core_bu_zone_mapping.effective_to')
            ->select('core_zone.id', 'core_zone.zone_name')
            ->orderBy('core_zone.zone_name')
            ->get();

        return response()->json(['zoneList' => $zoneList]);
    }

    public function getRegionByZone(Request $request)
    {
        $regionList = DB::table('core_zone_region_mapping')
            ->leftJoin('core_region', 'core_region.id', '=', 'core_zone_region_mapping.region_id')
            ->where('zone_id', $request->zone)
            ->select('core_region.id', 'core_region.region_name')
            ->orderBy('core_region.region_name')
            ->get();

        return response()->json(['regionList' => $regionList]);
    }

    public function getTerritoryByRegion(Request $request)
    {
        $territoryList = DB::table('core_region_territory_mapping')
            ->leftJoin('core_territory', 'core_territory.id', '=', 'core_region_territory_mapping.territory_id')
            ->where('region_id', $request->region)
            ->whereNull('core_region_territory_mapping.effective_to')
            ->select('core_territory.id', 'core_territory.territory_name')
            ->orderBy('core_territory.territory_name')
            ->get();

        return response()->json(['territoryList' => $territoryList]);
    }
}
