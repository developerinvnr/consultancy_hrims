<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // 👈 add this

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles; // 👈 add HasRoles here

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'emp_id',
        'emp_code',
        'reporting_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'created_at' => 'datetime',
             'updated_at' => 'datetime',
        ];
    }
}
