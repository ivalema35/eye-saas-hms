<div class="modal fade" id="addHospitalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:14px;border:none;box-shadow:0 25px 80px rgba(0,0,0,.2)">
            <div class="modal-header" style="border-bottom:1px solid rgba(27,79,114,.12);padding:1.25rem 1.5rem">
                <h5 class="modal-title"
                    style="font-weight:700;font-size:1rem;color:#1B4F72;display:flex;align-items:center;gap:.5rem">
                    <i class="bi bi-hospital-fill"></i>
                    Add New Hospital
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1.5rem">
                @include('superadmin.tenants._create_form', ['formId' => 'addHospital'])
            </div>
        </div>
    </div>
</div>

<style>
    #addHospitalSlugStatus.slug-ok,
    #addHospitalCodeStatus.slug-ok { color: #159a63; }
    #addHospitalSlugStatus.slug-taken,
    #addHospitalCodeStatus.slug-taken { color: #e11d48; }
    #addHospitalSlugStatus.slug-checking,
    #addHospitalCodeStatus.slug-checking { color: #64748b; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('addHospitalForm');
    var nameEl = document.getElementById('addHospitalName');
    var slugEl = document.getElementById('addHospitalSlug');
    var codeEl = document.getElementById('addHospitalCode');
    var mrdEl = document.getElementById('addHospitalMrdPreview');
    var slugStatus = document.getElementById('addHospitalSlugStatus');
    var codeStatus = document.getElementById('addHospitalCodeStatus');
    var formError = document.getElementById('addHospitalFormError');
    var modalEl = document.getElementById('addHospitalModal');
    var countryEl = document.getElementById('addHospitalCountry');
    var stateEl = document.getElementById('addHospitalState');
    var districtEl = document.getElementById('addHospitalDistrict');
    var cityEl = document.getElementById('addHospitalCity');

    var statesUrl = @json(route('location.states'));
    var districtsUrl = @json(route('location.districts'));
    var citiesUrl = @json(route('location.cities'));
    var checkSlugUrl = @json(route('check-slug'));
    var checkCodeUrl = @json(route('check-code'));

    var oldState = @json(old('state', ''));
    var oldDistrict = @json(old('district', ''));
    var oldCity = @json(old('city', ''));

    var slugTimer, codeTimer;

    function showError(msg) {
        if (!formError) return;
        formError.textContent = msg;
        formError.classList.remove('d-none');
    }

    function clearError() {
        if (!formError) return;
        formError.textContent = '';
        formError.classList.add('d-none');
    }

    function markField(el, invalid) {
        if (!el) return;
        el.classList.toggle('is-invalid', !!invalid);
    }

    function updateMrdPreview(code) {
        if (!mrdEl) return;
        var c = (code || '').toUpperCase();
        mrdEl.textContent = (c.length >= 3 ? c : '---') + '0001';
    }

    function checkCode(code) {
        clearTimeout(codeTimer);
        updateMrdPreview(code);
        if (!codeStatus) return;

        if (code.length < 3 || code.length > 4) {
            codeStatus.innerHTML = 'Must be 3 or 4 letters';
            codeStatus.className = 'slug-taken';
            return;
        }

        codeStatus.innerHTML = '<i class="bi bi-arrow-repeat"></i> Checking...';
        codeStatus.className = 'slug-checking';

        codeTimer = setTimeout(function () {
            fetch(checkCodeUrl + '?code=' + encodeURIComponent(code), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.available) {
                        codeStatus.innerHTML = '<i class="bi bi-check-circle-fill"></i> Available';
                        codeStatus.className = 'slug-ok';
                    } else {
                        codeStatus.innerHTML =
                            '<i class="bi bi-x-circle-fill"></i> ' +
                            (data.message || 'Already taken');
                        codeStatus.className = 'slug-taken';
                    }
                })
                .catch(function () { codeStatus.textContent = ''; });
        }, 400);
    }

    function checkSlug(slug) {
        clearTimeout(slugTimer);
        if (!slugStatus) return;

        if (slug.length < 3) {
            slugStatus.innerHTML = 'Minimum 3 characters required';
            slugStatus.className = 'slug-taken';
            return;
        }

        slugStatus.innerHTML = '<i class="bi bi-arrow-repeat"></i> Checking...';
        slugStatus.className = 'slug-checking';

        slugTimer = setTimeout(function () {
            fetch(checkSlugUrl + '?slug=' + encodeURIComponent(slug), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.available) {
                        slugStatus.innerHTML = '<i class="bi bi-check-circle-fill"></i> Available';
                        slugStatus.className = 'slug-ok';
                    } else {
                        var msg = '<i class="bi bi-x-circle-fill"></i> ' + (data.message || 'Not available');
                        if (data.suggestion) {
                            msg += ' — Try: <strong>' + data.suggestion + '</strong>';
                        }
                        slugStatus.innerHTML = msg;
                        slugStatus.className = 'slug-taken';
                    }
                })
                .catch(function () { slugStatus.textContent = ''; });
        }, 400);
    }

    function syncFromName(raw) {
        var slug = String(raw || '')
            .toLowerCase().trim()
            .replace(/[^a-z0-9\s\-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .substring(0, 30);
        if (slugEl && !slugEl.dataset.manual) {
            slugEl.value = slug;
            checkSlug(slug);
        }

        var code = String(raw || '').replace(/[^a-zA-Z]/g, '').substring(0, 4).toUpperCase();
        if (codeEl && !codeEl.dataset.manual) {
            codeEl.value = code;
            checkCode(code);
        }
    }

    if (nameEl) {
        nameEl.addEventListener('input', function () {
            syncFromName(this.value);
        });
        if (nameEl.value) syncFromName(nameEl.value);
    }

    if (slugEl) {
        slugEl.addEventListener('input', function () {
            this.dataset.manual = '1';
            this.value = this.value.toLowerCase().replace(/[^a-z0-9\-]/g, '');
            checkSlug(this.value);
        });
        if (slugEl.value) checkSlug(slugEl.value);
    }

    if (codeEl) {
        codeEl.addEventListener('input', function () {
            this.dataset.manual = '1';
            this.value = this.value.replace(/[^a-zA-Z]/g, '').toUpperCase().slice(0, 4);
            checkCode(this.value);
        });
        if (codeEl.value) checkCode(codeEl.value);
        else updateMrdPreview('');
    }

    function resetSelect(sel, placeholder, disable) {
        if (!sel) return;
        sel.innerHTML = '<option value="">' + placeholder + '</option>';
        sel.disabled = !!disable;
        sel.value = '';
    }

    function fillSelect(sel, rows, placeholder, selectedName) {
        if (!sel) return;
        sel.innerHTML = '<option value="">' + placeholder + '</option>';
        (rows || []).forEach(function (row) {
            var opt = document.createElement('option');
            opt.value = row.name;
            opt.textContent = row.name;
            opt.dataset.id = row.id;
            if (selectedName && selectedName === row.name) opt.selected = true;
            sel.appendChild(opt);
        });
        sel.disabled = false;
    }

    function fetchJson(url, params, cb) {
        var qs = new URLSearchParams(params).toString();
        fetch(url + (qs ? ('?' + qs) : ''), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(cb)
            .catch(function () { cb([]); });
    }

    if (countryEl) {
        countryEl.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            var countryId = opt ? opt.dataset.id : '';
            resetSelect(stateEl, '— Select State —', true);
            resetSelect(districtEl, '— Select District —', true);
            resetSelect(cityEl, '— Select City —', true);
            if (!countryId) return;
            fetchJson(statesUrl, { country_id: countryId }, function (rows) {
                fillSelect(stateEl, rows, '— Select State —', oldState);
                oldState = '';
                if (stateEl.value) stateEl.dispatchEvent(new Event('change'));
            });
        });
    }

    if (stateEl) {
        stateEl.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            var stateId = opt ? opt.dataset.id : '';
            resetSelect(districtEl, '— Select District —', true);
            resetSelect(cityEl, '— Select City —', true);
            if (!stateId) return;
            fetchJson(districtsUrl, { state_id: stateId }, function (rows) {
                fillSelect(districtEl, rows, '— Select District —', oldDistrict);
                oldDistrict = '';
                if (districtEl.value) districtEl.dispatchEvent(new Event('change'));
            });
        });
    }

    if (districtEl) {
        districtEl.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            var districtId = opt ? opt.dataset.id : '';
            resetSelect(cityEl, '— Select City —', true);
            if (!districtId) return;
            fetchJson(citiesUrl, { district_id: districtId }, function (rows) {
                fillSelect(cityEl, rows, '— Select City —', oldCity);
                oldCity = '';
            });
        });
    }

    // Restore cascade after validation error
    if (countryEl && countryEl.value) {
        countryEl.dispatchEvent(new Event('change'));
    }

    // Same client-side validation messages as free-trial register
    if (form) {
        form.addEventListener('submit', function (e) {
            clearError();

            var name = form.querySelector('[name="hospital_name"]');
            var slug = form.querySelector('[name="slug"]');
            var code = form.querySelector('[name="hospital_code"]');
            var country = form.querySelector('[name="country"]');
            var plan = form.querySelector('[name="plan"]');
            var adminName = form.querySelector('[name="admin_name"]');
            var adminEmail = form.querySelector('[name="admin_email"]');
            var adminPhone = form.querySelector('[name="admin_phone"]');
            var pass = form.querySelector('[name="password"]');
            var pass2 = form.querySelector('[name="password_confirmation"]');

            markField(name, !name.value.trim() || name.value.trim().length < 3);
            markField(slug, !slug.value.trim() || slug.value.trim().length < 3);
            markField(code, !/^[A-Za-z]{3,4}$/.test((code.value || '').trim()));
            markField(country, !(country && country.value.trim()));
            markField(plan, !(plan && plan.value));
            markField(adminName, !adminName.value.trim());
            markField(adminEmail, !adminEmail.value.trim() || !adminEmail.checkValidity());
            markField(adminPhone, !adminPhone.value.trim());
            markField(pass, !pass.value || pass.value.length < 8);
            markField(pass2, pass.value !== pass2.value);

            if (!name.value.trim() || name.value.trim().length < 3) {
                e.preventDefault();
                showError('Please enter hospital name (min 3 characters).');
                name.focus();
                return;
            }
            if (!slug.value.trim() || slug.value.trim().length < 3) {
                e.preventDefault();
                showError('Slug must be at least 3 characters.');
                slug.focus();
                return;
            }
            if (!/^[a-z0-9\-]+$/.test(slug.value.trim())) {
                e.preventDefault();
                showError('Slug may only contain lowercase letters, numbers, and hyphens.');
                slug.focus();
                return;
            }
            if (!/^[A-Za-z]{3,4}$/.test((code.value || '').trim())) {
                e.preventDefault();
                showError('Hospital code must be 3–4 letters.');
                code.focus();
                return;
            }
            if (!country || !country.value.trim()) {
                e.preventDefault();
                showError('Please select a country — plan prices depend on it.');
                country && country.focus();
                return;
            }
            if (!plan || !plan.value) {
                e.preventDefault();
                showError('Please select a plan.');
                plan && plan.focus();
                return;
            }
            if (!adminName.value.trim()) {
                e.preventDefault();
                showError('Please enter admin name.');
                adminName.focus();
                return;
            }
            if (!adminEmail.value.trim() || !adminEmail.checkValidity()) {
                e.preventDefault();
                showError('Please enter a valid admin email.');
                adminEmail.focus();
                return;
            }
            if (!adminPhone.value.trim()) {
                e.preventDefault();
                showError('Please enter phone number.');
                adminPhone.focus();
                return;
            }
            if (!pass.value || pass.value.length < 8) {
                e.preventDefault();
                showError('Password must be at least 8 characters.');
                pass.focus();
                return;
            }
            if (pass.value !== pass2.value) {
                e.preventDefault();
                showError('Password and confirm password do not match.');
                pass2.focus();
                return;
            }
        });
    }

    if (modalEl) {
        modalEl.addEventListener('shown.bs.modal', function () {
            if (window.HmsIntlPhone && typeof window.HmsIntlPhone.scan === 'function') {
                window.HmsIntlPhone.scan(modalEl);
            }
            if (nameEl) { nameEl.focus(); }
        });
    }

    @if(old('open_add_hospital_modal'))
        if (modalEl && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    @endif
});
</script>
