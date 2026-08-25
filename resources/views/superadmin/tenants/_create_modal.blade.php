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

<script>
document.addEventListener('DOMContentLoaded', function () {
    var nameEl = document.getElementById('addHospitalName');
    var slugEl = document.getElementById('addHospitalSlug');
    var codeEl = document.getElementById('addHospitalCode');
    var modalEl = document.getElementById('addHospitalModal');

    function syncFromName(raw) {
        var slug = String(raw || '')
            .toLowerCase().trim()
            .replace(/[^a-z0-9\s\-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .substring(0, 30);
        if (slugEl) { slugEl.value = slug; }

        var code = String(raw || '').replace(/[^a-zA-Z]/g, '').substring(0, 3).toUpperCase();
        if (codeEl) { codeEl.value = code; }
    }

    if (nameEl) {
        nameEl.addEventListener('input', function () {
            syncFromName(this.value);
        });
        if (!slugEl.value && nameEl.value) {
            syncFromName(nameEl.value);
        } else if (codeEl && !codeEl.value && nameEl.value) {
            codeEl.value = String(nameEl.value).replace(/[^a-zA-Z]/g, '').substring(0, 3).toUpperCase();
        }
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
