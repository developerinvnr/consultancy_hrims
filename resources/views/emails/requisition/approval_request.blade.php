@component('mail::message')

# Requisition Sent for Approval

Subject: **Peepal Bonsai Portal: Approval Required – Requisition ID {{ $requisition->request_code }}**

Dear {{ $approver->emp_name }},

A requisition has been submitted in the **Peepal Bonsai Portal** and requires your review and approval.

### Details

- **Requisition ID:** {{ $requisition->request_code }}
- **Name:** {{ $requisition->candidate_name }}
- **Engagement Type:** {{ $requisition->requisition_type }}
- **Duration:** {{ optional($requisition->contract_start_date)->format('d M Y') ?? 'N/A' }}
  – {{ optional($requisition->contract_end_date)->format('d M Y') ?? 'N/A' }}
- **Submitted By:** {{ $requisition->submittedBy->name ?? 'N/A' }}


@component('mail::button', ['url' => $approvalUrl])
Open Peepal Bonsai Portal
@endcomponent

Your approval in the portal is required to proceed further.

For any clarification, please connect with **HR Operations**.

Regards,  
**HR**

@component('mail::panel')
Mail to Reporting Manager with Peepal portal link.  
This is a system generated email. Please do not reply.
@endcomponent

@endcomponent