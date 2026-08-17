<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificate</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 13mm 14mm;
        }
        body {
            font-family: "Times New Roman", Times, Georgia, serif;
            margin: 0;
            background: #fff;
            color: #111;
            font-size: 13px;
            line-height: 1.5;
        }
        /* Base styles must already fit inside @page's own margin — DomPDF
           (used for the app's PDF export) ignores @media print entirely, so
           a "card preview" width/padding here would overflow the printable
           canvas and get clipped. Real browsers reset to this exact layout
           via @media print anyway, so there's no visual loss when printing
           from web. See OT_DISCHARGE_INVOICES_WEB_PARITY_FIX_PLAN.md
           Addendum (PDF clipping bug). */
        .page {
            width: 100%;
            min-height: auto;
            margin: 0;
            background: #fff;
            padding: 0;
            box-sizing: border-box;
            position: relative;
        }
        .date-row {
            text-align: right;
            font-size: 13px;
            letter-spacing: 0.02em;
            margin-bottom: 18px;
        }
        .title {
            text-align: center;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-decoration: underline;
            text-underline-offset: 4px;
            margin: 0 0 22px;
            text-transform: uppercase;
        }
        .body-text {
            text-align: justify;
            font-size: 13.5px;
            line-height: 1.7;
            margin: 0;
        }
        .body-text .hl {
            font-weight: 700;
            text-transform: uppercase;
        }
        .sign-block {
            margin-top: 36px;
            width: 58%;
            margin-left: auto;
            text-align: center;
        }
        .sign-img {
            max-height: 52px;
            max-width: 160px;
            margin-bottom: 4px;
            object-fit: contain;
        }
        .sign-line {
            min-height: 40px;
            margin-bottom: 4px;
        }
        .doctor-name {
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            margin: 0;
        }
        .doctor-meta {
            font-size: 13px;
            margin: 2px 0 0;
            line-height: 1.45;
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
            font-family: Arial, sans-serif;
            font-size: 13px;
        }
        @media print {
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
@php
    $patient = $booking->patient;
    $doctor = $booking->otDoctor;
    $patientName = strtoupper(trim((string) ($patient?->full_name ?? '-')));

    $eyeRaw = strtoupper(trim((string) ($booking->eye ?? '')));
    $eyeLabel = match (true) {
        in_array($eyeRaw, ['RE', 'OD', 'RIGHT', 'R'], true) => 'RIGHT',
        in_array($eyeRaw, ['LE', 'OS', 'LEFT', 'L'], true) => 'LEFT',
        in_array($eyeRaw, ['BOTH', 'BE', 'BILATERAL'], true) => 'BOTH',
        default => ($eyeRaw !== '' ? $eyeRaw : '—'),
    };

    $surgeryName = trim((string) ($surgery->surgery_name ?? $booking->ot_type ?? 'cataract'));
    $surgeryLabel = mb_strtolower($surgeryName);

    $gender = strtolower(trim((string) ($patient?->gender ?? '')));
    $pronoun = match (true) {
        in_array($gender, ['f', 'female', 'woman', 'girl'], true) => 'She',
        in_array($gender, ['m', 'male', 'man', 'boy'], true) => 'He',
        default => 'She/He',
    };

    $certDate = optional($booking->discharged_at)->format('d/m/Y')
        ?? now()->format('d/m/Y');

    $surgeryAt = $booking->operated_at
        ?? ($surgery?->surgery_at ?? null)
        ?? ($booking->surgery_date ? $booking->surgery_date->copy()->setTime(9, 0) : null);

    // Print times: always morning 9:00 AM → evening 5:00 PM (dates from actual records).
    $admitDate = $booking->attended_at
        ?? $surgeryAt
        ?? ($booking->surgery_date ? $booking->surgery_date->copy() : null);
    $admittedAt = $admitDate ? $admitDate->copy()->setTime(9, 0) : null;

    $dischargeDate = $booking->discharged_at
        ?? ($booking->surgery_date ? $booking->surgery_date->copy() : now());
    $dischargedAt = $dischargeDate->copy()->setTime(17, 0);

    $restDays = max(1, (int) ($restDays ?? 7));
    $restFrom = $surgeryAt?->copy()?->startOfDay()
        ?? ($booking->surgery_date?->copy()?->startOfDay() ?? now()->startOfDay());
    $fitFrom = $restFrom->copy()->addDays($restDays);

    $fmtDate = fn ($dt) => $dt ? $dt->format('d/m/Y') : '—';
    $fmtTime = fn ($dt) => $dt ? $dt->format('h:i A') : '—';

    $doctorName = trim((string) ($doctor?->name ?? 'Medical Officer'));
    if ($doctorName !== '' && ! str_starts_with(strtoupper($doctorName), 'DR')) {
        $doctorDisplay = 'Dr '.$doctorName;
    } else {
        $doctorDisplay = $doctorName !== '' ? $doctorName : 'Medical Officer';
    }
    $regNo = trim((string) ($doctor?->registration_no ?? ''));
    $signatureUrl = ! empty($doctor?->signature_path)
        ? asset('storage/'.$doctor->signature_path)
        : null;
@endphp

<button class="print-btn" onclick="window.print()">Print</button>

<div class="page">
    <div class="date-row">DATE: {{ $certDate }}</div>

    <h1 class="title">Certificate</h1>

    <p class="body-text">
        This is to certify that <span class="hl">{{ $patientName }}</span>
        was operated for <span class="hl">{{ $eyeLabel }}</span> Eye
        {{ $surgeryLabel }} with intra ocular lens implantation on
        {{ $fmtDate($surgeryAt) }}.
        {{ $pronoun }} was admitted on {{ $fmtDate($admittedAt) }} at {{ $fmtTime($admittedAt) }}
        &amp; Discharge on {{ $fmtDate($dischargedAt) }} at {{ $fmtTime($dischargedAt) }}.
        {{ $pronoun }} is advised rest for {{ $restDays }} days from {{ $fmtDate($restFrom) }}.
        {{ $pronoun }} may be fit for duty from {{ $fmtDate($fitFrom) }}.
    </p>

    <div class="sign-block">
        <div class="sign-line">
            @if($signatureUrl)
                <img src="{{ $signatureUrl }}" alt="Signature" class="sign-img">
            @endif
        </div>
        <p class="doctor-name">{{ strtoupper($doctorDisplay) }}</p>
        <p class="doctor-meta">Medical Officer</p>
        <p class="doctor-meta">{{ hospital_name() }}</p>
        @if(hospital_full_address())
            <p class="doctor-meta">{{ hospital_full_address() }}</p>
        @endif
        @if($regNo !== '')
            <p class="doctor-meta">Reg.No.{{ $regNo }}</p>
        @endif
    </div>
</div>
</body>
</html>
