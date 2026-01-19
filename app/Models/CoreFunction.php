<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoreFunction extends Model
{
    use HasFactory ;

    protected $table = 'core_org_function';

    protected $fillable = [
        'function_code',
        'function_name',
        'effective_date',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function requisitions()
    {
        return $this->hasMany(ManpowerRequisition::class, 'function_id');
    }
}
