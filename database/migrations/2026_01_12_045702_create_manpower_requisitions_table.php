<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('manpower_requisitions', function (Blueprint $table) {
            $table->id();
            
            // Requisition Identification
            $table->string('requisition_id', 50)->unique();
            $table->enum('requisition_type', ['Contractual', 'TFA', 'CB']);
            
            // Submitted By Information
            $table->unsignedBigInteger('submitted_by_user_id'); // Remove foreign key constraint
            $table->string('submitted_by_name', 255);
            $table->string('submitted_by_employee_id', 50);
            $table->dateTime('submission_date');
            
            // Personal Information
            $table->string('candidate_email', 255);
            $table->string('candidate_name', 255);
            $table->string('father_name', 255);
            $table->string('mobile_no', 15);
            $table->string('alternate_email', 255)->nullable();
            $table->string('address_line_1', 500);
            $table->string('city', 100);
            $table->string('state_residence', 100);
            $table->string('pin_code', 6);
            $table->date('date_of_birth');
            $table->enum('gender', ['Male', 'Female', 'Other']);
            $table->string('highest_qualification', 255);
            $table->string('college_name', 255)->nullable();
            
            // Work Information
            $table->string('work_location_hq', 255);
            $table->string('district', 100)->nullable();
            $table->string('state_work_location', 100);
            
            // Organization Structure
            $table->unsignedBigInteger('function_id')->nullable(); // Remove foreign key constraint
            $table->unsignedBigInteger('department_id')->nullable(); // Remove foreign key constraint
            $table->unsignedBigInteger('vertical_id')->nullable(); // Remove foreign key constraint
            $table->string('sub_department', 100)->nullable();
            $table->string('business_unit', 100)->nullable();
            $table->string('zone', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('territory', 100)->nullable();
            
            // Employment Details
            $table->string('reporting_to', 255);
            $table->string('reporting_manager_employee_id', 50);
            $table->date('date_of_joining_required');
            $table->integer('agreement_duration')->nullable(); // Only for Contractual
            $table->date('date_of_separation');
            $table->decimal('remuneration_per_month', 10, 2);
            $table->decimal('fuel_reimbursement_per_month', 10, 2)->nullable(); // Only for Contractual
            $table->text('reporting_manager_address');
            
            // Extracted Information
            $table->string('account_holder_name')->nullable();
            $table->string('bank_account_no', 50)->nullable();
            $table->string('bank_ifsc', 11)->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->string('pan_no', 10)->nullable();
            $table->string('aadhaar_no', 12)->nullable();
            // Workflow Status
            $table->enum('status', [
                'Pending HR Verification',
                'Hr Verified',
                'Correction Required',
                'Pending Approval',
                'Approved',
                'Rejected',
                'Processed',
                'Agreement Pending',
                'Completed'
            ])->default('Pending HR Verification');
            
            // Workflow Tracking
            $table->dateTime('hr_verification_date')->nullable();
            $table->text('hr_verification_remarks')->nullable();
            $table->unsignedBigInteger('hr_verified_id')->nullable();
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->dateTime('approval_date')->nullable();
            $table->text('approver_remarks')->nullable();
            $table->dateTime('rejection_date')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->dateTime('processing_date')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('requisition_type');
            $table->index('status');
            $table->index('submitted_by_user_id');
            $table->index('submission_date');
            $table->index('approver_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manpower_requisitions');
    }
};