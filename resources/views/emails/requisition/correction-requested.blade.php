@component('mail::message')

# Requisition Returned for Correction

Dear {{ $requisition->reportingManager->emp_name ?? 'Reporting Manager' }},

During HR verification, discrepancies have been identified in the requisition submitted via the **Peepal Bonsai Portal**.

### Details

• **Requisition ID:** {{ $requisition->request_code }}  
• **Name:** {{ $requisition->candidate_name }}  
• **Engagement Type:** {{ $requisition->requisition_type }}  
• **Duration:** {{ $requisition->contract_start_date }} – {{ $requisition->contract_end_date }}

### Correction Description

{{ $remarks }}

@component('mail::button', ['url' => $correctionUrl])
Open Peepal Bonsai Portal
@endcomponent

The requisition has been returned for correction in the portal.

Kindly review and resubmit at the earliest.

Regards  
**HR**

@endcomponent