{{-- Komponen UI bersama panel (header halaman, panel, tabel, tombol, form, badge, tab, stepper). Dimuat di layout. --}}
<style>
    .ui{display:grid;grid-template-columns:minmax(0,1fr);gap:16px}
    .ui > *{min-width:0}
    [hidden]{display:none!important}
    .sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
    /* ---------- Palet sistem ----------
       Navy: blok ringkasan (Monitoring, Dashboard, detail survei) dan aksen ikon.
       Status progres: dipakai sama persis di bar, legenda, dan angka pada semua halaman. */
    :root{--t-blue-bg:#dbeafe;--t-blue-fg:#1e40af;--t-green-bg:#dcfce7;--t-green-fg:#166534;--t-amber-bg:#fef3c7;--t-amber-fg:#92400e;--t-rose-bg:#ffe4e6;--t-rose-fg:#9f1239;--t-gray-bg:var(--soft);--t-gray-fg:var(--muted);
        --navy:#0b2447;--navy-muted:#b9cbe6;--navy-soft:#d5e1f3;--navy-chip:rgba(255,255,255,.14);--navy-track:rgba(255,255,255,.12);--navy-tint:#e8eef8;--navy-tint-fg:#0b2447;
        --st-submit:#16a34a;--st-draft:#f59e0b;--st-open:#94a3b8;--st-other:#38bdf8;--st-late:#e11d48;
        --st-submit-ink:#047857;--st-draft-ink:#b45309;--st-late-ink:#be123c}
    [data-theme="dark"]{--t-blue-bg:#1e3a5f;--t-blue-fg:#93c5fd;--t-green-bg:#0e3a2a;--t-green-fg:#86efac;--t-amber-bg:#3a2c0e;--t-amber-fg:#fcd34d;--t-rose-bg:#3a1620;--t-rose-fg:#fda4b4;
        --navy:#0f2a50;--navy-tint:#1b2c4a;--navy-tint-fg:#cbd9f0;--st-open:#64748b;
        --st-submit-ink:#4ade80;--st-draft-ink:#fbbf24;--st-late-ink:#fda4b4}
    .sw{width:10px;height:10px;border-radius:3px;flex:none;display:inline-block}
    .sw.submit{background:var(--st-submit)}.sw.draft{background:var(--st-draft)}.sw.open{background:var(--st-open)}.sw.other{background:var(--st-other)}
    .ink-submit{color:var(--st-submit-ink)!important}.ink-draft{color:var(--st-draft-ink)!important}
    .t-good{background:var(--t-green-bg);color:var(--t-green-fg)}.t-ok{background:var(--t-blue-bg);color:var(--t-blue-fg)}
    .t-warn{background:var(--t-amber-bg);color:var(--t-amber-fg)}.t-bad{background:var(--t-rose-bg);color:var(--t-rose-fg)}.t-none{background:var(--t-gray-bg);color:var(--t-gray-fg)}

    /* ---------- Header halaman ---------- */
    .pg-head{display:flex;flex-wrap:wrap;gap:12px 20px;align-items:flex-end;justify-content:space-between}
    .pg-head h1{margin:0;font-size:17px;font-weight:800;color:var(--brand-dark);letter-spacing:-.01em;text-wrap:balance}
    .pg-head p{margin:4px 0 0;font-size:13px;color:var(--muted);line-height:1.5;max-width:70ch}
    .pg-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
    .pg-back{display:inline-flex;align-items:center;gap:4px;font-size:12.5px;font-weight:600;color:var(--muted);text-decoration:none;margin-bottom:6px}
    .pg-back:hover{color:var(--brand)}
    .pg-back svg{width:15px;height:15px}

    /* Header survei (navy, selaras Monitoring Progres) */
    .hero-nv{background:var(--navy);color:#fff;border-radius:16px;padding:22px 24px}
    .hero-nv .top{display:flex;flex-wrap:wrap;gap:14px 24px;align-items:flex-start;justify-content:space-between}
    .hero-nv h1{margin:0;font-size:19px;font-weight:800;letter-spacing:-.01em;line-height:1.25;text-wrap:balance}
    .hero-nv .meta{margin-top:8px;font-size:12.5px;color:var(--navy-muted);display:flex;flex-wrap:wrap;gap:4px 10px;align-items:center}
    .hero-nv .meta b{color:#fff;font-weight:600}
    .hero-nv .desc{margin:10px 0 0;font-size:13px;color:var(--navy-soft);line-height:1.55;max-width:75ch}
    .hero-nv .chip{background:var(--navy-chip);color:#fff;border-radius:6px;padding:2px 8px;font-size:11.5px;font-weight:700;letter-spacing:.04em}
    .hero-nv .ledger{display:grid;grid-template-columns:minmax(180px,220px) 1fr;gap:28px;align-items:end;margin-top:24px}
    .hero-nv .big .lbl{font-size:12px;color:var(--navy-muted);font-weight:600}
    .hero-nv .big .val{font-size:34px;font-weight:800;line-height:1;margin-top:6px;letter-spacing:-.02em;font-variant-numeric:tabular-nums}
    .hero-nv .big .sub{font-size:12px;color:var(--navy-muted);margin-top:8px}
    .hero-nv .big .sub b{color:#fff}
    .hero-nv .track{display:flex;height:12px;border-radius:6px;overflow:hidden;background:var(--navy-track)}
    .hero-nv .track i + i{box-shadow:inset 2px 0 0 var(--navy)}
    .hero-nv .track i{display:block;height:100%}
    .hero-nv .legend{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:14px}
    .hero-nv .legend .k{font-size:12px;color:var(--navy-muted);font-weight:600;white-space:nowrap;display:flex;align-items:center;gap:7px}
    .hero-nv .legend .v{font-size:16px;font-weight:700;margin-top:3px;font-variant-numeric:tabular-nums}
    .hero-nv .legend .v small{font-size:12px;font-weight:600;color:var(--navy-muted);margin-left:4px}
    .b-light{background:#fff!important;color:#0b2447!important}
    .b-light:focus-visible,.b-outline-light:focus-visible{outline:2px solid #fff;outline-offset:2px}
    .b-light:hover{box-shadow:0 0 0 3px rgba(255,255,255,.25)!important}
    .b-outline-light{background:transparent!important;color:#fff!important;border:1px solid rgba(255,255,255,.3)!important}
    .b-outline-light:hover{background:rgba(255,255,255,.1)!important;box-shadow:none!important}

    /* ---------- Panel ---------- */
    .pnl{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 2px 8px var(--shadow);min-width:0;display:flex;flex-direction:column}
    .pnl-h{display:flex;flex-wrap:wrap;gap:10px 14px;align-items:center;justify-content:space-between;padding:16px 18px 12px}
    .pnl-h h2{margin:0;font-size:14.5px;font-weight:700;color:var(--brand-dark);display:flex;align-items:center;gap:8px;text-wrap:balance}
    .pnl-h p{margin:3px 0 0;font-size:12px;color:var(--muted);line-height:1.45;max-width:70ch}
    .pnl-b{padding:0 18px 18px;min-width:0}
    .pnl-f{margin-top:auto;padding:12px 18px;border-top:1px solid var(--line);display:flex;flex-wrap:wrap;gap:8px 14px;align-items:center;justify-content:space-between;font-size:12px;color:var(--muted)}
    .pnl.flush .pnl-b{padding:0}
    .pnl-danger{border-color:#fecaca}
    [data-theme="dark"] .pnl-danger{border-color:#7f2336}
    .cols{display:grid;gap:16px;align-items:start}
    .cols > *{min-width:0}
    .cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}
    .cols-side{grid-template-columns:minmax(0,1fr) minmax(280px,340px)}

    /* ---------- Tombol ---------- */
    .b{display:inline-flex;align-items:center;justify-content:center;gap:7px;border-radius:10px;padding:8px 14px;font-size:13px;font-weight:600;line-height:1.3;text-decoration:none;cursor:pointer;border:1px solid transparent;white-space:nowrap;transition:background-color .15s ease-out,box-shadow .15s ease-out,color .15s ease-out}
    .b svg{width:16px;height:16px;flex:none}
    .b:focus-visible{outline:2px solid var(--brand);outline-offset:2px}
    .b:disabled{opacity:.55;cursor:not-allowed;box-shadow:none}
    .b-primary{background:var(--brand);color:#fff}
    .b-primary:hover{filter:none;box-shadow:0 4px 12px rgba(29,78,216,.25);background:#1e40af}
    .b-soft{background:var(--soft);color:var(--text);border-color:var(--line)}
    .b-soft:hover{background:var(--row-hover);box-shadow:none;filter:none;color:var(--brand)}
    .b-green{background:#047857;color:#fff}
    .b-green:hover{background:#065f46;box-shadow:0 4px 12px rgba(4,120,87,.25);filter:none}
    .b-danger{background:#be123c;color:#fff}
    .b-danger:hover{background:#9f1239;box-shadow:0 4px 12px rgba(190,18,60,.25);filter:none}
    .b-danger-soft{background:transparent;color:#be123c;border-color:#fecdd3}
    .b-danger-soft:hover{background:#fff1f2;box-shadow:none;filter:none}
    [data-theme="dark"] .b-danger-soft{color:#fda4b4;border-color:#7f2336}
    [data-theme="dark"] .b-danger-soft:hover{background:#3a1620}
    .b-ghost{background:transparent;color:var(--muted)}
    .b-ghost:hover{background:var(--soft);color:var(--text);box-shadow:none;filter:none}
    .b-sm{padding:6px 10px;font-size:12px;border-radius:8px}
    .b-icon{padding:7px;width:34px;height:34px}
    .b-icon.b-sm{width:30px;height:30px;padding:6px}

    /* ---------- Badge ---------- */
    .bdg{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 10px;font-size:12px;font-weight:600;white-space:nowrap;line-height:1.4}
    .bdg-dot::before{content:"";width:7px;height:7px;border-radius:50%;background:currentColor;opacity:.9}
    .bdg-blue{background:var(--t-blue-bg);color:var(--t-blue-fg)}.bdg-green{background:var(--t-green-bg);color:var(--t-green-fg)}
    .bdg-amber{background:var(--t-amber-bg);color:var(--t-amber-fg)}.bdg-rose{background:var(--t-rose-bg);color:var(--t-rose-fg)}
    .bdg-gray{background:var(--t-gray-bg);color:var(--t-gray-fg)}
    .tag{display:inline-block;border:1px solid var(--line);border-radius:5px;padding:0 5px;font-size:10.5px;font-weight:700;letter-spacing:.04em;color:var(--brand-dark);background:var(--soft)}
    .num-chip{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 7px;border-radius:999px;font-size:12px;font-weight:700;background:var(--soft);color:var(--muted);font-variant-numeric:tabular-nums}

    /* ---------- Tab / segmented ---------- */
    .seg{display:inline-flex;flex-wrap:wrap;padding:3px;background:var(--soft);border:1px solid var(--line);border-radius:10px}
    .seg a,.seg button{all:unset;box-sizing:border-box;padding:6px 12px;border-radius:7px;font-size:12.5px;font-weight:600;color:var(--muted);cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;transition:background-color .15s ease-out,color .15s ease-out}
    .seg a:hover,.seg button:hover{color:var(--text)}
    .seg a:focus-visible,.seg button:focus-visible{outline:2px solid var(--brand);outline-offset:1px}
    .seg .on,.seg [aria-pressed="true"],.seg [aria-current="page"]{background:var(--card);color:var(--brand);box-shadow:0 1px 3px rgba(15,39,71,.14)}
    .seg small{font-size:11.5px;font-weight:700;opacity:.8;font-variant-numeric:tabular-nums}

    /* ---------- Toolbar & pencarian ---------- */
    .toolbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between}
    .toolbar .grow{flex:1 1 auto}
    .search{position:relative;flex:0 0 260px;min-width:0}
    .toolbar .pg-actions{flex-wrap:nowrap}
    .search svg{position:absolute;left:10px;top:50%;width:16px;height:16px;transform:translateY(-50%);color:var(--muted);pointer-events:none}
    .search input{padding:8px 10px 8px 32px;width:100%;font-size:13px;background:var(--card);color:var(--text)}

    /* ---------- Form ---------- */
    .fld{display:grid;gap:6px;min-width:0}
    .fld > span,.fld-label{font-size:12.5px;font-weight:600;color:var(--text)}
    .fld > span small,.fld-label small{font-weight:500;color:var(--muted)}
    .fld input,.fld select,.fld textarea{width:100%;min-width:0;font-size:13.5px;color:var(--text);background:var(--card)}
    .fld input:focus-visible,.fld select:focus-visible,.fld textarea:focus-visible,.cell-input:focus-visible,.search input:focus-visible{outline:2px solid var(--brand);outline-offset:0;border-color:transparent}
    .fld input:disabled{background:var(--soft);color:var(--muted)}
    .fld .hint{font-size:12px;color:var(--muted);line-height:1.45;font-weight:400}
    .fld-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
    .fld-grid .full{grid-column:1/-1}
    .fld-row{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end}
    .fld-row > .fld{flex:1 1 180px}
    .form-foot{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;padding-top:14px;margin-top:4px;border-top:1px solid var(--line)}
    .form-foot p{margin:0;font-size:12px;color:var(--muted)}
    .cell-input{width:100%;min-width:0;font-size:13px;padding:7px 10px;background:var(--card);color:var(--text)}
    .choice{align-self:stretch;height:100%;box-sizing:border-box;display:flex;gap:10px;align-items:flex-start;border:1.5px solid var(--line);border-radius:12px;padding:12px 14px;cursor:pointer;transition:border-color .15s ease-out,background-color .15s ease-out}
    .choice:hover{border-color:var(--brand2)}
    .choice:has(input:checked){border-color:var(--brand);background:var(--soft)}
    .choice input{margin-top:2px;accent-color:var(--brand);width:16px;height:16px;padding:0;flex:none}
    .choice b{display:block;font-size:13.5px;color:var(--brand-dark)}
    .choice span span{display:block;font-size:12px;color:var(--muted);line-height:1.5;margin-top:2px}
    .dropzone{position:relative;display:flex;align-items:center;gap:12px;border:1.5px dashed var(--line);border-radius:12px;padding:14px;cursor:pointer;transition:border-color .15s ease-out,background-color .15s ease-out}
    .dropzone:hover,.dropzone.is-over{border-color:var(--brand);background:var(--soft)}
    .dropzone input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%}
    .dropzone svg{width:22px;height:22px;color:var(--brand);flex:none}
    .dropzone b{display:block;font-size:13.5px}
    .dropzone small{display:block;font-size:12px;color:var(--muted)}
    code.k{background:var(--soft);padding:1px 5px;border-radius:5px;font-size:12px;font-family:ui-monospace,Consolas,monospace}

    /* ---------- Tabel ---------- */
    .tbl-wrap{position:relative;overflow-x:auto;-webkit-overflow-scrolling:touch;overscroll-behavior-x:contain}
    .tbl{width:100%;border-collapse:collapse}
    .tbl th{background:var(--soft);color:var(--muted);font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:10px 14px;border-top:1px solid var(--line);border-bottom:1px solid var(--line);white-space:nowrap;text-align:left}
    .tbl td{padding:12px 14px;border-bottom:1px solid var(--line);font-size:13.5px;vertical-align:middle}
    .tbl tbody tr{transition:background-color .12s ease-out}
    .tbl tbody tr:hover{background:var(--row-hover)}
    .tbl tbody tr:last-child td{border-bottom:0}
    .tbl .num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
    .tbl .act{text-align:right;white-space:nowrap;width:1%}
    .tbl .act > *{margin-left:4px;vertical-align:middle}
    .tbl .muted-cell{color:var(--muted);font-size:12.5px}
    .tbl .rank{color:var(--muted);font-size:12px;width:40px;text-align:center;font-variant-numeric:tabular-nums}
    .who{display:flex;align-items:center;gap:10px;min-width:0}
    .who > span:last-child{min-width:0}
    .who b{display:block;font-size:13.5px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .who small{display:block;font-size:12px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .who a{color:inherit;text-decoration:none}
    .who a:hover b{color:var(--brand);text-decoration:underline}
    .avatar{flex:none;width:34px;height:34px;border-radius:10px;background:var(--soft);color:var(--brand);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px}
    .avatar.lg{width:52px;height:52px;border-radius:14px;font-size:16px}
    img.img-missing{background:var(--soft) center/40% no-repeat url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z'/%3E%3Cpath d='m2 2 20 20'/%3E%3C/svg%3E")}
    .meter{display:grid;gap:4px;min-width:120px}
    .meter .bar2{height:6px;border-radius:3px;background:var(--bar-bg);overflow:hidden}
    .meter .bar2 i{display:block;height:100%;border-radius:3px}
    .meter small{font-size:12px;color:var(--muted);font-variant-numeric:tabular-nums}
    .fill-good{background:var(--st-submit)}.fill-warn{background:var(--st-draft)}.fill-none{background:var(--st-open)}
    .pct{display:inline-block;min-width:58px;text-align:center;border-radius:7px;padding:3px 8px;font-size:12px;font-weight:700;font-variant-numeric:tabular-nums}
    .pct.good{background:var(--t-green-bg);color:var(--t-green-fg)}.pct.ok{background:var(--t-blue-bg);color:var(--t-blue-fg)}
    .pct.warn{background:var(--t-amber-bg);color:var(--t-amber-fg)}.pct.bad{background:var(--t-rose-bg);color:var(--t-rose-fg)}.pct.none{background:var(--t-gray-bg);color:var(--t-gray-fg)}

    /* ---------- Strip statistik ---------- */
    .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 2px 8px var(--shadow);overflow:hidden}
    .stats > div{padding:14px 18px;border-right:1px solid var(--line)}
    .stats > div:last-child{border-right:0}
    .stats .k{font-size:12px;color:var(--muted);font-weight:600}
    .stats .v{font-size:19px;font-weight:800;color:var(--brand-dark);margin-top:4px;font-variant-numeric:tabular-nums;letter-spacing:-.01em}
    .stats .v small{font-size:12px;font-weight:600;color:var(--muted);margin-left:4px}

    /* ---------- Stepper setup survei ---------- */
    .steps{display:flex;flex-wrap:wrap;gap:6px;list-style:none;margin:0;padding:0;counter-reset:step}
    .steps li{flex:1 1 150px;min-width:0}
    .steps.vert{flex-direction:column}
    .steps.vert li{flex:none}
    .steps a,.steps span.cur{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:var(--card);text-decoration:none;color:var(--muted);font-size:12.5px;font-weight:600;transition:border-color .15s ease-out,background-color .15s ease-out}
    .steps a:hover{border-color:var(--brand2);color:var(--text)}
    .steps a:focus-visible{outline:2px solid var(--brand);outline-offset:2px}
    .steps .n{flex:none;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;background:var(--soft);color:var(--muted);border:1px solid var(--line)}
    .steps .done .n{background:var(--t-green-bg);color:var(--t-green-fg);border-color:transparent}
    .steps .cur{border-color:var(--brand)!important;background:var(--soft)!important;color:var(--brand-dark)!important}
    .steps .cur .n{background:var(--brand);color:#fff;border-color:transparent}
    .steps small{display:block;font-size:11.5px;font-weight:500;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .steps .lbl{min-width:0}

    /* ---------- Empty state & catatan ---------- */
    .empty{display:grid;justify-items:center;gap:6px;text-align:center;padding:32px 18px;color:var(--muted);font-size:13px}
    .empty svg{width:28px;height:28px;color:var(--brand2);margin-bottom:4px}
    .empty b{color:var(--text);font-size:13.5px}
    .note{display:flex;gap:10px;align-items:flex-start;border-radius:12px;padding:12px 14px;font-size:12.5px;line-height:1.5}
    .note svg{width:17px;height:17px;flex:none;margin-top:1px}
    .note-blue{background:var(--t-blue-bg);color:var(--t-blue-fg)}.note-green{background:var(--t-green-bg);color:var(--t-green-fg)}
    .note-amber{background:var(--t-amber-bg);color:var(--t-amber-fg)}

    /* ---------- Dialog ---------- */
    .dlg{border:0;border-radius:16px;box-shadow:0 24px 60px rgba(15,39,71,.3);color:var(--text);background:var(--card);max-width:min(540px,calc(100vw - 32px));width:100%;padding:0}
    .dlg::backdrop{background:rgba(11,36,71,.45)}
    .dlg-h{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding:18px 20px 4px}
    .dlg-h h2{margin:0;font-size:15px;color:var(--brand-dark)}
    .dlg-h p{margin:3px 0 0;font-size:12.5px;color:var(--muted)}
    .dlg-b{padding:14px 20px 4px}
    .dlg-f{display:flex;flex-wrap:wrap;gap:8px;justify-content:space-between;align-items:center;padding:14px 20px 18px}
    .dlg-f .right{display:flex;gap:8px;margin-left:auto}

    @media (max-width:1100px){
        .cols-side{grid-template-columns:minmax(0,1fr)}

        .hero-nv .ledger{grid-template-columns:1fr;gap:16px}
    }
    @media (max-width:880px){
        .cols-2{grid-template-columns:minmax(0,1fr)}
        .hero-nv{padding:18px 16px}
        .hero-nv .legend{grid-template-columns:repeat(2,minmax(0,1fr))}
        .stats > div{border-right:0;border-bottom:1px solid var(--line)}
    }
    @media (max-width:640px){
        .fld-grid{grid-template-columns:minmax(0,1fr)}
        .search{flex:1 1 auto}
        .toolbar .pg-actions{flex-wrap:wrap}
        .pg-actions{width:100%}
        .steps,.steps.vert{position:relative;flex-direction:row;flex-wrap:nowrap;overflow-x:auto;scrollbar-width:none;margin:0 -2px;padding:2px}
        .steps::-webkit-scrollbar{display:none}
        .steps li,.steps.vert li{flex:0 0 auto}
        .steps a,.steps span.cur{padding:8px 10px;gap:8px}
        .steps small{display:none}

        /* Isian 16px agar Safari iOS tidak memperbesar halaman saat kolom diketuk. */
        input,select,textarea{font-size:16px!important}
        .b{min-height:40px}
        .b.b-sm{min-height:34px}
        .pnl-h{padding:14px 14px 10px}
        .pnl-b{padding:0 14px 14px}
        .hero-nv h1{font-size:17px}
        .hero-nv .big .val{font-size:30px}
        .pg-head h1{font-size:16px}

        /* Tabel .stack menjadi kartu bertumpuk; label kolom diisi otomatis dari thead (lihat layout). */
        .tbl.stack{min-width:0!important}
        .tbl.stack thead{display:none}
        .tbl.stack,.tbl.stack tbody,.tbl.stack tr,.tbl.stack td{display:block;width:100%}
        .tbl.stack tr{padding:12px 14px;border-bottom:1px solid var(--line)}
        .tbl.stack tbody tr:last-child{border-bottom:0}
        .tbl.stack tr[hidden],.tbl.stack td:empty{display:none}
        .tbl.stack .cell-input{max-width:60%}
        .tbl.stack td{border:0;padding:3px 0;display:flex;justify-content:space-between;align-items:center;gap:14px;text-align:right;max-width:none!important;white-space:normal}
        .tbl.stack td::before{content:attr(data-label);flex:none;color:var(--muted);font-size:12px;font-weight:600;text-align:left}
        .tbl.stack td:first-child{display:block;text-align:left;padding-bottom:6px}
        .tbl.stack td:first-child::before,.tbl.stack td.act::before,.tbl.stack td[data-label=""]::before{display:none}
        .tbl.stack td.rank{display:none}
        .tbl.stack td.rank + td{display:block;text-align:left;padding-bottom:6px}
        .tbl.stack td.rank + td::before{display:none}
        .tbl.stack td.act{justify-content:flex-start;flex-wrap:wrap;gap:6px;padding-top:8px;width:100%}
        .tbl.stack td.act > *{margin-left:0}
        .tbl.stack .meter{min-width:0;width:min(200px,60%)}
        .tbl.stack .num{text-align:right}
        .tbl.stack tfoot{display:block}
    }
    @media (prefers-reduced-motion:reduce){.ui *,.b,.seg a,.seg button,.steps a{transition:none!important}}
</style>
