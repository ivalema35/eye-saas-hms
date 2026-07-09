{{-- Patient History: table + pagination only. Re-rendered on every AJAX filter/page request,
     so this partial must not contain the filter form (that lives in the parent view and is
     never replaced, which is what keeps focus in the input while the user types). --}}
<div class="card custom-history-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle text-center mb-0" style="font-size: 14px;">
            <thead>
                <tr>
                    <th style="width:60px;">#</th>
                    <th>MRD No.</th>
                    <th class="text-start">Patient Name</th>
                    <th>Hospital</th>
                    <th>Doctor Name</th>
                    <th>Date</th>
                    <th>Contact</th>
                    <th>Age</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($historyPatients as $i => $patient)
                    <tr>
                        <td>{{ $historyPatients->firstItem() + $i }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $patient->patient_code ?? '—' }}</span></td>
                        <td class="text-start fw-semibold" style="color:#1B4F72;">
                            {{ $patient->first_name ?? '' }} {{ $patient->last_name ?? '' }}
                        </td>
                        <td>
                            @if($patient->tenant_id === $currentTenant->id)
                                <span style="background:#e0f2fe;color:#0369a1;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;">
                                    <i class="bi bi-house-fill me-1"></i>Own
                                </span>
                            @else
                                <span style="background:#f0fdf4;color:#166534;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;">
                                    <i class="bi bi-building me-1"></i>{{ $patient->tenant->name ?? '—' }}
                                </span>
                            @endif
                        </td>
                        <td class="fw-semibold">{{ $patient->doctor->name ?? '—' }}</td>
                        <td>{{ \Carbon\Carbon::parse($patient->appointment_date)->format('d M, Y') }}</td>
                        <td>{{ $patient->contact_no ?? '-' }}</td>
                        <td>{{ $patient->age ?? '-' }}</td>
                        <td>
                            @if($patient->tenant_id === $currentTenant->id)
                                <a href="{{ route('hospital.patients.history', ['slug' => $slug]) }}?patient_ids={{ $patient->all_patient_ids }}"
                                    class="btn btn-sm btn-view-history" title="View Details" target="_blank">
                                    <i class="bi bi-eye-fill"></i> View
                                </a>
                            @else
                                <a href="{{ route('hospital.shared.patient.history', ['slug' => $slug]) }}?patient_ids={{ $patient->all_patient_ids }}"
                                    class="btn btn-sm fw-bold" target="_blank"
                                    style="background:#0d9488;color:#fff;border-radius:8px;padding:5px 12px;">
                                    <i class="bi bi-eye-fill"></i> View
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-5 text-muted">No history found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($historyPatients->hasPages())
        <div class="d-flex justify-content-center p-3 border-top" style="background-color:#fcfcfc;">
        {{ $historyPatients->appends(request()->except('patient_page'))->appends([
    '_tab' => 'patient'
])->links() }}
                            </div>
    @endif
</div>
