<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryProcessing extends Model
{
    protected $fillable = [
        'candidate_id', 'month', 'year',
        'monthly_salary', 'per_day_salary','total_days',
        'paid_days', 'cl_days', 'absent_days',
        'approved_sundays',
        'deduction_amount', 'extra_amount', 'net_pay',
        'status', 'processed_by', 'processed_at'
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