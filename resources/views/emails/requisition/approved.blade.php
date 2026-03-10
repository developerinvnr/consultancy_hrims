@component('mail::message')

# Requisition Approved – Party Code Created

Dear {{ $requisition->reporting_to ?? 'Reporting Manager' }},

The requisition submitted via the **Peepal Bonsai Portal** has been approved and the **Party Code has been created**.

### Details

• **Requisition ID:** {{ $requisition->requisition_id }}  
• **Name:** {{ $requisition->candidate_name }}  
• **Engagement Type:** {{ $requisition->requisition_type }}  
• **Party Code:** {{ $requisition->candidate->emp_code ?? 'N/A' }}  
• **Effective Date:** {{ $requisition->contract_start_date }}

---

## Action Required

### 1️⃣ Initiate Regular Attendance Marking

Attendance marking should be initiated in the portal as per defined timelines.

### 2️⃣ Download & Execute Agreement

You will find the **Agreement Download option** in the Peepal Bonsai Portal.

Please download the agreement, print it on **A4 white sheet**, obtain necessary signatures, and upload the signed copy in the portal.

### 3️⃣ Dispatch Hard Copy

The signed agreement must be **dispatched to the VNR Seeds Raipur Office**.

### 4️⃣ Update Dispatch Details

Dispatch details must be updated in the portal **within 3 days from Party Code creation**.

---

Timely completion of the above actions is required to ensure **process compliance and documentation control**.

For any assistance, please connect with the **HR Operations Team**.

Regards  
**HR**

@endcomponent