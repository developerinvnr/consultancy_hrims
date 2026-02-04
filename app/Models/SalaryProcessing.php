<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryProcessing extends Model
{
    // Explicitly set the table name
    protected $table = 'salary_processings';
    
    protected $fillable = [
        'candidate_id', 'month', 'year',
        'monthly_salary', 'per_day_salary','total_days',
        'paid_days', 'cl_days', 'absent_days',
        'approved_sundays',
        'deduction_amount', 'extra_amount', 'net_pay',
        'status', 'processed_by', 'processed_at'
    ];

    // Optional: If you want to customize the primary key
    protected $primaryKey = 'id';

    // Optional: If your primary key is not auto-incrementing
    public $incrementing = true;

    // Optional: If you don't have timestamps in your table
    public $timestamps = true;

    // Optional: Define date columns
    protected $dates = ['processed_at'];

    public function candidate()
    {
        return $this->belongsTo(CandidateMaster::class, 'candidate_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}