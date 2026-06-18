@extends('landing.layouts.app')

@section('title', 'Register Your Hospital — Eye HMS SaaS')
@section('meta_description', 'Start your free 14-day trial of Eye HMS — complete hospital management software for eye clinics. No credit card required.')

@push('styles')
<style>
    .password-field-wrap {
        position: relative !important;
    }

    .password-field-input {
        padding-right: 3rem !important;
    }

    .password-field-toggle {
        position: absolute !important;
        right: .7rem;
        top: 50%;
        transform: translateY(-50%) !important;
        border: 1px solid rgba(0, 0, 0, .08) !important;
        background: #fff !important;
        padding: 0 !important;
        margin: 0 !important;
        color: #000 !important;
        width: 2rem !important;
        height: 2rem !important;
        border-radius: 999px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer !important;
        line-height: 1 !important;
        z-index: 3 !important;
        overflow: hidden !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
        transition: color .2s ease, border-color .2s ease, box-shadow .2s ease, transform .15s ease;
    }

    .password-field-toggle:hover {
        color: #000 !important;
        border-color: rgba(0, 0, 0, .16) !important;
        box-shadow: 0 4px 10px rgba(0, 0, 0, .08) !important;
        transform: translateY(-50%) scale(1.02) !important;
    }

    .password-field-toggle:focus-visible {
        outline: 2px solid rgba(0, 0, 0, .22) !important;
        outline-offset: 2px !important;
    }

    .password-field-toggle svg {
        width: 1rem !important;
        height: 1rem !important;
        display: block !important;
        stroke: currentColor !important;
        fill: none !important;
        color: #000 !important;
        pointer-events: none !important;
    }
