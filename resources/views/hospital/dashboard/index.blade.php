@extends('hospital.layouts.app')
@section('title', 'Dashboard')
@section('page-header', 'Dashboard')

@push('styles')
<style>
/*
  Hospital Admin Dashboard Theme
  Requirements:
  - Primary: #ebf5fbeb
  - Secondary: #1B4F72
  - Keep Blade/dynamic logic untouched; CSS-only refresh.
*/

/* ── Theme tokens (scoped to this page) ────────────────────────────────── */
.bento-page {
    --dash-primary: #ebf5fbeb;
    --dash-secondary: #1B4F72;
    --dash-s2-08: rgba(27, 79, 114, 0.08);
    --dash-s2-12: rgba(27, 79, 114, 0.12);
    --dash-s2-18: rgba(27, 79, 114, 0.18);
    --dash-s2-24: rgba(27, 79, 114, 0.24);
    --dash-s2-70: rgba(27, 79, 114, 0.70);
    --dash-s2-82: rgba(27, 79, 114, 0.82);
    --dash-white: #ffffff;

    background: #ffffff;
    padding: 1.75rem;
    min-height: 100%;
    font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
    color: var(--dash-secondary);
    position: relative;
    overflow: hidden;
}

/* Align Bootstrap muted text + inline muted styles with the theme */
.bento-page .text-muted {
    color: var(--dash-s2-70) !important;
}
.bento-page [style*="color:#94A3B8"],
.bento-page [style*="color: #94A3B8"] {
    color: var(--dash-s2-70) !important;
}

/* Enforce palette for icons that still have inline colors */
.bento-page i[style*="color:#"],
.bento-page i[style*="color: #"] {
    color: var(--dash-secondary) !important;
}

/* soft background accents (no extra colors, just secondary tint) */
.bento-page::before,
.bento-page::after {
    content: none;
}

/* ── Layout grid ───────────────────────────────────────────────────────── */
.bento-dashboard {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 18px;
    position: relative;
    z-index: 1;
}

/* ── Metric cards (match screenshot style) ─────────────────────────────── */
/* Scoped to the TOP dashboard cards only, so tables/sections keep layout */
.bento-dashboard > .bento-card {
    border-radius: 30px;
    background: rgba(255, 255, 255, 0.86);
    border: 1px solid var(--dash-s2-12);
    box-shadow: 0 18px 42px rgba(27, 79, 114, 0.12);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    position: relative;
    overflow: hidden;
}

.bento-dashboard > .bento-card::before {
    content: none;
}

/* Bubble accent removed as requested */

.bento-dashboard > .bento-card {
    animation: dash-card-pop 520ms cubic-bezier(.2,.9,.2,1) both;
}
@keyframes dash-card-pop {
    from { opacity: 0; transform: translateY(10px) scale(0.985); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

.bento-dashboard > .bento-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 26px 56px rgba(27, 79, 114, 0.16);
}

.bento-dashboard > .bento-card .bento-stat {
    position: relative;
    z-index: 1;
    flex-direction: row-reverse;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.2rem 1.35rem;
}

.bento-dashboard > .bento-card .bento-icon {
    width: 60px;
    height: 60px;
    border-radius: 999px;
    background: rgba(27, 79, 114, 0.08) !important;
    border: 1px solid rgba(27, 79, 114, 0.14) !important;
    box-shadow: none;
    margin-top: .1rem;
}

.bento-dashboard > .bento-card .metric-label {
    color: var(--dash-s2-82);
    font-size: 12px;
    letter-spacing: .10em;
}

.bento-dashboard > .bento-card .metric-value {
    font-size: 36px;
    margin-top: .35rem;
    line-height: 1.05;
}

.bento-dashboard > .bento-card .metric-meta {
    margin-top: .55rem;
    font-size: 12px;
    color: rgba(27, 79, 114, 0.62);
}

/* ── Card (glass + border) ─────────────────────────────────────────────── */
.bento-card {
    background: rgba(255, 255, 255, 0.78);
    border: 1px solid var(--dash-s2-12);
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(27, 79, 114, 0.07);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform: translateY(0);
    transition: transform 220ms ease, box-shadow 220ms ease, border-color 220ms ease;
    animation: dash-fade-up 420ms ease both;
}
.bento-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 16px 44px rgba(27, 79, 114, 0.12);
    border-color: var(--dash-s2-24);
}

@keyframes dash-fade-up {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
    .bento-card { animation: none; transition: none; }
    .bento-card:hover { transform: none; }
}

/* ── Span helpers (kept, so markup remains unchanged) ───────────────────── */
.span-2  { grid-column: span 2; }
.span-3  { grid-column: span 3; }
.span-4  { grid-column: span 4; }
.span-6  { grid-column: span 6; }
.span-7  { grid-column: span 7; }
.span-8  { grid-column: span 8; }
.span-12 { grid-column: span 12; }
.row-span-2 { grid-row: span 2; }

/* ── Stat card interior ────────────────────────────────────────────────── */
.bento-stat {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem 1.375rem;
    height: 100%;
}

.bento-icon {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid var(--dash-s2-12);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
    color: var(--dash-secondary);
}

/* force icon color to theme even if inline styles exist */
.bento-icon i,
.bento-icon svg {
    color: var(--dash-secondary) !important;
    stroke: var(--dash-secondary) !important;
}

/* Neutralize old per-color icon classes to stay in 2-color palette */
.ig-blue,
.ig-green,
.ig-orange,
.ig-teal,
.ig-purple,
.ig-red,
.ig-indigo,
.ig-cobalt {
    background: rgba(255, 255, 255, 0.9) !important;
    border: 1px solid var(--dash-s2-12) !important;
    color: var(--dash-secondary) !important;
}

/* ── Metric typography ─────────────────────────────────────────────────── */
.metric-label {
    font-weight: 700;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--dash-s2-70);
    margin: 0;
}
.metric-value {
    font-weight: 900;
    font-size: 32px;
    color: var(--dash-secondary);
    letter-spacing: -1px;
    line-height: 1.15;
    margin: 6px 0 0;
}
.metric-meta {
    font-size: 12px;
    color: var(--dash-s2-70);
    margin: 3px 0 0;
}

/* ── Card header (section title strip) ─────────────────────────────────── */
.bento-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.375rem;
    border-bottom: 1px solid var(--dash-s2-12);
    background: rgba(255, 255, 255, 0.88);
}
.bento-title {
    font-size: .95rem;
    font-weight: 800;
    color: var(--dash-secondary);
    margin: 0;
    letter-spacing: -0.2px;
}

