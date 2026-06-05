@extends('hospital.layouts.app')
@section('title', 'Manage '.$title)
@section('page-header', $title)

@section('content')

@php
    $masterPermissionKey = str_contains($routeGroup, '.detail.') ? 'master.eye_exam' : 'master.case_types';
    $canWrite = auth('hospital_user')->user()?->role?->is_super
        || app(\App\Services\Auth\RolePermissionService::class)->can($masterPermissionKey);
    $modalMasterTypes = ['cases', 'locations', 'referrers', 'durations', 'chief-complaints', 'kcos', 'diagnosis', 'advice', 'vn', 'vngl', 'vnst', 'pnvn', 'nrvn', 'sph_cyl', 'axis', 'nct', 'sac', 'lid', 'conj', 'cornea', 'ac', 'iris', 'pupil', 'lens', 'em', 'covertest', 'disc', 'fr'];
    $useModalLayout = in_array($type, $modalMasterTypes, true);
    $showBackButton = in_array($type, $modalMasterTypes, true);
@endphp

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

<div class="{{ $useModalLayout ? 'case-master-page' : '' }}">
    <div class="case-master-toolbar">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if($showBackButton)
                @php
                    $backUrl = auth('hospital_user')->user()?->role?->slug === 'doctor'
                        ? route('hospital.dashboard', ['slug' => $slug])
                        : route('hospital.masters.index', ['slug' => $slug]);
                @endphp
                <a href="{{ $backUrl }}" class="btn btn-light case-master-back-btn">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            @endif
            <p class="text-muted small mb-0">Keep your hospital's master data organized and up-to-date.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if($canWrite && $useModalLayout)
                <button type="button"
                        class="btn btn-primary case-master-add-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#masterFormModal"
                        onclick="resetForm()">
                    <i class="bi bi-plus-lg me-1"></i> Add {{ Str::singular($title) }}
                </button>
            @endif
        </div>
    </div>

    <div class="row g-4">
        @if($canWrite && ! $useModalLayout)
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 80px;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="rounded-circle p-2 me-3 bg-primary-subtle text-primary" id="formIconBg">
                                <i class="bi bi-plus-lg fs-5" id="formIcon"></i>
                            </div>
                            <h5 class="fw-bold mb-0" id="formTitle" style="color: var(--color-primary);">Add New Record</h5>
                        </div>

                        <form id="masterForm"
                              action="{{ route($routeGroup.'.store', ['slug' => $slug, 'type' => $type]) }}"
                              method="POST">
                            @csrf
                            <input type="hidden" name="_method" id="formMethod" value="POST">
                            <input type="hidden" name="_edit_id" id="editId" value="{{ old('_edit_id') }}">

                            @foreach($columns as $col)
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                                        {{ Str::headline($col) }} <span class="text-danger">*</span>
                                    </label>
                                    @if($type === 'sph_cyl' && $col === 'type')
                                        <select name="{{ $col }}"
                                                id="input-{{ $col }}"
                                                class="form-select form-select-lg bg-light border-0"
                                                style="font-size: 15px;"
                                                required>
                                            <option value="Positive" {{ old($col, 'Positive') === 'Positive' ? 'selected' : '' }}>Positive</option>
                                            <option value="Negative" {{ old($col) === 'Negative' ? 'selected' : '' }}>Negative</option>
                                        </select>
                                    @else
                                        <input type="text"
                                               name="{{ $col }}"
                                               id="input-{{ $col }}"
                                               class="form-control form-control-lg bg-light border-0"
                                               style="font-size: 15px;"
                                               placeholder="Enter {{ strtolower(Str::headline($col)) }}..."
                                               value="{{ old($col) }}"
                                               required>
                                    @endif
                                </div>
                            @endforeach

                            <div class="mt-4 pt-2">
                                <button type="submit" id="submitBtn" class="btn btn-primary w-100 py-2 fw-semibold rounded-3 mb-2">
                                    <i class="bi bi-check2 me-1"></i> Save Record
                                </button>
                                <button type="button" id="cancelBtn"
                                        class="btn btn-light w-100 py-2 fw-medium rounded-3 d-none text-muted"
                                        onclick="resetForm()">
                                    <i class="bi bi-x me-1"></i> Cancel Edit
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="{{ $canWrite && ! $useModalLayout ? 'col-lg-8' : 'col-12' }}">
            <div class="card border-0 shadow-sm rounded-4 {{ $useModalLayout ? 'case-master-card' : '' }}">
                <div class="card-body p-0">
                    <div class="table-responsive {{ $useModalLayout ? 'case-master-table-wrap' : '' }}">
                        <table class="table table-hover align-middle mb-0 {{ $useModalLayout ? 'case-master-table' : '' }}">
                            <thead class="bg-light">
                                <tr>
                                    <th width="8%" class="text-center py-3 text-muted small fw-bold font-monospace">#</th>
                                    @foreach($columns as $col)
                                        <th class="py-3 text-muted text-uppercase small fw-bold" style="letter-spacing: 0.5px;">
                                            {{ Str::headline($col) }}
                                        </th>
                                    @endforeach
                                    @if(in_array($type, ['advice','advices']) && isset($diagnoses) && $diagnoses->isNotEmpty())
                                        <th class="py-3 text-muted text-uppercase small fw-bold" style="letter-spacing: 0.5px;">
                                            Diagnosis
                                        </th>
                                    @endif
                                    @if(in_array($type, ['complaints','chief-complaints','kcos','sac','lid','conj','cornea','ac','iris','pupil','lens','em','covertest','disc','fr','diagnosis','diagnoses','advice','advices']))
                                        <th class="py-3 text-muted text-uppercase small fw-bold text-center" style="letter-spacing:0.5px;width:90px;">
                                            Favourite
                                        </th>
                                    @endif
                                    @if($canWrite)
                                        <th width="15%" class="text-end pe-4 py-3 text-muted text-uppercase small fw-bold">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $index => $record)
                                    <tr>
                                        <td class="text-center text-muted small font-monospace">{{ $index + 1 }}</td>
                                        @foreach($columns as $col)
                                            <td class="fw-medium text-dark py-3">{{ $record->$col }}</td>
                                        @endforeach
                                        @if(in_array($type, ['advice','advices']) && isset($diagnoses) && $diagnoses->isNotEmpty())
                                            <td class="py-3">
                                                @if($record->diagnosis)
                                                    <span style="font-size:.78rem;font-weight:700;color:#0d6949;background:rgba(13,105,73,.08);border:1px solid rgba(13,105,73,.2);border-radius:999px;padding:.2rem .6rem;">
                                                        <i class="bi bi-clipboard2-pulse me-1"></i>{{ $record->diagnosis->value }}
                                                    </span>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                        @endif
                                        @if(in_array($type, ['complaints','chief-complaints','kcos','sac','lid','conj','cornea','ac','iris','pupil','lens','em','covertest','disc','fr','diagnosis','diagnoses','advice','advices']))
                                            <td class="py-3 text-center">
                                                <button type="button"
                                                        class="btn btn-sm fav-toggle-btn border-0 bg-transparent p-1"
                                                        data-id="{{ $record->id }}"
                                                        data-state="{{ $record->is_favourite ? '1' : '0' }}"
                                                        data-url="{{ route('hospital.masters.detail.toggle-favourite', ['slug' => $slug, 'type' => $type, 'id' => $record->id]) }}"
                                                        title="{{ $record->is_favourite ? 'Remove favourite' : 'Mark as favourite' }}"
                                                        style="font-size:1.4rem;line-height:1;transition:transform .15s;">
                                                    <i class="bi {{ $record->is_favourite ? 'bi-heart-fill' : 'bi-heart' }}"
                                                       style="color:{{ $record->is_favourite ? '#e11d48' : '#cbd5e1' }};"></i>
                                                </button>
                                            </td>
                                        @endif
                                        @if($canWrite)
                                            <td class="text-end pe-4">
                                                <div class="btn-group shadow-sm rounded-3" role="group">
                                                    <button type="button"
                                                            class="btn btn-light border-0 text-primary {{ $useModalLayout ? 'case-master-icon-btn' : '' }}"
                                                            onclick="editRecord(@js($record))"
                                                            title="Edit">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <form action="{{ route($routeGroup.'.destroy', ['slug' => $slug, 'type' => $type, 'id' => $record->id]) }}"
                                                          method="POST"
                                                          class="d-inline"
                                                          onsubmit="return confirm('Delete this record? This cannot be undone.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="btn btn-light border-0 text-danger {{ $useModalLayout ? 'case-master-icon-btn' : '' }}"
                                                                title="Delete">
                                                            <i class="bi bi-trash3-fill"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($columns) + ($canWrite ? 2 : 1) }}" class="text-center py-5">
                                            <div class="text-muted opacity-50 mb-3">
                                                <i class="bi bi-inbox-fill" style="font-size: 3rem;"></i>
                                            </div>
                                            <h6 class="fw-bold text-dark">No {{ $title }} Found</h6>
                                            <p class="text-muted small mb-0">
                                                @if($canWrite)
                                                    {{ $useModalLayout ? 'Use the Add button to create your first record.' : 'Use the form on the left to add your first record.' }}
                                                @else
                                                    No records have been added yet.
                                                @endif
                                            </p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($canWrite && $useModalLayout)
        <div class="modal fade case-master-modal" id="masterFormModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <div class="modal-header border-0 pb-0">
                        <div class="d-flex align-items-center">
                            <span class="case-master-modal-icon" id="formIconBg">
                                <i class="bi bi-plus-lg" id="formIcon"></i>
                            </span>
                            <h5 class="modal-title fw-bold mb-0" id="formTitle">Add New Record</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <form id="masterForm"
                          action="{{ route($routeGroup.'.store', ['slug' => $slug, 'type' => $type]) }}"
                          method="POST">
                        @csrf
                        <input type="hidden" name="_method" id="formMethod" value="POST">
                        <input type="hidden" name="_edit_id" id="editId" value="{{ old('_edit_id') }}">

                        <div class="modal-body">
                            @foreach($columns as $col)
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                                        {{ Str::headline($col) }} <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           name="{{ $col }}"
                                           id="input-{{ $col }}"
                                           class="form-control form-control-lg case-master-input"
                                           style="font-size: 15px;"
                                           placeholder="Enter {{ strtolower(Str::headline($col)) }}..."
                                           value="{{ old($col) }}"
                                           required>
                                </div>
                            @endforeach

                            @if(in_array($type, ['advice','advices']) && isset($diagnoses) && $diagnoses->isNotEmpty())
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                                    <i class="bi bi-clipboard2-pulse me-1"></i> Diagnosis
                                </label>
                                <select name="diagnosis_id"
                                        id="input-diagnosis_id"
                                        class="form-select form-select-lg case-master-input"
                                        style="font-size: 15px;">
                                    <option value="">— Select diagnosis (optional) —</option>
                                    @foreach($diagnoses as $diag)
                                        <option value="{{ $diag->id }}">{{ $diag->value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                        </div>

                        <div class="modal-footer border-0 gap-2">
                            <button type="button" id="cancelBtn" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">
                                <i class="bi bi-x me-1"></i> Cancel
                            </button>
                            <button type="submit" id="submitBtn" class="btn btn-primary fw-semibold rounded-3">
                                <i class="bi bi-check2 me-1"></i> Save Record
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@if($canWrite)
@push('scripts')
<script>
    const storeUrl   = "{{ route($routeGroup.'.store',  ['slug' => $slug, 'type' => $type]) }}";
    const updateBase = "{{ route($routeGroup.'.update', ['slug' => $slug, 'type' => $type, 'id' => '__ID__']) }}";

    function resetForm() {
        const form = document.getElementById('masterForm');
        form.reset();
        document.getElementById('editId').value     = '';
        document.getElementById('formMethod').value = 'POST';
        form.action = storeUrl;

        const submitBtn = document.getElementById('submitBtn');
        submitBtn.innerHTML = '<i class="bi bi-check2 me-1"></i> Save Record';
        submitBtn.classList.replace('btn-success', 'btn-primary');

        document.getElementById('cancelBtn')?.classList.add('d-none');
        document.getElementById('formTitle').innerText = 'Add New Record';

        const iconBg = document.getElementById('formIconBg');
        iconBg?.classList.replace('bg-success-subtle', 'bg-primary-subtle');
        iconBg?.classList.replace('text-success', 'text-primary');
        document.getElementById('formIcon').className = 'bi bi-plus-lg{{ $useModalLayout ? '' : ' fs-5' }}';
    }
    window.resetForm = resetForm;

    function editRecord(record) {
        document.getElementById('formMethod').value  = 'PUT';
        document.getElementById('editId').value      = record.id;
        document.getElementById('masterForm').action = updateBase.replace('__ID__', record.id);

        const submitBtn = document.getElementById('submitBtn');
        submitBtn.innerHTML = '<i class="bi bi-pencil-square me-1"></i> Update Record';
        submitBtn.classList.replace('btn-primary', 'btn-success');

        document.getElementById('cancelBtn')?.classList.remove('d-none');
        document.getElementById('formTitle').innerText = 'Edit Record';

        const iconBg = document.getElementById('formIconBg');
        iconBg?.classList.replace('bg-primary-subtle', 'bg-success-subtle');
        iconBg?.classList.replace('text-primary', 'text-success');
        document.getElementById('formIcon').className = 'bi bi-pencil-square{{ $useModalLayout ? '' : ' fs-5' }}';

        for (const [key, value] of Object.entries(record)) {
            const field = document.getElementById('input-' + key);
            if (field) { field.value = value ?? ''; }
        }

        // Diagnosis dropdown (advice type)
        const diagSel = document.getElementById('input-diagnosis_id');
        if (diagSel) { diagSel.value = record.diagnosis_id ?? ''; }


        @if($useModalLayout)
            bootstrap.Modal.getOrCreateInstance(document.getElementById('masterFormModal')).show();
        @else
            window.scrollTo({ top: 0, behavior: 'smooth' });
        @endif
    }
    window.editRecord = editRecord;

    // Heart / favourite toggle
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.fav-toggle-btn');
        if (!btn) return;

        const icon    = btn.querySelector('i');
        const current = btn.dataset.state === '1';
        const newState = !current;

        // Optimistic UI
        btn.dataset.state = newState ? '1' : '0';
        icon.className    = 'bi ' + (newState ? 'bi-heart-fill' : 'bi-heart');
        icon.style.color  = newState ? '#e11d48' : '#cbd5e1';
        btn.title         = newState ? 'Remove favourite' : 'Mark as favourite';
        btn.style.transform = 'scale(1.3)';
        setTimeout(() => btn.style.transform = '', 200);

        fetch(btn.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        }).then(r => r.json()).then(data => {
            const confirmed = data.is_favourite;
            btn.dataset.state = confirmed ? '1' : '0';
            icon.className    = 'bi ' + (confirmed ? 'bi-heart-fill' : 'bi-heart');
            icon.style.color  = confirmed ? '#e11d48' : '#cbd5e1';
            btn.title         = confirmed ? 'Remove favourite' : 'Mark as favourite';
        }).catch(() => {
            // revert on error
            btn.dataset.state = current ? '1' : '0';
            icon.className    = 'bi ' + (current ? 'bi-heart-fill' : 'bi-heart');
            icon.style.color  = current ? '#e11d48' : '#cbd5e1';
        });
    });

    @if($errors->any() && old('_method') === 'PUT' && old('_edit_id'))
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('formMethod').value  = 'PUT';
        document.getElementById('editId').value      = @json(old('_edit_id'));
        document.getElementById('masterForm').action = updateBase.replace('__ID__', "{{ old('_edit_id') }}");

        const submitBtn = document.getElementById('submitBtn');
        submitBtn.innerHTML = '<i class="bi bi-pencil-square me-1"></i> Update Record';
        submitBtn.classList.replace('btn-primary', 'btn-success');

        document.getElementById('cancelBtn')?.classList.remove('d-none');
        document.getElementById('formTitle').innerText = 'Edit Record';

        const iconBg = document.getElementById('formIconBg');
        iconBg?.classList.replace('bg-primary-subtle', 'bg-success-subtle');
        iconBg?.classList.replace('text-primary', 'text-success');
        document.getElementById('formIcon').className = 'bi bi-pencil-square{{ $useModalLayout ? '' : ' fs-5' }}';

        @if($useModalLayout)
            bootstrap.Modal.getOrCreateInstance(document.getElementById('masterFormModal')).show();
        @endif
    });
    @endif
