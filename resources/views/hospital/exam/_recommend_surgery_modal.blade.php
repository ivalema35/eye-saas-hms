{{-- Shared Recommend Surgery modal — used on primary + secondary exam. Follows OT/exam theme. --}}
@php
    $otSlots = $otSlots ?? collect();
    $otSurgeryTypes = $otSurgeryTypes ?? collect();
    $existingOtRecommendation = $existingOtRecommendation ?? null;
    $otDefaultSlotId = $otDefaultSlotId ?? null;
    $otDefaultSurgeryDate = $otDefaultSurgeryDate ?? null;
    $otDefaultDiagnosisHint = $otDefaultDiagnosisHint ?? null;
    $returnTo = $recommendReturnTo ?? 'secondary';

    $defaultSurgeryDate = old(
        'surgery_date',
        optional($existingOtRecommendation?->surgery_date)->format('Y-m-d')
        ?: ($otDefaultSurgeryDate ?: now()->toDateString())
    );
    $defaultSlotId = (int) old(
        'slot_id',
        $existingOtRecommendation?->slot_id ?: $otDefaultSlotId
    );
    $defaultDiagnosisHint = old(
        'diagnosis_hint',
        $existingOtRecommendation?->counselling?->diagnosis
        ?: ($otDefaultDiagnosisHint ?? '')
    );
@endphp

