@extends('hospital.layouts.app')
@section('title', 'OT Package Master')
@section('page-header', 'OT Package Master')

@section('content')
    <div class="ot-master-page">
        <div class="ot-master-toolbar">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('hospital.masters.index', ['slug' => $slug]) }}" class="btn btn-light ot-master-back-btn">
                    Back
                </a>
                <p class="text-muted small mb-0">Package name + room + charges — counsellor picks package; lens cost is entered separately on counselling.</p>
            </div>
            <button type="button" class="btn btn-primary ot-master-add-btn" data-bs-toggle="modal"
                data-bs-target="#packageFormModal" onclick="resetForm()">
                <i class="bi bi-plus-lg me-1"></i> Add Package
            </button>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-4 ot-master-card">
            <div class="card-body p-0">
                <div class="table-responsive ot-master-table-wrap">
                    <table class="table table-hover align-middle mb-0 ot-master-table js-datatable" style="width:100%">
                        <thead>
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Package</th>
                                <th>Room</th>
                                <th>OT</th>
                                <th>Surgeon</th>
                                <th>Nursing</th>
                                <th>Consumables</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $index => $record)
                                <tr>
                                    <td class="ps-4">{{ $index + 1 }}</td>
                                    <td>{{ $record->package_name }}</td>
                                    <td class="text-capitalize">{{ $record->room_category }}</td>
                                    <td>{{ money((float) $record->ot_charges, 2) }}</td>
                                    <td>{{ money((float) $record->surgeon_charges, 2) }}</td>
                                    <td>{{ money((float) $record->nursing_charges, 2) }}</td>
                                    <td>{{ money((float) $record->consumables_charges, 2) }}</td>
                                    <td>
                                        @if($record->is_active)
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group shadow-sm rounded-3" role="group">
                                            <button class="btn btn-light border-0 text-primary ot-master-icon-btn" type="button"
                                                onclick='editRecord(@json($record))' title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <form method="POST" class="d-inline"
                                                action="{{ route('hospital.masters.ot.packages.destroy', ['slug' => $slug, 'id' => $record->id]) }}"
                                                onsubmit="return confirm('Delete this package?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-light border-0 text-danger ot-master-icon-btn"
                                                    type="submit" title="Delete">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No packages found. Add one to enable counselling autofill.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade ot-master-modal" id="packageFormModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0">
                    <div class="modal-header border-0 pb-0">
                        <div class="d-flex align-items-center">
                            <span class="ot-master-modal-icon">
                                <i class="bi bi-plus-lg" id="formIcon"></i>
                            </span>
                            <h5 class="modal-title fw-bold mb-0" id="formTitle">Add Package</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <form id="packageForm" method="POST"
                        action="{{ route('hospital.masters.ot.packages.store', ['slug' => $slug]) }}">
                        @csrf
                        <input type="hidden" name="_method" id="formMethod" value="POST">

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Package Name</label>
                                    <input type="text" name="package_name" id="package_name"
                                        class="form-control ot-master-input" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Room Category</label>
                                    <select name="room_category" id="room_category" class="form-select ot-master-input" required>
                                        <option value="general">General</option>
                                        <option value="private">Private</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted small fw-bold text-uppercase">OT Charges</label>
                                    <input type="number" step="0.01" min="0" name="ot_charges" id="ot_charges"
                                        class="form-control ot-master-input" value="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Surgeon Charges</label>
                                    <input type="number" step="0.01" min="0" name="surgeon_charges" id="surgeon_charges"
                                        class="form-control ot-master-input" value="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Nursing Charges</label>
                                    <input type="number" step="0.01" min="0" name="nursing_charges" id="nursing_charges"
                                        class="form-control ot-master-input" value="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Consumables</label>
                                    <input type="number" step="0.01" min="0" name="consumables_charges" id="consumables_charges"
                                        class="form-control ot-master-input" value="0">
                                </div>
                                <div class="col-12">
                                    <div class="form-check ot-master-check">
                                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-0 gap-2">
                            <button class="btn btn-outline-secondary rounded-3" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary fw-semibold rounded-3" type="submit" id="submitBtn">Save Package</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const storeUrl = "{{ route('hospital.masters.ot.packages.store', ['slug' => $slug]) }}";
        const updateUrl = "{{ route('hospital.masters.ot.packages.update', ['slug' => $slug, 'id' => '__ID__']) }}";

        function editRecord(record) {
            const form = document.getElementById('packageForm');
            form.action = updateUrl.replace('__ID__', record.id);
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('package_name').value = record.package_name ?? '';
            document.getElementById('room_category').value = record.room_category ?? 'general';
            document.getElementById('ot_charges').value = record.ot_charges ?? 0;
            document.getElementById('surgeon_charges').value = record.surgeon_charges ?? 0;
            document.getElementById('nursing_charges').value = record.nursing_charges ?? 0;
            document.getElementById('consumables_charges').value = record.consumables_charges ?? 0;
            document.getElementById('is_active').checked = Boolean(Number(record.is_active));
            document.getElementById('submitBtn').innerText = 'Update Package';
            document.getElementById('formTitle').innerText = 'Edit Package';
            document.getElementById('formIcon').className = 'bi bi-pencil-square';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('packageFormModal')).show();
        }

        function resetForm() {
            const form = document.getElementById('packageForm');
            form.reset();
            form.action = storeUrl;
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('is_active').checked = true;
            document.getElementById('ot_charges').value = 0;
            document.getElementById('surgeon_charges').value = 0;
            document.getElementById('nursing_charges').value = 0;
            document.getElementById('consumables_charges').value = 0;
            document.getElementById('submitBtn').innerText = 'Save Package';
            document.getElementById('formTitle').innerText = 'Add Package';
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
        }
        .ot-master-back-btn {
            border: 1px solid var(--ot-border-strong) !important;
            border-radius: 12px;
            color: var(--ot-primary) !important;
            font-weight: 800;
            background: #fff !important;
        }
        .ot-master-card {
            border: 1px solid var(--ot-border) !important;
            border-radius: 22px !important;
            overflow: hidden;
        }
        .ot-master-table-wrap { padding: .9rem; background: var(--ot-soft); }
        .ot-master-table { border-collapse: separate; border-spacing: 0 8px; min-width: 900px; }
        .ot-master-table thead th {
            background: var(--ot-primary) !important;
            color: #fff !important;
            border: 0 !important;
            padding: .9rem 1rem !important;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .ot-master-table tbody td {
            background: #fff;
            color: var(--ot-primary);
            padding: .85rem 1rem;
            font-weight: 650;
        }
        .ot-master-icon-btn {
            width: 34px; height: 34px; border-radius: 50% !important;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .ot-master-modal .modal-content { border-radius: 22px; }
        .ot-master-modal-icon {
            width: 42px; height: 42px; border-radius: 14px;
            background: var(--ot-primary); color: #fff;
            display: inline-flex; align-items: center; justify-content: center;
            margin-right: .8rem;
        }
        .ot-master-input {
            border: 1.5px solid var(--ot-border) !important;
            border-radius: 12px;
            background: rgba(235, 245, 251, .42);
            color: var(--ot-primary);
            font-weight: 650;
        }
        .ot-master-check { color: var(--ot-primary); font-weight: 650; }
    </style>
@endpush
