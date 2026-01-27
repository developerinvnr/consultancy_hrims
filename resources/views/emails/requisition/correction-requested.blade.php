@component('mail::message')
# Correction Required – Manpower Requisition

Dear {{ $requisition->submittedBy->name ?? 'User' }},

Your manpower requisition requires correction before it can proceed further.

## Requisition Details:
- **Requisition ID:** {{ $requisition->requisition_id }}
- **Candidate Name:** {{ $requisition->candidate_name }}
- **Department:** {{ $requisition->department->department_name ?? 'N/A' }}
- **Function:** {{ $requisition->function->function_name ?? 'N/A' }}

## HR Remarks:
@component('mail::panel')
{{ $remarks }}
@endcomponent

Please review and update the requisition using the button below.

@component('mail::button', ['url' => $correctionUrl])
Edit Requisition
@endcomponent

**Note:** This is a system generated email. Please do not reply.

Thanks,  
{{ config('app.name') }}
@endcomponent
