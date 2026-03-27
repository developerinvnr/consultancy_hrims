<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CandidateMaster;
use App\Mail\AttendancePendingMail;
use Illuminate\Support\Facades\Mail;

class AttendancePendingReminderCommand extends Command
{

    protected $signature = 'attendance:pending-reminders 
                            {--dry-run : Test without sending emails}';

    protected $description = 'Send attendance pending reminders';

    public function handle()
    {

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {

            $this->warn('============================================');
            $this->warn('RUNNING IN DRY RUN MODE - No emails will be sent');
            $this->warn('============================================');

            $this->newLine();
        }


        $candidates = CandidateMaster::whereNotNull('candidate_code')
            ->where('candidate_status', 'Active')
            ->whereNull('last_working_date')
            ->where('department_id', '!=', 10)
            ->whereDate('contract_start_date', '<=', now())
            ->where(function ($q) {

                $q->whereNull('contract_end_date')
                    ->orWhereDate('contract_end_date', '>=', now());

            })
            ->with('reportingManager.manager.manager')
            ->get();


        $this->info("Found " . $candidates->count() . " active candidates");

        $this->newLine();


        $sentCount = 0;
        $skippedCount = 0;
        $noEmailCount = 0;


        foreach ($candidates as $candidate) {

            $this->line("-----------------------------------");

            $this->line("Candidate: {$candidate->candidate_name}");

            $this->line("Party Code: {$candidate->candidate_code}");

            $this->line("Contract Start: {$candidate->contract_start_date}");



            $pendingDates = $candidate->pendingAttendanceDates();


            $this->line("Pending dates found: " . count($pendingDates));


            if (!empty($pendingDates)) {

                $this->line(
                    "Dates: " .
                        implode(", ", array_slice($pendingDates, 0, 5)) .
                        (count($pendingDates) > 5 ? "..." : "")
                );

            }


            if (empty($pendingDates)) {

                $this->line("⏭️ Skipped: No pending dates");

                $skippedCount++;

                $this->newLine();

                continue;
            }


            $pendingCount = count($pendingDates);


            $mailTo = $candidate->reportingManager?->emp_email;


            if (!$mailTo) {

                $this->line("⚠️ Skipped: No reporting manager email");

                $noEmailCount++;

                $skippedCount++;

                $this->newLine();

                continue;
            }


            $mailCc = [];


            if ($pendingCount >= 4 && $pendingCount <= 5) {

                $rmManagerEmail =
                    optional($candidate->reportingManager->manager)->emp_email;

                if ($rmManagerEmail) {

                    $mailCc[] = $rmManagerEmail;
                }

                $this->line("📧 Escalation: Adding RM's manager");
            }


            if ($pendingCount >= 6) {

                $rmManagerEmail =
                    optional($candidate->reportingManager->manager)->emp_email;

                if ($rmManagerEmail) {

                    $mailCc[] = $rmManagerEmail;
                }


                $highestManagerEmail =
                    optional(
                        optional($candidate->reportingManager->manager)->manager
                    )->emp_email;


                if ($highestManagerEmail) {

                    $mailCc[] = $highestManagerEmail;
                }


                $this->line("📧 Escalation: Adding RM's manager and highest manager");
            }


            $this->line("📧 To: {$mailTo}");

            if (!empty($mailCc)) {

                $this->line("📧 Cc: " . implode(", ", $mailCc));
            }


            $this->line("📧 Pending days: {$pendingCount}");



            if (!$isDryRun) {

                Mail::to($mailTo)
                    ->cc(array_filter($mailCc))
                    ->send(new AttendancePendingMail($candidate,$pendingDates));

                sleep(1);


                $this->line("✅ Email sent successfully");

            } else {

                $this->line("🔍 [DRY RUN] Email would be sent");

            }


            $sentCount++;

            $this->newLine();
        }



        $this->newLine();

        $this->info("============================================");

        $this->info("SUMMARY");

        $this->info("============================================");

        $this->info("Total candidates checked: " . $candidates->count());

        $this->info("Emails sent/would be sent: {$sentCount}");

        $this->info("Skipped (no pending dates): {$skippedCount}");

        $this->info("- No reporting manager email: {$noEmailCount}");



        if ($isDryRun) {

            $this->warn("DRY RUN completed - No actual emails were sent");

        } else {

            $this->info("Attendance reminders sent successfully");

        }

    }

}