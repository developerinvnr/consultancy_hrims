<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ManpowerRequisition;

class TeamRequisitionController extends Controller
{
    /**
     * Get all subordinate employees recursively (direct and indirect reports)
     */
    private function getAllSubordinateIds($managerId)
    {
        $subordinateIds = [];
        
        $directReports = DB::table('core_employee')
            ->where('emp_reporting', $managerId)
            ->pluck('employee_id')
            ->toArray();
        
        foreach ($directReports as $empId) {
            $subordinateIds = array_merge(
                $subordinateIds, 
                [$empId],
                $this->getAllSubordinateIds($empId)
            );
        }
        
        return array_unique($subordinateIds);
    }
    
    /**
     * Display team requisitions
     */
    public function index()
    {
        $userId = auth()->user()->emp_id;
        $userId = (int)$userId;
        
        // Get team member IDs
        $teamMemberIds = $this->getAllSubordinateIds($userId);
        
        // Query 1: Requisitions where user is approver (pending their approval)
        $pendingApprovals = ManpowerRequisition::where('approver_id', $userId)
            ->where('status', 'Pending Approval')
            ->get()
            ->map(function($req) {
                $req->source = 'pending_my_approval';
                return $req;
            });
        
        // Query 2: Requisitions from team members
        $teamSubmissions = collect();
        if (!empty($teamMemberIds)) {
            $teamSubmissions = ManpowerRequisition::whereIn('submitted_by_employee_id', $teamMemberIds)
                ->get()
                ->map(function($req) {
                    $req->source = 'team_submission';
                    return $req;
                });
        }
        
        // Query 3: Requisitions where user is reporting manager
        $reportingManagerReqs = ManpowerRequisition::where('reporting_manager_employee_id', $userId)
            ->get()
            ->map(function($req) {
                $req->source = 'reporting_manager';
                return $req;
            });
        
        // Merge all collections and remove duplicates
        $allRequisitions = $pendingApprovals
            ->concat($teamSubmissions)
            ->concat($reportingManagerReqs)
            ->unique('id');
        
        // Sort: Pending approvals first, then by ID desc
        $sortedRequisitions = $allRequisitions->sortByDesc(function($req) use ($userId) {
            // Priority: 1 for pending my approval, 2 for others
            return ($req->approver_id == $userId && $req->status == 'Pending Approval') ? 1 : 2;
        })->sortByDesc('id');
        
        // Paginate manually
        $currentPage = request()->get('page', 1);
        $perPage = 10;
        $currentItems = $sortedRequisitions->slice(($currentPage - 1) * $perPage, $perPage);
        $teamRequisitions = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $sortedRequisitions->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
        
        // Statistics
        $pendingApprovalCount = $pendingApprovals->count();
        $teamSubmissionsCount = $teamSubmissions->count();
        
        return view('team_requisitions.index', compact(
            'teamRequisitions',
            'pendingApprovalCount',
            'teamSubmissionsCount',
            'userId'
        ));
    }
}