/* ── Badges ────────────────────────────────────────────────────────────── */
.b-badge {
    display: inline-flex;
    align-items: center;
    font-size: 11px;
    font-weight: 800;
    padding: .35em .85em;
    border-radius: 999px;
    letter-spacing: .02em;
    border: 1px solid var(--dash-s2-18);
    background: rgba(27, 79, 114, 0.08);
    color: var(--dash-secondary);
}
.b-badge-warn {
    /* Pending: red */
    background: rgba(220, 38, 38, 0.08) !important;
    color: #DC2626 !important;
    border-color: rgba(220, 38, 38, 0.12) !important;
}
.b-badge-info {
    /* In Process: blue (theme) */
    background: rgba(27, 79, 114, 0.12) !important;
    color: var(--dash-secondary) !important;
    border-color: rgba(27, 79, 114, 0.18) !important;
}
.b-badge-green {
    /* Completed: green */
    background: rgba(16, 185, 129, 0.08) !important;
    color: #10B981 !important;
    border-color: rgba(16, 185, 129, 0.12) !important;
}

/* ── Tables (premium header + soft rows) ───────────────────────────────── */
.bento-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 13.5px;
}
.bento-table thead tr {
    background: var(--dash-secondary);
}
.bento-table thead th {
    padding: .8rem 1.125rem;
    font-weight: 800;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .09em;
    color: var(--dash-white);
    border-bottom: 1px solid rgba(255, 255, 255, 0.18);
    white-space: nowrap;
}
.bento-table thead th:first-child { border-top-left-radius: 14px; }
.bento-table thead th:last-child  { border-top-right-radius: 14px; }

.bento-table tbody tr {
    background: rgba(255, 255, 255, 0.86);
    transition: transform 160ms ease, background 160ms ease;
}
.bento-table tbody tr:nth-child(even) {
    background: rgba(27, 79, 114, 0.04);
}
.bento-table tbody tr:hover {
    background: rgba(27, 79, 114, 0.08);
    transform: translateX(2px);
}
.bento-table tbody td {
    padding: .85rem 1.125rem;
    color: var(--dash-secondary);
    border-bottom: 1px solid var(--dash-s2-12);
    vertical-align: middle;
}
.bento-table tbody tr:last-child td {
    border-bottom: 0;
}

/* Scrollable table viewport for queue-style sections */
.dashboard-table-scroll {
    --dash-scroll-rows: 5;
    --dash-scroll-row-height: 56px;
    --dash-scroll-header-height: 48px;
    max-height: calc((var(--dash-scroll-rows) * var(--dash-scroll-row-height)) + var(--dash-scroll-header-height));
    overflow: auto;
}

/* .dashboard-table-scroll .bento-table {
    min-width: 980px;
    width: max-content;
    width: -moz-max-content;
} */

/* .dashboard-table-scroll .bento-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: var(--dash-secondary);
} */

/* .dashboard-table-scroll .bento-table thead th:first-child {
    left: 0;
    z-index: 3;
} */

/* ── Revenue block ─────────────────────────────────────────────────────── */
.rev-grid { display: flex; flex: 1; }
.rev-col  { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.25rem .75rem; text-align: center; }
.rev-col + .rev-col { border-left: 1px solid var(--dash-s2-12); }
.rev-label {
    font-weight: 800;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .09em;
    color: var(--dash-s2-70);
    margin: 0;
}
.rev-value {
    font-weight: 900;
    font-size: 1.375rem;
    color: var(--dash-secondary);
    letter-spacing: -0.4px;
    margin: 6px 0 0;
}

/* ── Quick actions ─────────────────────────────────────────────────────── */
.qa-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 14px;
    padding: 1.25rem 1.375rem 1.5rem;
}
.qa-pill {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .6rem;
    padding: 1.1rem .85rem;
    background: rgba(255, 255, 255, 0.86);
    border: 1px solid var(--dash-s2-12);
    border-radius: 16px;
    text-decoration: none;
    color: var(--dash-secondary);
    font-weight: 800;
    font-size: 13px;
    text-align: center;
    transition: transform 200ms ease, box-shadow 200ms ease, background 200ms ease, border-color 200ms ease;
}
.qa-pill i,
.qa-pill svg {
    color: var(--dash-secondary) !important;
    stroke: var(--dash-secondary) !important;
}
.qa-pill:hover {
    background: var(--dash-secondary);
    border-color: var(--dash-secondary);
    color: var(--dash-white);
    transform: translateY(-3px);
    box-shadow: 0 16px 40px rgba(27, 79, 114, 0.18);
}
.qa-pill:hover i,
.qa-pill:hover svg {
    color: var(--dash-white) !important;
    stroke: var(--dash-white) !important;
}

/* ── Alerts ────────────────────────────────────────────────────────────── */
.bento-alert {
    border-radius: 16px;
    padding: .95rem 1.15rem;
    display: flex;
    align-items: center;
    gap: .75rem;
    font-size: .9rem;
    font-weight: 700;
    margin-bottom: 1.25rem;
    position: relative;
    z-index: 1;
    background: rgba(255, 255, 255, 0.78);
    border: 1px solid var(--dash-s2-12);
}
.bento-alert-warn,
.bento-alert-danger {
    color: var(--dash-secondary);
    border-left: 5px solid var(--dash-secondary);
}

/* ── FOC badge pulse (same secondary palette) ──────────────────────────── */
.foc-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 24px;
    border-radius: 999px;
    background: var(--dash-secondary);
    color: var(--dash-white);
    font-size: 11px;
    font-weight: 900;
    padding: 0 .45rem;
    animation: dash-pulse 2s infinite;
}
@keyframes dash-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(27,79,114,.35); }
    50%      { box-shadow: 0 0 0 9px rgba(27,79,114,0); }
}

/* ── Buttons (normalize HMS button colors to match theme) ───────────────── */
.hms-btn {
    border-radius: 12px !important;
    font-weight: 800 !important;
    letter-spacing: .01em;
    transition: transform 160ms ease, box-shadow 160ms ease, background 160ms ease, border-color 160ms ease, color 160ms ease;
}
.hms-btn:hover { transform: translateY(-1px); box-shadow: 0 12px 26px rgba(27, 79, 114, 0.16); }

.hms-btn-primary,
.hms-btn-success {
    background: var(--dash-secondary) !important;
    border-color: var(--dash-secondary) !important;
    color: var(--dash-white) !important;
}
.hms-btn-outline,
.foc-view-btn {
    background: rgba(255, 255, 255, 0.86) !important;
    border-color: var(--dash-s2-24) !important;
    color: var(--dash-secondary) !important;
}
.hms-btn-outline:hover,
.foc-view-btn:hover {
    background: var(--dash-secondary) !important;
    border-color: var(--dash-secondary) !important;
    color: var(--dash-white) !important;
}

.hms-btn-print {
    width: 38px;
    height: 38px;
    padding: 0 !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px !important;
    background: #EAF3FF !important;
    border-color: #D6E8FF !important;
    color: #4E79B8 !important;
}
.hms-btn-print i {
    color: inherit !important;
    font-size: 1rem;
}
.hms-btn-print:hover {
    background: #DDEBFF !important;
    border-color: #BFD6FF !important;
    color: #ffffff !important;
}

