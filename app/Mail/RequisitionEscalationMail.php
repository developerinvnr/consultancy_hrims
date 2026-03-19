<?php

namespace App\Mail;

use App\Models\ManpowerRequisition;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequisitionEscalationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $requisition;
    public $recipient;
    public $approvalUrl;

    public function __construct(ManpowerRequisition $requisition, $recipient = null)
    {
        $this->requisition = $requisition;
        $this->recipient = $recipient;

        $this->approvalUrl = route('hr_requisitions.index');
    }

    public function build()
    {
        return $this->subject(
            'Escalation: Approval Pending – Requisition ID ' . $this->requisition->request_code
        )
        ->markdown('emails.requisition.escalation');
    }
}