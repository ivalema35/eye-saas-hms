<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 40px 45px;
            size: A4 portrait;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #1A202C;
            line-height: 1.5;
            background: #FFFFFF;
        }

        table {
            border-collapse: collapse;
        }

        /* ── Header ── */
        .brand-name {
            font-size: 20px;
            font-weight: bold;
            color: #1B4F72;
        }

        .brand-sub {
            font-size: 10px;
            color: #4A5568;
            margin-top: 3px;
        }

        .brand-url {
            font-size: 10px;
            color: #4A5568;
            margin-top: 4px;
        }

        .inv-label {
            font-size: 26px;
            font-weight: bold;
            color: #1B4F72;
            letter-spacing: 1px;
        }

        .inv-num {
            font-size: 13px;
            font-weight: bold;
            color: #1A202C;
        }

        .inv-meta {
            font-size: 10px;
            color: #4A5568;
            margin-top: 3px;
        }

        /* ── Dividers ── */
        .rule-thick {
            border: none;
            border-top: 2.5px solid #1B4F72;
            margin: 14px 0;
        }

        .rule-thin {
            border: none;
            border-top: 1px solid #E2E8F0;
            margin: 12px 0;
        }

        /* ── Section labels ── */
        .sec-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #718096;
            margin-bottom: 6px;
        }

        .hosp-name {
            font-size: 14px;
            font-weight: bold;
            color: #1A202C;
        }

        .hosp-sub {
            font-size: 10px;
            color: #4A5568;
            margin-top: 2px;
        }

        /* ── Payment info key/val ── */
        .ikey {
            font-size: 10px;
            color: #718096;
            padding-right: 12px;
            padding-bottom: 5px;
            vertical-align: top;
            white-space: nowrap;
        }

        .ival {
            font-size: 11px;
            font-weight: bold;
            color: #1A202C;
            padding-bottom: 5px;
            vertical-align: top;
        }

        /* ── Status badge — table-based to avoid inline-block issues ── */
        .badge-cell {
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            padding: 3px 10px;
            border: 1px solid #27AE60;
            color: #1A6F5B;
            background-color: #D5F5E3;
        }

        .badge-cell-pending {
            border-color: #E67E22;
            color: #784212;
            background-color: #FDEBD0;
        }

        .badge-cell-failed {
            border-color: #C0392B;
            color: #641E16;
            background-color: #FADBD8;
        }

        /* ── Line items table ── */
        .items-thead td {
            background-color: #F8FAFC;
            color: #4A5568;
            padding: 8px 10px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            border-top: 1px solid #CBD5E0;
            border-bottom: 1px solid #CBD5E0;
        }

        .items-tbody td {
            padding: 10px 10px;
            font-size: 11px;
            border-bottom: 1px solid #EDF2F7;
            vertical-align: top;
        }

        .item-main {
            font-weight: bold;
            font-size: 12px;
            color: #1A202C;
        }

        .item-sub {
            font-size: 10px;
            color: #718096;
            margin-top: 3px;
        }

        .amount-col {
            text-align: right;
            font-size: 13px;
            font-weight: bold;
            color: #1B4F72;
        }

        /* ── Totals ── */
        .tot-lbl {
            font-size: 11px;
            color: #4A5568;
            padding: 5px 12px 5px 0;
        }

        .tot-val {
            font-size: 11px;
            font-weight: bold;
            color: #1A202C;
            text-align: right;
            padding: 5px 0;
        }

        .tot-sep {
            border-top: 1px solid #E2E8F0;
        }

        /* ── Grand total box — solid background, no border-radius ── */
        .grand-box {
            background-color: #1B4F72;
            padding: 12px 16px;
        }

        .grand-lbl {
            font-size: 10px;
            color: #A8D8EA;
        }

        .grand-amt {
            font-size: 22px;
            font-weight: bold;
            color: #FFFFFF;
            margin-top: 4px;
        }

        /* ── Notes ── */
        .note-box {
            background: #F7FAFC;
            border-left: 3px solid #2980B9;
            padding: 9px 12px;
            margin-top: 14px;
            font-size: 10px;
            color: #4A5568;
        }

        /* ── Footer ── */
        .footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 1px solid #E2E8F0;
            text-align: center;
            font-size: 9px;
            color: #718096;
        }

        .txn {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 10px;
            color: #2980B9;
        }
    </style>
</head>

