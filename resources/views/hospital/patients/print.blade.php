<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OPD Bill — {{ $patient->patient_code }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 14mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            background: #fff;
        }

        .bill-page {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0;
        }

        .bill-box {
            width: 100%;
            background: #fff;
            border: 1.5px solid #000;
            padding: 6px 10px 10px;
        }

        .bill-header {
            text-align: center;
            padding-bottom: 4px;
            border-bottom: 1px solid #000;
        }

        .bill-header h1 {
            font-size: 20px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            line-height: 1.15;
        }

        .bill-header p {
            font-size: 11px;
            font-weight: 400;
            margin-top: 2px;
            line-height: 1.25;
        }

        .bill-title {
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 5px 0;
            border-bottom: 1px solid #000;
        }

        .bill-fields {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px solid #000;
        }

        .bill-fields td {
            width: 50%;
            padding: 3px 2px;
            font-size: 12px;
            vertical-align: top;
            line-height: 1.3;
            text-align: left;
        }

        .bill-fields td.col-right {
            padding-left: 10px;
        }

        .bill-fields strong {
            font-weight: 700;
        }

        .bill-charge {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px solid #000;
        }

        .bill-charge td {
            padding: 5px 2px;
            font-size: 12px;
            font-weight: 700;
        }

        .bill-charge td.right {
            text-align: right;
            white-space: nowrap;
        }

        .bill-signature {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .bill-signature td {
            width: 50%;
            font-size: 12px;
            font-weight: 700;
            vertical-align: bottom;
            padding: 2px;
        }

        .bill-signature td.right {
            text-align: right;
        }

        .bill-stamp {
            font-size: 11px;
            margin-top: 6px;
            padding-left: 2px;
        }

        .bill-actions {
            text-align: center;
            margin-top: 18px;
        }

        .btn-print {
            background: #1B4F72;
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin: 0 4px;
        }

        .btn-print:hover {
            background: #154360;
        }

        .btn-outline {
            background: #fff;
            color: #1B4F72;
            border: 1.5px solid #1B4F72;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            margin: 0 4px;
            display: inline-block;
        }

        @media screen {
            body {
                background: #f4f6f9;
            }

            .bill-page {
                max-width: 820px;
                margin: 16px auto;
                padding: 0 12px;
            }
        }

        @media print {
            body {
                background: #fff;
            }

            .bill-page {
                margin: 0;
                padding: 0;
                width: 100%;
            }

            .bill-actions {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    @php
        if (!empty($tenant?->currency_code)) {
            config(['app.hospital_currency_code' => $tenant->currency_code]);
        }
        if (!empty($tenant?->currency_symbol)) {
            config(['app.hospital_currency_symbol' => $tenant->currency_symbol]);
        }

        $consultant = trim((string) ($patient->doctor?->doctor_prefix ?? ''));
        if ($consultant === '') {
            $consultant = $patient->doctor?->name ?? '—';
        }
        $sex = match (strtolower(trim((string) ($patient->gender ?? '')))) {
            'm', 'male' => 'Male',
            'f', 'female' => 'Female',
            'o', 'other' => 'Other',
            default => ($patient->gender ? ucfirst((string) $patient->gender) : ''),
        };
        $ageSex = trim(($patient->age !== null && $patient->age !== '' ? (string) $patient->age : '—') . ($sex !== '' ? ', ' . $sex : ''));
        $city = trim(collect([$patient->city_name, $patient->district_name])->filter()->implode(', ')) ?: '—';
    @endphp
    <div class="bill-page">
        <div class="bill-box">
            @php $letterPad = hospital_setting('letter_pad', 'unavailable'); @endphp
            @if($letterPad !== 'available')
                <div class="bill-header">
                    <h1>{{ hospital_name() }}</h1>
                    @if(hospital_full_address())
                        <p>{{ hospital_full_address() }}</p>
                    @endif
                    @if(hospital_contact_number())
                        <p>Phone No.: {{ hospital_contact_number() }}</p>
                    @endif
                </div>
            @endif

            <div class="bill-title">OPD BILL</div>

            <table class="bill-fields">
                <tr>
                    <td><strong>MRD No.:</strong> {{ $patient->patient_code }}</td>
                    <td class="col-right"><strong>Bill Date:</strong> {{ now()->format('d-m-Y H:i:s') }}</td>
                </tr>
                <tr>
                    <td><strong>Name:</strong> {{ strtoupper($patient->full_name) }}</td>
                    <td class="col-right"><strong>Phone No.:</strong> {{ $patient->contact_no }}</td>
                </tr>
                <tr>
                    <td><strong>Age, Sex:</strong> {{ $ageSex }}</td>
                    <td class="col-right"><strong>Consultant Name:</strong> {{ $consultant }}</td>
                </tr>
                <tr>
                    <td><strong>City:</strong> {{ $city }}</td>
                    <td class="col-right"><strong>Index No.:</strong> {{ $patient->doctor_patient_no ?? '—' }}</td>
                </tr>
            </table>

            <table class="bill-charge">
                <tr>
                    <td>Consultation charge:</td>
                    <td class="right">{{ money($patient->case_fee ?? 0, 2) }}</td>
                </tr>
            </table>

            <table class="bill-signature">
                <tr>
                    <td>Signature: __________</td>
                    <td class="right">Receptionist Name: {{ $patient->reception?->name ?? '—' }}</td>
                </tr>
            </table>
        </div>

        <div class="bill-stamp">Date &amp; Time Stamp:</div>

        <div class="bill-actions">
            <button class="btn-print" type="button" onclick="window.print()">Print Bill</button>
            <a href="{{ route('hospital.patients.bill-pdf', ['slug' => $slug, 'patient' => $patient->id]) }}"
                class="btn-outline">Download PDF</a>
            <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}"
                onclick="if (window.history.length > 1) { window.history.back(); return false; }"
                class="btn-outline">Back</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            const returnTo = urlParams.get('return_to');

            if (urlParams.has('auto_print') && urlParams.get('auto_print') == '1') {
                setTimeout(function () { window.print(); }, 300);

                window.onafterprint = function () {
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