/* Keep premium FOC section consistent */
.foc-premium-card {
    border-radius: 18px !important;
    border: 1px solid var(--dash-s2-12) !important;
    box-shadow: 0 10px 30px rgba(27,79,114,0.07) !important;
}
.foc-premium-table thead tr { background: var(--dash-secondary) !important; }
.foc-premium-table thead th {
    color: var(--dash-white) !important;
    border-bottom: 1px solid rgba(255,255,255,.18) !important;
}
.foc-accept-btn {
    background: var(--dash-secondary) !important;
    border-color: var(--dash-secondary) !important;
    color: var(--dash-white) !important;
    border-radius: 12px !important;
}

.foc-detail-modal .modal-dialog {
    max-width: 620px;
}

.foc-request-modal .modal-dialog {
    max-width: 680px;
}

.foc-detail-modal .modal-content {
    border: 1px solid var(--dash-s2-12);
    border-radius: 24px;
    overflow: hidden;
    background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(248,251,253,.98));
    box-shadow: 0 28px 60px rgba(27, 79, 114, 0.18);
}

.foc-request-modal .modal-content {
    border: 1px solid var(--dash-s2-12);
    border-radius: 24px;
    overflow: hidden;
    background: linear-gradient(180deg, rgba(255,255,255,.99), rgba(248,251,253,.98));
    box-shadow: 0 28px 60px rgba(27, 79, 114, 0.18);
}

.foc-detail-modal .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.2rem 1.35rem 1rem;
    border-bottom: 0;
    background: linear-gradient(135deg, var(--dash-secondary), rgba(27, 79, 114, 0.9));
    color: var(--dash-white);
    position: relative;
}

.foc-request-modal .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.2rem 1.35rem 1rem;
    border-bottom: 0;
    background: linear-gradient(135deg, var(--dash-secondary), rgba(27, 79, 114, 0.9));
    color: var(--dash-white);
    position: relative;
}

.foc-request-modal .modal-header::after {
    content: "";
    position: absolute;
    left: 1.35rem;
    right: 1.35rem;
    bottom: 0;
    height: 1px;
    background: rgba(255, 255, 255, 0.16);
}

.foc-detail-modal .modal-header::after {
    content: "";
    position: absolute;
    left: 1.35rem;
    right: 1.35rem;
    bottom: 0;
    height: 1px;
    background: rgba(255, 255, 255, 0.16);
}

.foc-detail-modal .modal-title {
    display: flex;
    align-items: center;
    gap: .8rem;
    flex: 1 1 auto;
    min-width: 0;
    margin: 0;
    font-size: 1.02rem;
    font-weight: 800;
    letter-spacing: -.01em;
    color: var(--dash-white);
    line-height: 1.2;
    background: transparent !important;
    text-decoration: none !important;
    white-space: normal;
}

.foc-request-modal .modal-title {
    display: flex;
    align-items: center;
    gap: .8rem;
    flex: 1 1 auto;
    min-width: 0;
    margin: 0;
    font-size: 1.02rem;
    font-weight: 800;
    letter-spacing: -.01em;
    color: var(--dash-white);
    line-height: 1.2;
    background: transparent !important;
    text-decoration: none !important;
    white-space: normal;
}

.foc-detail-modal .modal-title-icon {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.14);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.12);
    flex-shrink: 0;
}

.foc-detail-modal .modal-title-icon i {
    font-size: 1.05rem;
    color: #fff !important;
    line-height: 1;
}

.foc-request-modal .modal-title-icon {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.14);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.12);
    flex-shrink: 0;
}

.foc-request-modal .modal-title-icon i {
    font-size: 1.05rem;
    color: #fff !important;
    line-height: 1;
}

.foc-detail-modal .btn-close {
    width: 2.15rem;
    height: 2.15rem;
    margin: 0;
    border-radius: 999px;
    background-color: rgba(255,255,255,.14);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.16);
    opacity: 1;
    filter: invert(1) grayscale(100%) brightness(200%);
    flex-shrink: 0;
}

.foc-detail-modal .modal-body {
    padding: 1.35rem;
    background:
        radial-gradient(circle at top right, rgba(27, 79, 114, 0.08), transparent 34%),
        linear-gradient(180deg, rgba(255,255,255,.98), rgba(244,248,251,.98));
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .95rem;
}

.foc-detail-modal .modal-body > p {
    margin: 0 !important;
    border: 1px solid var(--dash-s2-12);
    border-radius: 18px;
    background: rgba(255,255,255,.9);
    padding: .95rem 1rem;
    box-shadow: 0 10px 22px rgba(27, 79, 114, 0.06);
    color: var(--dash-secondary);
    font-size: 1rem;
    font-weight: 800;
    line-height: 1.45;
    word-break: break-word;
}

.foc-request-modal .modal-body {
    padding: 1.35rem;
    background:
        radial-gradient(circle at top right, rgba(27, 79, 114, 0.08), transparent 34%),
        linear-gradient(180deg, rgba(255,255,255,.98), rgba(244,248,251,.98));
}

.foc-request-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .95rem;
}

.foc-request-card {
    border: 1px solid var(--dash-s2-12);
    border-radius: 18px;
    background: rgba(255,255,255,.9);
    padding: .95rem 1rem;
    box-shadow: 0 10px 22px rgba(27, 79, 114, 0.06);
}

.foc-request-card.is-full {
    grid-column: 1 / -1;
}

.foc-request-label {
    display: block;
    margin-bottom: .35rem;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--dash-s2-70);
}

.foc-request-value {
    color: var(--dash-secondary);
    font-size: 1.02rem;
    font-weight: 800;
    line-height: 1.45;
    word-break: break-word;
}

.foc-request-fee {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .5rem .8rem;
    border-radius: 999px;
    background: rgba(27, 79, 114, 0.08);
    color: var(--dash-secondary);
    font-size: 1rem;
    font-weight: 900;
}

.foc-request-select,
.foc-request-textarea {
    border-radius: 14px;
    border-color: var(--dash-s2-18);
    background: rgba(255,255,255,.96);
    color: var(--dash-secondary);
    box-shadow: none;
}

.foc-request-select:focus,
.foc-request-textarea:focus {
    border-color: var(--dash-secondary);
    box-shadow: 0 0 0 .2rem rgba(27,79,114,.12);
}

.foc-request-modal .modal-footer {
    padding: 1rem 1.35rem 1.35rem;
    border-top: 1px solid var(--dash-s2-12);
    background: rgba(255,255,255,.92);
}

.foc-request-modal .modal-footer .hms-btn {
    min-width: 118px;
}

.foc-modal-kicker {
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: rgba(255,255,255,.78);
    margin-bottom: .25rem;
}

.foc-detail-modal .modal-body > p strong {
    display: block;
    margin-bottom: .35rem;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--dash-s2-70);
}

