<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequisitionVerification extends Model
{
    protected $table = 'requisition_verifications';
    
    protected $fillable = [
        'requisition_id',
        'section',
        'verified_by_user_id',
        'verified_by_role',
        'verification_date',
        'remarks'
    ];
    
    protected $casts = [
        'verification_date' => 'datetime'
    ];
    
    /**
     * Relation with requisition
     */
    public function requisition()
    {
        return $this->belongsTo(ManpowerRequisition::class, 'requisition_id');
    }
    
    /**
     * Relation with user who verified
     */
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }
}