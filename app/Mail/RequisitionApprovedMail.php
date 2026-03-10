<?php

namespace App\Mail;

use App\Models\ManpowerRequisition;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequisitionApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $requisition;

    public function __construct(ManpowerRequisition $requisition)
    {
        $this->requisition = $requisition;
    }

    public function build()
    {
        return $this->subject(
            'Peepal Bonsai Portal: Requisition Approved | ID: '
            . $this->requisition->requisition_id
        )
        ->markdown('emails.requisition.approved');
    }
}