<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'core_employee';

    // Primary key is varchar, so we override defaults
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'company_name',
        'employee_id',
        'emp_code',
        'emp_name',
        'emp_status',
        'emp_reporting',
        'emp_doj',
        'emp_email',
        'emp_contact',
        'emp_gender',
        'emp_title',
        'emp_married',
        'emp_function',
        'emp_vertical',
        'emp_department',
        'emp_sub_department',
        'emp_section',
        'emp_designation',
        'emp_grade',
        'emp_territory',
        'emp_region',
        'emp_zone',
        'emp_bu',
        'emp_state',
        'emp_city',
        'department',
        'sub_department',
        'designation',
        'grade',
        'bu',
        'zone',
        'region',
        'territory',
    ];

    /**
     * Relations
     */
    public function territory()
    {
        return $this->belongsTo(CoreTerritory::class, 'emp_territory');
    }

    public function region()
    {
        return $this->belongsTo(CoreRegion::class, 'emp_region');
    }

    public function zone()
    {
        return $this->belongsTo(CoreZone::class, 'zone');
    }

    public function bu()
    {
        return $this->belongsTo(CoreBusinessUnit::class, 'bu');
    }

    public function departmentRelation()
    {
        return $this->belongsTo(Department::class, 'department');
    }

    public function reportingManager()
    {
        return $this->belongsTo(Employee::class, 'emp_reporting', 'employee_id');
    }

    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'emp_reporting', 'employee_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'emp_id');
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'emp_reporting', 'employee_id');
    }

    /**
     * Helpers for role/designation logic
     */
    public function isGeneralManager()
    {
        return $this->designation && str_contains($this->designation->name ?? '', 'General Manager');
    }

    public function isZonalManager()
    {
        return $this->designation && str_contains($this->designation->name ?? '', 'Zonal Business Manager');
    }

    public function isRegionalManager()
    {
        return $this->designation && str_contains($this->designation->name ?? '', 'Regional Business Manager');
    }

    public function isMisTeam()
    {
        return $this->departmentRelation && $this->departmentRelation->department_code === 'MIS';
    }

    public function getReportingManager(int $level = 1): ?self
    {
        $manager = $this;

        for ($i = 0; $i < $level; $i++) {
            if (!$manager?->emp_reporting) {
                return null;
            }

            $manager = $manager->reportingManager; // This uses the belongsTo above

            if (!$manager) {
                return null;
            }
        }

        return $manager;
    }

    public function getReportingManagerEmail(int $level = 1): ?string
    {
        return $this->getReportingManager($level)?->emp_email;
    }

    public function getReportingManagerName(int $level = 1): ?string
    {
        return $this->getReportingManager($level)?->emp_name;
    }
}
