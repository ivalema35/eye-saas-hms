<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Operation Certificate</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #111827; }
        .head { border-bottom: 2px solid #0f4c81; padding-bottom: 8px; margin-bottom: 16px; }
        .head h1 { margin: 0; color: #0f4c81; }
        .line { margin-bottom: 8px; }
        .label { color: #6b7280; }
        .print-btn { position: fixed; right: 12px; top: 12px; }
        @media print { .print-btn { display:none; } body { margin: 0; } }
    </style>
</head>
<body>
<button class="print-btn" onclick="window.print()">Print</button>
<div class="head">
    <h1>Operation Certificate</h1>
    <div>{{ config('app.name') }} | Tenant: {{ $slug }}</div>
</div>
<div class="line"><span class="label">Patient:</span> {{ $booking->patient?->full_name ?? '-' }}</div>
<div class="line"><span class="label">Patient Code:</span> {{ $booking->patient?->patient_code ?? '-' }}</div>
<div class="line"><span class="label">Doctor:</span> {{ $booking->otDoctor?->name ?? '-' }}</div>
<div class="line"><span class="label">Surgery Performed:</span> {{ $surgery->surgery_name ?? $booking->ot_type ?? '-' }}</div>
<div class="line"><span class="label">Date of Surgery:</span> {{ optional($booking->operated_at)->format('d M Y h:i A') ?? optional($booking->surgery_date)->format('d M Y') }}</div>
<div class="line"><span class="label">Complication Status:</span> {{ strtoupper((string) (optional($surgery)->complication_status ?? 'none')) }}</div>
</body>
</html>
