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
        border-spacing: 0 8px;
        min-width: 900px;
    }

    .group-form-modal .group-modal-table thead th {
        background: #1B4F72 !important;
        color: #fff !important;
        border: 0 !important;
        padding: .82rem .9rem;
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

    .group-form-modal .group-modal-table tbody td {
        background: rgba(255, 255, 255, .92);
        border-top: 1px solid rgba(27, 79, 114, .08);
        border-bottom: 1px solid rgba(27, 79, 114, .08);
        padding: .65rem;
        vertical-align: middle;
    }

    .group-form-modal .group-modal-table tbody td:first-child {
        border-left: 1px solid rgba(27, 79, 114, .08);
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .group-form-modal .group-modal-table tbody td:last-child {
        border-right: 1px solid rgba(27, 79, 114, .08);
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
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
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">
                                Group Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" id="group-name"
                                   value="{{ old('name') }}"
                                   class="form-control clinical-input @error('name') is-invalid @enderror"
                                   required placeholder="e.g. Cataract Post-Op Standard">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <h6 class="group-modal-section-title mb-3 pb-2 border-bottom">
                        <i class="bi bi-list-ul me-1"></i> Medicines in this Group
                    </h6>

                    <div class="table-responsive mb-3">
                        <table class="table premium-table align-middle group-modal-table">
                            <thead>
                                <tr>
                                    <th style="min-width:220px">Medicine</th>
                                    <th style="min-width:160px">Dose</th>
                                    <th style="min-width:190px">Instruction</th>
                                    <th style="min-width:120px">Days</th>
                                    <th style="width:90px" class="text-center">Qty</th>
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
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
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
            <select class="form-select clinical-input-sm" data-field="instructions">
                <option value="">Select instruction...</option>
                @foreach($instructions as $inst)
                    <option value="{{ $inst->value }}">{{ $inst->value }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="text" class="form-control clinical-input-sm" required placeholder="e.g. 7"
                   style="display:inline-block; width:60px;" data-field="duration"> Days
            <input type="hidden" data-field="frequency" value="OD">
        </td>
        <td>
            <input type="number" class="form-control clinical-input-sm text-center" required min="1" value="1" data-field="quantity">
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
const groupUpdateBase = "{{ route('hospital.medicine-groups.update', ['slug' => $slug, 'medicine_group' => '__ID__']) }}";

let groupRowIndex = 0;

function addGroupRow(item = {}, canRemove = true) {
    const template = document.getElementById('groupItemRowTemplate');
    const row = template.content.firstElementChild.cloneNode(true);
    const index = groupRowIndex++;

    row.dataset.index = index;
    row.querySelectorAll('[data-field]').forEach(function (field) {
        const fieldName = field.dataset.field;
        field.name = `items[${index}][${fieldName}]`;
        field.value = item[fieldName] ?? (fieldName === 'quantity' ? '1' : (fieldName === 'frequency' ? 'OD' : ''));
    });

    const removeBtn = row.querySelector('.remove-row');
    removeBtn.disabled = !canRemove;
    removeBtn.addEventListener('click', function () {
        row.remove();
    });

    document.getElementById('groupRepeaterBody').appendChild(row);
}

function resetGroupForm() {
    groupRowIndex = 0;
    document.getElementById('groupFormModalTitle').innerText = 'New Prescription Group';
    document.getElementById('groupFormSubmitBtn').innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Medicine Group';
    document.getElementById('groupForm').action = groupStoreUrl;
    document.getElementById('groupFormMethod').value = 'POST';
    document.getElementById('group-id').value = '';
    document.getElementById('group-name').value = '';
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
    document.getElementById('group-id').value = record.id ?? '';
    document.getElementById('group-name').value = record.name ?? '';
    document.getElementById('groupRepeaterBody').innerHTML = '';

    const items = record.items && record.items.length ? record.items : [{}];
    items.forEach(function (item, index) {
        addGroupRow({
            medicine_id: item.medicine_id ?? '',
            dosage_id: item.dosage_id ?? '',
            instructions: item.instructions ?? '',
            duration: item.duration ?? '',
            frequency: item.frequency ?? 'OD',
            quantity: item.quantity ?? '1',
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

    document.getElementById('groupAddRowBtn').addEventListener('click', function () {
        addGroupRow({}, true);
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
