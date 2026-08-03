<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lens Implant Details</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 8mm;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f4f6fb;
            color: #111827;
            font-size: 12px;
        }
        .page {
            width: 148mm;
            min-height: 210mm;
            margin: 12px auto;
            background: #fff;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            padding: 8mm;
            box-sizing: border-box;
        }
        .header { border-bottom: 2px solid #0f4c81; margin-bottom: 10px; padding-bottom: 6px; }
        .header h1 { margin: 0; color: #0f4c81; font-size: 16px; }
        .meta { font-size: 11px; margin-bottom: 10px; }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px 12px;
            font-size: 11px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
        }
        .label { color: #6b7280; display: block; }
        .value { font-weight: 700; color: #111827; }
        .implant-badge {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }
        .implant-yes { background: #dcfce7; color: #166534; }
        .implant-no { background: #fee2e2; color: #991b1b; }
        .print-btn { position: fixed; right: 12px; top: 12px; border: 0; background: #0f4c81; color: #fff; padding: 8px 12px; border-radius: 8px; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .page { width: 100%; min-height: auto; margin: 0; box-shadow: none; padding: 0; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
<button class="print-btn" onclick="window.print()">Print</button>

<div class="page">
    @php $letterPad = hospital_setting('letter_pad', 'unavailable'); @endphp
    @if($letterPad === 'available')
        <div class="header">
            <h1>Lens Implant Details</h1>
        </div>
    @else
        <div class="header" style="display:flex;align-items:center;gap:10px;">
            <div style="width:56px;height:56px;border-radius:12px;background:#F8FAFC;border:1px solid #E5E7EB;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                @if(hospital_logo_url())
                    <img src="{{ hospital_logo_url() }}" alt="{{ hospital_name() }} logo" style="width:100%;height:100%;object-fit:contain;padding:6px;">
                @else
                    <span style="font-size:22px;color:#0f4c81">👁</span>
                @endif
            </div>
            <div>
                <h1>{{ hospital_name() }} - Lens Implant Details</h1>
                <div style="font-size:11px;color:#4b5563;">Tenant: {{ $slug }}</div>
            </div>
        </div>
    @endif

    <div class="meta">
        <div><strong>Patient:</strong> {{ $booking->patient?->full_name ?? '-' }} ({{ $booking->patient?->patient_code ?? '-' }})</div>
        <div><strong>Doctor:</strong> {{ $booking->otDoctor?->name ?? '-' }} | <strong>Eye:</strong> {{ $booking->eye }}</div>
    </div>

    @if($lensDetail)
        <div class="grid">
            <div><span class="label">Manufacturer</span><span class="value">{{ $lensDetail->manufacturer ?? '-' }}</span></div>
            <div><span class="label">Lens Name</span><span class="value">{{ $lensDetail->lens_name ?? '-' }}</span></div>
            <div><span class="label">Lens Type</span><span class="value">{{ $lensDetail->lens_type ?? '-' }}</span></div>
            <div><span class="label">Power</span><span class="value">+{{ number_format((float) ($lensDetail->lens_power ?? 0), 2) }} D</span></div>
            <div><span class="label">Axis</span><span class="value">{{ $lensDetail->axis ? number_format((float) $lensDetail->axis, 2).'°' : '-' }}</span></div>
            <div><span class="label">MRP</span><span class="value">{{ money_code((float) ($lensDetail->lens_mrp ?? 0), 2) }}</span></div>
            <div><span class="label">Batch Number</span><span class="value">{{ $lensDetail->batch_number ?? '-' }}</span></div>
            <div><span class="label">Serial Number</span><span class="value">{{ $lensDetail->serial_number ?? '-' }}</span></div>
            <div><span class="label">Expiry Date</span><span class="value">{{ $lensDetail->expiry_date ? \Illuminate\Support\Carbon::parse($lensDetail->expiry_date)->format('d M Y') : '-' }}</span></div>
        </div>

        <span class="implant-badge {{ $lensDetail->is_implanted ? 'implant-yes' : 'implant-no' }}">
            {{ $lensDetail->is_implanted ? 'Implanted' : 'Not Implanted' }}
        </span>
    @else
        <p style="font-size:11px;color:#6b7280;">No lens details recorded for this booking.</p>
    @endif
</div>
</body>
</html>
