<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Discharge Card</title>
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
            padding: 18mm 20mm;
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
        .row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 8px;
        }
        .field {
            margin-bottom: 8px;
        }
        .field .k,
        .row .k {
            font-weight: 600;
        }
        .half {
            flex: 1 1 0;
            min-width: 0;
        }
        .clinical {
            margin-top: 18px;
        }
        .clinical .field {
            margin-bottom: 10px;
        }
        .rx-title {
            margin: 22px 0 10px;
            font-size: 16px;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 3px;
        }
        .rx-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .rx-list li {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 3px 0;
            border-bottom: 1px dotted #d1d5db;
        }
        .rx-list li:last-child {
            border-bottom: 0;
        }
        .rx-qty {
            white-space: nowrap;
            font-weight: 600;
        }
        .muted {
            color: #6b7280;
            font-style: italic;
        }
        .sign-block {
            margin-top: 56px;
            width: 46%;
            margin-left: auto;
            text-align: center;
        }
        .sign-line {
            min-height: 40px;
            margin-bottom: 4px;
        }
        .sign-img {
            max-height: 52px;
            max-width: 160px;
            object-fit: contain;
        }
        .doctor-name {
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            margin: 0;
        }
        .doctor-meta {
            font-size: 12px;
            margin: 2px 0 0;
            line-height: 1.4;
        }
        .print-btn {
            position: fixed;
            top: 14px;
            right: 14px;
            border: 0;
            background: #0d5c63;
            color: #fff;
            padding: 10px 14px;
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
    $doctor = $booking->otDoctor;
    $counselling = $booking->counselling;

    $indoorNo = $patient?->patient_code ?? (string) $booking->id;
    $cardDate = optional($booking->discharged_at)->format('d/m/Y')
        ?? now()->format('d/m/Y');

    $patientName = strtoupper(trim((string) ($patient?->full_name ?? '-')));
    $address = strtoupper(trim((string) ($patient?->city_name ?: $patient?->location_label ?: '-')));
    $age = $patient?->age !== null && $patient?->age !== '' ? (string) $patient->age : '—';

    $genderRaw = strtoupper(trim((string) ($patient?->gender ?? '')));
    $sex = match (true) {
        in_array($genderRaw, ['F', 'FEMALE', 'WOMAN', 'GIRL'], true) => 'FEMALE',
        in_array($genderRaw, ['M', 'MALE', 'MAN', 'BOY'], true) => 'MALE',
        $genderRaw !== '' => $genderRaw,
        default => '—',
    };

    $eyeRaw = strtoupper(trim((string) (optional($surgery)->eye_operated ?? $booking->eye ?? '')));
    $eyeLabel = match (true) {
        in_array($eyeRaw, ['RE', 'OD', 'RIGHT', 'R'], true) => 'RIGHT',
        in_array($eyeRaw, ['LE', 'OS', 'LEFT', 'L'], true) => 'LEFT',
        in_array($eyeRaw, ['BOTH', 'BE', 'BILATERAL'], true) => 'BOTH',
        default => ($eyeRaw !== '' ? $eyeRaw : '—'),
    };

    $surgeryName = trim((string) (optional($surgery)->surgery_name ?? $booking->ot_type ?? 'cataract'));
    $surgeryLower = mb_strtolower($surgeryName);
    $diagnosisHint = trim((string) ($counselling?->diagnosis ?? ''));
    $diagnosis = $diagnosisHint !== ''
        ? strtoupper($eyeLabel.' Eye '.$diagnosisHint)
        : strtoupper($eyeLabel.' Eye '.$surgeryLower);

    $operativeNote = strtoupper($eyeLabel).' Eye '.$surgeryLower.' extraction with IOL implantation';
    // If surgery name already mentions extraction/IOL, keep simpler operative wording.
    if (stripos($surgeryName, 'iol') !== false || stripos($surgeryName, 'extraction') !== false) {
        $operativeNote = strtoupper($eyeLabel).' Eye '.$surgeryName;
    }

    // Print times: always morning 9:00 AM → evening 5:00 PM (dates from actual records).
    $admitDate = $booking->attended_at
        ?? $booking->operated_at
        ?? ($booking->surgery_date ? $booking->surgery_date->copy() : null);
    $admittedAt = $admitDate ? $admitDate->copy()->setTime(9, 0) : null;

    $dischargeDate = $booking->discharged_at
        ?? ($booking->surgery_date ? $booking->surgery_date->copy() : now());
    $dischargedAt = $dischargeDate->copy()->setTime(17, 0);

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

    $medicines = is_array($wardMedicines ?? null) ? $wardMedicines : [];
@endphp

<button class="print-btn" onclick="window.print()">Print</button>

<div class="page">
    <h1 class="title">Discharge Card</h1>

    <div class="row">
        <div class="half"><span class="k">Indoor No. :</span> {{ $indoorNo }}</div>
        <div class="half" style="text-align:right;"><span class="k">DATE:</span> {{ $cardDate }}</div>
    </div>

    <div class="field"><span class="k">Name of Patient :</span> {{ $patientName }}</div>
    <div class="field"><span class="k">Address :</span> {{ $address }}</div>

    <div class="row">
        <div class="half"><span class="k">Age :</span> {{ $age }}</div>
        <div class="half"><span class="k">Sex:</span> {{ $sex }}</div>
    </div>

    <div class="row">
        <div class="half"><span class="k">Date of admission :</span> {{ $fmtDate($admittedAt) }}</div>
        <div class="half"><span class="k">Time:</span> {{ $fmtTime($admittedAt) }}</div>
    </div>

    <div class="row">
        <div class="half"><span class="k">Date of discharge :</span> {{ $fmtDate($dischargedAt) }}</div>
        <div class="half"><span class="k">Time :</span> {{ $fmtTime($dischargedAt) }}</div>
    </div>

    <div class="clinical">
        <div class="field"><span class="k">Diagnosis :</span> {{ $diagnosis }}</div>
        <div class="field"><span class="k">Operative Note :</span> {{ $operativeNote }}</div>
    </div>

    <div class="rx-title">Rx on Discharge:</div>
    @if(count($medicines) > 0)
        <ul class="rx-list">
            @foreach($medicines as $medicine)
                @php
                    $medName = trim((string) ($medicine['medicine'] ?? 'Medicine'));
                    $dose = trim((string) ($medicine['dose'] ?? ''));
                    $qtyRaw = $medicine['quantity'] ?? null;
                    $qty = $qtyRaw !== null && $qtyRaw !== ''
                        ? str_pad((string) (int) $qtyRaw, 2, '0', STR_PAD_LEFT)
                        : null;
                    $label = $dose !== '' ? $medName.' — '.$dose : $medName;
                @endphp
                <li>
                    <span>{{ $label }}</span>
                    @if($qty !== null)
                        <span class="rx-qty">({{ $qty }})</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <p class="muted">No take-home medicines recorded.</p>
    @endif

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
