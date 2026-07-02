@extends('hospital.layouts.app')
@section('title', 'Patient — ' . $patient->patient_code)
@section('page-header', '')

@section('page-actions')
    <a href="{{ url()->previous() }}" class="hms-btn hms-btn-outline">
        Back
    </a>
@endsection

@section('content')
    <div style="max-width:780px;margin:0 auto">

        {{-- Header card --}}
        <div class="hms-card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;margin-bottom:1.25rem">
            <div
                style="background:linear-gradient(135deg,#1B4F72,#2980B9);padding:1.5rem 1.75rem;display:flex;align-items:center;gap:1rem">
                <div
                    style="width:52px;height:52px;background:rgba(255,255,255,.2);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:#fff;flex-shrink:0">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div style="flex:1">
                    <div style="font-size:1.25rem;font-weight:800;color:#fff">{{ $patient->full_name }}</div>
                    <div style="font-size:.85rem;color:rgba(255,255,255,.8);margin-top:.2rem">
                        MRD: <strong style="color:#fff">{{ $patient->patient_code }}</strong>
                        &nbsp;|&nbsp;
                        {{ $patient->age }}y / {{ ucfirst($patient->gender) }}
                        @if($patient->occupation)
                            &nbsp;|&nbsp; {{ $patient->occupation }}
                        @endif
                    </div>
                </div>
                <div>
                    @if($patient->case_id)
                        <span
                            style="background:rgba(39,174,96,.25);color:#D5F5E3;border:1px solid rgba(39,174,96,.4);border-radius:20px;padding:.3rem .9rem;font-size:.75rem;font-weight:700">
                            <i class="bi bi-check-circle-fill"></i> Checked In
                        </span>
                    @else
                        <span
                            style="background:rgba(255,193,7,.2);color:#FFEAA7;border:1px solid rgba(255,193,7,.35);border-radius:20px;padding:.3rem .9rem;font-size:.75rem;font-weight:700">
                            <i class="bi bi-clock"></i> Pending Check-In
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Detail sections --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem">

            {{-- Contact --}}
            <div class="hms-card border-0 shadow-sm" style="border-radius:14px;padding:1.25rem 1.5rem">
                <div
                    style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748B;margin-bottom:1rem">
                    <i class="bi bi-telephone-fill me-1" style="color:#1B4F72"></i> Contact
                </div>
                <div style="display:flex;flex-direction:column;gap:.65rem">
                    <div>
                        <div style="font-size:.72rem;color:#94A3B8;font-weight:600">Mobile</div>
                        <div style="font-weight:700;color:#1A202C">{{ $patient->contact_no ?: '—' }}</div>
                    </div>
                    <div>
                        <div style="font-size:.72rem;color:#94A3B8;font-weight:600">WhatsApp</div>
                        <div style="font-weight:600;color:#1A202C">{{ $patient->whatsapp_no ?: 'Same as mobile' }}</div>
                    </div>
                    <div>
                        <div style="font-size:.72rem;color:#94A3B8;font-weight:600">City</div>
                        <div style="font-weight:600;color:#1A202C">{{ $patient->cityName ?: '—' }}</div>
                    </div>
                </div>
            </div>

            {{-- Appointment --}}
            <div class="hms-card border-0 shadow-sm" style="border-radius:14px;padding:1.25rem 1.5rem">
                <div
                    style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748B;margin-bottom:1rem">
                    <i class="bi bi-calendar-check-fill me-1" style="color:#1B4F72"></i> Appointment
                </div>
                <div style="display:flex;flex-direction:column;gap:.65rem">
                    <div>
                        <div style="font-size:.72rem;color:#94A3B8;font-weight:600">Date</div>
                        <div style="font-weight:700;color:#1A202C">
                            {{ $patient->appointment_date ? \Carbon\Carbon::parse($patient->appointment_date)->format('d M Y') : '—' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:.72rem;color:#94A3B8;font-weight:600">Doctor</div>
                        <div style="font-weight:700;color:#1A202C">{{ $patient->doctor?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div style="font-size:.72rem;color:#94A3B8;font-weight:600">Receptionist</div>
                        <div style="font-weight:600;color:#1A202C">{{ $patient->reception?->name ?? '—' }}</div>
                    </div>
                </div>
            </div>

            {{-- Case Details --}}
            <div class="hms-card border-0 shadow-sm" style="border-radius:14px;padding:1.25rem 1.5rem">
                <div
                    style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748B;margin-bottom:1rem">
                    <i class="bi bi-clipboard2-pulse-fill me-1" style="color:#1B4F72"></i> Case Details
                </div>
                <div style="display:flex;flex-direction:column;gap:.65rem">
                    <div>
                        <div style="font-size:.72rem;color:#94A3B8;font-weight:600">Case Type</div>
                        <div style="font-weight:700;color:#1A202C">{{ $patient->caseType?->case_type ?? '—' }}</div>
                    </div>
                    <div>
                        <div style="font-size:.72rem;color:#94A3B8;font-weight:600">Case Fee</div>
                        <div style="font-weight:800;color:#27AE60;font-size:1.1rem">
                            ₹{{ $patient->case_fee ? number_format((float) $patient->case_fee, 0) : '—' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:.72rem;color:#94A3B8;font-weight:600">Type</div>
                        <div style="font-weight:600;color:#1A202C">{{ ucfirst($patient->type) }}</div>
                    </div>
                </div>
            </div>

            {{-- Registered --}}
            <div class="hms-card border-0 shadow-sm" style="border-radius:14px;padding:1.25rem 1.5rem">
                <div
                    style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748B;margin-bottom:1rem">
                    <i class="bi bi-info-circle-fill me-1" style="color:#1B4F72"></i> Registration Info
                </div>
                <div style="display:flex;flex-direction:column;gap:.65rem">
                    <div>
                        <div style="font-size:.72rem;color:#94A3B8;font-weight:600">Registered On</div>
                        <div style="font-weight:700;color:#1A202C">
                            {{ $patient->created_at?->format('d M Y, h:i A') ?? '—' }}</div>
                    </div>
                    <div>
                        <div style="font-size:.72rem;color:#94A3B8;font-weight:600">MRD</div>
                        <div style="font-weight:800;color:#1B4F72;font-size:1rem">{{ $patient->patient_code }}</div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Check-In button if not yet checked in --}}
        @if(!$patient->case_id)
            <div class="hms-card border-0 shadow-sm"
                style="border-radius:14px;padding:1.25rem 1.5rem;margin-top:1.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;background:linear-gradient(135deg,#FFF9E6,#FFFDE7)">
                <div>
                    <div style="font-weight:700;color:#856404"><i class="bi bi-exclamation-circle-fill me-1"></i> Patient not
                        yet checked in</div>
                    <div style="font-size:.8rem;color:#A07800;margin-top:.2rem">Assign case type and fee to complete check-in
                    </div>
                </div>
                <a href="{{ route('hospital.patients.checkin', ['slug' => $slug, 'patient' => $patient->id]) }}"
                    style="background:#1B4F72;color:#fff;border-radius:10px;padding:.6rem 1.25rem;font-weight:700;font-size:.875rem;text-decoration:none;white-space:nowrap;display:inline-flex;align-items:center;gap:.4rem">
                    <i class="bi bi-person-check-fill"></i> Check In Now
                </a>
            </div>
        @endif

    </div>
@endsection