<body>

    {{-- ═══════════════ HEADER ═══════════════ --}}
    <table width="100%">
        <tr>
            <td width="55%" style="vertical-align: top;">
                <div class="brand-name">EYENOSIS</div>
                <div class="brand-sub">Multi-Tenant Hospital Management Platform</div>
                <div class="brand-url">hmssaas.com &nbsp;|&nbsp; support@hmssaas.com</div>
            </td>
            <td width="45%" style="vertical-align: top; text-align: right;">
                <div class="inv-label">INVOICE</div>
                <div class="inv-num" style="margin-top: 5px;">{{ $invoiceNumber }}</div>
                <div class="inv-meta">Date: {{ ($payment->paid_at ?? $payment->created_at ?? now())->format('d F Y') }}
                </div>
                <div style="margin-top: 8px; text-align: right;">
                    @php
                        $badgeClass = match ($payment->status) {
                            'pending' => 'badge-cell badge-cell-pending',
                            'failed' => 'badge-cell badge-cell-failed',
                            default => 'badge-cell',
                        };
                    @endphp
                    <table style="display: inline-table; margin-left: auto;">
                        <tr>
                            <td class="{{ $badgeClass }}">{{ strtoupper($payment->status) }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <hr class="rule-thick">

    {{-- ═══════════════ BILLED TO + PAYMENT INFO ═══════════════ --}}
    <table width="100%">
        <tr>
            <td width="50%" style="vertical-align: top; padding-right: 20px;">
                <div class="sec-label">Billed To</div>
                <div class="hosp-name">{{ $tenant->name ?? 'Hospital' }}</div>
                @if(!empty($tenant->admin_name))
                    <div class="hosp-sub">{{ $tenant->admin_name }}</div>
                @endif
                @if(!empty($tenant->admin_email))
                    <div class="hosp-sub">{{ $tenant->admin_email }}</div>
                @endif
                @if(!empty($tenant->city) || !empty($tenant->state))
                    <div class="hosp-sub">{{ implode(', ', array_filter([$tenant->city ?? null, $tenant->state ?? null])) }}
                    </div>
                @endif
                <div class="hosp-sub" style="margin-top: 4px; color: #2980B9;">
                    hmssaas.com/{{ $tenant->slug }}
                </div>
            </td>
            <td width="1" style="border-left: 1px solid #E2E8F0;">&nbsp;</td>
            <td width="49%" style="vertical-align: top; padding-left: 20px;">
                <div class="sec-label">Payment Info</div>
                <table>
                    <tr>
                        <td class="ikey">Method</td>
                        <td class="ival">{{ ucfirst($payment->method ?? '—') }}</td>
                    </tr>
                    <tr>
                        <td class="ikey">Gateway</td>
                        <td class="ival">{{ ucfirst($payment->gateway ?? 'Manual') }}</td>
                    </tr>
                    @if(!empty($payment->transaction_id))
                        <tr>
                            <td class="ikey">Transaction ID</td>
                            <td class="ival"><span class="txn">{{ $payment->transaction_id }}</span></td>
                        </tr>
                    @endif
                    <tr>
                        <td class="ikey">Paid On</td>
                        <td class="ival">{{ ($payment->paid_at ?? now())->format('d M Y, h:i A') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <hr class="rule-thin">

    {{-- ═══════════════ LINE ITEMS ═══════════════ --}}
    <table width="100%" style="margin-top: 6px;">
        <thead>
            <tr class="items-thead">
                <td width="30">#</td>
                <td>Description</td>
                <td width="100" style="text-align: center;">Billing Cycle</td>
                <td width="120" style="text-align: right;">Amount (&#8377;)</td>
            </tr>
        </thead>
        <tbody>
            <tr class="items-tbody">
                <td>1</td>
                <td>
                    <div class="item-main">EYENOSIS — Subscription Plan</div>
                    <div class="item-sub">All features included &mdash; {{ ucfirst($payment->cycle ?? 'Monthly') }}
                        billing</div>
                    @if($payment->paid_at)
                        @php
                            $periodEnd = match ($payment->cycle) {
                                'quarterly' => $payment->paid_at->copy()->addDays(90),
                                'yearly' => $payment->paid_at->copy()->addDays(365),
                                default => $payment->paid_at->copy()->addDays(30),
                            };
                        @endphp
                        <div class="item-sub" style="margin-top: 3px;">
                            Validity: {{ $payment->paid_at->format('d M Y') }} &mdash; {{ $periodEnd->format('d M Y') }}
                        </div>
                    @endif
                </td>
                <td style="text-align: center; vertical-align: middle;">{{ ucfirst($payment->cycle ?? 'Monthly') }}</td>
                <td class="amount-col" style="vertical-align: middle;">
                    &#8377;{{ number_format((float) $payment->amount, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- ═══════════════ TOTALS ═══════════════ --}}
    <table width="100%" style="margin-top: 10px;">
        <tr>
            <td width="55%">&nbsp;</td>
            <td width="45%">
                <table width="100%">
                    <tr>
                        <td class="tot-lbl">Subtotal</td>
                        <td class="tot-val">&#8377;{{ number_format((float) $payment->amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="tot-lbl">GST / Tax</td>
                        <td class="tot-val">Included</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top: 8px;">
                            <div class="grand-box">
                                <div class="grand-lbl">Total Amount Paid</div>
                                <div class="grand-amt">&#8377;{{ number_format((float) $payment->amount, 2) }}</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ═══════════════ NOTES ═══════════════ --}}
    @if(!empty($payment->notes))
        <div class="note-box">
            <strong>Notes:</strong> {{ $payment->notes }}
        </div>
    @endif

    {{-- ═══════════════ FOOTER ═══════════════ --}}
    <div class="footer">
        <div>This is a computer-generated invoice and does not require a physical signature.</div>
        <div style="margin-top: 4px;">
            For support: support@hmssaas.com &nbsp;|&nbsp; EYENOSIS &mdash; Made with &#10084; in India
        </div>
        <div style="margin-top: 4px; color: #A0AEC0;">
            Generated: {{ now()->format('d M Y, h:i A') }} &nbsp;|&nbsp; {{ $invoiceNumber }}
        </div>
    </div>

</body>

</html>