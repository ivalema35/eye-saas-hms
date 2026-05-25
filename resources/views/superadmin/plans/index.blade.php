@extends('superadmin.layouts.app')

@section('title', 'Plan Management')
@section('page-header', 'Plan Management')

@section('page-actions')
    <button type="button" class="sa-premium-btn sa-premium-btn-primary" onclick="document.getElementById('editPricingModal').showModal()">
        <i class="fa-solid fa-pen-to-square"></i> Edit Pricing
    </button>
@endsection

@section('content')

{{-- Live Price Preview Banner --}}
<div class="sa-premium-card" style="margin-bottom:1.5rem">
    <div class="sa-premium-card-header">
        <div class="sa-premium-card-header-left">
            <div class="sa-premium-card-icon" style="background:rgba(27,79,114,.08);color:#1B4F72">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <div class="sa-premium-card-title">Subscription Plans — Live Pricing</div>
                <div class="sa-premium-card-subtitle">One plan, all features. Hospitals choose their billing cycle.</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:.5rem">
            <span style="font-size:.75rem;color:#64748B">
                Trial: <strong>{{ $trialDays }}</strong> days &nbsp;|&nbsp;
                Grace: <strong>{{ $graceDays }}</strong> days
            </span>
        </div>
    </div>
</div>

{{-- Plan Cards --}}
<div class="sa-plans-grid">

    {{-- Monthly --}}
    <div class="sa-plan-card">
        <div class="sa-plan-badge">Monthly</div>
        <div class="sa-plan-price">
            <span class="sa-plan-currency">₹</span>
            <span class="sa-plan-amount">{{ number_format($monthlyPrice) }}</span>
            <span class="sa-plan-period">/month</span>
        </div>
        <div class="sa-plan-savings" style="visibility:hidden">—</div>
        <div class="sa-plan-cycle-info">Billed monthly &nbsp;·&nbsp; No commitment</div>
        <hr style="border-color:#E2E8F0;margin:1.25rem 0">
        <ul class="sa-plan-features">
            @foreach($features as $f)
                <li><i class="fa-solid fa-circle-check"></i> {{ $f }}</li>
            @endforeach
        </ul>
    </div>

    {{-- Quarterly --}}
    <div class="sa-plan-card sa-plan-card-popular">
        <div class="sa-plan-popular-badge"><i class="fa-solid fa-star"></i> Most Popular</div>
        <div class="sa-plan-badge sa-plan-badge-popular">Quarterly</div>
        <div class="sa-plan-price">
            <span class="sa-plan-currency">₹</span>
            <span class="sa-plan-amount">{{ number_format($quarterlyPrice) }}</span>
            <span class="sa-plan-period">/3 months</span>
        </div>
        <div class="sa-plan-savings">
            Save ₹{{ number_format($quarterlyOriginal - $quarterlyPrice) }}
            <span class="sa-plan-discount-badge">{{ $quarterlyDiscount }}% OFF</span>
        </div>
        <div class="sa-plan-cycle-info">
            <s style="color:#94A3B8">₹{{ number_format($quarterlyOriginal) }}</s> &nbsp;·&nbsp; Billed every 3 months
        </div>
        <hr style="border-color:rgba(27,79,114,.15);margin:1.25rem 0">
        <ul class="sa-plan-features">
            @foreach($features as $f)
                <li><i class="fa-solid fa-circle-check"></i> {{ $f }}</li>
            @endforeach
        </ul>
    </div>

    {{-- Yearly --}}
    <div class="sa-plan-card">
        <div class="sa-plan-badge sa-plan-badge-value">Best Value</div>
        <div class="sa-plan-price">
            <span class="sa-plan-currency">₹</span>
            <span class="sa-plan-amount">{{ number_format($yearlyPrice) }}</span>
            <span class="sa-plan-period">/year</span>
        </div>
        <div class="sa-plan-savings">
            Save ₹{{ number_format($yearlyOriginal - $yearlyPrice) }}
            <span class="sa-plan-discount-badge sa-plan-discount-badge-green">{{ $yearlyDiscount }}% OFF</span>
        </div>
        <div class="sa-plan-cycle-info">
            <s style="color:#94A3B8">₹{{ number_format($yearlyOriginal) }}</s> &nbsp;·&nbsp; Billed annually
        </div>
        <hr style="border-color:#E2E8F0;margin:1.25rem 0">
        <ul class="sa-plan-features">
            @foreach($features as $f)
                <li><i class="fa-solid fa-circle-check"></i> {{ $f }}</li>
            @endforeach
        </ul>
    </div>

