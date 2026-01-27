<?php

namespace App\Mail;

use App\Models\ManpowerRequisition;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CorrectionRequested extends Mailable
{
    use Queueable, SerializesModels;

    public $requisition;
    public $remarks;
    public $correctionUrl;

    public function __construct(ManpowerRequisition $requisition, string $remarks)
    {
        $this->requisition = $requisition;
        $this->remarks = $remarks;

        // Example URL where user can edit the requisition
        $this->correctionUrl = route('requisitions.edit', $requisition->id);
    }

    public function build()
    {
        return $this->subject('Correction Required - Manpower Requisition')
            ->markdown('emails.requisition.correction-requested');
    }
}