.foc-detail-modal .modal-body > p:last-child {
    grid-column: 1 / -1;
}

.foc-detail-modal .modal-body > p:nth-child(4) {
    background: linear-gradient(180deg, rgba(27,79,114,.08), rgba(255,255,255,.96));
}

.foc-detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .95rem;
}

.foc-detail-card {
    border: 1px solid var(--dash-s2-12);
    border-radius: 18px;
    background: rgba(255,255,255,.9);
    padding: .95rem 1rem;
    box-shadow: 0 10px 22px rgba(27, 79, 114, 0.06);
}

.foc-detail-card.is-full {
    grid-column: 1 / -1;
}

.foc-detail-label {
    display: block;
    margin-bottom: .35rem;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--dash-s2-70);
}

.foc-detail-value {
    color: var(--dash-secondary);
    font-size: 1.02rem;
    font-weight: 800;
    line-height: 1.45;
    word-break: break-word;
}

.foc-detail-value.is-muted {
    color: var(--dash-s2-70);
    font-weight: 700;
}

.foc-detail-fee {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .5rem .8rem;
    border-radius: 999px;
    background: rgba(27, 79, 114, 0.08);
    color: var(--dash-secondary);
    font-size: 1rem;
    font-weight: 900;
}

.foc-detail-modal .modal-footer {
    padding: 1rem 1.35rem 1.35rem;
    border-top: 1px solid var(--dash-s2-12);
    background: rgba(255,255,255,.92);
}

.foc-detail-modal .modal-footer .hms-btn {
    min-width: 118px;
}






/* ── Receptionist doctor strip ────────────────────────────────────────── */
.doctor-strip-wrap {
    margin-bottom: 1.25rem;
}

.doctor-strip-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}

.doctor-strip-card {
    border: 1px solid var(--dash-s2-18);
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.9);
    box-shadow: 0 10px 24px rgba(27, 79, 114, 0.08);
    padding: 1.2rem;
}

.doctor-strip-head {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.doctor-strip-avatar {
    width: 56px;
    height: 56px;
    border-radius: 999px;
    border: 1px solid var(--dash-s2-18);
    background: rgba(27, 79, 114, 0.12);
    color: var(--dash-secondary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    font-weight: 800;
}

.doctor-strip-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 800;
    color: var(--dash-secondary);
    line-height: 1;
}

.doctor-strip-name {
    margin: 0;
    color: var(--dash-secondary);
    font-size: 1.08rem;
    font-weight: 800;
    letter-spacing: .01em;
    line-height: 1.2;
}

.doctor-strip-sub {
    margin: .35rem 0 0;
    color: var(--dash-s2-70);
    font-size: .9rem;
    font-weight: 600;
}

.doctor-strip-status {
    margin-top: .35rem;
    color: #18a957;
    font-size: .82rem;
    font-weight: 700;
}

.doctor-strip-pills {
    margin-top: .8rem;
    display: flex;
    gap: .55rem;
    flex-wrap: wrap;
}

.doctor-strip-pill {
    border-radius: 999px;
    padding: .3rem .68rem;
    font-size: .74rem;
    font-weight: 700;
    line-height: 1.1;
    border: 1px solid var(--dash-s2-12);
}

.doctor-strip-pill.is-primary {
    background: var(--dash-secondary);
    color: #ffffff;
    border-color: var(--dash-secondary);
}

.doctor-strip-pill.is-secondary {
    background: var(--dash-s2-12);
    color: var(--dash-secondary);
}

.doctor-strip-pill.is-muted {
    background: rgba(255, 255, 255, 0.9);
    color: var(--dash-s2-70);
}

/* ── Receptionist today patients panel ────────────────────────────────── */
.rec-patient-scroll {
    --rec-scroll-rows: 5;
    --rec-scroll-row-height: 48px;
    --rec-scroll-header-height: 44px;
    max-height: calc((var(--rec-scroll-rows) * var(--rec-scroll-row-height)) + var(--rec-scroll-header-height));
    overflow: auto;
}

.rec-patient-scroll .bento-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: var(--dash-secondary) !important;
    color: var(--dash-white) !important;
}



@media (max-width: 576px) {
    .foc-detail-modal .modal-dialog {
        margin: .85rem;
    }

    .foc-detail-modal .modal-body {
        grid-template-columns: 1fr;
    }

    .foc-request-modal .modal-dialog {
        margin: .85rem;
    }

    .foc-request-grid {
        grid-template-columns: 1fr;
    }

    .foc-detail-modal .modal-body > p:last-child {
        grid-column: auto;
    }

    .foc-detail-grid {
        grid-template-columns: 1fr;
    }

    .foc-request-card.is-full {
        grid-column: auto;
    }




    .doctor-strip-grid {
        grid-template-columns: 1fr;
    }

    .doctor-strip-card {
        border-radius: 18px;
    }
}


/* ── Fallback welcome card ─────────────────────────────────────────────── */
.bento-welcome {
    max-width: 560px;
    margin: 3.5rem auto;
    background: rgba(255, 255, 255, 0.78);
    border-radius: 22px;
    border: 1px solid var(--dash-s2-12);
    box-shadow: 0 18px 60px rgba(27,79,114,0.10);
    padding: 3.25rem 2.5rem;
    text-align: center;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}
.bento-welcome-icon {
    width: 92px;
    height: 92px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.92);
    border: 1px solid var(--dash-s2-18);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.25rem;
    font-size: 2.4rem;
    color: var(--dash-secondary);
}

/* ── Responsive breakpoints ────────────────────────────────────────────── */
@media (max-width: 1200px) {
    .span-3  { grid-column: span 4; }
    .span-7  { grid-column: span 8; }
    .span-4, .span-5 { grid-column: span 6; }
}
@media (max-width: 900px) {
    .span-2, .span-3, .span-4 { grid-column: span 6; }
    .span-7, .span-8 { grid-column: span 12; }
    .row-span-2 { grid-row: span 1; }
}
@media (max-width: 600px) {
    .bento-page { padding: 1rem; }
    .bento-dashboard { gap: 12px; }
    .span-2, .span-3, .span-4, .span-6, .span-7, .span-8, .span-12 { grid-column: span 12; }
    .bento-table thead th,
    .bento-table tbody td { padding-left: .9rem; padding-right: .9rem; }
}
</style>
@endpush

@section('content')
<div class="bento-page">

