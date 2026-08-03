@extends('hospital.layouts.app')
@section('title', 'Create Medicine Group')
@section('page-header', 'Create Medicine Group')

@section('page-actions')
    <a href="{{ route('hospital.medicine-groups.index', ['slug' => $slug]) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Groups
    </a>
@endsection

@section('content')

    @if($errors->any())
        <div class="alert alert-danger d-flex gap-2 mb-4">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <ul class="mb-0 ps-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card premium-form-card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0 fw-bold" style="color: var(--color-primary);">
                <i class="bi bi-collection me-2"></i> New Prescription Group
            </h5>
            <p class="mb-0 text-muted small mt-1">
                Group medicines together for one-click prescription (e.g. "Cataract Post-Op", "Dry Eye Protocol").
            </p>
        </div>

        <div class="card-body p-4">
            <form action="{{ route('hospital.medicine-groups.store', ['slug' => $slug]) }}" method="POST">
                @csrf

                <!-- <input type="hidden" name="usage_scope" id="usage_scope" value="opd"> -->
                {{-- Group Name + Group Code + Usage Scope --}}
                <div class="row mb-4 g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-medium">Group Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}"
                            class="form-control clinical-input @error('name') is-invalid @enderror" required
                            placeholder="e.g. Cataract Post-Op Standard">
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Group Code</label>
                        <input type="text" name="group_code" value="{{ old('group_code') }}"
                            class="form-control clinical-input @error('group_code') is-invalid @enderror"
                            placeholder="e.g. CAT-001">
                        @error('group_code')
                        <div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Used In <span class="text-danger">*</span></label>
                        <select name="usage_scope"
                            class="form-select clinical-input @error('usage_scope') is-invalid @enderror" required>
                            <option value="opd" {{ old('usage_scope', 'opd') === 'opd' ? 'selected' : '' }}>OPD only</option>
                            <option value="ot" {{ old('usage_scope') === 'ot' ? 'selected' : '' }}>OT only</option>
                            <option value="both" {{ old('usage_scope') === 'both' ? 'selected' : '' }}>Both OPD &amp; OT
                            </option>
                        </select>
                        @error('usage_scope')
                        <div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Repeater Table --}}
                <h6 class="fw-bold mb-3 pb-2 border-bottom" style="color: var(--color-primary);">
                    <i class="bi bi-list-ul me-1"></i> Medicines in this Group
                </h6>

                <div class="table-responsive mb-3">
                    <table class="table premium-table align-middle" id="repeaterTable">
                        <thead>
                            <tr>
                                <th style="min-width:200px">Medicine Name</th>
                                <th style="min-width:140px">Dosage</th>
                                <th style="min-width:100px">Days</th>
                                <th style="width:80px" class="text-center">Qty</th>
                                <th style="min-width:160px">Route of Administration</th>
                                <th style="width:60px" class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody id="repeaterBody">
                            <tr class="item-row" data-index="0">
                                <td>
                                    <select name="items[0][medicine_id]"
                                        class="form-select clinical-input-sm medicine-select" required>
                                        <option value="">Select medicine...</option>
                                        @foreach($medicines as $med)
                                            <option value="{{ $med->id }}">{{ $med->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="items[0][dosage_id]" class="form-select clinical-input-sm">
                                        <option value="">Select dosage...</option>
                                        @foreach($dosages as $d)
                                            <option value="{{ $d->id }}">{{ $d->dosage }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="items[0][duration]" class="form-control clinical-input-sm"
                                        placeholder="e.g. 5 days">
                                </td>
                                <td>
                                    <input type="number" name="items[0][quantity]"
                                        class="form-control clinical-input-sm text-center row-qty" required min="1"
                                        value="1">
                                </td>
                                <td>
                                    <select name="items[0][route_id]" class="form-select clinical-input-sm">
                                        <option value="">Select route...</option>
                                        @foreach($routes as $r)
                                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-row" disabled
                                        title="Remove">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <button type="button" class="btn btn-outline-primary mb-4" id="addRowBtn">
                    <i class="bi bi-plus-circle me-1"></i> Add Another Medicine
                </button>

                <div class="text-end border-top pt-3">
                    <a href="{{ route('hospital.medicine-groups.index', ['slug' => $slug]) }}"
                        class="btn btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i> Save Medicine Group
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        @php $medicineMap = $medicines->keyBy('id')->map(function ($m) {
            return ['dosage_id' => $m->dosage_id, 'duration' => $m->duration, 'qty' => $m->qty];
        }); @endphp
        const medicineDataMap = @json($medicineMap);

        document.addEventListener('DOMContentLoaded', function () {
            let rowIndex = 1;
            const tbody = document.getElementById('repeaterBody');

            // Auto-fill when medicine selected
            tbody.addEventListener('change', function (e) {
                const sel = e.target;
                if (!sel.classList.contains('medicine-select')) return;
                const row = sel.closest('tr');
                const med = medicineDataMap[sel.value];
                if (!med) return;
                const dosageSel = row.querySelector('select[name$="[dosage_id]"]');
                const durInput = row.querySelector('input[name$="[duration]"]');
                const qtyInput = row.querySelector('input[name$="[quantity]"]');
                if (dosageSel && med.dosage_id) dosageSel.value = med.dosage_id;
                if (durInput && med.duration) durInput.value = med.duration;
                if (qtyInput && med.qty) qtyInput.value = med.qty;
            });
            const addBtn = document.getElementById('addRowBtn');
            // Always reference the very first template row (index 0)
            const firstRow = tbody.querySelector('tr.item-row[data-index="0"]');

            addBtn.addEventListener('click', function () {
                const newRow = firstRow.cloneNode(true);
                newRow.setAttribute('data-index', rowIndex);

                newRow.querySelectorAll('input, select').forEach(function (el) {
                    const nameAttr = el.getAttribute('name');
                    if (nameAttr) {
                        // Replace [0] specifically — we always clone the index-0 template row
                        el.setAttribute('name', nameAttr.replace(/\[0\]/, '[' + rowIndex + ']'));
                    }
                    if (el.tagName === 'INPUT') {
                        el.value = el.type === 'number' ? '1' : '';
                    } else if (el.tagName === 'SELECT') {
                        el.selectedIndex = 0;
                    }
                });

                const deleteBtn = newRow.querySelector('.remove-row');
                deleteBtn.disabled = false;
                deleteBtn.addEventListener('click', function () {
                    newRow.remove();
                });

                tbody.appendChild(newRow);
                rowIndex++;
            });

            // Wire remove buttons for rows present on page load (after validation re-render)
            tbody.querySelectorAll('.remove-row:not([disabled])').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    btn.closest('tr').remove();
                });
            });
        });
    </script>
@endpush