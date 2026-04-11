<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bill of Summary</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #111827; }
        h1 { margin: 0 0 12px; color: #0f4c81; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
        .totals { margin-top: 14px; width: 320px; margin-left: auto; }
        .row { display:flex; justify-content:space-between; margin-bottom:6px; }
        .print-btn { position: fixed; right: 12px; top: 12px; }
        @media print { .print-btn { display:none; } body { margin: 0; } }
    </style>
</head>
<body>
<button class="print-btn" onclick="window.print()">Print</button>
<h1>Bill of Summary</h1>
<div>Invoice: {{ $invoice->invoice_number ?? '-' }} | Patient: {{ $booking->patient?->full_name ?? '-' }}</div>
<table>
    <thead>
        <tr>
            <th>Charge Head</th>
            <th>Percentage</th>
            <th>Amount (INR)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($lineItems as $item)
            <tr>
                <td>{{ $item['head'] ?? '-' }}</td>
                <td>{{ isset($item['percentage']) && $item['percentage'] !== null ? number_format((float) $item['percentage'], 2).'%' : '-' }}</td>
                <td>{{ number_format((float) ($item['amount'] ?? 0), 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="3">No line items found.</td></tr>
        @endforelse
    </tbody>
</table>
<div class="totals">
    <div class="row"><span>Total</span><strong>{{ number_format((float) ($invoice->total_amount ?? 0), 2) }}</strong></div>
    <div class="row"><span>Tax</span><strong>{{ number_format((float) ($invoice->tax_amount ?? 0), 2) }}</strong></div>
    <div class="row"><span>Discount</span><strong>{{ number_format((float) ($invoice->discount ?? 0), 2) }}</strong></div>
    <div class="row"><span>Net Amount</span><strong>{{ number_format((float) ($invoice->net_amount ?? 0), 2) }}</strong></div>
</div>
</body>
</html>
