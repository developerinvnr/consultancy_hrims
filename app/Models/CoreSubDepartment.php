<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreSubDepartment extends Model
{
    protected $table = 'core_sub_department';

    protected $fillable = [
        'sub_department_name',
        'sub_department_code',
        'numeric_code',
        'effective_date',
        'is_active',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Optional: reverse relation
    public function candidates()
    {
        return $this->hasMany(CandidateMaster::class, 'sub_department', 'id');
    }
}
