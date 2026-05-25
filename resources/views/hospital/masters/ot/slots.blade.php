@extends('hospital.layouts.app')
@section('title', 'OT Slots Master')
@section('page-header', 'OT Slots Master')

@section('content')
<div class="ot-master-page">
    <div class="ot-master-toolbar">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('hospital.masters.index', ['slug' => $slug]) }}"
               class="btn btn-light ot-master-back-btn">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <p class="text-muted small mb-0">Keep your OT master data organized and easy to update.</p>
        </div>
        <button type="button"
                class="btn btn-primary ot-master-add-btn"
                data-bs-toggle="modal"
                data-bs-target="#slotFormModal"
                onclick="resetForm()">
            <i class="bi bi-plus-lg me-1"></i> Add OT Slot
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4 ot-master-card">
        <div class="card-body p-0">
            <div class="table-responsive ot-master-table-wrap">
                <table class="table table-hover align-middle mb-0 ot-master-table">
                    <thead>
                    <tr>
                        <th class="ps-4">#</th>
                        <th>Slot</th>
                        <th>Start</th>
                        <th>End</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($records as $index => $record)
                        <tr>
                            <td class="ps-4">{{ $index + 1 }}</td>
                            <td>{{ $record->slot_name }}</td>
                            <td>{{ $record->start_time }}</td>
                            <td>{{ $record->end_time }}</td>
                            <td class="text-end pe-4">
                                <div class="btn-group shadow-sm rounded-3" role="group">
                                    <button class="btn btn-light border-0 text-primary ot-master-icon-btn" type="button" onclick='editRecord(@json($record))' title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form method="POST" class="d-inline" action="{{ route('hospital.masters.ot.slots.destroy', ['slug' => $slug, 'id' => $record->id]) }}" onsubmit="return confirm('Delete this OT slot?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-light border-0 text-danger ot-master-icon-btn" type="submit" title="Delete">
                                            <i class="bi bi-trash3-fill"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No OT slots found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade ot-master-modal" id="slotFormModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center">
                        <span class="ot-master-modal-icon" id="formIconBg">
                            <i class="bi bi-plus-lg" id="formIcon"></i>
                        </span>
                        <h5 class="modal-title fw-bold mb-0" id="formTitle">Add OT Slot</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="slotForm" method="POST" action="{{ route('hospital.masters.ot.slots.store', ['slug' => $slug]) }}">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Slot Name</label>
                            <input type="text" name="slot_name" id="slot_name" class="form-control form-control-lg ot-master-input" value="{{ old('slot_name') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Start Time</label>
                            <input type="time" name="start_time" id="start_time" class="form-control form-control-lg ot-master-input" value="{{ old('start_time') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">End Time</label>
                            <input type="time" name="end_time" id="end_time" class="form-control form-control-lg ot-master-input" value="{{ old('end_time') }}">
                        </div>
                    </div>

                    <div class="modal-footer border-0 gap-2">
                        <button class="btn btn-outline-secondary rounded-3" type="button" id="cancelBtn" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary fw-semibold rounded-3" type="submit" id="submitBtn">Save Slot</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const storeUrl = "{{ route('hospital.masters.ot.slots.store', ['slug' => $slug]) }}";
    const updateUrl = "{{ route('hospital.masters.ot.slots.update', ['slug' => $slug, 'id' => '__ID__']) }}";

    function editRecord(record) {
        const form = document.getElementById('slotForm');
        form.action = updateUrl.replace('__ID__', record.id);
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('slot_name').value = record.slot_name ?? '';
        document.getElementById('start_time').value = record.start_time ? record.start_time.substring(0, 5) : '';
        document.getElementById('end_time').value = record.end_time ? record.end_time.substring(0, 5) : '';
        document.getElementById('submitBtn').innerText = 'Update Slot';
        document.getElementById('formTitle').innerText = 'Edit OT Slot';
        document.getElementById('formIcon').className = 'bi bi-pencil-square';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('slotFormModal')).show();
    }

    function resetForm() {
        const form = document.getElementById('slotForm');
        form.reset();
        form.action = storeUrl;
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('submitBtn').innerText = 'Save Slot';
        document.getElementById('formTitle').innerText = 'Add OT Slot';
        document.getElementById('formIcon').className = 'bi bi-plus-lg';
    }
