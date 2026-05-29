@extends('hospital.layouts.app')

@section('title', 'Edit User')
@section('page-header', 'Edit User')

@section('page-actions')
    <a href="{{ route('hospital.users.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')
<form method="POST" action="{{ route('hospital.users.update', ['slug' => $slug, 'id' => $user->id]) }}">
    @csrf
    @method('PUT')

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
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                           class="hms-input @error('name') is-invalid @enderror" required>
                    @error('name')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Email <span class="hms-required">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                           class="hms-input @error('email') is-invalid @enderror" required>
                    @error('email')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Role <span class="hms-required">*</span></label>
                    <select name="role_id" id="role_id" class="hms-select @error('role_id') is-invalid @enderror" required>
                        <option value="">Select Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" data-slug="{{ $role->slug }}"
                                @selected(old('role_id', $user->role_id) == $role->id)>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Contact</label>
                    <input type="text" name="contact" value="{{ old('contact', $user->contact) }}"
                           class="hms-input @error('contact') is-invalid @enderror" maxlength="15">
                    @error('contact')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Status <span class="hms-required">*</span></label>
                    <select name="status" class="hms-select @error('status') is-invalid @enderror" required>
                        <option value="active"   @selected(old('status', $user->status) === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $user->status) === 'inactive')>Inactive</option>
                    </select>
                    @error('status')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group doctor-only" style="display:none">
                    <label>Doctor Type</label>
                    <select name="doctor_type" class="hms-select @error('doctor_type') is-invalid @enderror">
                        <option value="">Select Type</option>
                        <option value="primary"   @selected(old('doctor_type', $user->doctor_type) === 'primary')>Primary</option>
                        <option value="secondary" @selected(old('doctor_type', $user->doctor_type) === 'secondary')>Secondary</option>
                    </select>
                    @error('doctor_type')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>
<!-- 
                <div class="hms-form-group doctor-only" style="display:none">
                    <label>&nbsp;</label>
                    <label class="hms-checkbox-label" style="padding:.5rem 0">
                        <input class="hms-checkbox" type="checkbox" value="1" id="foc_permission"
                               name="foc_permission" @checked(old('foc_permission', $user->foc_permission))>
                        <span>Allow FOC Permission</span>
                    </label>
                </div> -->

                <div class="hms-form-group">
                    <label>New Password <span style="color:var(--hms-text-muted);font-size:.8rem">(leave blank to keep current)</span></label>
                    <input type="password" name="password" class="hms-input @error('password') is-invalid @enderror">
                    @error('password')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="password_confirmation" class="hms-input">
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:.75rem">
        <button type="submit" class="hms-btn hms-btn-primary">
            <i class="fa-solid fa-floppy-disk"></i> Update User
        </button>
        <a href="{{ route('hospital.users.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">Cancel</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
    const doctorSlugs = ['doctor'];

    function toggleDoctorFields() {
        const roleSelect = document.getElementById('role_id');
        const selected   = roleSelect.options[roleSelect.selectedIndex];
        const slug       = selected ? selected.dataset.slug : '';
        const showDoctor = doctorSlugs.includes(slug);

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