{{-- ────────────────────────────────────────────────────────────────────────
     PHP flags — each is null when the user's role lacks the gate permission
──────────────────────────────────────────────────────────────────────────── --}}
@php
    $hasClinical  = $todayPatients      !== null;
    $hasReception = $todayRegistrations !== null;
    $hasReceptionistSummary = $receptionistTotalPatients !== null;
    $hasRevenue   = $revenueToday       !== null;
    $hasStaff     = $totalDoctors       !== null;
    $hasQueue     = $primaryQueue       !== null;
    $hasPerf      = $receptionists      !== null;
    $hasOt        = $otToday            !== null;
    $hasFocAlert  = $focAlerts          !== null;
    $focReceptionists = $focReceptionists ?? collect();
    $pendingFocRequests = $pendingFocRequests ?? collect();
    $doctorName = $doctorName ?? auth('hospital_user')->user()?->name;
    $doctorAssignedPatients = $doctorAssignedPatients ?? null;
    $doctorPrimaryDone = $doctorPrimaryDone ?? null;
    $doctorSecondaryDone = $doctorSecondaryDone ?? null;


    $doctorCards = $doctorCards ?? collect();
    $doctorStripCards = $doctorCards;
    $receptionistTodayPatients = $receptionistTodayPatients ?? collect();
    $doctorTodayPatients = $isDoctorUser && $doctorAssignedPatients !== null ? $doctorAssignedPatients : $todayPatients;

    if ($isDoctorUser) {
        $doctorStripCards = $doctorCards->reject(fn ($doctor) => (int) $doctor->id === (int) auth('hospital_user')->id())->values();
    }

    $hasAnyData   = $hasClinical || $hasReception || $hasRevenue || $hasStaff || $hasOt || $hasFocAlert;
@endphp

{{-- Subscription Alert --}}
@if($subscriptionDaysLeft !== null && $subscriptionDaysLeft <= 14)
    <div class="bento-alert {{ $subscriptionDaysLeft <= 3 ? 'bento-alert-danger' : 'bento-alert-warn' }}">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>
            @if($subscriptionDaysLeft <= 0)
                Your subscription has <strong>expired</strong>. Please renew immediately.
            @else
                Subscription expires in <strong>{{ $subscriptionDaysLeft }} day{{ $subscriptionDaysLeft === 1 ? '' : 's' }}</strong>. Please renew soon.
            @endif
        </span>
        <a href="{{ route('hospital.settings.index', ['slug' => $slug]) }}"
           class="ms-auto text-decoration-none fw-semibold" style="color:inherit">Renew Now →</a>
    </div>
@endif

{{-- ────────────────────────────────────────────────────────────────────────
     FALLBACK: No dashboard permissions
──────────────────────────────────────────────────────────────────────────── --}}
@if(!$hasAnyData)
    @php
        $authUser     = auth('hospital_user')->user();
        $hospitalName = $tenant?->name ?? config('app.name');
    @endphp
    <div class="bento-welcome">
        <div class="bento-welcome-icon"><i class="fa-solid fa-hospital"></i></div>
        <h4 class="fw-bold mb-1" style="color:#1B4F72">Welcome to {{ $hospitalName }}</h4>
        <p class="text-muted mb-1">
            Logged in as <strong>{{ $authUser?->name }}</strong>
            @if($authUser?->role?->name) &mdash; {{ $authUser->role->name }} @endif
        </p>
        <p class="text-muted small mb-0">Your role has no dashboard widgets assigned yet. Contact your administrator to configure the appropriate permissions.</p>
    </div>
@else

{{-- ════════════════════════════════════════════════════════════════════════
     BENTO GRID — ROW 1: Stat Metric Cards
     Each card only renders when its permission gate was satisfied.
════════════════════════════════════════════════════════════════════════════ --}}
<div class="bento-dashboard mb-4">

    {{-- Doctor Summary (doctor / ot_doctor) --}}
    @if($isDoctorUser && $doctorAssignedPatients !== null)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-cobalt">
                    <i data-lucide="user-round-check" style="width:22px;height:22px;color:#2980B9;stroke-width:1.75"></i>
                </div>
                <div>
                    <p class="metric-label">Doctor Dashboard</p>
                    <div class="metric-value" style="font-size:20px">{{ $doctorName }}</div>
                    <p class="metric-meta">
                        Assigned: {{ $doctorAssignedPatients }} &bull;
                        Primary: {{ $doctorPrimaryDone }} &bull;
                        Secondary: {{ $doctorSecondaryDone }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Today's Patients (exam.primary.view) --}}
    @if($hasClinical)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-blue">
                    <i data-lucide="users" style="width:22px;height:22px;color:#1B4F72;stroke-width:1.75"></i>
                </div>
                <div>
                    <p class="metric-label">Today's Patients</p>
                    <div class="metric-value">{{ $doctorTodayPatients }}</div>
                    <p class="metric-meta">Assigned to you today</p>
                </div>
            </div>
        </div>

        {{-- Pending Exams --}}
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-orange">
                    <i data-lucide="stethoscope" style="width:22px;height:22px;color:#E67E22;stroke-width:1.75"></i>
                </div>
                <div>
                    <p class="metric-label">Pending Exams</p>
                    <div class="metric-value">{{ $pendingExams }}</div>
                    <p class="metric-meta">In queue</p>
                </div>
            </div>
        </div>

        {{-- Primary Done --}}
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-teal">
                    <i data-lucide="eye" style="width:22px;height:22px;color:#1ABC9C;stroke-width:1.75"></i>
                </div>
                <div>
                    <p class="metric-label">Primary Queue</p>
                    <div class="metric-value">{{ $doctorPrimaryDone }}</div>
                    <p class="metric-meta">Secondary Queue: {{ $doctorSecondaryDone }}</p>
                </div>
            </div>
        </div>
    @endif

    @if($isReceptionistUser && $hasReceptionistSummary)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-blue">
                    <i data-lucide="users" style="width:22px;height:22px;color:#1B4F72;stroke-width:1.75"></i>
                </div>
                <div>
                    <p class="metric-label">Total Patients</p>
                    <div class="metric-value">{{ $receptionistTotalPatients }}</div>
                </div>
            </div>
        </div>

        <div class="bento-card span-3">
            <a href="{{ route('hospital.patients.phone-history', ['slug' => $slug]) }}" class="text-decoration-none" style="color:inherit;display:block;height:100%">
                <div class="bento-stat">
                    <div class="bento-icon ig-teal">
                        <i data-lucide="phone" style="width:22px;height:22px;color:#1ABC9C;stroke-width:1.75"></i>
                    </div>
                    <div>
                        <p class="metric-label">Phone Appointment</p>
                        <div class="metric-value">{{ $receptionistPhoneAppointments }}</div>
                        <p class="metric-meta">Click to view date-wise history</p>
                    </div>
                </div>
            </a>
        </div>
    @endif

    {{-- Today's Registrations (opd.patient.register / opd.patient.register_phone) --}}
    @if($hasReception)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-indigo">
                    <i data-lucide="clipboard-list" style="width:22px;height:22px;color:#34495E;stroke-width:1.75"></i>
                </div>
                <div>
                    <p class="metric-label">{{ $isReceptionistUser ? 'My Patient' : 'Registrations' }}</p>
                    <div class="metric-value">{{ $isReceptionistUser ? $receptionistMyPatients : $todayRegistrations }}</div>
                    <p class="metric-meta">
                        Walk-in: {{ $isReceptionistUser ? $receptionistMyWalkin : $todayWalkin }}
                        &bull;
                        Phone: {{ $isReceptionistUser ? $receptionistMyPhone : $todayPhone }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Revenue Today (reports.view / reports.export context) --}}
    @if($hasRevenue)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-green">
                    <i data-lucide="indian-rupee" style="width:22px;height:22px;color:#27AE60;stroke-width:1.75"></i>
                </div>
                <div>
                    <p class="metric-label">Today Revenue</p>
                    <div class="metric-value">₹{{ number_format($revenueToday, 0) }}</div>
                    <p class="metric-meta">Month: ₹{{ number_format($revenueMonth, 0) }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- OT Stats (ot.patient.list) --}}
    @if($hasOt)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-purple">
                    <i data-lucide="activity" style="width:22px;height:22px;color:#8E44AD;stroke-width:1.75"></i>
                </div>
                <div>
                    <p class="metric-label">OT Today</p>
                    <div class="metric-value">{{ $otToday }}</div>
                    <p class="metric-meta">Done: {{ $otOperated }} &bull; Pending: {{ $otPending }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- FOC Approval Alert (opd.foc.accept) --}}
    <!-- @if($hasFocAlert)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-red">
                    <i data-lucide="file-check" style="width:22px;height:22px;color:#C0392B;stroke-width:1.75"></i>
                </div>
                <div>
                    <p class="metric-label">FOC Approval</p>
                    <div class="d-flex align-items-center gap-2">
                        <div class="metric-value">{{ $focAlerts }}</div>
                        @if($focAlerts > 0)<span class="foc-badge">!</span>@endif
                    </div>
                    <p class="metric-meta">Awaiting approval</p>
                </div>
            </div>
        </div>
    @endif -->

    {{-- Staff Counts (master.doctors / master.receptions) --}}
    @if($hasStaff)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-cobalt">
                    <i data-lucide="user-cog" style="width:22px;height:22px;color:#2980B9;stroke-width:1.75"></i>
                </div>
                <div>
                    <p class="metric-label">Staff</p>
                    <div class="metric-value">{{ $totalDoctors + $totalReceptions }}</div>
                    <p class="metric-meta">Drs: {{ $totalDoctors }} &bull; Rec: {{ $totalReceptions }}</p>
                </div>
            </div>
        </div>
    @endif

