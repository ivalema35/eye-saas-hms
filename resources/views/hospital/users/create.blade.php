@extends('hospital.layouts.app')

@section('title', 'Add User')
{{-- Layout page-header intentionally unused — heading, breadcrumb and
list all sit inside one bordered card, matching the panel design used
across the rest of the app. --}}

@section('content')
    <div class="user-create-page">

        <div class="user-create-outer-card">
            <div class="user-create-header-block">
                <div>
                    <div class="user-create-header-title"><i class="fa-solid fa-user-plus"></i> Add User</div>
                    <nav class="user-create-breadcrumb" aria-label="breadcrumb">
                        <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                        <span class="user-create-breadcrumb-sep">/</span>
                        <a href="{{ route('hospital.users.index', ['slug' => $slug]) }}">Users</a>
                        <span class="user-create-breadcrumb-sep">/</span>
                        <span class="user-create-breadcrumb-current">Add User</span>
                    </nav>
                </div>
                <a href="{{ route('hospital.users.index', ['slug' => $slug]) }}"
                    class="hms-btn hms-btn-outline user-create-back-btn">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('hospital.users.store', ['slug' => $slug]) }}" enctype="multipart/form-data">
            @csrf

            <div class="user-create-shell">
                <div class="user-create-hero">
                    <div>
                        <span class="user-create-kicker">Team Access</span>
                        <h3 class="user-create-heading">Create a new hospital user</h3>
                        <p class="user-create-copy">Add account details, assign the correct role, and configure
                            doctor-specific access only when needed.</p>
                    </div>
                </div>

                <div class="hms-card user-create-card">
                    <div class="hms-card-body user-create-card-body">
                        <div class="user-create-grid">
                            <div class="hms-form-group user-create-field">
                                <label>Name <span class="hms-required">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}"
                                    class="hms-input @error('name') is-invalid @enderror" required>
                                @error('name')
                                <div class="hms-field-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="hms-form-group user-create-field">
                                <label>Role <span class="hms-required">*</span></label>
                                <select name="role_id" id="role_id"
                                    class="hms-select @error('role_id') is-invalid @enderror" required>
                                    <option value="">Select Role</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" data-slug="{{ $role->slug }}"
                                            data-doctor-fields="{{ !empty($role->shows_doctor_fields) ? '1' : '0' }}"
                                            @selected(old('role_id') == $role->id)>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role_id')
                                <div class="hms-field-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="hms-form-group user-create-field">
                                <label>Contact</label>
                                <input type="text" name="contact" value="{{ old('contact') }}"
                                    class="hms-input @error('contact') is-invalid @enderror" data-intl-phone
                                    placeholder="+919876543210">
                                @error('contact')
                                <div class="hms-field-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="hms-form-group user-create-field">
                                <label>Email <span class="hms-required">*</span></label>
                                <input type="email" name="email" value="{{ old('email') }}"
                                    class="hms-input @error('email') is-invalid @enderror" required>
                                @error('email')
                                <div class="hms-field-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="hms-form-group user-create-field">
                                <label>Password <span class="hms-required">*</span></label>
                                <div class="user-password-field-wrap">
                                    <input type="password" name="password" id="userCreatePassword"
                                        class="hms-input user-password-field-input @error('password') is-invalid @enderror"
                                        required>
                                    <button type="button" id="toggleUserCreatePassword" class="user-password-field-toggle"
                                        aria-label="Toggle password visibility">
                                        <svg id="userCreatePasswordEye" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"
                                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                                            <circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle>
                                        </svg>
                                    </button>
                                </div>
                                @error('password')
                                <div class="hms-field-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="hms-form-group user-create-field">
                                <label>Confirm Password <span class="hms-required">*</span></label>
                                <div class="user-password-field-wrap">
                                    <input type="password" name="password_confirmation" id="userCreatePasswordConfirm"
                                        class="hms-input user-password-field-input" required>
                                    <button type="button" id="toggleUserCreatePasswordConfirm"
                                        class="user-password-field-toggle" aria-label="Toggle confirm password visibility">
                                        <svg id="userCreatePasswordConfirmEye" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"
                                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                                            <circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="hms-form-group user-create-field">
                                <label>Status <span class="hms-required">*</span></label>
                                <select name="status" class="hms-select @error('status') is-invalid @enderror" required>
                                    <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                    <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                                </select>
                                @error('status')
                                <div class="hms-field-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="hms-form-group doctor-only user-create-field" style="display:none">
                                <label>Doctor Prefix <span style="font-size:.75rem;color:#64748B;font-weight:400">(2–5
                                        letters, e.g. JP)</span></label>
                                <input type="text" name="doctor_prefix" value="{{ strtoupper(old('doctor_prefix', '')) }}"
                                    maxlength="5" placeholder="e.g. JP" style="text-transform:uppercase"
                                    class="hms-input @error('doctor_prefix') is-invalid @enderror">
                                <div style="font-size:.73rem;color:#94A3B8;margin-top:.25rem">Daily serial format:
                                    <strong>JP-001, JP-002…</strong></div>
                                @error('doctor_prefix')
                                <div class="hms-field-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="hms-form-group doctor-only user-create-field" style="display:none">
                                <label>Registration No.</label>
                                <input type="text" name="registration_no" value="{{ old('registration_no') }}"
                                    class="hms-input @error('registration_no') is-invalid @enderror"
                                    placeholder="e.g. MCI-12345">
                                @error('registration_no')
                                <div class="hms-field-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="hms-form-group doctor-only user-create-field" style="display:none">
                                <label>Experience <span
                                        style="font-size:.75rem;color:#64748B;font-weight:400">(years)</span></label>
                                <input type="number" name="experience_years" value="{{ old('experience_years') }}" min="0"
                                    max="60" class="hms-input @error('experience_years') is-invalid @enderror"
                                    placeholder="e.g. 5">
                                @error('experience_years')
                                <div class="hms-field-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="hms-form-group doctor-only user-create-field" style="display:none">
                                <label>Signature <span style="font-size:.75rem;color:#64748B;font-weight:400">(JPG/PNG · max
                                        20KB)</span></label>
                                <input type="file" name="signature" accept="image/jpg,image/jpeg,image/png"
                                    class="hms-input @error('signature') is-invalid @enderror">
                                @error('signature')
                                <div class="hms-field-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="hms-form-group doctor-only user-create-field" style="display:none">
                                <label>Profile Photo <span style="font-size:.75rem;color:#64748B;font-weight:400">(JPG/PNG ·
                                        max 20KB)</span></label>
                                <input type="file" name="profile_photo" accept="image/jpg,image/jpeg,image/png"
                                    class="hms-input @error('profile_photo') is-invalid @enderror">
                                @error('profile_photo')
                                <div class="hms-field-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="user-create-actions">
                    <button type="submit" class="hms-btn hms-btn-primary user-create-submit-btn">
                        <i class="fa-solid fa-check"></i> Save User
                    </button>
                    <a href="{{ route('hospital.users.index', ['slug' => $slug]) }}"
                        class="hms-btn hms-btn-outline user-create-cancel-btn">Cancel</a>
                </div>
            </div>
        </form>
    </div>
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

            document.querySelectorAll('.doctor-only').forEach(function (element) {
                element.style.display = showDoctor ? '' : 'none';
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const roleSelect = document.getElementById('role_id');
            const passwordInput = document.getElementById('userCreatePassword');
            const passwordConfirmInput = document.getElementById('userCreatePasswordConfirm');
            const togglePassword = document.getElementById('toggleUserCreatePassword');
            const togglePasswordConfirm = document.getElementById('toggleUserCreatePasswordConfirm');
            const eyeSvg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle></svg>';
            const eyeSlashSvg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18" stroke-width="1.8" stroke-linecap="round"></path><path d="M10.2 5.1A11.3 11.3 0 0 1 12 5c7 0 10.5 7 10.5 7a19 19 0 0 1-4.1 4.7" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M9.9 9.9A3.2 3.2 0 0 0 12 15.2c.5 0 1-.1 1.4-.3" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M7.1 7.4C4 9.6 1.5 12 1.5 12s3.5 7 10.5 7c1.7 0 3.2-.3 4.6-.8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>';

            if (roleSelect) {
                roleSelect.addEventListener('change', toggleDoctorFields);
                if (window.jQuery) {
                    window.jQuery(roleSelect).on('change.select2 change', toggleDoctorFields);
                }
            }
            toggleDoctorFields();

            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function () {
                    const isHidden = passwordInput.type === 'password';
                    passwordInput.type = isHidden ? 'text' : 'password';
                    document.getElementById('userCreatePasswordEye').innerHTML = isHidden ? eyeSlashSvg : eyeSvg;
                });
            }

            if (togglePasswordConfirm && passwordConfirmInput) {
                togglePasswordConfirm.addEventListener('click', function () {
                    const isHidden = passwordConfirmInput.type === 'password';
                    passwordConfirmInput.type = isHidden ? 'text' : 'password';
                    document.getElementById('userCreatePasswordConfirmEye').innerHTML = isHidden ? eyeSlashSvg : eyeSvg;
                });
            }
        });
    </script>
