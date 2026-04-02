@component('mail::message')

# Requisition Approval Escalation

Dear {{ $recipient->emp_name ?? 'Manager' }},

The following requisition approval is **still pending** in the Peepal Bonsai Portal.

### Requisition Details

- **Requisition ID:** {{ $requisition->request_code }}
- **Candidate Name:** {{ $requisition->candidate_name }}
- **Engagement Type:** {{ $requisition->requisition_type }}
- **Department:** {{ $requisition->department->department_name ?? 'N/A' }}
- **Location:** {{ $requisition->work_location_hq ?? 'N/A' }}
- **Submitted By:** {{ $requisition->submittedBy->name ?? 'N/A' }}

@component('mail::button', ['url' => $approvalUrl])
Open Peepal Bonsai Portal
@endcomponent

This requisition has been pending for approval and has now been escalated.

Please review the request at the earliest.

Regards,  
**HR Team**

@component('mail::panel')
This is a system generated escalation notification.
@endcomponent

@endcomponent