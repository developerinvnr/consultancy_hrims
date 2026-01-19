<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreState extends Model
{
    protected $table = 'core_state';
    
    protected $fillable = [
        'state_name',
        'state_code',
        'short_code',
        'effective_date',
        'is_active'
    ];
}