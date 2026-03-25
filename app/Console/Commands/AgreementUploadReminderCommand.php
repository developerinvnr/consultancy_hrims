<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CandidateMaster;
use App\Mail\AgreementUploadPendingMail;
use Illuminate\Support\Facades\Mail;

class AgreementUploadReminderCommand extends Command
{
    protected $signature = 'agreement:upload-reminders';

    protected $description = 'Send agreement upload pending reminders';

    public function handle()
    {
        $candidates = CandidateMaster::whereNotNull('candidate_code')
            ->where('candidate_status', 'Unsigned Agreement Created')
            ->whereNull('last_working_date')
            ->whereDate('contract_start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('contract_end_date')
                    ->orWhereDate('contract_end_date', '>=', now());
            })
            ->whereHas('unsignedAgreements')
            ->with('reportingManager.manager.manager')
            ->get();

        foreach ($candidates as $candidate) {

            $agreement = $candidate->unsignedAgreements()->latest()->first();

            if (!$agreement) {
                continue;
            }

            $pendingDays = now()->diffInDays($agreement->created_at);

            $mailTo = $candidate->reportingManager?->emp_email;

            if (!$mailTo) {
                continue;
            }

            $mailCc = [];

            /*
            Escalation Logic
            */

            // 3–5 days → add RM Manager
            if ($pendingDays >= 3 && $pendingDays <= 5) {

                $rmManagerEmail = optional(
                    $candidate->reportingManager->manager
                )->emp_email;

                if ($rmManagerEmail) {
                    $mailCc[] = $rmManagerEmail;
                }
            }

            // 6+ days → add RM Manager + Highest Manager
            if ($pendingDays >= 6) {

                $rmManagerEmail = optional(
                    $candidate->reportingManager->manager
                )->emp_email;

                if ($rmManagerEmail) {
                    $mailCc[] = $rmManagerEmail;
                }

                $highestManagerEmail = optional(
                    optional($candidate->reportingManager->manager)->manager
                )->emp_email;

                if ($highestManagerEmail) {
                    $mailCc[] = $highestManagerEmail;
                }
            }

            Mail::to($mailTo)
                ->cc(array_filter($mailCc))
                ->send(new AgreementUploadPendingMail(
                    $candidate,
                    $pendingDays
                ));
        }

        $this->info('Agreement upload reminders sent successfully.');
    }
}
