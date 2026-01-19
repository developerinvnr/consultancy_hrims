<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SundayWorkRequest extends Model
{
    protected $table = 'sunday_work_requests';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'candidate_id', 'month', 'year', 'sunday_date', 'work_hours',
        'rate_multiplier', 'daily_rate', 'amount', 'remark', 'attachment_path',
        'requested_by', 'approved_by', 'status', 'approved_at'
    ];
    
    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'work_hours' => 'decimal:2',
        'rate_multiplier' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime'
    ];
    
    /**
     * Get the candidate associated with the request
     */
    public function candidate()
    {
        return $this->belongsTo(CandidateMaster::class, 'candidate_id', 'id');
    }
    
    /**
     * Get the user who requested
     */
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by', 'id');
    }
    
    /**
     * Get the user who approved
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }
    
    /**
     * Get the attachment URL
     */
    public function getAttachmentUrlAttribute()
    {
        if ($this->attachment_path) {
            return Storage::url($this->attachment_path);
        }
        return null;
    }
}