</div>

{{-- ── Edit Pricing Modal (native <dialog>) ── --}}
<dialog id="editPricingModal" class="sa-modal">
    <div class="sa-modal-header">
        <h4><i class="fa-solid fa-pen-to-square"></i> Edit Plan Pricing</h4>
        <button type="button" class="sa-modal-close" onclick="document.getElementById('editPricingModal').close()">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <form method="POST" action="{{ route('superadmin.plans.update', 'pricing') }}">
        @csrf
        @method('PUT')

        <div class="sa-modal-body">

            {{-- Pricing --}}
            <div class="sa-modal-section-label">Pricing</div>
            <div class="sa-modal-grid-3">
                <div class="hms-form-group">
                    <label class="hms-label">Monthly Price (₹)</label>
                    <input type="number" name="monthly_price" class="hms-input"
                           value="{{ $monthlyPrice }}" min="1" max="99999" required>
                </div>
                <div class="hms-form-group">
                    <label class="hms-label">Quarterly Discount (%)</label>
                    <input type="number" name="quarterly_discount" class="hms-input"
                           value="{{ $quarterlyDiscount }}" min="0" max="70" required>
                </div>
                <div class="hms-form-group">
                    <label class="hms-label">Yearly Discount (%)</label>
                    <input type="number" name="yearly_discount" class="hms-input"
                           value="{{ $yearlyDiscount }}" min="0" max="70" required>
                </div>
            </div>

            {{-- Trial & Grace --}}
            <div class="sa-modal-section-label" style="margin-top:1rem">Trial & Grace Period</div>
            <div class="sa-modal-grid-2">
                <div class="hms-form-group">
                    <label class="hms-label">Trial Days</label>
                    <input type="number" name="trial_days" class="hms-input"
                           value="{{ $trialDays }}" min="1" max="90" required>
                    <span class="hms-hint">Days of free access after registration</span>
                </div>
                <div class="hms-form-group">
                    <label class="hms-label">Grace Period Days</label>
                    <input type="number" name="grace_days" class="hms-input"
                           value="{{ $graceDays }}" min="1" max="30" required>
                    <span class="hms-hint">Read-only access after trial/subscription expires</span>
                </div>
            </div>

            {{-- Features List --}}
            <div class="sa-modal-section-label" style="margin-top:1rem">Features Included (shown on all plans)</div>
            <div id="featuresContainer">
                @foreach($features as $i => $f)
                    <div class="sa-feature-row">
                        <input type="text" name="features[]" class="hms-input" value="{{ $f }}" placeholder="Feature description">
                        <button type="button" class="sa-remove-feature" onclick="this.parentElement.remove()">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                @endforeach
            </div>
            <button type="button" class="sa-add-feature-btn" onclick="addFeatureRow()">
                <i class="fa-solid fa-plus"></i> Add Feature
            </button>

        </div>

        <div class="sa-modal-footer">
            <button type="button" class="sa-premium-btn sa-premium-btn-secondary"
                    onclick="document.getElementById('editPricingModal').close()">
                Cancel
            </button>
            <button type="submit" class="sa-premium-btn sa-premium-btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Save Pricing
            </button>
        </div>
    </form>
</dialog>

@endsection

@push('styles')
<style>
/* ── Plan Cards Grid ── */
.sa-plans-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
    align-items: start;
}
@media (max-width: 992px) { .sa-plans-grid { grid-template-columns: 1fr; max-width: 480px; } }

