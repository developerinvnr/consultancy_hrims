<?php

namespace App\Mail;

use App\Models\ManpowerRequisition;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CorrectionRequested extends Mailable
{
    use Queueable, SerializesModels;

    public $requisition;
    public $remarks;
    public $correctionUrl;

    public function __construct(ManpowerRequisition $requisition, string $remarks)
    {
        $this->requisition = $requisition;
        $this->remarks = $remarks;

        $this->correctionUrl = route('requisitions.edit', $requisition->id);
    }

    public function build()
    {
        return $this->subject(
            'Peepal Bonsai Portal: Action Required – Requisition Correction | ' 
            . $this->requisition->request_code
        )
        ->markdown('emails.requisition.correction-requested');
    }
}