</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.min.css">
<style>
    /* Tom Select — HMS form integration */
    .ts-wrapper { font-size: .9375rem; }
    .ts-wrapper .ts-control {
        border: 1px solid var(--hms-border, #dce3ed) !important;
        border-radius: var(--hms-radius-sm, 6px) !important;
        padding: .5rem .875rem !important;
        background: #fff !important;
        box-shadow: none !important;
        min-height: 42px;
        cursor: pointer;
        align-items: center;
    }
    .ts-wrapper.focus .ts-control {
        border-color: var(--hms-primary, #1B4F72) !important;
        box-shadow: 0 0 0 3px rgba(27,79,114,.1) !important;
        outline: none !important;
    }
    .ts-wrapper .ts-control input {
        font-size: .9375rem !important;
        color: var(--hms-text, #1a2535) !important;
        padding: 0 !important;
    }
    .ts-wrapper .ts-control .placeholder {
        color: var(--hms-text-muted, #8fa1b5) !important;
        font-size: .9375rem;
    }
    .ts-wrapper.disabled .ts-control {
        background: #f5f7fa !important;
        cursor: not-allowed !important;
        opacity: .65;
    }
    .ts-dropdown {
        border: 1px solid var(--hms-border, #dce3ed) !important;
        border-radius: var(--hms-radius-sm, 6px) !important;
        box-shadow: 0 4px 16px rgba(0,0,0,.1) !important;
        font-size: .9375rem;
    }
    .ts-dropdown .option { padding: .5rem .875rem; cursor: pointer; }
    .ts-dropdown .option.active {
        background: rgba(27,79,114,.08) !important;
        color: var(--hms-text, #1a2535) !important;
    }
    .ts-dropdown .option.selected {
        background: rgba(27,79,114,.14) !important;
        font-weight: 600;
    }
    .ts-dropdown .no-results {
        padding: .5rem .875rem;
        color: var(--hms-text-muted, #8fa1b5);
        font-size: .875rem;
    }
</style>
@endpush

@section('content')

<div class="reg-page-body">
    <div class="reg-body">

        {{-- Left Panel --}}
        <div class="reg-left-panel">
            <h3>Start Your Free 14-Day Trial</h3>
            <p style="font-size:.8125rem;color:rgba(255,255,255,.65);margin:0 0 1.75rem;line-height:1.6">
                No credit card required. Full access from day one.
            </p>

            <ul class="reg-benefits">
                <li>
                    <span class="rb-icon"><i class="fa-solid fa-check"></i></span>
                    <span>Complete Hospital Management System</span>
                </li>
                <li>
                    <span class="rb-icon"><i class="fa-solid fa-check"></i></span>
                    <span>Patient Records, Appointments &amp; Billing</span>
                </li>
                <li>
                    <span class="rb-icon"><i class="fa-solid fa-check"></i></span>
                    <span>Eye Examination &amp; OT Surgery Modules</span>
                </li>
                <li>
                    <span class="rb-icon"><i class="fa-solid fa-check"></i></span>
                    <span>Multi-user with Role-based Permissions</span>
                </li>
                <li>
                    <span class="rb-icon"><i class="fa-solid fa-check"></i></span>
                    <span>Secure Cloud — Access from Anywhere</span>
                </li>
                <li>
                    <span class="rb-icon"><i class="fa-solid fa-check"></i></span>
                    <span>Daily Automatic Backups</span>
                </li>
            </ul>

            <div class="reg-stat-grid">
                <div class="reg-stat-mini">
                    <span class="rsm-num">500+</span>
                    <span class="rsm-lbl">Hospitals</span>
                </div>
                <div class="reg-stat-mini">
                    <span class="rsm-num">14</span>
                    <span class="rsm-lbl">Days Free</span>
                </div>
                <div class="reg-stat-mini">
                    <span class="rsm-num">99.9%</span>
                    <span class="rsm-lbl">Uptime SLA</span>
                </div>
                <div class="reg-stat-mini">
                    <span class="rsm-num">24/7</span>
                    <span class="rsm-lbl">Support</span>
                </div>
            </div>

            <div style="margin-top:1.75rem;background:rgba(26,188,156,.15);border:1px solid rgba(26,188,156,.3);border-radius:var(--hms-radius);padding:.875rem 1rem;display:flex;align-items:center;gap:.625rem;font-size:.8125rem;color:rgba(255,255,255,.85)">
                <i class="fa-solid fa-gift" style="color:#1ABC9C"></i>
                <span>14 Days Free Trial — No Payment Required</span>
            </div>
        </div>

        {{-- Right: Form --}}
        <div class="reg-form-card">
            <div class="reg-form-head">
                <h2><i class="fa-solid fa-hospital-user" style="color:var(--hms-primary);margin-right:.5rem"></i>Create Your Account</h2>
                <p style="margin:0;font-size:.8125rem;color:var(--hms-text-muted)">All fields marked * are required</p>
            </div>

            <div class="reg-form-body">

                <!-- @if($errors->any())
                    <div style="background:var(--hms-danger-bg);border:1px solid rgba(192,57,43,.25);border-radius:var(--hms-radius);padding:.875rem 1rem;margin-bottom:1.25rem;display:flex;align-items:flex-start;gap:.75rem;color:var(--hms-danger)">
                        <i class="fa-solid fa-circle-exclamation" style="margin-top:.1rem;flex-shrink:0"></i>
                        <div style="font-size:.875rem">
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif -->

                <form method="POST" action="{{ route('register.store') }}" id="registerForm">
                    @csrf

                    {{-- Hospital Info --}}
                    <div class="reg-section">
                        <div class="reg-section-label"><i class="fa-solid fa-hospital"></i> Hospital Details</div>

                        <div class="hms-form-group">
                            <label>Hospital Name *</label>
                            <input type="text" name="hospital_name"
                                   class="hms-input @error('hospital_name') is-invalid @enderror"
                                   value="{{ old('hospital_name') }}"
                                   placeholder="e.g. Vision Eye Centre" required id="hospitalName">
                            @error('hospital_name')
                                <span class="hms-form-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="hms-form-group">
                            <label>Hospital URL Slug *</label>
                            <div class="slug-input-wrap">
                                <span class="slug-prefix">{{ parse_url(url('/'), PHP_URL_HOST) }}/</span>
                                <input type="text" name="slug" id="slugInput"
                                       class="slug-input-field @error('slug') is-invalid @enderror"
                                       value="{{ old('slug') }}"
                                       placeholder="vision-eye-centre" required
                                       pattern="[a-z0-9\-]+" minlength="3" maxlength="30">
                            </div>
                            <div class="slug-status" id="slugStatus"></div>
                            @error('slug')
                                <span class="hms-form-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-row-2">
                            <div class="hms-form-group">
                                <label>Country</label>
                                <select name="country" id="regCountry" class="hms-select">
                                    <option value="">-- Select Country --</option>
                                    @foreach($countries as $c)
                                    <option value="{{ $c->name }}" data-id="{{ $c->id }}"
                                        {{ old('country') === $c->name ? 'selected' : '' }}>
                                        {{ $c->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="hms-form-group">
                                <label>State</label>
                                <select name="state" id="regState" class="hms-select" disabled>
                                    <option value="">-- Select State --</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row-2">
                            <div class="hms-form-group">
                                <label>District</label>
                                <select name="district" id="regDistrict" class="hms-select" disabled>
                                    <option value="">-- Select District --</option>
                                </select>
                            </div>
                            <div class="hms-form-group">
                                <label>City</label>
                                <select name="city" id="regCity" class="hms-select" disabled>
                                    <option value="">-- Select City --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Admin Account --}}
                    <div class="reg-section">
                        <div class="reg-section-label"><i class="fa-solid fa-user-tie"></i> Admin Account</div>

                        <div class="form-row-2">
                            <div class="hms-form-group">
                                <label>Admin Name *</label>
                                <input type="text" name="admin_name"
                                       class="hms-input @error('admin_name') is-invalid @enderror"
                                       value="{{ old('admin_name') }}"
                                       placeholder="Dr. John Smith" required>
                                @error('admin_name')
                                    <span class="hms-form-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                            <div class="hms-form-group">
                                <label>Admin Email *</label>
                                <input type="email" name="admin_email"
                                       class="hms-input @error('admin_email') is-invalid @enderror"
                                       value="{{ old('admin_email') }}"
                                       placeholder="admin@hospital.com" required>
                                @error('admin_email')
                                    <span class="hms-form-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="hms-form-group">
                            <label>Phone Number *</label>
                            <input type="tel" name="admin_phone"
                            class="hms-input @error('admin_phone') is-invalid @enderror"
                            value="{{ old('admin_phone') }}"
                            placeholder="9876543210"
                            required
                            maxlength="10"
                            oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                            @error('admin_phone')
                                <span class="hms-form-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Security --}}
                    <div class="reg-section">
                        <div class="reg-section-label"><i class="fa-solid fa-lock"></i> Security</div>

                        <div class="form-row-2">
                            <div class="hms-form-group">
                                <label>Password *</label>
                                <div class="password-field-wrap">
                                    <input type="password" name="password" id="registerPassword"
                                           class="hms-input password-field-input @error('password') is-invalid @enderror"
                                           placeholder="Min 8 characters" required minlength="8"
                                           >
                                    <button type="button" id="toggleRegisterPassword" class="password-field-toggle" aria-label="Toggle password visibility">
                                        <svg id="regPassEye" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                                            <circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle>
                                        </svg>
                                    </button>
                                </div>
                                @error('password')
                                    <span class="hms-form-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                            <div class="hms-form-group">
                                <label>Confirm Password *</label>
                                <div class="password-field-wrap">
                                    <input type="password" name="password_confirmation" id="registerPasswordConfirm" class="hms-input password-field-input"
                                           placeholder="Repeat password" required minlength="8"
                                           >
                                    <button type="button" id="toggleRegisterPasswordConfirm" class="password-field-toggle" aria-label="Toggle confirm password visibility">
                                        <svg id="regPassConfirmEye" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                                            <circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Plan Selection --}}
                    <div class="reg-section">
                        <div class="reg-section-label"><i class="fa-solid fa-credit-card"></i> Plan After Trial</div>

                        <div class="plan-cards">
                            <label class="plan-card-label">
                                <input type="radio" name="plan" value="monthly"
                                    {{ old('plan', request('plan', 'monthly')) === 'monthly' ? 'checked' : '' }}>
                                <div class="plan-card-inner">
                                    <span class="pc-name">Monthly</span>
                                    <span class="pc-price">&#8377;999/mo</span>
                                </div>
                            </label>
                            <label class="plan-card-label">
                                <input type="radio" name="plan" value="quarterly"
                                    {{ old('plan', request('plan')) === 'quarterly' ? 'checked' : '' }}>
                                <div class="plan-card-inner">
                                    <span class="pc-name">Quarterly</span>
                                    <span class="pc-price">&#8377;2,697/qtr</span>
                                    <span class="pc-save">Save 10% &#9733; Popular</span>
                                </div>
                            </label>
                            <label class="plan-card-label">
                                <input type="radio" name="plan" value="yearly"
                                    {{ old('plan', request('plan')) === 'yearly' ? 'checked' : '' }}>
                                <div class="plan-card-inner">
                                    <span class="pc-name">Yearly</span>
                                    <span class="pc-price">&#8377;9,590/yr</span>
                                    <span class="pc-save">Save 20%</span>
                                </div>
                            </label>
                        </div>
                        @error('plan')
                            <span class="hms-form-error" style="margin-top:.5rem"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit"
                            class="hms-btn hms-btn-success hms-btn-block"
                            style="padding:.875rem;font-size:1rem;display:flex;align-items:center;justify-content:center;gap:.5rem;border-radius:var(--hms-radius);border:none;cursor:pointer;font-weight:700;margin-top:.25rem">
                        <i class="fa-solid fa-rocket"></i> Start 14-Day Free Trial
                    </button>

                    <p style="text-align:center;margin-top:1rem;font-size:.775rem;color:var(--hms-text-muted)">
                        By registering, you agree to our <a href="#" style="color:var(--hms-primary)">Terms of Service</a> and <a href="#" style="color:var(--hms-primary)">Privacy Policy</a>.
                    </p>
                </form>
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    var hospitalName = document.getElementById('hospitalName');
    var slugInput    = document.getElementById('slugInput');
    var slugStatus   = document.getElementById('slugStatus');
    var debounceTimer;

    function toSlug(str) {
        return str.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .substring(0, 30);
    }

    hospitalName.addEventListener('input', function () {
        var slug = toSlug(this.value);
        slugInput.value = slug;
        checkSlug(slug);
    });

    slugInput.addEventListener('input', function () {
        this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
        checkSlug(this.value);
    });

    function checkSlug(slug) {
        clearTimeout(debounceTimer);
        if (slug.length < 3) {
            slugStatus.innerHTML = 'Minimum 3 characters required';
            slugStatus.className = 'slug-status slug-taken';
            return;
        }
        slugStatus.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking...';
        slugStatus.className = 'slug-status slug-checking';

        debounceTimer = setTimeout(function () {
            fetch('{{ route("check-slug") }}?slug=' + encodeURIComponent(slug))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.available) {
                        slugStatus.innerHTML = '<i class="fa-solid fa-circle-check"></i> Available';
                        slugStatus.className = 'slug-status slug-ok';
                    } else {
                        var msg = '<i class="fa-solid fa-circle-xmark"></i> ' + (data.message || 'Not available');
                        if (data.suggestion) { msg += ' &mdash; Try: <strong>' + data.suggestion + '</strong>'; }
                        slugStatus.innerHTML = msg;
                        slugStatus.className = 'slug-status slug-taken';
                    }
                })
                .catch(function () { slugStatus.textContent = ''; });
        }, 400);
    }
}());
</script>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
(function () {
    // Country name → ID map for cascade (IDs come from server-rendered options)
    var countryIdMap = {
        @foreach($countries as $c)
        '{{ addslashes($c->name) }}': {{ $c->id }},
        @endforeach
    };

    function initTs(id, placeholder, disabled) {
        var ts = new TomSelect(id, {
            placeholder: placeholder,
            allowEmptyOption: true,
            create: false,
            maxOptions: 300,
        });
        if (disabled) ts.disable();
        return ts;
    }

    function resetTs(ts) {
        ts.clear(true);
        ts.clearOptions();
        ts.disable();
    }

    function populateTs(ts, items) {
        ts.addOptions(items.map(function (item) {
            return { value: item.name, text: item.name, locId: item.id };
        }));
        if (items.length > 0) ts.enable();
    }

    function cascadeFetch(url, params, callback) {
        var qs = Object.keys(params).map(function (k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
        }).join('&');
        fetch(url + '?' + qs)
            .then(function (r) { return r.json(); })
            .then(callback)
            .catch(function () {});
    }

    var tsCountry  = initTs('#regCountry',  '-- Select Country --',  false);
    var tsState    = initTs('#regState',    '-- Select State --',    true);
    var tsDistrict = initTs('#regDistrict', '-- Select District --', true);
    var tsCity     = initTs('#regCity',     '-- Select City --',     true);

    tsCountry.on('change', function (name) {
        resetTs(tsState);
        resetTs(tsDistrict);
        resetTs(tsCity);
        var id = name ? countryIdMap[name] : null;
        if (!id) return;
        cascadeFetch('{{ route("location.states") }}', { country_id: id }, function (data) {
            populateTs(tsState, data);
        });
    });

    tsState.on('change', function (name) {
        resetTs(tsDistrict);
        resetTs(tsCity);
        var opt = name ? tsState.options[name] : null;
        if (!opt) return;
        cascadeFetch('{{ route("location.districts") }}', { state_id: opt.locId }, function (data) {
            populateTs(tsDistrict, data);
        });
    });

    tsDistrict.on('change', function (name) {
        resetTs(tsCity);
        var opt = name ? tsDistrict.options[name] : null;
        if (!opt) return;
        cascadeFetch('{{ route("location.cities") }}', { district_id: opt.locId }, function (data) {
            populateTs(tsCity, data);
        });
    });
}());
</script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var pass = document.getElementById('registerPassword');
    var passConfirm = document.getElementById('registerPasswordConfirm');
    var togglePass = document.getElementById('toggleRegisterPassword');
    var togglePassConfirm = document.getElementById('toggleRegisterPasswordConfirm');
    var eye = document.getElementById('regPassEye');
    var eyeConfirm = document.getElementById('regPassConfirmEye');
    var eyeSvg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle></svg>';
    var eyeSlashSvg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18" stroke-width="1.8" stroke-linecap="round"></path><path d="M10.2 5.1A11.3 11.3 0 0 1 12 5c7 0 10.5 7 10.5 7a19 19 0 0 1-4.1 4.7" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M9.9 9.9A3.2 3.2 0 0 0 12 15.2c.5 0 1-.1 1.4-.3" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M7.1 7.4C4 9.6 1.5 12 1.5 12s3.5 7 10.5 7c1.7 0 3.2-.3 4.6-.8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>';

    if (togglePass && pass && eye) {
        togglePass.addEventListener('click', function () {
            var isPwd = pass.getAttribute('type') === 'password';
            pass.setAttribute('type', isPwd ? 'text' : 'password');
            eye.innerHTML = isPwd ? eyeSlashSvg : eyeSvg;
        });
    }

    if (togglePassConfirm && passConfirm && eyeConfirm) {
        togglePassConfirm.addEventListener('click', function () {
            var isPwd = passConfirm.getAttribute('type') === 'password';
            passConfirm.setAttribute('type', isPwd ? 'text' : 'password');
            eyeConfirm.innerHTML = isPwd ? eyeSlashSvg : eyeSvg;
        });
    }
});
</script>
@endpush
