@extends('hospital.layouts.app')

@section('title', 'Add User')
@section('page-header', 'Add User')

@section('page-actions')
    <a href="{{ route('hospital.users.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')
<form method="POST" action="{{ route('hospital.users.store', ['slug' => $slug]) }}">
    @csrf

    <div class="hms-card" style="margin-bottom:1rem">
        <div class="hms-card-header">
            <h3 class="hms-card-title">
                <i class="fa-solid fa-user-gear" style="color:var(--hms-primary)"></i> User Details
            </h3>
        </div>
        <div class="hms-card-body">
            <div class="hms-form-grid-2">
                <div class="hms-form-group">
                    <label>Name <span class="hms-required">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="hms-input @error('name') is-invalid @enderror" required>
                    @error('name')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Email <span class="hms-required">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="hms-input @error('email') is-invalid @enderror" required>
                    @error('email')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Role <span class="hms-required">*</span></label>
                    <select name="role_id" id="role_id" class="hms-select @error('role_id') is-invalid @enderror" required>
                        <option value="">Select Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" data-slug="{{ $role->slug }}" @selected(old('role_id') == $role->id)>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Contact</label>
                    <input type="text" name="contact" value="{{ old('contact') }}"
                           class="hms-input @error('contact') is-invalid @enderror" maxlength="15">
                    @error('contact')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Status <span class="hms-required">*</span></label>
                    <select name="status" class="hms-select @error('status') is-invalid @enderror" required>
                        <option value="active"   @selected(old('status', 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                    </select>
                    @error('status')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group doctor-only" style="display:none">
                    <label>Doctor Type</label>
                    <select name="doctor_type" class="hms-select @error('doctor_type') is-invalid @enderror">
                        <option value="">Select Type</option>
                        <option value="primary"   @selected(old('doctor_type') === 'primary')>Primary</option>
                        <option value="secondary" @selected(old('doctor_type') === 'secondary')>Secondary</option>
                    </select>
                    @error('doctor_type')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group doctor-only" style="display:none">
                    <label>&nbsp;</label>
                    <label class="hms-checkbox-label" style="padding:.5rem 0">
                        <input class="hms-checkbox" type="checkbox" value="1" id="foc_permission"
                               name="foc_permission" @checked(old('foc_permission'))>
                        <span>Allow FOC Permission</span>
                    </label>
                </div>

                <div class="hms-form-group">
                    <label>Password <span class="hms-required">*</span></label>
                    <input type="password" name="password" class="hms-input @error('password') is-invalid @enderror" required>
                    @error('password')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Confirm Password <span class="hms-required">*</span></label>
                    <input type="password" name="password_confirmation" class="hms-input" required>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:.75rem">
        <button type="submit" class="hms-btn hms-btn-primary">
            <i class="fa-solid fa-check"></i> Save User
        </button>
        <a href="{{ route('hospital.users.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">Cancel</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function toggleDoctorFields() {
        const roleSelect = document.getElementById('role_id');
        const selected   = roleSelect.options[roleSelect.selectedIndex];
        const slug       = selected ? selected.dataset.slug : '';
        const showDoctor = slug === 'doctor';

        document.querySelectorAll('.doctor-only').forEach(function (el) {
            el.style.display = showDoctor ? '' : 'none';
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('role_id');
        roleSelect.addEventListener('change', toggleDoctorFields);
        toggleDoctorFields();
    });
</script>
@endpush


@section('page-actions')
    <a href="{{ route('hospital.users.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')
<form method="POST" action="{{ route('hospital.users.store', ['slug' => $slug]) }}">
    @csrf

    <div class="hms-card mb-3">
        <div class="hms-card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role_id" id="role_id" class="form-control @error('role_id') is-invalid @enderror" required>
                        <option value="">Select Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" data-slug="{{ $role->slug }}" @selected(old('role_id') == $role->id)>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Contact</label>
                    <input type="text" name="contact" value="{{ old('contact') }}"
                           class="form-control @error('contact') is-invalid @enderror" maxlength="15">
                    @error('contact') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                        <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6 doctor-only" style="display:none">
                    <label class="form-label">Doctor Type</label>
                    <select name="doctor_type" class="form-control @error('doctor_type') is-invalid @enderror">
                        <option value="">Select Type</option>
                        <option value="primary" @selected(old('doctor_type') === 'primary')>Primary</option>
                        <option value="secondary" @selected(old('doctor_type') === 'secondary')>Secondary</option>
                    </select>
                    @error('doctor_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6 doctor-only" style="display:none;align-self:end">
                    <div class="form-check" style="padding-bottom:.5rem">
                        <input class="form-check-input" type="checkbox" value="1" id="foc_permission" name="foc_permission" @checked(old('foc_permission'))>
                        <label class="form-check-label" for="foc_permission">Allow FOC Permission</label>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="hms-btn hms-btn-primary">
            <i class="fa-solid fa-check"></i> Save User
        </button>
        <a href="{{ route('hospital.users.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">Cancel</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function toggleDoctorFields() {
        const roleSelect = document.getElementById('role_id');
        const selected = roleSelect.options[roleSelect.selectedIndex];
        const slug = selected ? selected.dataset.slug : '';
        const showDoctor = slug === 'doctor';

        document.querySelectorAll('.doctor-only').forEach(function (element) {
            element.style.display = showDoctor ? '' : 'none';
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('role_id');
        roleSelect.addEventListener('change', toggleDoctorFields);
        toggleDoctorFields();
    });
</script>
@endpush