@haspermission('ot.surgery.recommend')
<div class="modal fade ot-recommend-modal" id="recommendSurgeryModal" tabindex="-1"
    aria-labelledby="recommendSurgeryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST"
                action="{{ route('hospital.ot.recommend-surgery', ['slug' => $slug, 'patientId' => $patient->id]) }}">
                @csrf
                <input type="hidden" name="return_to" value="{{ $returnTo }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="recommendSurgeryModalLabel">
                        <span class="ot-modal-icon" aria-hidden="true"><i class="bi bi-hospital"></i></span>
                        Recommend Surgery
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="ot-modal-hint">
                        Creates an OT booking as <strong>Surgery Recommended</strong> and sends the patient to the
                        Counsellor queue.
                        @if($existingOtRecommendation)
                            <span class="d-block mt-1">Existing open booking (#{{ $existingOtRecommendation->id }},
                                {{ $existingOtRecommendation->ot_status }}) will be updated.</span>
                        @endif
                    </p>

                    <div class="mb-3">
                        <label class="form-label">Eye to Operate <span class="text-danger">*</span></label>
                        <select name="eye" class="form-select hms-input" required>
                            @php $eyeVal = old('eye', $existingOtRecommendation?->eye ?? ''); @endphp
                            <option value="">Select eye</option>
                            <option value="RE" {{ $eyeVal === 'RE' ? 'selected' : '' }}>Right (RE)</option>
                            <option value="LE" {{ $eyeVal === 'LE' ? 'selected' : '' }}>Left (LE)</option>
                            <option value="Both" {{ $eyeVal === 'Both' ? 'selected' : '' }}>Both</option>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Proposed OT Date <span class="text-danger">*</span></label>
                            <input type="date" name="surgery_date" class="form-control hms-input" required
                                min="{{ now()->toDateString() }}" value="{{ $defaultSurgeryDate }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">OT Slot <span class="text-danger">*</span></label>
                            <select name="slot_id" class="form-select hms-input" required>
                                <option value="">Select slot</option>
                                @foreach($otSlots as $slot)
                                    <option value="{{ $slot->id }}" {{ $defaultSlotId === (int) $slot->id ? 'selected' : '' }}>
                                        {{ $slot->slot_name }}
                                        @if($slot->start_time || $slot->end_time)
                                            ({{ \Illuminate\Support\Str::of($slot->start_time)->substr(0, 5) }}–{{ \Illuminate\Support\Str::of($slot->end_time)->substr(0, 5) }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Surgery Type <span class="text-danger">*</span></label>
                        <select name="ot_surgery_type_id" class="form-select hms-input" required>
                            <option value="">Select surgery</option>
                            @foreach($otSurgeryTypes as $stype)
                                <option value="{{ $stype->id }}" {{ (int) old('ot_surgery_type_id') === (int) $stype->id ? 'selected' : '' }}>
                                    {{ $stype->surgery_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Diagnosis hint <span class="text-muted">(optional)</span></label>
                        <input type="text" name="diagnosis_hint" id="ot_diagnosis_hint" class="form-control hms-input"
                            maxlength="255" placeholder="e.g. Senile Cataract RE" value="{{ $defaultDiagnosisHint }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn ot-modal-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ot-modal-btn-submit">
                        <i class="bi bi-send-check me-1"></i>
                        {{ $existingOtRecommendation ? 'Update Recommendation' : 'Refer to Counsellor' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            const modal = document.getElementById('recommendSurgeryModal');
            if (!modal) return;

            function checkedDiagnosisNames() {
                return Array.from(document.querySelectorAll('input[name="exam_data[diagnoses][]"]:checked')).map(function (el) {
                    if (el.dataset.name) return String(el.dataset.name).trim();
                    const lbl = document.querySelector('label[for="' + el.id + '"]');
                    if (!lbl) return '';
                    const clone = lbl.cloneNode(true);
                    clone.querySelectorAll('.badge').forEach(function (b) { b.remove(); });
                    return (clone.textContent || '').trim();
                }).filter(Boolean);
            }

            modal.addEventListener('show.bs.modal', function () {
                const input = document.getElementById('ot_diagnosis_hint');
                if (!input) return;
                const names = checkedDiagnosisNames();
                if (names.length) {
                    input.value = names.join(', ').slice(0, 255);
                }
            });
        })();
    </script>
@endpush

@push('styles')
    <style>
        .ot-recommend-modal .modal-content {
            border: 0;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 24px 70px rgba(27, 79, 114, 0.22);
        }

        .ot-recommend-modal .modal-header {
            background: linear-gradient(135deg, #1B4F72, #245f86);
            color: #fff;
            border-bottom: 0;
            padding: 1.1rem 1.25rem;
        }

        .ot-recommend-modal .modal-title {
            display: flex;
            align-items: center;
            gap: .7rem;
            font-weight: 900;
            letter-spacing: -.02em;
            color: #ffffff;
            margin: 0;
        }

        .ot-recommend-modal .ot-modal-icon {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.18);
            flex: 0 0 auto;
        }

        .ot-recommend-modal .modal-body {
            padding: 1.25rem;
            background: #f7fbfe;
        }

        .ot-recommend-modal .ot-modal-hint {
            color: rgba(27, 79, 114, 0.72);
            font-size: .85rem;
            font-weight: 600;
            background: rgba(27, 79, 114, 0.06);
            border: 1px solid rgba(27, 79, 114, 0.12);
            border-radius: 14px;
            padding: .75rem .9rem;
            margin-bottom: 1.1rem;
        }

        .ot-recommend-modal .form-label {
            font-weight: 800;
            font-size: .82rem;
            color: #1B4F72;
            letter-spacing: .01em;
        }

        .ot-recommend-modal .form-control,
        .ot-recommend-modal .form-select {
            border: 1px solid rgba(27, 79, 114, 0.18);
            border-radius: 12px;
            padding: .55rem .85rem;
            background: #ffffff;
            color: #1B4F72;
            font-weight: 600;
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }

        .ot-recommend-modal .form-control:focus,
        .ot-recommend-modal .form-select:focus {
            border-color: #1B4F72;
            box-shadow: 0 0 0 .2rem rgba(27, 79, 114, 0.12);
        }

        .ot-recommend-modal .modal-footer {
            border-top: 1px solid rgba(27, 79, 114, 0.12);
            background: #f7fbfe;
            padding: 1rem 1.25rem;
        }

        .ot-recommend-modal .ot-modal-btn-cancel {
            border: 1px solid rgba(27, 79, 114, 0.24);
            color: #1B4F72;
            background: #fff;
            border-radius: 12px;
            font-weight: 800;
            padding: .5rem 1.1rem;
        }

        .ot-recommend-modal .ot-modal-btn-cancel:hover {
            background: rgba(27, 79, 114, 0.06);
            color: #1B4F72;
        }

        .ot-recommend-modal .ot-modal-btn-submit {
            background: #1B4F72;
            border: 1px solid #1B4F72;
            color: #fff;
            border-radius: 12px;
            font-weight: 800;
            padding: .5rem 1.1rem;
            transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease;
        }

        .ot-recommend-modal .ot-modal-btn-submit:hover {
            background: #15405d;
            border-color: #15405d;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(27, 79, 114, 0.20);
        }
    </style>
@endpush
@endhaspermission