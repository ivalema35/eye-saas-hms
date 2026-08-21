@extends('hospital.layouts.app')
@section('title', 'Dashboard')
{{-- Layout page-header intentionally unused — replaced by the "Welcome
     back" banner rendered inside the page content (design refresh). --}}

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
    padding: 0.25rem 2rem;
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

/* ── Welcome banner ────────────────────────────────────────────────────── */
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

/* ── Quick actions ─────────────────────────────────────────────────────── */
.qa-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 16px;
    padding: 1.5rem 1.5rem 1.75rem;
}
.qa-pill {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .7rem;
    padding: 1.2rem 1rem;
    background: #ffffff;
    border: 1px solid rgba(27, 79, 114, 0.12);
    border-radius: 16px;
    text-decoration: none !important;
    color: var(--dash-secondary);
    font-weight: 800;
    font-size: 13px;
    text-align: center;
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease, background .22s ease, color .22s ease;
    box-shadow: var(--dash-shadow);
}
.qa-pill i,
.qa-pill svg {
    color: var(--dash-secondary) !important;
    stroke: var(--dash-secondary) !important;
    font-size: 1.4rem;
}
.qa-pill:hover {
    background: var(--dash-secondary);
    border-color: var(--dash-secondary);
    color: var(--dash-white);
    text-decoration: none;
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14);
}
.qa-pill:hover i,
.qa-pill:hover svg {
    color: var(--dash-white) !important;
    stroke: var(--dash-white) !important;
}

/* ── Alerts ────────────────────────────────────────────────────────────── */
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

/* ── FOC badge pulse (same secondary palette with enhanced animation) ──────── */
.foc-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 26px;
    height: 26px;
    border-radius: 999px;
    background: linear-gradient(135deg, var(--dash-secondary) 0%, rgba(27,79,114,.88) 100%);
    color: var(--dash-white);
    font-size: 11px;
    font-weight: 900;
    padding: 0 .5rem;
    animation: dash-pulse 2s infinite;
    border: 1px solid rgba(255,255,255,.2);
}
@keyframes dash-pulse {
    0%, 100% { transform: scale(1); }
    50%      { transform: scale(1.1); }
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
.hms-btn-outline,
.foc-view-btn {
    background: rgba(255, 255, 255, 0.96) !important;
    border-color: rgba(27,79,114,.2) !important;
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
    border: 2px solid rgba(27,79,114,.2) !important;
    background: linear-gradient(135deg, rgba(255,255,255,.98) 0%, rgba(235,245,251,.75) 100%) !important;
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
    border: 2px solid rgba(27,79,114,.2);
    border-radius: 24px;
    overflow: hidden;
    background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(248,251,253,.98));
}

