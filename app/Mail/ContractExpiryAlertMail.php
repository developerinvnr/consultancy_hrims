<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractExpiryAlertMail extends Mailable
{
    use SerializesModels;

    public $candidate;
    public $daysRemaining;

    public function __construct($candidate, $daysRemaining)
    {
        $this->candidate = $candidate;
        $this->daysRemaining = $daysRemaining;
    }

    public function build()
    {
        return $this->subject(
            "Peepal Bonsai Portal: Contract Expiry Alert | Party Code: {$this->candidate->candidate_code}"
        )->markdown('emails.requisition.expiry-alert');
    }
}