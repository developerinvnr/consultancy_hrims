<?php

namespace App\Http\Controllers;

use App\Models\CandidateMaster;
use App\Models\ManpowerRequisition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\PanVerificationService;

class CandidateController extends Controller
{
    public function deactivate(Request $request, CandidateMaster $candidate)
    {
        $request->validate([
            'last_working_date' => 'required|date|before_or_equal:today',
        ]);

        // Authorization: only creator can deactivate
        $requisition = ManpowerRequisition::findOrFail($candidate->requisition_id);

        if ($requisition->submitted_by_user_id !== Auth::id() && !Auth::user()->hasRole('hr_admin')) {
            abort(403, 'You are not allowed to deactivate this team member.');
        }
        // Only Active candidates can be deactivated
        if ($candidate->candidate_status !== 'Active') {
            return back()->with('error', 'This candidate is not active.');
        }

        DB::transaction(function () use ($candidate, $requisition, $request) {

            // Update candidate
            $candidate->update([
                'last_working_date' => $request->last_working_date,
                'candidate_status'  => 'Inactive',
                'final_status'      => 'D',
                'updated_by_user_id' => Auth::id(),
            ]);

            // Update requisition
            $requisition->update([
                'last_working_date' => $request->last_working_date,
                'status'            => 'Inactive',
            ]);
        });

        return redirect()
            ->route('dashboard')
            ->with('success', 'Team member deactivated successfully.');
    }

    public function verifyPan(Request $request)
    {
        $request->validate([
            'pan_no' => 'required|string|max:10',
            'candidate_id' => 'required'
        ]);

        $panData = PanVerificationService::verify($request->pan_no);

        if (!$panData) {

            return response()->json([
                'success' => false,
                'message' => 'PAN verification failed'
            ]);
        }

        // Update candidate table
        CandidateMaster::where('id', $request->candidate_id)
            ->update([
                'pan_status_2' => $panData['individual_tax_compliance_status'] ?? null,
                'pan_verification_status' => $panData['is_valid'] ? 'Valid' : 'Invalid',
                'pan_aadhaar_link_status' => $panData['aadhaar_seeding_status'] ?? null,
            ]);

        return response()->json([
            'success' => true,
            'pan_status' => $panData['individual_tax_compliance_status'] ?? null,
            'aadhaar_status' => $panData['aadhaar_seeding_status'] ?? null
        ]);
    }
}
