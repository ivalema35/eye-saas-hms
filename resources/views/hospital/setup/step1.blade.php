@extends('hospital.setup.layout')

@section('wizard-content')
    <div class="wizard-card-header">
        <h2><i class="fa-solid fa-hospital" style="color:#1B4F72; margin-right:.3rem"></i> Hospital Profile</h2>
        <p>Let's start by confirming your hospital information.</p>
    </div>

    <form method="POST" action="{{ route('hospital.setup.store', ['slug' => $slug, 'step' => $step]) }}">
        @csrf

        <div class="wizard-card-body">
            <div class="form-group">
                <label class="form-label" for="name">Hospital Name <span style="color:#C0392B">*</span></label>
                <input type="text" id="name" name="name" class="form-input"
                       value="{{ old('name', $tenant->name) }}" required autofocus>
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem">
                <div class="form-group">
                    <label class="form-label" for="city">City</label>
                    <input type="text" id="city" name="city" class="form-input"
                           value="{{ old('city', $tenant->city) }}">
                    @error('city') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="state">State</label>
                    <input type="text" id="state" name="state" class="form-input"
                           value="{{ old('state', $tenant->state) }}">
                    @error('state') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="admin_phone">Admin Phone</label>
                <input type="text" id="admin_phone" name="admin_phone" class="form-input"
                       value="{{ old('admin_phone', $tenant->admin_phone) }}" data-intl-phone
                       placeholder="+919876543210">
                @error('admin_phone') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="hospital_address">Full Address <span style="color:#C0392B">*</span></label>
                <textarea id="hospital_address" name="hospital_address" class="form-input"
                          rows="2" placeholder="Building, Street, City, State, PIN"
                          style="resize:vertical">{{ old('hospital_address', \App\Models\Hospital\HospitalSetting::get('hospital_address', '')) }}</textarea>
                @error('hospital_address') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="wizard-actions">
            <button type="button" class="btn-wizard btn-wizard-skip" onclick="document.getElementById('skip-form').submit()">
                <i class="fa-solid fa-forward"></i> Skip
            </button>

            <button type="submit" class="btn-wizard btn-wizard-primary">
                Save &amp; Continue <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </form>

    <form id="skip-form" method="POST" action="{{ route('hospital.setup.skip', ['slug' => $slug, 'step' => $step]) }}" style="display:none">
        @csrf
    </form>
@endsection
