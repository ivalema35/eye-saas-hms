@if($errors->any())
    <div class="hms-alert hms-alert-danger">
        <ul style="margin:0;padding-left:1.2rem">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}">
    @csrf
    @method('PUT')

    @isset($editTenantId)
        <input type="hidden" name="edit_tenant_id" value="{{ $editTenantId }}">
    @endisset

    <div class="hms-form-group">
        <label>Hospital Name <span style="color:var(--hms-danger)">*</span></label>
        <input type="text" name="name"
               class="hms-input @error('name') is-invalid @enderror"
               value="{{ old('name', $tenant->name) }}" required>
        @error('name') <span class="hms-error">{{ $message }}</span> @enderror
    </div>

    <div class="hms-form-group">
        <label>Hospital Slug <span style="color:var(--hms-danger)">*</span></label>
        <div style="display:flex;align-items:center;gap:.5rem">
            <span style="font-size:.8rem;color:var(--hms-text-muted);white-space:nowrap">
                {{ config('app.url') }}/
            </span>
            <input type="text" name="slug"
                   class="hms-input @error('slug') is-invalid @enderror"
                   value="{{ old('slug', $tenant->slug) }}" required
                   maxlength="30" pattern="[a-z0-9\-]+">
        </div>
        <span style="font-size:.75rem;color:var(--hms-warning)">
            <i class="bi bi-exclamation-triangle-fill"></i>
            Slug change karne se login URL change ho jayega.
        </span>
        @error('slug') <span class="hms-error">{{ $message }}</span> @enderror
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="hms-form-group">
            <label>Admin Name <span style="color:var(--hms-danger)">*</span></label>
            <input type="text" name="admin_name"
                   class="hms-input @error('admin_name') is-invalid @enderror"
                   value="{{ old('admin_name', $tenant->admin_name) }}" required>
            @error('admin_name') <span class="hms-error">{{ $message }}</span> @enderror
        </div>
        <div class="hms-form-group">
            <label>Admin Phone <span style="color:var(--hms-danger)">*</span></label>
            <input type="text" name="admin_phone"
                   class="hms-input @error('admin_phone') is-invalid @enderror"
                   value="{{ old('admin_phone', $tenant->admin_phone) }}"
                   data-intl-phone placeholder="+919876543210" required>
            @error('admin_phone') <span class="hms-error">{{ $message }}</span> @enderror
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="hms-form-group">
            <label>City</label>
            <input type="text" name="city" class="hms-input" value="{{ old('city', $tenant->city) }}">
        </div>
        <div class="hms-form-group">
            <label>State</label>
            <input type="text" name="state" class="hms-input" value="{{ old('state', $tenant->state) }}">
        </div>
    </div>

    <div class="hms-form-group">
        <label>Admin Email (read-only)</label>
        <input type="text" class="hms-input" value="{{ $tenant->admin_email }}" disabled
               style="background:var(--hms-bg);cursor:not-allowed">
        <span style="font-size:.75rem;color:var(--hms-text-muted)">
            Email change karne ke liye alag security flow zaroor hoga.
        </span>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        @php
            $pwdFieldId = isset($editTenantId) ? 'saAdminPassword-' . $editTenantId : 'saAdminPassword';
            $pwdConfirmFieldId = isset($editTenantId) ? 'saAdminPasswordConfirm-' . $editTenantId : 'saAdminPasswordConfirm';
        @endphp
        <div class="hms-form-group">
            <label>New Admin Password</label>
            <div style="position:relative">
                <input type="password" name="admin_password" id="{{ $pwdFieldId }}"
                       class="hms-input sa-pwd-input @error('admin_password') is-invalid @enderror"
                       value="{{ old('admin_password') }}"
                       autocomplete="new-password"
                       placeholder="Leave blank to keep current">
                <button type="button" class="sa-pwd-toggle" onclick="toggleSaPwd('{{ $pwdFieldId }}', this)" aria-label="Show password">
                    <i class="bi bi-eye-fill"></i>
                </button>
            </div>
            @error('admin_password') <span class="hms-error">{{ $message }}</span> @enderror
        </div>
        <div class="hms-form-group">
            <label>Confirm Password</label>
            <div style="position:relative">
                <input type="password" name="admin_password_confirmation" id="{{ $pwdConfirmFieldId }}"
                       class="hms-input sa-pwd-input @error('admin_password') is-invalid @enderror"
                       value="{{ old('admin_password_confirmation') }}"
                       autocomplete="new-password"
                       placeholder="Re-enter new password">
                <button type="button" class="sa-pwd-toggle" onclick="toggleSaPwd('{{ $pwdConfirmFieldId }}', this)" aria-label="Show password">
                    <i class="bi bi-eye-fill"></i>
                </button>
            </div>
        </div>
    </div>
    <p style="font-size:.75rem;color:var(--hms-text-muted);margin:-.5rem 0 0">
        Password change thay to hospital admin ne SMTP thi notification email jashe.
    </p>

    <div style="display:flex;gap:.75rem;margin-top:1.5rem">
        <button type="submit" class="hms-btn hms-btn-primary">
            <i class="bi bi-floppy-fill"></i> {{ $submitLabel ?? 'Save Changes' }}
        </button>
        @if(!empty($cancelUrl))
            <a href="{{ $cancelUrl }}" class="hms-btn hms-btn-secondary">
                {{ $cancelLabel ?? 'Cancel' }}
            </a>
        @endif
    </div>
</form>

@once
    @push('styles')
        <style>
            .sa-pwd-input {
                padding-right: 2.5rem !important;
            }

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

            .sa-pwd-toggle:hover {
                color: #1B4F72;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            function toggleSaPwd(id, btn) {
                var input = document.getElementById(id);
                var icon = btn.querySelector('i');
                if (!input || !icon) { return; }
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
                    btn.setAttribute('aria-label', 'Hide password');
                } else {
                    input.type = 'password';
                    icon.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
                    btn.setAttribute('aria-label', 'Show password');
                }
            }
        </script>
    @endpush
@endonce