<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CandidateMaster;
use App\Models\SalaryProcessing;
use App\Services\SalaryCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalaryExport;

class SalaryController extends Controller
{
    public function index()
    {
        return view('hr.salary.index');
    }

    public function process(Request $request)
    {
        // Convert request data
        $requestData = $request->all();
        
        // Convert force to boolean properly
        if (isset($requestData['force'])) {
            $requestData['force'] = filter_var($requestData['force'], FILTER_VALIDATE_BOOLEAN);
        }
        
        // Validate the request
        $validated = $this->validate($request, [
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'force' => 'sometimes|boolean',
            'candidate_id' => 'sometimes|integer|exists:candidate_master,id',
            'candidate_ids' => 'sometimes|array',
            'candidate_ids.*' => 'sometimes|integer|exists:candidate_master,id',
        ]);

        $month = (int) $validated['month'];
        $year  = (int) $validated['year'];
        $force = $validated['force'] ?? false;

        // Build query
        $query = CandidateMaster::where('final_status', 'A');

        if ($request->filled('candidate_id')) {
            $query->where('id', $validated['candidate_id']);
        }
        
        if ($request->filled('candidate_ids')) {
            $query->whereIn('id', $validated['candidate_ids']);
        }

        $candidates = $query->get();
        
        if ($candidates->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active candidates found'
            ], 404);
        }

        $processedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($candidates as $candidate) {
            try {
                // Check if already processed (unless force)
                $existing = SalaryProcessing::where('candidate_id', $candidate->id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->first();

                if ($existing && !$force) {
                    $skippedCount++;
                    continue; // Skip already processed
                }

                // Calculate salary
                $data = SalaryCalculator::calculate($candidate, $month, $year);

                // Create or update salary record
                SalaryProcessing::updateOrCreate(
                    [
                        'candidate_id' => $candidate->id,
                        'month'        => $month,
                        'year'         => $year,
                    ],
                    array_merge($data, [
                        'processed_by' => auth()->id(),
                        'processed_at' => now(),
                    ])
                );

                $processedCount++;

            } catch (\Exception $e) {
                $errors[] = [
                    'candidate_code' => $candidate->candidate_code,
                    'candidate_name' => $candidate->candidate_name,
                    'error' => $e->getMessage()
                ];
                \Log::error("Salary calculation failed for {$candidate->candidate_code}: " . $e->getMessage(), [
                    'month' => $month,
                    'year' => $year,
                    'candidate_id' => $candidate->id
                ]);
            }
        }

        // Build response message
        $message = "Salary processing completed. ";
        $message .= "Processed: {$processedCount}, ";
        $message .= "Skipped (already processed): {$skippedCount}";
        
        if ($force && $skippedCount > 0) {
            $message .= " (forced recalculation)";
        }
        
        if (!empty($errors)) {
            $message .= ". Failed: " . count($errors);
        }

        $response = [
            'success' => true,
            'message' => $message,
            'processed' => $processedCount,
            'skipped' => $skippedCount,
            'total_candidates' => $candidates->count()
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response);
    }

    public function list(Request $request)
    {
        $request->merge([
            'month' => (int) $request->month,
            'year'  => (int) $request->year,
        ]);

        $request->validate([
            'month' => 'required|integer',
            'year'  => 'required|integer',
        ]);

        $month = $request->month;
        $year  = $request->year;

        $candidates = CandidateMaster::where('final_status', 'A')->get();

        $result = [];

        foreach ($candidates as $candidate) {
            // Check if already processed
            $salary = SalaryProcessing::where('candidate_id', $candidate->id)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            if ($salary) {
                // Already processed → use saved data
                $row = $salary->toArray();
                $row['candidate'] = $candidate;
                $row['processed'] = true;
            } else {
                // Not processed → calculate preview
                try {
                    $calc = SalaryCalculator::calculate($candidate, $month, $year);

                    $row = array_merge($calc, [
                        'candidate_id' => $candidate->id,
                        'candidate'    => $candidate,
                        'processed'    => false,
                    ]);
                } catch (\Exception $e) {
                    // Attendance missing etc.
                    $row = [
                        'candidate_id' => $candidate->id,
                        'candidate'    => $candidate,
                        'error'        => $e->getMessage(),
                        'processed'    => false
                    ];
                }
            }

            $result[] = $row;
        }

        return response()->json($result);
    }

    public function checkExists(Request $request)
    {
        $request->validate([
            'month' => 'required|integer',
            'year'  => 'required|integer',
        ]);

        $count = SalaryProcessing::where('month', $request->month)
            ->where('year', $request->year)
            ->count();

        return response()->json([
            'exists' => $count > 0,
            'count'  => $count
        ]);
    }

    public function downloadPayslip($id)
    {
        $salary = SalaryProcessing::with('candidate')->findOrFail($id);

        $pdf = Pdf::loadView('hr.salary.payslip', compact('salary'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Payslip_{$salary->candidate->candidate_code}_{$salary->month}_{$salary->year}.pdf");
    }

    public function exportExcel(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer|min:2020',
        ]);

        return Excel::download(
            new SalaryExport($request->month, $request->year),
            "Payroll_{$request->month}-{$request->year}.xlsx"
        );
    }
}