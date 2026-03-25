@component('mail::message')

# Agreement Upload Pending

Dear {{ $candidate->reportingManager->emp_name ?? 'Reporting Manager' }},

The agreement upload is pending in the **Peepal Bonsai Portal**.

### Details

• Name: {{ $candidate->candidate_name }}  
• Engagement Type: {{ $candidate->requisition_type }}  
• Party Code: {{ $candidate->candidate_code }}  
• Effective Date: {{ $candidate->contract_start_date }}  
• Pending Since: {{ $pendingDays }} Days

---

## Action Required

### 1️⃣ Download & Execute Agreement

Download the agreement from portal, print on **A4 white sheet**, obtain signatures and upload.

### 2️⃣ Dispatch Hard Copy

Send signed agreement to **VNR Seeds Raipur Office**

### 3️⃣ Update Dispatch Details

Update dispatch details in portal within **3 days**

---

Regards  
**HR Operations Team**

@endcomponent