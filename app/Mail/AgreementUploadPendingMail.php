<?php
namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AgreementUploadPendingMail extends Mailable
{
    use SerializesModels;

    public $candidate;
    public $pendingDays;

    public function __construct($candidate, $pendingDays)
    {
        $this->candidate = $candidate;
        $this->pendingDays = $pendingDays;
    }

    public function build()
    {
        return $this->subject(
            'Peepal Bonsai Portal: Agreement Upload Pending | Party Code: ' .
            $this->candidate->candidate_code
        )->markdown('emails.requisition.agreement_upload_pending');
    }
}