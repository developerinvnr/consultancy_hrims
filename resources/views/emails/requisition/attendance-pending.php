@component('mail::message')

# Attendance Pending

Dear {{ $candidate->reportingManager->emp_name ?? 'Reporting Manager' }},

Attendance has not been updated in the portal.

### Details

• Name: {{ $candidate->candidate_name }}  
• Party Code: {{ $candidate->candidate_code }}  
• Engagement Type: {{ $candidate->requisition_type }}

---

## Pending Dates

@foreach($pendingDates as $date)
• {{ $date }}
@endforeach

---

Kindly update attendance at the earliest.

Regards  
**HR Operations Team**

@endcomponent