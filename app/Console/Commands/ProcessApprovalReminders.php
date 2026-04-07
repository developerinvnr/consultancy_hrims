<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ManpowerRequisition;
use App\Models\Employee;
use Illuminate\Support\Facades\Mail;
use App\Mail\RequisitionApprovalReminder;
use App\Mail\RequisitionEscalationMail;

class ProcessApprovalReminders extends Command
{
    protected $signature = 'approval:reminders';

    protected $description = 'Process approval reminders and escalations';

    public function handle()
    {
        $excludedEmails = [
            'atul.sah@vnrseeds.com'
        ];
        $requisitions = ManpowerRequisition::where('status', 'Pending Approval')
            ->whereNotNull('approval_requested_at')
            ->get();

        foreach ($requisitions as $req) {

            $hours = now()->greaterThan($req->approval_requested_at) ? $req->approval_requested_at->diffInHours(now()) : 0;

            $approver = Employee::where('employee_id', $req->approver_id)->first();

            if (!$approver) {
                continue;
            }

            $manager = null;
            $upperManager = null;

            if ($approver->emp_reporting) {
                $manager = Employee::where(
                    'employee_id',
                    $approver->emp_reporting
                )->first();
            }

            if ($manager && $manager->emp_reporting) {
                $upperManager = Employee::where(
                    'employee_id',
                    $manager->emp_reporting
                )->first();
            }

            /* 24 HOUR REMINDER */

            if ($hours >= 24 && $req->reminder_level == 0) {

                if (!in_array($approver->emp_email, $excludedEmails)) {
                    Mail::to($approver->emp_email)->queue(new RequisitionApprovalReminder($req, $approver));
                }

                $req->reminder_level = 1;
                $req->save();

                $this->info("24h reminder sent for requisition {$req->request_code}");
            }

            /* 3 DAY ESCALATION */

            if ($hours >= 72 && $req->reminder_level == 1 && $manager) {

                $cc = [];

                if (!in_array($manager->emp_email, $excludedEmails)) {
                    $cc[] = $manager->emp_email;
                }

                Mail::to($approver->emp_email)->cc($cc)->queue(new RequisitionEscalationMail($req, $approver));

                $req->reminder_level = 2;
                $req->save();

                $this->info("3 day escalation sent for requisition {$req->request_code}");
            }


            /* 5 DAY ESCALATION */

            if ($hours >= 120 && $req->reminder_level == 2 && $upperManager) {

                $cc = [];

                if (!in_array($manager->emp_email, $excludedEmails)) {
                    $cc[] = $manager->emp_email;
                }

                if (!in_array($upperManager->emp_email, $excludedEmails)) {
                    $cc[] = $upperManager->emp_email;
                }

                if (!in_array($approver->emp_email, $excludedEmails)) {

                    Mail::to($approver->emp_email)
                        ->cc($cc)
                        ->queue(new RequisitionEscalationMail($req, $approver));
                }

                $req->reminder_level = 3;
                $req->save();

                $this->info("5 day escalation sent for requisition {$req->request_code}");
            }


            /* 7 DAY FINAL ESCALATION */

            if ($hours >= 168 && $req->reminder_level == 3 && $upperManager) {

                $cc = [];

                if (!in_array($manager->emp_email, $excludedEmails)) {
                    $cc[] = $manager->emp_email;
                }

                if (!in_array($upperManager->emp_email, $excludedEmails)) {
                    $cc[] = $upperManager->emp_email;
                }

                if (!in_array($approver->emp_email, $excludedEmails)) {

                    Mail::to($approver->emp_email)
                        ->cc($cc)
                        ->queue(new RequisitionEscalationMail($req, $approver));
                }

                $req->reminder_level = 4;
                $req->save();

                $this->info("7 day final escalation sent for requisition {$req->request_code}");
            }
        }

        return Command::SUCCESS;
    }
}
