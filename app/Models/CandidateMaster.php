<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;


class CandidateMaster extends Model
{
    use SoftDeletes;

    protected $table = 'candidate_master';

    protected $fillable = [
        'candidate_code',
        'requisition_id',
        'requisition_type',
        'candidate_email',
        'candidate_name',
        'father_name',
        'mobile_no',
        'alternate_email',
        'address_line_1',
        'city',
        'state_residence',
        'pin_code',
        'date_of_birth',
        'gender',
        'highest_qualification',
        'college_name',
        'work_location_hq',
        'work_location_id',
        'district',
        'district_id',
        'state_work_location',
        'function_id',
        'department_id',
        'vertical_id',
        'sub_department',
        'business_unit',
        'zone',
        'region',
        'territory',
        'reporting_to',
        'reporting_manager_employee_id',
        'reporting_manager_address',
        'contract_start_date',
        'contract_duration',
        'file_created_date',
        'contract_end_date',
        'last_working_date',
        'contract_cancelled_at',
        'contract_cancelled_by',
        'contract_cancellation_reason',
        'remuneration_per_month',
        'contract_amount',
        'account_holder_name',
        'bank_account_no',
        'bank_ifsc',
        'bank_name',
        'pan_no',
        'aadhaar_no',
        'candidate_status',
        'final_status',
        'leave_credited',
        'external_reference_id',
        'external_created_at',
        'external_response',
        'created_by_user_id',
        'updated_by_user_id',
        'other_reimbursement_required',
        'out_of_pocket_required',
        'bank_verification_status',
        'bank_branch_address',
        'pan_verification_status',
        'pan_aadhaar_link_status',
        'pan_status_2',
        'driving_licence_no',
        'dl_valid_from',
        'dl_valid_to',
        'dl_verification_status',
        'aadhaar_verification_status',
        'ledger_created',
        'ledger_created_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'remuneration_per_month' => 'decimal:2',
        'external_created_at' => 'datetime',
        'last_working_date' => 'date',
        'contract_cancelled_at' => 'date',
        'ledger_created' => 'boolean',
        'ledger_created_at' => 'datetime',
    ];

    // ADD THIS RELATIONSHIP
    public function requisition()
    {
        return $this->belongsTo(ManpowerRequisition::class, 'requisition_id');
    }

    public function agreementDocuments()
    {
        return $this->hasMany(AgreementDocument::class, 'candidate_id');
    }

    public function unsignedAgreements()
    {
        return $this->hasMany(AgreementDocument::class, 'candidate_id')
            ->where('document_type', 'agreement')
            ->where('sign_status', 'UNSIGNED');
    }

    public function signedAgreements()
    {
        return $this->hasMany(AgreementDocument::class, 'candidate_id')
            ->where('document_type', 'agreement')
            ->where('sign_status', 'SIGNED');
    }

    public function salaryProcessings()
    {
        return $this->hasMany(SalaryProcessing::class, 'candidate_id');
    }

    public function businessUnit()
    {
        return $this->belongsTo(CoreBusinessUnit::class, 'business_unit', 'id');
    }

    public function zoneRef()
    {
        return $this->belongsTo(CoreZone::class, 'zone', 'id');
    }

    public function regionRef()
    {
        return $this->belongsTo(CoreRegion::class, 'region', 'id');
    }

    public function territoryRef()
    {
        return $this->belongsTo(CoreTerritory::class, 'territory', 'id');
    }

    public function subDepartmentRef()
    {
        return $this->belongsTo(CoreSubDepartment::class, 'sub_department', 'id');
    }


    public function department()
    {
        // Check if you have a departments table
        return $this->belongsTo(CoreDepartment::class, 'department_id', 'id');
    }



    public function function()
    {
        return $this->belongsTo(CoreFunction::class, 'function_id', 'id');
    }

    /**
     * Relationship with Vertical (assuming you have a verticals table)
     */
    public function vertical()
    {
        return $this->belongsTo(CoreVertical::class, 'vertical_id', 'id');
    }

