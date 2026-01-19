<?php

namespace App\Mail;

use App\Models\ManpowerRequisition;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequisitionApprovalRequest extends Mailable
{
    use Queueable, SerializesModels;

    public $requisition;
    public $approver;
    public $approvalUrl;

    public function __construct(ManpowerRequisition $requisition, Employee $approver)
    {
        $this->requisition = $requisition;
        $this->approver = $approver;
        $this->approvalUrl = route('approver.requisitions.pending'); 
        // or better: route('approver.requisition.view', $requisition->id)
    }

    public function build()
    {
        return $this->subject('Requisition Approval Request')
            ->markdown('emails.requisition.approval-request');
    }
}
