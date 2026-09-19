<style>
    .ot-desk-filter-wrap {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .25rem;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .22);
        border-radius: 999px;
    }

    .ot-desk-filter-btn {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        padding: .4rem .85rem;
        border-radius: 999px;
        font-size: .8rem;
        font-weight: 800;
        color: rgba(255, 255, 255, .88);
        text-decoration: none;
        transition: background 160ms ease, color 160ms ease;
    }

    .ot-desk-filter-btn:hover {
        color: #fff;
        background: rgba(255, 255, 255, .16);
    }

    .ot-desk-filter-btn.active {
        background: #fff;
        color: #1B4F72;
    }

    .ot-desk-date-input {
        border-radius: 8px !important;
        border: 1px solid rgba(255, 255, 255, .35) !important;
        background: rgba(255, 255, 255, .95) !important;
        color: #1B4F72 !important;
        font-weight: 700;
        min-height: 34px;
    }

    .ot-desk-view-modal .modal-content {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 24px 70px rgba(27, 79, 114, .22);
    }

    .ot-desk-view-modal .modal-header {
        background: linear-gradient(135deg, #174562, #1b4f72);
        color: #fff;
        border-bottom: 0;
    }

    .ot-desk-view-modal .modal-title {
        color: #fff !important;
        font-weight: 800;
    }

    .ot-desk-view-modal .modal-body {
        background: #f7fbfe;
        padding: 1.15rem;
    }

    .ot-desk-modal-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .85rem;
    }

    .ot-desk-modal-field {
        background: #fff;
        border: 1px solid rgba(27, 79, 114, .10);
        border-radius: 14px;
        padding: .85rem .95rem;
    }

    .ot-desk-modal-wide {
        grid-column: 1 / -1;
    }

    .ot-desk-modal-label {
        font-size: .72rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: rgba(27, 79, 114, .62);
        margin-bottom: .3rem;
    }

    .ot-desk-modal-value {
        font-weight: 800;
        color: #1B4F72;
        word-break: break-word;
    }

    @media (max-width: 576px) {
        .ot-desk-modal-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
