<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgreementDocument extends Model
{
    protected $table = 'agreement_documents';

    protected $fillable = [
        'candidate_id',
        'candidate_code',
        'document_type', // 'unsigned', 'signed'
        'agreement_number',
        'agreement_path',
        'file_url',
        'uploaded_by_user_id',
        'uploaded_by_role', // 'hr_admin', 'submitter'
    ];
    
    public function candidate()
    {
        return $this->belongsTo(CandidateMaster::class, 'candidate_id');
    }
    
    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
    
    // Get full S3 URL
    public function getFullPathAttribute()
    {
        return 'https://s3.ap-south-1.amazonaws.com/developerinvnr.bkt/' . $this->agreement_path;
    }
}