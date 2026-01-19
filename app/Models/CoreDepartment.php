<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoreDepartment extends Model
{
    use HasFactory;

    protected $table = 'core_department';

    protected $fillable = [
        'department_code',
        'department_name',
        'effective_date',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function function()
    {
        return $this->belongsTo(CoreFunction::class, 'function_id');
    }

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
        return $this->hasMany(ManpowerRequisition::class, 'department_id');
    }
}
