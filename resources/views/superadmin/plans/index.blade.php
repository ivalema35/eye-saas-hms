@extends('superadmin.layouts.app')

@section('title', 'Plan Management')
@section('page-header', 'Plan Management')

@section('page-actions')
    <button type="button" class="hms-btn hms-btn-primary" onclick="document.getElementById('editPricingModal').showModal()" style="color: #1b4f72;">
        <i class="bi bi-pencil-fill"></i> Edit Pricing
    </button>
@endsection

@section('content')

{{-- Live Price Preview Banner --}}
<div class="hms-card" style="margin-bottom:1.5rem">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:.875rem">
            <div style="width:2.5rem;height:2.5rem;border-radius:10px;background:rgba(27,79,114,.08);color:#1B4F72;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0">
                <i class="bi bi-layers-fill"></i>
            </div>
            <div>
                <div style="font-weight:700;color:#0D2137;font-size:.95rem;line-height:1.3">Subscription Plans — Live Pricing</div>
                <div style="font-size:.8rem;color:#64748B">One plan, all features. Hospitals choose their billing cycle.</div>
            </div>
        </div>
        <div style="font-size:.75rem;color:#64748B;white-space:nowrap">
            Trial: <strong>{{ $trialDays }}</strong> days &nbsp;|&nbsp;
            Grace: <strong>{{ $graceDays }}</strong> days
        </div>
    </div>
</div>

