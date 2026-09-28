{{-- Shared styles for the OT desk detail modals (accountant payment view, ward view). --}}
@once
    @push('styles')
        <style>
            .apm-modal .modal-body { padding: 1.1rem 1.2rem 1.3rem; }
            .apm-hero { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap;
                padding: .9rem 1rem; border-radius: 14px; background: linear-gradient(135deg, #EBF5FB 0%, #fff 75%);
                border: 1px solid rgba(27, 79, 114, .14); }
            .apm-hero-main { display: flex; align-items: center; gap: .75rem; min-width: 0; }
            .apm-avatar { width: 44px; height: 44px; border-radius: 12px; background: #1B4F72; color: #fff; display: inline-flex;
                align-items: center; justify-content: center; font-weight: 800; font-size: 1.1rem; flex: 0 0 auto; }
            .apm-name { font-weight: 800; font-size: 1.1rem; color: #1B4F72; line-height: 1.2; }
            .apm-sub { font-size: .8rem; font-weight: 600; color: #64748b; margin-top: 2px; }
            .apm-badges { display: flex; flex-wrap: wrap; gap: .4rem; }
            .apm-badge { display: inline-block; padding: .35rem .8rem; border-radius: 999px; font-size: .78rem; font-weight: 800; }
            .apm-tone-green { background: #E7F8EF; color: #1E8E5A; }
            .apm-tone-blue { background: #dbeafe; color: #1B4F72; }
            .apm-tone-amber { background: #FFF6DF; color: #92660A; }
            .apm-tone-red { background: #fee2e2; color: #b91c1c; }
            .apm-tone-grey { background: #eef2f6; color: #475569; }
            .apm-tone-purple { background: #f3e8ff; color: #6b21a8; }
            .apm-tone-navy { background: #1B4F72; color: #fff; }
            .apm-amounts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; margin-top: .85rem; }
            .apm-amount { background: #fff; border: 1px solid rgba(27, 79, 114, .10); border-radius: 12px; padding: .7rem .85rem; }
            .apm-amount-label { font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: rgba(27, 79, 114, .6); }
            .apm-amount-value { font-size: 1.05rem; font-weight: 800; color: #1B4F72; margin-top: 2px; }
            .apm-amount-value.is-green { color: #1E8E5A; }
            .apm-amount-value.is-amber { color: #92660A; }
            .apm-amount-value.is-red { color: #b91c1c; }
            .apm-vitals { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .6rem; }
            .apm-vitals .apm-amount { text-align: center; padding: .6rem .5rem; }
            .apm-vitals .apm-amount-value { font-size: .98rem; }
            .apm-section-title { display: flex; align-items: center; gap: .45rem; margin: 1.1rem 0 .6rem; font-size: .74rem;
                font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: rgba(27, 79, 114, .7); }
            .apm-section-title .apm-badge { margin-left: auto; text-transform: none; letter-spacing: 0; }
            .apm-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .65rem; }
            .apm-grid .ot-desk-modal-field { padding: .65rem .8rem; border-radius: 12px; }
            .apm-grid .ot-desk-modal-value { font-size: .9rem; }
            .apm-empty { padding: .85rem 1rem; border-radius: 12px; background: #fff; border: 1px dashed rgba(27, 79, 114, .2);
                color: rgba(27, 79, 114, .6); font-weight: 700; font-size: .85rem; }
            .apm-table { width: 100%; background: #fff; border: 1px solid rgba(27, 79, 114, .10); border-radius: 12px; border-collapse: separate;
                border-spacing: 0; overflow: hidden; font-size: .85rem; }
            .apm-table th { background: #EBF5FB; color: rgba(27, 79, 114, .75); font-size: .7rem; font-weight: 800; text-transform: uppercase;
                letter-spacing: .05em; padding: .55rem .8rem; white-space: nowrap; }
            .apm-table td { padding: .55rem .8rem; border-top: 1px solid rgba(27, 79, 114, .08); color: #1B4F72; font-weight: 600; }
            .apm-footer { background: #fff; border-top: 1px solid rgba(27, 79, 114, .10); }
            .apm-primary-btn { background: #1B4F72; border: 1px solid #1B4F72; color: #fff; font-weight: 700; border-radius: 8px; }
            .apm-primary-btn:hover { background: #15405d; border-color: #15405d; color: #fff; }
            @media (max-width: 767.98px) {
                .apm-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .apm-vitals { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            }
            @media (max-width: 575.98px) {
                .apm-grid, .apm-amounts { grid-template-columns: 1fr; }
                .apm-vitals { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            }
        </style>
    @endpush
@endonce
