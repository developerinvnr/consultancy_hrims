@component('mail::message')

# Reminder: Requisition Approval Pending

Dear {{ $approver->emp_name }},

This is a **friendly reminder** that the following requisition is still pending your approval in the **Peepal Bonsai Portal**.

### Requisition Details

- **Requisition ID:** {{ $requisition->requisition_id }}
- **Candidate Name:** {{ $requisition->candidate_name }}
- **Engagement Type:** {{ $requisition->requisition_type }}
- **Department:** {{ $requisition->department->department_name ?? 'N/A' }}

@component('mail::button', ['url' => $approvalUrl])
Open Peepal Bonsai Portal
@endcomponent

Your approval is required to proceed further.

Regards,  
**HR Team**

@component('mail::panel')
This is a system generated reminder notification.
@endcomponent

@endcomponent