.foc-request-modal .modal-content {
    border: 2px solid rgba(27,79,114,.2);
    border-radius: 24px;
    overflow: hidden;
    background: linear-gradient(180deg, rgba(255,255,255,.99), rgba(248,251,253,.98));
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
    border: 1px solid rgba(255,255,255,.15);
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
    border: 1px solid rgba(255,255,255,.15);
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
    border: 1px solid rgba(255,255,255,.18);
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
    border: 2px solid rgba(27,79,114,.15);
    border-radius: 18px;
    background: rgba(255,255,255,.9);
    padding: .95rem 1rem;
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
    border: 2px solid rgba(27,79,114,.15);
    border-radius: 18px;
    background: rgba(255,255,255,.9);
    padding: .95rem 1rem;
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
    outline: none;
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
    border: 2px solid rgba(27,79,114,.15);
    border-radius: 18px;
    background: rgba(255,255,255,.9);
    padding: .95rem 1rem;
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
    $isHospitalAdmin = $isHospitalAdmin ?? false;
    $otTotalToday = $otTotalToday ?? null;
    $focReceptionists = $focReceptionists ?? collect();
    $pendingFocRequests = $pendingFocRequests ?? collect();
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

    $hasAnyData   = $isHospitalAdmin || $hasClinical || $hasReception || $hasRevenue || $hasStaff || $hasOt || $hasFocAlert || $dischargePendingCount !== null;
    $pendingShareRequestsCount = $pendingShareRequestsCount ?? null;
@endphp

{{-- Welcome banner (design refresh) --}}
<div class="dash-welcome-card">
    <div class="dash-welcome-left">
        <span class="dash-welcome-date"><i class="bi bi-calendar-check"></i> {{ now()->format('d M, Y') }}</span>
        <h2 class="dash-welcome-title">Welcome <span class="dash-welcome-wave">👋</span></h2>
        <p class="dash-welcome-sub">Here's what's happening with {{ $tenant?->name ?? config('app.name') }} today.</p>
    </div>
</div>

{{-- Subscription Alert --}}
@if($subscriptionDaysLeft !== null && $subscriptionDaysLeft <= 30)
    @php
        $isHospitalAdmin = auth('hospital_user')->user()?->role?->is_super;
    @endphp
    @if($isHospitalAdmin)
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
    @else
        <div class="bento-alert {{ $subscriptionDaysLeft <= 3 ? 'bento-alert-danger' : 'bento-alert-warn' }}">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>
                @if($subscriptionDaysLeft <= 0)
                    Your subscription has <strong>expired</strong>. Please contact administrator.
                @else
                    Subscription expires in <strong>{{ $subscriptionDaysLeft }} day{{ $subscriptionDaysLeft === 1 ? '' : 's' }}</strong>. Please renew soon.
                @endif
            </span>
        </div>
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
        <p class="text-muted small mb-0">Your role has no dashboard widgets assigned yet. Contact your administrator to configure the appropriate permissions.</p>
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
    @haspermission('reports.view')
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
     BENTO GRID — ROW 1: Stat Metric Cards
════════════════════════════════════════════════════════════════════════════ --}}
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

    {{-- Today's Registrations (opd.patient.register) --}}
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
    {{-- OT Appointment (ot.patient.list / ot.appointment.view) --}}
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

    {{-- FOC Approval Alert (opd.foc.accept) --}}
    <!-- @if($hasFocAlert)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-red">
                    <i class="bi bi-file-earmark-check" style="font-size:22px;color:#C0392B"></i>
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
                                            <!-- <h5 class="modal-title">Request FOC</h5> -->
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
                                            <div class="foc-request-value foc-request-fee">{{ money((float) $patient->case_fee, 2) }}</div>
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
<div class="row g-4 mb-4">
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
                <button type="button" class="tap-tab-btn tap-tab-active" data-tap-tab="phone">
                    <i class="bi bi-telephone"></i> Phone <span class="tap-tab-n" data-tap-n="phone">0</span>
                </button>
                <button type="button" class="tap-tab-btn" data-tap-tab="walkin">
                    <i class="bi bi-person-walking"></i> Walk-in <span class="tap-tab-n" data-tap-n="walkin">0</span>
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

                    window.__todayPatientsTab = window.__todayPatientsTab || 'phone';

                    window.applyTodayPatientsTabFilter = function () {
                        var tab = window.__todayPatientsTab || 'phone';
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
                            ['phone', 'walkin', 'ot'].forEach(function (key) {
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
                                <td>{{ money((float) $foc->foc_fee, 2) }}</td>
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
                    <p class="mb-1"><strong>Fee to Waive:</strong> {{ money((float) $foc->foc_fee, 2) }}</p>
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
                @haspermission('medicine.hospital')
                    <a href="{{ route('hospital.medicines.index', $slug) }}" class="qa-pill">
                        <i class="fa-solid fa-pills" style="font-size:24px;color:#34495E"></i>
                        <span>Medicine</span>
                    </a>
                @endhaspermission
                @haspermission('masters.hospital')
                    <a href="{{ route('hospital.masters.index', $slug) }}" class="qa-pill">
                        <i class="fa-solid fa-database" style="font-size:24px;color:#34495E"></i>
                        <span>Masters</span>
                    </a>
                @endhaspermission
                @haspermission('masters.hospital')
                    <a href="{{ route('hospital.masters.detail.index', ['slug' => $slug, 'type' => 'diagnosis']) }}" class="qa-pill">
                        <i class="fa-solid fa-stethoscope" style="font-size:24px;color:#34495E"></i>
                        <span>Diagnosis</span>
                    </a>
                @endhaspermission
            </div>
        </div>
    </div>
</div>{{-- /row quick actions --}}

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