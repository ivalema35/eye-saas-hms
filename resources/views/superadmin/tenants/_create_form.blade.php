@php
    $formId = $formId ?? 'addHospital';
    $nameInputId = $formId . 'Name';
    $slugInputId = $formId . 'Slug';
    $codeInputId = $formId . 'Code';
    $countryInputId = $formId . 'Country';
    $stateInputId = $formId . 'State';
    $districtInputId = $formId . 'District';
    $cityInputId = $formId . 'City';
    $modalCountries = $countries ?? \App\Models\Platform\MasterCountry::query()
        ->active()
        ->orderBy('name')
        ->get(['id', 'name', 'country_code']);
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
    <input type="hidden" name="start_trial" value="1">

    {{-- Hospital Details --}}
    <div class="hms-form-group">
        <label>Hospital Name <span style="color:var(--hms-danger)">*</span></label>
        <input type="text" name="hospital_name" id="{{ $nameInputId }}"
               class="hms-input @error('hospital_name') is-invalid @enderror"
               value="{{ old('hospital_name') }}" required minlength="3" maxlength="100"
               placeholder="e.g. Vision Eye Centre">
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
                   maxlength="30" minlength="3" pattern="[a-z0-9\-]+"
                   placeholder="vision-eye-centre">
        </div>
        <span style="font-size:.75rem;color:var(--hms-text-muted)">
            Auto-generated from hospital name. Lowercase, letters, numbers, hyphens only.
        </span>
        <div id="{{ $formId }}SlugStatus" style="font-size:.78rem;margin-top:.35rem;min-height:1.1em"></div>
        @error('slug') <span class="hms-error">{{ $message }}</span> @enderror
    </div>

    <div class="hms-form-group">
        <label>
            Hospital Code <span style="color:var(--hms-danger)">*</span>
            <span style="font-weight:400;color:var(--hms-text-muted);font-size:.8rem">(3–4 letters — MRD prefix)</span>
        </label>
        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
            <input type="text" name="hospital_code" id="{{ $codeInputId }}"
                   class="hms-input @error('hospital_code') is-invalid @enderror"
                   value="{{ old('hospital_code') }}" required maxlength="4"
                   pattern="[A-Za-z]{3,4}" placeholder="e.g. MAI"
                   style="text-transform:uppercase;max-width:140px;letter-spacing:.06em">
            <span style="font-size:.8rem;color:var(--hms-text-muted)">
                MRD preview: <strong id="{{ $formId }}MrdPreview">---0001</strong>
            </span>
        </div>
        <div id="{{ $formId }}CodeStatus" style="font-size:.78rem;margin-top:.35rem;min-height:1.1em"></div>
        @error('hospital_code') <span class="hms-error">{{ $message }}</span> @enderror
    </div>

    {{-- Location (same as free-trial registration) --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="hms-form-group">
            <label>Country <span style="color:var(--hms-danger)">*</span></label>
            <select name="country" id="{{ $countryInputId }}" class="hms-select" required>
                <option value="">— Select Country —</option>
                @foreach($modalCountries as $c)
                    <option value="{{ $c->name }}" data-id="{{ $c->id }}"
                        @selected(old('country') === $c->name)>
                        {{ $c->name }}@if($c->country_code) ({{ $c->country_code }})@endif
                    </option>
                @endforeach
            </select>
            @error('country') <span class="hms-error">{{ $message }}</span> @enderror
        </div>
        <div class="hms-form-group">
            <label>State</label>
            <select name="state" id="{{ $stateInputId }}" class="hms-select" disabled>
                <option value="">— Select State —</option>
            </select>
            @error('state') <span class="hms-error">{{ $message }}</span> @enderror
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="hms-form-group">
            <label>District</label>
            <select name="district" id="{{ $districtInputId }}" class="hms-select" disabled>
                <option value="">— Select District —</option>
            </select>
            @error('district') <span class="hms-error">{{ $message }}</span> @enderror
        </div>
        <div class="hms-form-group">
            <label>City</label>
            <select name="city" id="{{ $cityInputId }}" class="hms-select" disabled>
                <option value="">— Select City —</option>
            </select>
            @error('city') <span class="hms-error">{{ $message }}</span> @enderror
        </div>
    </div>

    {{-- Admin --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="hms-form-group">
            <label>Admin Full Name <span style="color:var(--hms-danger)">*</span></label>
            <input type="text" name="admin_name"
                   class="hms-input @error('admin_name') is-invalid @enderror"
                   value="{{ old('admin_name') }}" required placeholder="Dr. John Smith">
            @error('admin_name') <span class="hms-error">{{ $message }}</span> @enderror
        </div>
        <div class="hms-form-group">
            <label>Admin Email <span style="color:var(--hms-danger)">*</span></label>
            <input type="email" name="admin_email"
                   class="hms-input @error('admin_email') is-invalid @enderror"
                   value="{{ old('admin_email') }}" required placeholder="admin@hospital.com">
            @error('admin_email') <span class="hms-error">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="hms-form-group">
        <label>Admin Phone <span style="color:var(--hms-danger)">*</span></label>
        <input type="text" name="admin_phone"
               class="hms-input @error('admin_phone') is-invalid @enderror"
               value="{{ old('admin_phone') }}" data-intl-phone
               placeholder="+919876543210" required>
        @error('admin_phone') <span class="hms-error">{{ $message }}</span> @enderror
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="hms-form-group">
            <label>Password <span style="color:var(--hms-danger)">*</span></label>
            <input type="password" name="password"
                   class="hms-input @error('password') is-invalid @enderror"
                   minlength="8" placeholder="Minimum 8 characters" required>
            @error('password') <span class="hms-error">{{ $message }}</span> @enderror
        </div>
        <div class="hms-form-group">
            <label>Confirm Password <span style="color:var(--hms-danger)">*</span></label>
            <input type="password" name="password_confirmation"
                   class="hms-input" minlength="8" placeholder="Repeat password" required>
        </div>
    </div>

    <div class="hms-form-group">
        <label>Plan <span style="color:var(--hms-danger)">*</span></label>
        <select name="plan" class="hms-select" required>
            <option value="monthly" {{ old('plan','monthly')=='monthly' ? 'selected':'' }}>Monthly</option>
            <option value="quarterly" {{ old('plan')=='quarterly' ? 'selected':'' }}>Quarterly</option>
            <option value="yearly" {{ old('plan')=='yearly' ? 'selected':'' }}>Yearly</option>
        </select>
        <span style="font-size:.75rem;color:var(--hms-text-muted)">
            Hospital will start on {{ platform_trial_label() }} free trial regardless.
        </span>
        @error('plan') <span class="hms-error">{{ $message }}</span> @enderror
    </div>

    <div id="{{ $formId }}FormError" class="hms-alert hms-alert-danger d-none" style="margin-top:1rem"></div>

    <div style="display:flex;gap:.75rem;margin-top:1.5rem">
        <button type="submit" class="hms-btn hms-btn-primary">
            <i class="bi bi-hospital-fill"></i> Create Hospital
        </button>
        <button type="button" class="hms-btn hms-btn-secondary" data-bs-dismiss="modal">
            Cancel
        </button>
    </div>
</form>
