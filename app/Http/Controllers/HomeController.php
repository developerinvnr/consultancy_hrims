<?php

namespace App\Http\Controllers;

use App\Models\ManpowerRequisition;
use App\Models\User;
use App\Models\CandidateMaster;
use App\Models\AgreementDocument;
use App\Models\AgreementCourier;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->hasRole('hr_admin')) {
            return $this->hrAdminDashboard($request);
        }


        if ($user->hasRole('management')) {
            return redirect()->route('dashboard.management');
        }

        return $this->userDashboard($user, $request);
    }

    private function hrAdminDashboard(Request $request)
    {
        $reqTab = $request->get('req_tab', 'submission');
        $expTab = $request->get('exp_tab', 'exp30');
        $statusFilter = $request->get('status_filter');
        $actionFilter = $request->get('action_filter');
        $query = ManpowerRequisition::with(['submittedBy', 'department', 'candidate', 'rejectedBy', 'currentApprover']);

        switch ($reqTab) {

            case 'submission':

                $query->where('status', 'Pending HR Verification');

                break;


            case 'correction_required':

                $query->where('status', 'Correction Required');

                break;


            case 'hr_verified':

                $query->where('status', 'Hr Verified');

                break;


            case 'approval':

                $query->where('status', 'Pending Approval');

                break;


            case 'approved':

                $query->where('status', 'Approved');

                break;


            case 'unsigned':

                $query->whereHas('candidate', function ($q) {

                    $q->where('candidate_status', 'Unsigned Agreement Created');
                });

                break;


            case 'dispatch_pending':

                $query->whereHas('candidate', function ($q) {
                    $q->where('candidate_status', 'Signed Agreement Uploaded');
                })
                    ->whereHas('candidate.signedAgreements', function ($q) {
                        $q->whereDoesntHave('courierDetails');
                    });

                break;


            case 'courier_pending':

                $query->whereHas('candidate.signedAgreements.courierDetails', function ($q) {
                    $q->whereNull('received_date');
                });

                break;


            case 'file_pending':

                $query->whereHas('candidate', function ($q) {
                    $q->where('candidate_status', 'Signed Agreement Uploaded')
                        ->whereNull('file_created_date');
                })
                    ->whereHas('candidate.signedAgreements.courierDetails', function ($q) {
                        $q->whereNotNull('received_date');
                    });

                break;



            case 'active':

                $query->whereHas('candidate', fn($q) => $q->where('candidate_status', 'Active'));

                break;


            case 'inactive':

                $query->whereHas('candidate', fn($q) => $q->where('candidate_status', 'Inactive'));

                break;


            case 'rejected':

                $query->where(function ($q) {

                    $q->where('status', 'Rejected')
                        ->orWhereHas('candidate', fn($sub) => $sub->where('candidate_status', 'Rejected'));
                });

                break;

            default:

                $query->latest();

                break;
        }

        // Apply status filter
        if ($statusFilter) {

            $query->where(function ($q) use ($statusFilter) {

                // Requisition workflow statuses
                $workflowStatuses = [
                    'Pending HR Verification',
                    'Pending Approval',
                    'Approved',
                    'Correction Required'
                ];

                if (in_array($statusFilter, $workflowStatuses)) {

                    $q->where('status', $statusFilter);
                } else {

                    // Candidate statuses
                    $q->whereHas('candidate', function ($sub) use ($statusFilter) {
                        $sub->where('candidate_status', $statusFilter);
                    });
                }
            });
        }

        // Apply action filter
        if ($actionFilter) {

            switch ($actionFilter) {

                case 'process':
                    $query->where('status', 'Approved')
                        ->whereDoesntHave('candidate');
                    break;

                case 'upload_signed':
                    $query->whereHas('candidate', function ($q) {
                        $q->where('candidate_status', 'Unsigned Agreement Created');
                    });
                    break;

                case 'receive_courier':
                    $query->whereHas('candidate', function ($q) {
                        $q->where('candidate_status', 'Signed Agreement Uploaded');
                    });
                    break;
            }
        }

        $recent_requisitions = $query
            ->latest()
            ->paginate(10)
            ->appends([
                'req_tab' => $reqTab,
                'exp_tab' => $expTab,
                'status_filter' => $statusFilter,
                'action_filter' => $actionFilter
            ]);
        // KPI Stats with more detailed breakdowns
        $stats = [
            // Requisition Pipeline Stats
            'total_requisitions' => ManpowerRequisition::count(),
            'pending_verification' => ManpowerRequisition::where('status', 'Pending HR Verification')->count(),
            'hr_verified' => ManpowerRequisition::where('status', 'Hr Verified')->count(),
            'pending_approval' => ManpowerRequisition::where('status', 'Pending Approval')->count(),
            'approved' => ManpowerRequisition::where(function ($query) {
                $query->where('status', 'Approved')
                    ->orWhere('status', 'Agreement Pending')
                    ->orWhere('status', 'Unsigned Agreement Created')
                    ->orWhere('status', 'Agreement Completed');
            })->count(),
            'rejected' => ManpowerRequisition::where(function ($q) {

                $q->where('status', 'Rejected')
                    ->orWhereHas('candidate', function ($sub) {
                        $sub->where('candidate_status', 'Rejected');
                    });
            })->count(),
            'correction_required' => ManpowerRequisition::where('status', 'Correction Required')->count(),
            'processed' => ManpowerRequisition::where('status', 'Processed')->count(),

            // Agreement Workflow Stats
            'agreement_pending' => ManpowerRequisition::where('status', 'Agreement Pending')->count(),
            'unsigned_uploaded' => ManpowerRequisition::where('status', 'Unsigned Agreement Created')->count(),
            'agreement_completed' => ManpowerRequisition::where('status', 'Agreement Completed')->count(),

            // Candidate Master Stats
            'total_candidates' => CandidateMaster::count(),
            'active_candidates' => CandidateMaster::where('candidate_status', 'Active')->count(),
            'signed_agreement_uploaded' => CandidateMaster::where('candidate_status', 'Signed Agreement Uploaded')->count(),
            'rejected_candidates' => CandidateMaster::where('candidate_status', 'Rejected')->count(),

            'verification_count' => ManpowerRequisition::whereNotNull('hr_verification_date')
                ->whereNotNull('submission_date')
                ->count(),
            'approval_count' => ManpowerRequisition::whereNotNull('approval_date')
                ->whereNotNull('submission_date')
                ->count(),
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
            ->limit(6)
            ->get();

        // Department-wise breakdown
        $stats['by_department'] = CandidateMaster::select('department_id', DB::raw('count(*) as count'))->where('candidate_status', 'Active')
            ->with('department:id,department_name')
            ->groupBy('department_id')
            ->orderBy('count', 'desc')
            ->limit(6)
            ->get();

        // Average Processing Times
        $stats['avg_times'] = [
            'verification_time' => 0,
            'approval_time' => 0,
            'verification_display' => 'N/A',
            'approval_display' => 'N/A',
        ];

        // Calculate verification time (submission to HR verification)
        $verificationData = ManpowerRequisition::whereNotNull('hr_verification_date')
            ->whereNotNull('submission_date')
            ->selectRaw('TIMESTAMPDIFF(HOUR, submission_date, hr_verification_date) as hours_diff')
            ->get();

        if ($verificationData->isNotEmpty()) {
            $avgVerificationHours = $verificationData->avg('hours_diff');
            if ($avgVerificationHours > 0) {
                $stats['avg_times']['verification_time'] = $avgVerificationHours;
                $stats['avg_times']['verification_display'] = $this->formatHours($avgVerificationHours);
            }
        }

        // Calculate approval time (HR verification to approval)
        $approvalData = ManpowerRequisition::whereNotNull('approval_date')
            ->whereNotNull('submission_date')
            ->selectRaw('TIMESTAMPDIFF(DAY, submission_date, approval_date) as days_diff')
            ->get();

        if ($approvalData->isNotEmpty()) {
            $avgApprovalDays = $approvalData->avg('days_diff');
            if ($avgApprovalDays !== null && $avgApprovalDays >= 0) {
                $stats['avg_times']['approval_time'] = $avgApprovalDays;
                $stats['avg_times']['approval_display'] = number_format($avgApprovalDays, 1) . 'd';
            }
        } else {
            $stats['avg_times']['approval_display'] = 'N/A';
        }

        // Recent requisitions
        // $recent_requisitions = ManpowerRequisition::with(['submittedBy', 'department', 'function', 'candidate'])
        //     ->orderBy('created_at', 'desc')
        //     ->paginate(10);

        // FOR EACH REQUISITION, LOAD THE AGREEMENT AND COURIER DATA
        foreach ($recent_requisitions as $requisition) {
            if ($requisition->candidate) {
                // Get signed agreement
                $signedAgreement = AgreementDocument::where('candidate_id', $requisition->candidate->id)
                    ->where('document_type', 'agreement')
                    ->where('sign_status', 'SIGNED')
                    ->latest()
                    ->first();

                if ($signedAgreement) {
                    // Get courier details for this agreement
                    $courierDetails = AgreementCourier::where('agreement_document_id', $signedAgreement->id)
                        ->first();

                    // Attach to requisition object for use in view
                    $requisition->signed_agreement = $signedAgreement;
                    $requisition->courier_details = $courierDetails;
                } else {
                    $requisition->signed_agreement = null;
                    $requisition->courier_details = null;
                }
            }
        }

        // Status Distribution for Chart
        $stats['status_distribution'] = ManpowerRequisition::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Active candidates bifurcation by requisition type
        $stats['active_by_type'] = CandidateMaster::select(
            'requisition_type',
            DB::raw('count(*) as count')
        )->where('candidate_status', 'Active')
            ->groupBy('requisition_type')
            ->pluck('count', 'requisition_type')
            ->toArray();

        $today = Carbon::today();

        // =============================
        // ATTENTION PANEL STATS
        // =============================

        $attention = [];

        // Active Candidates
        $attention['active'] = CandidateMaster::where('candidate_status', 'Active')->count();

        // In Process Candidates
        $attention['in_process'] = CandidateMaster::whereIn('candidate_status', [
            'Agreement Pending',
            'Unsigned Agreement Created',
            'Signed Agreement Uploaded'
        ])->count();

        // Delayed Cases (> 3 days in same stage)
        $attention['delayed_cases'] = DB::table('manpower_requisitions')
            ->where('status', 'Pending Approval')
            ->whereDate('submission_date', '<=', now()->subDays(2))
            ->count();
        $avgDelayDays = DB::table('manpower_requisitions')
            ->where('status', 'Pending Approval')
            ->whereNotNull('submission_date')
            ->selectRaw('AVG(DATEDIFF(NOW(), submission_date)) as avg_days')
            ->value('avg_days');

        $avgDelayDays = round($avgDelayDays ?? 0, 1);

        $attention['avg_delay_days'] = $avgDelayDays;

        // Severity classification
        if ($avgDelayDays < 1) {
            $attention['delay_severity'] = '🟢 Low';
            $attention['delay_color'] = 'success';
        } elseif ($avgDelayDays <= 2) {
            $attention['delay_severity'] = '🟡 Medium';
            $attention['delay_color'] = 'warning';
        } else {
            $attention['delay_severity'] = '🔴 High';
            $attention['delay_color'] = 'danger';
        }

        // About to be delayed (2 days pending)
        $attention['about_to_delay'] = DB::table('manpower_requisitions')
            ->where('status', 'Pending Approval')
            ->whereBetween('submission_date', [
                now()->subDays(2),
                now()->subDay()
            ])
            ->count();


        // Agreement Not Signed
        $attention['agreement_not_signed'] = CandidateMaster::where(
            'candidate_status',
            'Unsigned Agreement Created'
        )->count();


        // Courier Pending
        $attention['courier_pending'] = ManpowerRequisition::whereHas(
            'candidate.signedAgreements.courierDetails',
            fn($q) => $q->whereNull('received_date')
        )->count();


        // Contracts expiring soon
        $attention['expiring_3_days'] = CandidateMaster::whereBetween(
            'contract_end_date',
            [now(), now()->addDays(3)]
        )->count();

        $attention['expiring_5_days'] = CandidateMaster::whereBetween(
            'contract_end_date',
            [now(), now()->addDays(5)]
        )->count();

        $attention['expiring_7_days'] = CandidateMaster::whereBetween(
            'contract_end_date',
            [now(), now()->addDays(7)]
        )->count();


        // Avg Req → Active Time
        $avgActiveTime = DB::table('candidate_master as cm')
            ->join('manpower_requisitions as mr', 'cm.requisition_id', '=', 'mr.id')
            ->whereNotNull('cm.contract_start_date')
            ->whereNotNull('mr.submission_date')
            ->whereRaw('cm.contract_start_date >= mr.submission_date')
            ->selectRaw('AVG(DATEDIFF(cm.contract_start_date, mr.submission_date)) as avg_days')
            ->value('avg_days');

        $attention['avg_req_to_active'] = round($avgActiveTime ?? 0, 1);


        // Detect Bottleneck Stage
       $bottleneckStage = DB::selectOne("
SELECT stage, AVG(days) avg_days
FROM (

    SELECT 'Pending HR Verification' stage,
    DATEDIFF(NOW(), submission_date) days
    FROM manpower_requisitions
    WHERE status='Pending HR Verification'

    UNION ALL

    SELECT 'Pending Approval',
    DATEDIFF(NOW(), hr_verification_date)
    FROM manpower_requisitions
    WHERE status='Pending Approval'

    UNION ALL

    SELECT 'Agreement Pending',
    DATEDIFF(NOW(), cm.created_at)
    FROM candidate_master cm
    WHERE cm.candidate_status='Agreement Pending'

    UNION ALL

    SELECT 'Unsigned Agreement Created',
    DATEDIFF(NOW(), ad.created_at)
    FROM agreement_documents ad
    WHERE ad.sign_status='UNSIGNED'

    UNION ALL

    SELECT 'Signed Agreement Uploaded',
    DATEDIFF(NOW(), ad.created_at)
    FROM agreement_documents ad
    WHERE ad.sign_status='SIGNED'

    UNION ALL

    SELECT 'Courier Pending',
    DATEDIFF(NOW(), ac.dispatch_date)
    FROM agreement_couriers ac
    WHERE ac.received_date IS NULL

) stage_times

GROUP BY stage
ORDER BY avg_days DESC
LIMIT 1
");

        $attention['bottleneck_stage'] = $bottleneckStage->stage ?? 'N/A';

$attention['bottleneck_avg_days'] =
    round($bottleneckStage->avg_days ?? 0, 1);

        $days = $attention['bottleneck_avg_days'];

        if ($days < 1) {
            $attention['bottleneck_color'] = 'success';
        } elseif ($days <= 2) {
            $attention['bottleneck_color'] = 'warning';
        } else {
            $attention['bottleneck_color'] = 'danger';
        }


        $fyStart = now()->month >= 4
            ? now()->year . '-04-01'
            : (now()->year - 1) . '-04-01';

        $fyEnd = now()->month >= 4
            ? (now()->year + 1) . '-03-31'
            : now()->year . '-03-31';


        $joiningsChart = DB::table('candidate_master')
            ->selectRaw('MONTH(contract_start_date) as month, COUNT(*) as total')
            ->whereNotNull('contract_start_date')
            ->whereBetween('contract_start_date', [$fyStart, $fyEnd])
            ->groupBy('month')
            ->pluck('total', 'month');

        $expiry = [

            'lt_30_days' => CandidateMaster::whereNotNull('contract_end_date')
                ->whereBetween('contract_end_date', [$today, $today->copy()->addDays(30)])
                ->orderBy('contract_end_date')
                ->paginate(10, ['*'], 'lt30_page'),

            'days_30_60' => CandidateMaster::whereNotNull('contract_end_date')
                ->whereBetween('contract_end_date', [$today->copy()->addDays(31), $today->copy()->addDays(60)])
                ->orderBy('contract_end_date')
                ->paginate(10, ['*'], 'd30_page'),

            'days_60_90' => CandidateMaster::whereNotNull('contract_end_date')
                ->whereBetween('contract_end_date', [$today->copy()->addDays(61), $today->copy()->addDays(90)])
                ->orderBy('contract_end_date')
                ->paginate(10, ['*'], 'd60_page'),

        ];

        $tabCounts = [
            'submission' => ManpowerRequisition::where('status', 'Pending HR Verification')->count(),

            'correction_required' => ManpowerRequisition::where('status', 'Correction Required')->count(),

            'hr_verified' => ManpowerRequisition::where('status', 'Hr Verified')->count(),

            'approval' => ManpowerRequisition::where('status', 'Pending Approval')->count(),

            'approved' => ManpowerRequisition::where('status', 'Approved')->count(),

            'unsigned' => ManpowerRequisition::whereHas('candidate', function ($q) {
                $q->where('candidate_status', 'Unsigned Agreement Created');
            })->count(),

            'dispatch_pending' => ManpowerRequisition::whereHas('candidate', function ($q) {
                $q->where('candidate_status', 'Signed Agreement Uploaded')
                    ->whereHas('signedAgreements', function ($q2) {
                        $q2->whereDoesntHave('courierDetails');
                    });
            })->count(),
            'courier_pending' => ManpowerRequisition::whereHas(
                'candidate.signedAgreements.courierDetails',
                function ($q) {
                    $q->whereNull('received_date');
                }
            )->count(),

            'file_pending' => ManpowerRequisition::whereHas('candidate', function ($q) {
                $q->where('candidate_status', 'Signed Agreement Uploaded')
                    ->whereNull('file_created_date')
                    ->whereHas('signedAgreements.courierDetails', function ($q2) {
                        $q2->whereNotNull('received_date');
                    });
            })->count(),

            'active' => CandidateMaster::where('candidate_status', 'Active')->count(),

            'inactive' => CandidateMaster::where('candidate_status', 'Inactive')->count(),

            'rejected' => ManpowerRequisition::where('status', 'Rejected')->count()
        ];

        return view('dashboard.hr-admin', compact('stats', 'recent_requisitions', 'expiry', 'tabCounts', 'attention', 'joiningsChart'))->with(['req_tab' => $reqTab, 'exp_tab' => $expTab]);
    }

    // ADD THIS HELPER METHOD TO YOUR CONTROLLER:
    private function formatHours($hours)
    {
        if (!$hours || $hours <= 0) {
            return 'N/A';
        }

        $days = $hours / 24;

        return number_format($days, 1) . 'd';
    }

    private function userDashboard($user, Request $request)
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

        $tab = $request->get('req_tab', 'status');
        $expTab = $request->get('exp_tab', 'exp30');
        $query = ManpowerRequisition::with(['submittedBy', 'candidate'])
            ->where(function ($q) use ($user, $isApprover) {

                $q->where('submitted_by_user_id', $user->id);

                if ($isApprover) {
                    $q->orWhere('approver_id', $user->emp_id);
                }
            });


        switch ($tab) {

            case 'active':

                $query->whereHas('candidate', function ($q) {
                    $q->where('candidate_status', 'Active');
                });

                break;


            case 'inactive':

                $query->whereHas('candidate', function ($q) {
                    $q->where('candidate_status', 'Inactive');
                });

                break;


            case 'rejected':

                $query->where(function ($q) {

                    $q->where('status', 'Rejected')
                        ->orWhereHas('candidate', function ($sub) {
                            $sub->where('candidate_status', 'Rejected');
                        });
                });

                break;


            case 'status':
            default:

                $query->where(function ($q) {

                    // workflow items without candidate
                    $q->whereDoesntHave('candidate')

                        // OR candidate exists but not Active/Inactive/Rejected
                        ->orWhereHas('candidate', function ($sub) {
                            $sub->whereNotIn('candidate_status', [
                                'Active',
                                'Inactive',
                                'Rejected'
                            ]);
                        });
                })

                    // IMPORTANT: also exclude rejected requisitions
                    ->where('status', '!=', 'Rejected');

                break;
        }


        $data['recent_requisitions'] = $query
            ->latest()
            ->paginate(10)
            ->appends(['req_tab' => $tab, 'exp_tab' => $expTab]);

        $data['tab'] = $tab;


        // $data['recent_requisitions'] = ManpowerRequisition::with(['submittedBy', 'candidate'])
        //     ->where(function ($query) use ($user, $isApprover) {
        //         $query->where('submitted_by_user_id', $user->id);

        //         if ($isApprover) {
        //             $query->orWhere('approver_id', $user->emp_id);
        //         }
        //     })
        //     ->orderBy('created_at', 'desc')
        //     ->paginate(10);


        // dd($data);
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
                        ->orWhere('status', 'Unsigned Agreement Created')
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
                ->where('status', 'Unsigned Agreement Created')->count(),
            'agreement_completed' => ManpowerRequisition::where('submitted_by_user_id', $user->id)
                ->where('status', 'Agreement Completed')->count(),
            'processed' => ManpowerRequisition::where('submitted_by_user_id', $user->id)
                ->where('status', 'Processed')->count(),
        ];

        $today = Carbon::today();
        //dd($user->emp_id);
        $data['expiry'] = [

            'lt_30_days' => CandidateMaster::whereNotNull('contract_end_date')
                ->where('reporting_manager_employee_id', $user->emp_id)
                ->whereBetween('contract_end_date', [$today, $today->copy()->addDays(30)])
                ->orderBy('contract_end_date')
                ->paginate(10, ['*'], 'lt30_page'),

            'days_30_60' => CandidateMaster::whereNotNull('contract_end_date')
                ->where('reporting_manager_employee_id', $user->emp_id)
                ->whereBetween('contract_end_date', [$today->copy()->addDays(31), $today->copy()->addDays(60)])
                ->orderBy('contract_end_date')
                ->paginate(10, ['*'], 'd30_page'),

            'days_60_90' => CandidateMaster::whereNotNull('contract_end_date')
                ->where('reporting_manager_employee_id', $user->emp_id)
                ->whereBetween('contract_end_date', [$today->copy()->addDays(61), $today->copy()->addDays(90)])
                ->orderBy('contract_end_date')
                ->paginate(10, ['*'], 'd90_page'),

        ];

        $data['is_approver'] = $isApprover;
        $data['req_tab'] = $tab;
        $data['exp_tab'] = $expTab;
        return view('dashboard.user', $data);
    }
}