.sa-plan-card {
    background: #fff;
    border: 2px solid #E2E8F0;
    border-radius: 18px;
    padding: 1.75rem 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
    transition: transform .25s, box-shadow .25s;
    position: relative;
}
.sa-plan-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,.1); }
.sa-plan-card-popular {
    border-color: #1B4F72;
    box-shadow: 0 8px 32px rgba(27,79,114,.15);
}
.sa-plan-popular-badge {
    position: absolute;
    top: -14px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #1B4F72, #2980B9);
    color: #fff;
    font-size: .72rem;
    font-weight: 700;
    padding: .25rem .875rem;
    border-radius: 999px;
    letter-spacing: .06em;
    text-transform: uppercase;
    white-space: nowrap;
}

.sa-plan-badge {
    display: inline-block;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    padding: .2rem .75rem;
    border-radius: 999px;
    background: rgba(27,79,114,.08);
    color: #1B4F72;
    margin-bottom: 1rem;
}
.sa-plan-badge-popular { background: rgba(27,79,114,.12); color: #1B4F72; }
.sa-plan-badge-value   { background: rgba(39,174,96,.1); color: #1A6F5B; }

.sa-plan-price { display: flex; align-items: baseline; gap: .25rem; margin-bottom: .25rem; }
.sa-plan-currency { font-size: 1.25rem; font-weight: 700; color: #475569; margin-top: .5rem; }
.sa-plan-amount   { font-size: 2.75rem; font-weight: 900; color: #0D2137; line-height: 1; letter-spacing: -.03em; }
.sa-plan-period   { font-size: .875rem; color: #64748B; font-weight: 500; }

.sa-plan-savings {
    font-size: .8rem;
    font-weight: 600;
    color: #27AE60;
    margin-bottom: .25rem;
    min-height: 1.4em;
    display: flex;
    align-items: center;
    gap: .5rem;
}
.sa-plan-discount-badge {
    font-size: .65rem;
    background: rgba(39,174,96,.12);
    color: #1A6F5B;
    border: 1px solid rgba(39,174,96,.2);
    padding: .1rem .5rem;
    border-radius: 999px;
    font-weight: 700;
}
.sa-plan-discount-badge-green { /* same as above */ }

.sa-plan-cycle-info { font-size: .78rem; color: #94A3B8; }

.sa-plan-features { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: .625rem; }
.sa-plan-features li {
    display: flex; align-items: flex-start; gap: .5rem;
    font-size: .845rem; color: #334155; font-weight: 500;
}
.sa-plan-features li i { color: #27AE60; flex-shrink: 0; margin-top: .15rem; font-size: .8rem; }

/* ── Modal ── */
.sa-modal {
    border: none;
    border-radius: 18px;
    padding: 0;
    width: 100%;
    max-width: 640px;
    box-shadow: 0 25px 80px rgba(0,0,0,.25);
}
.sa-modal::backdrop { background: rgba(13,33,55,.55); backdrop-filter: blur(4px); }
.sa-modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1.25rem 1.5rem; border-bottom: 1px solid #E2E8F0;
}
.sa-modal-header h4 { margin: 0; font-size: 1rem; font-weight: 700; color: #1B4F72; display:flex;align-items:center;gap:.5rem; }
.sa-modal-close { background: none; border: none; font-size: 1.1rem; color: #94A3B8; cursor: pointer; padding: .25rem; border-radius: 6px; }
.sa-modal-close:hover { background: #F0F4F8; color: #1B4F72; }
.sa-modal-body { padding: 1.5rem; max-height: 65vh; overflow-y: auto; }
.sa-modal-footer { padding: 1rem 1.5rem; border-top: 1px solid #E2E8F0; display: flex; justify-content: flex-end; gap: .75rem; }

.sa-modal-section-label {
    font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em;
    color: #64748B; margin-bottom: .625rem; padding-bottom: .375rem; border-bottom: 1px solid #E2E8F0;
}
.sa-modal-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: .875rem; }
.sa-modal-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: .875rem; }

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
function addFeatureRow() {
    var container = document.getElementById('featuresContainer');
    var row = document.createElement('div');
    row.className = 'sa-feature-row';
    row.innerHTML = '<input type="text" name="features[]" class="hms-input" placeholder="Feature description">' +
        '<button type="button" class="sa-remove-feature" onclick="this.parentElement.remove()">' +
        '<i class="fa-solid fa-trash"></i></button>';
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
