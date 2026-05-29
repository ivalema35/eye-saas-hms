<style>
    .user-form-modal .modal-dialog {
        max-width: min(920px, calc(100vw - 2rem));
    }

    .user-form-modal .modal-content {
        border: 1px solid rgba(27, 79, 114, .12) !important;
        border-radius: 22px;
        background: rgba(255, 255, 255, .98);
        box-shadow: 0 20px 50px rgba(27, 79, 114, .15);
        overflow: hidden;
    }

    .user-form-modal .modal-header {
        background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94));
        border-bottom: 1px solid rgba(27, 79, 114, .12) !important;
        padding: 1.25rem 1.5rem !important;
    }

    .user-form-modal .modal-title,
    .user-form-modal .form-label {
        color: #1B4F72;
        font-weight: 900;
    }

    .user-form-modal .modal-body {
        padding: 1.5rem !important;
    }

    .user-form-modal .modal-footer {
        background: linear-gradient(135deg, rgba(235, 245, 251, .72), rgba(255, 255, 255, .94));
        border-top: 1px solid rgba(27, 79, 114, .12) !important;
        padding: 1.2rem 1.5rem !important;
    }

    .user-form-modal .btn-close {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, .92);
        border: 1px solid rgba(27, 79, 114, .14);
        box-shadow: 0 8px 18px rgba(27, 79, 114, .10);
        opacity: 1;
    }

    .user-form-modal .hms-input,
    .user-form-modal .hms-select {
        border: 1.5px solid rgba(27, 79, 114, .12) !important;
        border-radius: 12px;
        background: rgba(255, 255, 255, .96);
        color: #1B4F72;
        font-weight: 650;
    }

    .user-form-modal .hms-input:focus,
    .user-form-modal .hms-select:focus {
        border-color: #1B4F72 !important;
        box-shadow: 0 0 0 4px rgba(27, 79, 114, .12);
    }

    .user-form-modal .btn-primary,
    .user-form-modal .hms-btn-primary {
        background: #1B4F72 !important;
        border-color: #1B4F72 !important;
        border-radius: 12px;
        font-weight: 900;
        box-shadow: 0 10px 24px rgba(27, 79, 114, .20);
    }

    .user-form-modal .btn-outline-secondary,
    .user-form-modal .hms-btn-outline {
        border-color: rgba(27, 79, 114, .18) !important;
        border-radius: 12px;
        color: #1B4F72 !important;
        font-weight: 850;
    }
</style>

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

