<?php
// app/Models/AgreementCourier.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementCourier extends Model
{
    protected $table = 'agreement_couriers';

    protected $fillable = [
        'agreement_document_id',
        'courier_name',
        'docket_number',
        'dispatch_date',
        'sent_by_user_id',
        'received_date',
        'received_by_user_id'
    ];

    protected $casts = [
        'dispatch_date' => 'date',
        'received_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the agreement document this courier detail belongs to
     */
    public function agreementDocument(): BelongsTo
    {
        return $this->belongsTo(AgreementDocument::class, 'agreement_document_id');
    }

    /**
     * Get the user who sent the agreement
     */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    /**
     * Get the user who received the agreement
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    /**
     * Check if the courier has been received
     */
    public function isReceived(): bool
    {
        return !is_null($this->received_date);
    }

    /**
     * Scope to get only received couriers
     */
    public function scopeReceived($query)
    {
        return $query->whereNotNull('received_date');
    }

    /**
     * Scope to get only pending couriers
     */
    public function scopePending($query)
    {
        return $query->whereNull('received_date');
    }

    /**
     * Get formatted dispatch date
     */
    public function getFormattedDispatchDateAttribute(): string
    {
        return $this->dispatch_date->format('d M Y');
    }

    /**
     * Get formatted received date
     */
    public function getFormattedReceivedDateAttribute(): ?string
    {
        return $this->received_date?->format('d M Y');
    }
}