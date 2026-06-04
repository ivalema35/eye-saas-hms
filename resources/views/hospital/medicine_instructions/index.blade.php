@extends('hospital.layouts.app')
@section('title', 'Medicine Instructions')
@section('page-header', 'Medicine Master')

@section('page-actions')
    <button class="btn btn-primary btn-sm med-add-btn"
            data-bs-toggle="modal"
            data-bs-target="#instructionModal"
            onclick="resetInstructionForm()">
        <i class="bi bi-plus-lg me-1"></i> Add Instruction
    </button>
@endsection

@section('content')
<div class="medicine-instructions-page medicines-page">

<style>
    .medicine-instructions-page {
        --instruction-primary: #ebf5fbeb;
        --instruction-secondary: #1B4F72;
        --instruction-secondary-08: rgba(27, 79, 114, .08);
        --instruction-secondary-12: rgba(27, 79, 114, .12);
        --instruction-secondary-18: rgba(27, 79, 114, .18);
        --instruction-secondary-24: rgba(27, 79, 114, .24);
        color: var(--instruction-secondary);
    }

    .type-nav-tabs { background: var(--card-bg, #f7fbff); padding: 10px; border-radius: 14px; border: none; display: flex; gap: .5rem; align-items: center; box-shadow: none; }
    .type-nav-tabs .nav-item { margin: 0; }
    .type-nav-tabs .nav-link { border: none !important; background: transparent; color: var(--muted-color, #1f3560); padding: .5rem .9rem; border-radius: 999px; box-shadow: none; transition: all .15s ease-in-out; display: inline-flex; align-items: center; }
    .type-nav-tabs .nav-link i { margin-right: .5rem; }
    .type-nav-tabs .nav-link.active { background: var(--color-primary) !important; color: #ffffff !important; border-color: transparent !important; box-shadow: 0 6px 18px rgba(36,85,160,0.12); }
    .type-nav-tabs .nav-link:hover { background: rgba(36,85,160,0.1); color: var(--muted-color, #1f3560) !important; }
    
    .instruction-card {
        border: 1px solid var(--instruction-secondary-12) !important;
        border-radius: 22px;
        background: rgba(255, 255, 255, .86);
        box-shadow: 0 18px 48px rgba(27, 79, 114, .10) !important;
        overflow: hidden;
        animation: med-card-rise 520ms cubic-bezier(.2,.9,.2,1) both;
    }
    .instruction-card-header {
        background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94)) !important;
        border-bottom: 1px solid var(--instruction-secondary-12) !important;
        padding: 1.15rem 1.25rem !important;
    }
    .instruction-title-wrap {
        display: flex;
        align-items: center;
        gap: .85rem;
    }
    .instruction-title-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        background: var(--instruction-secondary);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 14px 30px rgba(27, 79, 114, .22);
    }
    .instruction-title {
        color: var(--instruction-secondary) !important;
        font-weight: 900;
        letter-spacing: -.2px;
    }
    .instruction-subtitle {
        color: rgba(27, 79, 114, .68);
        font-size: .84rem;
        font-weight: 650;
        margin-top: .15rem;
    }
    .instruction-table-wrap {
        padding: .9rem;
        overflow-x: auto;
    }
    .instruction-table {
        border-collapse: separate;
        border-spacing: 0 8px;
        min-width: 640px;
    }
    .instruction-table thead tr,
    .instruction-table thead th {
        background: var(--instruction-secondary) !important;
    }
    .instruction-table thead th {
        color: #fff !important;
        border: 0 !important;
        padding: .9rem 1rem;
        font-size: .72rem;
        letter-spacing: .08em;
        font-weight: 900;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .instruction-table thead th:first-child {
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }
    .instruction-table thead th:last-child {
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }
    .instruction-table tbody tr {
        animation: med-row-in 460ms ease both;
        transition: transform 170ms ease, box-shadow 170ms ease;
    }
    .instruction-table tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 34px rgba(27, 79, 114, .10);
    }
    .instruction-table tbody td {
        background: rgba(255, 255, 255, .92);
        border-top: 1px solid var(--instruction-secondary-08);
        border-bottom: 1px solid var(--instruction-secondary-08);
        color: var(--instruction-secondary);
        padding: .9rem 1rem;
        vertical-align: middle;
        font-weight: 650;
    }
    .instruction-table tbody td:first-child {
        border-left: 1px solid var(--instruction-secondary-08);
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }
    .instruction-table tbody td:last-child {
        border-right: 1px solid var(--instruction-secondary-08);
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }
    .instruction-index-cell {
        color: rgba(27, 79, 114, .58) !important;
        font-weight: 900;
    }
    .instruction-name-cell {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        color: var(--instruction-secondary);
        font-weight: 800;
        letter-spacing: -.1px;
    }
    .instruction-name-cell i {
        color: var(--instruction-secondary);
    }
    .instruction-icon-btn {
        width: 34px;
        height: 34px;
        padding: 0 !important;
        border-radius: 50% !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, .78) !important;
        transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, color 170ms ease;
    }
    .instruction-edit-btn {
        border-color: var(--instruction-secondary-24) !important;
        color: var(--instruction-secondary) !important;
    }
    .instruction-edit-btn:hover {
        background: var(--instruction-secondary) !important;
        color: #fff !important;
    }
    .instruction-edit-btn:hover i {
        color: #fff !important;
    }
    .instruction-delete-btn {
        border-color: #dc2626 !important;
        color: #dc2626 !important;
    }
    .instruction-delete-btn:hover {
        background: #dc2626 !important;
        color: #fff !important;
    }
    .instruction-icon-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(27, 79, 114, .12);
    }
    .instruction-empty-cell {
        background: var(--instruction-primary) !important;
        border: 1px dashed var(--instruction-secondary-18) !important;
        border-radius: 16px !important;
        color: var(--instruction-secondary) !important;
        font-weight: 800;
        font-style: normal !important;
    }
    @media (prefers-reduced-motion: reduce) {
        .medicine-instructions-page,
        .instruction-card,
        .instruction-table tbody tr {
            animation: none;
        }
        .medicine-instructions-page * {
            transition: none !important;
        }
    }
    @media (max-width: 576px) {
        .instruction-card-header {
            align-items: flex-start;
        }
        .instruction-title-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
        }
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

<ul class="nav nav-tabs mb-4 type-nav-tabs">
    <li class="nav-item">
        <a class="nav-link"
           href="{{ route('hospital.medicine-dosages.index', ['slug' => $slug]) }}">
            <i class="bi bi-capsule-pill me-1"></i> Dosages
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('hospital.medicine-types.index', ['slug' => $slug]) }}">
            <i class="bi bi-tags me-1"></i> Medicine Types
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('hospital.medicine-categories.index', ['slug' => $slug]) }}">
            <i class="bi bi-grid me-1"></i> Medicine Categories
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('hospital.medicine-routes.index', ['slug' => $slug]) }}">
            <i class="bi bi-arrow-right-circle me-1"></i> Route of Admin.
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('hospital.medicines.index', ['slug' => $slug]) }}">
            <i class="bi bi-capsule me-1"></i> Medicines
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('hospital.medicine-groups.index', ['slug' => $slug]) }}">
            <i class="bi bi-collection me-1"></i> Medicine Groups
        </a>
    </li>
    {{-- <li class="nav-item">
        <a class="nav-link active"
           href="{{ route('hospital.medicine_instructions.index', ['slug' => $slug]) }}">
            <i class="bi bi-list-ul me-1"></i> Instructions
        </a>
    </li> --}}