</div>{{-- /bento-dashboard row 1 --}}



@if(($isReceptionistUser || $isDoctorUser) && $doctorStripCards->isNotEmpty())
<div class="mb-3 fw-bold" style="color:#1B4F72;font-size:1.05rem;letter-spacing:.02em">
    All Doctors
</div>
<div class="doctor-strip-wrap">
    <div class="doctor-strip-grid">
        @foreach($doctorStripCards as $doctor)
            @php
                $nameParts = preg_split('/\s+/', trim($doctor->name));
                $firstInitial = isset($nameParts[0]) ? substr($nameParts[0], 0, 1) : '';
                $secondInitial = isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : '';
                $doctorInitials = strtoupper($firstInitial.$secondInitial);
                $isOtDoctor = $doctor->role?->slug === 'ot_doctor';
                $isPrimaryDoctor = $doctor->doctor_type === 'primary';
                $isSecondaryDoctor = $doctor->doctor_type === 'secondary';
            @endphp
            <div class="doctor-strip-card">
                <div class="doctor-strip-head">
                    <div class="doctor-strip-avatar">{{ $doctorInitials }}</div>
                    <div>
                        <p class="doctor-strip-name">{{ $doctor->name }}</p>
                        <p class="doctor-strip-sub">{{ $isOtDoctor ? 'OT Doctor' : ($doctor->assigned_today . ' Assigned') }}</p>
                        @if($isOtDoctor)
                            <div class="doctor-strip-pills">
                                <span class="doctor-strip-pill is-muted">Assigned {{ $doctor->assigned_today }}</span>
                            </div>
                        @else
                            <div class="doctor-strip-pills">
                                <span class="doctor-strip-pill {{ $isPrimaryDoctor ? 'is-primary' : 'is-muted' }}">Primary Exam {{ $doctor->primary_count }}</span>
                                <span class="doctor-strip-pill {{ $isSecondaryDoctor ? 'is-secondary' : 'is-muted' }}">Secondary Exam {{ $doctor->secondary_count }}</span>
                            </div>
                        @endif
                        <!-- <div class="doctor-strip-status">All Clear</div> -->
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- ════════════════════════════════════════════════════════════════════════
     ROW 2: Queue (left, col-lg-8) + Revenue/Reception stacked (right, col-lg-4)
     Bootstrap columns for the skeleton · .bento-card for the aesthetics.
