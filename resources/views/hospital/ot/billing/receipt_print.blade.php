<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OT Payment Receipt</title>
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
        .header {
            border-bottom: 2px solid #0f4c81;
            margin-bottom: 12px;
            padding-bottom: 8px;
        }
        .header h1 { margin: 0; color: #0f4c81; font-size: 16px; }
        .meta { font-size: 11px; line-height: 1.6; margin-top: 4px; color: #4b5563; }
        .box {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .box h3 { margin: 0 0 8px; font-size: 12px; color: #0f172a; }
        .row { display: flex; justify-content: space-between; gap: 8px; margin-bottom: 6px; font-size: 11px; }
        .label { color: #6b7280; }
        .value { font-weight: 700; color: #111827; text-align: right; }
        .amount {
            font-size: 20px;
            font-weight: 800;
            color: #0f4c81;
            text-align: center;
            margin: 14px 0;
        }
        .print-btn {
            position: fixed; right: 12px; top: 12px; border: 0;
            background: #0f4c81; color: #fff; padding: 8px 12px; border-radius: 8px; cursor: pointer;
        }
        .back-btn {
            position: fixed; right: 12px; top: 52px; border: 1px solid #0f4c81;
            background: #fff; color: #0f4c81; padding: 8px 12px; border-radius: 8px; cursor: pointer; text-decoration: none; font-size: 12px;
        }
        @media print {
            body { background: #fff; }
            .page { margin: 0; box-shadow: none; width: auto; min-height: auto; }
            .print-btn, .back-btn { display: none; }
        }
    </style>
</head>
<body>
<button class="print-btn" onclick="window.print()">Print Receipt</button>
<a class="back-btn" href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug]) }}">Back to Queue</a>

@php $letterPad = hospital_setting('letter_pad', 'unavailable'); @endphp
<div class="page">
    @if($letterPad === 'available')
        <div class="header">
            <h1>Payment Receipt</h1>
            <div class="meta">Receipt: {{ $payment->receipt_number ?? '-' }}</div>
        </div>
    @else
        <div class="header" style="display:flex;align-items:center;gap:10px;">
            <div style="width:56px;height:56px;border-radius:10px;background:#F8FAFC;border:1px solid #E5E7EB;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                @if(hospital_logo_url())
                    <img src="{{ hospital_logo_url() }}" alt="{{ hospital_name() }} logo" style="width:100%;height:100%;object-fit:contain;padding:6px;">
                @else
                    <span style="font-size:22px;color:#0f4c81">👁</span>
                @endif
            </div>
            <div>
                <h1>{{ hospital_name() }}</h1>
                <div class="meta">Payment Receipt · {{ $payment->receipt_number ?? '-' }}</div>
            </div>
        </div>
    @endif

    @if(session('success'))
        <div class="box" style="background:#ecfdf5;border-color:#a7f3d0;color:#065f46;">{{ session('success') }}</div>
    @endif

    <div class="amount">{{ money_code((float) $payment->package_amount, 2) }}</div>

    <div class="box">
        <h3>Patient</h3>
        <div class="row"><span class="label">Name</span><span class="value">{{ $booking->patient?->full_name ?? '-' }}</span></div>
        <div class="row"><span class="label">UHID</span><span class="value">{{ $booking->patient?->patient_code ?? '-' }}</span></div>
        <div class="row"><span class="label">Mobile</span><span class="value">{{ $booking->patient?->contact_no ?? '-' }}</span></div>
    </div>

    <div class="box">
        <h3>Payment Details</h3>
        <div class="row"><span class="label">Receipt No.</span><span class="value">{{ $payment->receipt_number ?? '-' }}</span></div>
        <div class="row"><span class="label">Mode</span><span class="value">{{ strtoupper((string) $payment->payment_mode) }}</span></div>
        <div class="row"><span class="label">Mediclaim</span><span class="value">{{ $payment->has_mediclaim ? 'Yes' : 'No' }}</span></div>
        <div class="row"><span class="label">Paid At</span><span class="value">{{ optional($payment->paid_at)->format('d M Y, h:i A') ?? '-' }}</span></div>
        <div class="row"><span class="label">Recorded By</span><span class="value">{{ $payment->recordedBy?->name ?? '-' }}</span></div>
    </div>

    <div class="box">
        <h3>Package Balance</h3>
        <div class="row"><span class="label">Package Total</span><span class="value">{{ money_code($requiredTotal, 2) }}</span></div>
        <div class="row"><span class="label">Total Paid</span><span class="value">{{ money_code($totalPaid, 2) }}</span></div>
        <div class="row"><span class="label">Remaining</span><span class="value">{{ money_code(max(0, $requiredTotal - $totalPaid), 2) }}</span></div>
        <div class="row"><span class="label">Status</span><span class="value">{{ strtoupper(str_replace('_', ' ', (string) $booking->payment_status)) }}</span></div>
    </div>

    <p class="meta" style="margin-top:16px;">This is a computer-generated receipt for OT surgery charges.</p>
</div>
</body>
</html>