<div class="modal fade user-form-modal" id="userFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="userFormModalTitle">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="userForm" method="POST" action="{{ route('hospital.users.store', ['slug' => $slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="userFormMethod" value="POST">
                <input type="hidden" name="user_id" id="user-id" value="{{ old('user_id') }}">

                <div class="modal-body">
                    <div class="hms-form-grid-2">
                        <div class="hms-form-group">
                            <label>Name <span class="hms-required">*</span></label>
                            <input type="text" name="name" id="user-name" value="{{ old('name') }}"
                                   class="hms-input @error('name') is-invalid @enderror" placeholder="Enter full name" required>
                            @error('name')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="hms-form-group">
                            <label>Email <span class="hms-required">*</span></label>
                            <input type="email" name="email" id="user-email" value="{{ old('email') }}"
                                   class="hms-input @error('email') is-invalid @enderror" placeholder="name@example.com" required>
                            @error('email')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="hms-form-group">
                            <label>Role <span class="hms-required">*</span></label>
                            <select name="role_id" id="user-role-id" class="hms-select @error('role_id') is-invalid @enderror" required>
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
                            <input type="text" name="contact" id="user-contact" value="{{ old('contact') }}"
                                   class="hms-input @error('contact') is-invalid @enderror" placeholder="Enter contact number" maxlength="15">
                            @error('contact')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="hms-form-group">
                            <label>Status <span class="hms-required">*</span></label>
                            <select name="status" id="user-status" class="hms-select @error('status') is-invalid @enderror" required>
                                <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                            </select>
                            @error('status')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="hms-form-group user-doctor-only" style="display:none">
                            <label>Doctor Type</label>
                            <select name="doctor_type" id="user-doctor-type" class="hms-select @error('doctor_type') is-invalid @enderror">
                                <option value="">Select Type</option>
                                <option value="primary" @selected(old('doctor_type') === 'primary')>Primary</option>
                                <option value="secondary" @selected(old('doctor_type') === 'secondary')>Secondary</option>
                            </select>
                            @error('doctor_type')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <!-- <div class="hms-form-group user-doctor-only" style="display:none">
                            <label>&nbsp;</label>
                            <label class="hms-checkbox-label" style="padding:.5rem 0">
                                <input class="hms-checkbox" type="checkbox" value="1" id="user-foc-permission"
                                       name="foc_permission" @checked(old('foc_permission'))>
                                <span>Allow FOC Permission</span>
                            </label>
                        </div> -->

                        <div class="hms-form-group">
                            <label id="user-password-label">Password <span class="hms-required">*</span></label>
                            <input type="password" name="password" id="user-password" class="hms-input @error('password') is-invalid @enderror" placeholder="Create a password" required>
                            @error('password')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="hms-form-group">
                            <label id="user-password-confirm-label">Confirm Password <span class="hms-required">*</span></label>
                            <input type="password" name="password_confirmation" id="user-password-confirmation" class="hms-input" placeholder="Re-enter password" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 gap-2">
                    <button type="button" class="hms-btn hms-btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="hms-btn hms-btn-primary" id="userFormSubmitBtn">
                        <i class="fa-solid fa-check"></i> Save User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const userStoreUrl = "{{ route('hospital.users.store', ['slug' => $slug]) }}";
const userUpdateBase = "{{ route('hospital.users.update', ['slug' => $slug, 'id' => '__ID__']) }}";

function toggleUserDoctorFields() {
    const roleSelect = document.getElementById('user-role-id');
    const selected = roleSelect.options[roleSelect.selectedIndex];
    const slug = selected ? selected.dataset.slug : '';
    const showDoctor = slug === 'doctor';

    document.querySelectorAll('.user-doctor-only').forEach(function (el) {
        el.style.display = showDoctor ? '' : 'none';
    });
}

function setUserPasswordRequired(required) {
    document.getElementById('user-password').required = required;
    document.getElementById('user-password-confirmation').required = required;
    document.getElementById('user-password-label').innerHTML = required
        ? 'Password <span class="hms-required">*</span>'
        : 'New Password <span style="color:var(--hms-text-muted);font-size:.8rem">(leave blank to keep current)</span>';
    document.getElementById('user-password-confirm-label').innerText = required ? 'Confirm Password *' : 'Confirm Password';
}

function resetUserForm() {
    document.getElementById('userFormModalTitle').innerText = 'Add User';
    document.getElementById('userFormSubmitBtn').innerHTML = '<i class="fa-solid fa-check"></i> Save User';
    document.getElementById('userForm').action = userStoreUrl;
    document.getElementById('userFormMethod').value = 'POST';
    document.getElementById('user-id').value = '';
    document.getElementById('user-name').value = '';
    document.getElementById('user-email').value = '';
    document.getElementById('user-role-id').value = '';
    document.getElementById('user-contact').value = '';
    document.getElementById('user-status').value = 'active';
    document.getElementById('user-doctor-type').value = '';
    document.getElementById('user-foc-permission').checked = false;
    document.getElementById('user-password').value = '';
    document.getElementById('user-password-confirmation').value = '';
    setUserPasswordRequired(true);
    toggleUserDoctorFields();
}
window.resetUserForm = resetUserForm;

function openUserEditModal(record) {
    document.getElementById('userFormModalTitle').innerText = 'Edit User';
    document.getElementById('userFormSubmitBtn').innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Update User';
    document.getElementById('userForm').action = userUpdateBase.replace('__ID__', record.id);
    document.getElementById('userFormMethod').value = 'PUT';
    document.getElementById('user-id').value = record.id ?? '';
    document.getElementById('user-name').value = record.name ?? '';
    document.getElementById('user-email').value = record.email ?? '';
    document.getElementById('user-role-id').value = record.role_id ?? '';
    document.getElementById('user-contact').value = record.contact ?? '';
    document.getElementById('user-status').value = record.status ?? 'active';
    document.getElementById('user-doctor-type').value = record.doctor_type ?? '';
    document.getElementById('user-foc-permission').checked = !!record.foc_permission;
    document.getElementById('user-password').value = '';
    document.getElementById('user-password-confirmation').value = '';
    setUserPasswordRequired(false);
    toggleUserDoctorFields();

    bootstrap.Modal.getOrCreateInstance(document.getElementById('userFormModal')).show();
}
window.openUserEditModal = openUserEditModal;

document.addEventListener('DOMContentLoaded', function () {
    const userModalEl = document.getElementById('userFormModal');
    if (userModalEl && userModalEl.parentElement !== document.body) {
        document.body.appendChild(userModalEl);
    }

    document.getElementById('user-role-id').addEventListener('change', toggleUserDoctorFields);

    document.querySelectorAll('.edit-user-modal-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            openUserEditModal(JSON.parse(this.dataset.record));
        });
    });

    @if($errors->any())
    (function () {
        const oldMethod = @json(old('_method', 'POST'));
        const oldUserId = @json(old('user_id', ''));

        document.getElementById('userFormMethod').value = oldMethod;
        document.getElementById('user-id').value = oldUserId;
        document.getElementById('user-name').value = @json(old('name', ''));
        document.getElementById('user-email').value = @json(old('email', ''));
        document.getElementById('user-role-id').value = @json(old('role_id', ''));
        document.getElementById('user-contact').value = @json(old('contact', ''));
        document.getElementById('user-status').value = @json(old('status', 'active'));
        document.getElementById('user-doctor-type').value = @json(old('doctor_type', ''));
        document.getElementById('user-foc-permission').checked = @json((bool) old('foc_permission'));

        if (oldMethod === 'PUT' && oldUserId) {
            document.getElementById('userFormModalTitle').innerText = 'Edit User';
            document.getElementById('userFormSubmitBtn').innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Update User';
            document.getElementById('userForm').action = userUpdateBase.replace('__ID__', oldUserId);
            setUserPasswordRequired(false);
        } else {
            document.getElementById('userFormModalTitle').innerText = 'Add User';
            document.getElementById('userFormSubmitBtn').innerHTML = '<i class="fa-solid fa-check"></i> Save User';
            document.getElementById('userForm').action = userStoreUrl;
            setUserPasswordRequired(true);
        }

        toggleUserDoctorFields();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('userFormModal')).show();
    })();
    @else
    resetUserForm();
    @endif
});
</script>
@endpush
