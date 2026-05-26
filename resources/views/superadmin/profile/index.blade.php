@extends('superadmin.layouts.app')

@section('title', 'My Profile')
@section('page-header', 'My Profile')

@section('content')

<div style="max-width:720px">

    {{-- Account Information Card --}}
    <div class="hms-card" style="padding:0;margin-bottom:1.25rem">
        <div class="hms-card-header">
            <h3 class="hms-card-title">
                <i class="bi bi-person-circle" style="color:#1B4F72"></i>
                Account Information
            </h3>
        </div>
        <div style="padding:1.375rem">
            <form method="POST" action="{{ route('superadmin.profile.update') }}">
                @csrf
                @method('PUT')

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                    <div class="hms-form-group">
                        <label class="hms-label">Full Name</label>
                        <input type="text" name="name"
                               class="hms-input @error('name') is-invalid @enderror"
                               value="{{ old('name', $admin->name) }}"
                               placeholder="Super Admin Name">
                        @error('name')
                            <span class="hms-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="hms-form-group">
                        <label class="hms-label">Email Address</label>
                        <input type="email" name="email"
                               class="hms-input @error('email') is-invalid @enderror"
                               value="{{ old('email', $admin->email) }}"
                               placeholder="admin@eyehms.com">
                        @error('email')
                            <span class="hms-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="hms-form-group" style="margin-top:.5rem">
                    <label class="hms-label">Role</label>
                    <input type="text" class="hms-input"
                           value="{{ ucfirst($admin->role ?? 'superadmin') }}" readonly
                           style="background:#F8FAFC;color:#64748B;cursor:not-allowed">
                </div>

                <div style="display:flex;align-items:center;gap:.75rem;margin-top:1.25rem">
                    <button type="submit" class="hms-btn hms-btn-primary">
                        <i class="bi bi-floppy-fill"></i> Save Changes
                    </button>
                    @if(isset($admin->last_login_at) && $admin->last_login_at)
                        <span style="font-size:.78rem;color:#94A3B8">
                            Last login: {{ $admin->last_login_at->diffForHumans() }}
                            @if(isset($admin->last_login_ip) && $admin->last_login_ip)
                                from {{ $admin->last_login_ip }}
                            @endif
                        </span>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Change Password Card --}}
    <div class="hms-card" style="padding:0">
        <div class="hms-card-header">
            <h3 class="hms-card-title">
                <i class="bi bi-lock-fill" style="color:#1B4F72"></i>
                Change Password
            </h3>
        </div>
        <div style="padding:1.375rem">
            <form method="POST" action="{{ route('superadmin.profile.password') }}">
                @csrf
                @method('PUT')

                <div class="hms-form-group">
                    <label class="hms-label">Current Password</label>
                    <div style="position:relative">
                        <input type="password" name="current_password" id="currentPwd"
                               class="hms-input @error('current_password') is-invalid @enderror"
                               placeholder="Enter current password">
                        <button type="button" class="sa-pwd-toggle" onclick="togglePwd('currentPwd', this)">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                    @error('current_password')
                        <span class="hms-error">{{ $message }}</span>
                    @enderror
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:.75rem">
                    <div class="hms-form-group">
                        <label class="hms-label">New Password</label>
                        <div style="position:relative">
                            <input type="password" name="password" id="newPwd"
                                   class="hms-input @error('password') is-invalid @enderror"
                                   placeholder="Min 8 chars, mixed case + number">
                            <button type="button" class="sa-pwd-toggle" onclick="togglePwd('newPwd', this)">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>
                        @error('password')
                            <span class="hms-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="hms-form-group">
                        <label class="hms-label">Confirm New Password</label>
                        <div style="position:relative">
                            <input type="password" name="password_confirmation" id="confirmPwd"
                                   class="hms-input"
                                   placeholder="Repeat new password">
                            <button type="button" class="sa-pwd-toggle" onclick="togglePwd('confirmPwd', this)">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="hms-btn hms-btn-primary" style="margin-top:1.25rem">
                    <i class="bi bi-key-fill"></i> Change Password
                </button>
            </form>
        </div>
    </div>

</div>

@endsection

@push('styles')
<style>
.sa-pwd-toggle {
    position: absolute;
    right: .75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #94A3B8;
    cursor: pointer;
    padding: 0;
    font-size: .9rem;
    transition: color .15s;
}
.sa-pwd-toggle:hover { color: #1B4F72; }
</style>
@endpush

@push('scripts')
<script>
function togglePwd(id, btn) {
    var input = document.getElementById(id);
    var icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
    }
}
</script>
@endpush
