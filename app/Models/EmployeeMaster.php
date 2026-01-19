<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeMaster extends Model
{
    use SoftDeletes;
    
    protected $table = 'employee_master';
    
    protected $fillable = [
        'employee_code',
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
        'date_of_joining',
        'agreement_duration',
        'date_of_separation',
        'remuneration_per_month',
        'fuel_reimbursement_per_month',
        'account_holder_name',
        'bank_account_no',
        'bank_ifsc',
        'bank_name',
        'pan_no',
        'aadhaar_no',
        'employment_status',
        'agri_samvida_reference_id',
        'agri_samvida_created_at',
        'agri_samvida_response',
        'created_by_user_id',
        'updated_by_user_id'
    ];
    
    protected $casts = [
        'date_of_birth' => 'date',
        'date_of_joining' => 'date',
        'date_of_separation' => 'date',
        'remuneration_per_month' => 'decimal:2',
        'fuel_reimbursement_per_month' => 'decimal:2',
        'agri_samvida_created_at' => 'datetime'
    ];
}