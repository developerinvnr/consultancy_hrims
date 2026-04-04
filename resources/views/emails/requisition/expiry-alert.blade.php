@component('mail::message')

# Contract Expiry Alert

Dear {{ $candidate->reportingManager->emp_name ?? 'Manager' }},

The contract for the following engagement is approaching its expiry date in the **Peepal Bonsai Portal**.

### Details

- **Name:** {{ $candidate->candidate_name }}
- **Party Code:** {{ $candidate->candidate_code }}
- **Engagement Type:** {{ $candidate->requisition_type ?? 'N/A' }}
- **Contract End Date:** {{ \Carbon\Carbon::parse($candidate->contract_end_date)->format('d M Y') }}
- **Days Remaining:** {{ $daysRemaining }}

---

### Action Required

Kindly review the engagement and initiate the **Renewal of contract** by filing the **New contract Requisition** if required.

Timely action is required to ensure continuity of operations and avoid disruption.

For assistance, please connect with the **HR Operations Team**.

Regards,  
**HR Team**

@component('mail::panel')
This is a system generated notification from Peepal Bonsai Portal.
@endcomponent

@endcomponent