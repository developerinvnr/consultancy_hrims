<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [

            // Dashboard
            ['name' => 'dashboard.view', 'group_name' => 'dashboard'],

            // Users
            ['name' => 'users.view', 'group_name' => 'user_management'],
            ['name' => 'users.create', 'group_name' => 'user_management'],
            ['name' => 'users.edit', 'group_name' => 'user_management'],
            ['name' => 'users.delete', 'group_name' => 'user_management'],
            ['name' => 'users.manage-permissions', 'group_name' => 'user_management'],

            // Roles
            ['name' => 'roles.full-access', 'group_name' => 'role_management'],

            // Requisitions - Contractual
            ['name' => 'requisitions.contractual.view', 'group_name' => 'requisition_contractual'],
            ['name' => 'requisitions.contractual.create', 'group_name' => 'requisition_contractual'],
            ['name' => 'requisitions.contractual.edit', 'group_name' => 'requisition_contractual'],

            // Requisitions - TFA
            ['name' => 'requisitions.tfa.view', 'group_name' => 'requisition_tfa'],
            ['name' => 'requisitions.tfa.create', 'group_name' => 'requisition_tfa'],
            ['name' => 'requisitions.tfa.edit', 'group_name' => 'requisition_tfa'],

            // Requisitions - CB
            ['name' => 'requisitions.cb.view', 'group_name' => 'requisition_cb'],
            ['name' => 'requisitions.cb.create', 'group_name' => 'requisition_cb'],
            ['name' => 'requisitions.cb.edit', 'group_name' => 'requisition_cb'],

            // HR
            ['name' => 'requisitions.verification.manage', 'group_name' => 'hr_verification'],
            ['name' => 'requisitions.processing.manage', 'group_name' => 'hr_processing'],

            // Approval
            ['name' => 'requisitions.approve', 'group_name' => 'approval_workflow'],

            // Reports
            ['name' => 'reports.view', 'group_name' => 'reports'],
            ['name' => 'reports.export', 'group_name' => 'reports'],
			['name' => 'master_report', 'group_name' => 'reports'],
			['name' => 'payout_report', 'group_name' => 'reports'],
			['name' => 'focus_maste_report', 'group_name' => 'reports'],
			['name' => 'jv_report', 'group_name' => 'reports'],
			['name' => 'tds_jv_report', 'group_name' => 'reports'],
			['name' => 'payment_jv_report', 'group_name' => 'reports'],
			['name' => 'management_report', 'group_name' => 'reports'],
            ['name' => 'tat_report', 'group_name' => 'reports'],

            // Settings
            ['name' => 'settings.full-access', 'group_name' => 'settings'],
            ['name' => 'audit.view', 'group_name' => 'audit_logs'],
            // Ledger
            ['name' => 'ledger.manage', 'group_name' => 'ledger'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                ['group_name' => $permission['group_name']]
            );
        }

        $this->command->info('Permissions synced successfully!');
    }
}
