<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionDocument extends Model
{
	protected $table = 'requisition_documents';

	protected $fillable = [
		'requisition_id',
		'document_type',
		'file_name',
		'file_path',
		'uploaded_by_user_id',
	];

	/**
	 * Get the requisition that owns the document.
	 */
	public function requisition(): BelongsTo
	{
		return $this->belongsTo(ManpowerRequisition::class, 'requisition_id');
	}

	/**
	 * Get the user who uploaded the document.
	 */
	public function uploadedBy(): BelongsTo
	{
		return $this->belongsTo(User::class, 'uploaded_by_user_id');
	}

	/**
	 * Get the full S3 URL for the document.
	 */
	public function getFullUrlAttribute()
	{
		return Storage::disk('s3')->url($this->file_path);
	}

	public function getDocument($type)
	{
		return $this->documents()->where('document_type', $type)->first();
	}

	// Helper method to get document URL
	public function getDocumentUrl($type)
	{
		$document = $this->getDocument($type);
		return $document ? Storage::disk('s3')->url($document->file_path) : null;
	}
}