════════════════════════════════════════════════════════════════════════════ --}}
@if($hasQueue || $hasPerf || $hasRevenue)
<div class="row g-4 mb-4">

    {{-- Primary Patient Queue (Doctor) ─────────────────────────────────── --}}
    @if($hasQueue)
        <div class="{{ ($hasPerf || $hasRevenue) ? 'col-lg-8' : 'col-12' }}">
            <div class="bento-card h-100">
                <div class="bento-header">
                    <h3 class="bento-title">
                        <i class="fa-solid fa-list-ol me-1"></i> My Primary Queue
                    </h3>
                    <span class="b-badge {{ $primaryQueue->count() > 0 ? 'b-badge-warn' : 'b-badge-green' }}">
                        {{ $primaryQueue->count() }} waiting
                    </span>
                </div>
                <div class="table-responsive dashboard-table-scroll">
                    <table class="bento-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>MRD</th>
                                <th>Patient</th>
                                <th>Age / Gender</th>
                                <th>Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($primaryQueue as $i => $patient)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td><strong>{{ $patient->patient_code }}</strong></td>
                                    <td>{{ $patient->full_name }}</td>
                                    <td>{{ $patient->age }}y / {{ ucfirst($patient->gender) }}</td>
                                    <td>{{ $patient->created_at->format('h:i A') }}</td>
                                    <td>
                                        <a href="{{ route('hospital.exam.primary.show', ['slug' => $slug, 'id' => $patient->id]) }}"
                                           class="hms-btn hms-btn-sm hms-btn-primary">
                                            <i class="fa-solid fa-stethoscope"></i> Examine
                                        </a>
                                        <!-- @haspermission('opd.foc.create')
                                            <button type="button"
                                                    class="hms-btn hms-btn-sm hms-btn-outline"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#focRequestModal{{ $patient->id }}">
                                                <i class="fa-solid fa-hand-holding-heart"></i> Request FOC
                                            </button>
                                        @endhaspermission -->
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4" style="color:#94A3B8">
                                        <i class="fa-regular fa-circle-check fa-xl d-block mb-2" style="color:#27AE60"></i>
                                        Queue is clear
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @haspermission('opd.foc.create')
            @foreach($primaryQueue as $patient)
                <div class="modal fade foc-request-modal" id="focRequestModal{{ $patient->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('hospital.foc.request', ['slug' => $slug]) }}">
                                @csrf
                                <div class="modal-header">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="modal-title-icon">
                                            <i class="fa-solid fa-hand-holding-heart"></i>
                                        </span>
                                        <div>
                                            <div class="foc-modal-kicker">Queue action</div>
                                            <h5 class="modal-title">Request FOC</h5>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                    <input type="hidden" name="doctor_id" value="{{ auth('hospital_user')->id() }}">

                                    <div class="foc-request-grid">
                                        <div class="foc-request-card is-full">
                                            <span class="foc-request-label">Patient Name</span>
                                            <div class="foc-request-value">{{ $patient->full_name }}</div>
                                        </div>

                                        <div class="foc-request-card">
                                            <span class="foc-request-label">Case Fee</span>
                                            <div class="foc-request-value foc-request-fee">₹{{ number_format((float) $patient->case_fee, 2) }}</div>
                                        </div>

                                        <div class="foc-request-card">
                                            <label class="foc-request-label mb-2" for="reception_id_{{ $patient->id }}">Select Receptionist</label>
                                            <select id="reception_id_{{ $patient->id }}" name="reception_id" class="form-select foc-request-select" required>
                                                <option value="">Select Receptionist</option>
                                                @foreach($focReceptionists as $receptionist)
                                                    <option value="{{ $receptionist->id }}">{{ $receptionist->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="foc-request-card is-full">
                                            <label class="foc-request-label mb-2" for="reason_{{ $patient->id }}">Reason</label>
                                            <textarea id="reason_{{ $patient->id }}" name="reason" class="form-control foc-request-textarea" rows="3" placeholder="Why FOC is requested" required></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="hms-btn hms-btn-sm hms-btn-outline" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="hms-btn hms-btn-sm hms-btn-primary">Submit Request</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        @endhaspermission
    @endif

    {{-- Right pane: Revenue + Reception stacked ─────────────────────────── --}}
    @if($hasRevenue || $hasPerf)
        <div class="{{ $hasQueue ? 'col-lg-4' : 'col-12' }} d-flex flex-column gap-4">

            {{-- Revenue Overview (reports.view / reports.export context) --}}
            @if($hasRevenue)
                <div class="bento-card">
                    <div class="bento-header">
                        <h3 class="bento-title"><i class="fa-solid fa-chart-line me-1"></i> Revenue Overview</h3>
                    </div>
                    <div class="rev-grid">
                        <div class="rev-col">
                            <p class="rev-label">Today</p>
                            <div class="rev-value">₹{{ number_format($revenueToday, 0) }}</div>
                        </div>
                        <div class="rev-col">
                            <p class="rev-label">This Month</p>
                            <div class="rev-value">₹{{ number_format($revenueMonth, 0) }}</div>
                        </div>
                        <div class="rev-col">
                            <p class="rev-label">This Year</p>
                            <div class="rev-value">₹{{ number_format($revenueYear, 0) }}</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Reception Performance (master.receptions) --}}
            @if($hasPerf)
                <div class="bento-card">
                    <div class="bento-header">
                        <h3 class="bento-title"><i class="fa-solid fa-headset me-1"></i> Reception — Today</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="bento-table">
                            <thead>
                                <tr>
                                    <th>Receptionist</th>
                                    <th class="text-center">Walk-ins</th>
                                    <th class="text-end">Net (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($receptionists as $rec)
                                    <tr>
                                        <td>{{ $rec->name }}</td>
                                        <td class="text-center">{{ $rec->today_count }}</td>
                                        <td class="text-end"><strong>{{ number_format($rec->today_net, 0) }}</strong></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-3" style="color:#94A3B8">No receptionists found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    @endif

</div>{{-- /row middle --}}



@endif

@if($isReceptionistUser && $hasReception)
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="bento-card foc-premium-card">
            <div class="bento-header">
                <div>
                    <h3 class="bento-title"><i class="fa-solid fa-users me-1"></i> Today Added Patients</h3>
                </div>
                <span class="b-badge {{ $receptionistTodayPatients->count() > 0 ? 'b-badge-info' : 'b-badge-green' }}">
                    {{ $receptionistTodayPatients->count() }} today
                </span>
            </div>
            <div class="table-responsive rec-patient-scroll">
                <table class="bento-table foc-premium-table">
                    <thead style="background-color: #1B4F72 !important;">
                        <tr>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;">#</th>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;">MRD</th>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;">PATIENT NAME</th>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;">CITY / AGE</th>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;">TIME SLOT</th>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;">DOCTOR NAME</th>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;">DR INDEX NO.</th>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;">STATUS</th>
                            <th style="color: #ffffff !important; font-weight: 600; border: none; text-align:center;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($receptionistTodayPatients as $index => $patient)
                            @php
                                $latestOtStatus = $patient->otBookings->first()?->ot_status;
                                $hasPrimaryDone = $patient->primary_done_at !== null;
                                $hasSecondaryDone = $patient->secondary_done_at !== null;
                                $hasOtBooking = $latestOtStatus !== null;

                                $activeOtStatuses = ['booked', 'paid', 'in_ward', 'dilated', 'ready'];
                                $completedOtStatuses = ['operated', 'discharged'];

                                if ($latestOtStatus !== null && in_array($latestOtStatus, $activeOtStatuses, true)) {
                                    $processStatus = 'In Process';
                                } elseif ($hasPrimaryDone && $hasSecondaryDone && (! $hasOtBooking || in_array($latestOtStatus, $completedOtStatuses, true))) {
                                    $processStatus = 'Completed';
                                } elseif ($hasPrimaryDone || $hasSecondaryDone || ($hasOtBooking && in_array($latestOtStatus, $completedOtStatuses, true))) {
                                    $processStatus = 'In Process';
                                } else {
                                    $processStatus = 'Pending';
                                }
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><strong>{{ $patient->patient_code }}</strong></td>
                                <td>{{ $patient->full_name }}</td>
                                <td>{{ $patient->location?->city ?? '—' }} / {{ $patient->age }}y</td>
                                <td>{{ $patient->slot_name ?? '—' }}</td>
                                <td>{{ $patient->doctor?->name ?? '—' }}</td>
                                <td>#{{ $patient->doctor_id ?? '—' }}</td>
                                <td>
                                    <span class="b-badge {{ $processStatus === 'Pending' ? 'b-badge-warn' : ($processStatus === 'In Process' ? 'b-badge-info' : 'b-badge-green') }}">
                                        {{ $processStatus }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <!-- <a href="{{ route('hospital.patients.bill-pdf', ['slug' => $slug, 'patient' => $patient->id]) }}"
                                       class="hms-btn hms-btn-sm hms-btn-print"
                                       title="Print"
                                       aria-label="Print patient bill">
                                        <i class="fa-solid fa-print"></i> -->
                                        
                                    <a href="{{ route('hospital.patients.print', ['slug' => $slug, 'patient' => $patient->id]) }}"
                                       class="action-icon print" title="Print">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4" style="color:#94A3B8">No patients added today</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


@endif

<!-- @if($pendingFocRequests->isNotEmpty() || $hasFocAlert)
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="bento-card foc-premium-card">
            <div class="bento-header">
                <h3 class="bento-title"><i class="fa-solid fa-hand-holding-heart me-1"></i> Pending FOC Requests</h3>
                <span class="b-badge {{ $pendingFocRequests->count() > 0 ? 'b-badge-warn' : 'b-badge-green' }}">{{ $pendingFocRequests->count() }} pending</span>
            </div>
            <div class="table-responsive">
                <table class="bento-table foc-premium-table">
                    <thead style="background-color: #1B4F72 !important;">
                        <tr>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;">#</th>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;">DOCTOR NAME</th>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;">PATIENT NAME</th>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;">MRD</th>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;">FEE TO WAIVE</th>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;" class="text-center">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingFocRequests as $i => $foc)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $foc->doctor?->name ?? '—' }}</td>
                                <td>{{ $foc->patient?->full_name ?? '—' }}</td>
                                <td>{{ $foc->patient?->patient_code ?? '—' }}</td>
                                <td>₹{{ number_format((float) $foc->foc_fee, 2) }}</td>
                                <td>
                                    <button type="button" class="hms-btn hms-btn-sm hms-btn-outline foc-view-btn" data-bs-toggle="modal" data-bs-target="#focViewModal{{ $foc->id }}">
                                        <i class="fa-solid fa-eye"></i> View
                                    </button>

                                    @haspermission('opd.foc.accept')
                                        <form method="POST" action="{{ route('hospital.foc.accept', ['slug' => $slug, 'id' => $foc->id]) }}" style="display:inline">
                                            @csrf
                                            <button type="submit" class="hms-btn hms-btn-sm hms-btn-success foc-accept-btn">Accept</button>
                                        </form>
                                    @else
                                        <span class="text-muted small">No access</span>
                                    @endhaspermission
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4" style="color:#94A3B8">No pending FOC requests</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div> -->

