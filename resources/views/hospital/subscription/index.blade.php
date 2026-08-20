@extends('hospital.layouts.app')
@section('title', 'Subscription & Billing')

@section('content')
<div class="hospital-subscription-page">
    <style>
        .hospital-subscription-page {
            --sub-primary: #1B4F72;
            --sub-secondary: #2980B9;
            --sub-border: rgba(27, 79, 114, .12);
            color: var(--sub-primary);
        }

        .sub-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .sub-header h2 {
            font-weight: 800;
            font-size: 1.45rem;
            margin: 0 0 .35rem;
        }

        .sub-header-meta {
            font-size: .9rem;
            color: rgba(27, 79, 114, .65);
        }

        .sub-status-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .45rem .85rem;
            border-radius: 999px;
            font-size: .82rem;
            font-weight: 700;
            background: rgba(41, 128, 185, .12);
            color: var(--sub-secondary);
        }

        .sub-bypass-note {
            border-radius: 14px;
            padding: .85rem 1rem;
            background: rgba(245, 158, 11, .12);
            border: 1px solid rgba(245, 158, 11, .25);
            color: #92400e;
            font-size: .88rem;
            margin-bottom: 1.25rem;
        }

        .sub-card {
            background: #fff;
            border: 1px solid var(--sub-border);
            border-radius: 18px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        }

        .sub-card-title {
            font-weight: 800;
            font-size: 1.05rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .sub-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .88rem;
        }

        .sub-table th,
        .sub-table td {
            padding: .75rem .65rem;
            border-bottom: 1px solid rgba(27, 79, 114, .08);
            vertical-align: middle;
        }

        .sub-table th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: rgba(27, 79, 114, .55);
            font-weight: 700;
        }

        .sub-status-pill {
            display: inline-flex;
            padding: .2rem .55rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
        }

        .sub-status-pill.active { background: rgba(39, 174, 96, .12); color: #1e8449; }
        .sub-status-pill.expired { background: rgba(220, 38, 38, .1); color: #b91c1c; }
        .sub-status-pill.extended { background: rgba(41, 128, 185, .12); color: #1B4F72; }

        .sub-plans {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .sub-plans { grid-template-columns: 1fr; }
        }

        .sub-plan-card {
            border: 2px solid var(--sub-border);
            border-radius: 18px;
            padding: 1.15rem;
            cursor: pointer;
            transition: border-color .2s, box-shadow .2s, transform .2s;
            background: #fff;
        }

        .sub-plan-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(15, 23, 42, .08);
        }

        .sub-plan-card.selected {
            border-color: var(--sub-secondary);
            box-shadow: 0 0 0 3px rgba(41, 128, 185, .15);
        }

        .sub-plan-card.featured {
            position: relative;
        }

        .sub-plan-label {
            font-weight: 800;
            font-size: 1.1rem;
            margin-bottom: .25rem;
        }

        .sub-plan-price {
            font-size: 1.75rem;
            font-weight: 900;
            line-height: 1.1;
        }

        .sub-plan-price small {
            font-size: .85rem;
            font-weight: 600;
            color: rgba(27, 79, 114, .55);
        }

        .sub-plan-save {
            margin-top: .35rem;
            font-size: .82rem;
            color: #27AE60;
            font-weight: 700;
        }

        .sub-plan-save s {
            color: rgba(27, 79, 114, .45);
            margin-right: .35rem;
        }

        .sub-gst-box {
            margin-top: .85rem;
            padding: .75rem .85rem;
            border-radius: 12px;
            background: rgba(27, 79, 114, .04);
            font-size: .82rem;
        }

        .sub-gst-row {
            display: flex;
            justify-content: space-between;
            gap: .5rem;
            padding: .15rem 0;
        }

        .sub-gst-row.total {
            font-weight: 800;
            border-top: 1px dashed rgba(27, 79, 114, .15);
            margin-top: .35rem;
            padding-top: .45rem;
        }

        .sub-pay-wrap {
            margin-top: 1.25rem;
            display: flex;
            justify-content: flex-end;
        }
    </style>

    <div class="sub-header">
        <div>
            <h2><i class="bi bi-credit-card-2-front"></i> Subscription & Billing</h2>
            <div class="sub-header-meta">
                {{ $tenant->name }} &middot; {{ $ctx['country_name'] }} &middot; {{ $ctx['currency_code'] }} ({{ $ctx['currency_symbol'] }})
            </div>
        </div>
        @if($subscriptionDaysLeft !== null)
            <span class="sub-status-badge">
                <i class="bi bi-clock-history"></i>
                @if($subscriptionDaysLeft <= 0)
                    Plan expired
                @else
                    {{ $subscriptionDaysLeft }} day{{ $subscriptionDaysLeft === 1 ? '' : 's' }} remaining
                @endif
            </span>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-3">{{ session('success') }}</div>
    @endif
    @if(request('renewed'))
        <div class="alert alert-success rounded-3">Subscription renewed successfully.</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger rounded-3">{{ session('error') }}</div>
    @endif

    @if($razorpayBypass)
        <div class="sub-bypass-note">
            <i class="bi bi-info-circle"></i>
            Payment simulation mode — Razorpay is not configured. Checkout will complete automatically for testing.
        </div>
    @endif

    {{-- Plan History --}}
    <div class="sub-card">
        <div class="sub-card-title"><i class="bi bi-table"></i> Plan History</div>
        <div class="table-responsive">
            <table class="sub-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Days Used</th>
                        <th>Days Left</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($historyRows as $row)
                        <tr>
                            <td>{{ $row['type'] }}</td>
                            <td>{{ $row['start']?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $row['end']?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $row['days_used'] ?? '—' }}</td>
                            <td>{{ $row['days_remaining'] ?? '—' }}</td>
                            <td>
                                @php
                                    $pillClass = match ($row['status']) {
                                        'Active' => 'active',
                                        'Extended' => 'extended',
                                        default => 'expired',
                                    };
                                @endphp
                                <span class="sub-status-pill {{ $pillClass }}">{{ $row['status'] }}</span>
                            </td>
                            <td>
                                @if($row['payment_id'])
                                    <a href="{{ route('hospital.subscription.invoice', ['slug' => $slug, 'payment' => $row['payment_id']]) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-file-earmark-pdf"></i> Invoice
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No plan history yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Plan Selection --}}
    <div class="sub-card">
        <div class="sub-card-title"><i class="bi bi-grid-3x3-gap"></i> Select Plan</div>

        <div class="sub-plans">
            <div class="sub-plan-card selected" data-cycle="monthly" id="plan-monthly">
                <div class="sub-plan-label">Monthly</div>
                <div class="sub-plan-price">
                    {{ $ctx['currency_symbol'] }}{{ number_format($plans['monthly']['price']) }}
                    <small>/month</small>
                </div>
                <div class="sub-gst-box">
                    <div class="sub-gst-row"><span>Subtotal</span><span>{{ tenant_money($tenant, $monthlyQuote['subtotal']) }}</span></div>
                    @if($monthlyQuote['gst_amount'] > 0)
                        <div class="sub-gst-row"><span>GST ({{ number_format($monthlyQuote['gst_rate'], 0) }}%)</span><span>{{ tenant_money($tenant, $monthlyQuote['gst_amount']) }}</span></div>
                    @endif
                    <div class="sub-gst-row total"><span>Total</span><span>{{ tenant_money($tenant, $monthlyQuote['total']) }}</span></div>
                </div>
            </div>

            <div class="sub-plan-card featured" data-cycle="yearly" id="plan-yearly">
                <div class="sub-plan-label">Yearly</div>
                <div class="sub-plan-price">
                    {{ $ctx['currency_symbol'] }}{{ number_format($plans['yearly']['price']) }}
                    <small>/year</small>
                </div>
                @if(($plans['yearly']['save'] ?? 0) > 0)
                    <div class="sub-plan-save">
                        <s>{{ $ctx['currency_symbol'] }}{{ number_format($plans['yearly']['original']) }}</s>
                        Save {{ $ctx['currency_symbol'] }}{{ number_format($plans['yearly']['save']) }}
                    </div>
                @endif
                <div class="sub-gst-box">
                    <div class="sub-gst-row"><span>Subtotal</span><span>{{ tenant_money($tenant, $yearlyQuote['subtotal']) }}</span></div>
                    @if($yearlyQuote['gst_amount'] > 0)
                        <div class="sub-gst-row"><span>GST ({{ number_format($yearlyQuote['gst_rate'], 0) }}%)</span><span>{{ tenant_money($tenant, $yearlyQuote['gst_amount']) }}</span></div>
                    @endif
                    <div class="sub-gst-row total"><span>Total</span><span>{{ tenant_money($tenant, $yearlyQuote['total']) }}</span></div>
                </div>
            </div>
        </div>

        <div class="sub-pay-wrap">
            <button type="button" class="btn btn-primary btn-lg px-4" id="btn-pay-renew" disabled>
                <i class="bi bi-shield-check"></i> Pay &amp; Renew
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if(!$razorpayBypass)
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
@endif
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const checkoutUrl = @json(route('hospital.subscription.checkout', ['slug' => $slug]));
    const confirmUrl = @json(route('hospital.subscription.confirm', ['slug' => $slug]));
    const indexUrl = @json(route('hospital.subscription.index', ['slug' => $slug]));
    const razorpayBypass = @json($razorpayBypass);

    let selectedCycle = 'monthly';
    const planCards = document.querySelectorAll('.sub-plan-card');
    const payBtn = document.getElementById('btn-pay-renew');

    planCards.forEach(card => {
        card.addEventListener('click', () => {
            planCards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            selectedCycle = card.dataset.cycle;
            payBtn.disabled = false;
        });
    });

    payBtn.disabled = false;

    payBtn.addEventListener('click', async () => {
        payBtn.disabled = true;
        payBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

        try {
            const checkoutRes = await fetch(checkoutUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ cycle: selectedCycle }),
            });

            const checkoutData = await checkoutRes.json();
            if (!checkoutRes.ok) {
                throw new Error(checkoutData.message || 'Checkout failed.');
            }

            if (checkoutData.bypass || razorpayBypass) {
                await confirmPayment(checkoutData.payment_id);
                window.location.href = indexUrl + '?renewed=1';
                return;
            }

            const options = {
                key: checkoutData.key,
                amount: checkoutData.amount,
                currency: checkoutData.currency,
                order_id: checkoutData.order_id,
                name: @json($tenant->name),
                description: selectedCycle.charAt(0).toUpperCase() + selectedCycle.slice(1) + ' subscription',
                handler: async function (response) {
                    await confirmPayment(checkoutData.payment_id, response);
                    window.location.href = indexUrl + '?renewed=1';
                },
                modal: {
                    ondismiss: function () {
                        payBtn.disabled = false;
                        payBtn.innerHTML = '<i class="bi bi-shield-check"></i> Pay & Renew';
                    }
                }
            };

            const rzp = new Razorpay(options);
            rzp.open();
        } catch (err) {
            alert(err.message || 'Payment could not be started.');
            payBtn.disabled = false;
            payBtn.innerHTML = '<i class="bi bi-shield-check"></i> Pay & Renew';
        }
    });

    async function confirmPayment(paymentId, rzpResponse) {
        const body = { payment_id: paymentId };
        if (rzpResponse) {
            body.razorpay_payment_id = rzpResponse.razorpay_payment_id;
            body.razorpay_order_id = rzpResponse.razorpay_order_id;
            body.razorpay_signature = rzpResponse.razorpay_signature;
        }

        const res = await fetch(confirmUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify(body),
        });

        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.message || 'Payment confirmation failed.');
        }
    }
})();
</script>
@endpush
