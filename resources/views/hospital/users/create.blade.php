@extends('hospital.layouts.app')

@section('title', 'Add User')
@section('page-header', 'Add User')

@section('page-actions')
    <a href="{{ route('hospital.users.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline user-create-back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')
<div class="user-create-page">
    <form method="POST" action="{{ route('hospital.users.store', ['slug' => $slug]) }}">
        @csrf

        <div class="user-create-shell">
            <div class="user-create-hero">
                <div>
                    <span class="user-create-kicker">Team Access</span>
                    <h3 class="user-create-heading">Create a new hospital user</h3>
                    <p class="user-create-copy">Add account details, assign the correct role, and configure doctor-specific access only when needed.</p>
                </div>
            </div>

            <div class="hms-card user-create-card">
                <div class="hms-card-header user-create-card-header">
                    <h3 class="hms-card-title">
                        <i class="fa-solid fa-id-badge"></i> User Details
                    </h3>
                </div>
                <div class="hms-card-body user-create-card-body">
                    <div class="user-create-grid">
                        <div class="hms-form-group user-create-field">
                            <label>Name <span class="hms-required">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   class="hms-input @error('name') is-invalid @enderror" required>
                            @error('name')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="hms-form-group user-create-field">
                            <label>Email <span class="hms-required">*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                   class="hms-input @error('email') is-invalid @enderror" required>
                            @error('email')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="hms-form-group user-create-field">
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

                        <div class="hms-form-group user-create-field">
                            <label>Contact</label>
                            <input type="text" name="contact" value="{{ old('contact') }}"
                                   class="hms-input @error('contact') is-invalid @enderror" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)">
                            @error('contact')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="hms-form-group user-create-field">
                            <label>Status <span class="hms-required">*</span></label>
                            <select name="status" class="hms-select @error('status') is-invalid @enderror" required>
                                <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                            </select>
                            @error('status')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <!-- <div class="hms-form-group doctor-only user-create-field" style="display:none">
                            <label>Doctor Type</label>
                            <select name="doctor_type" class="hms-select @error('doctor_type') is-invalid @enderror">
                                <option value="">Select Type</option>
                                <option value="primary" @selected(old('doctor_type') === 'primary')>Primary</option>
                                <option value="secondary" @selected(old('doctor_type') === 'secondary')>Secondary</option>
                            </select>
                            @error('doctor_type')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div> -->

                        <div class="hms-form-group doctor-only user-create-field" style="display:none">
                            <label>Doctor Prefix <span style="font-size:.75rem;color:#64748B;font-weight:400">(2–5 letters, e.g. JP)</span></label>
                            <input type="text" name="doctor_prefix" value="{{ strtoupper(old('doctor_prefix', '')) }}"
                                   maxlength="5" placeholder="e.g. JP"
                                   style="text-transform:uppercase"
                                   class="hms-input @error('doctor_prefix') is-invalid @enderror">
                            <div style="font-size:.73rem;color:#94A3B8;margin-top:.25rem">Used in daily patient serial: <strong>JP-1, JP-2…</strong> (resets each day)</div>
                            @error('doctor_prefix')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>
<!-- 
                        <div class="hms-form-group doctor-only user-create-field user-create-checkbox-wrap" style="display:none">
                            <label>Doctor Access</label>
                            <label class="hms-checkbox-label user-create-checkbox">
                                <input class="hms-checkbox" type="checkbox" value="1" id="foc_permission"
                                       name="foc_permission" @checked(old('foc_permission'))>
                                <span>Allow FOC Permission</span>
                            </label>
                        </div> -->

                        <div class="hms-form-group user-create-field">
                            <label>Password <span class="hms-required">*</span></label>
                            <div class="user-password-field-wrap">
                                <input type="password" name="password" id="userCreatePassword" class="hms-input user-password-field-input @error('password') is-invalid @enderror" required>
                                <button type="button" id="toggleUserCreatePassword" class="user-password-field-toggle" aria-label="Toggle password visibility">
                                    <svg id="userCreatePasswordEye" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                                        <circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle>
                                    </svg>
                                </button>
                            </div>
                            @error('password')<div class="hms-field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="hms-form-group user-create-field">
                            <label>Confirm Password <span class="hms-required">*</span></label>
                            <div class="user-password-field-wrap">
                                <input type="password" name="password_confirmation" id="userCreatePasswordConfirm" class="hms-input user-password-field-input" required>
                                <button type="button" id="toggleUserCreatePasswordConfirm" class="user-password-field-toggle" aria-label="Toggle confirm password visibility">
                                    <svg id="userCreatePasswordConfirmEye" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                                        <circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="user-create-actions">
                <button type="submit" class="hms-btn hms-btn-primary user-create-submit-btn">
                    <i class="fa-solid fa-check"></i> Save User
                </button>
                <a href="{{ route('hospital.users.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline user-create-cancel-btn">Cancel</a>
            </div>
        </div>
    </form>
</div>
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
        const passwordInput = document.getElementById('userCreatePassword');
        const passwordConfirmInput = document.getElementById('userCreatePasswordConfirm');
        const togglePassword = document.getElementById('toggleUserCreatePassword');
        const togglePasswordConfirm = document.getElementById('toggleUserCreatePasswordConfirm');
        const eyeSvg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle></svg>';
        const eyeSlashSvg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18" stroke-width="1.8" stroke-linecap="round"></path><path d="M10.2 5.1A11.3 11.3 0 0 1 12 5c7 0 10.5 7 10.5 7a19 19 0 0 1-4.1 4.7" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M9.9 9.9A3.2 3.2 0 0 0 12 15.2c.5 0 1-.1 1.4-.3" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M7.1 7.4C4 9.6 1.5 12 1.5 12s3.5 7 10.5 7c1.7 0 3.2-.3 4.6-.8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>';

        roleSelect.addEventListener('change', toggleDoctorFields);
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

    .user-create-shell {
        display: grid;
        gap: 1rem;
    }

    .user-create-hero,
    .user-create-card {
        border: 1px solid var(--uc-border) !important;
        border-radius: 24px !important;
        background: rgba(255, 255, 255, .92);
        box-shadow: 0 18px 42px rgba(27, 79, 114, .08);
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
        background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(246,250,253,.96));
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