{{-- Plan Cards --}}
<div class="row g-4" style="margin-bottom:2rem">

    {{-- Monthly --}}
    <div class="col-lg-4">
        <div class="hms-card position-relative h-100">
            <span class="hms-badge" style="background:rgba(27,79,114,.08);color:#1B4F72;display:inline-block;margin-bottom:1rem">Monthly</span>
            <div style="display:flex;align-items:baseline;gap:.25rem;margin-bottom:.25rem">
                <span style="font-size:1.25rem;font-weight:700;color:#475569;margin-top:.5rem">{{ platform_currency_symbol() }}</span>
                <span style="font-size:2.75rem;font-weight:900;color:#0D2137;line-height:1;letter-spacing:-.03em">{{ number_format($monthlyPrice) }}</span>
                <span style="font-size:.875rem;color:#64748B;font-weight:500">/month</span>
            </div>
            <div style="min-height:1.4em;visibility:hidden;font-size:.8rem">—</div>
            <div style="font-size:.78rem;color:#94A3B8;margin-bottom:.25rem">Billed monthly &nbsp;·&nbsp; No commitment</div>
            <hr style="border-color:#E2E8F0;margin:1.25rem 0">
            <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.625rem">
                @foreach($features as $f)
                    <li style="display:flex;align-items:flex-start;gap:.5rem;font-size:.845rem;color:#334155;font-weight:500">
                        <i class="bi bi-check-circle-fill" style="color:#27AE60;flex-shrink:0;margin-top:.15rem;font-size:.8rem"></i> {{ $f }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Quarterly --}}
    <div class="col-lg-4">
        <div class="hms-card position-relative h-100" style="border-color:#1B4F72;box-shadow:0 8px 32px rgba(27,79,114,.15)">
            <div style="position:absolute;top:-14px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,#1B4F72,#2980B9);color:#fff;font-size:.72rem;font-weight:700;padding:.25rem .875rem;border-radius:999px;letter-spacing:.06em;text-transform:uppercase;white-space:nowrap">
                <i class="bi bi-star-fill"></i> Most Popular
            </div>
            <span class="hms-badge" style="background:rgba(27,79,114,.12);color:#1B4F72;display:inline-block;margin-bottom:1rem">Quarterly</span>
            <div style="display:flex;align-items:baseline;gap:.25rem;margin-bottom:.25rem">
                <span style="font-size:1.25rem;font-weight:700;color:#475569;margin-top:.5rem">{{ platform_currency_symbol() }}</span>
                <span style="font-size:2.75rem;font-weight:900;color:#0D2137;line-height:1;letter-spacing:-.03em">{{ number_format($quarterlyPrice) }}</span>
                <span style="font-size:.875rem;color:#64748B;font-weight:500">/3 months</span>
            </div>
            <div style="font-size:.8rem;font-weight:600;color:#27AE60;margin-bottom:.25rem;min-height:1.4em;display:flex;align-items:center;gap:.5rem">
                Save {{ platform_money($quarterlyOriginal - $quarterlyPrice) }}
                <span style="font-size:.65rem;background:rgba(39,174,96,.12);color:#1A6F5B;border:1px solid rgba(39,174,96,.2);padding:.1rem .5rem;border-radius:999px;font-weight:700">{{ $quarterlyDiscount }}% OFF</span>
            </div>
            <div style="font-size:.78rem;color:#94A3B8">
                <s style="color:#94A3B8">{{ platform_money($quarterlyOriginal) }}</s> &nbsp;·&nbsp; Billed every 3 months
            </div>
            <hr style="border-color:rgba(27,79,114,.15);margin:1.25rem 0">
            <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.625rem">
                @foreach($features as $f)
                    <li style="display:flex;align-items:flex-start;gap:.5rem;font-size:.845rem;color:#334155;font-weight:500">
                        <i class="bi bi-check-circle-fill" style="color:#27AE60;flex-shrink:0;margin-top:.15rem;font-size:.8rem"></i> {{ $f }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Yearly --}}
    <div class="col-lg-4">
        <div class="hms-card position-relative h-100">
            <span class="hms-badge" style="background:rgba(39,174,96,.1);color:#1A6F5B;display:inline-block;margin-bottom:1rem">Best Value</span>
            <div style="display:flex;align-items:baseline;gap:.25rem;margin-bottom:.25rem">
                <span style="font-size:1.25rem;font-weight:700;color:#475569;margin-top:.5rem">{{ platform_currency_symbol() }}</span>
                <span style="font-size:2.75rem;font-weight:900;color:#0D2137;line-height:1;letter-spacing:-.03em">{{ number_format($yearlyPrice) }}</span>
                <span style="font-size:.875rem;color:#64748B;font-weight:500">/year</span>
            </div>
            <div style="font-size:.8rem;font-weight:600;color:#27AE60;margin-bottom:.25rem;min-height:1.4em;display:flex;align-items:center;gap:.5rem">
                Save {{ platform_money($yearlyOriginal - $yearlyPrice) }}
                <span style="font-size:.65rem;background:rgba(39,174,96,.12);color:#1A6F5B;border:1px solid rgba(39,174,96,.2);padding:.1rem .5rem;border-radius:999px;font-weight:700">{{ $yearlyDiscount }}% OFF</span>
            </div>
            <div style="font-size:.78rem;color:#94A3B8">
                <s style="color:#94A3B8">{{ platform_money($yearlyOriginal) }}</s> &nbsp;·&nbsp; Billed annually
            </div>
            <hr style="border-color:#E2E8F0;margin:1.25rem 0">
            <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.625rem">
                @foreach($features as $f)
                    <li style="display:flex;align-items:flex-start;gap:.5rem;font-size:.845rem;color:#334155;font-weight:500">
                        <i class="bi bi-check-circle-fill" style="color:#27AE60;flex-shrink:0;margin-top:.15rem;font-size:.8rem"></i> {{ $f }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

</div>

{{-- ── Country-wise Price Overrides ── --}}
<div class="hms-card" style="margin-bottom:1.5rem;padding:0;overflow:hidden">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.1rem 1.25rem;border-bottom:1px solid rgba(15,79,134,.08);background:linear-gradient(180deg,#fafcfe,#fff)">
        <div style="display:flex;align-items:center;gap:.75rem">
            <div style="width:38px;height:38px;border-radius:11px;background:rgba(15,79,134,.1);color:#0f4f86;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0">
                <i class="bi bi-globe2"></i>
            </div>
            <div>
                <div style="font-weight:750;color:#0f4f86;font-size:.95rem">Country-wise Price Overrides</div>
                <div style="font-size:.72rem;color:#94a3b8">Set exact local prices per country. Falls back to FX conversion if not set.</div>
            </div>
        </div>
        <button type="button" class="hms-btn hms-btn-primary hms-btn-sm" onclick="document.getElementById('addCountryPriceModal').showModal()">
            <i class="bi bi-plus-lg"></i> Add Country Price
        </button>
    </div>

    @if($countryPrices->isEmpty())
        <div style="padding:2rem;text-align:center;color:#94a3b8;font-size:.875rem">
            <i class="bi bi-globe" style="font-size:1.5rem;display:block;margin-bottom:.5rem;opacity:.4"></i>
            No country-specific prices set — FX conversion is used for all countries.
        </div>
    @else
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.875rem">
                <thead>
                    <tr style="background:rgba(15,79,134,.03)">
                        <th style="padding:.7rem 1.15rem;text-align:left;font-size:.7rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em;color:#6b7f93;border-bottom:1px solid rgba(15,79,134,.08)">Country</th>
                        <th style="padding:.7rem 1.15rem;text-align:right;font-size:.7rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em;color:#6b7f93;border-bottom:1px solid rgba(15,79,134,.08)">Monthly</th>
                        <th style="padding:.7rem 1.15rem;text-align:right;font-size:.7rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em;color:#6b7f93;border-bottom:1px solid rgba(15,79,134,.08)">Quarterly</th>
                        <th style="padding:.7rem 1.15rem;text-align:right;font-size:.7rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em;color:#6b7f93;border-bottom:1px solid rgba(15,79,134,.08)">Yearly</th>
                        <th style="padding:.7rem 1.15rem;text-align:right;font-size:.7rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em;color:#6b7f93;border-bottom:1px solid rgba(15,79,134,.08)">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($countryPrices as $countryId => $prices)
                        @php
                            $priceByC = $prices->keyBy('cycle');
                            $countryName = $priceByC->first()?->country?->name ?? '—';
                            $currencySymbol = $priceByC->first()?->country?->currency_symbol ?? '';
                        @endphp
                        <tr class="cp-row" data-country-id="{{ $countryId }}" style="border-bottom:1px solid rgba(15,79,134,.05);transition:background .15s">
                            <td style="padding:.85rem 1.15rem;font-weight:600;color:#0a1628">{{ $countryName }}</td>
                            <td style="padding:.85rem 1.15rem;text-align:right;color:#334155">
                                {{ $currencySymbol }}{{ number_format($priceByC->get('monthly')?->price ?? 0) }}
                            </td>
                            <td style="padding:.85rem 1.15rem;text-align:right;color:#334155">
                                {{ $currencySymbol }}{{ number_format($priceByC->get('quarterly')?->price ?? 0) }}
                            </td>
                            <td style="padding:.85rem 1.15rem;text-align:right;color:#334155">
                                {{ $currencySymbol }}{{ number_format($priceByC->get('yearly')?->price ?? 0) }}
                            </td>
                            <td style="padding:.85rem 1.15rem;text-align:right">
                                <button type="button"
                                    class="hms-btn-icon cp-edit-btn"
                                    title="Edit"
                                    data-country-id="{{ $countryId }}"
                                    data-country-name="{{ $countryName }}"
                                    data-monthly="{{ $priceByC->get('monthly')?->price ?? 0 }}"
                                    data-quarterly="{{ $priceByC->get('quarterly')?->price ?? 0 }}"
                                    data-yearly="{{ $priceByC->get('yearly')?->price ?? 0 }}"
                                    onclick="openEditCountryPrice(this)">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <button type="button"
                                    class="hms-btn-icon"
                                    title="Delete"
                                    style="background:rgba(192,57,43,.08);color:#c0392b;margin-left:.25rem"
                                    onclick="deleteCountryPrice({{ $countryId }}, '{{ $countryName }}')">
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Add/Edit Country Price Modal --}}
<dialog id="addCountryPriceModal" style="border:none;border-radius:16px;padding:0;width:100%;max-width:520px;box-shadow:0 25px 80px rgba(0,0,0,.22)">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:1.15rem 1.5rem;border-bottom:1px solid rgba(15,79,134,.08);background:linear-gradient(180deg,#fafcfe,#fff)">
        <h5 style="margin:0;font-size:1rem;font-weight:750;color:#0f4f86;display:flex;align-items:center;gap:.5rem">
            <i class="bi bi-globe2"></i>
            <span id="cpModalTitle">Add Country Price Override</span>
        </h5>
        <button type="button" onclick="document.getElementById('addCountryPriceModal').close()" style="background:none;border:none;font-size:1.1rem;color:#94a3b8;cursor:pointer;padding:.25rem .4rem">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div style="padding:1.5rem">
        <input type="hidden" id="cpCountryIdInput">
        <div class="hms-form-group" style="margin-bottom:1rem">
            <label class="hms-label">Country</label>
            <select id="cpCountrySelect" class="hms-input">
                <option value="">— Select country —</option>
                @foreach($countries as $c)
                    <option value="{{ $c->id }}" data-symbol="{{ $c->currency_symbol ?? '' }}" data-code="{{ $c->currency_code ?? 'INR' }}">
                        {{ $c->name }} ({{ $c->currency_code ?? 'INR' }})
                    </option>
                @endforeach
            </select>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem">
            <div class="hms-form-group">
                <label class="hms-label">Monthly <span id="cpSymbol" style="color:#94a3b8"></span></label>
                <input type="number" id="cpMonthly" class="hms-input" min="0" step="1" placeholder="0">
            </div>
            <div class="hms-form-group">
                <label class="hms-label">Quarterly</label>
                <input type="number" id="cpQuarterly" class="hms-input" min="0" step="1" placeholder="0">
            </div>
            <div class="hms-form-group">
                <label class="hms-label">Yearly</label>
                <input type="number" id="cpYearly" class="hms-input" min="0" step="1" placeholder="0">
            </div>
        </div>
        <p style="font-size:.75rem;color:#94a3b8;margin-top:.75rem;margin-bottom:0">
            <i class="bi bi-info-circle"></i>
            Enter exact prices in the country's local currency. Leave all 0 to remove override.
        </p>
    </div>
    <div style="padding:1rem 1.5rem;border-top:1px solid rgba(15,79,134,.08);display:flex;justify-content:flex-end;gap:.75rem">
        <button type="button" class="hms-btn hms-btn-outline" onclick="document.getElementById('addCountryPriceModal').close()">Cancel</button>
        <button type="button" class="hms-btn hms-btn-primary" onclick="saveCountryPrice()">
            <i class="bi bi-floppy-fill"></i> Save Prices
        </button>
    </div>
</dialog>

{{-- ── Edit Pricing Modal (native <dialog>) ── --}}
<dialog id="editPricingModal" style="border:none;border-radius:16px;padding:0;width:100%;max-width:640px;box-shadow:0 25px 80px rgba(0,0,0,.25)">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #E2E8F0">
        <h5 style="margin:0;font-size:1rem;font-weight:700;color:#1B4F72;display:flex;align-items:center;gap:.5rem">
            <i class="bi bi-pencil-fill"></i> Edit Plan Pricing
        </h5>
        <button type="button" class="hms-btn hms-btn-outline" style="padding:.3rem .55rem;line-height:1;font-size:.9rem"
                onclick="document.getElementById('editPricingModal').close()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <form method="POST" action="{{ route('superadmin.plans.update', 'pricing') }}">
        @csrf
        @method('PUT')

        <div style="padding:1.5rem;max-height:65vh;overflow-y:auto">

            {{-- Pricing --}}
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748B;margin-bottom:.625rem;padding-bottom:.375rem;border-bottom:1px solid #E2E8F0">Pricing</div>
            <div class="row g-2" style="margin-bottom:.875rem">
                <div class="col-4">
                    <div class="hms-form-group">
                        <label class="hms-label">Monthly Price ({{ platform_currency_symbol() }})</label>
                        <input type="number" name="monthly_price" class="hms-input"
                               value="{{ $monthlyPrice }}" min="1" max="99999" required>
                    </div>
                </div>
                <div class="col-4">
                    <div class="hms-form-group">
                        <label class="hms-label">Quarterly Discount (%)</label>
                        <input type="number" name="quarterly_discount" class="hms-input"
                               value="{{ $quarterlyDiscount }}" min="0" max="70" required>
                    </div>
                </div>
                <div class="col-4">
                    <div class="hms-form-group">
                        <label class="hms-label">Yearly Discount (%)</label>
                        <input type="number" name="yearly_discount" class="hms-input"
                               value="{{ $yearlyDiscount }}" min="0" max="70" required>
                    </div>
                </div>
            </div>

            {{-- Trial & Grace --}}
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748B;margin-top:1rem;margin-bottom:.625rem;padding-bottom:.375rem;border-bottom:1px solid #E2E8F0">Trial & Grace Period</div>
            <div class="row g-2" style="margin-bottom:.875rem">
                <div class="col-6">
                    <div class="hms-form-group">
                        <label class="hms-label">Trial Days</label>
                        <input type="number" name="trial_days" class="hms-input"
                               value="{{ $trialDays }}" min="1" max="90" required>
                        <span class="hms-hint">Days of free access after registration</span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="hms-form-group">
                        <label class="hms-label">Grace Period Days</label>
                        <input type="number" name="grace_days" class="hms-input"
                               value="{{ $graceDays }}" min="1" max="30" required>
                        <span class="hms-hint">Read-only access after trial/subscription expires</span>
                    </div>
                </div>
            </div>

            {{-- Features List --}}
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748B;margin-top:1rem;margin-bottom:.625rem;padding-bottom:.375rem;border-bottom:1px solid #E2E8F0">Features Included (shown on all plans)</div>
            <div id="featuresContainer">
                @foreach($features as $i => $f)
                    <div class="sa-feature-row">
                        <input type="text" name="features[]" class="hms-input" value="{{ $f }}" placeholder="Feature description">
                        <button type="button" class="sa-remove-feature" onclick="this.parentElement.remove()">
                            <i class="bi bi-trash3-fill"></i>
                        </button>
                    </div>
                @endforeach
            </div>
            <button type="button" class="sa-add-feature-btn" onclick="addFeatureRow()">
                <i class="bi bi-plus-lg"></i> Add Feature
            </button>

        </div>

        <div style="padding:1rem 1.5rem;border-top:1px solid #E2E8F0;display:flex;justify-content:flex-end;gap:.75rem">
            <button type="button" class="hms-btn hms-btn-outline"
                    onclick="document.getElementById('editPricingModal').close()">
                Cancel
            </button>
            <button type="submit" class="hms-btn hms-btn-primary">
                <i class="bi bi-floppy-fill"></i> Save Pricing
            </button>
        </div>
    </form>
</dialog>

@endsection

@push('styles')
<style>
/* Native <dialog> backdrop — cannot be set inline */
#editPricingModal::backdrop { background: rgba(13,33,55,.55); backdrop-filter: blur(4px); }

/* Used by addFeatureRow() JS function — class names must not change */
.sa-feature-row { display: flex; gap: .5rem; margin-bottom: .5rem; align-items: center; }
.sa-remove-feature {
    background: none; border: 1px solid #FECACA; color: #C0392B;
    border-radius: 8px; padding: .45rem .6rem; cursor: pointer; flex-shrink: 0; transition: all .15s;
}
.sa-remove-feature:hover { background: #FECACA; }
.sa-add-feature-btn {
    display: flex; align-items: center; gap: .375rem;
    background: none; border: 1px dashed #CBD5E1; color: #64748B;
    border-radius: 8px; padding: .45rem .875rem; cursor: pointer; font-size: .85rem;
    margin-top: .375rem; transition: all .15s; width: 100%; justify-content: center;
}
.sa-add-feature-btn:hover { border-color: #1B4F72; color: #1B4F72; background: rgba(27,79,114,.04); }
</style>
@endpush

@push('scripts')
<script>
// ── Country Price Modal ───────────────────────────────────────────────────
var CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

document.getElementById('cpCountrySelect').addEventListener('change', function () {
    var opt = this.options[this.selectedIndex];
    var sym = opt.getAttribute('data-symbol') || '';
    document.getElementById('cpSymbol').textContent = sym ? '(' + sym + ')' : '';
});

function openEditCountryPrice(btn) {
    var d = btn.dataset;
    document.getElementById('cpModalTitle').textContent = 'Edit — ' + d.countryName;
    document.getElementById('cpCountryIdInput').value = d.countryId;
    var sel = document.getElementById('cpCountrySelect');
    sel.value = d.countryId;
    sel.dispatchEvent(new Event('change'));
    sel.disabled = true;
    document.getElementById('cpMonthly').value = d.monthly;
    document.getElementById('cpQuarterly').value = d.quarterly;
    document.getElementById('cpYearly').value = d.yearly;
    document.getElementById('addCountryPriceModal').showModal();
}

document.getElementById('addCountryPriceModal').addEventListener('close', function () {
    document.getElementById('cpModalTitle').textContent = 'Add Country Price Override';
    document.getElementById('cpCountryIdInput').value = '';
    document.getElementById('cpCountrySelect').disabled = false;
    document.getElementById('cpCountrySelect').value = '';
    document.getElementById('cpMonthly').value = '';
    document.getElementById('cpQuarterly').value = '';
    document.getElementById('cpYearly').value = '';
    document.getElementById('cpSymbol').textContent = '';
});

function saveCountryPrice() {
    var countryId = document.getElementById('cpCountryIdInput').value
                 || document.getElementById('cpCountrySelect').value;
    if (!countryId) { alert('Please select a country.'); return; }
    var monthly   = parseFloat(document.getElementById('cpMonthly').value)   || 0;
    var quarterly = parseFloat(document.getElementById('cpQuarterly').value) || 0;
    var yearly    = parseFloat(document.getElementById('cpYearly').value)    || 0;
    if (!monthly && !quarterly && !yearly) { alert('Enter at least one price.'); return; }

    fetch('{{ route("superadmin.plans.country-price.save") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ country_id: countryId, monthly: monthly, quarterly: quarterly, yearly: yearly })
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (data.success) {
            document.getElementById('addCountryPriceModal').close();
            window.location.reload();
        } else {
            alert(data.message || 'Error saving prices.');
        }
    })
    .catch(function () { alert('Network error.'); });
}

function deleteCountryPrice(countryId, countryName) {
    if (!confirm('Remove price override for ' + countryName + '?')) return;
    fetch('{{ route("superadmin.plans.country-price.delete") }}', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ country_id: countryId })
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (data.success) { window.location.reload(); }
    });
}

// ── Hover highlight on table rows ───────────────────────────────────────
document.querySelectorAll('.cp-row').forEach(function (row) {
    row.addEventListener('mouseenter', function () { this.style.background = 'rgba(15,79,134,.03)'; });
    row.addEventListener('mouseleave', function () { this.style.background = ''; });
});

// ── Plan Feature rows ────────────────────────────────────────────────────
function addFeatureRow() {
    var container = document.getElementById('featuresContainer');
    var row = document.createElement('div');
    row.className = 'sa-feature-row';
    row.innerHTML = '<input type="text" name="features[]" class="hms-input" placeholder="Feature description">' +
        '<button type="button" class="sa-remove-feature" onclick="this.parentElement.remove()">' +
        '<i class="bi bi-trash3-fill"></i></button>';
    container.appendChild(row);
    row.querySelector('input').focus();
}

// Open modal if there are validation errors on return
@if($errors->any())
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('editPricingModal').showModal();
});
@endif
</script>
@endpush
