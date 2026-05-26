<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription — {{ $patient->full_name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #1a1a1a; background: #fff; }

        .page { width: 210mm; min-height: 148mm; margin: 0 auto; padding: 12mm 14mm 10mm; }

        /* ── Header ── */
        .rx-header { display: flex; align-items: flex-start; justify-content: space-between;
            border-bottom: 2px solid #1a56a0; padding-bottom: 8px; margin-bottom: 10px; }

        .rx-logo {
            width: 72px;
            height: 72px;
            border-radius: 12px;
            background: #F8FAFC;
            border: 1px solid #E5E7EB;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-right: 12px;
        }

        .rx-logo img { width: 100%; height: 100%; object-fit: contain; padding: 8px; }

        .rx-logo span { font-size: 28px; color: #1a56a0; }
        .rx-header-hospital h1 { font-size: 20px; font-weight: 700; color: #1a56a0; letter-spacing: .02em; }
        .rx-header-hospital p  { font-size: 11px; color: #555; margin-top: 2px; }
        .rx-header-doctor { text-align: right; font-size: 11px; color: #444; }
        .rx-header-doctor strong { font-size: 13px; color: #1a56a0; display: block; }

        /* ── Patient Info ── */
        .rx-patient { background: #f0f5ff; border: 1px solid #c7d9f5; border-radius: 6px;
            padding: 7px 12px; margin-bottom: 10px; display: flex; flex-wrap: wrap; gap: 12px; }
        .rx-patient-field { display: flex; flex-direction: column; }
        .rx-patient-field label { font-size: 9px; text-transform: uppercase; letter-spacing: .06em; color: #666; }
        .rx-patient-field span { font-weight: 600; font-size: 12px; }

        /* ── Section headings ── */
        .rx-section { margin-bottom: 10px; }
        .rx-section-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
            color: #1a56a0; border-bottom: 1px solid #c7d9f5; padding-bottom: 3px; margin-bottom: 6px; }

        /* ── Vision table ── */
        table.rx-table { width: 100%; border-collapse: collapse; font-size: 11.5px; }
        table.rx-table th { background: #e8f0fb; padding: 4px 6px; text-align: center;
            border: 1px solid #c7d9f5; font-weight: 600; font-size: 10.5px; }
        table.rx-table td { padding: 3px 6px; border: 1px solid #c7d9f5; text-align: center; }
        table.rx-table td.label-cell { text-align: left; font-weight: 600; font-size: 10.5px;
            background: #f5f8ff; white-space: nowrap; }

        /* ── Diagnosis tags ── */
        .rx-tags { display: flex; flex-wrap: wrap; gap: 4px; }
        .rx-tag { background: #e8f0fb; border: 1px solid #c7d9f5; border-radius: 99px;
            padding: 2px 10px; font-size: 10.5px; }

        /* ── Medicine table ── */
        table.rx-medicines { width: 100%; border-collapse: collapse; font-size: 11.5px; margin-top: 4px; }
        table.rx-medicines th { background: #1a56a0; color: #fff; padding: 5px 7px;
            font-size: 10.5px; font-weight: 600; text-align: left; }
        table.rx-medicines td { padding: 4px 7px; border-bottom: 1px solid #e0e8f5; vertical-align: top; }
        table.rx-medicines tr:nth-child(even) td { background: #f8faff; }
        .med-type { font-size: 9.5px; color: #666; }

        /* ── Footer ── */
        .rx-footer { border-top: 1px solid #c7d9f5; margin-top: 14px; padding-top: 8px;
            display: flex; justify-content: space-between; align-items: flex-end; }
        .rx-followup { font-size: 11px; color: #333; }
        .rx-followup strong { color: #1a56a0; }
        .rx-signature { text-align: right; font-size: 11px; }
        .rx-signature .sig-line { border-top: 1px solid #333; width: 140px;
            margin: 30px 0 3px auto; }

        .rx-special-advice { font-size: 11.5px; color: #444; font-style: italic; margin-top: 4px; }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .page { padding: 8mm 12mm; }
        }
    </style>
</head>
<body>

{{-- Print / Back controls --}}
<div class="no-print" style="background:#f0f0f0;padding:8px 14mm;display:flex;gap:12px;align-items:center">
    <button onclick="window.print()"
            style="background:#1a56a0;color:#fff;border:none;padding:7px 20px;border-radius:5px;cursor:pointer;font-size:13px">
        <i class="fa-solid fa-print"></i> Print
    </button>
    <a href="javascript:history.back()"
       style="color:#1a56a0;font-size:13px;text-decoration:none">← Back</a>
</div>

@php
    // Provide safe defaults to avoid errors when variables aren't passed (e.g., auto-print after registration)
    $complaintMasters = $complaintMasters ?? collect();
    $kcoMasters       = $kcoMasters ?? collect();
    $diagnosisMasters = $diagnosisMasters ?? collect();
    $adviceMasters    = $adviceMasters ?? collect();

    $patient = $patient ?? (object) [
        'full_name' => '—',
        'patient_code' => '—',
        'age' => '—',
        'gender' => '—',
        'contact_no' => null,
        'appointment_date' => null,
    ];

    $exam = $exam ?? (object) [
        'exam_data' => [],
        'doctor' => null,
        'prescriptions' => collect(),
        'examined_at' => null,
    ];

    $ed        = $exam->exam_data ?? [];
    $vision    = $ed['vision']   ?? [];
    $st        = $ed['st']       ?? [];
    $nct       = $ed['nct']      ?? [];
    $oe        = $ed['oe']       ?? [];
    $fundus    = $ed['fundus']   ?? [];
    $diagnoses = $ed['diagnoses'] ?? [];
    $advices   = $ed['advices']   ?? [];
    $ccNames   = $complaintMasters->whereIn('id', $ed['complaints'] ?? [])->pluck('complaint')->implode(', ');
    $kcoNames  = $kcoMasters->whereIn('id', $ed['kcos'] ?? [])->pluck('kco')->implode(', ');
@endphp

<div class="page">

    {{-- ── Header ── --}}
    <div class="rx-header">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="rx-logo">
                @if(hospital_logo_url())
                    <img src="{{ hospital_logo_url() }}" alt="{{ hospital_name() }} logo">
                @else
                    <span>👁</span>
                @endif
            </div>
            <div class="rx-header-hospital">
                <h1>{{ hospital_name() }}</h1>
                <p>
                    @if(hospital_full_address()) {{ hospital_full_address() }} @endif
                    @if(hospital_contact_number()) &nbsp;|&nbsp; {{ hospital_contact_number() }} @endif
                    @if(hospital_official_email()) &nbsp;|&nbsp; {{ hospital_official_email() }} @endif
                </p>
            </div>
        </div>
        <div class="rx-header-doctor">
            <strong>{{ $exam->doctor?->name ?? '—' }}</strong>
            @if($exam->doctor?->designation ?? null)
                <span style="display:block">{{ $exam->doctor->designation }}</span>
            @endif
            <span style="display:block;margin-top:2px;color:#888">Date: {{ $exam->examined_at?->format('d M Y') ?? now()->format('d M Y') }}</span>
        </div>
    </div>

    {{-- ── Patient Info ── --}}
    <div class="rx-patient">
        <div class="rx-patient-field">
            <label>Patient</label>
            <span>{{ $patient->full_name }}</span>
        </div>
        <div class="rx-patient-field">
            <label>MRD</label>
            <span>{{ $patient->patient_code }}</span>
        </div>
        <div class="rx-patient-field">
            <label>Age / Gender</label>
            <span>{{ $patient->age }}y / {{ ucfirst($patient->gender) }}</span>
        </div>
        @if($patient->contact_no)
        <div class="rx-patient-field">
            <label>Contact</label>
            <span>{{ $patient->contact_no }}</span>
        </div>
        @endif
        <div class="rx-patient-field">
            <label>Visit Date</label>
            <span>{{ $patient->appointment_date?->format('d M Y') ?? $exam->examined_at?->format('d M Y') }}</span>
        </div>
    </div>

    {{-- ── Clinical History ── --}}
    @if($ccNames || $kcoNames || !empty($ed['complaint_duration']))
    <div class="rx-section">
        <div class="rx-section-title">Clinical History</div>
        @if($ccNames)
            <div class="mb-1">
                <strong>Chief Complaints:</strong> {{ $ccNames }}
                @if(!empty($ed['complaint_duration']))
                    &nbsp;<em>(Since: {{ $ed['complaint_duration'] }})</em>
                @endif
            </div>
        @endif
        @if($kcoNames)
            <div><strong>K/C/O:</strong> {{ $kcoNames }}</div>
        @endif
    </div>
    @endif

    {{-- ── Vision & Refraction ── --}}
    @php
        $hasVision = !empty($vision['vn_re']) || !empty($vision['vn_le']) || !empty($vision['pnvn_re']) || !empty($vision['nrvn_re']);
    @endphp

    @if($hasVision)
    <div class="rx-section">
        <div class="rx-section-title">Vision & Refraction</div>
        <table class="rx-table">
            <thead>
                <tr>
                    <th style="text-align:left;width:110px"></th>
                    <th colspan="2">RE (Right Eye)</th>
                    <th colspan="2">LE (Left Eye)</th>
                </tr>
                <tr>
                    <th></th>
                    <th>D.V. (VN)</th>
                    <th>Near (NV)</th>
                    <th>D.V. (VN)</th>
                    <th>Near (NV)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="label-cell">Without Glass</td>
                    <td>{{ $vision['vn_re'] ?? '—' }}</td>
                    <td>{{ $vision['nrvn_re'] ?? '—' }}</td>
                    <td>{{ $vision['vn_le'] ?? '—' }}</td>
                    <td>{{ $vision['nrvn_le'] ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Pinhole (PH)</td>
                    <td colspan="2">{{ $vision['pnvn_re'] ?? '—' }}</td>
                    <td colspan="2">{{ $vision['pnvn_le'] ?? '—' }}</td>
                </tr>
            </tbody>
        </table>

        @if(!empty($nct['iop_re']) || !empty($nct['iop_le']))
        <div style="margin-top:5px;font-size:11px">
            <strong>NCT / IOP (Intraocular Pressure):</strong>
            RE {{ $nct['iop_re'] ?? '—' }} mmHg &nbsp;|&nbsp;
            LE {{ $nct['iop_le'] ?? '—' }} mmHg
        </div>
        @endif
    </div>
    @endif

    {{-- ── Final Glass Prescription (ST) ── --}}
    @if(!empty($st['re']['ds']) || !empty($st['le']['ds']))
    <div class="rx-section">
        <div class="rx-section-title" style="display:flex;justify-content:space-between;align-items:baseline">
            <span>Final Glass Prescription</span>
            @if(!empty($st['add']))
                <span style="font-size:10px;font-weight:600;color:#1a56a0">
                    ADD: {{ $st['add'] }}
                    @if(!empty($st['lens_type'])) | {{ $st['lens_type'] }} @endif
                </span>
            @endif
        </div>
        <table class="rx-table">
            <thead>
                <tr>
                    <th rowspan="2" style="text-align:left;width:110px"></th>
                    <th colspan="3">Distance</th>
                    <th colspan="3">Near</th>
                </tr>
                <tr>
                    <th>SPH</th><th>CYL</th><th>AXIS</th>
                    <th>SPH</th><th>CYL</th><th>AXIS</th>
                </tr>
            </thead>
            <tbody>
                @foreach(['re' => 'Right Eye', 'le' => 'Left Eye'] as $eye => $eLabel)
                <tr>
                    <td class="label-cell">{{ $eLabel }}</td>
                    <td>{{ $st[$eye]['ds'] ?? '—' }}</td>
                    <td>{{ $st[$eye]['dc'] ?? '—' }}</td>
                    <td>{{ $st[$eye]['ax'] ?? '—' }}</td>
                    <td>{{ $st[$eye]['ns'] ?? '—' }}</td>
                    <td>{{ $st[$eye]['nc'] ?? '—' }}</td>
                    <td>{{ $st[$eye]['na'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ── Ocular Examination & Fundus ── --}}
    @php
        $oeFields  = ['sac' => 'SAC', 'lid' => 'Lids', 'conj' => 'Conjunctiva', 'cornea' => 'Cornea', 'ac' => 'Ant. Chamber', 'iris' => 'Iris', 'pupil' => 'Pupil', 'lens' => 'Lens', 'em' => 'Extra Ocular Movement', 'covertest' => 'Cover Test'];
        $hasOe     = collect(array_keys($oeFields))->first(fn ($k) => !empty($oe[$k.'_re']) || !empty($oe[$k.'_le']));
        $hasFundus = !empty($fundus['disc_re']) || !empty($fundus['disc_le']) || !empty($fundus['fr_re']) || !empty($fundus['fr_le']);
    @endphp
    @if($hasOe || $hasFundus)
    <div class="rx-section">
        <div class="rx-section-title">Ocular Examination & Fundus</div>
        <table class="rx-table">
            <thead>
                <tr>
                    <th style="text-align:left">Parameters</th>
                    <th>RE (Right Eye)</th>
                    <th>LE (Left Eye)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($oeFields as $key => $label)
                    @if(!empty($oe[$key.'_re']) || !empty($oe[$key.'_le']))
                    <tr>
                        <td class="label-cell">{{ $label }}</td>
                        <td>{{ $oe[$key.'_re'] ?? '—' }}</td>
                        <td>{{ $oe[$key.'_le'] ?? '—' }}</td>
                    </tr>
                    @endif
                @endforeach
                @if(!empty($fundus['disc_re']) || !empty($fundus['disc_le']))
                <tr>
                    <td class="label-cell" style="color:#1a56a0">Fundus — Disc</td>
                    <td>{{ $fundus['disc_re'] ?? '—' }}</td>
                    <td>{{ $fundus['disc_le'] ?? '—' }}</td>
                </tr>
                @endif
                @if(!empty($fundus['fr_re']) || !empty($fundus['fr_le']))
                <tr>
                    <td class="label-cell" style="color:#1a56a0">Fundus — FR</td>
                    <td>{{ $fundus['fr_re'] ?? '—' }}</td>
                    <td>{{ $fundus['fr_le'] ?? '—' }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    @endif

    {{-- ── Diagnosis ── --}}
    @if(!empty($diagnoses) && $diagnosisMasters->isNotEmpty())
    <div class="rx-section">
        <div class="rx-section-title">Diagnosis</div>
        <div class="rx-tags">
            @foreach($diagnosisMasters as $d)
                @if(in_array($d->id, $diagnoses))
                    <span class="rx-tag">{{ $d->diagnosis }}</span>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Medicine Prescription ── --}}
    @if($exam->prescriptions->isNotEmpty())
    <div class="rx-section">
        <div class="rx-section-title" style="display:flex;justify-content:space-between">
            <span>Medicines</span>
            <span style="font-size:9px;font-weight:400;font-style:italic;color:#777">Rx</span>
        </div>
        <table class="rx-medicines">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Medicine</th>
                    <th>Dosage</th>
                    <th>Duration</th>
                    <th>Mode/Eye</th>
                    <th>Instructions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($exam->prescriptions as $i => $rx)
                <tr>
                    <td style="text-align:center">{{ $i + 1 }}</td>
                    <td>
                        <span style="font-weight:600">{{ $rx->medicine?->name ?? '—' }}</span>
                        @if($rx->medicine?->brand_name)
                            <br><span class="med-type">{{ $rx->medicine->brand_name }}</span>
                        @endif
                        @if($rx->medicine?->medicineType)
                            <span class="med-type"> ({{ $rx->medicine->medicineType->name }})</span>
                        @endif
                    </td>
                    <td>{{ $rx->dosage?->dosage ?? '—' }}</td>
                    <td>{{ $rx->duration ? $rx->duration . ' Days' : '—' }}</td>
                    <td>{{ $rx->eye ?? '—' }}</td>
                    <td>{{ $rx->instructions ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ── Advice ── --}}
    @if(!empty($advices) && $adviceMasters->isNotEmpty())
    <div class="rx-section">
        <div class="rx-section-title">Advice</div>
        <div class="rx-tags">
            @foreach($adviceMasters as $a)
                @if(in_array($a->id, $advices))
                    <span class="rx-tag">{{ $a->advice }}</span>
                @endif
            @endforeach
        </div>
        @if(!empty($ed['special_advice']))
            <p class="rx-special-advice" style="margin-top:5px">{{ $ed['special_advice'] }}</p>
        @endif
    </div>
    @endif

    {{-- ── Footer (Follow-up + Signature) ── --}}
    <div class="rx-footer">
        <div class="rx-followup">
            @if(!empty($ed['followup_date']) || !empty($ed['followup_duration']))
                <strong>Follow-up:</strong>
                @if(!empty($ed['followup_date']))
                    {{ \Carbon\Carbon::parse($ed['followup_date'])->format('d M Y') }}
                @endif
                @if(!empty($ed['followup_duration']))
                    ({{ $ed['followup_duration'] }})
                @endif
            @endif
        </div>
        <div class="rx-signature">
            <div class="sig-line"></div>
            <div>{{ $exam->doctor?->name ?? '' }}</div>
            <div style="font-size:10px;color:#666">Signature / Stamp</div>
        </div>
    </div>

</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
      integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uf0aJSUYjaQfXArGPgql7EiSBEeP4MNFxZJR2A=="
      crossorigin="anonymous" referrerpolicy="no-referrer" />
</body>
</html>
