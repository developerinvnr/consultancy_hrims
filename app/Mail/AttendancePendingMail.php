<?php
namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AttendancePendingMail extends Mailable
{
    use SerializesModels;

    public $candidate;
    public $pendingDates;

    public function __construct($candidate, $pendingDates)
    {
        $this->candidate = $candidate;
        $this->pendingDates = $pendingDates;
    }

    public function build()
    {
        return $this->subject(
            'Peepal Bonsai Portal: Attendance Pending | Party Code: ' .
            $this->candidate->candidate_code
        )->markdown('emails.requisition.attendance-pending');
    }
}