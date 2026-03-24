<?php
namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CourierDetailsPendingMail extends Mailable
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
            'Peepal Bonsai Portal: Courier Details Pending | Party Code: ' .
            $this->candidate->candidate_code
        )->markdown('emails.requisition.courier-details-pending');
    }
}