@foreach($pendingFocRequests as $foc)
    <div class="modal fade foc-detail-modal" id="focViewModal{{ $foc->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <!-- <span class="modal-title-icon">
                            <i class="fa-solid fa-hand-holding-heart"></i>
                        </span> -->
                        FOC Request Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1"><strong>Patient:</strong> {{ $foc->patient?->full_name ?? '—' }}</p>
                    <p class="mb-1"><strong>MRD:</strong> {{ $foc->patient?->patient_code ?? '—' }}</p>
                    <p class="mb-1"><strong>Doctor:</strong> {{ $foc->doctor?->name ?? '—' }}</p>
                    <p class="mb-1"><strong>Fee to Waive:</strong> ₹{{ number_format((float) $foc->foc_fee, 2) }}</p>
                    <p class="mb-0"><strong>Reason:</strong><br>{{ $foc->reason ?: 'No reason provided.' }}</p>
                </div>

            </div>
        </div>
    </div>
@endforeach
@endif

{{-- ════════════════════════════════════════════════════════════════════════
     ROW 3: Quick Actions — full width, qa-pill grid inside bento-card
     Icons have explicit font-size so they render correctly.
     @haspermission gates preserved exactly.
════════════════════════════════════════════════════════════════════════════ --}}
<div class="row">
    <div class="col-12">
        <div class="bento-card">
            <div class="bento-header">
                <h3 class="bento-title"><i class="fa-solid fa-bolt me-1"></i> Quick Actions</h3>
            </div>
            <div class="qa-grid">
                @haspermission('opd.patient.register')
                    <a href="{{ route('hospital.patients.create', $slug) }}" class="qa-pill">
                        <i class="fa-solid fa-user-plus" style="font-size:24px;color:#1B4F72"></i>
                        <span>Add Patient</span>
                    </a>
                @endhaspermission
                @if($isReceptionistUser)
                    @haspermission('opd.patient.register_phone')
                        <a href="{{ route('hospital.patients.create-phone', ['slug' => $slug]) }}" class="qa-pill">
                            <i class="fa-solid fa-phone" style="font-size:24px;color:#1ABC9C"></i>
                            <span>Phone Register</span>
                        </a>
                    @endhaspermission
                @endif
                @haspermission('opd.patient.view')
                    <a href="{{ route('hospital.patients.index', $slug) }}" class="qa-pill">
                        <i class="fa-solid fa-users" style="font-size:24px;color:#1ABC9C"></i>
                        <span>All Patients</span>
                    </a>
                @endhaspermission
                @haspermission('ot.booking.create')
                    <a href="{{ route('hospital.ot.index', $slug) }}" class="qa-pill">
                        <i class="fa-solid fa-scalpel" style="font-size:24px;color:#8E44AD"></i>
                        <span>OT Bookings</span>
                    </a>
                @endhaspermission
                <!-- @haspermission('opd.foc.create')
                    <a href="{{ route('hospital.foc.index', $slug) }}" class="qa-pill">
                        <i class="fa-solid fa-hand-holding-heart" style="font-size:24px;color:#C0392B"></i>
                        <span>FOC Cases</span>
                    </a> -->
                @endhaspermission
                @haspermission('opd.reports.view')
                    <a href="{{ route('hospital.reports.index', $slug) }}" class="qa-pill">
                        <i class="fa-solid fa-chart-bar" style="font-size:24px;color:#27AE60"></i>
                        <span>Reports</span>
                    </a>
                @endhaspermission
                @haspermission('master.roles')
                    <a href="{{ route('hospital.roles.index', $slug) }}" class="qa-pill">
                        <i class="fa-solid fa-shield-halved" style="font-size:24px;color:#E67E22"></i>
                        <span>Roles</span>
                    </a>
                @endhaspermission
                @haspermission('master.doctors')
                    <a href="{{ route('hospital.users.create', ['slug' => $slug]) }}" class="qa-pill">
                        <i class="fa-solid fa-user-gear" style="font-size:24px;color:#34495E"></i>
                        <span>Add User</span>
                    </a>
                @endhaspermission
                @haspermission('settings.hospital')
                    <a href="{{ route('hospital.settings.index', $slug) }}" class="qa-pill">
                        <i class="fa-solid fa-gear" style="font-size:24px;color:#34495E"></i>
                        <span>Settings</span>
                    </a>
                @endhaspermission
            </div>
        </div>
    </div>
</div>{{-- /row quick actions --}}

@endif {{-- /hasAnyData --}}

</div>{{-- /bento-page --}}
@endsection