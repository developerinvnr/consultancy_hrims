<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CandidateMaster;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContractExpiryAlertMail;
use Carbon\Carbon;

class ContractExpiryReminderCommand extends Command
{
    protected $signature = 'contract:expiry-reminders';

    protected $description = 'Send contract expiry alert reminders';

    public function handle()
    {
        $today = Carbon::today();

        $candidates = CandidateMaster::whereNotNull('contract_end_date')
            ->whereNull('last_working_date')
            ->with('reportingManager.manager')
            ->get();

        foreach ($candidates as $candidate) {

            $endDate = Carbon::parse($candidate->contract_end_date);

            $daysRemaining = $today->diffInDays($endDate, false);

            // Trigger only on specific days
            if (!in_array($daysRemaining, [30, 15, 7, 0])) {
                continue;
            }

            $mailTo = $candidate->reportingManager?->emp_email;

           // Exclude Atul Sah email
            if (!$mailTo || $mailTo == 'atul.sah@vnrseeds.com') {
                continue;
            }

            $mailCc = [];

            $rmManager = optional($candidate->reportingManager)->manager;

           if ($rmManager?->emp_email && $rmManager->emp_email != 'atul.sah@vnrseeds.com') {
                $mailCc[] = $rmManager->emp_email;
            }

            Mail::to($mailTo)
                ->cc(array_unique(array_filter($mailCc)))
                ->queue(new ContractExpiryAlertMail(
                    $candidate,
                    $daysRemaining
                ));

            $this->info("Expiry reminder sent for {$candidate->candidate_code}");
        }

        return Command::SUCCESS;
    }
}