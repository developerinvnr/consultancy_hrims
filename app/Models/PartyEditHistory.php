<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartyEditHistory extends Model
{
    protected $table = 'party_edit_histories';
    
    protected $fillable = [
        'candidate_id',
        'field_name',
        'old_value',
        'new_value',
        'changed_by_user_id',
        'reason',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
    ];
    
    public function candidate()
    {
        return $this->belongsTo(CandidateMaster::class, 'candidate_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}