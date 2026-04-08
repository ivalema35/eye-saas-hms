<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page {
        margin: 35px 40px;
        size: A4 portrait;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 12px;
        color: #1A202C;
        line-height: 1.5;
        background: #FFFFFF;
    }
    table { border-collapse: collapse; }
    .w-full { width: 100%; }

    /* Header */
    .brand-name  { font-size: 22px; font-weight: bold; color: #1B4F72; }
    .brand-sub   { font-size: 11px; color: #4A5568; margin-top: 2px; }
    .brand-url   { font-size: 10px; color: #4A5568; margin-top: 5px; }
    .inv-label   { font-size: 28px; font-weight: bold; color: #1B4F72; text-align: right; letter-spacing: 1px; }
    .inv-meta    { text-align: right; font-size: 11px; color: #4A5568; margin-top: 4px; }

    /* Dividers */
    .divider-thick { border-top: 2.5px solid #1B4F72; margin: 16px 0; }
    .divider-thin  { border-top: 1px solid #E2E8F0; margin: 14px 0; }

    /* Section headers */
    .sec-label {
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #4A5568;
        margin-bottom: 6px;
    }

    /* Hospital name */
    .hospital-name { font-size: 15px; font-weight: bold; color: #1A202C; }
    .hospital-sub  { font-size: 11px; color: #4A5568; margin-top: 2px; }

    /* Info key-value pairs */
    .info-key { font-size: 10px; color: #4A5568; text-transform: uppercase; letter-spacing: 0.4px; padding-right: 10px; width: 120px; padding-bottom: 5px; vertical-align: top; }
    .info-val { font-size: 12px; font-weight: 600; color: #1A202C; padding-bottom: 5px; vertical-align: top; }

    /* Line-items table */
    .items-table { width: 100%; margin: 18px 0; }
    .items-table thead tr td {
        background-color: #1B4F72;
        color: #FFFFFF;
        padding: 9px 12px;
        font-size: 11px;
        font-weight: bold;
        letter-spacing: 0.3px;
    }
    .items-table tbody tr td {
        padding: 10px 12px;
        font-size: 12px;
        border-bottom: 1px solid #E2E8F0;
        vertical-align: top;
    }
    .items-table tbody tr:nth-child(even) td { background-color: #F8FAFB; }
    .item-desc-main { font-weight: 600; font-size: 12px; color: #1A202C; }
    .item-desc-sub  { font-size: 10px; color: #4A5568; margin-top: 3px; }
    .amount-col     { text-align: right; font-size: 14px; font-weight: bold; color: #1B4F72; }

    /* Total block */
    .total-row td { padding: 6px 12px; border-bottom: 1px solid #E2E8F0; font-size: 12px; }
    .total-row .lbl { color: #4A5568; }
    .total-row .val { text-align: right; font-weight: 600; }
    .total-final {
        background-color: #1B4F72;
        border-radius: 6px;
        padding: 14px 18px;
        text-align: right;
    }
    .total-final-label  { font-size: 12px; color: #A8D8EA; }
    .total-final-amount { font-size: 24px; font-weight: bold; color: #FFFFFF; margin-top: 3px; }

    /* Status badge */
    .badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: bold;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .badge-success { background: #D5F5E3; color: #1A6F5B; }
    .badge-pending { background: #FDEBD0; color: #784212; }
    .badge-failed  { background: #FADBD8; color: #641E16; }

    /* Notes */
    .note-box {
        background: #F0F4F8;
        border-left: 3px solid #2980B9;
        padding: 10px 14px;
        margin-top: 14px;
        font-size: 11px;
        color: #4A5568;
        border-radius: 0 4px 4px 0;
    }

    /* Footer */
    .footer-box {
        margin-top: 36px;
        padding-top: 14px;
        border-top: 1px solid #E2E8F0;
        text-align: center;
        font-size: 10px;
        color: #4A5568;
    }
    .txn-id { font-family: "Courier New", Courier, monospace; font-size: 10px; color: #2980B9; }
</style>
</head>
<body>

{{-- ===== PAGE HEADER ===== --}}
<table class="w-full" style="margin-bottom:14px">
    <tr>
        <td style="vertical-align:top;width:55%">
            <div class="brand-name">&#128065; Eye HMS SaaS</div>
            <div class="brand-sub">Multi-Tenant Hospital Management Platform</div>
            <div class="brand-url">hmssaas.com &nbsp;|&nbsp; support@hmssaas.com</div>
        </td>
        <td style="vertical-align:top;width:45%;text-align:right">
            <div class="inv-label">INVOICE</div>
            <div class="inv-meta" style="margin-top:6px">
                <strong style="font-size:13px;color:#1A202C">{{ $invoiceNumber }}</strong>
            </div>
            <div class="inv-meta">Date: {{ ($payment->paid_at ?? $payment->created_at ?? now())->format('d F Y') }}</div>
            <div style="margin-top:8px">
                @php
                    $statusClass = match($payment->status) {
                        'success' => 'badge-success',
                        'pending' => 'badge-pending',
                        default   => 'badge-failed',
                    };
                @endphp
                <span class="badge {{ $statusClass }}">{{ $payment->status }}</span>
            </div>
        </td>
    </tr>
</table>

<div class="divider-thick"></div>

{{-- ===== BILLED TO + INVOICE DETAILS ===== --}}
<table class="w-full" style="margin-bottom:4px">
    <tr>
        <td style="vertical-align:top;width:50%;padding-right:20px">
            <div class="sec-label">Billed To</div>
            <div class="hospital-name">{{ $tenant->name ?? 'Hospital' }}</div>
            @if($tenant->admin_name)
                <div class="hospital-sub">{{ $tenant->admin_name }}</div>
            @endif
            @if($tenant->admin_email)
                <div class="hospital-sub">{{ $tenant->admin_email }}</div>
            @endif
            @if($tenant->city || $tenant->state)
                <div class="hospital-sub">{{ implode(', ', array_filter([$tenant->city, $tenant->state])) }}</div>
            @endif
            <div class="hospital-sub" style="margin-top:4px;color:#2980B9">
                hmssaas.com/{{ $tenant->slug }}
            </div>
        </td>
        <td style="vertical-align:top;width:50%;padding-left:20px;border-left:1px solid #E2E8F0">
            <div class="sec-label">Payment Info</div>
            <table>
                <tr>
                    <td class="info-key">Method</td>
                    <td class="info-val">{{ ucfirst($payment->method ?? '—') }}</td>
                </tr>
                @if($payment->transaction_id)
                <tr>
                    <td class="info-key">Transaction ID</td>
                    <td class="info-val"><span class="txn-id">{{ $payment->transaction_id }}</span></td>
                </tr>
                @endif
                <tr>
                    <td class="info-key">Paid On</td>
                    <td class="info-val">{{ ($payment->paid_at ?? now())->format('d M Y, h:i A') }}</td>
                </tr>
                <tr>
                    <td class="info-key">Gateway</td>
                    <td class="info-val">{{ ucfirst($payment->gateway ?? 'Manual') }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ===== LINE ITEMS ===== --}}
<table class="items-table">
    <thead>
        <tr>
            <td style="width:36px">#</td>
            <td>Description</td>
            <td style="width:100px;text-align:center">Billing Cycle</td>
            <td style="width:120px;text-align:right">Amount (₹)</td>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1</td>
            <td>
                <div class="item-desc-main">Eye HMS SaaS — Subscription Plan</div>
                <div class="item-desc-sub">All features included — {{ ucfirst($payment->cycle ?? 'Monthly') }} billing</div>
                @if($payment->paid_at)
                    @php
                        $periodEnd = match($payment->cycle) {
                            'quarterly' => $payment->paid_at->copy()->addDays(90),
                            'yearly'    => $payment->paid_at->copy()->addDays(365),
                            default     => $payment->paid_at->copy()->addDays(30),
                        };
                    @endphp
                    <div class="item-desc-sub" style="margin-top:3px">
                        Validity: {{ $payment->paid_at->format('d M Y') }} — {{ $periodEnd->format('d M Y') }}
                    </div>
                @endif
            </td>
            <td style="text-align:center;vertical-align:middle">{{ ucfirst($payment->cycle ?? 'Monthly') }}</td>
            <td class="amount-col" style="vertical-align:middle">
                &#8377;{{ number_format((float) $payment->amount, 2) }}
            </td>
        </tr>
    </tbody>
</table>

{{-- ===== TOTAL SECTION ===== --}}
<table class="w-full">
    <tr>
        <td style="width:55%"></td>
        <td style="width:45%">
            <table class="w-full">
                <tr class="total-row">
                    <td class="lbl">Subtotal</td>
                    <td class="val">&#8377;{{ number_format((float) $payment->amount, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td class="lbl">GST / Tax</td>
                    <td class="val">Included</td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top:10px">
                        <div class="total-final">
                            <div class="total-final-label">Total Amount Paid</div>
                            <div class="total-final-amount">&#8377;{{ number_format((float) $payment->amount, 2) }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

@if($payment->notes)
<div class="note-box">
    <strong>Notes:</strong> {{ $payment->notes }}
</div>
@endif

{{-- ===== FOOTER ===== --}}
<div class="footer-box">
    <p>This is a computer-generated invoice and does not require a physical signature.</p>
    <p style="margin-top:5px">
        For support: support@hmssaas.com &nbsp;|&nbsp; Eye HMS SaaS — Made with &#10084; in India
    </p>
    <p style="margin-top:5px;color:#BDC3C7">
        Generated: {{ now()->format('d M Y, h:i A') }} &nbsp;|&nbsp; Invoice No: {{ $invoiceNumber }}
    </p>
</div>

</body>
</html>
