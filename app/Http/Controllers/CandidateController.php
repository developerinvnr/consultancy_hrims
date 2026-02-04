<?php

namespace App\Http\Controllers;

use App\Models\CandidateMaster;
use App\Models\ManpowerRequisition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CandidateController extends Controller
{
    public function deactivate(Request $request, CandidateMaster $candidate)
    {
        $request->validate([
            'last_working_date' => 'required|date|before_or_equal:today',
        ]);

        // Authorization: only creator can deactivate
        $requisition = ManpowerRequisition::findOrFail($candidate->requisition_id);

        if ($requisition->submitted_by_user_id !== Auth::id()) {
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
                'updated_by_user_id'=> Auth::id(),
            ]);

            // Update requisition
            $requisition->update([
                'last_working_date' => $request->last_working_date,
                'status'            => 'Completed',
            ]);
        });

        return redirect()
            ->route('dashboard')
            ->with('success', 'Team member deactivated successfully.');
    }
}
