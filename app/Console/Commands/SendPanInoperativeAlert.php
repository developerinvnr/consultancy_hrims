<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CandidateMaster;
use Illuminate\Support\Facades\Mail;

class SendPanInoperativeAlert extends Command
{
    protected $signature = 'alert:pan-inoperative';

    protected $description = 'Send alert email when candidate PAN is inoperative';

    public function handle()
    {
        \Log::info('PAN alert command STARTED at ' . now());
        $hrEmail = 'hrsupport@vnrseeds.com';

        $candidates = CandidateMaster::with('reportingManager')
            ->where('pan_status_2', '!=', 'Operative')
            ->whereNotNull('pan_no')
            ->get();
        foreach ($candidates as $candidate) {

            $managerEmail = $candidate->reportingManager->emp_email ?? null;

            if (!$managerEmail) {
                $this->warn("❌ No manager email for: {$candidate->candidate_name}");
                continue;
            }

            $this->info("📧 Sending email to: {$managerEmail}");

            Mail::send('emails.requisition.pan_inoperative_alert', [
                'candidate' => $candidate
            ], function ($message) use ($managerEmail, $hrEmail, $candidate) {

                $message->to($managerEmail)
                    ->cc($hrEmail)
                    ->subject("PAN Inoperative Alert - {$candidate->candidate_name}");
            });
        }
         \Log::info('PAN alert command COMPLETED at ' . now());
        $this->info('PAN Inoperative alert emails sent successfully.');
    }
}
