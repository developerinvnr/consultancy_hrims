<p>Dear {{ $requisition->reporting_to ?? 'Sir/Madam' }},</p>

<p>
The requisition submitted via the <strong>Peepal Bonsai Portal</strong> has been approved,
and the corresponding <strong>Party Code</strong> has been successfully created.
</p>

<p><strong>Details:</strong></p>

<table border="1" cellpadding="6" cellspacing="0">
<tr>
<td><strong>Requisition ID</strong></td>
<td>{{ $requisition->requisition_id }}</td>
</tr>

<tr>
<td><strong>Name</strong></td>
<td>{{ $candidate->candidate_name }}</td>
</tr>

<tr>
<td><strong>Engagement Type</strong></td>
<td>{{ $candidate->requisition_type }}</td>
</tr>

<tr>
<td><strong>Party Code</strong></td>
<td>{{ $candidate->candidate_code }}</td>
</tr>

<tr>
<td><strong>Effective Date</strong></td>
<td>{{ $candidate->contract_start_date }}</td>
</tr>
</table>

<br>

<p><strong>Action Required:</strong></p>

<p>
<b>1. Initiate Regular Attendance Marking</b><br>
Attendance marking should be initiated in the portal as per defined timelines.
</p>

<p>
<b>2. Download & Execute Agreement</b><br>
You will find the Agreement Download option in the Peepal Bonsai Portal.  
Please download the agreement, print it on an A4 white sheet, obtain necessary signatures, and upload the signed copy in the portal.
</p>

<p>
<b>3. Dispatch Hard Copy</b><br>
The signed agreement must be dispatched to the <b>VNR Seeds Raipur Office</b>.
</p>

<p>
<b>4. Update Dispatch Details in Portal (Within 3 Days)</b><br>
Dispatch details must be updated in the portal within 3 days from the date of Party Code creation.
</p>

<p>
Timely completion of the above actions is required to ensure process compliance and documentation control.
</p>

<p>
For any assistance, please connect with the <b>HR Operations Team</b>.
</p>

<br>

<p>Regards,<br>
HR</p>