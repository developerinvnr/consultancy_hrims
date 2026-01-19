<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $table = 'leave_balance';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'CandidateID', 'calendar_year', 'opening_cl_balance',
        'cl_utilized', 'lwp_days_accumulated', 'contract_start_date',
        'contract_end_date'
    ];
    
    protected $appends = ['cl_remaining'];
    
    /**
     * Get the calculated CL remaining
     */
    public function getClRemainingAttribute()
    {
        return $this->opening_cl_balance - $this->cl_utilized;
    }
    
    /**
     * Get the candidate associated with the leave balance
     */
    public function candidate()
    {
        return $this->belongsTo(CandidateMaster::class, 'CandidateID', 'id');
    }
}