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
        $requisitions = ManpowerRequisition::where('status', 'Pending Approval')
            ->whereNotNull('approval_requested_at')
            ->get();

        foreach ($requisitions as $req) {

            $hours = now()->diffInHours($req->approval_requested_at);

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

                Mail::to($approver->emp_email)
                    ->queue(new RequisitionApprovalReminder($req, $approver));

                $req->reminder_level = 1;
                $req->save();

                $this->info("24h reminder sent for requisition {$req->request_code}");
            }

            /* 3 DAY ESCALATION */

            if ($hours >= 72 && $req->reminder_level == 1 && $manager) {

                Mail::to($manager->emp_email)
                    ->queue(new RequisitionEscalationMail($req, $manager));

                $req->reminder_level = 2;
                $req->save();

                $this->info("3 day escalation sent for requisition {$req->request_code}");
            }

            /* 5 DAY ESCALATION */

            if ($hours >= 120 && $req->reminder_level == 2 && $upperManager) {

                Mail::to($upperManager->emp_email)
                    ->queue(new RequisitionEscalationMail($req, $upperManager));

                $req->reminder_level = 3;
                $req->save();

                $this->info("5 day escalation sent for requisition {$req->request_code}");
            }

            /* 7 DAY FINAL ESCALATION */

            if ($hours >= 168 && $req->reminder_level == 3 && $upperManager) {

                Mail::to($upperManager->emp_email)
                    ->queue(new RequisitionEscalationMail($req, $upperManager));

                $req->reminder_level = 4;
                $req->save();

                $this->info("7 day final escalation sent for requisition {$req->request_code}");
            }
        }

        return Command::SUCCESS;
    }
}