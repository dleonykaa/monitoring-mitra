@push('head')
<style>
    .mitra-survey-list {
        display: grid;
        gap: 9px;
    }

    .mitra-survey-row {
        display: grid;
        grid-template-columns: minmax(260px, 1.7fr) 82px 92px minmax(180px, 1fr) 104px 138px;
        gap: 12px;
        align-items: center;
        padding: 10px 12px;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: var(--soft2);
    }

    .mitra-survey-title {
        display: grid;
        gap: 3px;
        min-width: 0;
    }

    .mitra-survey-title strong {
        color: var(--brand-dark);
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .mitra-survey-title span,
    .survey-progress small,
    .survey-mini small {
        color: var(--muted);
        font-size: 11.5px;
    }

    .survey-mini {
        border: 1px solid var(--line);
        border-radius: 10px;
        background: var(--card);
        padding: 7px 10px;
        line-height: 1.1;
    }

    .survey-mini b {
        display: block;
        color: var(--brand-dark);
        font-size: 15px;
    }

    .survey-progress {
        display: grid;
        grid-template-columns: 1fr 42px;
        gap: 8px;
        align-items: center;
    }

    .survey-action {
        justify-self: end;
        min-width: 130px;
        text-align: right;
    }

    .survey-action .btn {
        padding: 8px 12px;
        width: 130px;
        text-align: center;
    }

    .invalid-note {
        border: 1px solid #fecaca;
        border-radius: 10px;
        background: #fff1f2;
        color: #9f1239;
        padding: 7px 9px;
        font-size: 12px;
        line-height: 1.35;
    }

    @media (max-width: 980px) {
        .mitra-survey-row {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush
