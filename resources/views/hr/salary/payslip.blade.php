<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $salary->candidate->candidate_name ?? 'Party' }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 13px;
            margin: 25px;
        }

        .header {
            text-align: center;
        }

        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .party-name {
            font-size: 20px;
            font-weight: bold;
            margin-top: 15px;
        }

        .section {
            margin-top: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
        }

        th {
            background: #f3f3f3;
        }

        .right {
            text-align: right;
        }

        .total-row {
            font-weight: bold;
            background: #f8f8f8;
        }

        .terms {
            margin-top: 30px;
            font-size: 12px;
        }

        .signature {
            margin-top: 60px;
            text-align: right;
        }

        .amount-words {
            margin-top: 15px;
            font-weight: bold;
        }
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
            @if(!empty($salary->candidate->pan_no))
                PAN: {{ $salary->candidate->pan_no }}<br>
            @endif
            Mobile: {{ $salary->candidate->mobile_no ?? '' }}
        </div>
    </div>

    <div class="section">
        <strong>Bill To:</strong><br>
        VNR Seeds Pvt. Ltd.
    </div>

    @php
    $tdsRate = 0.02; // Fixed 2%

    $baseAmount   = $salary->net_pay ?? 0;
    $sundayAmount = $salary->extra_amount ?? 0;
    $arrearAmount = $salary->arrear_amount ?? 0;

    // Row wise TDS
    $baseTds   = round($baseAmount * $tdsRate, 2);
    $sundayTds = round($sundayAmount * $tdsRate, 2);
    $arrearTds = round($arrearAmount * $tdsRate, 2);

    // Totals
    $totalAmount = $baseAmount + $sundayAmount + $arrearAmount;
    $totalTds    = $baseTds + $sundayTds + $arrearTds;

    $grandTotal = round($totalAmount + $totalTds, 2);

    function numberToWords($number)
    {
        $no = floor($number);
        $words = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
            5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight',
            9 => 'Nine', 10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve',
            13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
            19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
            40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty',
            70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
        ];

        if ($no < 21) return $words[$no];
        if ($no < 100) return $words[floor($no/10)*10] . ' ' . $words[$no%10];
        if ($no < 1000) return $words[floor($no/100)] . ' Hundred ' . numberToWords($no%100);
        if ($no < 100000) return numberToWords(floor($no/1000)) . ' Thousand ' . numberToWords($no%1000);
        if ($no < 10000000) return numberToWords(floor($no/100000)) . ' Lakh ' . numberToWords($no%100000);
        return numberToWords(floor($no/10000000)) . ' Crore ' . numberToWords($no%10000000);
    }
@endphp

<table>
    <thead>
<tr>
    <th width="60%">Particulars</th>
    <th width="20%" class="right">Amount (₹)</th>
    <th width="20%" class="right">TDS @2% (₹)</th>
</tr>
</thead>

<tbody>
    <tr>
        <td>Contractual service charges for the services in the defined territory</td>
        <td class="right">{{ number_format($baseAmount, 2) }}</td>
        <td class="right">{{ number_format($baseTds, 2) }}</td>
    </tr>

    @if($sundayAmount > 0)
    <tr>
        <td>Charges for the additional services (Sunday)</td>
        <td class="right">{{ number_format($sundayAmount, 2) }}</td>
        <td class="right">{{ number_format($sundayTds, 2) }}</td>
    </tr>
    @endif

    @if($arrearAmount > 0)
    <tr>
        <td>Charges for the services not covered under above (Arrear)</td>
        <td class="right">{{ number_format($arrearAmount, 2) }}</td>
        <td class="right">{{ number_format($arrearTds, 2) }}</td>
    </tr>
    @endif

    <tr class="total-row">
        <td>Total Invoice Amount</td>
        <td class="right">{{ number_format($totalAmount, 2) }}</td>
        <td class="right">{{ number_format($totalTds, 2) }}</td>
    </tr>

    <tr class="total-row">
        <td colspan="2">Grand Total (Amount + TDS)</td>
        <td class="right">₹ {{ number_format($grandTotal, 2) }}</td>
    </tr>
</tbody>
</table>

<div class="amount-words">
     Amount in Words: {{ numberToWords((int)$grandTotal) }} Rupees Only
</div>

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