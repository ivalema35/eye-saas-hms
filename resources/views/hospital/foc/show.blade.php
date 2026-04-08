@extends('hospital.layouts.app')
@section('title', 'FOC Request #'.$foc->id)
@section('page-header', 'FOC Request Details')

@section('page-actions')
    <a href="{{ route('hospital.foc.index', ['slug' => $slug]) }}"
       class="hms-btn hms-btn-outline">
        <i class="fa-solid fa-arrow-left"></i> Back to FOC List
    </a>
@endsection

@section('content')

<div style="max-width:680px">

    {{-- ── Status Banner ── --}}
    @if($foc->isAccepted())
        <div class="hms-alert hms-alert-success" style="margin-bottom:1rem;display:flex;align-items:center;gap:.75rem">
            <i class="fa-solid fa-circle-check fa-lg"></i>
            <div>
                <strong>FOC Approved</strong>
                <div style="font-size:.85rem;margin-top:.15rem">
                    Approved by <strong>{{ $foc->acceptedByUser?->name ?? '—' }}</strong>
                    @if($foc->accepted_at)
                        on {{ $foc->accepted_at->format('d M Y, h:i A') }}
                    @endif
                </div>
            </div>
        </div>
    @elseif($foc->isRejected())
        <div class="hms-alert hms-alert-danger" style="margin-bottom:1rem;display:flex;align-items:center;gap:.75rem">
            <i class="fa-solid fa-circle-xmark fa-lg"></i>
            <div>
                <strong>FOC Rejected</strong>
                @if($foc->rejected_reason)
                    <div style="font-size:.85rem;margin-top:.15rem">Reason: {{ $foc->rejected_reason }}</div>
                @endif
            </div>
        </div>
    @else
        <div class="hms-alert" style="margin-bottom:1rem;background:#fff8e1;border:1px solid #ffe082;display:flex;align-items:center;gap:.75rem">
            <i class="fa-solid fa-clock fa-lg" style="color:#f59e0b"></i>
            <strong style="color:#92400e">Pending Approval</strong>
        </div>
    @endif

    {{-- ── FOC Detail Card ── --}}
    <div class="hms-card" style="margin-bottom:1rem">
        <div class="hms-card-header">
            <h3 class="hms-card-title">
                <i class="fa-solid fa-hand-holding-heart"></i> FOC Request #{{ $foc->id }}
            </h3>
            <span class="hms-badge {{ $foc->isAccepted() ? 'hms-badge-success' : ($foc->isRejected() ? 'hms-badge-danger' : 'hms-badge-warning') }}">
                {{ ucfirst($foc->status ?? ($foc->accepted ? 'accepted' : 'pending')) }}
            </span>
        </div>
        <div class="hms-card-body">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1rem">
                <div>
                    <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--hms-text-muted);margin-bottom:.25rem">Patient</div>
                    <div style="font-weight:700;font-size:1rem">{{ $foc->patient?->full_name ?? '—' }}</div>
                    @if($foc->patient?->patient_code)
                        <div style="font-size:.8rem;color:var(--hms-text-muted)">MRD: {{ $foc->patient->patient_code }}</div>
                    @endif
                </div>
                <div>
                    <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--hms-text-muted);margin-bottom:.25rem">Doctor</div>
                    <div style="font-weight:600">{{ $foc->doctor?->name ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--hms-text-muted);margin-bottom:.25rem">FOC Fee Waived</div>
                    <div style="font-weight:600;font-size:1rem;color:var(--hms-success)">
                        ₹{{ number_format($foc->foc_fee ?? 0, 2) }}
                    </div>
                </div>
                <div>
                    <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--hms-text-muted);margin-bottom:.25rem">Submitted By</div>
                    <div style="font-weight:600">{{ $foc->reception?->name ?? '—' }}</div>
                    <div style="font-size:.8rem;color:var(--hms-text-muted)">{{ $foc->created_at?->format('d M Y, h:i A') }}</div>
                </div>
            </div>

            <div>
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--hms-text-muted);margin-bottom:.35rem">Reason for FOC</div>
                <div style="background:var(--hms-bg-light);border-radius:6px;padding:.75rem 1rem;font-size:.9rem;line-height:1.55">
                    {{ $foc->reason ?? '—' }}
                </div>
            </div>

        </div>
    </div>

    {{-- ── Action Panel (only when pending) ── --}}
    @if($foc->isPending())
    <div class="hms-card" style="margin-bottom:1rem;border:1px solid var(--hms-border)">
        <div class="hms-card-header">
            <h3 class="hms-card-title">
                <i class="fa-solid fa-gavel"></i> Take Action
            </h3>
        </div>
        <div class="hms-card-body">
            <div style="display:flex;gap:1.5rem;flex-wrap:wrap;align-items:flex-start">

                {{-- Approve --}}
                <form method="POST"
                      action="{{ route('hospital.foc.approve', ['slug' => $slug, 'foc' => $foc->id]) }}"
                      onsubmit="return confirm('Approve this FOC request and waive the fee?')">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="hms-btn hms-btn-success">
                        <i class="fa-solid fa-check"></i> Approve FOC
                    </button>
                </form>

                {{-- Reject (inline form) --}}
                <div style="flex:1;min-width:260px">
                    <form method="POST"
                          action="{{ route('hospital.foc.reject', ['slug' => $slug, 'foc' => $foc->id]) }}"
                          id="rejectForm">
                        @csrf
                        @method('PATCH')
                        <div class="hms-form-group" style="margin-bottom:.5rem">
                            <label>Rejection Reason <span style="color:var(--hms-danger)">*</span></label>
                            <textarea name="rejected_reason" class="hms-input @error('rejected_reason') is-invalid @enderror"
                                      rows="2" placeholder="State the reason for rejection..."
                                      required>{{ old('rejected_reason') }}</textarea>
                            @error('rejected_reason')
                                <div class="hms-field-error">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="hms-btn hms-btn-danger"
                                onclick="return confirm('Reject this FOC request?')">
                            <i class="fa-solid fa-xmark"></i> Reject FOC
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
    @endif

</div>

@endsection
