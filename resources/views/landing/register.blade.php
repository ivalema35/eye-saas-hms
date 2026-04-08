@extends('landing.layouts.app')

@section('title', 'Register Your Hospital — Eye HMS SaaS')
@section('meta_description', 'Start your free 14-day trial of Eye HMS — complete hospital management software for eye clinics. No credit card required.')

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

                @if($errors->any())
                    <div style="background:var(--hms-danger-bg);border:1px solid rgba(192,57,43,.25);border-radius:var(--hms-radius);padding:.875rem 1rem;margin-bottom:1.25rem;display:flex;align-items:flex-start;gap:.75rem;color:var(--hms-danger)">
                        <i class="fa-solid fa-circle-exclamation" style="margin-top:.1rem;flex-shrink:0"></i>
                        <div style="font-size:.875rem">
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

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
                                <label>City</label>
                                <input type="text" name="city" class="hms-input"
                                       value="{{ old('city') }}" placeholder="Mumbai">
                            </div>
                            <div class="hms-form-group">
                                <label>State</label>
                                <input type="text" name="state" class="hms-input"
                                       value="{{ old('state') }}" placeholder="Maharashtra">
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
                                   placeholder="9876543210" required
                                   pattern="[0-9]{10}" maxlength="10">
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
                                <input type="password" name="password"
                                       class="hms-input @error('password') is-invalid @enderror"
                                       placeholder="Min 8 characters" required minlength="8">
                                @error('password')
                                    <span class="hms-form-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                            <div class="hms-form-group">
                                <label>Confirm Password *</label>
                                <input type="password" name="password_confirmation" class="hms-input"
                                       placeholder="Repeat password" required minlength="8">
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
