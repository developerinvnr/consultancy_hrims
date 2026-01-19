<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
	protected $table = 'attendance';
	protected $primaryKey = 'id';

	protected $fillable = [
		'CandidateID',
		'Month',
		'Year',
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
		'total_lwp',
		'submitted_by',
		'status'
	];

	protected $casts = [
		'Month' => 'integer',
		'Year' => 'integer',
		'total_present' => 'integer',
		'total_absent' => 'integer',
		'total_cl' => 'integer',
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
