@extends('hospital.layouts.app')
@section('title', 'OT Surgery Types Master')
@section('page-header', 'OT Surgery Types Master')

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-semibold mb-3" id="formTitle">Add OT Surgery Type</h5>
                <form id="surgeryForm" method="POST" action="{{ route('hospital.masters.ot.surgery-types.store', ['slug' => $slug]) }}">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">

                    <div class="mb-3">
                        <label class="form-label">OT Type</label>
                        <select name="ot_type_id" id="ot_type_id" class="form-select" required>
                            <option value="">Select OT Type</option>
                            @foreach($types as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Surgery Name</label>
                        <input type="text" name="surgery_name" id="surgery_name" class="form-control" value="{{ old('surgery_name') }}" required>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary w-100" type="submit" id="submitBtn">Save Surgery Type</button>
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
                            <th>OT Type</th>
                            <th>Surgery Name</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($records as $index => $record)
                            <tr>
                                <td class="ps-4">{{ $index + 1 }}</td>
                                <td>{{ $record->ot_type_name }}</td>
                                <td>{{ $record->surgery_name }}</td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light border" type="button" onclick='editRecord(@json($record))'>Edit</button>
                                    <form method="POST" class="d-inline" action="{{ route('hospital.masters.ot.surgery-types.destroy', ['slug' => $slug, 'id' => $record->id]) }}" onsubmit="return confirm('Delete this surgery type?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No OT surgery types found.</td>
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
    const storeUrl = "{{ route('hospital.masters.ot.surgery-types.store', ['slug' => $slug]) }}";
    const updateUrl = "{{ route('hospital.masters.ot.surgery-types.update', ['slug' => $slug, 'id' => '__ID__']) }}";

    function editRecord(record) {
        const form = document.getElementById('surgeryForm');
        form.action = updateUrl.replace('__ID__', record.id);
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('ot_type_id').value = record.ot_type_id ?? '';
        document.getElementById('surgery_name').value = record.surgery_name ?? '';
        document.getElementById('submitBtn').innerText = 'Update Surgery Type';
        document.getElementById('formTitle').innerText = 'Edit OT Surgery Type';
        document.getElementById('cancelBtn').classList.remove('d-none');
    }

    function resetForm() {
        const form = document.getElementById('surgeryForm');
        form.reset();
        form.action = storeUrl;
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('submitBtn').innerText = 'Save Surgery Type';
        document.getElementById('formTitle').innerText = 'Add OT Surgery Type';
        document.getElementById('cancelBtn').classList.add('d-none');
    }
</script>
@endpush