@endpush

@push('styles')
    <style>
        .user-create-page {
            --uc-primary: #1B4F72;
            --uc-soft: #ebf5fbeb;
            --uc-border: rgba(27, 79, 114, .12);
            --uc-border-strong: rgba(27, 79, 114, .2);
            --uc-text-soft: rgba(27, 79, 114, .72);
            color: var(--uc-primary);
        }

        .user-create-back-btn {
            border-color: var(--uc-border-strong) !important;
            color: var(--uc-primary) !important;
            background: rgba(255, 255, 255, .92) !important;
            border-radius: 12px !important;
        }

        .user-create-outer-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.12);
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(15, 79, 134, 0.08);
            padding: 1.1rem 1.5rem;
            margin-bottom: 1.25rem;
        }

        .user-create-header-block {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .75rem;
        }

        .user-create-header-title {
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--uc-primary);
            letter-spacing: -.015em;
            display: flex;
            align-items: center;
            gap: .55rem;
        }

        .user-create-header-title i {
            color: var(--uc-primary);
            font-size: 1.2rem;
        }

        .user-create-breadcrumb {
            margin-top: .4rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: #8891a0;
        }

        .user-create-breadcrumb a {
            color: #8891a0;
            text-decoration: none;
        }

        .user-create-breadcrumb a:hover {
            color: var(--uc-primary);
        }

        .user-create-breadcrumb-sep {
            color: #c3c9d3;
        }

        .user-create-breadcrumb-current {
            color: #4a5568;
            font-weight: 600;
        }

        .user-create-shell {
            display: grid;
            gap: 1rem;
        }

        .user-create-hero,
        .user-create-card {
            border: 1px solid rgba(15, 79, 134, 0.08) !important;
            border-radius: 16px !important;
            background: #ffffff;
            box-shadow: 0 8px 24px rgba(15, 79, 134, 0.05);
            overflow: hidden;
        }

        .user-create-hero {
            padding: 1.35rem 1.5rem;
            background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94));
        }

        .user-create-kicker {
            display: inline-flex;
            align-items: center;
            font-size: .72rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(27, 79, 114, .78);
            margin-bottom: .35rem;
        }

        .user-create-heading {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 900;
            color: var(--uc-primary);
            letter-spacing: -.02em;
        }

        .user-create-copy {
            margin: .3rem 0 0;
            color: var(--uc-text-soft);
            font-size: .92rem;
            max-width: 700px;
        }

        .user-create-card-header {
            padding: 1.1rem 1.35rem !important;
            border-bottom: 1px solid var(--uc-border) !important;
            background: rgba(255, 255, 255, .92);
        }

        .user-create-card-header .hms-card-title {
            margin: 0;
            color: var(--uc-primary);
            font-weight: 850;
        }

        .user-create-card-header .hms-card-title i {
            color: var(--uc-primary) !important;
        }

        .user-create-card-body {
            padding: 1.35rem !important;
            background: linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(246, 250, 253, .96));
        }

        .user-create-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem 1.1rem;
        }

        .user-create-field {
            margin-bottom: 0 !important;
        }

        .user-create-field label:first-child {
            display: block;
            margin-bottom: .48rem;
            color: rgba(27, 79, 114, .8);
            font-size: .78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .user-create-field .hms-input,
        .user-create-field .hms-select {
            min-height: 52px;
            border: 1px solid var(--uc-border) !important;
            border-radius: 16px !important;
            background: rgba(255, 255, 255, .94) !important;
            color: var(--uc-primary);
            box-shadow: inset 0 1px 2px rgba(27, 79, 114, .04);
        }

        .user-create-field .hms-input:focus,
        .user-create-field .hms-select:focus {
            border-color: var(--uc-primary) !important;
            box-shadow: 0 0 0 4px rgba(27, 79, 114, .10) !important;
        }

        .user-password-field-wrap {
            position: relative;
        }

        .user-password-field-input {
            padding-right: 3rem !important;
        }

        .user-password-field-toggle {
            position: absolute;
            right: .7rem;
            top: 50%;
            transform: translateY(-50%);
            border: 1px solid rgba(27, 79, 114, .14) !important;
            background: rgba(255, 255, 255, .98) !important;
            padding: 0 !important;
            margin: 0 !important;
            color: var(--uc-primary) !important;
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

        .user-password-field-toggle:hover {
            border-color: rgba(27, 79, 114, .22) !important;
            box-shadow: 0 4px 10px rgba(27, 79, 114, .10);
            transform: translateY(-50%) scale(1.02);
        }

        .user-password-field-toggle:focus-visible {
            outline: 2px solid rgba(27, 79, 114, .22);
            outline-offset: 2px;
        }

        .user-password-field-toggle svg {
            width: 1rem;
            height: 1rem;
            display: block;
            stroke: currentColor;
            fill: none;
            color: inherit;
            pointer-events: none;
        }

        .user-create-checkbox-wrap {
            align-self: stretch;
        }

        .user-create-checkbox {
            min-height: 52px;
            width: 100%;
            display: flex !important;
            align-items: center;
            gap: .7rem;
            padding: .9rem 1rem;
            border: 1px solid var(--uc-border);
            border-radius: 16px;
            background: rgba(255, 255, 255, .94);
            color: var(--uc-primary);
            font-weight: 700;
        }

        .user-create-actions {
            display: flex;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .user-create-submit-btn,
        .user-create-cancel-btn {
            min-width: 140px;
            border-radius: 14px !important;
            font-weight: 800 !important;
            padding: .85rem 1.2rem !important;
        }

        .user-create-submit-btn {
            background: var(--uc-primary) !important;
            border-color: var(--uc-primary) !important;
            box-shadow: 0 14px 26px rgba(27, 79, 114, .16);
        }

        .user-create-cancel-btn {
            border-color: var(--uc-border-strong) !important;
            color: var(--uc-primary) !important;
            background: rgba(255, 255, 255, .92) !important;
        }

        @media (max-width: 768px) {
            .user-create-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush