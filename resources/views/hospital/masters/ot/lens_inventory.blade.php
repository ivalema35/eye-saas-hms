@extends('hospital.layouts.app')
@section('title', 'Lens Inventory')
@section('page-header', 'Lens Inventory')

@section('content')
    <div class="ot-master-page">
        <div class="ot-master-toolbar">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('hospital.masters.index', ['slug' => $slug]) }}" class="btn btn-light ot-master-back-btn">
                    Back
                </a>
                <p class="text-muted small mb-0">Stock-tracked lens units — batch, serial, expiry, cost, available stock.</p>
            </div>
            <button type="button" class="btn btn-primary ot-master-add-btn" data-bs-toggle="modal"
                data-bs-target="#lensInventoryFormModal" onclick="resetForm()">
                <i class="bi bi-plus-lg me-1"></i> Add Lens
            </button>
        </div>

        <div class="card border-0 shadow-sm rounded-4 ot-master-card">
            <div class="card-body p-0">
                <div class="table-responsive ot-master-table-wrap">
                    <table class="table table-hover align-middle mb-0 ot-master-table js-datatable" style="width:100%">
                        <thead>
                            <tr>
                                <th class="ps-4">Code</th>
                                <th>Lens Name</th>
                                <th>Manufacturer</th>
                                <th>Type / Power</th>
                                <th>Batch / Serial</th>
                                <th>Expiry</th>
                                <th>MRP</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $record)
                                <tr>
                                    <td class="ps-4">{{ $record->lens_code }}</td>
                                    <td>{{ $record->lens_name }}</td>
                                    <td>{{ $record->manufacturer ?? '-' }}</td>
                                    <td>{{ $record->type ?? '-' }} @if($record->power) / +{{ number_format((float) $record->power, 2) }}D @endif</td>
                                    <td>{{ $record->batch_number ?? '-' }} / {{ $record->serial_number ?? '-' }}</td>
                                    <td>{{ $record->expiry_date?->format('d M Y') ?? '-' }}</td>
                                    <td>{{ money_code((float) $record->mrp, 2) }}</td>
                                    <td>
                                        <span class="badge {{ $record->available_stock > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                            {{ $record->available_stock }}
                                        </span>
                                    </td>
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
                                                action="{{ route('hospital.masters.ot.lens-inventory.destroy', ['slug' => $slug, 'id' => $record->id]) }}"
                                                onsubmit="return confirm('Delete this lens inventory item?');">
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
                                    <td colspan="10" class="text-center py-4 text-muted">No lens inventory found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade ot-master-modal" id="lensInventoryFormModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0">
                    <div class="modal-header border-0 pb-0">
                        <div class="d-flex align-items-center">
                            <span class="ot-master-modal-icon" id="formIconBg">
                                <i class="bi bi-plus-lg" id="formIcon"></i>
                            </span>
                            <h5 class="modal-title fw-bold mb-0" id="formTitle">Add Lens</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <form id="lensInventoryForm" method="POST"
                        action="{{ route('hospital.masters.ot.lens-inventory.store', ['slug' => $slug]) }}">
                        @csrf
                        <input type="hidden" name="_method" id="formMethod" value="POST">

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Lens Code (SKU)</label>
                                    <input type="text" name="lens_code" id="lens_code" class="form-control ot-master-input" required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Lens Name</label>
                                    <input type="text" name="lens_name" id="lens_name" class="form-control ot-master-input" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Manufacturer</label>
                                    <input type="text" name="manufacturer" id="manufacturer" class="form-control ot-master-input">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Type</label>
                                    <input type="text" name="type" id="type" class="form-control ot-master-input" placeholder="e.g. Monofocal, Toric">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Power</label>
                                    <input type="number" step="0.01" name="power" id="power" class="form-control ot-master-input">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Batch Number</label>
                                    <input type="text" name="batch_number" id="batch_number" class="form-control ot-master-input">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Serial Number</label>
                                    <input type="text" name="serial_number" id="serial_number" class="form-control ot-master-input">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">MRP</label>
                                    <input type="number" step="0.01" min="0" name="mrp" id="mrp" class="form-control ot-master-input" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Purchase Cost</label>
                                    <input type="number" step="0.01" min="0" name="purchase_cost" id="purchase_cost" class="form-control ot-master-input">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Available Stock</label>
                                    <input type="number" min="0" name="available_stock" id="available_stock" class="form-control ot-master-input" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Supplier</label>
                                    <input type="text" name="supplier" id="supplier" class="form-control ot-master-input">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Expiry Date</label>
                                    <input type="date" name="expiry_date" id="expiry_date" class="form-control ot-master-input">
                                </div>
                            </div>

                            <div class="form-check ot-master-check mt-3">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>

                        <div class="modal-footer border-0 gap-2">
                            <button class="btn btn-outline-secondary rounded-3" type="button" id="cancelBtn"
                                data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary fw-semibold rounded-3" type="submit" id="submitBtn">Save Lens</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const storeUrl = "{{ route('hospital.masters.ot.lens-inventory.store', ['slug' => $slug]) }}";
        const updateUrl = "{{ route('hospital.masters.ot.lens-inventory.update', ['slug' => $slug, 'id' => '__ID__']) }}";

        function editRecord(record) {
            const form = document.getElementById('lensInventoryForm');
            form.action = updateUrl.replace('__ID__', record.id);
            document.getElementById('formMethod').value = 'PUT';

            document.getElementById('lens_code').value = record.lens_code ?? '';
            document.getElementById('lens_name').value = record.lens_name ?? '';
            document.getElementById('manufacturer').value = record.manufacturer ?? '';
            document.getElementById('type').value = record.type ?? '';
            document.getElementById('power').value = record.power ?? '';
            document.getElementById('batch_number').value = record.batch_number ?? '';
            document.getElementById('serial_number').value = record.serial_number ?? '';
            document.getElementById('mrp').value = record.mrp ?? '';
            document.getElementById('purchase_cost').value = record.purchase_cost ?? '';
            document.getElementById('available_stock').value = record.available_stock ?? 0;
            document.getElementById('supplier').value = record.supplier ?? '';
            document.getElementById('expiry_date').value = record.expiry_date ? record.expiry_date.substring(0, 10) : '';
            document.getElementById('is_active').checked = Boolean(record.is_active);

            document.getElementById('submitBtn').innerText = 'Update Lens';
            document.getElementById('formTitle').innerText = 'Edit Lens';
            document.getElementById('formIcon').className = 'bi bi-pencil-square';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('lensInventoryFormModal')).show();
        }

        function resetForm() {
            const form = document.getElementById('lensInventoryForm');
            form.reset();
            form.action = storeUrl;
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('is_active').checked = true;
            document.getElementById('submitBtn').innerText = 'Save Lens';
            document.getElementById('formTitle').innerText = 'Add Lens';
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
            text-decoration: none !important;
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
            overflow-x: auto;
        }

        .ot-master-table {
            border-collapse: separate;
            border-spacing: 0 8px;
            min-width: 1100px;
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

        .ot-master-check {
            color: var(--ot-primary);
            font-weight: 650;
        }
    </style>
@endpush