</script>
@endpush

@push('styles')
<style>
    .ot-master-page {
        --ot-primary: #1B4F72;
        --ot-soft: #ebf5fbeb;
        --ot-border: rgba(27, 79, 114, .12);
        --ot-border-strong: rgba(27, 79, 114, .22);
    }

    .ot-master-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .ot-master-add-btn {
        background: var(--ot-primary) !important;
        border-color: var(--ot-primary) !important;
        border-radius: 12px;
        font-weight: 900;
        box-shadow: 0 12px 26px rgba(27, 79, 114, .16);
    }

    .ot-master-back-btn {
        border: 1px solid var(--ot-border-strong) !important;
        border-radius: 12px;
        color: var(--ot-primary) !important;
        font-weight: 800;
        background: rgba(255, 255, 255, .92) !important;
        box-shadow: 0 10px 24px rgba(27, 79, 114, .08);
    }

    .ot-master-back-btn:hover {
        background: var(--ot-soft) !important;
        border-color: var(--ot-primary) !important;
        color: var(--ot-primary) !important;
    }

    .ot-master-card {
        border: 1px solid var(--ot-border) !important;
        border-radius: 22px !important;
        box-shadow: 0 18px 48px rgba(27, 79, 114, .10) !important;
        overflow: hidden;
    }

    .ot-master-table-wrap {
        padding: .9rem;
        background: var(--ot-soft);
    }

    .ot-master-table {
        border-collapse: separate;
        border-spacing: 0 8px;
        min-width: 760px;
    }

    .ot-master-table thead th {
        background: var(--ot-primary) !important;
        color: #fff !important;
        border: 0 !important;
        padding: .9rem 1rem !important;
        font-size: .72rem;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .ot-master-table thead th:first-child,
    .ot-master-table tbody td:first-child {
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .ot-master-table thead th:last-child,
    .ot-master-table tbody td:last-child {
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .ot-master-table tbody td {
        background: rgba(255, 255, 255, .94);
        border-top: 1px solid rgba(27, 79, 114, .08);
        border-bottom: 1px solid rgba(27, 79, 114, .08);
        color: var(--ot-primary);
        padding: .9rem 1rem;
        vertical-align: middle;
        font-weight: 650;
    }

    .ot-master-table tbody td:first-child {
        border-left: 1px solid rgba(27, 79, 114, .08);
    }

    .ot-master-table tbody td:last-child {
        border-right: 1px solid rgba(27, 79, 114, .08);
    }

    .ot-master-icon-btn {
        width: 34px;
        height: 34px;
        border-radius: 50% !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .ot-master-modal .modal-content {
        border: 1px solid var(--ot-border) !important;
        border-radius: 22px;
        box-shadow: 0 20px 50px rgba(27, 79, 114, .15);
        overflow: hidden;
    }

    .ot-master-modal .modal-header {
        background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94));
        border-bottom: 1px solid var(--ot-border) !important;
        padding: 1.25rem 1.5rem !important;
    }

    .ot-master-modal-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: var(--ot-primary);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: .8rem;
    }

    .ot-master-input {
        border: 1.5px solid var(--ot-border) !important;
        border-radius: 12px;
        background: rgba(235, 245, 251, .42);
        color: var(--ot-primary);
        font-weight: 650;
    }

    .ot-master-input:focus {
        border-color: var(--ot-primary) !important;
        box-shadow: 0 0 0 4px rgba(27, 79, 114, .12);
    }
</style>
@endpush
