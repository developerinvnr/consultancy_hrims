<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ManpowerRequisition;
use App\Mail\CorrectionRequested;
use Illuminate\Support\Facades\Mail;

class CorrectionReminderCommand extends Command
{
    protected $signature = 'correction:reminders';

    protected $description = 'Send correction pending reminders';

    public function handle()
    {
        $excludedEmails = [
            'atul.sah@vnrseeds.com'
        ];
        $requisitions = ManpowerRequisition::where('status', 'correction_pending')
            ->with('reportingManager.manager.manager')
            ->get();

        foreach ($requisitions as $requisition) {

            $mailTo = $requisition->reportingManager?->emp_email;

           if (!$mailTo || in_array($mailTo, $excludedEmails)) {
                continue;
            }

            // calculate how many days correction pending
            $pendingDays = now()->diffInDays($requisition->updated_at);

            $mailCc = [];

            /*
            Escalation Logic
            */

            // 3–5 days → add RM Manager
            if ($pendingDays >= 3 && $pendingDays <= 5) {

                $rmManagerEmail = optional(
                    $requisition->reportingManager->manager
                )->emp_email;

                if ($rmManagerEmail && !in_array($rmManagerEmail, $excludedEmails)) {
                    $mailCc[] = $rmManagerEmail;
                }
            }

            // 6+ days → add RM Manager + Highest Manager
            if ($pendingDays >= 6) {

                $rmManagerEmail = optional(
                    $requisition->reportingManager->manager
                )->emp_email;

                if ($rmManagerEmail && !in_array($rmManagerEmail, $excludedEmails)) {
                    $mailCc[] = $rmManagerEmail;
                }

                $highestManagerEmail = optional(
                    optional($requisition->reportingManager->manager)->manager
                )->emp_email;

                if ($highestManagerEmail && !in_array($highestManagerEmail, $excludedEmails)) {
                    $mailCc[] = $highestManagerEmail;
                }
            }

            Mail::to($mailTo)
                ->cc(array_filter($mailCc))
                ->send(new CorrectionRequested(
                    $requisition,
                    $requisition->hr_remark
                ));
        }

        $this->info('Correction reminder emails sent.');
    }
}