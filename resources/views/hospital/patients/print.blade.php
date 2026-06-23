<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OPD Bill — {{ $patient->patient_code }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', Arial, sans-serif; color: #111827; background: #F4F6F9; }

        .bill-container {
            max-width: 600px; margin: 20px auto; background: #fff;
            border: 1px solid #E5E7EB; border-radius: 8px; padding: 32px;
        }

        .bill-header { text-align: center; border-bottom: 2px solid #1B4F72; padding-bottom: 16px; margin-bottom: 20px; }
        .bill-header h1 { font-size: 20px; font-weight: 800; color: #0D2137; margin-bottom: 4px; }
        .bill-header p { font-size: 12px; color: #64748B; }
        .bill-logo {
            width: 72px;
            height: 72px;
            margin: 0 auto 8px;
            border-radius: 18px;
            background: #F8FAFC;
            border: 1px solid #E5E7EB;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .bill-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 8px;
        }

        .bill-logo span {
            font-size: 28px;
            color: #1B4F72;
        }

        .bill-title { text-align: center; font-size: 16px; font-weight: 700; color: #1B4F72; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 1px; }

        .bill-info { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 13px; }
        .bill-info-col { flex: 1; }
        .bill-info-col dt { font-weight: 600; color: #64748B; font-size: 11px; text-transform: uppercase; margin-bottom: 2px; }
        .bill-info-col dd { margin-bottom: 8px; color: #111827; }

        .bill-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .bill-table th { background: #F1F5F9; color: #374151; font-size: 11px; text-transform: uppercase; padding: 8px 12px; text-align: left; border-bottom: 2px solid #E5E7EB; }
        .bill-table td { padding: 10px 12px; border-bottom: 1px solid #F1F5F9; font-size: 13px; }
        .bill-table .total-row td { font-weight: 700; font-size: 14px; border-top: 2px solid #1B4F72; background: #F8FAFC; }

        .bill-footer { margin-top: 40px; display: flex; justify-content: space-between; font-size: 12px; color: #64748B; }
        .bill-signature { text-align: right; }
        .bill-signature-line { width: 160px; border-top: 1px solid #111827; margin-bottom: 4px; margin-left: auto; }

        .bill-actions { text-align: center; margin-top: 20px; }
        .btn-print {
            background: #1B4F72; color: #fff; border: none; padding: 10px 24px;
            border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;
            margin: 0 4px;
        }
        .btn-print:hover { background: #154360; }
        .btn-outline {
            background: #fff; color: #1B4F72; border: 1.5px solid #1B4F72;
            padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 600;
            cursor: pointer; text-decoration: none; margin: 0 4px;
        }

        @media print {
            body { background: #fff; }
            .bill-container { border: none; box-shadow: none; margin: 0; padding: 20px; max-width: 100%; }
            .bill-actions { display: none !important; }
        }
    </style>
</head>
<body>

<div class="bill-container">
    {{-- Hospital Header --}}
    @php $letterPad = hospital_setting('letter_pad', 'unavailable'); @endphp
    @if($letterPad === 'available')
    <div class="bill-header">
    </div>
    @else
    <div class="bill-header">
        <div class="bill-logo">
            @if(hospital_logo_url())
                <img src="{{ hospital_logo_url() }}" alt="{{ hospital_name() }} logo">
            @else
                <span>👁</span>
            @endif
        </div>
        <h1>{{ hospital_name() }}</h1>
        @if(hospital_full_address())
            <p>{{ hospital_full_address() }}</p>
        @endif
        <p>Phone: {{ hospital_contact_number() ?: '—' }} &bull; Email: {{ hospital_official_email() ?: '—' }}</p>
    </div>
    @endif

    <div class="bill-title">OPD Bill / Receipt</div>

    {{-- Patient Info --}}
    <div class="bill-info">
        <div class="bill-info-col">
            <dl>
                <dt>MRD No.</dt>
                <dd><strong>{{ $patient->patient_code }}</strong></dd>
                <dt>Patient Name</dt>
                <dd>{{ $patient->full_name }}</dd>
                <dt>Age / Gender</dt>
                <dd>{{ $patient->age }} yrs / {{ ucfirst($patient->gender) }}</dd>
                <dt>Contact</dt>
                <dd>{{ $patient->contact_no }}</dd>
            </dl>
        </div>
        <div class="bill-info-col" style="text-align:right">
            <dl style="text-align:right">
                <dt>Date</dt>
                <dd>{{ $patient->appointment_date?->format('d M Y') }}</dd>
                <dt>Consultant</dt>
                <dd>{{ $patient->doctor?->name ?? '—' }}</dd>
                @if($patient->doctor_patient_no)
                <dt>Doctor Serial No.</dt>
                <dd><strong>{{ ($patient->doctor?->doctor_prefix ?? '') ? $patient->doctor->doctor_prefix.'-'.str_pad($patient->doctor_patient_no, 3, '0', STR_PAD_LEFT) : str_pad($patient->doctor_patient_no, 3, '0', STR_PAD_LEFT) }}</strong></dd>
                @endif
                <dt>Receptionist</dt>
                <dd>{{ $patient->reception?->name ?? '—' }}</dd>
                <dt>Type</dt>
                <dd>{{ ucfirst($patient->type) }}</dd>
            </dl>
        </div>
    </div>

    {{-- Billing Table --}}
    <table class="bill-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Description</th>
                <th style="text-align:right">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>OPD Consultation Fee</td>
                <td style="text-align:right">{{ number_format($patient->case_fee, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="2" style="text-align:right">Total Payable</td>
                <td style="text-align:right">₹{{ number_format($patient->case_fee, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="bill-footer">
        <div>
            <p>Generated: {{ now()->format('d M Y, h:i A') }}</p>
            <p>{{ hospital_name() }}</p>
        </div>
        <div class="bill-signature">
            <div class="bill-signature-line"></div>
            <p>Authorized Signature</p>
        </div>
    </div>
</div>

{{-- Action Buttons --}}
<div class="bill-actions">
    <button class="btn-print" onclick="window.print()">
        🖨 Print Bill
    </button>
    <a href="{{ route('hospital.patients.bill-pdf', ['slug' => $slug, 'patient' => $patient->id]) }}"
       class="btn-outline">
        📄 Download PDF
    </a>
    <!-- <a href="{{ route('hospital.patients.index', ['slug' => $slug]) }}"
       class="btn-outline"></a> -->
    <a href="{{ route('hospital.ot.dashboard', ['slug' => $slug]) }}"
       onclick="if (window.history.length > 1) { window.history.back(); return false; }"
       class="btn-outline">
        ← Back
    </a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const returnTo = urlParams.get('return_to');

        if (urlParams.has('auto_print') && urlParams.get('auto_print') == '1') {
            setTimeout(function() {
                window.print();
            }, 300);

            window.onafterprint = function() {
                if (returnTo === 'dashboard') {
                    window.location.href = "{{ route('hospital.dashboard', ['slug' => app('tenant')->slug ?? request()->route('slug')]) }}";
                } else if (returnTo === 'create-phone') {
                    window.location.href = "{{ route('hospital.patients.create-phone', ['slug' => app('tenant')->slug ?? request()->route('slug')]) }}";
                } else if (returnTo === 'create') {
                    window.location.href = "{{ route('hospital.patients.create', ['slug' => app('tenant')->slug ?? request()->route('slug')]) }}";
                } else if (returnTo === 'back') {
                    window.history.back();
                }
            };
        }
    });
</script>

</body>
</html>