</script>
@endpush
@endif

@if($useModalLayout)
@push('styles')
<style>
    .case-master-page {
        --case-primary: #1B4F72;
        --case-soft: #ebf5fbeb;
        --case-border: rgba(27, 79, 114, .12);
        --case-border-strong: rgba(27, 79, 114, .22);
        color: var(--case-primary);
    }

    .case-master-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .case-master-add-btn {
        background: var(--case-primary) !important;
        border-color: var(--case-primary) !important;
        border-radius: 12px;
        font-weight: 900;
        box-shadow: 0 12px 26px rgba(27, 79, 114, .16);
    }

    .case-master-back-btn {
        border: 1px solid var(--case-border-strong) !important;
        border-radius: 12px;
        color: var(--case-primary) !important;
        font-weight: 800;
        background: rgba(255, 255, 255, .92) !important;
        box-shadow: 0 10px 24px rgba(27, 79, 114, .08);
    }

    .case-master-back-btn:hover {
        background: var(--case-soft) !important;
        border-color: var(--case-primary) !important;
        color: var(--case-primary) !important;
    }

    .case-master-card {
        border: 1px solid var(--case-border) !important;
        border-radius: 22px !important;
        box-shadow: 0 18px 48px rgba(27, 79, 114, .10) !important;
        overflow: hidden;
    }

    .case-master-table-wrap {
        padding: .9rem;
        background: var(--case-soft);
    }

    .case-master-table {
        border-collapse: separate;
        border-spacing: 0 8px;
        min-width: 720px;
    }

    .case-master-table thead th {
        background: var(--case-primary) !important;
        color: #fff !important;
        border: 0 !important;
        padding: .9rem 1rem !important;
        font-size: .72rem;
        letter-spacing: .08em;
    }

    .case-master-table thead th:first-child {
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .case-master-table thead th:last-child {
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .case-master-table tbody td {
        background: rgba(255, 255, 255, .94);
        border-top: 1px solid rgba(27, 79, 114, .08);
        border-bottom: 1px solid rgba(27, 79, 114, .08);
        color: var(--case-primary);
        padding: .9rem 1rem;
        vertical-align: middle;
        font-weight: 650;
    }

    .case-master-table tbody td:first-child {
        border-left: 1px solid rgba(27, 79, 114, .08);
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .case-master-table tbody td:last-child {
        border-right: 1px solid rgba(27, 79, 114, .08);
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .case-master-icon-btn {
        width: 34px;
        height: 34px;
        border-radius: 50% !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .case-master-modal .modal-content {
        border: 1px solid var(--case-border) !important;
        border-radius: 22px;
        box-shadow: 0 20px 50px rgba(27, 79, 114, .15);
        overflow: hidden;
    }

    .case-master-modal .modal-header {
        background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94));
        border-bottom: 1px solid var(--case-border) !important;
        padding: 1.25rem 1.5rem !important;
    }

    .case-master-modal-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: var(--case-primary);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: .8rem;
    }

    .case-master-input {
        border: 1.5px solid var(--case-border) !important;
        border-radius: 12px;
        background: rgba(235, 245, 251, .42);
        color: var(--case-primary);
        font-weight: 650;
    }

    .case-master-input:focus {
        border-color: var(--case-primary) !important;
        box-shadow: 0 0 0 4px rgba(27, 79, 114, .12);
    }
</style>
@endpush
@endif
