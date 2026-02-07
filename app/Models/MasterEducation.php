<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterEducation extends Model
{
    protected $table = 'master_education';

    protected $primaryKey = 'EducationId';

    public $timestamps = false;

    protected $fillable = [
        'EducationName',
        'EducationCode',
        'EducationType',
        'IsDeleted',
        'Status',
    ];
}
