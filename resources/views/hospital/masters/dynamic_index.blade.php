@extends('hospital.layouts.app')

@php
    $modalMasterTypes = ['cases', 'locations', 'referrers', 'durations', 'chief-complaints', 'kcos', 'hno', 'diagnosis', 'advice', 'vn', 'vngl', 'vnst', 'pnvn', 'nrvn', 'sph_cyl', 'axis', 'nct', 'sac', 'lid', 'conj', 'cornea', 'ac', 'iris', 'pupil', 'lens', 'em', 'covertest', 'disc', 'fr'];
    $useModalLayout = in_array($type, $modalMasterTypes, true);
@endphp

@section('title', 'Manage ' . $title)
@if(!$useModalLayout)
    @section('page-header', $title)
@endif
{{-- For $useModalLayout types the heading/breadcrumb sit inside the content
     card instead (case-master-outer-card), matching the Medicine Master /
     Users / Roles / History panel design. --}}

@section('content')

    @php
        $permService = app(\App\Services\Auth\RolePermissionService::class);
        $isSuper = auth('hospital_user')->user()?->role?->is_super;
        $canWrite = $isSuper || (str_contains($routeGroup, '.detail.')
            ? $permService->can('master.eye_exam')
            : $permService->canAny(['master.case_types', 'master.locations']));
        $showBackButton = $useModalLayout;
        $isAdviceType = in_array($type, ['advice', 'advices'], true);
        $showDiagnosis = $isAdviceType && isset($diagnoses) && $diagnoses->isNotEmpty();
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
        @if($useModalLayout)
        <div class="case-master-outer-card">
            <div class="case-master-header-block">
                <div class="case-master-header-title"><i class="bi bi-collection"></i> {{ $title }}</div>
                <nav class="case-master-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                    <span class="case-master-breadcrumb-sep">/</span>
                    <span class="case-master-breadcrumb-current">{{ $title }}</span>
                </nav>
            </div>
        @endif
        <div class="case-master-toolbar">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if($showBackButton)
                    @php
                        $backUrl = match (auth('hospital_user')->user()?->role?->slug) {
                            'doctor' => route('hospital.dashboard', ['slug' => $slug]),
                            'receptionist' => route('hospital.profile.show', ['slug' => $slug]),
                            default => route('hospital.masters.index', ['slug' => $slug]),
                        };
                    @endphp
                    <a href="{{ $backUrl }}" class="btn btn-light case-master-back-btn">
                        Back
                    </a>
                @endif
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if($canWrite && $showDiagnosis)
                    <button type="button" class="btn btn-outline-secondary case-master-diagnosis-btn"
                        style="border-radius:12px;font-weight:700;border-color:rgba(27,79,114,.3);color:#1B4F72;"
                        data-bs-toggle="modal" data-bs-target="#linkByDiagnosisModal">
                        <i class="bi bi-diagram-3 me-1"></i> Link by Diagnosis
                    </button>
                @endif
                @if($canWrite && $useModalLayout)
                    <button type="button" class="btn btn-primary case-master-add-btn" data-bs-toggle="modal"
                        data-bs-target="#masterFormModal" onclick="resetForm()">
                        <i class="bi bi-plus-lg me-1"></i> Add {{ Str::singular($title) }}
                    </button>
                @endif
            </div>
        </div>

        <div class="row g-4">
            @if($canWrite && !$useModalLayout)
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
                                action="{{ route($routeGroup . '.store', ['slug' => $slug, 'type' => $type]) }}" method="POST">
                                @csrf
                                <input type="hidden" name="_method" id="formMethod" value="POST">
                                <input type="hidden" name="_edit_id" id="editId" value="{{ old('_edit_id') }}">

                                @foreach($columns as $col)
                                    @php
                                        $isOptionalField = $type === 'locations' && $col === 'district';
                                        $isContactField = $col === 'contact';
                                    @endphp
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold text-uppercase"
                                            style="letter-spacing: 0.5px;">
                                            {{ Str::headline($col) }}
                                            @if($isOptionalField)
                                                <span class="text-muted fw-normal">(optional)</span>
                                            @else
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>
                                        <input type="text" name="{{ $col }}" id="input-{{ $col }}"
                                            class="form-control form-control-lg bg-light border-0 {{ $isContactField ? 'contact-input' : '' }}"
                                            style="font-size: 15px;"
                                            placeholder="Enter {{ strtolower(Str::headline($col)) }}..." value="{{ old($col) }}"
                                            @if($isContactField) data-intl-phone title="7–15 digits, optional leading +" @endif
                                            @if(!$isOptionalField) required @endif>
                                    </div>
                                @endforeach

                                <div class="mt-4 pt-2">
                                    <button type="submit" id="submitBtn"
                                        class="btn btn-primary w-100 py-2 fw-semibold rounded-3 mb-2">
                                        Save Record
                                    </button>
                                    <button type="button" id="cancelBtn"
                                        class="btn btn-light w-100 py-2 fw-medium rounded-3 d-none text-muted"
                                        onclick="resetForm()">
                                        Cancel Edit
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <div class="{{ $canWrite && !$useModalLayout ? 'col-lg-8' : 'col-12' }}">
                <div class="card border-0 shadow-sm rounded-4 {{ $useModalLayout ? 'case-master-card' : '' }}">
                    <div class="card-body p-0">
                        <div class="table-responsive {{ $useModalLayout ? 'case-master-table-wrap' : '' }}">
                            <table
                                class="table table-hover align-middle mb-0 js-datatable {{ $useModalLayout ? 'case-master-table' : '' }}"
                                style="width:100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="8%" class="text-center py-3 text-muted small fw-bold font-monospace">#
                                        </th>
                                        @if($type == 'locations')
                                            <th>CITY</th>
                                            <th>DISTRICT</th>
                                            <th>STATE</th>

                                        @else

                                            @foreach($columns as $col)
                                                <th>{{ Str::headline($col) }}</th>
                                            @endforeach

                                        @endif
                                        @if($showDiagnosis)
                                            <th class="py-3 text-muted text-uppercase small fw-bold"
                                                style="letter-spacing: 0.5px;">
                                                Diagnosis
                                            </th>
                                        @endif
                                        @if(in_array($type, ['complaints', 'chief-complaints', 'kcos', 'hno', 'sac', 'lid', 'conj', 'cornea', 'ac', 'iris', 'pupil', 'lens', 'em', 'covertest', 'disc', 'fr', 'diagnosis', 'diagnoses', 'advice', 'advices']))
                                            <th class="py-3 text-muted text-uppercase small fw-bold text-center"
                                                style="letter-spacing:0.5px;width:90px;">
                                                Favourite
                                            </th>
                                        @endif
                                        @if($canWrite)
                                            <th width="15%" class="text-end pe-4 py-3 text-muted text-uppercase small fw-bold">
                                                Actions</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($records as $index => $record)
                                        <tr>
                                            <td class="text-center text-muted small font-monospace">{{ $index + 1 }}</td>
                                            @if($type == 'locations')

                                                @php
                                                    $districtValue = $record->district;
                                                    $stateValue = $record->state;
                                                    $usesRelations = is_object($districtValue) || is_object($stateValue);
                                                @endphp

                                                <td>{{ $usesRelations ? ($record->name ?? $record->city) : $record->city }}</td>
                                                <td>{{ $usesRelations ? ($districtValue?->name ?? '') : $record->district }}</td>
                                                <td>{{ $usesRelations ? ($stateValue?->name ?? '') : $record->state }}</td>

                                            @else

                                                @foreach($columns as $col)
                                                    <td>{{ $record->$col }}</td>
                                                @endforeach

                                            @endif
                                            @if($showDiagnosis)
                                                <td class="py-3">
                                                    @if(collect($record->diagnoses)->isNotEmpty())
                                                        <div class="d-flex flex-wrap gap-1">
                                                            @foreach($record->diagnoses as $diag)
                                                                <span
                                                                    style="font-size:.78rem;font-weight:700;color:#0d6949;background:rgba(13,105,73,.08);border:1px solid rgba(13,105,73,.2);border-radius:999px;padding:.2rem .6rem;">
                                                                    <i class="bi bi-clipboard2-pulse me-1"></i>{{ $diag->value }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>
                                            @endif
                                            @if(in_array($type, ['complaints', 'chief-complaints', 'kcos', 'hno', 'sac', 'lid', 'conj', 'cornea', 'ac', 'iris', 'pupil', 'lens', 'em', 'covertest', 'disc', 'fr', 'diagnosis', 'diagnoses', 'advice', 'advices']))
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
                                                    @if(!isset($record->getCasts()['is_seeded']) || !$record->is_seeded)
                                                        <div class="btn-group shadow-sm rounded-3" role="group">
                                                            <button type="button"
                                                                class="btn btn-light border-0 text-primary {{ $useModalLayout ? 'case-master-icon-btn' : '' }}"
                                                                onclick="editRecord(@js($record))" title="Edit">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>
                                                            <form
                                                                action="{{ route($routeGroup . '.destroy', ['slug' => $slug, 'type' => $type, 'id' => $record->id]) }}"
                                                                method="POST" class="d-inline"
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
                                                    @else
                                                        <span class="text-muted small px-2">—</span>
                                                    @endif
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
        @if($useModalLayout)
        </div>{{-- /.case-master-outer-card --}}
        @endif

        {{-- Add / Edit modal --}}
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

                        <form id="masterForm" action="{{ route($routeGroup . '.store', ['slug' => $slug, 'type' => $type]) }}"
                            method="POST">
                            @csrf
                            <input type="hidden" name="_method" id="formMethod" value="POST">
                            <input type="hidden" name="_edit_id" id="editId" value="{{ old('_edit_id') }}">

                            <div class="modal-body">
                                @foreach($columns as $col)
                                    @php
                                        $isOptionalField = $type === 'locations' && $col === 'district';
                                        $isContactField = $col === 'contact';
                                    @endphp
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold text-uppercase"
                                            style="letter-spacing: 0.5px;">
                                            {{ Str::headline($col) }}
                                            @if($isOptionalField)
                                                <span class="text-muted fw-normal">(optional)</span>
                                            @else
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>
                                        <input type="text" name="{{ $col }}" id="input-{{ $col }}"
                                            class="form-control form-control-lg case-master-input {{ $isContactField ? 'contact-input' : '' }}"
                                            style="font-size: 15px;"
                                            placeholder="Enter {{ strtolower(Str::headline($col)) }}..." value="{{ old($col) }}"
                                            @if($isContactField) data-intl-phone title="7–15 digits, optional leading +" @endif
                                            @if(!$isOptionalField) required @endif>
                                    </div>
                                @endforeach

                                @if($showDiagnosis)
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold text-uppercase"
                                            style="letter-spacing: 0.5px;">
                                            <i class="bi bi-clipboard2-pulse me-1"></i> Diagnosis
                                        </label>
                                        <select name="diagnosis_ids[]" id="input-diagnosis_ids"
                                            class="form-select case-master-input" multiple style="font-size: 15px;">
                                            @foreach($diagnoses as $diag)
                                                <option value="{{ $diag->id }}">{{ $diag->value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                            </div>

                            <div class="modal-footer border-0 gap-2">
                                <button type="button" id="cancelBtn" class="btn btn-outline-secondary rounded-3"
                                    data-bs-dismiss="modal">
                                    Cancel
                                </button>
                                <button type="submit" id="submitBtn" class="btn btn-primary fw-semibold rounded-3">
                                    Save Record
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Link by Diagnosis modal --}}
        @if($canWrite && $showDiagnosis)
            @php
                $adviceDiagMap = $records->mapWithKeys(fn($r) => [
                    $r->id => collect($r->diagnoses)->pluck('id')->all()
                ])->all();
            @endphp
            <div class="modal fade" id="linkByDiagnosisModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0"
                        style="border-radius:22px;box-shadow:0 20px 50px rgba(27,79,114,.15);overflow:hidden;">

                        <div class="modal-header border-0 pb-0"
                            style="background:linear-gradient(135deg,rgba(235,245,251,.96),rgba(255,255,255,.94));border-bottom:1px solid rgba(27,79,114,.12)!important;padding:1.25rem 1.5rem!important;">
                            <div class="d-flex align-items-center">
                                <span
                                    style="width:42px;height:42px;border-radius:14px;background:#1B4F72;color:#fff;display:inline-flex;align-items:center;justify-content:center;margin-right:.8rem;">
                                    <i class="bi bi-diagram-3"></i>
                                </span>
                                <div>
                                    <h5 class="modal-title fw-bold mb-0" style="color:#1B4F72;">Link Advices to Diagnosis</h5>
                                    <p class="text-muted small mb-0" style="font-size:.78rem;">Select a diagnosis, then check
                                        which advices belong to it</p>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <form id="linkByDiagnosisForm"
                            action="{{ route('hospital.masters.detail.sync-by-diagnosis', ['slug' => $slug, 'type' => $type]) }}"
                            method="POST">
                            @csrf

                            <div class="modal-body p-4">

                                {{-- Diagnosis Dropdown --}}
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-uppercase small text-muted"
                                        style="letter-spacing:.05em;">
                                        <i class="bi bi-clipboard2-pulse me-1"></i> Select Diagnosis <span
                                            class="text-danger">*</span>
                                    </label>
                                    <select name="link_diagnosis_id" id="linkDiagSelect" class="form-select"
                                        style="border:1.5px solid rgba(27,79,114,.2);border-radius:12px;font-weight:650;color:#1B4F72;">
                                        <option value="">— Choose a diagnosis —</option>
                                        @foreach($diagnoses as $diag)
                                            <option value="{{ $diag->id }}">{{ $diag->value }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Advice Checklist (shown after diagnosis is selected) --}}
                                <div id="adviceChecklistWrap" style="display:none;">
                                    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                        <label class="fw-bold text-uppercase small text-muted" style="letter-spacing:.05em;">
                                            <i class="bi bi-list-check me-1"></i> Select Advices
                                            <span id="selectedAdviceCount" class="badge ms-1"
                                                style="background:#1B4F72;font-size:.7rem;">0</span>
                                        </label>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-3"
                                                style="font-size:.78rem;" onclick="linkDiagSelectAll(true)">
                                                <i class="bi bi-check2-all me-1"></i>Select All
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-3"
                                                style="font-size:.78rem;" onclick="linkDiagSelectAll(false)">
                                                <i class="bi bi-x me-1"></i>Clear All
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Search --}}
                                    <div class="mb-3 position-relative">
                                        <i class="bi bi-search position-absolute"
                                            style="top:50%;transform:translateY(-50%);left:12px;color:#94a3b8;"></i>
                                        <input type="text" id="adviceCheckSearch" class="form-control"
                                            placeholder="Search advice..."
                                            style="padding-left:34px;border:1.5px solid rgba(27,79,114,.18);border-radius:10px;font-size:13px;">
                                    </div>

                                    {{-- Checklist --}}
                                    <div id="adviceChecklist"
                                        style="max-height:320px;overflow-y:auto;border:1.5px solid rgba(27,79,114,.12);border-radius:14px;padding:.5rem .75rem;background:rgba(235,245,251,.3);">
                                        @foreach($records as $advice)
                                            <div class="advice-check-item d-flex align-items-center gap-2 py-2"
                                                style="border-bottom:1px solid rgba(27,79,114,.06);"
                                                data-label="{{ strtolower($advice->value) }}">
                                                <input type="checkbox" class="form-check-input advice-link-cb mt-0"
                                                    name="link_advice_ids[]" id="lnk_adv_{{ $advice->id }}"
                                                    value="{{ $advice->id }}"
                                                    style="width:18px;height:18px;border-radius:6px;cursor:pointer;flex-shrink:0;accent-color:#1B4F72;">
                                                <label class="form-check-label fw-medium mb-0" for="lnk_adv_{{ $advice->id }}"
                                                    style="cursor:pointer;font-size:13.5px;color:#1B4F72;">
                                                    {{ $advice->value }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Placeholder when no diagnosis selected --}}
                                <div id="adviceChecklistPlaceholder" class="text-center py-4 text-muted">
                                    <i class="bi bi-arrow-up-circle" style="font-size:2.5rem;opacity:.3;"></i>
                                    <p class="mt-2 small mb-0">Select a diagnosis above to see linked advices</p>
                                </div>

                            </div>

                            <div class="modal-footer border-0 gap-2" style="background:#f9fafb;">
                                <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">
                                    <i class="bi bi-x me-1"></i> Cancel
                                </button>
                                <button type="submit" id="linkDiagSubmitBtn" class="btn btn-primary fw-semibold rounded-3"
                                    disabled>
                                    <i class="bi bi-check2 me-1"></i> Save Links
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
            const storeUrl = "{{ route($routeGroup . '.store', ['slug' => $slug, 'type' => $type]) }}";
            const updateBase = "{{ route($routeGroup . '.update', ['slug' => $slug, 'type' => $type, 'id' => '__ID__']) }}";

            // Init Select2 on diagnosis multi-select
            $(function () {
                const $diagSel = $('#input-diagnosis_ids');
                if ($diagSel.length) {
                    $diagSel.select2({
                        dropdownParent: $('#masterFormModal'),
                        placeholder: '— Select diagnoses (optional) —',
                        allowClear: true,
                        width: '100%',
                    });
                }
            });

            // Contact fields (e.g. Referrer contact): international phone input.
            document.addEventListener('input', function (e) {
                if (!e.target.classList.contains('contact-input')) return;
                if (window.HmsIntlPhone) {
                    e.target.value = HmsIntlPhone.sanitize(e.target.value);
                }
            });
            if (window.HmsIntlPhone) {
                document.querySelectorAll('.contact-input').forEach(function (el) {
                    el.setAttribute('data-intl-phone', '');
                    HmsIntlPhone.bind(el);
                });
            }

            function resetForm() {
                const form = document.getElementById('masterForm');
                form.reset();
                document.getElementById('editId').value = '';
                document.getElementById('formMethod').value = 'POST';
                form.action = storeUrl;

                const submitBtn = document.getElementById('submitBtn');
                submitBtn.innerHTML = 'Save Record';
                submitBtn.classList.replace('btn-success', 'btn-primary');

                document.getElementById('cancelBtn')?.classList.add('d-none');
                document.getElementById('formTitle').innerText = 'Add New Record';

                const iconBg = document.getElementById('formIconBg');
                iconBg?.classList.replace('bg-success-subtle', 'bg-primary-subtle');
                iconBg?.classList.replace('text-success', 'text-primary');
                document.getElementById('formIcon').className = 'bi bi-plus-lg{{ $useModalLayout ? '' : ' fs-5' }}';

                // Clear diagnosis multi-select
                const $diagSel = $('#input-diagnosis_ids');
                if ($diagSel.length) { $diagSel.val(null).trigger('change'); }
            }
            window.resetForm = resetForm;

            function editRecord(record) {
                document.getElementById('formMethod').value = 'PUT';
                document.getElementById('editId').value = record.id;
                document.getElementById('masterForm').action = updateBase.replace('__ID__', record.id);

                const submitBtn = document.getElementById('submitBtn');
                submitBtn.innerHTML = 'Update Record';
                submitBtn.classList.replace('btn-primary', 'btn-success');

                document.getElementById('cancelBtn')?.classList.remove('d-none');
                document.getElementById('formTitle').innerText = 'Edit Record';

                const iconBg = document.getElementById('formIconBg');
                iconBg?.classList.replace('bg-primary-subtle', 'bg-success-subtle');
                iconBg?.classList.replace('text-primary', 'text-success');
                document.getElementById('formIcon').className = 'bi bi-pencil-square{{ $useModalLayout ? '' : ' fs-5' }}';

                for (const [key, value] of Object.entries(record)) {
                    const field = document.getElementById('input-' + key);
                    if (field && typeof value !== 'object') { field.value = value ?? ''; }
                }

                @if($type === 'locations')
                    // Location records come from MasterCity (name/state{obj}/district{obj})
                    const cityFld = document.getElementById('input-city');
                    const stateFld = document.getElementById('input-state');
                    const distFld = document.getElementById('input-district');
                    if (cityFld) cityFld.value = record.name ?? '';
                    if (stateFld) stateFld.value = (record.state && typeof record.state === 'object') ? (record.state.name ?? '') : (record.state ?? '');
                    if (distFld) distFld.value = (record.district && typeof record.district === 'object') ? (record.district.name ?? '') : (record.district ?? '');
                @endif

                                                                                                // Diagnosis multi-select (advice type)
                                                                                                const $diagSel = $('#input-diagnosis_ids');
                if ($diagSel.length) {
                    const ids = (record.diagnoses || []).map(d => String(d.id));
                    $diagSel.val(ids).trigger('change');
                }

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

                const icon = btn.querySelector('i');
                const current = btn.dataset.state === '1';
                const newState = !current;

                btn.dataset.state = newState ? '1' : '0';
                icon.className = 'bi ' + (newState ? 'bi-heart-fill' : 'bi-heart');
                icon.style.color = newState ? '#e11d48' : '#cbd5e1';
                btn.title = newState ? 'Remove favourite' : 'Mark as favourite';
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
                    icon.className = 'bi ' + (confirmed ? 'bi-heart-fill' : 'bi-heart');
                    icon.style.color = confirmed ? '#e11d48' : '#cbd5e1';
                    btn.title = confirmed ? 'Remove favourite' : 'Mark as favourite';
                }).catch(() => {
                    btn.dataset.state = current ? '1' : '0';
                    icon.className = 'bi ' + (current ? 'bi-heart-fill' : 'bi-heart');
                    icon.style.color = current ? '#e11d48' : '#cbd5e1';
                });
            });

            @if($errors->any() && old('_method') === 'PUT' && old('_edit_id'))
                document.addEventListener('DOMContentLoaded', function () {
                    document.getElementById('formMethod').value = 'PUT';
                    document.getElementById('editId').value = @json(old('_edit_id'));
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

            @if($showDiagnosis)
                // ── Link-by-Diagnosis modal ───────────────────────────────────────────
                (function () {
                    const adviceDiagMap = @json($adviceDiagMap ?? []);

                    const diagSelect = document.getElementById('linkDiagSelect');
                    const checklist = document.getElementById('adviceChecklist');
                    const checklistWrap = document.getElementById('adviceChecklistWrap');
                    const placeholder = document.getElementById('adviceChecklistPlaceholder');
                    const submitBtn = document.getElementById('linkDiagSubmitBtn');
                    const countBadge = document.getElementById('selectedAdviceCount');
                    const searchInput = document.getElementById('adviceCheckSearch');

                    function updateCount() {
                        const n = checklist ? checklist.querySelectorAll('.advice-link-cb:checked').length : 0;
                        if (countBadge) countBadge.textContent = n;
                    }

                    function loadDiagnosis(diagId) {
                        if (!diagId) {
                            if (checklistWrap) checklistWrap.style.display = 'none';
                            if (placeholder) placeholder.style.display = '';
                            if (submitBtn) submitBtn.disabled = true;
                            return;
                        }
                        diagId = +diagId;
                        checklist.querySelectorAll('.advice-link-cb').forEach(function (cb) {
                            const linkedDiags = adviceDiagMap[+cb.value] || [];
                            cb.checked = linkedDiags.includes(diagId);
                        });
                        if (searchInput) searchInput.value = '';
                        checklist.querySelectorAll('.advice-check-item').forEach(function (item) {
                            item.style.display = '';
                        });
                        if (checklistWrap) checklistWrap.style.display = '';
                        if (placeholder) placeholder.style.display = 'none';
                        if (submitBtn) submitBtn.disabled = false;
                        updateCount();
                    }

                    if (diagSelect) {
                        $(diagSelect).select2({
                            dropdownParent: $('#linkByDiagnosisModal'),
                            placeholder: '— Choose a diagnosis —',
                            allowClear: true,
                            width: '100%',
                        }).on('change', function () {
                            loadDiagnosis(this.value);
                        });
                    }

                    if (checklist) checklist.addEventListener('change', updateCount);

                    if (searchInput) {
                        searchInput.addEventListener('input', function () {
                            const q = this.value.toLowerCase().trim();
                            checklist.querySelectorAll('.advice-check-item').forEach(function (item) {
                                item.style.display = (!q || (item.dataset.label || '').includes(q)) ? '' : 'none';
                            });
                        });
                    }

                    document.getElementById('linkByDiagnosisModal')?.addEventListener('show.bs.modal', function () {
                        $(diagSelect).val(null).trigger('change');
                        if (searchInput) searchInput.value = '';
                    });

                    window.linkDiagSelectAll = function (state) {
                        if (!checklist) return;
                        checklist.querySelectorAll('.advice-link-cb').forEach(function (cb) {
                            const item = cb.closest('.advice-check-item');
                            if (!item || item.style.display !== 'none') cb.checked = state;
                        });
                        updateCount();
                    };
                })();
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

            .case-master-outer-card {
                background: #ffffff;
                border: 1px solid rgba(15, 79, 134, 0.12);
                border-radius: 16px;
                box-shadow: 0 12px 32px rgba(15, 79, 134, 0.08);
                overflow: hidden;
                padding: 1.25rem 1.5rem 1.5rem;
                margin-bottom: 1.5rem;
            }

            .case-master-header-block {
                padding: 0 0 1rem;
            }

            .case-master-header-title {
                font-weight: 800;
                font-size: 1.3rem;
                color: var(--case-primary);
                letter-spacing: -.015em;
                display: flex;
                align-items: center;
                gap: .55rem;
            }

            .case-master-header-title i {
                color: var(--case-primary);
                font-size: 1.2rem;
            }

            .case-master-breadcrumb {
                margin-top: .4rem;
                display: flex;
                align-items: center;
                gap: .4rem;
                font-size: .85rem;
                color: #8891a0;
            }

            .case-master-breadcrumb a {
                color: #8891a0;
                text-decoration: none;
            }

            .case-master-breadcrumb a:hover {
                color: var(--case-primary);
            }

            .case-master-breadcrumb-sep {
                color: #c3c9d3;
            }

            .case-master-breadcrumb-current {
                color: #4a5568;
                font-weight: 600;
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

            .case-master-diagnosis-btn {
                border-radius: 12px;
                font-weight: 700;
                border-color: rgba(27, 79, 114, .3) !important;
                color: #1B4F72 !important;
                background: #fff !important;
                transition: background-color 160ms ease, border-color 160ms ease, color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
            }

            .case-master-diagnosis-btn:hover,
            .case-master-diagnosis-btn:focus {
                background: #e0f0ff !important;
                border-color: #1B4F72 !important;
                color: #1B4F72 !important;
                box-shadow: 0 10px 22px rgba(27, 79, 114, .10);
                transform: translateY(-1px);
                text-decoration: none !important;
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
                text-decoration: none !important;
            }

            .case-master-card {
                border: 1px solid rgba(15, 79, 134, 0.08) !important;
                border-radius: 16px !important;
                box-shadow: 0 8px 24px rgba(15, 79, 134, 0.05) !important;
                overflow: hidden;
            }

            .case-master-table-wrap {
                overflow-x: auto;
            }

            .case-master-table {
                border-collapse: collapse;
                width: 100%;
                min-width: 720px;
            }

            .case-master-table thead th {
                background: #F8FAFC !important;
                color: #4A5568 !important;
                border: 0 !important;
                border-bottom: 1px solid #E2E8F0 !important;
                padding: .7rem 1rem !important;
                font-size: .75rem;
                letter-spacing: .05em;
                font-weight: 700;
                text-transform: uppercase;
                text-align: left;
            }

            .case-master-table tbody tr {
                transition: background 150ms ease;
            }

            .case-master-table tbody tr:hover {
                background: #F5F8FC;
            }

            .case-master-table tbody td {
                background: transparent;
                border: 0;
                border-bottom: 1px solid rgba(27, 79, 114, .08);
                color: var(--case-primary);
                padding: .75rem 1rem;
                vertical-align: middle;
                font-weight: 600;
            }

            .case-master-table tbody tr:last-child td {
                border-bottom: 0;
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