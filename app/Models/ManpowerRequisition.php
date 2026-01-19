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
        'requisition_id',
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
        'date_of_joining_required',
        'agreement_duration',
        'date_of_separation',
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
        'processing_date',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_of_joining_required' => 'date',
        'date_of_separation' => 'date',
        'remuneration_per_month' => 'decimal:2',
        'fuel_reimbursement_per_month' => 'decimal:2',
        'hr_verification_date' => 'datetime',
        'approval_date' => 'datetime',
        'rejection_date' => 'datetime',
        'processing_date' => 'datetime',
        'submission_date' => 'datetime',
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
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function employeeGeneral()
    {
        return $this->belongsTo(\App\Models\HrmEmployeeGeneral::class, 'submitted_by_employee_id', 'EmployeeID');
    }

    public function documents()
    {
        return $this->hasMany(RequisitionDocument::class, 'requisition_id');
    }

    // Generate Requisition ID
    public static function generateRequisitionId($type)
    {
        $prefix = match ($type) {
            'Contractual' => 'CON',
            'TFA' => 'TFA',
            'CB' => 'CB',
            default => 'REQ'
        };

        $year = date('Y');
        $month = date('m');
        $sequence = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;

        return $prefix . '-' . $year . $month . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function hrVerifier()
    {
        return $this->belongsTo(User::class, 'hr_verified_id');
    }
}
