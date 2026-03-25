<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CandidateMaster;
use App\Mail\CourierDetailsPendingMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class CourierDetailsReminderCommand extends Command
{
    protected $signature = 'courier:details-reminders';

    protected $description = 'Send courier details pending reminders';

    public function handle()
    {
        $candidates = CandidateMaster::whereNotNull('candidate_code')
            ->where('candidate_status', 'Signed Agreement Uploaded')
            ->whereNull('last_working_date')
            ->whereDate('contract_start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('contract_end_date')
                    ->orWhereDate('contract_end_date', '>=', now());
            })
            ->whereHas('signedAgreements')
            ->with('reportingManager.manager.manager')
            ->get();

        foreach ($candidates as $candidate) {

            // latest signed agreement
            $agreement = $candidate->signedAgreements()->latest()->first();

            if (!$agreement) {
                continue;
            }

            // check courier entry
            $courier = DB::table('agreement_couriers')
                ->where('agreement_document_id', $agreement->id)
                ->first();

            // already dispatched → skip
            if ($courier && $courier->dispatch_date) {
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
                ->send(new CourierDetailsPendingMail(
                    $candidate,
                    $pendingDays
                ));
        }

        $this->info('Courier reminder emails sent successfully.');
    }
}
