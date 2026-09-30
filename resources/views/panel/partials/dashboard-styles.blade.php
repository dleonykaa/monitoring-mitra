@push('head')
<style>
    /* Warna status & navy memakai palet sistem di panel/partials/ui.blade.php. */
    .db{--db-amber-tint:#fffbeb;--db-rose-tint:#fff1f2;--db-green-tint:#ecfdf5;
        display:grid;grid-template-columns:minmax(0,1fr);gap:16px}
    .db > *{min-width:0}
    [data-theme="dark"] .db{--db-amber-tint:#2e2410;--db-rose-tint:#2f1520;--db-green-tint:#0f2d23}

    /* ---------- Kepala halaman ---------- */
    .db-page{display:flex;flex-wrap:wrap;gap:12px 20px;align-items:flex-end;justify-content:space-between}
    .db-hello{margin:0 0 4px;font-size:13px;color:var(--muted)}
    .db-hello b{color:var(--text);font-weight:700}
    .db-page h1{margin:0;font-size:20px;font-weight:800;color:var(--brand-dark);letter-spacing:-.01em}

    /* ---------- Angka kunci ---------- */
    .db-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
    .kpi{background:var(--card);border:1.5px solid var(--navy);border-radius:14px;box-shadow:0 2px 8px var(--shadow);padding:14px 16px;display:flex;flex-direction:column;gap:10px;min-width:0}
    .kpi-h{display:flex;align-items:center;justify-content:space-between;gap:10px}
    .kpi-h h2{margin:0;font-size:13px;font-weight:700;color:var(--brand-dark);line-height:1.3}
    .kpi-ico{width:28px;height:28px;border-radius:8px;display:grid;place-items:center;flex:none;background:var(--navy);color:#fff}
    .kpi-ico svg{width:15px;height:15px}
    .kpi-val{font-size:26px;font-weight:800;line-height:1;letter-spacing:-.02em;color:var(--brand-dark);font-variant-numeric:tabular-nums}
    .kpi-bar{display:flex;height:8px;border-radius:4px;overflow:hidden;background:var(--bar-bg)}
    .kpi-bar i{display:block;height:100%}
    .kpi-bar i + i{box-shadow:inset 2px 0 0 var(--card)}
    /* Kartu sorotan berlatar navy */
    .kpi-navy{background:var(--navy);border-color:var(--navy);color:#fff}
    .kpi-navy .kpi-h h2{color:var(--navy-muted)}
    .kpi-navy .kpi-ico{background:var(--navy-chip);color:#fff}
    .kpi-navy .kpi-val{color:#fff;font-size:30px}
    .kpi-navy .kpi-bar{background:var(--navy-track)}
    .kpi-navy .kpi-bar i + i{box-shadow:inset 2px 0 0 var(--navy)}
    .kpi-navy .kpi-foot{display:block;color:var(--navy-muted)}
    .kpi-navy .kpi-foot b{color:#fff}
    .kpi-val small{font-size:13px;font-weight:600;color:var(--muted);letter-spacing:0}
    .kpi-foot{margin:auto 0 0;font-size:12px;color:var(--muted);line-height:1.45;display:flex;flex-wrap:wrap;gap:4px 6px;align-items:center}
    .kpi-foot b{color:var(--text);font-variant-numeric:tabular-nums}
    .kpi-foot a{color:var(--brand);font-weight:600;text-decoration:none}
    .kpi-foot a:hover{text-decoration:underline}
    .kpi-list{margin:0;display:grid;gap:5px}
    .kpi-list div{display:flex;justify-content:space-between;align-items:center;gap:10px}
    .kpi-list dt{display:flex;align-items:center;gap:7px;font-size:12.5px;color:var(--text)}
    .kpi-list dd{margin:0;font-size:14px;font-weight:700;font-variant-numeric:tabular-nums;color:var(--text)}
    .kpi-row{display:flex;align-items:flex-end;justify-content:space-between;gap:12px}
    .kpi-spark{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(7,20px);gap:4px;align-items:end;height:52px}
    .kpi-spark li{display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;gap:3px}
    .kpi-spark i{display:block;width:100%;border-radius:3px 3px 1px 1px;background:var(--navy);opacity:.55;min-height:2px}
    [data-theme="dark"] .kpi-spark i{background:var(--brand2)}
    .kpi-spark li.today i{opacity:1}
    .kpi-spark span:not(.sr-only){font-size:9.5px;color:var(--muted);line-height:1}
    .kpi-chips{margin:0;display:flex;flex-wrap:wrap;gap:6px}
    .kpi-chips span{font-size:11.5px;font-weight:600;color:var(--navy-tint-fg);background:var(--navy-tint);border-radius:6px;padding:2px 8px;font-variant-numeric:tabular-nums}

    /* ---------- Kartu & tata letak ---------- */
    .db-main{display:grid;grid-template-columns:minmax(0,2fr) minmax(290px,1fr);gap:16px;align-items:start}
    .db-side{display:grid;gap:16px;min-width:0}
    .db-card{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 2px 8px var(--shadow);display:flex;flex-direction:column;min-width:0;overflow:hidden}
    .db-card-h{display:flex;flex-wrap:wrap;gap:10px 14px;align-items:flex-start;justify-content:space-between;padding:16px 18px 12px}
    .db-card-h h2{margin:0;font-size:14.5px;font-weight:700;color:var(--brand-dark);display:flex;align-items:center;gap:8px;text-wrap:balance}
    .db-h-ico{width:26px;height:26px;border-radius:8px;display:inline-grid;place-items:center;flex:none;background:var(--navy);color:#fff}
    .db-h-ico svg{width:14px;height:14px}
    .db-card-h p{margin:3px 0 0;font-size:12px;color:var(--muted);line-height:1.45;max-width:60ch}
    .db-card-b{padding:0 18px 16px;min-width:0}
    .db-card-f{margin-top:auto;padding:11px 18px;border-top:1px solid var(--line);display:flex;flex-wrap:wrap;gap:6px 14px;align-items:center;justify-content:space-between;font-size:12px;color:var(--muted)}
    .db-link{color:var(--brand);font-weight:600;text-decoration:none;font-size:12.5px;display:inline-flex;align-items:center;gap:4px;white-space:nowrap}
    .db-link:hover{text-decoration:underline}
    .db-link:focus-visible{outline:2px solid var(--brand);outline-offset:2px;border-radius:4px}
    .db-count{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 7px;border-radius:999px;font-size:12px;font-weight:700;background:var(--soft);color:var(--muted);font-variant-numeric:tabular-nums}
    .db-count.amber{background:var(--t-amber-bg);color:var(--t-amber-fg)}.db-count.rose{background:var(--t-rose-bg);color:var(--t-rose-fg)}
    .db-chip{display:inline-flex;align-items:center;border-radius:999px;padding:1px 8px;font-size:11.5px;font-weight:600;white-space:nowrap;background:var(--soft);color:var(--muted)}
    .db-chip.amber{background:var(--t-amber-bg);color:var(--t-amber-fg)}.db-chip.rose{background:var(--t-rose-bg);color:var(--t-rose-fg)}

    /* ---------- Capaian per survei ---------- */
    .db-list{list-style:none;margin:0;padding:0 8px 8px;display:grid;gap:2px;max-height:460px;overflow-y:auto}
    .db-list li[hidden]{display:none}
    .db-srow{display:grid;grid-template-columns:minmax(0,1fr) minmax(130px,200px) 64px;gap:14px;align-items:center;padding:10px;border-radius:10px;color:var(--text)}
    .db-srow-title{display:block;font-weight:600;font-size:13.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .db-srow-sub{display:flex;flex-wrap:wrap;gap:4px 8px;align-items:center;margin-top:4px;font-size:12px;color:var(--muted)}
    .db-tag{border-radius:5px;padding:0 6px;font-size:10.5px;font-weight:700;letter-spacing:.04em;color:#fff;background:var(--navy)}
    .db-srow-prog{display:grid;gap:5px}
    .db-srow-prog small{font-size:11.5px;color:var(--muted);font-variant-numeric:tabular-nums;text-align:right}
    .db-minibar{display:flex;height:6px;border-radius:3px;background:var(--bar-bg);overflow:hidden}
    .db-minibar i{display:block;height:100%}
    .db-pct{text-align:center;border-radius:7px;padding:3px 6px;font-size:12px;font-weight:700;font-variant-numeric:tabular-nums}
    .db-empty-row{padding:18px;text-align:center;color:var(--muted);font-size:12.5px}

    /* ---------- Keadaan kosong / aman ---------- */
    .db-alert-ico{width:32px;height:32px;border-radius:10px;display:grid;place-items:center;flex:none}
    .db-alert-ico svg{width:17px;height:17px}
    .db-alert-ico.green{background:var(--db-green-tint);color:var(--st-submit-ink)}
    .db-allclear{display:flex;gap:12px;align-items:center;padding:4px 18px 18px}
    .db-allclear b{display:block;font-size:13px;color:var(--text)}
    .db-allclear span{font-size:12px;color:var(--muted)}

    /* ---------- Mitra belum update ---------- */
    .db-people{list-style:none;margin:0;padding:0 18px 12px;display:grid}
    .db-people li{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid var(--line)}
    .db-people li:last-of-type{border-bottom:0}
    .db-people li[hidden]{display:none!important}
    .db-people strong{display:block;font-size:13px;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .db-people .sub{display:block;font-size:12px;color:var(--muted);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

    /* ---------- Progres per kecamatan ---------- */
    .db-dist{min-width:540px}
    .db-dist th:first-child,.db-dist td:first-child{padding-left:18px}
    .db-dist th:last-child,.db-dist td:last-child{padding-right:18px}
    .db-dist tbody th{background:transparent;text-transform:none;letter-spacing:normal;border-top:0;white-space:normal;font-size:13.5px;font-weight:600;color:var(--text);text-align:left;padding:11px 14px;border-bottom:1px solid var(--line)}
    .db-dist tbody th small{display:block;font-size:11.5px;font-weight:500;color:var(--muted);margin-top:2px}
    .db-dist tbody tr:last-child > *{border-bottom:0}
    .db-dist-row{cursor:pointer}
    .db-dist-toggle{display:flex;align-items:center;gap:8px;width:100%;padding:0;border:0;border-radius:6px;background:transparent;color:inherit;font:inherit;text-align:left;cursor:pointer}
    .db-dist-toggle:hover{filter:none;box-shadow:none}
    .db-dist-toggle svg{width:14px;height:14px;flex:none;color:var(--muted);transition:transform .16s}
    .db-dist-toggle[aria-expanded="true"] svg{transform:rotate(90deg)}
    .db-dist-sub[hidden]{display:none}
    .db-dist tbody .db-dist-sub > *{background:var(--soft)}
    .db-dist tbody .db-dist-sub th{padding-left:40px;font-size:13px;font-weight:500}
    .db-dist .prog{width:32%;min-width:160px}
    .db-prog{display:grid;grid-template-columns:minmax(48px,1fr) 62px;gap:8px;align-items:center}
    .db-prog .db-minibar{height:6px}
    .db-blank{min-height:140px;display:grid;place-items:center;align-content:center;gap:6px;text-align:center;border:1px dashed var(--line);border-radius:12px;color:var(--muted);font-size:12.5px;padding:18px}
    .db-blank b{color:var(--text);font-size:13px}
    .db-blank span{max-width:52ch}
    .db-blank .b{margin-top:6px}

    @media (max-width:1180px){
        .db-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}
        .db-main{grid-template-columns:minmax(0,1fr)}
    }
    @media (max-width:560px){
        .db-kpis{grid-template-columns:minmax(0,1fr)}
        .db-srow{grid-template-columns:minmax(0,1fr) 60px}
        .db-srow-prog{grid-column:1 / -1;grid-row:2}
    }
    @media (prefers-reduced-motion:reduce){.db *{transition:none!important}}
</style>
@endpush