    /**
     * Get salary for specific month and year
     */
    public function salaryForSpecificMonth($month, $year)
    {
        return $this->hasOne(SalaryProcessing::class, 'candidate_id')
            ->where('month', $month)
            ->where('year', $year);
    }


    // City
    public function cityMaster()
    {
        return $this->belongsTo(\App\Models\CoreCityVillage::class, 'city');
    }

    // State (Residence)
    public function residenceState()
    {
        return $this->belongsTo(\App\Models\CoreState::class, 'state_residence');
    }

    // State (Work Location)
    public function workState()
    {
        return $this->belongsTo(\App\Models\CoreState::class, 'state_work_location');
    }

    // Highest Qualification
    public function qualification()
    {
        return $this->belongsTo(\App\Models\MasterEducation::class, 'highest_qualification', 'EducationId');
    }

    public function editHistory()
    {
        return $this->hasMany(\App\Models\PartyEditHistory::class, 'candidate_id');
    }


    public function reportingManager()
    {
        return $this->belongsTo(Employee::class, 'reporting_manager_employee_id', 'employee_id')
            ->where('company_id', 1);
    }

    public function getFormattedAddressAttribute()
    {
        $parts = [];

        // Father Name
        if (!empty($this->father_name)) {
            $parts[] = 'S/O-' . $this->father_name;
        }

        // Address Line
        if (!empty($this->address_line_1)) {
            $parts[] = $this->address_line_1;
        }

        // City
        if ($this->cityMaster && $this->cityMaster->city_village_name) {
            $parts[] = $this->cityMaster->city_village_name;
        }

        // District
        if (!empty($this->district)) {
            $parts[] = $this->district;
        }

        // State
        if ($this->workState && $this->workState->state_name) {
            $parts[] = $this->workState->state_name;
        }

        // Pin Code
        if (!empty($this->pin_code)) {
            $parts[] = 'Pin Code -' . $this->pin_code;
        }

        return implode(', ', $parts);
    }

    public function workLocation()
    {
        return $this->belongsTo(\App\Models\CoreCityVillage::class, 'work_location_id', 'id');
    }

    public function pendingAttendanceDates()
    {
        $attendance = \DB::table('attendance')
            ->where('candidate_id', $this->id)
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->first();

        if (!$attendance) {
            return [];
        }

        $pendingDates = [];

        $today = now();

        $contractStartDate = $this->contract_start_date;
        $contractEndDate   = $this->contract_end_date;

        // If contract not started yet → return empty
        if ($contractStartDate && $contractStartDate->gt($today)) {
            return [];
        }

        // First & last day of month
        $firstDayOfMonth = $today->copy()->startOfMonth();
        $lastDayOfMonth  = $today->copy()->endOfMonth();

        // Determine active start date
        $activeStart = $firstDayOfMonth;

        if ($contractStartDate && $contractStartDate->gt($firstDayOfMonth)) {
            $activeStart = $contractStartDate->copy();
        }

        // Determine active end date
        //$activeEnd = $today;
        $activeEnd = $today->copy()->subDay();


        if ($contractEndDate && $contractEndDate->lt($activeEnd)) {
            $activeEnd = $contractEndDate->copy();
        }

        // If candidate not active in this range
        if ($activeStart->gt($activeEnd)) {
            return [];
        }

        $currentCheckDate = $activeStart->copy();

        while ($currentCheckDate->between($activeStart, $activeEnd)) {

            // Skip Sundays
            if ($currentCheckDate->isSunday()) {
                $currentCheckDate->addDay();
                continue;
            }

            $day = $currentCheckDate->day;
            $column = 'A' . $day;

            if (empty($attendance->$column)) {
                $pendingDates[] = $currentCheckDate->toDateString();
            }

            $currentCheckDate->addDay();
        }

        return $pendingDates;
    }
}
