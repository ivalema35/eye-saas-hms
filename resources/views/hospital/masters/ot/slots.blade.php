@extends('hospital.layouts.app')
@section('title', 'OT Slots Master')
@section('page-header', 'OT Slots Master')

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-semibold mb-3" id="formTitle">Add OT Slot</h5>
                <form id="slotForm" method="POST" action="{{ route('hospital.masters.ot.slots.store', ['slug' => $slug]) }}">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">

                    <div class="mb-3">
                        <label class="form-label">Slot Name</label>
                        <input type="text" name="slot_name" id="slot_name" class="form-control" value="{{ old('slot_name') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Start Time</label>
                        <input type="time" name="start_time" id="start_time" class="form-control" value="{{ old('start_time') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">End Time</label>
                        <input type="time" name="end_time" id="end_time" class="form-control" value="{{ old('end_time') }}">
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary w-100" type="submit" id="submitBtn">Save Slot</button>
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
                                    <button
                                        class="btn btn-sm btn-light border"
                                        type="button"
                                        onclick='editRecord(@json($record))'
                                    >Edit</button>
                                    <form method="POST" class="d-inline" action="{{ route('hospital.masters.ot.slots.destroy', ['slug' => $slug, 'id' => $record->id]) }}" onsubmit="return confirm('Delete this OT slot?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
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
        document.getElementById('cancelBtn').classList.remove('d-none');
    }

    function resetForm() {
        const form = document.getElementById('slotForm');
        form.reset();
        form.action = storeUrl;
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('submitBtn').innerText = 'Save Slot';
        document.getElementById('formTitle').innerText = 'Add OT Slot';
        document.getElementById('cancelBtn').classList.add('d-none');
    }
</script>
@endpush
