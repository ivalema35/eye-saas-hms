@extends('hospital.layouts.app')

@section('title', 'Edit User')
@section('page-header', 'Edit User')

@section('page-actions')
    <a href="{{ route('hospital.users.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')
<form method="POST" action="{{ route('hospital.users.update', ['slug' => $slug, 'id' => $user->id]) }}" enctype="multipart/form-data">
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
                    <label>Role <span class="hms-required">*</span></label>
                    <select name="role_id" id="role_id" class="hms-select @error('role_id') is-invalid @enderror" required>
                        <option value="">Select Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}"
                                data-slug="{{ $role->slug }}"
                                data-doctor-fields="{{ !empty($role->shows_doctor_fields) ? '1' : '0' }}"
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
                           class="hms-input @error('contact') is-invalid @enderror" data-intl-phone
                           placeholder="+919876543210">
                    @error('contact')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Email <span class="hms-required">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                           class="hms-input @error('email') is-invalid @enderror" required>
                    @error('email')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Password <span class="hms-required">*</span></label>
                    <input type="text" name="password" class="hms-input @error('password') is-invalid @enderror"
                           value="{{ old('password', $user->original_password) }}" autocomplete="new-password">
                    @error('password')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Confirm Password <span class="hms-required">*</span></label>
                    <input type="text" name="password_confirmation" class="hms-input"
                           value="{{ old('password_confirmation', $user->original_password) }}" autocomplete="new-password">
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
                    <label>Doctor Prefix <span style="font-size:.75rem;color:#64748B;font-weight:400">(2–5 letters, e.g. JP)</span></label>
                    <input type="text" name="doctor_prefix"
                           value="{{ strtoupper(old('doctor_prefix', $user->doctor_prefix ?? '')) }}"
                           maxlength="5" placeholder="e.g. JP"
                           style="text-transform:uppercase"
                           class="hms-input @error('doctor_prefix') is-invalid @enderror">
                    <div style="font-size:.73rem;color:#94A3B8;margin-top:.25rem">Daily serial format: <strong>JP-001, JP-002…</strong></div>
                    @error('doctor_prefix')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group doctor-only" style="display:none">
                    <label>Registration No.</label>
                    <input type="text" name="registration_no"
                           value="{{ old('registration_no', $user->registration_no) }}"
                           class="hms-input @error('registration_no') is-invalid @enderror"
                           placeholder="e.g. MCI-12345">
                    @error('registration_no')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group doctor-only" style="display:none">
                    <label>Experience <span style="font-size:.75rem;color:#64748B;font-weight:400">(years)</span></label>
                    <input type="number" name="experience_years"
                           value="{{ old('experience_years', $user->experience_years) }}"
                           min="0" max="60"
                           class="hms-input @error('experience_years') is-invalid @enderror"
                           placeholder="e.g. 5">
                    @error('experience_years')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group doctor-only" style="display:none">
                    <label>Signature <span style="font-size:.75rem;color:#64748B;font-weight:400">(JPG/PNG · max 20KB)</span></label>
                    <input type="file" name="signature" accept="image/jpg,image/jpeg,image/png"
                           class="hms-input @error('signature') is-invalid @enderror">
                    @if($user->signature_path)
                        <div style="margin-top:.4rem">
                            <span style="font-size:.72rem;color:#64748B;">Current: </span>
                            <img src="{{ asset('storage/'.$user->signature_path) }}" alt="Signature"
                                 style="max-height:44px;border:1px solid rgba(27,79,114,.15);border-radius:8px;padding:3px;">
                        </div>
                    @endif
                    @error('signature')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group doctor-only" style="display:none">
                    <label>Profile Photo <span style="font-size:.75rem;color:#64748B;font-weight:400">(JPG/PNG · max 20KB)</span></label>
                    <input type="file" name="profile_photo" accept="image/jpg,image/jpeg,image/png"
                           class="hms-input @error('profile_photo') is-invalid @enderror">
                    @if($user->profile_photo_path)
                        <div style="margin-top:.4rem">
                            <span style="font-size:.72rem;color:#64748B;">Current: </span>
                            <img src="{{ asset('storage/'.$user->profile_photo_path) }}" alt="Photo"
                                 style="max-height:55px;border:1px solid rgba(27,79,114,.15);border-radius:8px;padding:3px;">
                        </div>
                    @endif
                    @error('profile_photo')<div class="hms-field-error">{{ $message }}</div>@enderror
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
    function roleShowsDoctorFields(roleSelect) {
        if (!roleSelect || roleSelect.selectedIndex < 0) {
            return false;
        }
        const selected = roleSelect.options[roleSelect.selectedIndex];
        if (!selected || !selected.value) {
            return false;
        }
        if (selected.dataset.doctorFields === '1') {
            return true;
        }
        const slug = (selected.dataset.slug || '').toLowerCase();
        const label = (selected.textContent || '').toLowerCase().trim();
        return slug === 'doctor'
            || slug === 'ot_doctor'
            || slug.indexOf('doctor') !== -1
            || label.indexOf('doctor') !== -1;
    }

    function toggleDoctorFields() {
        const roleSelect = document.getElementById('role_id');
        const showDoctor = roleShowsDoctorFields(roleSelect);

        document.querySelectorAll('.doctor-only').forEach(function (el) {
            el.style.display = showDoctor ? '' : 'none';
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('role_id');
        if (roleSelect) {
            roleSelect.addEventListener('change', toggleDoctorFields);
            if (window.jQuery) {
                window.jQuery(roleSelect).on('change.select2 change', toggleDoctorFields);
            }
        }
        toggleDoctorFields();
    });
</script>
@endpush
