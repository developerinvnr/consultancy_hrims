<?php

namespace App\Mail;

use App\Models\ManpowerRequisition;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequisitionRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public ManpowerRequisition $requisition;
    public string $remarks;

    public function __construct(ManpowerRequisition $requisition, string $remarks)
    {
        $this->requisition = $requisition;
        $this->remarks = $remarks;
    }

    public function build()
    {
        return $this->subject(
            'Peepal Bonsai Portal: Requisition Not Approved at HR Verification | '
            . $this->requisition->requisition_id
        )
        ->markdown('emails.requisition.rejected');
    }
}