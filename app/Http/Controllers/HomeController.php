<?php

namespace App\Http\Controllers;

use App\Models\ManpowerRequisition;
use App\Models\User;
use App\Models\CandidateMaster;
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

    public function index()
    {
        $user = Auth::user();

        // HR Admin Dashboard
        if ($user->hasRole('hr_admin')) {
            return $this->hrAdminDashboard();
        }

        // Regular User Dashboard (can be submitter, approver, or both)
        return $this->userDashboard($user);
    }

    /**
     * HR Admin Dashboard
     */
    private function hrAdminDashboard()
{
    $stats = [
        // Manpower Requisition Stats
        'pending_verification' => ManpowerRequisition::where('status', 'Pending HR Verification')->count(),
        'hr_verified' => ManpowerRequisition::where('status', 'Hr Verified')->count(),
        'pending_approval' => ManpowerRequisition::where('status', 'Pending Approval')->count(),
        'approved' => ManpowerRequisition::where(function($query) {
            $query->where('status', 'Approved')
                  ->orWhere('status', 'Agreement Pending')
                  ->orWhere('status', 'Unsigned Agreement Uploaded')
                  ->orWhere('status', 'Agreement Completed');
        })->count(),
        'rejected' => ManpowerRequisition::where('status', 'Rejected')->count(),
        'correction_required' => ManpowerRequisition::where('status', 'Correction Required')->count(),
        'processed' => ManpowerRequisition::where('status', 'Processed')->count(),
        
        // Candidate Master Stats (for approved requisitions)
        'agreement_pending' => ManpowerRequisition::where('status', 'Agreement Pending')->count(),
        'unsigned_uploaded' => ManpowerRequisition::where('status', 'Unsigned Agreement Uploaded')->count(),
        'agreement_completed' => ManpowerRequisition::where('status', 'Agreement Completed')->count(),
        
        // Additional stats from CandidateMaster for HR perspective
        'total_candidates' => CandidateMaster::count(),
        'signed_agreement_uploaded' => CandidateMaster::where('candidate_status', 'Signed Agreement Uploaded')->count(),
        'active_candidates' => CandidateMaster::where('candidate_status', 'Active')->count(),
        'rejected_candidates' => CandidateMaster::where('candidate_status', 'Rejected')->count(),
        
        // Overall totals
        'total_requisitions' => ManpowerRequisition::count(),
    ];

    // Recent requisitions for HR Admin
    $recent_requisitions = ManpowerRequisition::with(['submittedBy', 'department', 'function'])
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    //dd($recent_requisitions);
    return view('dashboard.hr-admin', compact('stats', 'recent_requisitions'));
}

    /**
     * Regular User Dashboard (can be submitter, approver, or both)
     */
    /**
     * Regular User Dashboard (can be submitter, approver, or both)
     */
    private function userDashboard($user)
    {
        $data = [];

        // Get user's submissions
        $data['my_submissions'] = ManpowerRequisition::where('submitted_by_user_id', $user->id)
            ->with(['department', 'function', 'vertical'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Initialize variables
        $isApprover = false;
        $data['pending_approvals'] = collect();
        $data['approval_stats'] = [];

        // Check if user is an approver (has employee ID AND has approvals assigned)
        if ($user->emp_id) {
            $hasApprovals = ManpowerRequisition::where('approver_id', $user->emp_id)->exists();

            if ($hasApprovals) {
                $isApprover = true;

                // Get pending approvals
                $data['pending_approvals'] = ManpowerRequisition::where('status', 'Pending Approval')
                    ->where('approver_id', $user->emp_id)
                    ->with(['department', 'function', 'submittedBy'])
                    ->orderBy('created_at', 'desc')
                    ->get();

                // Get statistics
                $data['approval_stats'] = [
                    'pending' => ManpowerRequisition::where('approver_id', $user->emp_id)
                        ->where('status', 'Pending Approval')
                        ->count(),

                    'total_approved' => ManpowerRequisition::where('approver_id', $user->emp_id)
                        ->whereNotNull('approval_date')
                        ->count(),

                    'total_rejected' => ManpowerRequisition::where('approver_id', $user->emp_id)
                        ->whereNotNull('rejection_date')
                        ->count(),

                    'total_assigned' => ManpowerRequisition::where('approver_id', $user->emp_id)
                        ->count(),

                    'approved_but_overridden' => ManpowerRequisition::where('approver_id', $user->emp_id)
                        ->whereNotNull('approval_date')
                        ->whereNotIn('status', ['Approved', 'Agreement Completed', 'Completed'])
                        ->count(),
                ];
            }
        }

        // Get recent requisitions
        $data['recent_requisitions'] = ManpowerRequisition::with(['submittedBy'])
            ->where(function ($query) use ($user, $isApprover) {
                $query->where('submitted_by_user_id', $user->id);

                if ($isApprover) {
                    $query->orWhere('approver_id', $user->emp_id);
                }
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get user statistics - FIXED to include all post-approval statuses
        $data['user_stats'] = [
            'total_submissions' => ManpowerRequisition::where('submitted_by_user_id', $user->id)->count(),
            'pending_hr_verification' => ManpowerRequisition::where('submitted_by_user_id', $user->id)
                ->where('status', 'Pending HR Verification')->count(),
            'hr_verified' => ManpowerRequisition::where('submitted_by_user_id', $user->id)
                ->where('status', 'Hr Verified')->count(),
            'pending_approval' => ManpowerRequisition::where('submitted_by_user_id', $user->id)
                ->where('status', 'Pending Approval')->count(),

            // FIXED: Include all post-approval statuses
            'approved' => ManpowerRequisition::where('submitted_by_user_id', $user->id)
                ->where(function ($query) {
                    $query->where('status', 'Approved')
                        ->orWhere('status', 'Agreement Pending')
                        ->orWhere('status', 'Unsigned Agreement Uploaded')
                        ->orWhere('status', 'Agreement Completed')
                        ->orWhere('status', 'Completed');
                })->count(),

            'rejected' => ManpowerRequisition::where('submitted_by_user_id', $user->id)
                ->where('status', 'Rejected')->count(),
            'correction_required' => ManpowerRequisition::where('submitted_by_user_id', $user->id)
                ->where('status', 'Correction Required')->count(),
            'agreement_pending' => ManpowerRequisition::where('submitted_by_user_id', $user->id)
                ->where('status', 'Agreement Pending')->count(),
            'unsigned_uploaded' => ManpowerRequisition::where('submitted_by_user_id', $user->id)
                ->where('status', 'Unsigned Agreement Uploaded')->count(),
            'agreement_completed' => ManpowerRequisition::where('submitted_by_user_id', $user->id)
                ->where('status', 'Agreement Completed')->count(),
            'processed' => ManpowerRequisition::where('submitted_by_user_id', $user->id)
                ->where('status', 'Processed')->count(),
        ];

        $data['is_approver'] = $isApprover;
        return view('dashboard.user', $data);
    }
}
