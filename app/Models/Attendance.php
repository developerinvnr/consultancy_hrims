<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
	protected $table = 'attendance';
	protected $primaryKey = 'id';

	protected $fillable = [
		'candidate_id',
		'month',
		'year',

		'A1',
		'A2',
		'A3',
		'A4',
		'A5',
		'A6',
		'A7',
		'A8',
		'A9',
		'A10',
		'A11',
		'A12',
		'A13',
		'A14',
		'A15',
		'A16',
		'A17',
		'A18',
		'A19',
		'A20',
		'A21',
		'A22',
		'A23',
		'A24',
		'A25',
		'A26',
		'A27',
		'A28',
		'A29',
		'A30',
		'A31',

		'total_present',
		'total_absent',
		'total_cl',
		'total_ch',
		'total_od',
		'total_lwp',
		'submitted_by',
		'status'
	];


	protected $casts = [
		'month' => 'integer',
		'year' => 'integer',
		'total_present' => 'float',
		'total_absent' => 'float',
		'total_cl' => 'integer',
		'total_ch' => 'float',
		'total_od' => 'integer',
		'total_lwp' => 'integer'
	];

	/**
	 * Get the employee associated with the attendance
	 */
	public function employee()
	{
		return $this->belongsTo(CandidateMaster::class, 'candidate_id', 'id');
	}
	/**
	 * Get the user who submitted the attendance
	 */
	public function submittedBy()
	{
		return $this->belongsTo(User::class, 'submitted_by', 'id');
	}
}
