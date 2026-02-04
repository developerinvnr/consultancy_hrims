<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip - {{ $salary->candidate->candidate_name ?? 'Employee' }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 13px; margin: 20px; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 25px; }
        .info p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #555; padding: 8px; }
        th { background: #f8f8f8; text-align: left; }
        .total { font-weight: bold; background: #f0f0f0; }
        .net { font-size: 1.4em; text-align: right; margin-top: 25px; padding-right: 20px; }
        .footer { margin-top: 40px; font-size: 11px; color: #666; text-align: center; }
    </style>
</head>
<body>

<div class="header">
    <h2>Salary Payslip</h2>
    <p>{{ date('F Y', strtotime("{$salary->year}-{$salary->month}-01")) }}</p>
</div>

<div class="info">
    <p><strong>Party Code:</strong> {{ $salary->candidate->candidate_code ?? '—' }}</p>
    <p><strong>Name:</strong> {{ $salary->candidate->candidate_name ?? '—' }}</p>
    <p><strong>Designation/Type:</strong> {{ $salary->candidate->requisition_type ?? '—' }}</p>
</div>

<table>
    <thead>
        <tr><th colspan="2">Earnings</th></tr>
    </thead>
    <tbody>
        <tr>
            <td>Monthly Remuneration (Reference)</td>
            <td align="right">₹ {{ number_format($salary->monthly_salary, 2) }}</td>
        </tr>

        <tr>
            <td>Paid Days</td>
            <td align="right">{{ $salary->paid_days }}</td>
        </tr>

        <tr>
            <td>Approved Sunday Work</td>
            <td align="right">₹ {{ number_format($salary->extra_amount, 2) }}</td>
        </tr>

        <tr class="total">
            <td>Total Payable Earnings</td>
            <td align="right">₹ {{ number_format($salary->net_pay + $salary->deduction_amount, 2) }}</td>
        </tr>
    </tbody>
</table>


<table>
    <thead><tr><th colspan="2">Deductions</th></tr></thead>
    <tbody>
        <tr><td>Absent / Leave Without Pay</td><td align="right">₹ {{ number_format($salary->deduction_amount, 2) }}</td></tr>
        <tr class="total"><td>Total Deductions</td><td align="right">₹ {{ number_format($salary->deduction_amount, 2) }}</td></tr>
    </tbody>
</table>

<div class="net">
    <strong>NET PAY: ₹ {{ number_format($salary->net_pay, 2) }}</strong>
</div>

<div class="footer">
    Generated on {{ now()->format('d M Y H:i') }} • Computer Generated Document • No Signature Required
</div>

</body>
</html>