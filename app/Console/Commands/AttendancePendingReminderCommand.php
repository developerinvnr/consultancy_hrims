<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CandidateMaster;
use App\Mail\AttendancePendingMail;
use Illuminate\Support\Facades\Mail;

class AttendancePendingReminderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:pending-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send attendance pending reminders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $candidates = CandidateMaster::whereNotNull('candidate_code')
            ->whereNull('last_working_date')
            ->with('reportingManager.manager.manager')
            ->get();

        foreach ($candidates as $candidate) {

            $pendingDates = $candidate->pendingAttendanceDates();

            if (empty($pendingDates)) {
                continue;
            }

            $pendingCount = count($pendingDates);

            // Reporting Manager email
            $mailTo = $candidate->reportingManager?->emp_email;

            if (!$mailTo) {
                continue;
            }

            $mailCc = [];

            /*
        Escalation Logic
        */

            // 4–5 pending days → add RM's manager
            if ($pendingCount >= 4 && $pendingCount <= 5) {

                $rmManagerEmail = optional(
                    $candidate->reportingManager->manager
                )->emp_email;

                if ($rmManagerEmail) {
                    $mailCc[] = $rmManagerEmail;
                }
            }

            // 6+ pending days → add RM's manager + highest level manager
            if ($pendingCount >= 6) {

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
                ->send(new AttendancePendingMail(
                    $candidate,
                    $pendingDates
                ));
        }

        $this->info('Attendance reminders sent successfully.');
    }
}
