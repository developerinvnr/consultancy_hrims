<?php

namespace App\Mail;

use App\Models\ManpowerRequisition;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequisitionApprovalReminder extends Mailable
{
    use Queueable, SerializesModels;

    public $requisition;
    public $approver;
    public $approvalUrl;

    public function __construct(ManpowerRequisition $requisition, Employee $approver)
    {
        $this->requisition = $requisition;
        $this->approver = $approver;
        $this->approvalUrl = route('hr_requisitions.index');
    }

    public function build()
    {
        return $this->subject(
            'Reminder: Approval Pending – Requisition ID ' . $this->requisition->requisition_id
        )->markdown('emails.requisition.reminder');
    }
}