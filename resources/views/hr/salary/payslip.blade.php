<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $salary->candidate->candidate_name ?? 'Employee' }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 13px; margin: 25px; }
        .header { text-align: center; }
        .invoice-title { font-size: 26px; font-weight: bold; letter-spacing: 2px; }
        .party-name { font-size: 20px; font-weight: bold; margin-top: 15px; }
        .section { margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 8px; }
        th { background: #f3f3f3; }
        .right { text-align: right; }
        .total-row { font-weight: bold; background: #f8f8f8; }
        .terms { margin-top: 30px; font-size: 12px; }
        .signature { margin-top: 60px; text-align: right; }
    </style>
</head>
<body>

<div class="header">
    <div class="invoice-title">INVOICE</div>
    <div>{{ date('F Y', strtotime("{$salary->year}-{$salary->month}-01")) }}</div>
</div>

<div class="section">
    <div class="party-name">
        {{ strtoupper($salary->candidate->candidate_name ?? '') }}
    </div>

    <div>
        {{ $salary->candidate->address_line_1 ?? '' }}<br>
        {{ $salary->candidate->cityMaster->name ?? '' }},
        {{ $salary->candidate->residenceState->state_name ?? '' }} -
        {{ $salary->candidate->pin_code ?? '' }}<br>
        PAN: {{ $salary->candidate->pan_no ?? 'N/A' }}<br>
        Mobile: {{ $salary->candidate->mobile_no ?? '' }}
    </div>
</div>

<div class="section">
    <strong>Bill To:</strong><br>
    VNR Seeds Pvt. Ltd.<br>
</div>

@php
    $tdsRate = 0.10; // 10% example
    $baseAmount = $salary->net_pay;
    $sundayAmount = $salary->extra_amount ?? 0;
    $arrearAmount = $salary->arrear_amount ?? 0;

    $baseTds = $baseAmount * $tdsRate;
    $sundayTds = $sundayAmount * $tdsRate;
    $arrearTds = $arrearAmount * $tdsRate;

    $grandTotal = ($baseAmount + $sundayAmount + $arrearAmount);
@endphp

<table>
    <thead>
        <tr>
            <th width="60%">Particulars</th>
            <th width="20%" class="right">Amount (₹)</th>
            <th width="20%" class="right">TDS (₹)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Contractual service charges for the services in the defined territory</td>
            <td class="right">{{ number_format($baseAmount, 2) }}</td>
            <td class="right">{{ number_format($baseTds, 2) }}</td>
        </tr>

        <tr>
            <td>Charges for the additional services (Sunday)</td>
            <td class="right">{{ number_format($sundayAmount, 2) }}</td>
            <td class="right">{{ number_format($sundayTds, 2) }}</td>
        </tr>

        <tr>
            <td>Charges for the services not covered under above (Arrear)</td>
            <td class="right">{{ number_format($arrearAmount, 2) }}</td>
            <td class="right">{{ number_format($arrearTds, 2) }}</td>
        </tr>

        <tr class="total-row">
            <td>Total</td>
            <td class="right">{{ number_format($grandTotal, 2) }}</td>
            <td class="right">
                {{ number_format($baseTds + $sundayTds + $arrearTds, 2) }}
            </td>
        </tr>
    </tbody>
</table>

<div class="terms">
    <strong>Terms & Conditions:</strong><br><br>

    1. The bill is inclusive of all applicable taxes.<br>
    2. Services have been verified by the designated team of the service receiver.<br>
    3. Payment is payable within 30 days of invoice date.<br>
    4. Jurisdiction: Raipur Court only.
</div>

<div class="signature">
    For {{ strtoupper($salary->candidate->candidate_name ?? '') }}<br><br><br>
    ___________________________<br>
    Authorized Signature
</div>

</body>
</html>