<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ManpowerRequisition extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'manpower_requisitions';

    protected $fillable = [
        'request_code',
        'requisition_type',
        'submitted_by_user_id',
        'submitted_by_name',
        'submitted_by_employee_id',
        'submission_date',
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
        'district',
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
        'contract_start_date',
        'contract_duration',
        'contract_end_date',
        'remuneration_per_month',
        'fuel_reimbursement_per_month',
        'reporting_manager_address',
        'account_holder_name',
        'bank_account_no',
        'bank_ifsc',
        'bank_name',
        'pan_no',
        'aadhaar_no',
        'status',
        'hr_verification_date',
        'hr_verification_remarks',
        'hr_verified_id',
        'approver_id',
        'approval_date',
        'approver_remarks',
        'rejection_date',
        'rejection_reason',
        'rejected_by_user_id',
        'processing_date',
        'team_id',
        'other_reimbursement',
        'other_reimbursement_remark',
        'out_of_pocket_expense',
        'last_working_date',
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
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'remuneration_per_month' => 'decimal:2',
        'fuel_reimbursement_per_month' => 'decimal:2',
        'hr_verification_date' => 'datetime',
        'approval_date' => 'datetime',
        'rejection_date' => 'datetime',
        'processing_date' => 'datetime',
        'submission_date' => 'datetime',
        'last_working_date' => 'date',
    ];

    // Relationships
    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function function()
    {
        return $this->belongsTo(\App\Models\CoreFunction::class, 'function_id');
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\CoreDepartment::class, 'department_id');
    }

    public function vertical()
    {
        return $this->belongsTo(\App\Models\CoreVertical::class, 'vertical_id');
    }

    public function currentApprover()
    {
        return $this->belongsTo(User::class, 'approver_id', 'emp_id');
    }

    // public function employeeGeneral()
    // {
    //     return $this->belongsTo(\App\Models\HrmEmployeeGeneral::class, 'submitted_by_employee_id', 'EmployeeID');
    // }

    public function documents()
    {
        return $this->hasMany(RequisitionDocument::class, 'requisition_id');
    }

    // Generate Requisition ID
    public static function generateRequestCode($type)
    {
        // Define prefixes
        $prefix = match ($type) {
            'Contractual' => 'CRS',
            'TFA'         => 'TFA-',
            'CB'          => 'CBS',
            default       => 'REQ'
        };

        // Count records only for that type
        $lastRecord = static::where('requisition_type', $type)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastRecord) {
            // Extract numeric part
            preg_match('/(\d+)$/', $lastRecord->request_code, $matches);
            $nextNumber = isset($matches[1]) ? ((int)$matches[1] + 1) : 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }


    public function hrVerifier()
    {
        return $this->belongsTo(User::class, 'hr_verified_id');
    }

    public function candidate()
    {
        return $this->hasOne(CandidateMaster::class, 'requisition_id', 'id');
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

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function getWorkflowStatusAttribute()
    {
        if (!$this->candidate) {
            return $this->status;
        }

        $candidate = $this->candidate;

        // normalize short codes
        if ($candidate->candidate_status == 'A') {
            return 'Active';
        }

        if ($candidate->candidate_status == 'D') {
            return 'Inactive';
        }

        if ($candidate->candidate_status == 'Unsigned Agreement Created') {
            return 'Agreement Upload Pending';
        }

        $signedAgreement = $candidate->signedAgreements()->latest()->first();

        if ($signedAgreement && !$signedAgreement->courierDetails) {
            return 'Pending Dispatch';
        }

        if (
            $signedAgreement &&
            $signedAgreement->courierDetails &&
            !$signedAgreement->courierDetails->received_date
        ) {
            return 'Courier Pending';
        }

        if (
            $signedAgreement &&
            $signedAgreement->courierDetails &&
            $signedAgreement->courierDetails->received_date &&
            !$candidate->file_created_date
        ) {
            return 'File Creation Pending';
        }

        return $candidate->candidate_status;
    }
}
