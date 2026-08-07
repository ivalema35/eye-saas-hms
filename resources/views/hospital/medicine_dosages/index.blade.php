@extends('hospital.layouts.app')
@section('title', 'Medicine Dosages')
@section('page-header', 'Medicine Master')

@section('page-actions')
    <button class="btn btn-primary btn-sm med-add-btn" data-bs-toggle="modal" data-bs-target="#dosageModal"
        onclick="resetDosageForm()">
        <i class="bi bi-plus-lg me-1"></i> Add Dosage
    </button>
@endsection

@section('content')
    <div class="medicine-dosages-page medicines-page">

        <style>
            .medicine-dosages-page {
                --type-primary: #ebf5fbeb;
                --type-secondary: #1B4F72;
                --type-secondary-08: rgba(27, 79, 114, .08);
                --type-secondary-12: rgba(27, 79, 114, .12);
                --type-secondary-18: rgba(27, 79, 114, .18);
                --type-secondary-24: rgba(27, 79, 114, .24);
                --dosage-primary: #ebf5fbeb;
                --dosage-secondary: #1B4F72;
                --dosage-secondary-08: rgba(27, 79, 114, .08);
                --dosage-secondary-12: rgba(27, 79, 114, .12);
                --dosage-secondary-18: rgba(27, 79, 114, .18);
                --dosage-secondary-24: rgba(27, 79, 114, .24);
                color: var(--dosage-secondary);
            }

            .dosage-card {
                border: 1px solid var(--dosage-secondary-12) !important;
                border-radius: 22px;
                background: rgba(255, 255, 255, .86);
                box-shadow: 0 18px 48px rgba(27, 79, 114, .10) !important;
                overflow: hidden;
                animation: med-card-rise 520ms cubic-bezier(.2, .9, .2, 1) both;
            }

            .dosage-card-header {
                background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94)) !important;
                border-bottom: 1px solid var(--dosage-secondary-12) !important;
                padding: 1.15rem 1.25rem !important;
            }

            .dosage-title-wrap {
                display: flex;
                align-items: center;
                gap: .85rem;
            }

            .dosage-title-icon {
                width: 48px;
                height: 48px;
                border-radius: 16px;
                background: var(--dosage-secondary);
                color: #fff;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 14px 30px rgba(27, 79, 114, .22);
            }

            .dosage-title {
                color: var(--dosage-secondary) !important;
                font-weight: 900;
                letter-spacing: -.2px;
            }

            .dosage-subtitle {
                color: rgba(27, 79, 114, .68);
                font-size: .84rem;
                font-weight: 650;
                margin-top: .15rem;
            }

            .dosage-table-wrap {
                padding: .9rem;
                overflow-x: auto;
            }

            .dosage-table {
                border-collapse: separate;
                border-spacing: 0 8px;
                min-width: 640px;
            }

            .dosage-table thead tr,
            .dosage-table thead th {
                background: var(--dosage-secondary) !important;
            }

            .dosage-table thead th {
                color: #fff !important;
                border: 0 !important;
                padding: .9rem 1rem;
                font-size: .72rem;
                letter-spacing: .08em;
                font-weight: 900;
                text-transform: uppercase;
                white-space: nowrap;
            }

            .dosage-table thead th:first-child {
                border-top-left-radius: 14px;
                border-bottom-left-radius: 14px;
            }

            .dosage-table thead th:last-child {
                border-top-right-radius: 14px;
                border-bottom-right-radius: 14px;
            }

            .dosage-table tbody tr {
                animation: med-row-in 460ms ease both;
                transition: transform 170ms ease, box-shadow 170ms ease;
            }

            .dosage-table tbody tr:hover {
                transform: translateY(-2px);
                box-shadow: 0 16px 34px rgba(27, 79, 114, .10);
            }

            .dosage-table tbody td {
                background: rgba(255, 255, 255, .92);
                border-top: 1px solid var(--dosage-secondary-08);
                border-bottom: 1px solid var(--dosage-secondary-08);
                color: var(--dosage-secondary);
                padding: .9rem 1rem;
                vertical-align: middle;
                font-weight: 650;
            }

            .dosage-table tbody td:first-child {
                border-left: 1px solid var(--dosage-secondary-08);
                border-top-left-radius: 14px;
                border-bottom-left-radius: 14px;
            }

            .dosage-table tbody td:last-child {
                border-right: 1px solid var(--dosage-secondary-08);
                border-top-right-radius: 14px;
                border-bottom-right-radius: 14px;
            }

            .dosage-index-cell {
                color: rgba(27, 79, 114, .58) !important;
                font-weight: 900;
            }

            .dosage-name-cell {
                display: inline-flex;
                align-items: center;
                gap: .5rem;
                color: var(--dosage-secondary);
                font-weight: 800;
                letter-spacing: -.1px;
            }

            .dosage-name-cell i {
                color: var(--dosage-secondary);
            }

            .dosage-icon-btn {
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

            .dosage-edit-btn {
                border-color: var(--dosage-secondary-24) !important;
                color: var(--dosage-secondary) !important;
            }

            .dosage-edit-btn:hover {
                background: var(--dosage-secondary) !important;
                color: #fff !important;
            }

            .dosage-edit-btn:hover i {
                color: #fff !important;
            }

            .dosage-delete-btn {
                border-color: #dc2626 !important;
                color: #dc2626 !important;
            }

            .dosage-delete-btn:hover {
                background: #dc2626 !important;
                color: #fff !important;
            }

            .dosage-icon-btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 10px 22px rgba(27, 79, 114, .12);
            }

            .dosage-empty-cell {
                background: var(--dosage-primary) !important;
                border: 1px dashed var(--dosage-secondary-18) !important;
                border-radius: 16px !important;
                color: var(--dosage-secondary) !important;
                font-weight: 800;
                font-style: normal !important;
            }

            @media (prefers-reduced-motion: reduce) {

                .medicine-dosages-page,
                .dosage-card,
                .dosage-table tbody tr {
                    animation: none;
                }

                .medicine-dosages-page * {
                    transition: none !important;
                }
            }

            @media (max-width: 576px) {
                .dosage-card-header {
                    align-items: flex-start;
                }

                .dosage-title-icon {
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

        @if(session('error'))
            <div class="alert alert-danger d-flex gap-2 mb-4">
                <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <ul class="nav nav-tabs mb-4 type-nav-tabs">
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('hospital.medicine-dosages.index', ['slug' => $slug]) }}">
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
                    <i class="bi bi-arrow-right-circle me-1"></i> Mode
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
                <a class="nav-link" href="{{ route('hospital.medicine_instructions.index', ['slug' => $slug]) }}">
                    <i class="bi bi-list-ul me-1"></i> Instructions
                </a>
            </li> --}}
        </ul>

        <div class="card type-card border-0 dosage-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 dosage-card-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="dosage-title-icon" style="flex-shrink:0">
                        <i class="bi bi-capsule-pill fs-4"></i>
                    </span>
                    <div>
                        <h5 class="mb-0 fw-bold dosage-title" style="color: var(--color-primary);">Dosages</h5>
                        <div class="dosage-subtitle">Manage reusable dosage instructions for prescriptions.</div>
                    </div>
                </div>
                <span class="badge text-bg-light border"
                    style="font-size:.85rem;font-weight:900;padding:.45rem .85rem">{{ $dosages->count() }} total</span>
            </div>

            <div class="card-body p-0">
                <div class="type-table-wrap">
                    <table class="table type-table table-hover align-middle mb-0 js-datatable" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width:50px">#</th>
                                <th>Dosage</th>
                                <th class="text-end" style="width:120px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dosages as $index => $dosage)
                                <tr>
                                    <td class="type-index-cell">{{ $index + 1 }}</td>
                                    <td class="type-name-cell">{{ $dosage->dosage }}</td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1 action-btn-group">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary dosage-icon-btn dosage-edit-btn edit-dosage-btn"
                                                data-record="{{ json_encode($dosage) }}" title="Edit">
                                                <i class="bi bi-pencil-fill" style="color: var(--color-secondary);"></i>
                                            </button>
                                            <form
                                                action="{{ route('hospital.medicine-dosages.destroy', ['slug' => $slug, 'id' => $dosage->id]) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Delete this dosage? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-danger dosage-icon-btn dosage-delete-btn"
                                                    title="Delete">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted dosage-empty-cell fst-italic">
                                        No dosage found. Add one to get started.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade premium-modal" id="dosageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="dosageModalTitle" style="color: var(--color-primary);">
                        Add Dosage
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="dosageForm" action="{{ route('hospital.medicine-dosages.store', ['slug' => $slug]) }}"
                    method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="dosageFormMethod" value="POST">

                    <div class="modal-body py-4">
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                Dosage <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="dosage" id="input-dosage"
                                class="form-control clinical-input @error('dosage') is-invalid @enderror"
                                placeholder="Enter dosage instruction..." value="{{ old('dosage') }}" required>
                            @error('dosage')
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
        const storeUrl = "{{ route('hospital.medicine-dosages.store', ['slug' => $slug]) }}";
        const updateBase = "{{ route('hospital.medicine-dosages.update', ['slug' => $slug, 'id' => '__ID__']) }}";

        function resetDosageForm() {
            document.getElementById('dosageModalTitle').innerText = 'Add Dosage';
            document.getElementById('dosageForm').reset();
            document.getElementById('dosageFormMethod').value = 'POST';
            document.getElementById('dosageForm').action = storeUrl;
        }
        window.resetDosageForm = resetDosageForm;

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.edit-dosage-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var record = JSON.parse(this.dataset.record);

                    document.getElementById('dosageModalTitle').innerText = 'Edit Dosage';
                    document.getElementById('dosageFormMethod').value = 'PUT';
                    document.getElementById('dosageForm').action = updateBase.replace('__ID__', record.id);
                    document.getElementById('input-dosage').value = record.dosage ?? '';

                    bootstrap.Modal.getOrCreateInstance(document.getElementById('dosageModal')).show();
                });
            });

            @if($errors->any())
                (function () {
                    var oldMethod = @json(old('_method', 'POST'));
                    document.getElementById('dosageFormMethod').value = oldMethod;
                    document.getElementById('dosageModalTitle').innerText =
                        oldMethod === 'PUT' ? 'Edit Dosage' : 'Add Dosage';
                    if (oldMethod !== 'PUT') {
                        document.getElementById('dosageForm').action = storeUrl;
                    }
                    document.getElementById('input-dosage').value = @json(old('dosage', ''));
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('dosageModal')).show();
                })();
            @endif
                });
    </script>
@endpush