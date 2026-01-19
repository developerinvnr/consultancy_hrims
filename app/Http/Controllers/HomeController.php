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
            'pending_verification' => ManpowerRequisition::where('status', 'Pending HR Verification')->count(),
            'hr_verified' => ManpowerRequisition::where('status', 'Hr Verified')->count(),
            'pending_approval' => ManpowerRequisition::where('status', 'Pending Approval')->count(),
            'approved' => ManpowerRequisition::where('status', 'Approved')->count(),
            'processed' => ManpowerRequisition::where('status', 'Processed')->count(),
            'correction_required' => ManpowerRequisition::where('status', 'Correction Required')->count(),
            'rejected' => ManpowerRequisition::where('status', 'Rejected')->count(),

            // Agreement statistics
            'agreement_pending' => CandidateMaster::where('candidate_status', 'Agreement Pending')->count(),
            'unsigned_uploaded' => CandidateMaster::where('candidate_status', 'Unsigned Agreement Uploaded')->count(),
            'agreement_completed' => CandidateMaster::where('candidate_status', 'Agreement Completed')->count(),
            'total_candidates' => CandidateMaster::count(),
        ];

        // Recent requisitions for HR Admin
        $recent_requisitions = ManpowerRequisition::with(['submittedBy', 'department', 'function'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

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

        // Check if user is an approver (has employee ID)
        $isApprover = false;
        $data['pending_approvals'] = collect();
        $data['approval_stats'] = [];

        if ($user->emp_id) {
            // Get pending approvals (where approver_id matches AND status is Pending Approval)
            $data['pending_approvals'] = ManpowerRequisition::where('status', 'Pending Approval')
                ->where('approver_id', $user->emp_id)
                ->with(['department', 'function', 'submittedBy'])
                ->orderBy('created_at', 'desc')
                ->get();

            $isApprover = true;
            $isApprover = ManpowerRequisition::where('approver_id', $user->emp_id)->exists();

            if ($isApprover) {
                // Get statistics based on approver_id AND approval_date
                $data['approval_stats'] = [
                    // Currently pending for approval
                    'pending' => ManpowerRequisition::where('approver_id', $user->emp_id)
                        ->where('status', 'Pending Approval')
                        ->count(),

                    // Approved (has approval_date and approver_id matches)
                    'total_approved' => ManpowerRequisition::where('approver_id', $user->emp_id)
                        ->whereNotNull('approval_date')
                        ->count(),

                    // Rejected (has rejection_date and approver_id matches)
                    'total_rejected' => ManpowerRequisition::where('approver_id', $user->emp_id)
                        ->whereNotNull('rejection_date')
                        ->count(),

                    // Total assigned to this approver (any status)
                    'total_assigned' => ManpowerRequisition::where('approver_id', $user->emp_id)
                        ->count(),

                    // Approved but later overridden (approver_id matches but status changed)
                    'approved_but_overridden' => ManpowerRequisition::where('approver_id', $user->emp_id)
                        ->whereNotNull('approval_date')
                        ->whereNotIn('status', ['Approved', 'Agreement Completed'])
                        ->count(),
                ];
            }
        }

        // Get recent requisitions for user
        if ($isApprover) {
            // For approvers, show both their submissions and approvals
            $data['recent_requisitions'] = ManpowerRequisition::with(['submittedBy'])
                ->where('approver_id', $user->emp_id)
                ->orWhere('submitted_by_user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        } else {
            // For regular users, only show their submissions
            $data['recent_requisitions'] = ManpowerRequisition::with(['submittedBy'])
                ->where('submitted_by_user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        // Get user statistics - ALWAYS get submitter stats if user has submissions
        $data['user_stats'] = [
            'total_submissions' => ManpowerRequisition::where('submitted_by_user_id', $user->id)->count(),
            'pending_hr_verification' => ManpowerRequisition::where('submitted_by_user_id', $user->id)
                ->where('status', 'Pending HR Verification')->count(),
            'hr_verified' => ManpowerRequisition::where('submitted_by_user_id', $user->id)
                ->where('status', 'Hr Verified')->count(),
            'pending_approval' => ManpowerRequisition::where('submitted_by_user_id', $user->id)
                ->where('status', 'Pending Approval')->count(),
            'approved' => ManpowerRequisition::where('submitted_by_user_id', $user->id)
                ->where('status', 'Approved')->count(),
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
       //   dd($data['is_approver']);
        return view('dashboard.user', $data);
    }
}
