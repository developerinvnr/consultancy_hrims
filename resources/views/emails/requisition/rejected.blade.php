@component('mail::message')

# Requisition Not Approved at HR Verification

Dear {{ $requisition->reporting_to ?? 'Reporting Manager' }},

The requisition submitted via the **Peepal Bonsai Portal** was **not approved at the HR Verification stage**.

### Details

- **Requisition ID:** {{ $requisition->requisition_id }}
- **Name:** {{ $requisition->candidate_name }}
- **Engagement Type:** {{ $requisition->requisition_type }}
- **Duration:** {{ $requisition->contract_start_date }} – {{ $requisition->contract_end_date }}

### HR Remark

{{ $remarks }}

The requisition has been **closed in the portal based on the above review**.

For any clarification, please connect with the **HR Operations Team**.

Regards  
**HR Team**

@endcomponent