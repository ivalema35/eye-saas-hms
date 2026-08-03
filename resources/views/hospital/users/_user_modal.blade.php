<style>
    .user-form-modal .modal-dialog {
        max-width: min(920px, calc(100vw - 2rem));
        max-height: calc(100vh - 2rem);
    }

    .user-form-modal .modal-content {
        border: 1px solid rgba(27, 79, 114, .12) !important;
        border-radius: 22px;
        background: rgba(255, 255, 255, .98);
        box-shadow: 0 20px 50px rgba(27, 79, 114, .15);
        max-height: calc(100vh - 3rem);
        display: flex;
        flex-direction: column;
    }

    .user-form-modal .modal-content>form {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
    }

    .user-form-modal .modal-body {
        overflow-y: auto !important;
        flex: 1 1 auto;
        min-height: 0;
        padding: 1.5rem !important;
    }

    .user-form-modal .modal-footer {
        flex-shrink: 0;
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

    .user-form-modal .modal-footer {
        background: linear-gradient(135deg, rgba(235, 245, 251, .72), rgba(255, 255, 255, .94));
        border-top: 1px solid rgba(27, 79, 114, .12) !important;
        padding: 1.2rem 1.5rem !important;
        flex-shrink: 0;
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

    .user-form-modal .password-field-wrap {
        position: relative;
    }

    .user-form-modal .password-field-input {
        padding-right: 3rem !important;
    }

    .user-form-modal .password-field-toggle {
        position: absolute;
        right: .7rem;
        top: 50%;
        transform: translateY(-50%);
        border: 1px solid rgba(27, 79, 114, .14) !important;
        background: rgba(255, 255, 255, .98) !important;
        padding: 0 !important;
        margin: 0 !important;
        color: #1B4F72 !important;
        width: 2rem;
        height: 2rem;
        border-radius: 999px !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        line-height: 1;
        z-index: 3;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(27, 79, 114, .06);
        transition: color .2s ease, border-color .2s ease, box-shadow .2s ease, transform .15s ease;
    }

    .user-form-modal .password-field-toggle:hover {
        border-color: rgba(27, 79, 114, .22) !important;
        box-shadow: 0 4px 10px rgba(27, 79, 114, .10);
        transform: translateY(-50%) scale(1.02);
    }

    .user-form-modal .password-field-toggle:focus-visible {
        outline: 2px solid rgba(27, 79, 114, .22);
        outline-offset: 2px;
    }

    .user-form-modal .password-field-toggle svg {
        width: 1rem;
        height: 1rem;
        display: block;
        stroke: currentColor;
        fill: none;
        color: inherit;
        pointer-events: none;
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

            <form id="userForm" method="POST" action="{{ route('hospital.users.store', ['slug' => $slug]) }}"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="userFormMethod" value="POST">
                <input type="hidden" name="user_id" id="user-id" value="{{ old('user_id') }}">

                <div class="modal-body">
                    <div class="hms-form-grid-2">
                        <div class="hms-form-group">
                            <label>Name <span class="hms-required">*</span></label>
                            <input type="text" name="name" id="user-name" value="{{ old('name') }}"
                                class="hms-input @error('name') is-invalid @enderror" placeholder="Enter full name"
                                required>
                            @error('name')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="hms-form-group">
                            <label>Role <span class="hms-required">*</span></label>
                            <select name="role_id" id="user-role-id"
                                class="hms-select @error('role_id') is-invalid @enderror" required>
                                <option value="">Select Role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" data-slug="{{ $role->slug }}"
                                        @selected(old('role_id') == $role->id)>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role_id')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="hms-form-group">
                            <label>Contact</label>
                            <input type="text" name="contact" id="user-contact" value="{{ old('contact') }}"
                                class="hms-input @error('contact') is-invalid @enderror"
                                placeholder="+919876543210" data-intl-phone>
                            @error('contact')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="hms-form-group">
                            <label>Email <span class="hms-required">*</span></label>
                            <input type="email" name="email" id="user-email" value="{{ old('email') }}"
                                class="hms-input @error('email') is-invalid @enderror" placeholder="name@example.com"
                                required>
                            @error('email')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="hms-form-group">
                            <label id="user-password-label">Password <span class="hms-required">*</span></label>
                            <div class="password-field-wrap">
                                <input type="password" name="password" id="user-password"
                                    class="hms-input password-field-input @error('password') is-invalid @enderror"
                                    placeholder="Create a password" required>
                                <button type="button" id="toggleUserPassword" class="password-field-toggle"
                                    aria-label="Toggle password visibility">
                                    <svg id="userPasswordEye" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"
                                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                                        <circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle>
                                    </svg>
                                </button>
                            </div>
                            @error('password')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="hms-form-group">
                            <label id="user-password-confirm-label">Confirm Password <span
                                    class="hms-required">*</span></label>
                            <div class="password-field-wrap">
                                <input type="password" name="password_confirmation" id="user-password-confirmation"
                                    class="hms-input password-field-input" placeholder="Re-enter password" required>
                                <button type="button" id="toggleUserPasswordConfirmation" class="password-field-toggle"
                                    aria-label="Toggle confirm password visibility">
                                    <svg id="userPasswordConfirmationEye" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"
                                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                                        <circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="hms-form-group">
                            <label>Status <span class="hms-required">*</span></label>
                            <select name="status" id="user-status"
                                class="hms-select @error('status') is-invalid @enderror" required>
                                <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                            </select>
                            @error('status')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <!-- <div class="hms-form-group user-doctor-only" style="display:none">
                            <label>Doctor Type</label>
                            <select name="doctor_type" id="user-doctor-type" class="hms-select @error('doctor_type') is-invalid @enderror">
                                <option value="">Select Type</option>
                                <option value="primary" @selected(old('doctor_type') === 'primary')>Primary</option>
                                <option value="secondary" @selected(old('doctor_type') === 'secondary')>Secondary</option>
                            </select>
                            @error('doctor_type')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div> -->

                        <div class="hms-form-group user-doctor-only" style="display:none">
                            <label>Doctor Prefix <span style="font-size:.75rem;color:#64748B;font-weight:400">(2–5
                                    letters)</span></label>
                            <input type="text" name="doctor_prefix" id="user-doctor-prefix" maxlength="5"
                                placeholder="e.g. JP" style="text-transform:uppercase"
                                class="hms-input @error('doctor_prefix') is-invalid @enderror">
                            <div style="font-size:.72rem;color:#94A3B8;margin-top:.2rem">Daily serial format:
                                <strong>JP-001, JP-002…</strong>
                            </div>
                            @error('doctor_prefix')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="hms-form-group user-doctor-only" style="display:none">
                            <label>Registration No.</label>
                            <input type="text" name="registration_no" id="user-registration-no"
                                class="hms-input @error('registration_no') is-invalid @enderror"
                                placeholder="e.g. MCI-12345">
                            @error('registration_no')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="hms-form-group user-doctor-only" style="display:none">
                            <label>Experience <span
                                    style="font-size:.75rem;color:#64748B;font-weight:400">(years)</span></label>
                            <input type="number" name="experience_years" id="user-experience-years" min="0" max="60"
                                class="hms-input @error('experience_years') is-invalid @enderror" placeholder="e.g. 5">
                            @error('experience_years')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="hms-form-group user-doctor-only" style="display:none">
                            <label>Signature <span style="font-size:.75rem;color:#64748B;font-weight:400">(JPG/PNG · max
                                    20KB)</span></label>
                            <input type="file" name="signature" id="user-signature"
                                accept="image/jpg,image/jpeg,image/png"
                                class="hms-input @error('signature') is-invalid @enderror"
                                onchange="previewDoctorFile(this, 'user-signature-preview')">
                            <div id="user-signature-preview" style="margin-top:.4rem;display:none;">
                                <img src="" alt="Signature Preview"
                                    style="max-height:50px;border:1px solid rgba(27,79,114,.15);border-radius:8px;padding:4px;">
                            </div>
                            <div id="user-signature-current" style="margin-top:.4rem;display:none;">
                                <span style="font-size:.72rem;color:#64748B;">Current: </span>
                                <img src="" alt="Current Signature"
                                    style="max-height:44px;border:1px solid rgba(27,79,114,.15);border-radius:8px;padding:3px;">
                            </div>
                            @error('signature')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="hms-form-group user-doctor-only" style="display:none">
                            <label>Profile Photo <span style="font-size:.75rem;color:#64748B;font-weight:400">(JPG/PNG ·
                                    max 20KB)</span></label>
                            <input type="file" name="profile_photo" id="user-profile-photo"
                                accept="image/jpg,image/jpeg,image/png"
                                class="hms-input @error('profile_photo') is-invalid @enderror"
                                onchange="previewDoctorFile(this, 'user-photo-preview')">
                            <div id="user-photo-preview" style="margin-top:.4rem;display:none;">
                                <img src="" alt="Photo Preview"
                                    style="max-height:60px;border:1px solid rgba(27,79,114,.15);border-radius:8px;padding:4px;">
                            </div>
                            <div id="user-photo-current" style="margin-top:.4rem;display:none;">
                                <span style="font-size:.72rem;color:#64748B;">Current: </span>
                                <img src="" alt="Current Photo"
                                    style="max-height:55px;border:1px solid rgba(27,79,114,.15);border-radius:8px;padding:3px;">
                            </div>
                            @error('profile_photo')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <!-- <div class="hms-form-group user-doctor-only" style="display:none">
                            <label>&nbsp;</label>
                            <label class="hms-checkbox-label" style="padding:.5rem 0">
                                <input class="hms-checkbox" type="checkbox" value="1" id="user-foc-permission"
                                       name="foc_permission" @checked(old('foc_permission'))>
                                <span>Allow FOC Permission</span>
                            </label>
                        </div> -->
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
        const userPasswordEyeSvg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle></svg>';
        const userPasswordEyeSlashSvg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18" stroke-width="1.8" stroke-linecap="round"></path><path d="M10.2 5.1A11.3 11.3 0 0 1 12 5c7 0 10.5 7 10.5 7a19 19 0 0 1-4.1 4.7" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M9.9 9.9A3.2 3.2 0 0 0 12 15.2c.5 0 1-.1 1.4-.3" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M7.1 7.4C4 9.6 1.5 12 1.5 12s3.5 7 10.5 7c1.7 0 3.2-.3 4.6-.8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>';

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

        function bindUserPasswordToggle(inputId, buttonId, eyeId) {
            const input = document.getElementById(inputId);
            const button = document.getElementById(buttonId);
            const eye = document.getElementById(eyeId);

            if (!input || !button || !eye) {
                return;
            }

            button.addEventListener('click', function () {
                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                eye.innerHTML = isHidden ? userPasswordEyeSlashSvg : userPasswordEyeSvg;
            });
        }

        function resetUserPasswordToggleState() {
            const passwordInput = document.getElementById('user-password');
            const passwordConfirmInput = document.getElementById('user-password-confirmation');
            const passwordEye = document.getElementById('userPasswordEye');
            const passwordConfirmEye = document.getElementById('userPasswordConfirmationEye');

            if (passwordInput) {
                passwordInput.type = 'password';
            }

            if (passwordConfirmInput) {
                passwordConfirmInput.type = 'password';
            }

            if (passwordEye) {
                passwordEye.innerHTML = userPasswordEyeSvg;
            }

            if (passwordConfirmEye) {
                passwordConfirmEye.innerHTML = userPasswordEyeSvg;
            }
        }

        function previewDoctorFile(input, previewId) {
            var previewWrap = document.getElementById(previewId);
            if (!previewWrap) return;
            var img = previewWrap.querySelector('img');
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    img.src = e.target.result;
                    previewWrap.style.display = '';
                };
                reader.readAsDataURL(input.files[0]);
            }
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
            const doctorTypeResetEl = document.getElementById('user-doctor-type');
            if (doctorTypeResetEl) doctorTypeResetEl.value = '';
            document.getElementById('user-doctor-prefix').value = '';
            document.getElementById('user-registration-no').value = '';
            document.getElementById('user-experience-years').value = '';
            document.getElementById('user-signature').value = '';
            document.getElementById('user-profile-photo').value = '';
            document.getElementById('user-signature-preview').style.display = 'none';
            document.getElementById('user-photo-preview').style.display = 'none';
            document.getElementById('user-signature-current').style.display = 'none';
            document.getElementById('user-photo-current').style.display = 'none';
            const userFocPermissionInput = document.getElementById('user-foc-permission');
            if (userFocPermissionInput) {
                userFocPermissionInput.checked = false;
            }
            document.getElementById('user-password').value = '';
            document.getElementById('user-password-confirmation').value = '';
            setUserPasswordRequired(true);
            resetUserPasswordToggleState();
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
            const doctorTypeEditEl = document.getElementById('user-doctor-type');
            if (doctorTypeEditEl) doctorTypeEditEl.value = record.doctor_type ?? '';
            document.getElementById('user-doctor-prefix').value = (record.doctor_prefix ?? '').toUpperCase();
            document.getElementById('user-registration-no').value = record.registration_no ?? '';
            document.getElementById('user-experience-years').value = record.experience_years ?? '';
            document.getElementById('user-signature').value = '';
            document.getElementById('user-profile-photo').value = '';
            document.getElementById('user-signature-preview').style.display = 'none';
            document.getElementById('user-photo-preview').style.display = 'none';

            var sigCurrent = document.getElementById('user-signature-current');
            var photoCurrent = document.getElementById('user-photo-current');
            if (record.signature_path) {
                sigCurrent.querySelector('img').src = '/storage/' + record.signature_path;
                sigCurrent.style.display = '';
            } else {
                sigCurrent.style.display = 'none';
            }
            if (record.profile_photo_path) {
                photoCurrent.querySelector('img').src = '/storage/' + record.profile_photo_path;
                photoCurrent.style.display = '';
            } else {
                photoCurrent.style.display = 'none';
            }

            const userFocPermissionInput = document.getElementById('user-foc-permission');
            if (userFocPermissionInput) {
                userFocPermissionInput.checked = !!record.foc_permission;
            }
            document.getElementById('user-password').value = '';
            document.getElementById('user-password-confirmation').value = '';
            setUserPasswordRequired(false);
            resetUserPasswordToggleState();
            toggleUserDoctorFields();

            bootstrap.Modal.getOrCreateInstance(document.getElementById('userFormModal')).show();
        }
        window.openUserEditModal = openUserEditModal;

        document.addEventListener('DOMContentLoaded', function () {
            const userModalEl = document.getElementById('userFormModal');
            if (userModalEl && userModalEl.parentElement !== document.body) {
                document.body.appendChild(userModalEl);
            }

            bindUserPasswordToggle('user-password', 'toggleUserPassword', 'userPasswordEye');
            bindUserPasswordToggle('user-password-confirmation', 'toggleUserPasswordConfirmation', 'userPasswordConfirmationEye');

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
                    const doctorTypeOldEl = document.getElementById('user-doctor-type');
                    if (doctorTypeOldEl) doctorTypeOldEl.value = @json(old('doctor_type', ''));
                    document.getElementById('user-doctor-prefix').value = (@json(old('doctor_prefix', ''))).toUpperCase();
                    document.getElementById('user-registration-no').value = @json(old('registration_no', ''));
                    document.getElementById('user-experience-years').value = @json(old('experience_years', ''));
                    const userFocPermissionInput = document.getElementById('user-foc-permission');
                    if (userFocPermissionInput) {
                        userFocPermissionInput.checked = @json((bool) old('foc_permission'));
                    }

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

                    resetUserPasswordToggleState();
                    toggleUserDoctorFields();
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('userFormModal')).show();
                })();
            @else
                resetUserForm();
            @endif
                });
    </script>
@endpush