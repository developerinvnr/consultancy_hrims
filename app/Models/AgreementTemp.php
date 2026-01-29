<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgreementTemp extends Model
{
	use SoftDeletes;

	protected $table = 'agreement_temp';

	protected $fillable = [
		'candidate_code',
		'requisition_id',
		'candidate_name',
		'contract_start_date',
		'emp_type',
		'contact_number',
		'father_name',
		'address_line_1',
		'country',
		'state',
		'district',
		'pin_code',
		'date_of_birth',
		'age',
		'aadhaar_number',
		'id_proof_path',
		'address_proof_path',
		'agreement_generated',
		'agreement_generated_at',
		'agreement_response'
	];

	protected $casts = [
		'contract_start_date' => 'date',
		'date_of_birth' => 'date',
		'agreement_generated_at' => 'datetime'
	];

	public function requisition()
	{
		return $this->belongsTo(ManpowerRequisition::class, 'requisition_id');
	}

	public function candidate()
	{
		return $this->belongsTo(CandidateMaster::class, 'candidate_code', 'candidate_code');
	}

	/**
	 * Get document URLs with S3 prefix
	 */
	public function getIdProofUrlAttribute()
	{
		return $this->id_proof_path ? 'https://s3.ap-south-1.amazonaws.com/developerinvnr.bkt/' . $this->id_proof_path : null;
	}

	public function getAddressProofUrlAttribute()
	{
		return $this->address_proof_path ? 'https://s3.ap-south-1.amazonaws.com/developerinvnr.bkt/' . $this->address_proof_path : null;
	}

	public function getPanCardUrlAttribute()
	{
		return $this->pan_card_path ? 'https://s3.ap-south-1.amazonaws.com/developerinvnr.bkt/' . $this->pan_card_path : null;
	}

	public function getBankDocumentUrlAttribute()
	{
		return $this->bank_document_path ? 'https://s3.ap-south-1.amazonaws.com/developerinvnr.bkt/' . $this->bank_document_path : null;
	}

	public function getResumeUrlAttribute()
	{
		return $this->resume_path ? 'https://s3.ap-south-1.amazonaws.com/developerinvnr.bkt/' . $this->resume_path : null;
	}
}
