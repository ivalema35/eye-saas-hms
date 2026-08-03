<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bill Summery</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 16mm 18mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            background: #f4f6fb;
            color: #111;
            font-size: 14px;
            line-height: 1.5;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 12px auto;
            background: #fff;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            padding: 18mm 22mm;
            box-sizing: border-box;
        }
        .title {
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-decoration: underline;
            text-underline-offset: 4px;
            text-transform: uppercase;
            margin: 0 0 22px;
        }
        .meta {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .for-block {
            margin-bottom: 22px;
        }
        .for-block .for-label {
            margin-bottom: 4px;
        }
        .for-block .patient-name {
            font-weight: 700;
            font-size: 16px;
            text-transform: uppercase;
            margin: 0 0 4px;
        }
        .for-block .place {
            margin: 0;
        }
        table.charges {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        table.charges th {
            text-align: left;
            padding: 6px 4px 10px;
            border-bottom: 1px solid #111;
            font-size: 14px;
        }
        table.charges th.amt {
            text-align: right;
        }
        table.charges td {
            padding: 7px 4px;
            vertical-align: top;
        }
        table.charges td.amt {
            text-align: right;
            white-space: nowrap;
        }
        table.charges tr.total-row td {
            border-top: 1px solid #111;
            padding-top: 10px;
            font-weight: 700;
        }
        .sign-block {
            margin-top: 64px;
            width: 42%;
            margin-left: auto;
            text-align: center;
            border: 1.5px solid #5b2c8a;
            padding: 14px 10px;
            color: #5b2c8a;
        }
        .sign-block .for-hosp {
            margin: 0 0 28px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .sign-block .role {
            margin: 0;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .print-btn {
            position: fixed;
            right: 12px;
            top: 12px;
            border: 0;
            background: #0f4c81;
            color: #fff;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
        }
        @media print {
            body { background: #fff; }
            .page {
                width: 100%;
                min-height: auto;
                margin: 0;
                box-shadow: none;
                padding: 0;
            }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
@php
    $patient = $booking->patient;
    $billNo = $invoice->invoice_number
        ?? $patient?->patient_code
        ?? (string) $booking->id;

    $billDate = ! empty($invoice->created_at)
        ? \Illuminate\Support\Carbon::parse($invoice->created_at)->format('d/m/Y')
        : (optional($booking->discharged_at)->format('d/m/Y') ?? now()->format('d/m/Y'));

    $patientName = strtoupper(trim((string) ($patient?->full_name ?? '-')));
    $place = strtoupper(trim((string) ($patient?->city_name ?: $patient?->location_label ?: '-')));

    $items = is_array($lineItems ?? null) ? array_values($lineItems) : [];
    $items = array_values(array_filter($items, function ($item) {
        return is_array($item) && (float) ($item['amount'] ?? 0) > 0;
    }));

    $computedTotal = collect($items)->sum(fn ($item) => (float) ($item['amount'] ?? 0));
    $displayTotal = (float) ($invoice->net_amount ?? 0) > 0
        ? (float) $invoice->net_amount
        : ((float) ($invoice->total_amount ?? 0) > 0
            ? (float) $invoice->total_amount
            : $computedTotal);
@endphp

<button class="print-btn" onclick="window.print()">Print</button>

<div class="page">
    <h1 class="title">Bill Summery</h1>

    <div class="meta">
        <div><strong>Bill no:</strong> {{ $billNo }}</div>
        <div><strong>DATE :</strong>{{ $billDate }}</div>
    </div>

    <div class="for-block">
        <div class="for-label">For,</div>
        <p class="patient-name">{{ $patientName }}</p>
        <p class="place"><strong>Place:</strong> {{ $place }}</p>
    </div>

    <table class="charges">
        <thead>
            <tr>
                <th>Charge for</th>
                <th class="amt">Amount ({{ currency_symbol() }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                <tr>
                    <td>{{ ($index + 1) }} {{ $item['head'] ?? 'Charge' }}</td>
                    <td class="amt">{{ number_format((float) ($item['amount'] ?? 0), 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">No charges found.</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td>TOTAL</td>
                <td class="amt">{{ number_format($displayTotal, 0) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="sign-block">
        <p class="for-hosp">For, {{ strtoupper(hospital_name()) }}</p>
        <p class="role">Accountant</p>
    </div>
</div>
</body>
</html>
