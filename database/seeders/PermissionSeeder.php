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
        // Clear existing permissions
        DB::table('permissions')->delete();

        $permissions = [
            // Dashboard
            ['name' => 'dashboard.view',         'group_name' => 'dashboard'],

            // Users
            ['name' => 'users.view',             'group_name' => 'user_management'],
            ['name' => 'users.create',           'group_name' => 'user_management'],
            ['name' => 'users.edit',             'group_name' => 'user_management'],
            ['name' => 'users.delete',           'group_name' => 'user_management'],
            ['name' => 'users.manage-permissions', 'group_name' => 'user_management'], // merged export + permission manage

            // Roles (usually only for super-admins → can stay coarse)
            ['name' => 'roles.full-access',      'group_name' => 'role_management'],

            // Requisitions - Contractual (very common pattern)
            ['name' => 'requisitions.contractual.view',   'group_name' => 'requisition_contractual'],
            ['name' => 'requisitions.contractual.create', 'group_name' => 'requisition_contractual'],
            ['name' => 'requisitions.contractual.edit',   'group_name' => 'requisition_contractual'],
            ['name' => 'requisitions.contractual.manage', 'group_name' => 'requisition_contractual'], // submit + delete + export

            // Same pattern for TFA & CB
            ['name' => 'requisitions.tfa.view',   'group_name' => 'requisition_tfa'],
            ['name' => 'requisitions.tfa.manage', 'group_name' => 'requisition_tfa'],

            // HR Verification & Processing
            ['name' => 'requisitions.verification.manage', 'group_name' => 'hr_verification'], // list + verify + reject + send
            ['name' => 'requisitions.processing.manage',   'group_name' => 'hr_processing'],   // all processing actions

            // Approval (common for multiple approvers)
            ['name' => 'requisitions.approve',    'group_name' => 'approval_workflow'],

            // Employee Master
            ['name' => 'employees.view',          'group_name' => 'employee_master'],
            ['name' => 'employees.manage',        'group_name' => 'employee_master'], // create/edit/status/export

            // Agreement + Documents (often merged)
            ['name' => 'agreements.manage',       'group_name' => 'agreement_management'], // generate/download/upload/verify
            ['name' => 'documents.manage',        'group_name' => 'document_management'],

            // Attendance & Leave → very often merged
            ['name' => 'attendance.manage',       'group_name' => 'attendance_management'],
            ['name' => 'leave.manage',            'group_name' => 'leave_management'],

            // Reports (almost always one big permission)
            ['name' => 'reports.view',            'group_name' => 'reports'],
            ['name' => 'reports.export',          'group_name' => 'reports'],

            // Settings & Audit (sensitive → keep separated)
            ['name' => 'settings.full-access',    'group_name' => 'settings'],
            ['name' => 'audit.view',              'group_name' => 'audit_logs'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        $this->command->info('Permissions seeded successfully!');
        $this->command->info('Total permissions created: ' . count($permissions));
    }
}
