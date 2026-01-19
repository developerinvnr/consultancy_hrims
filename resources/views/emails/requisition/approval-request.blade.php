@component('mail::message')
# Manpower Requisition Approval Required

Dear {{ $approver->emp_name  }},

A new manpower requisition requires your approval.

## Requisition Details:
- **Requisition ID:** {{ $requisition->requisition_id }}
- **Candidate Name:** {{ $requisition->candidate_name }}
- **Position Type:** {{ $requisition->requisition_type }}
- **Submitted By:** {{ $requisition->submitted_by_name }} ({{ $requisition->submitted_by_employee_id }})
- **Submission Date:** {{ optional($requisition->submission_date)->format('d M Y, H:i') ?? 'N/A' }}
- **Joining Date Required:** {{ optional($requisition->date_of_joining_required)->format('d M Y') ?? 'N/A' }}

## Employment Details:
- **Department:** {{ $requisition->department->department_name ?? 'N/A' }}
- **Function:** {{ $requisition->function->function_name ?? 'N/A' }}
- **Location:** {{ $requisition->work_location_hq ?? 'N/A' }}
- **Remuneration:** ₹{{ number_format($requisition->remuneration_per_month ?? 0, 2) }}/month

## HR Verification:
- **Verified by:** {{ $requisition->hrVerifier->name ?? 'N/A' }}
- **Verification Date:** {{ optional($requisition->hr_verification_date)->format('d M Y, H:i') ?? 'N/A' }}
@if($requisition->hr_verification_remarks)
- **HR Remarks:** {{ $requisition->hr_verification_remarks }}
@endif

@component('mail::button', ['url' => $approvalUrl])
View & Approve Requisition
@endcomponent

**Note:** Please log in to the system to review and approve/reject this requisition.

@component('mail::panel')
This is a system generated email. Please do not reply.
@endcomponent

Thanks,  
{{ config('app.name') }}
@endcomponent
