<?php

namespace App\Mail;

use App\Models\CandidateMaster;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PartyCodeGenerated extends Mailable
{
    use Queueable, SerializesModels;

    public $candidate;
    public $requisition;

    public function __construct($candidate, $requisition)
    {
        $this->candidate = $candidate;
        $this->requisition = $requisition;
    }

    public function build()
    {
        return $this->subject('Peepal Bonsai Portal: Requisition Approved | ID: ' . $this->requisition->requisition_id)
            ->view('emails.requisition.party-code-generated');
    }
}