</ul>

<div class="card premium-card border-0 shadow-sm instruction-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3 py-3 instruction-card-header">
        <div class="instruction-title-wrap">
            <span class="instruction-title-icon">
                <i class="bi bi-list-ul fs-4"></i>
            </span>
            <div>
                <h5 class="mb-0 fw-bold instruction-title" style="color: var(--color-primary);">
                    Instructions
                </h5>
                <div class="instruction-subtitle">Manage common medicine timing and usage instructions.</div>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive instruction-table-wrap">
            <table class="table premium-table table-hover align-middle mb-0 instruction-table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Instruction</th>
                        <th class="text-end" style="width:120px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($instructions as $index => $instruction)
                    <tr>
                        <td class="text-muted instruction-index-cell">{{ $index + 1 }}</td>
                        <td class="fw-semibold">
                            <span class="instruction-name-cell">{{ $instruction->value }}</span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1 action-btn-group">
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary instruction-icon-btn instruction-edit-btn edit-instruction-btn"
                                        data-record="{{ json_encode($instruction) }}"
                                        title="Edit">
                                    <i class="bi bi-pencil-fill" style="color: var(--color-secondary);"></i>
                                </button>
                                <form action="{{ route('hospital.medicine_instructions.destroy', ['slug' => $slug, 'id' => $instruction->id]) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Delete this instruction? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger instruction-icon-btn instruction-delete-btn" title="Delete">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center py-5 text-muted instruction-empty-cell fst-italic">
                            No instructions found. Add one to get started.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<div class="modal fade premium-modal" id="instructionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="instructionModalTitle" style="color: var(--color-primary);">
                    Add Instruction
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="instructionForm"
                  action="{{ route('hospital.medicine_instructions.store', ['slug' => $slug]) }}"
                  method="POST">
                @csrf
                <input type="hidden" name="_method" id="instructionFormMethod" value="POST">

                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-medium">
                            Instruction <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="value"
                               id="input-value"
                               class="form-control clinical-input @error('value') is-invalid @enderror"
                               placeholder="e.g. After Food, Empty Stomach..."
                               value="{{ old('value') }}"
                               required>
                        @error('value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                         Cancel
                    </button>
                    <button type="submit" class="btn btn-primary px-4">
                         Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const storeUrl = "{{ route('hospital.medicine_instructions.store', ['slug' => $slug]) }}";
const updateBase = "{{ route('hospital.medicine_instructions.update', ['slug' => $slug, 'id' => '__ID__']) }}";

function resetInstructionForm() {
    document.getElementById('instructionModalTitle').innerText = 'Add Instruction';
    document.getElementById('instructionForm').reset();
    document.getElementById('instructionFormMethod').value = 'POST';
    document.getElementById('instructionForm').action = storeUrl;
}
window.resetInstructionForm = resetInstructionForm;

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.edit-instruction-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var record = JSON.parse(this.dataset.record);

            document.getElementById('instructionModalTitle').innerText = 'Edit Instruction';
            document.getElementById('instructionFormMethod').value = 'PUT';
            document.getElementById('instructionForm').action = updateBase.replace('__ID__', record.id);
            document.getElementById('input-value').value = record.value ?? '';

            bootstrap.Modal.getOrCreateInstance(document.getElementById('instructionModal')).show();
        });
    });

    @if($errors->any())
    (function () {
        var oldMethod = @json(old('_method', 'POST'));
        document.getElementById('instructionFormMethod').value = oldMethod;
        document.getElementById('instructionModalTitle').innerText =
            oldMethod === 'PUT' ? 'Edit Instruction' : 'Add Instruction';
        if (oldMethod !== 'PUT') {
            document.getElementById('instructionForm').action = storeUrl;
        }
        document.getElementById('input-value').value = @json(old('value', ''));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('instructionModal')).show();
    })();
    @endif
});
</script>
@endpush
