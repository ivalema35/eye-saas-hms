@extends('hospital.layouts.app')
@section('title', 'Edit Medicine Group')
@section('page-header', 'Edit Medicine Group')

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
            <i class="bi bi-pencil-square me-2"></i> Edit: {{ $group->name }}
        </h5>
    </div>

    <div class="card-body p-4">
        <form action="{{ route('hospital.medicine-groups.update', ['slug' => $slug, 'medicine_group' => $group->id]) }}"
              method="POST">
            @csrf @method('PUT')

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-medium">
                        Group Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="name"
                           value="{{ old('name', $group->name) }}"
                           class="form-control clinical-input @error('name') is-invalid @enderror"
                           required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <h6 class="fw-bold mb-3 pb-2 border-bottom" style="color: var(--color-primary);">
                <i class="bi bi-list-ul me-1"></i> Medicines in this Group
            </h6>

            <div class="table-responsive mb-3">
                <table class="table premium-table align-middle" id="repeaterTable">
                    <thead>
                        <tr>
                            <th style="min-width:220px">Medicine</th>
                            <th style="min-width:160px">Dose</th>
                            <th style="min-width:180px">Instruction</th>
                            <th style="min-width:120px">Days</th>
                            <th style="width:90px" class="text-center">Qty</th>
                            <th style="width:60px" class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody id="repeaterBody">
                        @foreach($group->items as $idx => $item)
                        <tr class="item-row" data-index="{{ $idx }}">
                            <td>
                                <select name="items[{{ $idx }}][medicine_id]"
                                        class="form-select clinical-input-sm medicine-select" required>
                                    <option value="">Select medicine...</option>
                                    @foreach($medicines as $med)
                                        <option value="{{ $med->id }}"
                                            {{ $item->medicine_id == $med->id ? 'selected' : '' }}>
                                            {{ $med->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="items[{{ $idx }}][dosage_id]"
                                        class="form-select clinical-input-sm">
                                    <option value="">Select dosage...</option>
                                    @foreach($dosages as $d)
                                        <option value="{{ $d->id }}"
                                            {{ $item->dosage_id == $d->id ? 'selected' : '' }}>
                                            {{ $d->dosage }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="items[{{ $idx }}][instructions]"
                                        class="form-select clinical-input-sm">
                                    <option value="">Select instruction...</option>
                                    @foreach($instructions as $inst)
                                        <option value="{{ $inst->value }}"
                                            {{ $item->instructions == $inst->value ? 'selected' : '' }}>
                                            {{ $inst->value }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" name="items[{{ $idx }}][duration]"
                                       class="form-control clinical-input-sm" required
                                       placeholder="e.g. 7" style="display:inline-block; width:60px;"
                                       value="{{ $item->duration }}"> Days
                                <input type="hidden" name="items[{{ $idx }}][frequency]" value="{{ $item->frequency ?: 'OD' }}">
                            </td>
                            <td>
                                <input type="number" name="items[{{ $idx }}][quantity]"
                                       class="form-control clinical-input-sm text-center"
                                       required min="1" value="{{ $item->quantity }}">
                            </td>
                            <td class="text-end">
                                <button type="button"
                                        class="btn btn-outline-danger btn-sm remove-row"
                                        {{ $loop->first ? 'disabled' : '' }}
                                        title="Remove">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
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
                    <i class="bi bi-check-lg me-1"></i> Update Group
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let rowIndex = document.querySelectorAll('#repeaterBody .item-row').length;
    const tbody    = document.getElementById('repeaterBody');
    const addBtn   = document.getElementById('addRowBtn');
    // Capture index-0 template row once — always clone this, never a deleted row
    const firstRow = tbody.querySelector('tr.item-row[data-index="0"]');

    // Wire existing remove buttons
    tbody.querySelectorAll('.remove-row:not([disabled])').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.closest('tr').remove();
        });
    });

    addBtn.addEventListener('click', function () {
        const newRow = firstRow.cloneNode(true);
        newRow.setAttribute('data-index', rowIndex);

        newRow.querySelectorAll('input, select').forEach(function (el) {
            const name = el.getAttribute('name');
            if (name) {
                // Replace [0] specifically — we always clone the index-0 template row
                el.setAttribute('name', name.replace(/\[0\]/, '[' + rowIndex + ']'));
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
});
</script>
@endpush
