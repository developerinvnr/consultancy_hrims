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

        .provider-details {
            text-align: center;
            margin-top: 10px;
        }

        .section {
            margin-top: 20px;
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

        .amount-words {
            margin-top: 15px;
            font-weight: bold;
        }


        .terms {
            margin-top: 30px;
            font-size: 12px;
        }

        .signature {
            margin-top: 60px;
            text-align: right;
        }
    </style>
</head>

<body>

    @php
    use Carbon\Carbon;

    $candidateCode = $salary->candidate->candidate_code ?? 'CID';

    $billingDate = Carbon::create($salary->year, $salary->month, 1);
    $billingMonth = $billingDate->format('m');
    $lastDate = $billingDate->endOfMonth()->format('d');
    $yearShort = $billingDate->format('y');
    $invoiceDatePart = $billingDate->endOfMonth()->format('dmy');

    $invoiceNumber = $candidateCode . '-' . $invoiceDatePart;

    $tdsRate = 0.02;

    // Net values
    $baseNet = $salary->net_pay ?? 0;
    $sundayNet = $salary->extra_amount ?? 0;
    $arrearNet = $salary->arrear_amount ?? 0;

    // Reverse calculate each separately
    $baseGrossRaw = $baseNet > 0 ? ($baseNet / (1 - $tdsRate)) : 0;
    $sundayGrossRaw = $sundayNet > 0 ? ($sundayNet / (1 - $tdsRate)) : 0;
    $arrearGrossRaw = $arrearNet > 0 ? ($arrearNet / (1 - $tdsRate)) : 0;

    // Round each to nearest rupee
    $baseGross = round($baseGrossRaw);
    $sundayGross = round($sundayGrossRaw);
    $arrearGross = round($arrearGrossRaw);

    // Recalculate total from rounded values
    $grossTotal = $baseGross + $sundayGross + $arrearGross;

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


        <div class="header">
        <div class="invoice-title">INVOICE</div>
        <div>Invoice No: {{ $invoiceNumber }}</div>
        </div>

        <!-- SERVICE PROVIDER DETAILS CENTER -->
        <div class="provider-details">
            <strong>{{ strtoupper($salary->candidate->candidate_name ?? '') }}</strong><br>

            @if(!empty($salary->candidate->address_line_1))
            {{ $salary->candidate->address_line_1 }}<br>
            @endif

            @php
            $city = $salary->candidate->cityMaster->name ?? null;
            $state = $salary->candidate->residenceState->state_name ?? null;
            $pin = $salary->candidate->pin_code ?? null;

            $locationParts = array_filter([$city, $state]);
            $locationLine = implode(', ', $locationParts);

            if($pin){
            $locationLine .= ' - ' . $pin;
            }
            @endphp

            @if(!empty($locationLine))
            {{ $locationLine }}<br>
            @endif

            @if(!empty($salary->candidate->pan_no))
            PAN: {{ $salary->candidate->pan_no }}<br>
            @endif

            @if(!empty($salary->candidate->mobile_no))
            Mobile: {{ $salary->candidate->mobile_no }}
            @endif
        </div>

        <!-- BILL TO -->
        <div class="section">
            <strong>Bill To:</strong><br>
            VNR Seeds Pvt. Ltd.<br>
            Corporate Office: VNR Seeds Pvt. Ltd.,<br>
            Dhamtari Road, Raipur, Chhattisgarh - 492001<br>
            GSTIN: 22AAACV1234F1Z5
        </div>

        <table>
            <thead>
                <tr>
                    <th width="70%">Description</th>
                    <th width="30%" class="right">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Contractual service charges for the month of
                        {{ date('F Y', strtotime("{$salary->year}-{$salary->month}-01")) }}
                        as per agreement.
                    </td>
                    <td class="right">{{ number_format($baseGross) }}</td>
                </tr>

                @if($sundayNet > 0)
                <tr>
                    <td>Charges for additional services (Sunday)</td>
                    <td class="right">{{ number_format($sundayGross) }}</td>
                </tr>
                @endif

                @if($arrearNet > 0)
                <tr>
                    <td>Charges for services not covered above (Arrear)</td>
                    <td class="right">{{ number_format($arrearGross) }}</td>
                </tr>
                @endif

                <tr class="total-row">
                    <td>Total Invoice Amount</td>
                    <td class="right">₹ {{ number_format($grossTotal, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="amount-words">
            Amount in Words: {{ numberToWords((int)$grossTotal) }} Rupees Only
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
        </div>

</body>

</html>