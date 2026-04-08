@extends('hospital.layouts.app')
@section('title', 'Manage '.$title)
@section('page-header', $title)

@section('content')

@php
    $masterPermissionKey = str_contains($routeGroup, '.detail.') ? 'master.eye_exam' : 'master.case_types';
    $canWrite = auth('hospital_user')->user()?->role?->is_super
        || app(\App\Services\Auth\RolePermissionService::class)->can($masterPermissionKey);
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

<p class="text-muted small mb-4">Keep your hospital's master data organized and up-to-date.</p>

<div class="row g-4">

    {{-- ── Left: Add / Edit Form ──────────────────────────────────────── --}}
    @if($canWrite)
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
                            <label class="form-label text-muted small fw-bold text-uppercase"
                                   style="letter-spacing: 0.5px;">
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
                        <button type="submit" id="submitBtn"
                                class="btn btn-primary w-100 py-2 fw-semibold rounded-3 mb-2">
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

    {{-- ── Right: Records Table ────────────────────────────────────────── --}}
    <div class="{{ $canWrite ? 'col-lg-8' : 'col-12' }}">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th width="8%" class="text-center py-3 text-muted small fw-bold font-monospace">#</th>
                                @foreach($columns as $col)
                                    <th class="py-3 text-muted text-uppercase small fw-bold"
                                        style="letter-spacing: 0.5px;">{{ Str::headline($col) }}</th>
                                @endforeach
                                @if($canWrite)
                                    <th width="15%"
                                        class="text-end pe-4 py-3 text-muted text-uppercase small fw-bold">
                                        Actions
                                    </th>
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
                                    @if($canWrite)
                                        <td class="text-end pe-4">
                                            <div class="btn-group shadow-sm rounded-3" role="group">
                                                <button type="button"
                                                        class="btn btn-light border-0 text-primary"
                                                        data-record="{{ json_encode($record) }}"
                                                        onclick="editRecord(JSON.parse(this.dataset.record))"
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
                                                            class="btn btn-light border-0 text-danger"
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
                                    <td colspan="{{ count($columns) + ($canWrite ? 2 : 1) }}"
                                        class="text-center py-5">
                                        <div class="text-muted opacity-50 mb-3">
                                            <i class="bi bi-inbox-fill" style="font-size: 3rem;"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark">No {{ $title }} Found</h6>
                                        <p class="text-muted small mb-0">
                                            @if($canWrite)
                                                Use the form on the left to add your first record.
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

        document.getElementById('cancelBtn').classList.add('d-none');
        document.getElementById('formTitle').innerText = 'Add New Record';

        const iconBg = document.getElementById('formIconBg');
        iconBg.classList.replace('bg-success-subtle', 'bg-primary-subtle');
        iconBg.classList.replace('text-success', 'text-primary');
        document.getElementById('formIcon').className = 'bi bi-plus-lg fs-5';
    }
    window.resetForm = resetForm;

    function editRecord(record) {
        document.getElementById('formMethod').value  = 'PUT';
        document.getElementById('editId').value      = record.id;
        document.getElementById('masterForm').action = updateBase.replace('__ID__', record.id);

        const submitBtn = document.getElementById('submitBtn');
        submitBtn.innerHTML = '<i class="bi bi-pencil-square me-1"></i> Update Record';
        submitBtn.classList.replace('btn-primary', 'btn-success');

        document.getElementById('cancelBtn').classList.remove('d-none');
        document.getElementById('formTitle').innerText = 'Edit Record';

        const iconBg = document.getElementById('formIconBg');
        iconBg.classList.replace('bg-primary-subtle', 'bg-success-subtle');
        iconBg.classList.replace('text-primary', 'text-success');
        document.getElementById('formIcon').className = 'bi bi-pencil-square fs-5';

        for (const [key, value] of Object.entries(record)) {
            const field = document.getElementById('input-' + key);
            if (field) { field.value = value ?? ''; }
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    window.editRecord = editRecord;

    @if($errors->any() && old('_method') === 'PUT' && old('_edit_id'))
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('formMethod').value  = 'PUT';
        document.getElementById('editId').value      = @json(old('_edit_id'));
        document.getElementById('masterForm').action = updateBase.replace('__ID__', "{{ old('_edit_id') }}");

        const submitBtn = document.getElementById('submitBtn');
        submitBtn.innerHTML = '<i class="bi bi-pencil-square me-1"></i> Update Record';
        submitBtn.classList.replace('btn-primary', 'btn-success');

        document.getElementById('cancelBtn').classList.remove('d-none');
        document.getElementById('formTitle').innerText = 'Edit Record';

        const iconBg = document.getElementById('formIconBg');
        iconBg.classList.replace('bg-primary-subtle', 'bg-success-subtle');
        iconBg.classList.replace('text-primary', 'text-success');
        document.getElementById('formIcon').className = 'bi bi-pencil-square fs-5';
    });
    @endif
</script>
@endpush
@endif
