@extends('hospital.setup.layout')

@section('wizard-content')
    <div class="wizard-card-header">
        <h2><i class="fa-solid fa-briefcase-medical" style="color:#1B4F72; margin-right:.3rem"></i> Add Case Types &amp; Fees</h2>
        <p>Define the case types your hospital handles along with their consultation fees.</p>
    </div>

    <form method="POST" action="{{ route('hospital.setup.store', ['slug' => $slug, 'step' => $step]) }}" id="cases-form">
        @csrf

        <div class="wizard-card-body">
            <table style="width:100%; border-collapse:collapse" id="cases-table">
                <thead>
                    <tr>
                        <th style="text-align:left; font-size:.8125rem; font-weight:600; color:#374151; padding:.5rem .5rem .5rem 0; border-bottom:1.5px solid #E5E7EB">#</th>
                        <th style="text-align:left; font-size:.8125rem; font-weight:600; color:#374151; padding:.5rem; border-bottom:1.5px solid #E5E7EB">Case Name</th>
                        <th style="text-align:left; font-size:.8125rem; font-weight:600; color:#374151; padding:.5rem; border-bottom:1.5px solid #E5E7EB; width:140px">Fee (₹)</th>
                        <th style="width:44px; border-bottom:1.5px solid #E5E7EB"></th>
                    </tr>
                </thead>
                <tbody id="cases-body">
                    {{-- Rows injected by JS --}}
                </tbody>
            </table>

            <button type="button" id="add-row-btn"
                    style="margin-top:.75rem; background:none; border:1.5px dashed #93C5FD; color:#2563EB; padding:.5rem 1rem; border-radius:8px; font-size:.8125rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:.4rem; transition:background .15s"
                    onmouseover="this.style.background='#EFF6FF'" onmouseout="this.style.background='none'">
                <i class="fa-solid fa-plus"></i> Add Row
            </button>
        </div>

        <div class="wizard-actions">
            <button type="button" class="btn-wizard btn-wizard-skip" onclick="document.getElementById('skip-form').submit()">
                <i class="fa-solid fa-forward"></i> Skip for now
            </button>

            <button type="submit" class="btn-wizard btn-wizard-primary">
                <i class="fa-solid fa-check"></i> Finish Setup
            </button>
        </div>
    </form>

    <form id="skip-form" method="POST" action="{{ route('hospital.setup.skip', ['slug' => $slug, 'step' => $step]) }}" style="display:none">
        @csrf
    </form>
@endsection

@push('styles')
<style>
    .case-row td { padding: .4rem .5rem; vertical-align: middle; border-bottom: 1px solid #F1F5F9; }
    .case-row td:first-child { padding-left: 0; color: #9CA3AF; font-size: .8125rem; font-weight: 600; }
    .case-row .form-input { padding: .5rem .75rem; }
    .remove-row-btn {
        background: none; border: none; color: #D1D5DB; cursor: pointer; padding: .25rem;
        border-radius: 6px; transition: color .15s, background .15s; font-size: .875rem;
    }
    .remove-row-btn:hover { color: #C0392B; background: #FEF2F2; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('cases-body');
    const addBtn = document.getElementById('add-row-btn');

    // Defaults preferrably loaded from existing tenant cases, then old input, then fallback defaults
    const defaults = [
        { name: 'General OPD', fee: '300' },
        { name: 'Cataract', fee: '500' },
        { name: 'Glaucoma', fee: '500' },
        { name: 'Retina', fee: '600' },
    ];

    @if(old('cases'))
        const oldCases = @json(old('cases'));
        oldCases.forEach(function (c) { addRow(c.name || '', c.fee || ''); });
    @elseif(! empty($existingCases ?? []))
        const existingCases = @json($existingCases);
        existingCases.forEach(function (c) { addRow(c.name || '', c.fee || ''); });
    @else
        defaults.forEach(function (d) { addRow(d.name, d.fee); });
    @endif

    addBtn.addEventListener('click', function () {
        addRow('', '');
        const lastInput = tbody.querySelector('tr:last-child input[name$="[name]"]');
        if (lastInput) lastInput.focus();
    });

    function addRow(name, fee) {
        const idx = tbody.children.length;
        const tr = document.createElement('tr');
        tr.className = 'case-row';
        tr.innerHTML =
            '<td class="row-num">' + (idx + 1) + '</td>' +
            '<td><input type="text" name="cases[' + idx + '][name]" class="form-input" value="' + escHtml(name) + '" required placeholder="e.g. Cataract"></td>' +
            '<td><input type="number" name="cases[' + idx + '][fee]" class="form-input" value="' + escHtml(fee) + '" required min="0" step="1" placeholder="500"></td>' +
            '<td><button type="button" class="remove-row-btn" title="Remove"><i class="fa-solid fa-xmark"></i></button></td>';
        tbody.appendChild(tr);
        tr.querySelector('.remove-row-btn').addEventListener('click', function () {
            tr.remove();
            reindex();
        });
    }

    function reindex() {
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(function (r, i) {
            r.querySelector('.row-num').textContent = i + 1;
            r.querySelector('input[name$="[name]"]').name = 'cases[' + i + '][name]';
            r.querySelector('input[name$="[fee]"]').name = 'cases[' + i + '][fee]';
        });
    }

    function escHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }
});
</script>
@endpush
