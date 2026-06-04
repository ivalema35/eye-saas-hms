<style>
    .group-form-modal .modal-content {
        border: 1px solid rgba(27, 79, 114, .12) !important;
        border-radius: 22px;
        background: rgba(255, 255, 255, .98);
        box-shadow: 0 20px 50px rgba(27, 79, 114, .15);
        overflow: hidden;
    }

    .group-form-modal .modal-dialog {
        max-width: min(1180px, calc(100vw - 2rem));
    }

    .group-form-modal .modal-header {
        background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94));
        border-bottom: 1px solid rgba(27, 79, 114, .12) !important;
        padding: 1.25rem 1.5rem !important;
    }

    .group-form-modal .modal-title,
    .group-form-modal .form-label,
    .group-form-modal .group-modal-section-title {
        color: #1B4F72;
        font-weight: 900;
    }

    .group-form-modal .modal-body {
        padding: 1.5rem !important;
        max-height: calc(100vh - 220px);
        overflow-y: auto;
    }

    .group-form-modal .modal-footer {
        background: linear-gradient(135deg, rgba(235, 245, 251, .72), rgba(255, 255, 255, .94));
        border-top: 1px solid rgba(27, 79, 114, .12) !important;
        padding: 1.2rem 1.5rem !important;
    }

    .group-form-modal .btn-close {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, .92);
        border: 1px solid rgba(27, 79, 114, .14);
        box-shadow: 0 8px 18px rgba(27, 79, 114, .10);
        opacity: 1;
    }

    .group-form-modal .clinical-input,
    .group-form-modal .clinical-input-sm {
        border: 1.5px solid rgba(27, 79, 114, .12);
        border-radius: 12px;
        background: rgba(235, 245, 251, .4);
        color: #1B4F72;
        font-weight: 650;
    }

    .group-form-modal .clinical-input:focus,
    .group-form-modal .clinical-input-sm:focus {
        border-color: #1B4F72;
        background: rgba(255, 255, 255, .96);
        box-shadow: 0 0 0 4px rgba(27, 79, 114, .12);
    }

    .group-form-modal .group-modal-table {
        border-collapse: separate;
        border-spacing: 0 10px;
        min-width: 900px;
    }

    .group-form-modal .group-modal-table thead th {
        background: #1B4F72 !important;
        color: #fff !important;
        border: 0 !important;
        padding: .9rem 1rem;
        font-size: .72rem;
        letter-spacing: .08em;
        font-weight: 900;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .group-form-modal .group-modal-table thead th:first-child {
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .group-form-modal .group-modal-table thead th:last-child {
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .group-form-modal .group-modal-table tbody tr {
        box-shadow: 0 2px 12px rgba(27,79,114,.08);
        transition: box-shadow .2s ease;
    }

    .group-form-modal .group-modal-table tbody tr:hover {
        box-shadow: 0 6px 20px rgba(27,79,114,.14);
    }

    .group-form-modal .group-modal-table tbody td {
        background: #fff;
        border-top: 1px solid rgba(27, 79, 114, .10);
        border-bottom: 1px solid rgba(27, 79, 114, .10);
        padding: .75rem .8rem;
        vertical-align: middle;
    }

    .group-form-modal .group-modal-table tbody td:first-child {
        border-left: 1px solid rgba(27, 79, 114, .10);
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .group-form-modal .group-modal-table tbody td:last-child {
        border-right: 1px solid rgba(27, 79, 114, .10);
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    /* Styled inputs & selects inside table */
    .group-form-modal .group-modal-table .form-select,
    .group-form-modal .group-modal-table .form-control {
        border: 1.5px solid rgba(27,79,114,.18) !important;
        border-radius: 10px !important;
        background: rgba(235,245,251,.35) !important;
        color: #1B4F72 !important;
        font-weight: 600;
        font-size: .85rem;
        padding: .45rem .8rem;
        transition: border-color .15s, box-shadow .15s, background .15s;
    }

    .group-form-modal .group-modal-table .form-select:focus,
    .group-form-modal .group-modal-table .form-control:focus {
        border-color: #1B4F72 !important;
        background: #fff !important;
        box-shadow: 0 0 0 3px rgba(27,79,114,.12) !important;
        outline: none;
    }

    .group-form-modal .group-modal-table .form-select option {
        color: #1B4F72;
        font-weight: 500;
    }

    /* Select2 custom styling */
    .group-form-modal .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1.5px solid rgba(27,79,114,.18) !important;
        border-radius: 10px !important;
        background: rgba(235,245,251,.35) !important;
        display: flex;
        align-items: center;
    }
    .group-form-modal .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #1B4F72 !important;
        font-weight: 600;
        font-size: .85rem;
        padding-left: .8rem;
        line-height: 36px;
    }
    .group-form-modal .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: rgba(27,79,114,.5) !important;
    }
    .group-form-modal .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 8px;
    }
    .group-form-modal .select2-container--default.select2-container--focus .select2-selection--single,
    .group-form-modal .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #1B4F72 !important;
        background: #fff !important;
        box-shadow: 0 0 0 3px rgba(27,79,114,.12) !important;
    }
    .group-form-modal .select2-dropdown {
        border: 1.5px solid rgba(27,79,114,.2) !important;
        border-radius: 12px !important;
        box-shadow: 0 8px 24px rgba(27,79,114,.15) !important;
        overflow: hidden;
    }
    .group-form-modal .select2-search--dropdown .select2-search__field {
        border: 1.5px solid rgba(27,79,114,.18) !important;
        border-radius: 8px !important;
        padding: .4rem .7rem;
        color: #1B4F72;
        font-size: .85rem;
    }
    .group-form-modal .select2-results__option {
        font-size: .85rem;
        color: #1B4F72;
        padding: .5rem .8rem;
    }
    .group-form-modal .select2-results__option--highlighted {
        background: #1B4F72 !important;
        color: #fff !important;
    }
    .group-form-modal .select2-results__option[aria-selected=true] {
        background: rgba(27,79,114,.08) !important;
        color: #1B4F72 !important;
        font-weight: 700;
    }

    .group-form-modal .group-modal-table .btn-outline-danger {
        border-radius: 50% !important;
        width: 34px;
        height: 34px;
        padding: 0 !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-color: rgba(220,38,38,.3) !important;
        color: #dc2626 !important;
        transition: all .15s;
    }

    .group-form-modal .group-modal-table .btn-outline-danger:hover {
        background: #dc2626 !important;
        border-color: #dc2626 !important;
        color: #fff !important;
    }

    .group-form-modal .group-modal-add-row,
    .group-form-modal .btn-outline-secondary {
        border-color: rgba(27, 79, 114, .18) !important;
        border-radius: 12px;
        color: #1B4F72 !important;
        font-weight: 850;
    }

    .group-form-modal .group-modal-add-row {
        background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .98));
        box-shadow: 0 8px 18px rgba(27, 79, 114, .08);
        transition: transform .2s ease, box-shadow .2s ease, background .2s ease, border-color .2s ease, color .2s ease;
    }

    .group-form-modal .group-modal-add-row:hover,
    .group-form-modal .group-modal-add-row:focus-visible {
        background: linear-gradient(135deg, #1B4F72, #2E6A93) !important;
        border-color: #1B4F72 !important;
        color: #fff !important;
        box-shadow: 0 12px 28px rgba(27, 79, 114, .24);
        transform: translateY(-1px);
    }

    .group-form-modal .group-modal-add-row:hover .bi,
    .group-form-modal .group-modal-add-row:focus-visible .bi {
        color: #fff;
    }

    .group-form-modal .btn-primary {
        background: #1B4F72 !important;
        border-color: #1B4F72 !important;
        border-radius: 12px;
        font-weight: 900;
        box-shadow: 0 10px 24px rgba(27, 79, 114, .20);
    }
</style>

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

<div class="modal fade group-form-modal" id="groupFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="groupFormModalTitle">New Prescription Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="groupForm" action="{{ route('hospital.medicine-groups.store', ['slug' => $slug]) }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="groupFormMethod" value="POST">
                <input type="hidden" name="group_id" id="group-id" value="{{ old('group_id') }}">

                <div class="modal-body">
                    <div class="row mb-3 g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Group Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="group-name"
                                   value="{{ old('name') }}"
                                   class="form-control clinical-input @error('name') is-invalid @enderror"
                                   required placeholder="e.g. Cataract Post-Op Standard">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Group Code</label>
                            <input type="text" name="group_code" id="group-code"
                                   value="{{ old('group_code') }}"
                                   class="form-control clinical-input @error('group_code') is-invalid @enderror"
                                   placeholder="e.g. CAT-001">
                            @error('group_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row mb-4 g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-medium">
                                <i class="bi bi-clipboard2-pulse me-1"></i> Diagnosis
                            </label>
                            <select name="diagnosis_id" id="group-diagnosis"
                                    class="form-select clinical-input @error('diagnosis_id') is-invalid @enderror">
                                <option value="">— Select diagnosis —</option>
                                @foreach($diagnoses as $d)
                                    <option value="{{ $d->id }}">{{ $d->value }}</option>
                                @endforeach
                            </select>
                            @error('diagnosis_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <h6 class="group-modal-section-title mb-3 pb-2 border-bottom">
                        <i class="bi bi-list-ul me-1"></i> Medicines in this Group
                    </h6>

                    <div class="table-responsive mb-3">
                        <table class="table premium-table align-middle group-modal-table">
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
                            <tbody id="groupRepeaterBody"></tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-outline-primary group-modal-add-row" id="groupAddRowBtn">
                        <i class="bi bi-plus-circle me-1"></i> Add Another Medicine
                    </button>
                </div>

                <div class="modal-footer border-0 gap-2">
                    <button type="button" class="hms-btn hms-btn-outline hms-btn-sm" data-bs-dismiss="modal">
                         Cancel
                    </button>
                    <button type="submit" class="btn btn-primary px-4" id="groupFormSubmitBtn">
                         Save Medicine Group
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="groupItemRowTemplate">
    <tr class="item-row">
        <td>
            <select class="form-select clinical-input-sm medicine-select" required data-field="medicine_id">
                <option value="">Select medicine...</option>
                @foreach($medicines as $med)
                    <option value="{{ $med->id }}">{{ $med->name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select class="form-select clinical-input-sm" data-field="dosage_id">
                <option value="">Select dosage...</option>
                @foreach($dosages as $d)
                    <option value="{{ $d->id }}">{{ $d->dosage }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="text" class="form-control clinical-input-sm" placeholder="e.g. 5 days" data-field="duration">
        </td>
        <td>
            <input type="number" class="form-control clinical-input-sm text-center" required min="1" value="1" data-field="quantity">
        </td>
        <td>
            <select class="form-select clinical-input-sm" data-field="route_id">
                <option value="">Select route...</option>
                @foreach($routes as $r)
                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                @endforeach
            </select>
        </td>
        <td class="text-end">
            <button type="button" class="btn btn-outline-danger btn-sm remove-row" title="Remove">
                <i class="bi bi-trash3"></i>
            </button>
        </td>
    </tr>
</template>

@push('scripts')
<script>
const groupStoreUrl = "{{ route('hospital.medicine-groups.store', ['slug' => $slug]) }}";

// Medicine data map for auto-fill
@php
$medicineMap = $medicines->keyBy('id')->map(function($m) {
    return ['dosage_id' => $m->dosage_id, 'duration' => $m->duration, 'qty' => $m->qty];
});
@endphp
const medicineDataMap = @json($medicineMap);
const groupUpdateBase = "{{ route('hospital.medicine-groups.update', ['slug' => $slug, 'medicine_group' => '__ID__']) }}";

let groupRowIndex = 0;

function initMedicineSelect2(selectEl) {
    $(selectEl).select2({
        dropdownParent: $('#groupFormModal'),
        placeholder: 'Search medicine...',
        allowClear: true,
        width: '100%',
    }).on('change', function () {
        const row = $(this).closest('tr')[0];
        const med = medicineDataMap[this.value];
        if (!med) return;

        // Dosage — use Select2 trigger since it's also Select2
        const dosageSel = row.querySelector('[data-field="dosage_id"]');
        if (dosageSel && med.dosage_id) {
            $(dosageSel).val(med.dosage_id).trigger('change.select2');
        }

        // Duration & Qty — plain inputs
        const durInput = row.querySelector('[data-field="duration"]');
        const qtyInput = row.querySelector('[data-field="quantity"]');
        if (durInput && med.duration) durInput.value = med.duration;
        if (qtyInput && med.qty)      qtyInput.value = med.qty;
    });
}

function addGroupRow(item = {}, canRemove = true) {
    const template = document.getElementById('groupItemRowTemplate');
    const row = template.content.firstElementChild.cloneNode(true);
    const index = groupRowIndex++;

    row.dataset.index = index;
    row.querySelectorAll('[data-field]').forEach(function (field) {
        const fieldName = field.dataset.field;
        field.name = `items[${index}][${fieldName}]`;
        const val = item[fieldName] ?? (fieldName === 'quantity' ? '1' : '');
        field.value = val;
    });

    const removeBtn = row.querySelector('.remove-row');
    removeBtn.disabled = !canRemove;
    removeBtn.addEventListener('click', function () {
        $(row).find('.medicine-select, [data-field="dosage_id"], [data-field="route_id"]').select2('destroy');
        row.remove();
    });

    document.getElementById('groupRepeaterBody').appendChild(row);

    // Init Select2 on medicine select
    const medicineSel = row.querySelector('.medicine-select');
    if (medicineSel) {
        initMedicineSelect2(medicineSel);
        if (item.medicine_id) $(medicineSel).val(item.medicine_id).trigger('change.select2');
    }

    // Init Select2 on dosage select
    const dosageSel = row.querySelector('[data-field="dosage_id"]');
    if (dosageSel) {
        $(dosageSel).select2({
            dropdownParent: $('#groupFormModal'),
            placeholder: 'Select dosage...',
            allowClear: true,
            width: '100%',
        });
        if (item.dosage_id) $(dosageSel).val(item.dosage_id).trigger('change.select2');
    }

    // Init Select2 on route select
    const routeSel = row.querySelector('[data-field="route_id"]');
    if (routeSel) {
        $(routeSel).select2({
            dropdownParent: $('#groupFormModal'),
            placeholder: 'Select route...',
            allowClear: true,
            width: '100%',
        });
        if (item.route_id) $(routeSel).val(item.route_id).trigger('change.select2');
    }
}

function resetGroupForm() {
    groupRowIndex = 0;
    document.getElementById('groupFormModalTitle').innerText = 'New Prescription Group';
    document.getElementById('groupFormSubmitBtn').innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Medicine Group';
    document.getElementById('groupForm').action = groupStoreUrl;
    document.getElementById('groupFormMethod').value = 'POST';
    document.getElementById('group-id').value = '';
    document.getElementById('group-name').value = '';
    document.getElementById('group-code').value = '';
    $('#group-diagnosis').val('').trigger('change.select2');
    $('#groupRepeaterBody .medicine-select, #groupRepeaterBody [data-field="dosage_id"], #groupRepeaterBody [data-field="route_id"]').select2('destroy');
    document.getElementById('groupRepeaterBody').innerHTML = '';
    addGroupRow({}, false);
}
window.resetGroupForm = resetGroupForm;

function openGroupEditModal(record) {
    groupRowIndex = 0;
    document.getElementById('groupFormModalTitle').innerText = 'Edit: ' + (record.name ?? 'Medicine Group');
    document.getElementById('groupFormSubmitBtn').innerHTML = '<i class="bi bi-check-lg me-1"></i> Update Group';
    document.getElementById('groupForm').action = groupUpdateBase.replace('__ID__', record.id);
    document.getElementById('groupFormMethod').value = 'PUT';
    document.getElementById('group-id').value   = record.id ?? '';
    document.getElementById('group-name').value  = record.name ?? '';
    document.getElementById('group-code').value  = record.group_code ?? '';
    $('#group-diagnosis').val(record.diagnosis_id ?? '').trigger('change.select2');
    document.getElementById('groupRepeaterBody').innerHTML = '';

    const items = record.items && record.items.length ? record.items : [{}];
    items.forEach(function (item, index) {
        addGroupRow({
            medicine_id: item.medicine_id ?? '',
            dosage_id:   item.dosage_id ?? '',
            route_id:    item.route_id ?? '',
            duration:    item.duration ?? '',
            quantity:    item.quantity ?? '1',
        }, index !== 0);
    });

    bootstrap.Modal.getOrCreateInstance(document.getElementById('groupFormModal')).show();
}
window.openGroupEditModal = openGroupEditModal;

document.addEventListener('DOMContentLoaded', function () {
    const groupModalEl = document.getElementById('groupFormModal');
    if (groupModalEl && groupModalEl.parentElement !== document.body) {
        document.body.appendChild(groupModalEl);
    }

    // Diagnosis Select2
    $('#group-diagnosis').select2({
        dropdownParent: $('#groupFormModal'),
        placeholder: 'Search diagnosis...',
        allowClear: true,
        width: '100%',
    });

    document.getElementById('groupAddRowBtn').addEventListener('click', function () {
        addGroupRow({}, true);
    });

    // Auto-fill row fields when medicine is selected
    document.getElementById('groupRepeaterBody').addEventListener('change', function (e) {
        const sel = e.target;
        if (!sel.classList.contains('medicine-select')) return;

        const row  = sel.closest('tr');
        const med  = medicineDataMap[sel.value];
        if (!med) return;

        // dosage_id
        const dosageSel = row.querySelector('[data-field="dosage_id"], [name$="[dosage_id]"]');
        if (dosageSel && med.dosage_id) dosageSel.value = med.dosage_id;

        // duration
        const durInput = row.querySelector('[data-field="duration"], [name$="[duration]"]');
        if (durInput && med.duration) durInput.value = med.duration;

        // qty
        const qtyInput = row.querySelector('[data-field="quantity"], [name$="[quantity]"]');
        if (qtyInput && med.qty) qtyInput.value = med.qty;
    });

    document.querySelectorAll('.edit-group-modal-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            openGroupEditModal(JSON.parse(this.dataset.record));
        });
    });

    @if($errors->any())
    (function () {
        var oldItems = @json(old('items', []));
        groupRowIndex = 0;
        document.getElementById('groupFormMethod').value = @json(old('_method', 'POST'));
        document.getElementById('group-id').value = @json(old('group_id', ''));
        document.getElementById('group-name').value = @json(old('name', ''));
        document.getElementById('group-code').value = @json(old('group_code', ''));
        $('#group-diagnosis').val(@json(old('diagnosis_id', ''))).trigger('change.select2');
        document.getElementById('groupRepeaterBody').innerHTML = '';

        if (document.getElementById('groupFormMethod').value === 'PUT' && document.getElementById('group-id').value) {
            document.getElementById('groupFormModalTitle').innerText = 'Edit Medicine Group';
            document.getElementById('groupFormSubmitBtn').innerHTML = '<i class="bi bi-check-lg me-1"></i> Update Group';
            document.getElementById('groupForm').action = groupUpdateBase.replace('__ID__', document.getElementById('group-id').value);
        } else {
            document.getElementById('groupFormModalTitle').innerText = 'New Prescription Group';
            document.getElementById('groupFormSubmitBtn').innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Medicine Group';
            document.getElementById('groupForm').action = groupStoreUrl;
        }

        var oldItemsList = Array.isArray(oldItems) ? oldItems : Object.values(oldItems);
        (oldItemsList.length ? oldItemsList : [{}]).forEach(function (item, index) {
            addGroupRow(item, index !== 0);
        });

        bootstrap.Modal.getOrCreateInstance(document.getElementById('groupFormModal')).show();
    })();
    @else
    resetGroupForm();
    @endif
});
</script>
@endpush
