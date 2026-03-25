@component('mail::message')

# Courier Details Pending

Dear {{ $candidate->reportingManager->emp_name ?? 'Reporting Manager' }},

Courier dispatch details are pending in the **Peepal Bonsai Portal**.

### Details

• Name: {{ $candidate->candidate_name }}  
• Engagement Type: {{ $candidate->requisition_type }}  
• Party Code: {{ $candidate->candidate_code }}  
• Effective Date: {{ $candidate->contract_start_date }}  
• Pending Since: {{ $pendingDays }} Days

---

## Action Required

### Update Courier Details

Please update:

• Courier Name  
• Tracking Number  
• Dispatch Date  

within **3 days**

---

Regards  
**HR Operations Team**

@endcomponent