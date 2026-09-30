@extends('panel.layout', ['panelTitle' => $panelTitle, 'pageTitle' => 'Monitoring Progres'])

@section('menu')
    @include($menuView)
@endsection

@push('head')
    <style>
        .fx{--fx-submit:var(--st-submit);--fx-draft:var(--st-draft);--fx-open:var(--st-open);--fx-other:var(--st-other);
            --fx-submit-text:var(--st-submit-ink);--fx-draft-text:var(--st-draft-ink);--fx-open-text:var(--st-late-ink)}

        /* ---------- Header + ringkasan ---------- */
        .fx-head{background:var(--navy);color:#fff;border-radius:16px;padding:22px 24px 24px;margin-bottom:16px}
        .fx-top{display:flex;flex-wrap:wrap;gap:14px 24px;align-items:flex-start;justify-content:space-between}
        .fx-title{min-width:0;flex:1 1 360px;margin:0}
        .fx-title h1{margin:0;font-size:19px;font-weight:800;letter-spacing:-.01em;line-height:1.25;text-wrap:balance;overflow-wrap:anywhere}

        /* ---------- Filter survei (di luar kartu) ---------- */
        .fx-filter{display:flex;flex-wrap:wrap;align-items:center;gap:8px 12px;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:10px 14px;margin-bottom:14px;box-shadow:0 2px 8px var(--shadow)}
        .fx-filter-label{display:inline-flex;align-items:center;gap:7px;font-size:12.5px;font-weight:600;color:var(--muted);white-space:nowrap}
        .fx-filter-label svg{width:16px;height:16px;color:var(--brand)}
        .fx-filter-select{position:relative;flex:1 1 280px;min-width:0;max-width:520px}
        .fx-filter-select select{appearance:none;-webkit-appearance:none;width:100%;min-height:40px;padding:8px 36px 8px 12px;border:1px solid var(--line);border-radius:10px;background:var(--soft);color:var(--text);font-size:13.5px;font-weight:600;text-overflow:ellipsis;cursor:pointer}
        .fx-filter-select select:focus-visible{outline:2px solid var(--brand);outline-offset:1px}
        .fx-filter-select svg{position:absolute;right:11px;top:50%;width:17px;height:17px;transform:translateY(-50%);color:var(--muted);pointer-events:none}
        .fx-filter-count{margin-left:auto;font-size:12px;color:var(--muted);font-variant-numeric:tabular-nums}
        @media (max-width:560px){.fx-filter{padding:10px 12px}.fx-filter-select{flex-basis:100%;max-width:none}.fx-filter-count{display:none}}
        .fx-type{background:var(--navy-chip);color:#fff;border-radius:6px;padding:2px 8px;font-size:11.5px;font-weight:700;letter-spacing:.04em}
        .fx-status{border-radius:999px;padding:2px 9px;font-size:11.5px;font-weight:700}
        .fx-status.st-run{background:#dbeafe;color:#1e40af}.fx-status.st-done{background:#dcfce7;color:#166534}.fx-status.st-draft{background:#fef3c7;color:#92400e}
        .fx-empty{margin-top:20px;padding:14px 16px;border-radius:12px;background:rgba(255,255,255,.08);color:#dbe7f7;font-size:13.5px}
        .fx-empty a{color:#fff;font-weight:700;margin-left:6px}
        .fx-legend.three{grid-template-columns:repeat(3,minmax(0,1fr))}
        .fx-meta{margin-top:5px;font-size:12.5px;color:var(--navy-muted);display:flex;flex-wrap:wrap;gap:4px 10px;align-items:center}
        .fx-meta b{color:#fff;font-weight:600}
        .fx-old{background:#fef3c7;color:#92400e;border-radius:999px;padding:2px 9px;font-size:11.5px;font-weight:700}
        .fx-meta a{color:#fff;font-weight:600}
        .fx-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
        .fx-actions select{background:rgba(255,255,255,.08)!important;color:#fff!important;border:1px solid rgba(255,255,255,.22);border-radius:10px;padding:8px 10px;font-size:12.5px;max-width:260px}
        .fx-actions select option{color:#0f2747;background:#fff}
        .fx-btn-light{background:#fff!important;color:#0b2447!important;font-size:13px;padding:9px 14px;display:inline-flex;align-items:center;gap:7px}
        .fx-btn-light:hover{box-shadow:0 0 0 3px rgba(255,255,255,.25)!important;filter:none!important}
        .fx-btn-light svg{width:16px;height:16px}

        .fx-ledger{display:grid;grid-template-columns:minmax(200px,240px) 1fr;gap:28px;align-items:end;margin-top:26px}
        .fx-big .lbl{font-size:12px;color:var(--navy-muted);font-weight:600}
        .fx-big .val{font-size:36px;font-weight:800;line-height:1;margin-top:6px;letter-spacing:-.02em;font-variant-numeric:tabular-nums}
        .fx-big .sub{font-size:12px;color:var(--navy-muted);margin-top:8px}
        .fx-big .sub b{color:#fff}
        .fx-bar{display:flex;height:14px;border-radius:7px;overflow:hidden;background:var(--navy-track)}
        .fx-bar i{display:block;height:100%}
        .fx-bar i + i{box-shadow:inset 2px 0 0 var(--navy)}
        .fx-legend{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:14px}
        .fx-legend div{min-width:0}
        .fx-legend .k{display:flex;align-items:center;gap:7px;font-size:12px;color:var(--navy-muted);font-weight:600;white-space:nowrap}
        .fx-legend .v{font-size:17px;font-weight:700;margin-top:3px;font-variant-numeric:tabular-nums}
        .fx-legend .v small{font-size:12px;font-weight:600;color:var(--navy-muted);margin-left:4px}
        .fx-scope{font-size:12px;color:var(--navy-muted);margin-top:14px}
        .fx-scope b{color:#fff}
        .fx-sched{margin-top:6px;display:flex;flex-wrap:wrap;gap:4px 8px;align-items:center}
        .fx-sched-chip{border-radius:999px;padding:2px 9px;font-size:11.5px;font-weight:700}
        .fx-sched-chip.ok{background:#dcfce7;color:#166534}.fx-sched-chip.late{background:#ffe4e6;color:#9f1239}

        /* ---------- Panel import ---------- */
        .fx-import{margin-top:18px;background:var(--card);color:var(--text);border-radius:12px;padding:16px}
        .fx-import[hidden]{display:none}
        .fx-import form.up{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
        .fx-import form.up > button{padding:11px 18px}
        .fx-drop{flex:1 1 320px;position:relative;display:flex;align-items:center;gap:12px;border:1.5px dashed var(--line);border-radius:10px;padding:12px 14px;cursor:pointer;transition:border-color .15s,background-color .15s}
        .fx-drop:hover,.fx-drop.is-over{border-color:var(--brand);background:var(--soft)}
        .fx-drop input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%}
        .fx-drop svg{width:22px;height:22px;color:var(--brand);flex:none}
        .fx-drop .t{font-size:13.5px;font-weight:600}
        .fx-drop .d{font-size:12px;color:var(--muted)}
        .fx-import .hint{font-size:12px;color:var(--muted);margin:10px 0 0;line-height:1.55}
        .fx-import .hint code{background:var(--soft);padding:1px 5px;border-radius:5px;font-size:12px}
        .fx-import-foot{display:flex;flex-wrap:wrap;justify-content:space-between;gap:10px;align-items:center;margin-top:12px;padding-top:12px;border-top:1px solid var(--line)}
        .fx-link-danger{background:transparent!important;color:#be123c!important;padding:4px 0!important;font-size:12.5px;font-weight:600}
        .fx-link-danger:hover{text-decoration:underline;box-shadow:none!important}
        [data-theme="dark"] .fx-link-danger{color:#fda4b4!important}
        .fx-errors{margin-top:10px;background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;border-radius:10px;padding:10px 12px;font-size:12.5px;line-height:1.5}
        [data-theme="dark"] .fx-errors{background:#3a1620;border-color:#7f2336;color:#fda4b4}

        /* ---------- Tabel ---------- */
        .fx-card{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 2px 8px var(--shadow);overflow:hidden}
        .fx-card-h{padding:16px 20px 14px;display:grid;gap:12px}
        .fx-crumbs{display:flex;flex-wrap:wrap;align-items:center;gap:4px;font-size:12.5px;margin:0;padding:0;list-style:none}
        .fx-crumbs li{display:flex;align-items:center;gap:4px;color:var(--muted)}
        .fx-crumbs li + li::before{content:"›";color:var(--muted);margin:0 2px}
        .fx-crumbs a{color:var(--brand);text-decoration:none;font-weight:600}
        .fx-crumbs a:hover{text-decoration:underline}
        .fx-crumbs [aria-current]{color:var(--text);font-weight:600}
        .fx-bar-row{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between}
        .fx-bar-row h2{margin:0;font-size:15px;color:var(--brand-dark);display:flex;align-items:baseline;gap:8px}
        .fx-bar-row h2 small{font-size:12.5px;color:var(--muted);font-weight:600}
        .fx-tools{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
        .fx-seg{display:inline-flex;padding:3px;background:var(--soft);border:1px solid var(--line);border-radius:10px}
        .fx-seg a{padding:6px 13px;border-radius:7px;font-size:12.5px;font-weight:600;color:var(--muted);text-decoration:none}
        .fx-seg a:hover{color:var(--text)}
        .fx-seg a.on{background:var(--card);color:var(--brand);box-shadow:0 1px 3px rgba(15,39,71,.14)}
        .fx-search{position:relative}
        .fx-search svg{position:absolute;left:10px;top:50%;width:16px;height:16px;transform:translateY(-50%);color:var(--muted);pointer-events:none}
        .fx-search input{padding:8px 10px 8px 32px;width:220px;font-size:13px;background:var(--card);color:var(--text)}

        .fx-table{width:100%;border-collapse:collapse;min-width:900px}
        .fx-table th{background:var(--soft);color:var(--muted);font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:0;border-top:1px solid var(--line);border-bottom:1px solid var(--line);white-space:nowrap}
        .fx-table th .sort{all:unset;box-sizing:border-box;display:flex;align-items:center;gap:5px;width:100%;padding:11px 12px;cursor:pointer}
        .fx-table th.num .sort{justify-content:flex-end}
        .fx-table th .sort:hover{color:var(--text)}
        .fx-table th .sort:focus-visible{outline:2px solid var(--brand);outline-offset:-2px}
        .fx-table th .sort::after{content:"↕";opacity:.35;font-size:11px}
        .fx-table th[aria-sort="ascending"] .sort,.fx-table th[aria-sort="descending"] .sort{color:var(--brand)}
        .fx-table th[aria-sort="ascending"] .sort::after{content:"↑";opacity:1}
        .fx-table th[aria-sort="descending"] .sort::after{content:"↓";opacity:1}
        .fx-table th.plain{padding:11px 12px}
        .fx-table td{padding:12px;border-bottom:1px solid var(--line);font-size:13.5px;vertical-align:middle}
        .fx-table tbody tr{transition:background-color .12s}
        .fx-table tbody tr[data-href],.fx-table tbody tr[data-expandable]{cursor:pointer}
        .fx-table td.fx-cell-name{padding-left:calc(12px + var(--d, 0) * 22px)}
        .fx-tog{all:unset;box-sizing:border-box;display:inline-flex;align-items:center;gap:6px;cursor:pointer;border-radius:6px;margin-left:-4px;padding:1px 4px}
        .fx-tog:focus-visible{outline:2px solid var(--brand);outline-offset:1px}
        .fx-tog svg{width:14px;height:14px;flex:none;color:var(--muted);transition:transform .16s ease-out}
        .fx-tog[aria-expanded="true"] svg{transform:rotate(90deg);color:var(--brand)}
        .fx-tog + .fx-sub{margin-left:20px}
        /* Tingkatan baris: makin dalam makin ringan (tebal huruf, ukuran, dan isi chip persen). */
        .fx-table tbody tr[data-depth="0"][data-expandable] .fx-name{font-weight:700}
        .fx-table tbody tr[data-depth="0"][data-expandable] .c-total{font-weight:700}
        .fx-table tbody tr[data-depth="0"][data-expandable] .fx-pct{font-weight:700}
        .fx-table tbody tr[data-depth="1"]{background:var(--soft2)}
        .fx-table tbody tr[data-depth="1"] td{font-size:13px;padding-top:10px;padding-bottom:10px}
        .fx-table tbody tr[data-depth="1"] .fx-name{font-weight:500}
        .fx-table tbody tr[data-depth="1"] .c-total,.fx-table tbody tr[data-depth="1"] .c-submit,.fx-table tbody tr[data-depth="1"] .c-draft{font-weight:500}
        .fx-table tbody tr[data-depth="1"] .fx-pct{font-weight:500;background:transparent;box-shadow:inset 0 0 0 1.5px currentColor}
        .fx-table tbody tr[data-depth="1"] .fx-minibar{height:6px}
        .fx-table tbody tr[data-depth="2"],.fx-table tbody tr[data-depth="3"]{background:var(--soft)}
        .fx-table tbody tr[data-depth="2"] td,.fx-table tbody tr[data-depth="3"] td{font-size:12.5px;padding-top:8px;padding-bottom:8px}
        .fx-table tbody tr[data-depth="2"] .fx-name,.fx-table tbody tr[data-depth="3"] .fx-name{font-weight:400;color:var(--muted)}
        .fx-table tbody tr[data-depth="2"] td.num,.fx-table tbody tr[data-depth="3"] td.num{font-weight:400}
        .fx-table tbody tr[data-depth="2"] .fx-pct,.fx-table tbody tr[data-depth="3"] .fx-pct{font-weight:400;background:transparent;padding-left:0;padding-right:0}
        .fx-table tbody tr[data-depth="2"] .fx-minibar,.fx-table tbody tr[data-depth="3"] .fx-minibar{height:4px}
        .fx-table tbody tr.fx-row:not([data-depth="0"]):hover{background:var(--row-hover)}
        @media (prefers-reduced-motion:reduce){.fx-tog svg{transition:none}}
        .fx-table tbody tr:hover{background:var(--row-hover)}
        .fx-table .no{color:var(--muted);font-size:12px;width:44px;text-align:center;font-variant-numeric:tabular-nums}
        .fx-table .num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
        .fx-name{font-weight:600;color:var(--text);text-decoration:none}
        .fx-name:hover{color:var(--brand);text-decoration:underline}
        .fx-sub{display:block;font-size:12px;color:var(--muted);margin-top:2px;font-weight:400}
        .fx-sub code{font-family:ui-monospace,Consolas,monospace;font-size:11.5px}
        .fx-mitra{font-size:12.5px;line-height:1.45;max-width:260px}
        .c-total{font-weight:700}
        .c-submit{color:var(--fx-submit-text);font-weight:600}
        .c-draft{color:var(--fx-draft-text);font-weight:600}
        .c-open.hot{color:var(--fx-open-text);font-weight:700}
        .c-other{color:var(--muted)}
        .fx-table td.zero{color:var(--muted);font-weight:400;opacity:.7}
        .fx-prog{display:flex;align-items:center;gap:10px;justify-content:flex-end}
        .fx-minibar{width:84px;height:6px;border-radius:3px;background:var(--bar-bg);overflow:hidden;display:flex}
        .fx-minibar i{display:block;height:100%}
        .fx-pct{min-width:62px;text-align:center;border-radius:7px;padding:3px 8px;font-size:12px;font-weight:700;font-variant-numeric:tabular-nums}
        .fx-go{width:28px;color:var(--muted);text-align:center;font-weight:700}
        .fx-table tfoot td{background:var(--soft);font-weight:700;border-bottom:0}
        .fx-none td{padding:32px 12px;text-align:center;color:var(--muted)}

        @media (max-width:1100px){
            .fx-ledger{grid-template-columns:1fr;gap:18px}
        }
        @media (max-width:880px){
            .fx-head{padding:18px 16px 20px}
            .fx-legend{grid-template-columns:repeat(2,minmax(0,1fr))}
            .fx-big .val{font-size:32px}
            .fx-card-h{padding:14px 16px}
            .fx-search{flex:1 1 180px}
            .fx-search input{width:100%}
            .fx-actions,.fx-actions select{width:100%;max-width:none}
        }

        /* ---------- Entri per hari (PAPI) ---------- */
        .fx-daily{margin-bottom:16px;padding:16px 20px 14px}
        .fx-daily-h{display:flex;flex-wrap:wrap;gap:12px 28px;align-items:flex-start;justify-content:space-between}
        .fx-daily-h h2{margin:0;font-size:15px;color:var(--brand-dark)}
        .fx-daily-h p{margin:3px 0 0;font-size:12px;color:var(--muted)}
        .fx-daily-stats{display:flex;gap:22px;margin:0}
        .fx-daily-stats dt{font-size:12px;color:var(--muted);font-weight:600;white-space:nowrap}
        .fx-daily-stats dd{margin:2px 0 0;font-size:17px;font-weight:800;color:var(--text);font-variant-numeric:tabular-nums}
        .fx-daily-stats dd small{font-size:11.5px;font-weight:600;color:var(--muted);margin-left:5px}
        .fx-daily{scroll-margin-top:80px}
        .fx-week{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:14px;padding-top:12px;border-top:1px solid var(--line)}
        .fx-week-label{font-size:12.5px;font-weight:700;color:var(--text);text-align:center}
        .fx-week .is-off{opacity:.45;cursor:not-allowed}
        .fx-week .is-off:hover{background:var(--soft);color:var(--text)}
        .fx-daily-scroll{overflow-x:auto;margin-top:14px;padding-bottom:2px}
        .fx-days{list-style:none;margin:0;padding:0;display:grid;grid-auto-flow:column;grid-auto-columns:minmax(0,1fr);gap:4px;align-items:end;height:150px}
        .fx-days li{display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;gap:4px;min-width:0}
        .fx-days b{font-size:10.5px;font-weight:700;color:var(--text);font-variant-numeric:tabular-nums;line-height:1}
        .fx-days i{display:block;width:100%;max-width:56px;min-height:2px;border-radius:4px 4px 1px 1px;background:var(--fx-submit);opacity:.75}
        .fx-days li.zero i{background:var(--line);opacity:1}
        .fx-days li.last i{opacity:1}
        .fx-days li:hover i{opacity:1}
        .fx-days span:not(.sr-only){display:grid;justify-items:center;font-size:10.5px;font-weight:600;color:var(--muted);line-height:1.15;font-variant-numeric:tabular-nums}
        .fx-days span small{font-size:9.5px;font-weight:500}
        .fx-days li.last span:not(.sr-only){color:var(--text)}
        @media (max-width:560px){.fx-daily{padding:14px}.fx-daily-stats{gap:16px}}

        .fx-pager{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 16px;border-top:1px solid var(--line)}
        .fx-pager[hidden]{display:none}
        .fx-pager span{font-size:12.5px;font-weight:600;color:var(--muted);font-variant-numeric:tabular-nums}
        .fx-pager .b:disabled{opacity:.45;cursor:not-allowed}

        /* ---------- Peringatan ---------- */
        .fx-warn{margin-top:16px;padding:16px 20px 10px}
        .fx-warn-h h2{margin:0;font-size:15px;color:var(--brand-dark);display:flex;align-items:center;gap:8px}
        .fx-warn-h p{margin:3px 0 0;font-size:12px;color:var(--muted)}
        .fx-warn-count{border-radius:999px;padding:1px 9px;font-size:12px;font-weight:700;background:var(--t-rose-bg);color:var(--t-rose-fg)}
        .fx-warn-list{list-style:none;margin:8px 0 0;padding:0;display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:0 28px}
        .fx-warn-list li{display:flex;align-items:center;gap:10px;padding:8px 0;border-top:1px solid var(--line);min-width:0}
        .fx-warn-list li.wide{grid-column:1 / -1;border-top:0}
        .fx-warn-ico{flex:none;width:28px;height:28px;border-radius:8px;display:grid;place-items:center;background:var(--t-rose-bg);color:var(--t-rose-fg)}
        .fx-warn-ico.ok{background:var(--t-green-bg);color:var(--t-green-fg)}
        .fx-warn-ico svg{width:15px;height:15px}
        .fx-warn-txt{flex:1 1 auto;min-width:0}
        .fx-warn-txt b{display:block;font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .fx-warn-txt small{display:block;font-size:12px;color:var(--muted);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        /* Meter kecil: isi = capaian, garis tegak = target. */
        .fx-meter{position:relative;flex:none;width:72px;height:6px;border-radius:3px;background:var(--bar-bg)}
        .fx-warn-list li.wide .fx-meter{width:120px}
        .fx-meter i{display:block;height:100%;border-radius:3px;min-width:2px}
        .fx-meter i.late{background:var(--st-late)}.fx-meter i.warn{background:var(--st-draft)}.fx-meter i.ok{background:var(--st-submit)}
        .fx-meter em{position:absolute;top:-3px;bottom:-3px;width:2px;margin-left:-1px;border-radius:1px;background:var(--brand-dark)}
        .fx-warn-chip{flex:none;min-width:54px;text-align:center;border-radius:999px;padding:1px 9px;font-size:11.5px;font-weight:700;white-space:nowrap;font-variant-numeric:tabular-nums;background:var(--t-amber-bg);color:var(--t-amber-fg)}
        .fx-warn-chip.late{background:var(--t-rose-bg);color:var(--t-rose-fg)}
        .fx-warn-chip.ok{background:var(--t-green-bg);color:var(--t-green-fg)}
        @media (max-width:560px){.fx-warn-list{grid-template-columns:minmax(0,1fr)}.fx-meter,.fx-warn-list li.wide .fx-meter{width:48px}}
        @media (prefers-reduced-motion:reduce){.fx *{transition:none!important}}
    </style>
@endpush


@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $fmtPct = fn ($n) => number_format((float) $n, 1, ',', '.').'%';
    $share = fn ($part, $total) => $total > 0 ? round($part / $total * 100, 2) : 0;
    $tone = fn ($p) => $p >= 90 ? 't-good' : ($p >= 70 ? 't-ok' : ($p >= 50 ? 't-warn' : 't-bad'));
    $isCapi = $selectedSurvey?->isCapi() ?? false;
    $surveyParam = $selectedSurvey ? ['survey' => $selectedSurvey->id] : [];
    $importParam = ($selectedImport && ! $isLatestImport) ? ['import' => $selectedImport->id] : [];
    $url = function (array $params = [], ?string $path = null) use ($surveyParam, $importParam, $basePath): string {
        $query = http_build_query(array_filter([...$surveyParam, ...$importParam, ...$params], fn ($v) => $v !== null && $v !== ''));

        return ($path ?? $basePath).($query !== '' ? '?'.$query : '');
    };
    $scopeParams = array_intersect_key($filters, array_flip(['kecamatan', 'desa', 'sls']));
    $currentParams = array_intersect_key($filters, array_flip(['mode', 'kecamatan', 'desa', 'sls', 'mitra']));
    $levelLabel = \App\Services\FasihProgressReport::LEVELS[$level];
    $isMitraLevel = $level === 'mitra';
    $columnCount = $isCapi ? 10 : 9;
    $isMitraDetail = $mode === 'mitra' && $level === 'sls';
    $scopeLabel = end($breadcrumbs)['label'];
    $unit = $isCapi ? 'dokumen' : 'ruta';
    $showImportPanel = $canManage && $isCapi && (! $selectedImport || $errors->has('file'));
    $statusPill = fn ($status) => match ($status) { 'Selesai' => 'st-done', 'Draft' => 'st-draft', default => 'st-run' };
@endphp

@section('content')
<div class="fx">
    <form class="fx-filter" method="GET" action="{{ $basePath }}" role="search" aria-label="Pilih survei">
        <label class="fx-filter-label" for="fxSurvey">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 3H2l8 9.46V19l4 2v-8.54z"/></svg>
            Pilih survei
        </label>
        <div class="fx-filter-select">
            <select id="fxSurvey" name="survey" onchange="this.form.submit()" @disabled($surveys->isEmpty())>
                @forelse ($surveys as $item)
                    <option value="{{ $item->id }}" @selected($selectedSurvey?->is($item))>{{ $item->title }} · {{ $item->typeLabel() }}</option>
                @empty
                    <option>Tidak ada survei berjalan</option>
                @endforelse
            </select>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </div>
        <span class="fx-filter-count">{{ $surveys->count() }} survei berjalan</span>
        <noscript><button type="submit">Tampilkan</button></noscript>
    </form>

    <section class="fx-head" aria-labelledby="fxHeadTitle">
        <div class="fx-top">
            <div class="fx-title">
                <h1 id="fxHeadTitle">{{ $selectedSurvey ? 'Progres pencacahan '.$selectedSurvey->title : 'Monitoring progres' }}</h1>
                @if ($selectedSurvey)
                    <div class="fx-meta">
                        <span class="fx-type">{{ $selectedSurvey->typeLabel() }}</span>
                        <span class="fx-status {{ $statusPill($selectedSurvey->status) }}">{{ $selectedSurvey->status }}</span>
                        <span>{{ $selectedSurvey->start_date->translatedFormat('d M Y') }} – {{ $selectedSurvey->end_date->translatedFormat('d M Y') }}</span>
                        @if ($isCapi && $selectedImport)
                            <span>·</span>
                            @unless ($isLatestImport)<span class="fx-old">Data lama</span>@endunless
                            <span>Data FASIH per <b>{{ $selectedImport->created_at->translatedFormat('d M Y, H:i') }}</b></span>
                            @unless ($isLatestImport)<a href="{{ $basePath }}?survey={{ $selectedSurvey->id }}">Buka data terbaru</a>@endunless
                        @elseif (! $isCapi)
                            <span>·</span>
                            <span>Dihitung langsung dari alokasi dan entri mitra</span>
                        @endif
                    </div>
                @endif
            </div>

            @if ($selectedSurvey)
                <div class="fx-actions">
                    @if ($isCapi && $imports->count() > 1)
                        <form method="GET" action="{{ $basePath }}">
                            <input type="hidden" name="survey" value="{{ $selectedSurvey->id }}">
                            <select name="import" aria-label="Riwayat data import" onchange="this.form.submit()">
                                @foreach ($imports as $item)
                                    <option value="{{ $item->id }}" @selected($selectedImport?->is($item))>
                                        {{ $loop->first ? 'Terbaru · ' : '' }}{{ $item->created_at->translatedFormat('d M Y, H:i') }}
                                    </option>
                                @endforeach
                            </select>
                            <noscript><button type="submit">Buka</button></noscript>
                        </form>
                    @endif
                    @if ($canManage && $isCapi)
                        <button type="button" class="fx-btn-light" id="fxImportToggle" aria-controls="fxImport" aria-expanded="{{ $showImportPanel ? 'true' : 'false' }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M7 8l5-5 5 5"/><path d="M5 15v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"/></svg>
                            Import CSV
                        </button>
                    @endif
                </div>
            @endif
        </div>

        @if ($canManage && $isCapi)
            <div class="fx-import" id="fxImport" @unless ($showImportPanel) hidden @endunless>
                <form class="up" method="POST" action="/admin/monitoring/progres/import" enctype="multipart/form-data" id="fxImportForm">
                    @csrf
                    <input type="hidden" name="survey_id" value="{{ $selectedSurvey->id }}">
                    <label class="fx-drop" id="fxDrop">
                        <input type="file" name="file" accept=".csv,text/csv" required aria-describedby="fxImportHint">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/></svg>
                        <span><span class="t" id="fxDropName">Pilih atau seret file CSV ke sini</span><span class="d" style="display:block">Ekspor progres FASIH, maksimal 10 MB</span></span>
                    </label>
                    <button type="submit" id="fxImportBtn">Import data</button>
                </form>
                @if ($errors->has('file'))
                    <div class="fx-errors" role="alert">
                        @foreach ($errors->get('file') as $message)
                            <div>{{ is_array($message) ? implode(' ', $message) : $message }}</div>
                        @endforeach
                    </div>
                @endif
                <p class="hint" id="fxImportHint">
                    Kolom yang dibaca: <code>email</code>, <code>regionCode</code>, <code>totalRegion</code>, <code>statusBreakdown</code>.
                    Nama pencacah diambil dari kolom <code>namaPetugas</code>; bila kosong, dari akun SIMPROCA dengan email yang sama.
                    File baru menjadi data terbaru survei ini; data sebelumnya tetap ada di riwayat.
                </p>
                @if ($selectedImport)
                    <div class="fx-import-foot">
                        <span class="hint" style="margin:0">Data yang sedang dibuka: {{ $selectedImport->file_name }} · {{ $fmt($selectedImport->row_count) }} baris{{ $selectedImport->user ? ' · diimpor '.$selectedImport->user->name : '' }}</span>
                        <form method="POST" action="/admin/monitoring/progres/imports/{{ $selectedImport->id }}" data-confirm="Hapus data import ini? Progres dari file {{ $selectedImport->file_name }} akan hilang.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="fx-link-danger">Hapus data ini</button>
                        </form>
                    </div>
                @endif
            </div>
        @endif

        @if ($hasData)
            <div class="fx-ledger">
                <div class="fx-big">
                    <div class="lbl">Progres submit</div>
                    <div class="val">{{ $fmtPct($totals['percent']) }}</div>
                    <div class="sub"><b>{{ $fmt($totals['submit']) }}</b> dari {{ $fmt($totals['total']) }} {{ $unit }}</div>
                </div>
                <div>
                    <div class="fx-bar" role="img" aria-label="Submit {{ $fmt($totals['submit']) }}, draft {{ $fmt($totals['draft']) }}, open {{ $fmt($totals['open']) }}{{ $isCapi ? ', lainnya '.$fmt($totals['other']) : '' }}">
                        <i style="width:{{ $share($totals['submit'], $totals['total']) }}%;background:var(--fx-submit)"></i>
                        <i style="width:{{ $share($totals['draft'], $totals['total']) }}%;background:var(--fx-draft)"></i>
                        <i style="width:{{ $share($totals['open'], $totals['total']) }}%;background:var(--fx-open)"></i>
                        <i style="width:{{ $share($totals['other'], $totals['total']) }}%;background:var(--fx-other)"></i>
                    </div>
                    <div class="fx-legend {{ $isCapi ? '' : 'three' }}">
                        <div title="{{ $isCapi ? 'SUBMITTED BY Pencacah + APPROVED BY Pengawas' : 'Entri yang sudah dikirim mitra' }}">
                            <div class="k"><i class="sw submit"></i>Submit</div>
                            <div class="v">{{ $fmt($totals['submit']) }}<small>{{ $fmtPct($totals['percent']) }}</small></div>
                        </div>
                        <div title="{{ $isCapi ? 'DRAFT' : 'Sudah diisi mitra, belum dikirim' }}">
                            <div class="k"><i class="sw draft"></i>Draft</div>
                            <div class="v">{{ $fmt($totals['draft']) }}<small>{{ $fmtPct($share($totals['draft'], $totals['total'])) }}</small></div>
                        </div>
                        <div title="{{ $isCapi ? 'OPEN + REJECTED BY Pengawas' : 'Ruta atau target yang belum diisi' }}">
                            <div class="k"><i class="sw open"></i>Open (sisa)</div>
                            <div class="v">{{ $fmt($totals['open']) }}<small>{{ $fmtPct($share($totals['open'], $totals['total'])) }}</small></div>
                        </div>
                        @if ($isCapi)
                            <div title="EDITED BY Admin Kabupaten, REVOKED BY Pengawas, SUBMITTED RESPONDENT, dll.">
                                <div class="k"><i class="sw other"></i>Lainnya</div>
                                <div class="v">{{ $fmt($totals['other']) }}<small>{{ $fmtPct($share($totals['other'], $totals['total'])) }}</small></div>
                            </div>
                        @endif
                    </div>
                    <div class="fx-scope">Cakupan <b>{{ $scopeLabel }}</b> · {{ $fmt($totals['mitra_count']) }} mitra · {{ $fmt($totals['sls_count']) }} SLS</div>
                    @php
                        $isBehind = $selectedSurvey->isBehindSchedule((float) $totals['percent']);
                    @endphp
                    <div class="fx-scope fx-sched">
                        @if ($passedCheckpoint)
                            Checkpoint {{ $passedCheckpoint->checkpoint_date->locale('id')->translatedFormat('d M') }}: target <b>{{ $passedCheckpoint->target_percentage }}%</b>, progres <b>{{ $fmtPct($totals['percent']) }}</b>
                        @else
                            Jadwal: <b>{{ $fmtPct($selectedSurvey->elapsedPercent()) }}</b> waktu survei sudah berjalan, progres <b>{{ $fmtPct($totals['percent']) }}</b>
                        @endif
                        <span class="fx-sched-chip {{ $isBehind ? 'late' : 'ok' }}">{{ $isBehind ? ($passedCheckpoint ? 'Di bawah target' : 'Tertinggal jadwal') : ($passedCheckpoint ? 'Target tercapai' : 'Sesuai jadwal') }}</span>
                    </div>
                </div>
            </div>
        @elseif ($selectedSurvey && ! $showImportPanel)
            <div class="fx-empty">
                @if ($isCapi)
                    {{ $canManage ? 'Belum ada data FASIH untuk survei ini. Klik Import CSV untuk mengunggah data scraping.' : 'Admin belum mengimpor data FASIH untuk survei ini.' }}
                @else
                    Belum ada alokasi mitra untuk survei ini.
                    @if ($canManage)<a href="/admin/surveys/{{ $selectedSurvey->id }}/assignments">Atur alokasi mitra</a>@endif
                @endif
            </div>
        @elseif (! $selectedSurvey)
            <div class="fx-empty">
                Tidak ada survei yang sedang berjalan. Survei Draft belum dijalankan dan survei yang ditandai selesai tidak dipantau lagi.
                @if ($canManage)<a href="/admin/surveys/create">Buat survei</a>@endif
            </div>
        @endif
    </section>

    @if ($hasData && $dailyEntries->isNotEmpty())
        @php
            $dailyMax = max(1, (int) $dailyEntries->max('count'));
            $dailyTotal = (int) $dailyEntries->sum('count');
            $dailyToday = $dailyEntries->first(fn ($day) => $day['date']->isToday());
            $dailyBest = $dailyEntries->sortByDesc('count')->first();
            $weekUrl = fn (int $week) => $url([...$currentParams, 'minggu' => $week > 0 ? $week : null]).'#fxDaily';
        @endphp
        <section class="fx-card fx-daily" id="fxDaily" aria-labelledby="fxDailyTitle">
            <div class="fx-daily-h">
                <div>
                    <h2 id="fxDailyTitle">Entri per hari</h2>
                    <p>Entri yang dikirim mitra pada survei ini, {{ $dailyEntries->first()['date']->locale('id')->translatedFormat('d M') }} – {{ $dailyEntries->last()['date']->locale('id')->translatedFormat('d M Y') }}.</p>
                </div>
                <dl class="fx-daily-stats">
                    @if ($dailyToday)
                        <div><dt>Hari ini</dt><dd>{{ $fmt($dailyToday['count']) }}</dd></div>
                    @endif
                    <div><dt>{{ $dailyWeek === 0 ? 'Minggu ini' : 'Total seminggu' }}</dt><dd>{{ $fmt($dailyTotal) }}</dd></div>
                    <div><dt>Tertinggi</dt><dd>{{ $fmt($dailyBest['count']) }}<small>{{ $dailyBest['count'] > 0 ? $dailyBest['date']->locale('id')->translatedFormat('d M') : '' }}</small></dd></div>
                </dl>
            </div>
            <nav class="fx-week" aria-label="Pindah minggu">
                @if ($dailyWeek < $dailyMaxWeek)
                    <a class="b b-soft b-sm" href="{{ $weekUrl($dailyWeek + 1) }}" rel="prev">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                        Minggu sebelumnya
                    </a>
                @else
                    <span class="b b-soft b-sm is-off" aria-disabled="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                        Minggu sebelumnya
                    </span>
                @endif
                <span class="fx-week-label">{{ $dailyWeek === 0 ? 'Minggu ini' : $dailyWeek.' minggu lalu' }}</span>
                @if ($dailyWeek > 0)
                    <a class="b b-soft b-sm" href="{{ $weekUrl($dailyWeek - 1) }}" rel="next">
                        Minggu berikutnya
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                    </a>
                @else
                    <span class="b b-soft b-sm is-off" aria-disabled="true">
                        Minggu berikutnya
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                    </span>
                @endif
            </nav>
            <div class="fx-daily-scroll">
                <ol class="fx-days" aria-label="Entri terkirim per hari" style="min-width:{{ $dailyEntries->count() * 21 }}px">
                    @foreach ($dailyEntries as $day)
                        <li class="{{ $day['date']->isToday() ? 'last' : '' }} {{ $day['count'] === 0 ? 'zero' : '' }}" title="{{ $day['date']->locale('id')->translatedFormat('l, d M Y') }}: {{ $fmt($day['count']) }} entri">
                            <b aria-hidden="true">{{ $day['count'] > 0 ? $fmt($day['count']) : '' }}</b>
                            <i style="height:{{ round($day['count'] / $dailyMax * 100, 1) }}%"></i>
                            <span aria-hidden="true">{{ $day['date']->locale('id')->translatedFormat('D') }}<small>{{ $day['date']->locale('id')->translatedFormat('d M') }}</small></span>
                            <span class="sr-only">{{ $day['date']->locale('id')->translatedFormat('l, d F') }}: {{ $fmt($day['count']) }} entri</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    @if ($hasData)
        <section class="fx-card" aria-labelledby="fxTableTitle">
            <div class="fx-card-h">
                <nav aria-label="Cakupan wilayah">
                    <ol class="fx-crumbs">
                        @foreach ($breadcrumbs as $crumb)
                            <li>
                                @if ($loop->last)
                                    <span aria-current="page">{{ $crumb['label'] }}</span>
                                @else
                                    <a href="{{ $url($crumb['params']) }}">{{ $crumb['label'] }}</a>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
                <div class="fx-bar-row">
                    <h2 id="fxTableTitle">Progres per {{ $levelLabel }} <small id="fxCount">{{ $fmt($groups->count()) }}</small></h2>
                    <div class="fx-tools">
                        <div class="fx-seg" role="group" aria-label="Sudut pandang">
                            <a href="{{ $url($scopeParams) }}" class="{{ $mode === 'wilayah' ? 'on' : '' }}" @if ($mode === 'wilayah') aria-current="page" @endif>Wilayah</a>
                            <a href="{{ $url(['mode' => 'mitra', ...$scopeParams]) }}" class="{{ $mode === 'mitra' ? 'on' : '' }}" @if ($mode === 'mitra') aria-current="page" @endif>Mitra</a>
                        </div>
                        <label class="fx-search">
                            <span class="sr-only" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)">Cari {{ strtolower($levelLabel) }}</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            <input type="search" id="fxSearch" placeholder="Cari {{ strtolower($levelLabel) }}…" autocomplete="off">
                        </label>
                    </div>
                </div>
            </div>

            <div style="position:relative;overflow-x:auto;-webkit-overflow-scrolling:touch">
                <table class="fx-table" id="fxTable" @if ($mode === 'mitra' && $isMitraLevel) data-page-size="10" @endif>
                    <thead>
                        <tr>
                            <th class="plain no">No</th>
                            <th data-type="text"><button type="button" class="sort">{{ $isMitraLevel ? 'Nama Pencacah' : $levelLabel }}</button></th>
                            <th data-type="{{ $isMitraLevel ? 'num' : 'text' }}" class="{{ $isMitraLevel ? 'num' : '' }}"><button type="button" class="sort">{{ $isMitraLevel ? 'SLS' : ($isMitraDetail ? 'Desa/Kelurahan' : 'Mitra') }}</button></th>
                            <th data-type="num" class="num"><button type="button" class="sort">Beban</button></th>
                            <th data-type="num" class="num"><button type="button" class="sort">Submit</button></th>
                            <th data-type="num" class="num"><button type="button" class="sort">Draft</button></th>
                            <th data-type="num" class="num"><button type="button" class="sort">Open</button></th>
                            @if ($isCapi)<th data-type="num" class="num"><button type="button" class="sort">Lainnya</button></th>@endif
                            <th data-type="num" class="num"><button type="button" class="sort">Progres</button></th>
                            <th class="plain" aria-hidden="true"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($groups as $group)
                            @include('panel.partials.monitoring-row', ['group' => $group, 'depth' => 0, 'parentId' => null, 'rowIndex' => $loop->index])
                        @empty
                            <tr class="fx-none"><td colspan="{{ $columnCount }}">Tidak ada data pada cakupan ini.</td></tr>
                        @endforelse
                        <tr class="fx-none" id="fxNoMatch" hidden><td colspan="{{ $columnCount }}">Tidak ada {{ strtolower($levelLabel) }} yang cocok dengan pencarian.</td></tr>
                    </tbody>
                    @if ($groups->count() > 1)
                        <tfoot>
                            <tr>
                                <td></td>
                                <td>Total</td>
                                <td class="{{ $isMitraLevel ? 'num' : '' }}">{{ $isMitraLevel ? $fmt($totals['sls_count']) : $fmt($totals['mitra_count']).' mitra · '.$fmt($totals['sls_count']).' SLS' }}</td>
                                <td class="num">{{ $fmt($totals['total']) }}</td>
                                <td class="num c-submit">{{ $fmt($totals['submit']) }}</td>
                                <td class="num c-draft">{{ $fmt($totals['draft']) }}</td>
                                <td class="num">{{ $fmt($totals['open']) }}</td>
                                @if ($isCapi)<td class="num c-other">{{ $fmt($totals['other']) }}</td>@endif
                                <td class="num"><span class="fx-pct {{ $tone($totals['percent']) }}">{{ $fmtPct($totals['percent']) }}</span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            @if ($mode === 'mitra' && $isMitraLevel)
                <div class="fx-pager" id="fxPager" hidden>
                    <button type="button" class="b b-soft b-sm" id="fxPrev">Sebelumnya</button>
                    <span id="fxPageInfo"></span>
                    <button type="button" class="b b-soft b-sm" id="fxNext">Berikutnya</button>
                </div>
            @endif
        </section>
    @endif

    @if ($hasData)
        @php
            $scheduleLate = $selectedSurvey->isBehindSchedule((float) $totals['percent']);
            $warningCount = $belowCheckpoint->count() + ($scheduleLate ? 1 : 0);
        @endphp
        <section class="fx-card fx-warn" aria-labelledby="fxWarnTitle">
            @php
                // Patokan yang dibandingkan dengan progres: target checkpoint, atau porsi waktu bila belum ada checkpoint.
                $benchmark = $passedCheckpoint ? (float) $passedCheckpoint->target_percentage : (float) $selectedSurvey->elapsedPercent();
                $benchmarkLabel = $passedCheckpoint ? 'target checkpoint' : 'waktu berjalan';
                $surveyGap = max(0, (int) ceil($totals['total'] * $benchmark / 100) - (int) $totals['submit']);
            @endphp
            <div class="fx-warn-h">
                <div>
                    <h2 id="fxWarnTitle">Peringatan @if ($warningCount > 0)<span class="fx-warn-count">{{ $fmt($warningCount) }}</span>@endif</h2>
                    <p>
                        @if ($passedCheckpoint)
                            Capaian terhadap checkpoint {{ $passedCheckpoint->checkpoint_date->locale('id')->translatedFormat('d M Y') }}, target {{ $passedCheckpoint->target_percentage }}%.
                        @else
                            Posisi survei terhadap jadwal. Belum ada checkpoint yang lewat.
                        @endif
                    </p>
                </div>
            </div>
            <ul class="fx-warn-list">
                <li class="wide">
                    <span class="fx-warn-ico {{ $scheduleLate ? '' : 'ok' }}" aria-hidden="true">
                        @if ($scheduleLate)
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v5M12 16.5h.01"/><circle cx="12" cy="12" r="9"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        @endif
                    </span>
                    <span class="fx-warn-txt">
                        <b>{{ $scheduleLate ? ($passedCheckpoint ? 'Survei di bawah target checkpoint' : 'Survei tertinggal jadwal') : ($passedCheckpoint ? 'Survei mencapai target checkpoint' : 'Survei sesuai jadwal') }}</b>
                        <small>Progres {{ $fmtPct($totals['percent']) }} dari {{ $benchmarkLabel }} {{ $fmtPct($benchmark) }}{{ $scheduleLate ? ' · kurang '.$fmt($surveyGap).' '.$unit : '' }}</small>
                    </span>
                    <span class="fx-meter" role="img" aria-label="Progres {{ $fmtPct($totals['percent']) }}, {{ $benchmarkLabel }} {{ $fmtPct($benchmark) }}">
                        <i class="{{ $scheduleLate ? 'late' : 'ok' }}" style="width:{{ min(100, $totals['percent']) }}%"></i>
                        <em style="left:{{ min(100, $benchmark) }}%"></em>
                    </span>
                    <span class="fx-warn-chip {{ $scheduleLate ? 'late' : 'ok' }}">{{ $fmtPct($totals['percent']) }}</span>
                </li>
                @foreach ($belowCheckpoint as $mitra)
                    @php
                        $mitraGap = max(1, (int) ceil($mitra['target'] * $passedCheckpoint->target_percentage / 100) - $mitra['progress']);
                    @endphp
                    <li>
                        <span class="fx-warn-txt">
                            <b title="{{ $mitra['name'] }}">{{ $mitra['name'] }}</b>
                            <small>{{ $fmt($mitra['progress']) }} dari {{ $fmt($mitra['target']) }} {{ $unit }} · kurang {{ $fmt($mitraGap) }}</small>
                        </span>
                        <span class="fx-meter" role="img" aria-label="Capaian {{ $fmtPct($mitra['percent']) }} dari target {{ $passedCheckpoint->target_percentage }}%">
                            <i class="warn" style="width:{{ min(100, $mitra['percent']) }}%"></i>
                            <em style="left:{{ $passedCheckpoint->target_percentage }}%"></em>
                        </span>
                        <span class="fx-warn-chip">{{ $fmtPct($mitra['percent']) }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
@endsection

@push('scripts')
    <script>
        (function () {
            const toggle = document.getElementById('fxImportToggle');
            const panel = document.getElementById('fxImport');
            toggle?.addEventListener('click', () => {
                const open = panel.hidden;
                panel.hidden = !open;
                toggle.setAttribute('aria-expanded', String(open));
                if (open) panel.querySelector('input[type=file]')?.focus();
            });

            const drop = document.getElementById('fxDrop');
            const fileInput = drop?.querySelector('input[type=file]');
            fileInput?.addEventListener('change', () => {
                document.getElementById('fxDropName').textContent = fileInput.files[0]?.name || 'Pilih atau seret file CSV ke sini';
            });
            ['dragenter', 'dragover'].forEach((type) => drop?.addEventListener(type, () => drop.classList.add('is-over')));
            ['dragleave', 'drop'].forEach((type) => drop?.addEventListener(type, () => drop.classList.remove('is-over')));
            document.getElementById('fxImportForm')?.addEventListener('submit', () => {
                const button = document.getElementById('fxImportBtn');
                button.disabled = true;
                button.textContent = 'Mengimpor…';
            });

            // Grafik harian yang lebih lebar dari layar dibuka pada hari terbaru.
            const daily = document.querySelector('.fx-daily-scroll');
            if (daily) daily.scrollLeft = daily.scrollWidth;

            const table = document.getElementById('fxTable');
            if (!table) return;
            const tbody = table.tBodies[0];
            // Baris tingkat teratas; turunannya (desa, SLS, mitra) mengikuti induknya saat diurutkan atau dicari.
            const dataRows = () => [...tbody.querySelectorAll('tr.fx-row[data-depth="0"]')];
            const descendants = (row) => [...tbody.querySelectorAll('tr.fx-row[data-id^="' + row.dataset.id + '-"]')];
            const setExpanded = (row, open) => {
                row.querySelector('.fx-tog')?.setAttribute('aria-expanded', String(open));
                if (open) {
                    tbody.querySelectorAll('tr.fx-row[data-parent="' + row.dataset.id + '"]').forEach((child) => { child.hidden = false; });
                } else {
                    descendants(row).forEach((child) => {
                        child.hidden = true;
                        child.querySelector('.fx-tog')?.setAttribute('aria-expanded', 'false');
                    });
                }
            };

            tbody.addEventListener('click', (event) => {
                if (event.target.closest('a')) return;
                const expandable = event.target.closest('tr[data-expandable]');
                if (expandable) {
                    setExpanded(expandable, expandable.querySelector('.fx-tog').getAttribute('aria-expanded') !== 'true');

                    return;
                }
                const row = event.target.closest('tr[data-href]');
                if (row) window.location.href = row.dataset.href;
            });

            table.querySelectorAll('th .sort').forEach((button) => {
                button.addEventListener('click', () => {
                    const th = button.closest('th');
                    const index = [...th.parentNode.children].indexOf(th);
                    const numeric = th.dataset.type === 'num';
                    const direction = th.getAttribute('aria-sort') === 'descending' ? 'ascending' : (th.getAttribute('aria-sort') === 'ascending' ? 'descending' : (numeric ? 'descending' : 'ascending'));
                    table.querySelectorAll('th[aria-sort]').forEach((other) => other.removeAttribute('aria-sort'));
                    th.setAttribute('aria-sort', direction);
                    const factor = direction === 'ascending' ? 1 : -1;
                    const value = (row) => row.children[index].dataset.sort ?? '';
                    const sorted = dataRows().sort((a, b) => numeric
                        ? (parseFloat(value(a)) - parseFloat(value(b))) * factor
                        : value(a).localeCompare(value(b), 'id', { numeric: true }) * factor);
                    sorted.forEach((row, i) => {
                        const children = descendants(row);
                        row.querySelector('.no').textContent = i + 1;
                        [row, ...children].forEach((item) => tbody.insertBefore(item, document.getElementById('fxNoMatch')));
                    });
                });
            });

            // Pencarian dan pembagian halaman (bila tabel punya data-page-size) bekerja pada baris tingkat teratas.
            const search = document.getElementById('fxSearch');
            const count = document.getElementById('fxCount');
            const total = dataRows().length;
            const pageSize = parseInt(table.dataset.pageSize || '0', 10);
            const pager = document.getElementById('fxPager');
            let page = 1;
            const render = () => {
                const term = (search?.value || '').trim().toLowerCase();
                const matches = dataRows().filter((row) => !term || row.dataset.search.includes(term));
                const pages = pageSize ? Math.max(1, Math.ceil(matches.length / pageSize)) : 1;
                page = Math.min(page, pages);
                const visible = pageSize ? matches.slice((page - 1) * pageSize, page * pageSize) : matches;
                dataRows().forEach((row) => {
                    const show = visible.includes(row);
                    row.hidden = !show;
                    if (!show) setExpanded(row, false);
                });
                document.getElementById('fxNoMatch').hidden = matches.length > 0 || total === 0;
                count.textContent = term ? `${matches.length} dari ${total}` : total.toLocaleString('id-ID');
                if (table.tFoot) table.tFoot.hidden = !!term;
                if (pager) {
                    pager.hidden = matches.length <= pageSize;
                    document.getElementById('fxPageInfo').textContent = `Halaman ${page} dari ${pages}`;
                    document.getElementById('fxPrev').disabled = page <= 1;
                    document.getElementById('fxNext').disabled = page >= pages;
                }
            };
            search?.addEventListener('input', () => { page = 1; render(); });
            document.getElementById('fxPrev')?.addEventListener('click', () => { page -= 1; render(); });
            document.getElementById('fxNext')?.addEventListener('click', () => { page += 1; render(); });
            table.querySelectorAll('th .sort').forEach((button) => button.addEventListener('click', () => { page = 1; render(); }));
            render();
        })();
    </script>
@endpush
