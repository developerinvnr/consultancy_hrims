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
        $hrEmail = 'hr@test.com';

        $candidates = CandidateMaster::with('reportingManager')
            ->where('pan_status_2', '!=', 'Operative')
            ->whereNotNull('pan_no')
            ->get();

        foreach ($candidates as $candidate) {

            $managerEmail = $candidate->reportingManager->emp_email ?? null;

            if (!$managerEmail) {
                continue;
            }

            Mail::send('emails.requisition.pan-inoperative-alert', [
                'candidate' => $candidate
            ], function ($message) use ($managerEmail, $hrEmail, $candidate) {

                $message->to($managerEmail)
                    ->cc($hrEmail)
                    ->subject("PAN Inoperative Alert - {$candidate->candidate_name}");
            });

        }

        $this->info('PAN Inoperative alert emails sent successfully.');
    }
}