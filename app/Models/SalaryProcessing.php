<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryProcessing extends Model
{
    protected $table = 'salary_processings';

    protected $fillable = [
        'candidate_id', 'month', 'year',
        'monthly_salary', 'per_day_salary','total_days',
        'paid_days', 'cl_days', 'absent_days',
        'approved_sundays',
        'deduction_amount', 'extra_amount', 'net_pay',
        'arrear_amount', 'arrear_days', 'arrear_remarks',
        'status', 'processed_by', 'processed_at'
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'month' => 'integer',
        'year' => 'integer',
        'monthly_salary' => 'float',
        'per_day_salary' => 'float',
        'paid_days' => 'float',
        'deduction_amount' => 'float',
        'extra_amount' => 'float',
        'net_pay' => 'float',
        'arrear_amount' => 'float',
        'arrear_days' => 'float',
    ];

    public function candidate()
    {
        return $this->belongsTo(CandidateMaster::class, 'candidate_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}