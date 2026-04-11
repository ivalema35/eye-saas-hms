@extends('hospital.layouts.app')
@section('title', 'OT Charge Heads Master')
@section('page-header', 'OT Charge Heads Master')

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-semibold mb-3" id="formTitle">Add OT Charge Head</h5>
                <form id="chargeForm" method="POST" action="{{ route('hospital.masters.ot.charge-heads.store', ['slug' => $slug]) }}">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">

                    <div class="mb-3">
                        <label class="form-label">Charge Name</label>
                        <input type="text" name="charge_name" id="charge_name" class="form-control" value="{{ old('charge_name') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Percentage</label>
                        <input type="number" step="0.01" min="0" max="100" name="percentage" id="percentage" class="form-control" value="{{ old('percentage') }}" required>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary w-100" type="submit" id="submitBtn">Save Charge Head</button>
                        <button class="btn btn-light border w-100 d-none" type="button" id="cancelBtn" onclick="resetForm()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Charge Head</th>
                            <th>Percentage</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($records as $index => $record)
                            <tr>
                                <td class="ps-4">{{ $index + 1 }}</td>
                                <td>{{ $record->charge_name }}</td>
                                <td>{{ number_format((float) $record->percentage, 2) }}%</td>
                                <td>
                                    @if($record->is_active)
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light border" type="button" onclick='editRecord(@json($record))'>Edit</button>
                                    <form method="POST" class="d-inline" action="{{ route('hospital.masters.ot.charge-heads.destroy', ['slug' => $slug, 'id' => $record->id]) }}" onsubmit="return confirm('Delete this charge head?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No OT charge heads found.</td>
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

@push('scripts')
<script>
    const storeUrl = "{{ route('hospital.masters.ot.charge-heads.store', ['slug' => $slug]) }}";
    const updateUrl = "{{ route('hospital.masters.ot.charge-heads.update', ['slug' => $slug, 'id' => '__ID__']) }}";

    function editRecord(record) {
        const form = document.getElementById('chargeForm');
        form.action = updateUrl.replace('__ID__', record.id);
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('charge_name').value = record.charge_name ?? '';
        document.getElementById('percentage').value = record.percentage ?? '';
        document.getElementById('is_active').checked = Boolean(record.is_active);
        document.getElementById('submitBtn').innerText = 'Update Charge Head';
        document.getElementById('formTitle').innerText = 'Edit OT Charge Head';
        document.getElementById('cancelBtn').classList.remove('d-none');
    }

    function resetForm() {
        const form = document.getElementById('chargeForm');
        form.reset();
        form.action = storeUrl;
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('is_active').checked = true;
        document.getElementById('submitBtn').innerText = 'Save Charge Head';
        document.getElementById('formTitle').innerText = 'Add OT Charge Head';
        document.getElementById('cancelBtn').classList.add('d-none');
    }
</script>
@endpush
