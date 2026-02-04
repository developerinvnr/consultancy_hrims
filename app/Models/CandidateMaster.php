<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'reporting_manager_address',
        'contract_start_date',
        'contract_duration',
        'contract_end_date',
        'remuneration_per_month',
        'fuel_reimbursement_per_month',
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
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'remuneration_per_month' => 'decimal:2',
        'fuel_reimbursement_per_month' => 'decimal:2',
        'external_created_at' => 'datetime',
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

    public function unsignedAgreement()
    {
        return $this->hasOne(AgreementDocument::class, 'candidate_id')
            ->where('document_type', 'unsigned');
    }

    public function signedAgreement()
    {
        return $this->hasOne(AgreementDocument::class, 'candidate_id')
            ->where('document_type', 'signed');
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
}
