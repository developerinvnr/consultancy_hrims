<?php

namespace App\Http\Controllers;

use App\Models\ManpowerRequisition;
use App\Models\User;
use App\Models\CandidateMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('hr_admin')) {
            return $this->hrAdminDashboard();
        }

          
        if ($user->hasRole('management')) {
            return redirect()->route('dashboard.management');
        }

        return $this->userDashboard($user);
    }

    private function hrAdminDashboard()
    {
        // KPI Stats with more detailed breakdowns
        $stats = [
            // Requisition Pipeline Stats
            'total_requisitions' => ManpowerRequisition::count(),
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
            
            // Agreement Workflow Stats
            'agreement_pending' => ManpowerRequisition::where('status', 'Agreement Pending')->count(),
            'unsigned_uploaded' => ManpowerRequisition::where('status', 'Unsigned Agreement Uploaded')->count(),
            'agreement_completed' => ManpowerRequisition::where('status', 'Agreement Completed')->count(),
            
            // Candidate Master Stats
            'total_candidates' => CandidateMaster::count(),
            'active_candidates' => CandidateMaster::where('candidate_status', 'Active')->count(),
            'signed_agreement_uploaded' => CandidateMaster::where('candidate_status', 'Signed Agreement Uploaded')->count(),
            'rejected_candidates' => CandidateMaster::where('candidate_status', 'Rejected')->count(),
        ];

        // Requisition Type Breakdown
        $stats['requisition_by_type'] = ManpowerRequisition::select('requisition_type', DB::raw('count(*) as count'))
            ->groupBy('requisition_type')
            ->pluck('count', 'requisition_type')
            ->toArray();

        // Monthly Stats (Current Month)
        $currentMonth = now()->startOfMonth();
        $stats['this_month'] = [
            'submissions' => ManpowerRequisition::where('submission_date', '>=', $currentMonth)->count(),
            'verifications' => ManpowerRequisition::where('hr_verification_date', '>=', $currentMonth)->count(),
            'approvals' => ManpowerRequisition::where('approval_date', '>=', $currentMonth)->count(),
            'processed' => ManpowerRequisition::where('processing_date', '>=', $currentMonth)->count(),
        ];

        // Top Submitters (Last 30 days)
        $stats['top_submitters'] = ManpowerRequisition::select('submitted_by_name', DB::raw('count(*) as count'))
            ->where('submission_date', '>=', now()->subDays(30))
            ->groupBy('submitted_by_name')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        // Department-wise breakdown
        $stats['by_department'] = ManpowerRequisition::select('department_id', DB::raw('count(*) as count'))
            ->with('department:id,department_name')
            ->groupBy('department_id')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        // Average Processing Times
        $stats['avg_times'] = [
            'verification_time' => ManpowerRequisition::whereNotNull('hr_verification_date')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, submission_date, hr_verification_date)) as avg_hours')
                ->value('avg_hours'),
            'approval_time' => ManpowerRequisition::whereNotNull('approval_date')
                ->whereNotNull('hr_verification_date')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, hr_verification_date, approval_date)) as avg_hours')
                ->value('avg_hours'),
        ];

        // Recent requisitions
        $recent_requisitions = ManpowerRequisition::with(['submittedBy', 'department', 'function', 'candidate', 'candidate'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Status Distribution for Chart
        $stats['status_distribution'] = ManpowerRequisition::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('dashboard.hr-admin', compact('stats', 'recent_requisitions'));
    }

    private function userDashboard($user)
    {
        $data = [];

        $data['my_submissions'] = ManpowerRequisition::where('submitted_by_user_id', $user->id)
            ->with(['department', 'function', 'vertical'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $isApprover = false;
        $data['pending_approvals'] = collect();
        $data['approval_stats'] = [];

        if ($user->emp_id) {
            $hasApprovals = ManpowerRequisition::where('approver_id', $user->emp_id)->exists();

            if ($hasApprovals) {
                $isApprover = true;

                $data['pending_approvals'] = ManpowerRequisition::where('status', 'Pending Approval')
                    ->where('approver_id', $user->emp_id)
                    ->with(['department', 'function', 'submittedBy'])
                    ->orderBy('created_at', 'desc')
                    ->get();

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

        $data['recent_requisitions'] = ManpowerRequisition::with(['submittedBy','candidate'])
            ->where(function ($query) use ($user, $isApprover) {
                $query->where('submitted_by_user_id', $user->id);
                if ($isApprover) {
                    $query->orWhere('approver_id', $user->emp_id);
                }
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $data['user_stats'] = [
            'total_submissions' => ManpowerRequisition::where('submitted_by_user_id', $user->id)->count(),
            'pending_hr_verification' => ManpowerRequisition::where('submitted_by_user_id', $user->id)
                ->where('status', 'Pending HR Verification')->count(),
            'hr_verified' => ManpowerRequisition::where('submitted_by_user_id', $user->id)
                ->where('status', 'Hr Verified')->count(),
            'pending_approval' => ManpowerRequisition::where('submitted_by_user_id', $user->id)
                ->where('status', 'Pending Approval')->count(),
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