@php
    $formId = $formId ?? 'addHospital';
    $nameInputId = $formId . 'Name';
    $slugInputId = $formId . 'Slug';
    $codeInputId = $formId . 'Code';
@endphp

@if($errors->any() && old('open_add_hospital_modal'))
    <div class="hms-alert hms-alert-danger">
        <ul style="margin:0;padding-left:1.2rem">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('superadmin.hospitals.store') }}" id="{{ $formId }}Form">
    @csrf
    <input type="hidden" name="open_add_hospital_modal" value="1">
    <input type="hidden" name="hospital_code" id="{{ $codeInputId }}" value="{{ old('hospital_code') }}">

    <div class="hms-form-group">
        <label>Hospital Name <span style="color:var(--hms-danger)">*</span></label>
        <input type="text" name="hospital_name" id="{{ $nameInputId }}"
               class="hms-input @error('hospital_name') is-invalid @enderror"
               value="{{ old('hospital_name') }}" required maxlength="100">
        @error('hospital_name') <span class="hms-error">{{ $message }}</span> @enderror
    </div>

    <div class="hms-form-group">
        <label>Hospital Slug (URL) <span style="color:var(--hms-danger)">*</span></label>
        <div style="display:flex;align-items:center;gap:.5rem">
            <span style="font-size:.8rem;color:var(--hms-text-muted);white-space:nowrap">
                {{ config('app.url') }}/
            </span>
            <input type="text" name="slug" id="{{ $slugInputId }}"
                   class="hms-input @error('slug') is-invalid @enderror"
                   value="{{ old('slug') }}" required
                   maxlength="30" pattern="[a-z0-9\-]+">
        </div>
        <span style="font-size:.75rem;color:var(--hms-text-muted)">
            Auto-generated from hospital name. Lowercase, letters, numbers, hyphens only.
        </span>
        @error('slug') <span class="hms-error">{{ $message }}</span> @enderror
        @error('hospital_code') <span class="hms-error">{{ $message }}</span> @enderror
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="hms-form-group">
            <label>Admin Full Name <span style="color:var(--hms-danger)">*</span></label>
            <input type="text" name="admin_name"
                   class="hms-input @error('admin_name') is-invalid @enderror"
                   value="{{ old('admin_name') }}" required>
            @error('admin_name') <span class="hms-error">{{ $message }}</span> @enderror
        </div>
        <div class="hms-form-group">
            <label>Admin Email <span style="color:var(--hms-danger)">*</span></label>
            <input type="email" name="admin_email"
                   class="hms-input @error('admin_email') is-invalid @enderror"
                   value="{{ old('admin_email') }}" required>
            @error('admin_email') <span class="hms-error">{{ $message }}</span> @enderror
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="hms-form-group">
            <label>Admin Phone <span style="color:var(--hms-danger)">*</span></label>
            <input type="text" name="admin_phone"
                   class="hms-input @error('admin_phone') is-invalid @enderror"
                   value="{{ old('admin_phone') }}" data-intl-phone
                   placeholder="+919876543210" required>
            @error('admin_phone') <span class="hms-error">{{ $message }}</span> @enderror
        </div>
        <div class="hms-form-group">
            <label>Password <span style="color:var(--hms-danger)">*</span></label>
            <input type="password" name="password"
                   class="hms-input @error('password') is-invalid @enderror"
                   minlength="8" placeholder="Minimum 8 characters" required>
            @error('password') <span class="hms-error">{{ $message }}</span> @enderror
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="hms-form-group">
            <label>City</label>
            <input type="text" name="city" class="hms-input" value="{{ old('city') }}">
        </div>
        <div class="hms-form-group">
            <label>State</label>
            <input type="text" name="state" class="hms-input" value="{{ old('state') }}">
        </div>
    </div>

    <div class="hms-form-group">
        <label>Plan (for reference)</label>
        <select name="plan" class="hms-select">
            <option value="monthly" {{ old('plan','monthly')=='monthly' ? 'selected':'' }}>Monthly</option>
            <option value="quarterly" {{ old('plan')=='quarterly' ? 'selected':'' }}>Quarterly</option>
            <option value="yearly" {{ old('plan')=='yearly' ? 'selected':'' }}>Yearly</option>
        </select>
        <span style="font-size:.75rem;color:var(--hms-text-muted)">
            Hospital will start on {{ platform_trial_label() }} free trial regardless.
        </span>
    </div>

    <input type="hidden" name="start_trial" value="1">

    <div style="display:flex;gap:.75rem;margin-top:1.5rem">
        <button type="submit" class="hms-btn hms-btn-primary">
            <i class="bi bi-hospital-fill"></i> Create Hospital
        </button>
        <button type="button" class="hms-btn hms-btn-secondary" data-bs-dismiss="modal">
            Cancel
        </button>
    </div>
</form>
