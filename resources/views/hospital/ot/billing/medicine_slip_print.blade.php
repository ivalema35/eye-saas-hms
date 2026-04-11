<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Take-Home Medicine Slip</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #111827; }
        h1 { margin: 0 0 12px; color: #0f4c81; }
        ul { margin-top: 8px; }
        li { margin-bottom: 6px; }
        .print-btn { position: fixed; right: 12px; top: 12px; }
        @media print { .print-btn { display:none; } body { margin: 0; } }
    </style>
</head>
<body>
<button class="print-btn" onclick="window.print()">Print</button>
<h1>Take-Home Medicine Slip</h1>
<div>Patient: {{ $booking->patient?->full_name ?? '-' }} | Doctor: {{ $booking->otDoctor?->name ?? '-' }}</div>
<div>Date: {{ optional($booking->discharged_at)->format('d M Y h:i A') ?? now()->format('d M Y h:i A') }}</div>

@if(!empty($wardMedicines))
    <ul>
        @foreach($wardMedicines as $medicine)
            <li><strong>{{ $medicine['medicine'] ?? 'Medicine' }}</strong> @if(!empty($medicine['dose'])) - {{ $medicine['dose'] }} @endif</li>
        @endforeach
    </ul>
@else
    <p>No take-home medicines recorded.</p>
@endif
</body>
</html>
