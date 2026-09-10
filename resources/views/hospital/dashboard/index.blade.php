@extends('hospital.layouts.app')
@section('title', 'Dashboard')
{{-- Layout page-header unused; hospital admin welcome banner is in content. --}}

@push('styles')
<style>
/* ── Receptionist 6-card row (Enhanced) ────────────────────────────────────── */
.rec-5row {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 12px;
}
.rec-5card {
    background: #ffffff;
    border: 1px solid rgba(27, 79, 114, 0.12);
    border-radius: 16px;
    min-height: 104px;
    padding: .75rem .8rem .7rem;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: .4rem;
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    text-decoration: none;
    color: inherit;
    position: relative;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    animation: dash-card-pop 500ms cubic-bezier(.22,1,.36,1) both;
}
.rec-5card.rec-5link:hover {
    transform: translateY(-2px);
    border-color: rgba(27, 79, 114, 0.18);
    background: #ffffff;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.10);
}
.rec-5card .bento-gloss {
    position: absolute;
    inset: -40% -20%;
    background: linear-gradient(115deg,
        transparent 35%,
        rgba(255, 255, 255, .7) 48%,
        rgba(27, 79, 114, .1) 52%,
        transparent 65%);
    pointer-events: none;
    z-index: 0;
    animation: bentoGlossSweep 3.2s ease-in-out infinite;
}
.rec-5icon {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    border: 1px solid rgba(27, 79, 114, 0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .92rem;
    flex-shrink: 0;
    font-weight: 600;
    background: #EBF5FB;
    color: #1B4F72;
    position: relative;
    z-index: 1;
    transition: transform .22s ease;
}
.rec-5card:hover .rec-5icon {
    transform: scale(1.06);
}
.rec-5label {
    order: 2;
    position: relative;
    z-index: 1;
    font-size: 9.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: rgba(27,79,114,.68);
    margin: 0;
}
.rec-5value {
    order: 1;
    position: relative;
    z-index: 1;
    font-size: clamp(1.15rem, 1.35vw, 1.4rem);
    font-weight: 900;
    color: #1B4F72;
    line-height: 1.1;
    letter-spacing: -.035em;
}
.rec-5meta {
    order: 3;
    position: relative;
    z-index: 1;
    font-size: 9px;
    font-weight: 600;
    color: rgba(27, 79, 114, .55);
    margin: 0;
    line-height: 1.25;
}
@media (max-width: 1100px) { .rec-5row { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 576px) { .rec-5row { grid-template-columns: 1fr 1fr; } }

/* Receptionist dashboard — fit viewport, scroll only inside patient table */
html:has(.bento-page--receptionist),
body.hms-body:has(.bento-page--receptionist) {
    overflow: hidden;
    height: 100%;
}
.hms-main:has(.bento-page--receptionist) {
    height: 100vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    padding-bottom: 0.75rem;
    box-sizing: border-box;
}
.bento-page--receptionist {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    padding: 0.35rem 1.25rem 0;
    min-height: auto;
}
.bento-page--receptionist .rec-5row {
    flex-shrink: 0;
    margin-bottom: 0.65rem !important;
}
.bento-page--receptionist .rec-5card {
    min-height: 92px;
    padding: 0.65rem 0.75rem 0.6rem;
}
.bento-page--receptionist .rec-dash-patients {
    flex: 1 1 auto;
    min-height: 0;
    margin-bottom: 0 !important;
    --bs-gutter-y: 0;
}
.bento-page--receptionist .rec-dash-patients > .col-12 {
    height: 100%;
    min-height: 0;
}
.bento-page--receptionist .tap-table-wrap {
    height: 90%;
    display: flex;
    flex-direction: column;
    min-height: 0;
}
.bento-page--receptionist #today-patients-results {
    flex: 1 1 auto;
    min-height: 0;
    overflow: hidden;
}
.bento-page--receptionist .tap-panes {
    height: 100%;
    min-height: 0;
}
.bento-page--receptionist .tap-pane.is-active {
    height: 100%;
    display: flex !important;
    flex-direction: column;
    min-height: 0;
}
.bento-page--receptionist .tap-pane.is-active .table-responsive {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}
@media (max-width: 768px) {
    .hms-main:has(.bento-page--receptionist) {
        height: 100dvh;
    }
    .bento-page--receptionist {
        padding: 0.25rem 0.75rem 0;
    }
}

/*
  Hospital Admin Dashboard Theme
  Primary soft: #EBF5FB · Secondary: #1B4F72
  Hover: soft neutral shadow (no blue glow)
*/

/* ── Theme tokens (scoped to this page) ────────────────────────────────── */
.bento-page {
    --dash-primary: #EBF5FB;
    --dash-secondary: #1B4F72;
    --dash-s2-08: rgba(27, 79, 114, 0.08);
    --dash-s2-12: rgba(27, 79, 114, 0.12);
    --dash-s2-18: rgba(27, 79, 114, 0.18);
    --dash-s2-24: rgba(27, 79, 114, 0.24);
    --dash-s2-70: rgba(27, 79, 114, 0.70);
    --dash-s2-82: rgba(27, 79, 114, 0.82);
    --dash-white: #ffffff;
    --dash-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    --dash-shadow-hover: 0 12px 30px rgba(15, 23, 42, 0.10);

    background: #f7fafc;
    padding: 2.25rem 2rem;
    min-height: 100%;
    font-family: 'Inter', -apple-system, 'Segoe UI', Roboto, sans-serif;
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

/* soft background accents */
.bento-page::before {
    content: '';
    position: fixed;
    top: -50%;
    right: -10%;
    width: 560px;
    height: 560px;
    background: radial-gradient(circle, rgba(235,245,251,.55) 0%, transparent 70%);
    z-index: 0;
    pointer-events: none;
}
.bento-page::after {
    content: '';
    position: fixed;
    bottom: -40%;
    left: -5%;
    width: 460px;
    height: 460px;
    background: radial-gradient(circle, rgba(27,79,114,.05) 0%, transparent 70%);
    z-index: 0;
    pointer-events: none;
}

/* ── Welcome banner (hospital admin) ───────────────────────────────────── */
.dash-welcome-card {
    position: relative;
    z-index: 1;
    background: linear-gradient(135deg, rgba(255,255,255,.98) 0%, rgba(235,245,251,.7) 100%);
    border: 1px solid rgba(27, 79, 114, 0.12);
    border-radius: 20px;
    padding: 1rem 1rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    flex-wrap: wrap;
    box-shadow: var(--dash-shadow);
}

.dash-welcome-date {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    background: rgba(27, 79, 114, 0.08);
    color: var(--dash-secondary);
    border-radius: 999px;
    padding: .3rem .8rem;
    font-size: .78rem;
    font-weight: 800;
}

.dash-welcome-title {
    margin: .6rem 0 0;
    font-size: 1.5rem;
    font-weight: 900;
    color: var(--dash-secondary);
    letter-spacing: -.02em;
}

.dash-welcome-wave {
    display: inline-block;
    animation: dash-wave 2.2s ease-in-out infinite;
    transform-origin: 70% 70%;
}

@keyframes dash-wave {
    0%, 60%, 100% { transform: rotate(0deg); }
    10%, 30% { transform: rotate(-12deg); }
    20% { transform: rotate(10deg); }
}

.dash-welcome-sub {
    margin: .35rem 0 0;
    color: var(--dash-s2-70);
    font-size: .92rem;
    font-weight: 600;
}

@media (max-width: 600px) {
    .dash-welcome-card {
        padding: 1.25rem;
    }

    .dash-welcome-title {
        font-size: 1.25rem;
    }
}

/* ── Layout grid ───────────────────────────────────────────────────────── */
.bento-dashboard {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 20px;
    position: relative;
    z-index: 1;
}

/* ── Metric cards (top row) ────────────────────────────────────────────── */
.bento-dashboard > .bento-card {
    border-radius: 18px;
    background: #ffffff;
    min-height: 118px;
    /* border: 1px solid var(--dash-secondary); */
    position: relative;
    overflow: hidden;
    box-shadow: var(--dash-shadow);
    animation: dash-card-pop 500ms cubic-bezier(.22,1,.36,1) both;
}
@keyframes dash-card-pop {
    from { opacity: 0; transform: translateY(12px) scale(.96); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

.bento-dashboard > .bento-card:hover {
    transform: translateY(-2px);
    /* border: 1px solid var(--dash-secondary); */
    background: #ffffff;
    box-shadow: var(--dash-shadow-hover);
}

/* Diagonal glossy splash — continuous sweep across the card */
.bento-dashboard > .bento-card .bento-gloss {
    position: absolute;
    inset: -40% -20%;
    background: linear-gradient(115deg,
        transparent 35%,
        rgba(255, 255, 255, .7) 48%,
        rgba(27, 79, 114, .1) 52%,
        transparent 65%);
    pointer-events: none;
    z-index: 0;
    animation: bentoGlossSweep 3.2s ease-in-out infinite;
}
@keyframes bentoGlossSweep {
    0%   { transform: translateX(-50%) rotate(18deg); opacity: 0; }
    20%  { opacity: .45; }
    50%  { transform: translateX(40%) rotate(18deg); opacity: .35; }
    80%  { opacity: .2; }
    100% { transform: translateX(130%) rotate(18deg); opacity: 0; }
}

.bento-dashboard > .bento-card .bento-stat {
    position: relative;
    z-index: 1;
    flex-direction: column;
    align-items: flex-start;
    gap: .55rem;
    padding: 1rem 1.05rem .95rem;
}

.bento-dashboard > .bento-card .bento-icon {
    width: 38px;
    height: 38px;
    border-radius: 11px;
    background: #EBF5FB;
    border: 1px solid rgba(27, 79, 114, 0.12);
    margin: 0 !important;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform .22s ease;
}

.bento-dashboard > .bento-card:hover .bento-icon {
    transform: scale(1.06);
}

.bento-dashboard > .bento-card .bento-stat > div:not(.bento-icon) {
    display: flex;
    flex-direction: column;
    width: 100%;
}

.bento-dashboard > .bento-card .metric-value {
    order: 1;
    font-size: clamp(1.35rem, 1.8vw, 1.65rem);
    margin-top: 0;
    line-height: 1.1;
    font-weight: 800;
    letter-spacing: -.035em;
    color: var(--dash-secondary);
}

.bento-dashboard > .bento-card .metric-label {
    order: 2;
    margin-top: 0;
    color: var(--dash-s2-82);
    font-size: 10.5px;
    letter-spacing: .1em;
    font-weight: 700;
    text-transform: uppercase;
}

.bento-dashboard > .bento-card .metric-meta {
    order: 3;
    margin-top: .15rem;
    font-size: 11px;
    color: rgba(27, 79, 114, 0.6);
    font-weight: 600;
}

/* ── Card (content panels) ─────────────────────────────────────────────── */
.bento-card {
    background: #ffffff;
    border: 1px solid rgba(27, 79, 114, 0.12);
    border-radius: 18px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform: translateY(0);
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    animation: dash-fade-up 400ms ease both;
    position: relative;
    box-shadow: var(--dash-shadow);
}

.bento-card:hover {
    transform: translateY(-4px);
    border-color: rgba(27, 79, 114, 0.16);
    background: #ffffff;
    box-shadow: var(--dash-shadow-hover);
}

@keyframes dash-fade-up {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
    .bento-card,
    .bento-dashboard > .bento-card,
    .bento-gloss,
    .rec-5card,
    .doctor-strip-card { animation: none; transition: none; }
    .bento-card:hover,
    .bento-dashboard > .bento-card:hover,
    .rec-5card.rec-5link:hover,
    .doctor-strip-card:hover { transform: none; }
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
    gap: 1.2rem;
    padding: 1.35rem 1.5rem;
    height: 100%;
    position: relative;
    z-index: 1;
}

.bento-icon {
    width: 54px;
    height: 54px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: #EBF5FB;
    border: 1px solid rgba(27, 79, 114, 0.12);
    color: var(--dash-secondary);
}

.bento-icon i,
.bento-icon svg {
    font-weight: 600;
}

/* Type-colored icon badges — same idea as the receptionist 5-card row:
   each stat gets a pastel background tinted to match its own icon color
   (set inline per-icon), instead of one flat neutral tone. */
.ig-blue    { background: #EBF5FB !important; border: 1px solid rgba(27, 79, 114, .18) !important; }
.ig-green   { background: #D5F5E3 !important; border: 1px solid rgba(39, 174, 96, .25) !important; }
.ig-orange  { background: #FDEBD0 !important; border: 1px solid rgba(230, 126, 34, .25) !important; }
.ig-teal    { background: #D1F2EB !important; border: 1px solid rgba(26, 188, 156, .25) !important; }
.ig-purple  { background: #F5EEF8 !important; border: 1px solid rgba(142, 68, 173, .25) !important; }
.ig-red     { background: #FADBD8 !important; border: 1px solid rgba(192, 57, 43, .25) !important; }
.ig-indigo  { background: #EAECEE !important; border: 1px solid rgba(52, 73, 94, .2) !important; }
.ig-cobalt  { background: #EAF2FF !important; border: 1px solid rgba(41, 128, 185, .25) !important; }

/* ── Metric typography ─────────────────────────────────────────────────── */
.metric-label {
    font-weight: 800;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: var(--dash-s2-82);
    margin: 0;
}
.metric-value {
    font-weight: 900;
    font-size: 36px;
    color: var(--dash-secondary);
    letter-spacing: -1.2px;
    line-height: 1.1;
    margin: 8px 0 0;
}
.metric-meta {
    font-size: 12px;
    color: var(--dash-s2-70);
    margin: 6px 0 0;
    font-weight: 600;
}

/* ── Card header (section title strip) ─────────────────────────────────── */
.bento-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.15rem 1.4rem;
    border-bottom: 1px solid rgba(27, 79, 114, 0.10);
    background: #ffffff;
}
.bento-title {
    font-size: 1.05rem;
    font-weight: 900;
    color: var(--dash-secondary);
    margin: 0;
    letter-spacing: -0.3px;
    display: flex;
    align-items: center;
    gap: .7rem;
}
.bento-title i {
    font-size: 1.15rem;
    opacity: .9;
}

/* ── Badges ────────────────────────────────────────────────────────────── */
.b-badge {
    display: inline-flex;
    align-items: center;
    font-size: 11px;
    font-weight: 800;
    padding: .4em .9em;
    border-radius: 999px;
    letter-spacing: .03em;
    border: 2px solid rgba(27,79,114,.2);
    background: linear-gradient(135deg, rgba(27,79,114,.12) 0%, rgba(235,245,251,.4) 100%);
    color: var(--dash-secondary);
}
.b-badge-warn {
    /* Pending: red */
    background: linear-gradient(135deg, rgba(220,38,38,.15) 0%, rgba(220,38,38,.08) 100%) !important;
    color: #DC2626 !important;
    border-color: rgba(220, 38, 38, 0.25) !important;
}
.b-badge-info {
    /* In Process: blue (theme) */
    background: linear-gradient(135deg, rgba(27,79,114,.15) 0%, rgba(235,245,251,.5) 100%) !important;
    color: var(--dash-secondary) !important;
    border-color: rgba(27,79,114,.25) !important;
}
.b-badge-green {
    /* Completed: green */
    background: linear-gradient(135deg, rgba(16,185,129,.15) 0%, rgba(16,185,129,.08) 100%) !important;
    color: #10B981 !important;
    border-color: rgba(16, 185, 129, 0.25) !important;
}

/* ── Tables (premium header + soft rows) ───────────────────────────────── */
.bento-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 13.5px;
}
.bento-table thead tr {
    background: linear-gradient(90deg, var(--dash-secondary) 0%, rgba(27,79,114,.95) 100%);
}
.bento-table thead th {
    padding: .9rem 1.2rem;
    font-weight: 800;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: var(--dash-white);
    border-bottom: 1px solid rgba(255, 255, 255, 0.25);
    white-space: nowrap;
}
.bento-table thead th:first-child { border-top-left-radius: 16px; }
.bento-table thead th:last-child  { border-top-right-radius: 16px; }

.bento-table tbody tr {
    background: rgba(255, 255, 255, 0.92);
    transition: all 180ms ease;
}
.bento-table tbody tr:nth-child(even) {
    background: rgba(27, 79, 114, 0.03);
}
.bento-table tbody tr:hover {
    background: #F8FAFC;
}
.bento-table tbody td {
    padding: .9rem 1.2rem;
    color: var(--dash-secondary);
    border-bottom: 1px solid var(--dash-s2-12);
    vertical-align: middle;
    font-weight: 600;
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
.rev-col  { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.5rem .85rem; text-align: center; position: relative; }
.rev-col + .rev-col { border-left: 1.5px solid var(--dash-s2-12); }
.rev-label {
    font-weight: 800;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .11em;
    color: var(--dash-s2-70);
    margin: 0;
}
.rev-value {
    font-weight: 900;
    font-size: 1.55rem;
    color: var(--dash-secondary);
    letter-spacing: -0.5px;
    margin: 8px 0 0;
}

/* ── Alerts (hospital admin subscription) ──────────────────────────────── */
.bento-alert {
    border-radius: 18px;
    padding: 1.1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: .85rem;
    font-size: .95rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    position: relative;
    z-index: 1;
    background: linear-gradient(135deg, rgba(220,38,38,.12) 0%, rgba(220,38,38,.08) 100%);
    border: 2px solid rgba(220,38,38,.25);
}
.bento-alert-warn,
.bento-alert-danger {
    color: #DC2626;
    border-left: 4px solid #DC2626;
}
.bento-alert-link {
    text-decoration: none;
    color: inherit;
    cursor: pointer;
    transition: transform .2s ease, box-shadow .2s ease;
}
.bento-alert-link:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(220, 38, 38, .12);
    color: inherit;
}
.bento-alert-arrow {
    margin-left: auto;
    flex-shrink: 0;
    font-size: 1rem;
    opacity: .85;
}

/* ── Buttons (normalize HMS button colors to match theme) ───────────────── */
.hms-btn {
    border-radius: 14px !important;
    font-weight: 800 !important;
    letter-spacing: .01em;
    transition: all 200ms cubic-bezier(.34, 1.56, .64, 1) !important;
}
.hms-btn:hover { 
    transform: translateY(-2px) !important;
}

.hms-btn-primary,
.hms-btn-success {
    background: linear-gradient(135deg, var(--dash-secondary) 0%, rgba(27,79,114,.88) 100%) !important;
    border-color: var(--dash-secondary) !important;
    color: var(--dash-white) !important;
}
.hms-btn-outline {
    background: rgba(255, 255, 255, 0.96) !important;
    border-color: rgba(27,79,114,.2) !important;
    color: var(--dash-secondary) !important;
}
.hms-btn-outline:hover {
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

/* ── Receptionist doctor strip ────────────────────────────────────────── */
.doctor-strip-wrap {
    margin-bottom: 1.75rem;
}

.doctor-strip-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1.25rem;
}

.doctor-strip-card {
    border: 1px solid rgba(27, 79, 114, 0.12);
    border-radius: 18px;
    background: #ffffff;
    padding: 1.35rem;
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    box-shadow: var(--dash-shadow);
}

.doctor-strip-card:hover {
    transform: translateY(-4px);
    border-color: rgba(27, 79, 114, 0.16);
    background: #ffffff;
    box-shadow: var(--dash-shadow-hover);
}

.doctor-strip-head {
    display: flex;
    align-items: center;
    gap: 1.1rem;
}

.doctor-strip-avatar {
    width: 58px;
    height: 58px;
    border-radius: 999px;
    border: 1px solid rgba(27, 79, 114, 0.12);
    background: #EBF5FB;
    color: var(--dash-secondary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    font-weight: 800;
}

.doctor-strip-title {
    margin: 0;
    font-size: .95rem;
    font-weight: 800;
    color: var(--dash-s2-70);
    line-height: 1;
}

.doctor-strip-name {
    margin: 0;
    color: var(--dash-secondary);
    font-size: 1.1rem;
    font-weight: 900;
    letter-spacing: -.02em;
    line-height: 1.2;
}

.doctor-strip-sub {
    margin: .4rem 0 0;
    font-size: .85rem;
    font-weight: 700;
}
.doctor-strip-sub .assigned-num {
    color: #1B4F72;
    background: #EBF5FB;
    padding: 2px 10px;
    border-radius: 20px;
    font-weight: 800;
    font-size: .82rem;
    margin-right: 4px;
}
.doctor-strip-sub .assigned-text {
    color: var(--dash-s2-70);
}

.doctor-strip-status {
    margin-top: .4rem;
    color: #10B981;
    font-size: .85rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: .4rem;
}

.doctor-strip-pills {
    margin-top: 1rem;
    display: flex;
    gap: .6rem;
    flex-wrap: wrap;
}

.doctor-strip-pill {
    border-radius: 999px;
    padding: .4rem .75rem;
    font-size: .75rem;
    font-weight: 800;
    line-height: 1;
    border: 2px solid rgba(27,79,114,.2);
    transition: all 180ms ease;
}
.doctor-strip-pill.is-primary   { background: linear-gradient(135deg, var(--dash-secondary) 0%, rgba(27,79,114,.88) 100%); color: #fff; border-color: var(--dash-secondary); }
.doctor-strip-pill.is-secondary { background: linear-gradient(135deg, #2563EB 0%, rgba(37,99,235,.88) 100%); color: #fff; border-color: #2563EB; }
.doctor-strip-pill.is-muted     { background: rgba(255,255,255,.95); color: var(--dash-s2-70); }

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
    .doctor-strip-grid {
        grid-template-columns: 1fr;
    }

    .doctor-strip-card {
        border-radius: 18px;
    }
}


/* ── Fallback welcome card ─────────────────────────────────────────────── */
.bento-welcome {
    max-width: 580px;
    margin: 4rem auto;
    background: linear-gradient(135deg, rgba(255,255,255,.98) 0%, rgba(235,245,251,.85) 100%);
    border-radius: 24px;
    border: 2px solid rgba(27,79,114,.2);
    padding: 3.5rem 2.75rem;
    text-align: center;
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
}
.bento-welcome-icon {
    width: 100px;
    height: 100px;
    border-radius: 999px;
    background: linear-gradient(135deg, rgba(255,255,255,.98) 0%, rgba(235,245,251,.65) 100%);
    border: 2px solid rgba(27,79,114,.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 2.6rem;
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
    .doctor-strip-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
    .bento-page { padding: 1.5rem 1rem; }
    .bento-dashboard { gap: 14px; }
    .span-2, .span-3, .span-4, .span-6, .span-7, .span-8, .span-12 { grid-column: span 12; }
    .bento-table thead th,
    .bento-table tbody td { padding-left: .95rem; padding-right: .95rem; }
    .doctor-strip-grid { grid-template-columns: 1fr; }
    .doctor-strip-card { border-radius: 18px; }
    .rec-5row { grid-template-columns: 1fr; }
}

/* ── Patient Status Badge (same as patient list page) ───────────────── */
.dash-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 13px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    border: 1.5px solid transparent;
    backdrop-filter: blur(10px);
}
.dash-status-badge.waiting    { background: linear-gradient(135deg, rgba(26,188,156,.15) 0%, rgba(26,188,156,.08) 100%); color: #1abc9c; border-color: rgba(26,188,156,.3); }
.dash-status-badge.in-progress{ background: linear-gradient(135deg, rgba(27,79,114,.12) 0%, rgba(235,245,251,.4) 100%);  color: #1B4F72; border-color: rgba(27,79,114,.25); }
.dash-status-badge.completed  { background: linear-gradient(135deg, rgba(27,79,114,.1) 0%, rgba(235,245,251,.3) 100%);  color: #1B4F72; border-color: rgba(27,79,114,.2); }

/* ── Wait Status Pill ─────────────────────────────────────────────────────── */
.wait-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border-radius: 999px;
    padding: 4px 12px 4px 4px;
    font-weight: 800;
    white-space: nowrap;
    transition: all .4s cubic-bezier(.34, 1.56, .64, 1);
    vertical-align: middle;
    border: 1.5px solid transparent;
}
.wait-pill.wait-green  { background: linear-gradient(135deg, rgba(22,163,74,.15) 0%, rgba(22,163,74,.08) 100%); border-color: rgba(22,163,74,.3); }
.wait-pill.wait-orange { background: linear-gradient(135deg, rgba(234,88,12,.15) 0%, rgba(234,88,12,.08) 100%); border-color: rgba(234,88,12,.3); }
.wait-pill.wait-red    { background: linear-gradient(135deg, rgba(220,38,38,.15) 0%, rgba(220,38,38,.08) 100%); border-color: rgba(220,38,38,.3); }
.wait-pill.wait-fire   { background: linear-gradient(135deg, rgba(220,38,38,.15) 0%, rgba(234,88,12,.1) 100%); border-color: rgba(220,38,38,.35); animation: fire-glow 1s ease-in-out infinite alternate; }
@keyframes checkin-pulse {
    from { transform: scale(1); border-color: rgba(27,79,114,.35); }
    to   { transform: scale(1.08); border-color: rgba(27,79,114,.6); }
}
@keyframes fire-glow {
    from { border-color: rgba(220,38,38,.35); }
    to   { border-color: rgba(220,38,38,.55); }
}
.wp-r {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    font-size: .7rem;
    font-weight: 900;
    color: #fff;
    flex-shrink: 0;
}
.wait-green  .wp-r { background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); }
.wait-orange .wp-r { background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%); }
.wait-red    .wp-r { background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); }
.wait-fire   .wp-r { background: linear-gradient(135deg,#dc2626,#ea580c); animation: fire-glow 1s ease-in-out infinite alternate; }
.wp-time { font-size: .76rem; font-weight: 800; }
.wait-green  .wp-time { color: #15803d; }
.wait-orange .wp-time { color: #c2410c; }
.wait-red    .wp-time { color: #b91c1c; }
.wait-fire   .wp-time { color: #dc2626; }
</style>
@endpush

@section('content')
<div class="bento-page{{ ($isReceptionistUser ?? false) ? ' bento-page--receptionist' : '' }}">

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
    $isHospitalAdmin = $isHospitalAdmin ?? false;
    $otTotalToday = $otTotalToday ?? null;
    $doctorName = $doctorName ?? auth('hospital_user')->user()?->name;
    $doctorAssignedPatients = $doctorAssignedPatients ?? null;
    $doctorPrimaryDone = $doctorPrimaryDone ?? null;
    $doctorSecondaryDone = $doctorSecondaryDone ?? null;
    $primaryQueueCount = $primaryQueueCount ?? null;
    $secondaryQueueCount = $secondaryQueueCount ?? null;


    $doctorCards = $doctorCards ?? collect();
    $doctorStripCards = $doctorCards;
    $receptionistTodayPatients = $receptionistTodayPatients ?? collect();
    $wGreen  = (int) hospital_setting('wait_green_max',  30);
    $wOrange = (int) hospital_setting('wait_orange_max', 60);
    $wRed    = (int) hospital_setting('wait_red_max',   120);
    // D (dilated) thresholds
    $wDGreen  = (int) hospital_setting('wait_d_green_max',  40);
    $wDOrange = (int) hospital_setting('wait_d_orange_max', 90);
    $wDRed    = (int) hospital_setting('wait_d_red_max',   120);
    // ND (not dilated) thresholds
    $wNdGreen  = (int) hospital_setting('wait_nd_green_max',  20);
    $wNdOrange = (int) hospital_setting('wait_nd_orange_max', 60);
    $wNdRed    = (int) hospital_setting('wait_nd_red_max',   120);
    $doctorTodayPatients = $isDoctorUser && $doctorAssignedPatients !== null ? $doctorAssignedPatients : $todayPatients;

    if ($isDoctorUser) {
        $doctorStripCards = $doctorCards->reject(fn ($doctor) => (int) $doctor->id === (int) auth('hospital_user')->id())->values();
    }

    $hasAnyData   = $isHospitalAdmin
        || $hasClinical
        || $hasReception
        || $hasReceptionistSummary
        || $hasRevenue
        || $hasStaff
        || $hasOt
        || $dischargePendingCount !== null
        || ($accountantPendingCount ?? null) !== null
        || ($wardPendingCount ?? null) !== null
        || ($otAssistantPendingCount ?? null) !== null;
    $pendingShareRequestsCount = $pendingShareRequestsCount ?? null;
@endphp

{{-- Welcome + subscription — hospital admin only --}}
@if($isHospitalAdmin)
<div class="dash-welcome-card">
    <div class="dash-welcome-left">
        <span class="dash-welcome-date"><i class="bi bi-calendar-check"></i> {{ now()->format('d M, Y') }}</span>
        <h2 class="dash-welcome-title">Welcome <span class="dash-welcome-wave">👋</span></h2>
        <p class="dash-welcome-sub">Here's what's happening with {{ $tenant?->name ?? config('app.name') }} today.</p>
    </div>
</div>

@if($subscriptionDaysLeft !== null && $subscriptionDaysLeft <= 30)
    <a href="{{ route('hospital.subscription.index', ['slug' => $slug]) }}"
       class="bento-alert bento-alert-link {{ $subscriptionDaysLeft <= 3 ? 'bento-alert-danger' : 'bento-alert-warn' }}">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>
            @if($subscriptionDaysLeft <= 0)
                Your subscription has <strong>expired</strong>. Please renew now.
            @else
                Subscription expires in <strong>{{ $subscriptionDaysLeft }} day{{ $subscriptionDaysLeft === 1 ? '' : 's' }}</strong>. Please renew soon.
            @endif
        </span>
        <i class="fa-solid fa-chevron-right bento-alert-arrow"></i>
    </a>
@endif
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
        <p class="text-muted small mb-0">No dashboard widgets are enabled for this role. Open <strong>Roles &amp; Permissions → Dashboard → Home widgets</strong> and check the cards you want (Clinical, Reception, Revenue, OT, Staff). Empty counts still show as 0.</p>
    </div>
@else

{{-- ════════════════════════════════════════════════════════════════════════
     RECEPTIONIST — 6 stat cards in a single row (own grid, outside bento)
════════════════════════════════════════════════════════════════════════════ --}}
@if($isReceptionistUser && $receptionistTodayCollection !== null)
<div class="rec-5row mb-4">

    {{-- Today Collection --}}
    <div class="rec-5card">
        <span class="bento-gloss" aria-hidden="true"></span>
        <div class="rec-5icon" style="background:#D5F5E3;color:#27AE60">
            <i class="bi bi-wallet2"></i>
        </div>
        <p class="rec-5label">Today Collection</p>
        <div class="rec-5value">{{ money($receptionistTodayCollection, 0) }}</div>
    </div>

    {{-- Total Patients --}}
    <a href="{{ route('hospital.patients.index', ['slug' => $slug]) }}" class="rec-5card rec-5link">
        <span class="bento-gloss" aria-hidden="true"></span>
        <div class="rec-5icon" style="background:#EBF5FB;color:#1B4F72">
            <i class="bi bi-people-fill"></i>
        </div>
        <p class="rec-5label">Total Patients</p>
        <div class="rec-5value">{{ $todayRegistrations ?? 0 }}</div>
    </a>

    {{-- My Patients --}}
    <a href="{{ route('hospital.patients.index', ['slug' => $slug]) }}" class="rec-5card rec-5link">
        <span class="bento-gloss" aria-hidden="true"></span>
        <div class="rec-5icon" style="background:#EAF2FF;color:#2C6FAC">
            <i class="bi bi-person-check-fill"></i>
        </div>
        <p class="rec-5label">My Patients</p>
        <div class="rec-5value">{{ $receptionistMyPatientsToday }}</div>
    </a>

    {{-- Reports --}}
    @haspermission('report_view')
    <a href="{{ route('hospital.reports.index', ['slug' => $slug]) }}" class="rec-5card rec-5link">
        <span class="bento-gloss" aria-hidden="true"></span>
        <div class="rec-5icon" style="background:#F5EEF8;color:#8E44AD">
            <i class="bi bi-bar-chart-line-fill"></i>
        </div>
        <p class="rec-5label">Reports</p>
        <div class="rec-5value" style="font-size:1.15rem">View →</div>
    </a>
    @endhaspermission

    {{-- Phone Appointments --}}
    <a href="{{ route('hospital.patients.phone-history', ['slug' => $slug]) }}" class="rec-5card rec-5link">
        <span class="bento-gloss" aria-hidden="true"></span>
        <div class="rec-5icon" style="background:#D1F2EB;color:#1ABC9C">
            <i class="bi bi-telephone-fill"></i>
        </div>
        <p class="rec-5label">Phone Appt</p>
        <div class="rec-5value">{{ $receptionistTodayPhone }}</div>
    </a>

    {{-- OT Appointment --}}
    @if($hasOt)
    <a href="{{ route('hospital.dashboard.ot-appointments', ['slug' => $slug]) }}" class="rec-5card rec-5link">
        <span class="bento-gloss" aria-hidden="true"></span>
        <div class="rec-5icon" style="background:#FCE4EC;color:#C2185B">
            <i class="bi bi-activity"></i>
        </div>
        <p class="rec-5label">OT Appointment</p>
        <div class="rec-5value">{{ $otToday }}</div>
        <p class="rec-5meta">Confirmed: {{ $otOperated }} • Booked: {{ $otPending }}</p>
    </a>
    @endif

</div>
@endif

{{-- ════════════════════════════════════════════════════════════════════════
     BENTO GRID — ROW 1: Stat Metric Cards (not used on receptionist dashboard)
════════════════════════════════════════════════════════════════════════════ --}}
@if(!$isReceptionistUser)
<div class="bento-dashboard mb-4">

@if($isHospitalAdmin)
    {{-- Hospital admin: only these 8 cards --}}

    <div class="bento-card span-2">
        <span class="bento-gloss" aria-hidden="true"></span>
        <div class="bento-stat">
            <div class="bento-icon ig-blue">
                <i class="bi bi-people-fill" style="font-size:22px;color:#1B4F72"></i>
            </div>
            <div>
                <p class="metric-label">Total Today Patients</p>
                <div class="metric-value">{{ $todayPatients ?? 0 }}</div>
                <p class="metric-meta">{{ now()->format('d M Y') }}</p>
            </div>
        </div>
    </div>

    <a href="{{ route('hospital.dashboard.collection', ['slug' => $slug]) }}" class="bento-card span-2 text-decoration-none">
        <span class="bento-gloss" aria-hidden="true"></span>
        <div class="bento-stat">
            <div class="bento-icon ig-green">
                <i class="bi bi-wallet2" style="font-size:22px;color:#27AE60"></i>
            </div>
            <div>
                <p class="metric-label">Total Collection</p>
                <div class="metric-value">{{ money($revenueToday ?? 0, 0) }}</div>
            </div>
        </div>
    </a>

    <a href="{{ route('hospital.reports.index', ['slug' => $slug]) }}" class="bento-card span-2 text-decoration-none">
        <span class="bento-gloss" aria-hidden="true"></span>
        <div class="bento-stat">
            <div class="bento-icon ig-indigo">
                <i class="bi bi-file-earmark-bar-graph" style="font-size:22px;color:#34495E"></i>
            </div>
            <div>
                <p class="metric-label">Report</p>
                <div class="metric-value" style="font-size:1.15rem">—</div>
                <p class="metric-meta">Summary card</p>
            </div>
        </div>
    </a>

    <a href="{{ route('hospital.users.index', ['slug' => $slug, 'role' => 'doctor']) }}" class="bento-card span-2 text-decoration-none">
        <span class="bento-gloss" aria-hidden="true"></span>
        <div class="bento-stat">
            <div class="bento-icon ig-cobalt">
                <i class="bi bi-person-circle" style="font-size:22px;color:#2980B9"></i>
            </div>
            <div>
                <p class="metric-label">Doctor</p>
                <div class="metric-value">{{ $totalDoctors ?? 0 }}</div>
            </div>
        </div>
    </a>

    <a href="{{ route('hospital.users.index', ['slug' => $slug, 'role' => 'receptionist']) }}" class="bento-card span-2 text-decoration-none">
        <span class="bento-gloss" aria-hidden="true"></span>
        <div class="bento-stat">
            <div class="bento-icon" style="background:rgba(26,188,156,.12);">
                <i class="bi bi-headset" style="font-size:22px;color:#1ABC9C"></i>
            </div>
            <div>
                <p class="metric-label">Reception</p>
                <div class="metric-value">{{ $totalReceptions ?? 0 }}</div>
            </div>
        </div>
    </a>

    <div class="bento-card span-2">
        <span class="bento-gloss" aria-hidden="true"></span>
        <div class="bento-stat">
            <div class="bento-icon ig-teal">
                <i class="bi bi-eye-fill" style="font-size:22px;color:#1ABC9C"></i>
            </div>
            <div>
                <p class="metric-label">Primary / Second</p>
                <div class="metric-value">{{ $todayPrimary ?? 0 }}/{{ $todaySecondary ?? 0 }}</div>
            </div>
        </div>
    </div>

    <a href="{{ route('hospital.dashboard.doctor-ot', ['slug' => $slug]) }}" class="bento-card span-2 text-decoration-none">
        <span class="bento-gloss" aria-hidden="true"></span>
        <div class="bento-stat">
            <div class="bento-icon ig-purple">
                <i class="bi bi-activity" style="font-size:22px;color:#8E44AD"></i>
            </div>
            <div>
                <p class="metric-label">OT Total</p>
                <div class="metric-value">{{ $otTotalToday ?? 0 }}</div>
            </div>
        </div>
    </a>

    @if(($pendingShareRequestsCount ?? null) !== null)
        <a href="{{ route('hospital.doctor.history', ['slug' => $slug]) }}?_tab=request"
            class="bento-card span-2 text-decoration-none" style="position:relative;">
            <span class="bento-gloss" aria-hidden="true"></span>
            <div class="bento-stat">
                <div class="bento-icon" style="background:rgba(13,148,136,.12);">
                    <i class="bi bi-send-fill" style="font-size:22px;color:#0d9488"></i>
                </div>
                <div>
                    <p class="metric-label">Incoming Requests</p>
                    <div class="metric-value">{{ $pendingShareRequestsCount }}</div>
                </div>
            </div>
            @if($pendingShareRequestsCount > 0)
                <span style="position:absolute;top:12px;right:14px;background:#dc2626;color:#fff;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;">
                    {{ $pendingShareRequestsCount }} New
                </span>
            @endif
        </a>
    @endif

@else
    {{-- Non-admin: existing card set (unchanged) --}}

    {{-- Doctor Summary (doctor / ot_doctor) --}}
    @if($isDoctorUser && $doctorAssignedPatients !== null)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-cobalt">
                    <i class="bi bi-person-check-fill" style="font-size:22px;color:#2980B9"></i>
                </div>
                <div>
                    <p class="metric-label">Doctor Dashboard</p>
                    <div class="metric-value" style="font-size:20px">{{ $doctorName }}</div>
                    <p class="metric-meta">
                        Assigned: {{ $doctorAssignedPatients }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Incoming Share Requests (hospital admin only) --}}
    @if(($pendingShareRequestsCount ?? null) !== null)
        <a href="{{ route('hospital.doctor.history', ['slug' => $slug]) }}?_tab=request"
            class="bento-card span-3 text-decoration-none" style="position:relative;">
            <div class="bento-stat">
                <div class="bento-icon" style="background:rgba(13,148,136,.12);">
                    <i class="bi bi-send-fill" style="font-size:22px;color:#0d9488"></i>
                </div>
                <div>
                    <p class="metric-label">Incoming Requests</p>
                    <div class="metric-value">{{ $pendingShareRequestsCount }}</div>
                </div>
            </div>
            @if($pendingShareRequestsCount > 0)
                <span style="position:absolute;top:12px;right:14px;background:#dc2626;color:#fff;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;">
                    {{ $pendingShareRequestsCount }} New
                </span>
            @endif
        </a>
    @endif

    {{-- OT Management Overview (hospital admin only) — Phase 8 of OT Workflow Upgrade --}}
    @if($otOverview ?? null)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-blue">
                    <i class="bi bi-people-fill" style="font-size:22px;color:#1B4F72"></i>
                </div>
                <div>
                    <p class="metric-label">Total Patients</p>
                    <div class="metric-value">{{ $otOverview['total_patients'] }}</div>
                    <p class="metric-meta">All-time, this hospital</p>
                </div>
            </div>
        </div>

        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon" style="background:rgba(39,174,96,.12);">
                    <i class="bi bi-scissors" style="font-size:22px;color:#27AE60"></i>
                </div>
                <div>
                    <p class="metric-label">Surgeries Completed</p>
                    <div class="metric-value">{{ $otOverview['surgeries_completed'] }}</div>
                    <p class="metric-meta">This month</p>
                </div>
            </div>
        </div>

        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon" style="background:rgba(27,79,114,.12);">
                    <i class="bi bi-eye-fill" style="font-size:22px;color:#1B4F72"></i>
                </div>
                <div>
                    <p class="metric-label">Lens Consumption</p>
                    <div class="metric-value">{{ $otOverview['lens_consumption'] }}</div>
                    <p class="metric-meta">Lenses implanted, this month</p>
                </div>
            </div>
        </div>

        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon" style="background:rgba(230,126,34,.12);">
                    <i class="bi bi-box-seam" style="font-size:22px;color:#E67E22"></i>
                </div>
                <div>
                    <p class="metric-label">Lens Low Stock</p>
                    <div class="metric-value">{{ $otOverview['lens_low_stock'] ?? 0 }}</div>
                    <p class="metric-meta">Stock ≤ 5 units</p>
                </div>
            </div>
        </div>

        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon" style="background:rgba(192,57,43,.12);">
                    <i class="bi bi-calendar-x" style="font-size:22px;color:#C0392B"></i>
                </div>
                <div>
                    <p class="metric-label">Lens Near Expiry</p>
                    <div class="metric-value">{{ $otOverview['lens_near_expiry'] ?? 0 }}</div>
                    <p class="metric-meta">Expires within 30 days</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Today's Patients (exam.primary.view) --}}
    @if($hasClinical)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-blue">
                    <i class="bi bi-people-fill" style="font-size:22px;color:#1B4F72"></i>
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
                    <i class="bi bi-clipboard2-pulse" style="font-size:22px;color:#E67E22"></i>
                </div>
                <div>
                    <p class="metric-label">Pending Exams</p>
                    <div class="metric-value">{{ $pendingExams }}</div>
                    <p class="metric-meta">In queue</p>
                </div>
            </div>
        </div>
        {{-- Primary & Secondary Queue --}}
        @php
            $cardPrimary   = $primaryQueueCount   ?? 0;
            $cardSecondary = $secondaryQueueCount  ?? 0;
        @endphp
<div class="bento-card span-3">
    <div class="px-3 pt-3 pb-3">
<div class="d-flex align-items-center gap-4">
    <div class="bento-icon ig-teal">
        <i class="bi bi-eye-fill" style="font-size:20px"></i>
    </div>

    <p class="metric-label mb-0">OPD QUEUE</p>
</div>
    </div>

    <div class="d-flex gap-3 px-3 pb-3">
        <div class="flex-fill text-center py-2"
             style="background:#e8f8f5;border-radius:15px;">
            <div style="font-size:22px;font-weight:800;color:#1abc9c;">
                {{ $cardPrimary }}
            </div>
            <div class="metric-label" style="font-size:10px;">
                PRIMARY
            </div>
        </div>

        <div class="flex-fill text-center py-2"
             style="background:#eaf4fb;border-radius:15px;">
            <div style="font-size:22px;font-weight:800;color:#1B4F72;">
                {{ $cardSecondary }}
            </div>
            <div class="metric-label" style="font-size:10px;">
                SECONDARY
            </div>
        </div>
    </div>
</div>
    @endif

    {{-- Today's Registrations (patient_register) --}}
    @if($hasReception && !$isReceptionistUser)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-indigo">
                    <i class="bi bi-clipboard-check" style="font-size:22px;color:#34495E"></i>
                </div>
                <div>
                    <p class="metric-label">Registrations</p>
                    <div class="metric-value">{{ $todayRegistrations }}</div>
                    <p class="metric-meta">Walk-in: {{ $todayWalkin }} &bull; Phone: {{ $todayPhone }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Revenue Today --}}
    @if($hasRevenue && !$isReceptionistUser)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-green">
                    <i class="bi bi-currency-rupee" style="font-size:22px;color:#27AE60"></i>
                </div>
                <div>
                    <p class="metric-label">Today Revenue</p>
                    <div class="metric-value">{{ money($revenueToday, 0) }}</div>
                    <p class="metric-meta">Month: {{ money($revenueMonth, 0) }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Accountant: Pending Patients / Refunds / Completed (replaces OT Appointment card) --}}
    @if($isAccountantUser && $accountantPendingCount !== null)
        <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug, 'filter' => 'today']) }}"
           class="bento-card span-4 text-decoration-none">
            <span class="bento-gloss" aria-hidden="true"></span>
            <div class="bento-stat">
                <div class="bento-icon" style="background:#FDEBD0;color:#E67E22">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <p class="metric-label">Pending Patients</p>
                    <div class="metric-value">{{ $accountantPendingCount }}</div>
                    <p class="metric-meta">Awaiting OT package payment</p>
                </div>
            </div>
        </a>

        <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug, 'filter' => 'refunds']) }}"
           class="bento-card span-4 text-decoration-none">
            <span class="bento-gloss" aria-hidden="true"></span>
            <div class="bento-stat">
                <div class="bento-icon" style="background:#FADBD8;color:#C0392B">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </div>
                <div>
                    <p class="metric-label">Refunds</p>
                    <div class="metric-value">{{ $accountantRefundsCount }}</div>
                    <p class="metric-meta">Surgery refused</p>
                </div>
            </div>
        </a>

        <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug, 'filter' => 'completed']) }}"
           class="bento-card span-4 text-decoration-none">
            <span class="bento-gloss" aria-hidden="true"></span>
            <div class="bento-stat">
                <div class="bento-icon" style="background:#D5F5E3;color:#229954">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <p class="metric-label">Completed</p>
                    <div class="metric-value">{{ $accountantCompletedCount }}</div>
                    <p class="metric-meta">Payment verified &amp; onward</p>
                </div>
            </div>
        </a>
    {{-- Ward Management: Pending Patient (replaces OT Appointment card) --}}
    @elseif($isWardManagementUser && $wardPendingCount !== null)
        <a href="{{ route('hospital.ot.ward.index', ['slug' => $slug]) }}"
           class="bento-card span-4 text-decoration-none">
            <span class="bento-gloss" aria-hidden="true"></span>
            <div class="bento-stat">
                <div class="bento-icon" style="background:#FDEBD0;color:#E67E22">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <p class="metric-label">Pending Patient</p>
                    <div class="metric-value">{{ $wardPendingCount }}</div>
                    <p class="metric-meta">Awaiting ward entry</p>
                </div>
            </div>
        </a>
    {{-- OT Assistant: Pending Patient (replaces OT Appointment card) --}}
    @elseif($isOtAssistantUser && $otAssistantPendingCount !== null)
        <a href="{{ route('hospital.ot.assistant.dashboard', ['slug' => $slug]) }}"
           class="bento-card span-4 text-decoration-none">
            <span class="bento-gloss" aria-hidden="true"></span>
            <div class="bento-stat">
                <div class="bento-icon" style="background:#FDEBD0;color:#E67E22">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <p class="metric-label">Pending Patient</p>
                    <div class="metric-value">{{ $otAssistantPendingCount }}</div>
                    <p class="metric-meta">Ready for OT — record surgery</p>
                </div>
            </div>
        </a>
    {{-- Discharge Counter: Pending Patient (Billing Desk queue) --}}
    @elseif($isDischargeCounterUser && $dischargePendingCount !== null)
        <a href="{{ route('hospital.ot.billing.index', ['slug' => $slug]) }}"
           class="bento-card span-4 text-decoration-none">
            <span class="bento-gloss" aria-hidden="true"></span>
            <div class="bento-stat">
                <div class="bento-icon" style="background:#FDEBD0;color:#E67E22">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <p class="metric-label">Pending Patient</p>
                    <div class="metric-value">{{ $dischargePendingCount }}</div>
                    <p class="metric-meta">Discharge &amp; invoices pending</p>
                </div>
            </div>
        </a>
    {{-- OT Appointment (ot_patient_list / ot_appointment_view) --}}
    {{-- Receptionist: shown in the top 6-card row instead --}}
    @elseif($hasOt && !$isReceptionistUser)
        <a href="{{ route('hospital.dashboard.ot-appointments', ['slug' => $slug]) }}"
           class="bento-card span-3 text-decoration-none">
            <div class="bento-stat">
                <div class="bento-icon ig-purple">
                    <i class="bi bi-activity" style="font-size:22px;color:#8E44AD"></i>
                </div>
                <div>
                    <p class="metric-label">OT Appointment</p>
                    <div class="metric-value">{{ $otToday }}</div>
                    <p class="metric-meta">Confirmed: {{ $otOperated }} &bull; Booked: {{ $otPending }}</p>
                </div>
            </div>
        </a>
    @endif

    {{-- Staff Counts (user_doctor_manage / user_reception_manage) --}}
    @if($hasStaff)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-cobalt">
                    <i class="bi bi-person-gear" style="font-size:22px;color:#2980B9"></i>
                </div>
                <div>
                    <p class="metric-label">Staff</p>
                    <div class="metric-value">{{ $totalDoctors + $totalReceptions }}</div>
                    <!-- <p class="metric-meta">Drs: {{ $totalDoctors }} &bull; Rec: {{ $totalReceptions }}</p> -->
                </div>
            </div>
        </div>
    @endif

@endif
</div>
@endif

@if($isDoctorUser && $doctorStripCards->isNotEmpty())
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
                $isOtDoctor = $doctor->role?->slug === 'ot_assistant';
                $isPrimaryDoctor = $doctor->doctor_type === 'primary';
                $isSecondaryDoctor = $doctor->doctor_type === 'secondary';
            @endphp
            <div class="doctor-strip-card">
                <div class="doctor-strip-head">
                    <div class="doctor-strip-avatar">{{ $doctorInitials }}</div>
                    <div>
                        <p class="doctor-strip-name">{{ $doctor->name }}</p>
                        <p class="doctor-strip-sub">
                            <span class="assigned-num">{{ $doctor->assigned_today }}</span>
                            <span class="assigned-text">Assigned</span>
                        </p>
                        @if(!$isOtDoctor && $doctor->primary_count === 0 && $doctor->secondary_count === 0)
                            <div class="doctor-strip-status"><i class="bi bi-check-circle-fill me-1"></i>All Clear</div>
                        @endif
                    </div>
                </div>
                @if($isOtDoctor)
                    <div class="doctor-strip-pills">
                        <span class="doctor-strip-pill is-muted">OT Doctor</span>
                    </div>
                @else
                    <div class="doctor-strip-pills">
                        <span class="doctor-strip-pill {{ $doctor->primary_count > 0 ? 'is-primary' : 'is-muted' }}">Primary Exam {{ $doctor->primary_count }}</span>
                        <span class="doctor-strip-pill {{ $doctor->secondary_count > 0 ? 'is-secondary' : 'is-muted' }}">Secondary Exam {{ $doctor->secondary_count }}</span>
                    </div>
                @endif
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
                                @php
                                    $qWaitMins  = (int) $patient->created_at->diffInMinutes(now());
                                    $qWaitClass = $qWaitMins < $wGreen ? 'wait-green' : ($qWaitMins < $wOrange ? 'wait-orange' : ($qWaitMins < $wRed ? 'wait-red' : 'wait-fire'));
                                    $qWaitFmt   = $qWaitMins < 60 ? $qWaitMins.'m' : floor($qWaitMins/60).'h'.($qWaitMins%60 > 0 ? ' '.($qWaitMins%60).'m' : '');
                                @endphp
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td><strong>{{ $patient->patient_code }}</strong></td>
                                    <td>{{ $patient->full_name }}</td>
                                    <td>{{ $patient->age }}y / {{ ucfirst($patient->gender) }}</td>
                                    <td>
                                        {{ $patient->created_at->format('h:i A') }}
                                        <span class="wait-pill {{ $qWaitClass }}" data-wait-from="{{ $patient->created_at->toIso8601String() }}" style="margin-left:6px">
                                            <span class="wp-r">R</span>
                                            <span class="wp-time">{{ $qWaitFmt }}</span>
                                        </span>
                                    </td>
                                    <td>
                                        @haspermission('exam_primary')
                                        <a href="{{ route('hospital.exam.primary.show', ['slug' => $slug, 'id' => $patient->id]) }}"
                                           class="hms-btn hms-btn-sm hms-btn-primary">
                                            <i class="fa-solid fa-stethoscope"></i> Examine
                                        </a>
                                        @endhaspermission
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
    @endif

    {{-- Right pane: Revenue + Reception stacked ─────────────────────────── --}}
    @if($hasRevenue || $hasPerf)
        <div class="{{ $hasQueue ? 'col-lg-4' : 'col-12' }} d-flex flex-column gap-4">

            {{-- Revenue Overview (report_view / report_export context) --}}
            @if($hasRevenue)
                <div class="bento-card">
                    <div class="bento-header">
                        <h3 class="bento-title"><i class="fa-solid fa-chart-line me-1"></i> Revenue Overview</h3>
                    </div>
                    <div class="rev-grid">
                        <div class="rev-col">
                            <p class="rev-label">Today</p>
                            <div class="rev-value">{{ money($revenueToday, 0) }}</div>
                        </div>
                        <div class="rev-col">
                            <p class="rev-label">This Month</p>
                            <div class="rev-value">{{ money($revenueMonth, 0) }}</div>
                        </div>
                        <div class="rev-col">
                            <p class="rev-label">This Year</p>
                            <div class="rev-value">{{ money($revenueYear, 0) }}</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Reception Performance (user_reception_manage) --}}
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
                                    <th class="text-end">Net ({{ currency_symbol() }})</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($receptionists as $rec)
                                    <tr>
                                        <td>{{ $rec->name }}</td>
                                        <td class="text-center">{{ $rec->today_count }}</td>
                                        <td class="text-end"><strong>{{ money($rec->today_net, 0) }}</strong></td>
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
</div>

@endif

@if($isReceptionistUser && $hasReception)
<style>
.tap-table-wrap { background:#ffffff; border-radius:16px; border:1px solid rgba(27,79,114,.12); overflow:hidden; box-shadow:0 1px 2px rgba(15,23,42,.04); }
.tap-header { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; padding:16px 20px 14px; border-bottom:1px solid #edf2f7; }
.tap-title { font-size:15px; font-weight:700; color:#1B4F72; display:flex; align-items:center; gap:8px; margin:0; flex:1 1 auto; min-width:0; }
.tap-title i { font-size:14px; opacity:.8; }
.tap-count { background:#EBF5FB; color:#1B4F72; font-size:12px; font-weight:700; padding:4px 12px; border-radius:20px; letter-spacing:.3px; flex-shrink:0; }
.tap-header #today-patients-form { flex:1 1 220px; min-width:0; max-width:100%; }
.tap-header #today-patients-form .input-group { width:100% !important; max-width:320px; }
.tap-table { width:100%; border-collapse:collapse; font-size:13px; }
.tap-table thead tr { background:#1B4F72; }
.tap-table thead th { color:rgba(255,255,255,.88); font-weight:600; font-size:11px; letter-spacing:.6px; text-transform:uppercase; padding:11px 14px; border:none; white-space:nowrap; }
.tap-table tbody tr { border-bottom:1px solid #f1f5f9; transition:background .15s; }
.tap-table tbody tr:last-child { border-bottom:none; }
.tap-table tbody tr:hover { background:#F8FAFC; }
.tap-table tbody td { padding:12px 14px; vertical-align:middle; color:#374151; }
.tap-mrd { font-family:monospace; font-size:12.5px; font-weight:700; color:#1B4F72; background:#EBF5FB; padding:3px 8px; border-radius:6px; letter-spacing:.5px; }
.tap-patient-cell { display:flex; align-items:center; gap:10px; }
.tap-avatar { width:34px; height:34px; border-radius:50%; background:#1B4F72; color:#fff; font-size:13px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.tap-name { font-weight:600; font-size:13px; color:#1e293b; line-height:1.3; }
.tap-type-pill { font-size:10px; font-weight:600; padding:1px 7px; border-radius:10px; letter-spacing:.2px; display:inline-block; margin-top:2px; }
.tap-type-phone { background:#FEF3C7; color:#92400E; }
.tap-type-walkin { background:#DBEAFE; color:#1e40af; }
.tap-type-ot { background:#EDE9FE; color:#5B21B6; }
.tap-meta { font-size:12px; color:#64748b; }
.tap-meta strong { color:#334155; font-weight:600; }
.tap-slot { font-size:12px; color:#475569; }
.tap-slot i { color:#94a3b8; margin-right:3px; }
.tap-dr-index { font-family:monospace; font-size:12.5px; font-weight:700; color:#1B4F72; background:#EBF5FB; padding:3px 9px; border-radius:6px; }
.tap-status-done     { display:inline-flex; align-items:center; gap:5px; font-size:11.5px; font-weight:700; color:#15803d; background:#dcfce7; padding:4px 10px; border-radius:20px; }
.tap-status-primary  { display:inline-flex; align-items:center; gap:5px; font-size:11.5px; font-weight:700; color:#1B4F72; background:#dbeafe; padding:4px 10px; border-radius:20px; }
.tap-status-waiting  { display:inline-flex; align-items:center; gap:5px; font-size:11.5px; font-weight:700; color:#b45309; background:#fef3c7; padding:4px 10px; border-radius:20px; }
.tap-print-btn { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; border:1px solid #e2e8f0; color:#64748b; background:#fff; transition:all .15s; text-decoration:none; }
.tap-print-btn:hover { background:#1B4F72; color:#fff; border-color:#1B4F72; }
.tap-empty { text-align:center; padding:3rem 1rem; color:#94a3b8; font-size:13.5px; }
.tap-tabs { display:flex; gap:6px; padding:12px 20px 0; border-bottom:1px solid #edf2f7; overflow-x:auto; -webkit-overflow-scrolling:touch; scrollbar-width:thin; }
.tap-tab-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; font-size:12.5px; font-weight:700; color:#64748b; background:none; border:none; border-bottom:2px solid transparent; cursor:pointer; margin-bottom:-1px; transition:color .15s, border-color .15s; white-space:nowrap; flex-shrink:0; }
.tap-tab-btn:hover { color:#1B4F72; }
.tap-tab-btn.tap-tab-active { color:#1B4F72; border-bottom-color:#1B4F72; }
.tap-tab-n { display:inline-flex; align-items:center; justify-content:center; min-width:18px; height:18px; padding:0 5px; margin-left:2px; border-radius:9px; background:#e2e8f0; color:#475569; font-size:10px; font-weight:800; }
.tap-tab-btn.tap-tab-active .tap-tab-n { background:#1B4F72; color:#fff; }
.tap-pane:not(.is-active),
.tap-pane[hidden] { display:none !important; }
@media (max-width: 640px) {
    .tap-header { padding:14px 14px 12px; }
    .tap-header #today-patients-form { flex:1 1 100%; order:3; }
    .tap-header #today-patients-form .input-group { max-width:100%; }
    .tap-count { order:2; }
    .tap-tabs { padding:10px 14px 0; }
    .tap-table-wrap { border-radius:14px; }
}
</style>
<div class="row g-4 mb-4 rec-dash-patients">
    <div class="col-12">
        <div class="tap-table-wrap">
            <!-- <div class="tap-header">
                <h3 class="tap-title"><i class="bi bi-people-fill"></i> Today Added Patients</h3>
                <span class="tap-count">{{ $receptionistTodayPatients->count() }} today</span>
            </div> -->
            <div class="tap-header">
                <h3 class="tap-title"><i class="bi bi-people-fill"></i> Today Added Patients</h3>

                <form method="GET" action="{{ route('hospital.dashboard', ['slug' => $slug]) }}" id="today-patients-form" class="d-flex gap-2">
                    <div class="input-group">
                        <input type="text" name="search_contact" value="{{ request('search_contact') }}"
                            class="form-control form-control-sm" placeholder="Search by mobile..."
                            data-intl-phone>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
                        @if(request('search_contact'))
                            <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                        @endif
                    </div>
                </form>

                <span class="tap-count" id="today-patients-count">{{ $receptionistTodayPatients->count() }} today</span>
            </div>

            <div class="tap-tabs" id="today-patients-tabs">
                <button type="button" class="tap-tab-btn tap-tab-active" data-tap-tab="walkin">
                    <i class="bi bi-person-walking"></i> Walk-in <span class="tap-tab-n" data-tap-n="walkin">0</span>
                </button>
                <button type="button" class="tap-tab-btn" data-tap-tab="phone">
                    <i class="bi bi-telephone"></i> Phone <span class="tap-tab-n" data-tap-n="phone">0</span>
                </button>
                <button type="button" class="tap-tab-btn" data-tap-tab="ot">
                    <i class="bi bi-hospital"></i> OT <span class="tap-tab-n" data-tap-n="ot">0</span>
                </button>
            </div>

            <div id="today-patients-results">
                @include('hospital.dashboard.partials.receptionist-today-patients-table')
            </div>
            <script>
                (function () {
                    var tabsWrap = document.getElementById('today-patients-tabs');
                    var results = document.getElementById('today-patients-results');
                    if (!tabsWrap || !results) return;

                    window.__todayPatientsTab = window.__todayPatientsTab || 'walkin';

                    window.applyTodayPatientsTabFilter = function () {
                        var tab = window.__todayPatientsTab || 'walkin';
                        results.querySelectorAll('[data-tap-pane]').forEach(function (pane) {
                            var on = pane.getAttribute('data-tap-pane') === tab;
                            pane.classList.toggle('is-active', on);
                            pane.hidden = !on;
                        });
                        tabsWrap.querySelectorAll('[data-tap-tab]').forEach(function (btn) {
                            btn.classList.toggle('tap-tab-active', btn.getAttribute('data-tap-tab') === tab);
                        });
                        var marker = results.querySelector('[data-patient-count]');
                        if (marker) {
                            ['walkin', 'phone', 'ot'].forEach(function (key) {
                                var n = marker.getAttribute('data-tap-count-' + key);
                                var el = tabsWrap.querySelector('[data-tap-n="' + key + '"]');
                                if (el && n !== null) el.textContent = n;
                            });
                        }
                    };

                    if (!tabsWrap.dataset.tapBound) {
                        tabsWrap.dataset.tapBound = '1';
                        tabsWrap.addEventListener('click', function (e) {
                            var btn = e.target.closest('[data-tap-tab]');
                            if (!btn) return;
                            e.preventDefault();
                            window.__todayPatientsTab = btn.getAttribute('data-tap-tab');
                            window.applyTodayPatientsTabFilter();
                        });
                    }

                    window.applyTodayPatientsTabFilter();
                })();
            </script>
        </div>
    </div>
</div>

@endif

@push('scripts')
<script>
    // ─────────────────────────────────────────────────────────────────
    // AJAX mobile-number search for the receptionist "Today Added
    // Patients" widget. Only #today-patients-results is ever replaced —
    // the search input (in #today-patients-form) and the count badge
    // live outside it and are never touched directly, so the input
    // never loses focus while the user types.
    //
    // The AJAX endpoint returns the rendered partial as plain HTML
    // (no JSON envelope). The count badge is kept in sync by reading
    // the `data-patient-count` attribute the partial's root element
    // carries — see partials/receptionist-today-patients-table.blade.php.
    // ─────────────────────────────────────────────────────────────────
    (function () {
        'use strict';

        function init() {
            var form = document.getElementById('today-patients-form');
            var results = document.getElementById('today-patients-results');
            var countEl = document.getElementById('today-patients-count');
            if (!form || !results) return;

            var DEBOUNCE_MS = 500;
            var debounceTimer = null;
            var abortController = null;

            function applyTabFilter() {
                if (typeof window.applyTodayPatientsTabFilter === 'function') {
                    window.applyTodayPatientsTabFilter();
                }
            }

            applyTabFilter();

            function updateCountBadge() {
                if (!countEl) return;
                var marker = results.querySelector('[data-patient-count]');
                var count = marker ? marker.getAttribute('data-patient-count') : '0';
                countEl.textContent = count + ' today';
            }

            function showError() {
                results.insertAdjacentHTML(
                    'afterbegin',
                    '<div class="alert alert-danger m-3" role="alert">' +
                        'Could not load results. Please check your connection and try again.' +
                    '</div>'
                );
            }

            async function loadResults(params, pushToHistory) {
                if (abortController) abortController.abort();
                abortController = new AbortController();

                var basePath = form.getAttribute('action');
                var displayUrl = basePath + (params.toString() ? '?' + params.toString() : '');

                var fetchParams = new URLSearchParams(params.toString());
                fetchParams.set('section', 'today_patients');

                try {
                    var res = await fetch(basePath + '?' + fetchParams.toString(), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        signal: abortController.signal,
                    });

                    if (!res.ok) throw new Error('HTTP ' + res.status);

                    results.innerHTML = await res.text();
                    updateCountBadge();
                    applyTabFilter();

                    if (pushToHistory) {
                        history.pushState({ todayPatients: true }, '', displayUrl + window.location.hash);
                    }
                } catch (err) {
                    if (err.name === 'AbortError') return;
                    showError();
                    console.error('[today-patients-filter]', err);
                }
            }

            function currentParams() {
                return new URLSearchParams(new FormData(form));
            }

            function scheduleFilter() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    loadResults(currentParams(), true);
                }, DEBOUNCE_MS);
            }

            var searchInput = form.elements.namedItem('search_contact');
            if (searchInput) {
                searchInput.addEventListener('input', scheduleFilter);
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                clearTimeout(debounceTimer);
                loadResults(currentParams(), true);
            });

            window.addEventListener('popstate', function () {
                var params = new URLSearchParams(window.location.search);
                if (searchInput) searchInput.value = params.get('search_contact') || '';
                loadResults(params, false);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>
@endpush

@endif {{-- /hasAnyData --}}

</div>{{-- /bento-page --}}

@push('scripts')
<script>
(function () {
    const W = { green: {{ $wGreen }}, orange: {{ $wOrange }}, red: {{ $wRed }} };
    function getWaitClassCustom(m, g, o, r) {
        return m < g ? 'wait-green' : m < o ? 'wait-orange' : m < r ? 'wait-red' : 'wait-fire';
    }
    function getWaitClass(m) { return getWaitClassCustom(m, W.green, W.orange, W.red); }
    function fmtTime(m) {
        if (m < 60) return m + 'm';
        const h = Math.floor(m / 60), r = m % 60;
        return r > 0 ? h + 'h ' + r + 'm' : h + 'h';
    }
    function updateWaitPills() {
        const now = Date.now();
        document.querySelectorAll('.wait-pill[data-wait-from]').forEach(function (pill) {
            const mins = Math.floor((now - new Date(pill.dataset.waitFrom).getTime()) / 60000);
            const thr  = pill.dataset.thresholds ? pill.dataset.thresholds.split(',').map(Number) : null;
            const cls  = thr ? getWaitClassCustom(mins, thr[0], thr[1], thr[2]) : getWaitClass(mins);
            pill.className = 'wait-pill ' + cls;
            const t = pill.querySelector('.wp-time');
            if (t) t.textContent = fmtTime(mins);
        });
    }
    document.addEventListener('DOMContentLoaded', function () {
        updateWaitPills();
        setInterval(updateWaitPills, 30000);
    });
})();
</script>
@endpush

@endsection