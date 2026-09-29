<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($pageTitle ?? 'Dashboard') }} · SIMKM BPS Kepulauan Seribu</title>
    <script>(function(){try{if(localStorage.getItem('simkm-theme')==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        :root{--bg:#eef4fb;--card:#fff;--line:#dbe6f5;--text:#0f2747;--muted:#5b7290;--brand:#1d4ed8;--brand2:#3b82f6;--brand-dark:#0b2447;
            --soft:#f1f6fd;--soft2:#f4f8fe;--topbar:rgba(255,255,255,.85);--bar-bg:#e4edfa;--row-even:#fafcff;--row-hover:#eff5ff;--shadow:rgba(15,39,71,.04)}
        /* ===== Dark mode ===== */
        [data-theme="dark"]{--bg:#0b1220;--card:#141d30;--line:#27344c;--text:#e4ebf7;--muted:#93a5c2;--brand:#3b82f6;--brand2:#60a5fa;--brand-dark:#cbd9f0;
            --soft:#1b2740;--soft2:#1b2740;--topbar:rgba(17,25,42,.88);--bar-bg:#243049;--row-even:#172136;--row-hover:#1d2944;--shadow:rgba(0,0,0,.35)}
        [data-theme="dark"] .hero{box-shadow:0 8px 24px rgba(0,0,0,.4)}
        [data-theme="dark"] input,[data-theme="dark"] select,[data-theme="dark"] textarea{background:#1b2740;color:var(--text)}
        [data-theme="dark"] .summary-item strong{color:#dbe7ff}
        [data-theme="dark"] .notif-item.unread{background:#16223a}
        [data-theme="dark"] .notif-item.unread .notif-ico{background:#1e3a5f}
        [data-theme="dark"] .u-chip:hover{background:#1f2d49}
        [data-theme="dark"] .notif-item{border-bottom-color:var(--line)}
        [data-theme="dark"] .pill-blue{background:#1e3a5f;color:#93c5fd}
        [data-theme="dark"] .pill-green{background:#0e3a2a;color:#6ee7b7}
        [data-theme="dark"] .pill-amber{background:#3a2c0e;color:#fcd34d}
        [data-theme="dark"] .pill-rose{background:#3a1620;color:#fda4b4}
        .ic-sun{display:none}.ic-moon{display:block}
        [data-theme="dark"] .ic-sun{display:block}[data-theme="dark"] .ic-moon{display:none}
        [data-theme="dark"] .alert-ok{background:#0e3a2a;border-color:#155e44;color:#7fe7be}
        [data-theme="dark"] .alert-err{background:#3a1620;border-color:#7f2336;color:#fda4b4}
        *{box-sizing:border-box}
        html,body{max-width:100%;overflow-x:hidden}
        body{margin:0;font-family:'Segoe UI',system-ui,Arial,sans-serif;background:var(--bg);color:var(--text)}
        a{color:inherit}
        .app{display:block;min-height:100vh;width:100%;max-width:100vw;overflow-x:hidden}

        /* ---------- Sidebar ---------- */
        .sidebar{background:#0b2447;color:#cfe0fa;padding:18px 16px;display:flex;flex-direction:column;position:fixed;inset:0 auto 0 0;width:248px;height:100vh;z-index:30}
        .sidebar-backdrop{display:none}
        .brand{display:flex;align-items:center;gap:11px;padding:6px 6px 16px;margin-bottom:8px;border-bottom:1px solid rgba(255,255,255,.12)}
        .brand .logo{flex:0 0 auto;width:42px;height:42px;border-radius:10px;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,.2)}
        .brand .b-name{font-size:20px;font-weight:800;letter-spacing:.5px;color:#fff;line-height:1}
        .brand .b-sub{font-size:10.5px;color:#9db8e6;margin-top:3px;line-height:1.2}
        .nav-label{font-size:10.5px;text-transform:uppercase;letter-spacing:1px;color:#7d97c9;margin:14px 8px 6px}
        .sidebar nav{flex:1;overflow-y:auto}
        .sidebar a{display:flex;align-items:center;gap:9px;color:#cfe0fa;text-decoration:none;padding:10px 12px;border-radius:9px;margin-bottom:4px;font-size:13.5px;font-weight:500;transition:.15s}
        .sidebar a:hover{background:rgba(255,255,255,.08);color:#fff}
        .sidebar a.active{background:#2563eb;color:#fff;box-shadow:0 4px 12px rgba(37,99,235,.45)}
        .sidebar a::before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor;opacity:.5;flex:0 0 auto}
        .sidebar a.active::before{opacity:1}
        .logout-btn{width:100%;margin-top:10px;background:rgba(255,255,255,.10);color:#fff;border:1px solid rgba(255,255,255,.18);padding:10px;border-radius:9px;cursor:pointer;font-weight:600;font-size:13px}
        .logout-btn:hover{background:rgba(239,68,68,.85);border-color:transparent}

        /* ---------- Main ---------- */
        .main{padding:0;min-width:0;margin-left:248px;width:calc(100% - 248px);max-width:calc(100% - 248px);overflow-x:hidden}
        .topbar{position:sticky;top:0;z-index:20;background:var(--topbar);backdrop-filter:blur(8px);border-bottom:1px solid var(--line);padding:13px 26px;display:flex;justify-content:space-between;align-items:center}
        .topbar-left{display:flex;align-items:center;gap:12px;min-width:0}
        .topbar .t-title{font-weight:800;font-size:17px;color:var(--brand-dark)}
        .topbar .t-sub{font-size:12px;color:var(--muted)}
        .t-actions{display:flex;align-items:center;gap:10px}
        .menu-toggle{display:none;width:40px;height:40px;border-radius:11px;background:var(--soft);border:1px solid var(--line);align-items:center;justify-content:center;color:var(--brand);padding:0}
        .menu-toggle:hover{background:var(--brand);color:#fff;box-shadow:0 4px 12px rgba(29,78,216,.25)}
        .menu-toggle span{display:block;width:18px;height:2px;border-radius:999px;background:currentColor;position:relative}
        .menu-toggle span::before,.menu-toggle span::after{content:"";position:absolute;left:0;width:18px;height:2px;border-radius:999px;background:currentColor}
        .menu-toggle span::before{top:-6px}
        .menu-toggle span::after{top:6px}
        .ic-btn{position:relative;width:40px;height:40px;border-radius:11px;background:var(--soft);border:1px solid var(--line);display:flex;align-items:center;justify-content:center;text-decoration:none;color:var(--brand);transition:.15s}
        .ic-btn:hover{background:var(--brand);color:#fff;box-shadow:0 4px 12px rgba(29,78,216,.3)}
        .ic-btn svg{width:19px;height:19px}
        .ic-badge{position:absolute;top:-4px;right:-4px;min-width:17px;height:17px;padding:0 4px;border-radius:9px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid #fff}
        /* notification dropdown */
        .notif{position:relative}
        .notif-panel{position:absolute;right:0;top:48px;width:340px;background:var(--card);border:1px solid var(--line);border-radius:14px;box-shadow:0 18px 40px rgba(15,39,71,.18);opacity:0;visibility:hidden;transform:translateY(-8px);transition:.16s;z-index:40;overflow:hidden}
        .notif-panel.open{opacity:1;visibility:visible;transform:translateY(0)}
        .notif-head{display:flex;justify-content:space-between;align-items:center;padding:13px 15px;font-weight:700;color:var(--brand-dark);background:var(--soft);border-bottom:1px solid var(--line)}
        .notif-list{max-height:360px;overflow-y:auto}
        .notif-item{display:flex;gap:10px;padding:12px 15px;text-decoration:none;color:var(--text);border-bottom:1px solid #eef2f9;transition:.12s}
        .notif-item:hover{background:var(--row-hover)}
        .notif-item.unread{background:#eff5ff}
        .notif-item.unread .notif-ico{background:#dbeafe}
        .notif-ico{flex:0 0 auto;width:34px;height:34px;border-radius:10px;background:var(--soft);display:flex;align-items:center;justify-content:center;font-size:16px}
        .notif-body{display:flex;flex-direction:column;gap:2px;font-size:13px;line-height:1.3}
        .notif-body b{font-size:13px;color:var(--brand-dark)}
        .notif-body em{font-style:normal;font-size:11px;color:var(--muted);margin-top:2px}
        .notif-empty{padding:26px 15px;text-align:center;color:var(--muted);font-size:13px}
        /* history card (mitra detail) */
        .history-card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px;box-shadow:0 2px 10px rgba(15,39,71,.05);transition:.2s;border-left:4px solid var(--brand2)}
        .history-card:hover{transform:translateY(-3px);box-shadow:0 12px 26px rgba(15,39,71,.14)}
        .u-chip{display:flex;align-items:center;gap:9px;padding:7px 12px;border-radius:11px;background:var(--soft);border:1px solid var(--line);text-decoration:none}
        .u-chip:hover{background:#e3eefc}
        .u-meta{line-height:1.15}.u-name{font-size:13px;font-weight:700;color:var(--text)}.u-role{font-size:11px;color:var(--muted)}
        .content{padding:22px 26px;min-width:0;width:100%;max-width:100%;overflow-x:hidden}

        /* ---------- Cards / grids ---------- */
        .card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:16px;margin-bottom:14px;box-shadow:0 2px 8px var(--shadow);min-width:0;max-width:100%}
        .grid{display:grid;gap:14px;margin-bottom:14px;min-width:0}
        .grid > *{min-width:0}
        .g4{grid-template-columns:repeat(auto-fit,minmax(190px,1fr))}
        .g3{grid-template-columns:repeat(auto-fit,minmax(230px,1fr))}
        .g2{grid-template-columns:repeat(auto-fit,minmax(300px,1fr))}
        /* Kartu dengan lebar tetap (tidak melar mengisi baris) agar ukurannya konsisten
           berapa pun jumlah kartu di tiap baris, dipakai untuk daftar kartu survei. */
        .g-fixed{grid-template-columns:repeat(auto-fit,minmax(230px,260px))}
        .metric{font-size:28px;font-weight:800;line-height:1.1}.muted{color:var(--muted);font-size:13px}

        /* greeting banner */
        .hero{background:#1d4ed8;color:#fff;border-radius:16px;padding:22px 24px;margin-bottom:16px;position:relative;overflow:hidden;box-shadow:0 8px 24px rgba(13,40,90,.25)}
        .hero::after{content:"";position:absolute;right:-40px;top:-40px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.08)}
        .hero::before{content:"";position:absolute;right:60px;bottom:-60px;width:130px;height:130px;border-radius:50%;background:rgba(255,255,255,.06)}
        .hero h2{margin:0 0 6px;font-size:21px;font-weight:800;position:relative}
        .hero p{margin:0;opacity:.9;font-size:13.5px;max-width:680px;position:relative}
        .hero .h-chips{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;position:relative}
        .hero .h-chip{background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.22);padding:6px 12px;border-radius:10px;font-size:12.5px;font-weight:600}
        .hero .h-chip b{font-size:15px}

        /* colorful stat cards */
        .stat{position:relative;overflow:hidden;border-radius:14px;padding:16px 18px;color:#fff;box-shadow:0 6px 18px rgba(15,39,71,.12)}
        .stat .s-label{font-size:13px;opacity:.92;font-weight:600;letter-spacing:.2px}
        .stat .s-val{font-size:30px;font-weight:800;margin-top:6px;line-height:1}
        .stat .s-ico{position:absolute;right:14px;top:14px;font-size:30px;opacity:.28}
        .stat .s-foot{font-size:12px;opacity:.9;margin-top:8px}
        .st-blue{background:#1d4ed8}
        .st-sky{background:#0284c7}
        .st-indigo{background:#4338ca}
        .st-cyan{background:#0e7490}
        .st-navy{background:#0b2447}
        .st-amber{background:#b45309}
        .st-rose{background:#be123c}
        .st-green{background:#047857}

        .card-h{display:flex;align-items:center;gap:8px;font-weight:700;margin-bottom:14px;font-size:15px;color:var(--brand-dark)}
        .card-h .dot{width:10px;height:10px;border-radius:50%;background:var(--brand2)}
        .chart-wrap{position:relative;width:100%}

        /* ---------- Entity / survey cards with hover overlay ---------- */
        .entity-card{position:relative;overflow:hidden;background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px;box-shadow:0 2px 10px rgba(15,39,71,.05);transition:.2s;border-top:4px solid var(--brand2);display:flex;flex-direction:column;min-height:262px}
        .entity-card:hover{transform:translateY(-4px);box-shadow:0 14px 30px rgba(15,39,71,.16)}
        .entity-card:nth-child(4n+1){border-top-color:#1d4ed8}
        .entity-card:nth-child(4n+2){border-top-color:#0ea5e9}
        .entity-card:nth-child(4n+3){border-top-color:#10b981}
        .entity-card:nth-child(4n+4){border-top-color:#f59e0b}
        .entity-title{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.3;min-height:2.6em}
        .entity-team{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .entity-footer{margin-top:auto}
        .summary-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:14px}
        .summary-item{background:var(--soft2);border:1px solid var(--line);border-radius:10px;padding:9px 10px;text-align:center}
        .summary-item .muted{font-size:11px}
        .summary-item strong{font-size:17px;color:var(--brand-dark)}
        .entity-overlay{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;
            background:rgba(11,36,71,.95);
            opacity:0;visibility:hidden;transition:.2s;backdrop-filter:blur(2px)}
        .entity-card:hover .entity-overlay{opacity:1;visibility:visible}
        .entity-overlay .btn,.entity-overlay button{min-width:170px;text-align:center;padding:11px 16px;border-radius:10px;font-weight:600;font-size:14px;box-shadow:0 6px 16px rgba(0,0,0,.25)}
        .entity-overlay form{margin:0}
        .btn-danger{background:#be123c!important;color:#fff;border:0;cursor:pointer}

        /* progress bar */
        .bar{height:9px;border-radius:6px;background:var(--bar-bg);overflow:hidden}
        .bar > i{display:block;height:100%;border-radius:6px;background:#1d4ed8}
        .pill{display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600}
        .pill-blue{background:#dbeafe;color:#1d4ed8}.pill-green{background:#d1fae5;color:#047857}.pill-amber{background:#fef3c7;color:#b45309}.pill-rose{background:#ffe4e6;color:#be123c}

        table{width:100%;border-collapse:collapse}
        th{background:var(--soft);color:#1d4ed8;font-size:12px;text-transform:uppercase;letter-spacing:.4px}
        th,td{border-bottom:1px solid var(--line);padding:9px 10px;text-align:left;font-size:14px}
        tbody tr:hover{background:var(--row-hover)}
        .rich-table th{padding:11px 14px}
        .rich-table td{padding:12px 14px;vertical-align:middle}
        .rich-table tbody tr:nth-child(even){background:var(--row-even)}
        .rich-table tbody tr:hover{background:var(--row-hover)}
        .mini-avatar{flex:0 0 auto;width:34px;height:34px;border-radius:10px;background:#1d4ed8;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12.5px}
        input,select,button,textarea{padding:9px 12px;border:1px solid var(--line);border-radius:10px;font:inherit;background:#fff}
        button,.btn{background:var(--brand);color:#fff;border:0;cursor:pointer;text-decoration:none;display:inline-block;border-radius:10px;padding:9px 14px;font-weight:600;transition:.15s}
        button:hover,.btn:hover{filter:brightness(1.07);box-shadow:0 4px 12px rgba(29,78,216,.25)}
        .btn-grey{background:#64748b!important}
        .btn-green{background:#047857!important}
        form.inline{display:inline}
        .alert{border-radius:10px;padding:12px 14px;margin-bottom:14px;font-size:14px}
        .alert-ok{background:#d1fae5;border:1px solid #6ee7b7;color:#065f46}
        .alert-err{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
        .flash-toast{position:fixed;right:22px;top:82px;z-index:90;min-width:min(360px,calc(100vw - 32px));border-radius:14px;padding:13px 15px;background:#ecfdf5;border:1px solid #86efac;color:#065f46;box-shadow:0 16px 36px rgba(15,39,71,.18);font-weight:700;display:flex;gap:10px;align-items:flex-start;animation:toastIn .18s ease-out}
        .flash-toast.err{background:#fff1f2;border-color:#fda4af;color:#9f1239}
        .flash-toast button{margin-left:auto;background:transparent!important;color:inherit;border:0;padding:0;box-shadow:none;font-size:18px;line-height:1;cursor:pointer}
        @keyframes toastIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}

        /* ---------- Pagination (dipakai di semua tabel berhalaman) ---------- */
        .simkm-pagination{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between}
        .simkm-pagination-info{color:var(--muted);font-size:12.5px}
        .simkm-pagination-list{display:flex;flex-wrap:wrap;gap:6px;list-style:none;margin:0;padding:0}
        .simkm-page-link{display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;padding:0 10px;border-radius:9px;border:1px solid var(--line);background:var(--card);color:var(--brand-dark);font-size:13px;font-weight:600;text-decoration:none;transition:.15s;cursor:pointer}
        a.simkm-page-link:hover{background:var(--row-hover);border-color:var(--brand2)}
        .simkm-page-link.active{background:var(--brand);border-color:var(--brand);color:#fff}
        .simkm-page-link.disabled{color:var(--muted);cursor:not-allowed;opacity:.55}
        .simkm-page-link.dots{border-color:transparent;background:transparent;cursor:default}
        @media(max-width:560px){.simkm-pagination{flex-direction:column;align-items:flex-start}}

        @media(max-width:880px){
            body.sidebar-open{overflow:hidden}
            .app{display:block;min-height:100vh}
            .main{margin-left:0;width:100%;max-width:100%}
            .sidebar{position:fixed;inset:0 auto 0 0;width:min(82vw,280px);height:100dvh;z-index:60;transform:translateX(-105%);transition:transform .2s ease-out;box-shadow:18px 0 42px rgba(15,39,71,.34)}
            body.sidebar-open .sidebar{transform:translateX(0)}
            .sidebar-backdrop{display:block;position:fixed;inset:0;background:rgba(15,39,71,.46);border:0;border-radius:0;padding:0;opacity:0;visibility:hidden;transition:.18s ease-out;z-index:50}
            .sidebar-backdrop:hover{filter:none;box-shadow:none}
            body.sidebar-open .sidebar-backdrop{opacity:1;visibility:visible}
            .menu-toggle{display:inline-flex;flex:0 0 auto}
            .topbar{padding:12px 16px;gap:12px}
            .topbar-left > div{min-width:0}
            .topbar .t-title{font-size:16px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:38vw}
            .topbar .t-sub{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:42vw}
            .content{padding:16px}
            .summary-grid{grid-template-columns:repeat(auto-fit,minmax(150px,1fr))!important}
        }
        @media(max-width:640px){
            .topbar{align-items:flex-start}
            .t-actions{gap:7px}
            .ic-btn{width:38px;height:38px}
            .u-chip{padding:4px;border-radius:10px}
            .u-chip .u-meta{display:none}
            .notif-panel{position:fixed;left:14px;right:14px;top:66px;width:auto}
            .flash-toast{left:14px;right:14px;top:72px;min-width:0}
            .summary-grid{grid-template-columns:1fr!important}
        }
    </style>
    @stack('head')
</head>
<body>
@php
    $role = auth()->user()?->getRoleNames()->first();
    $prefix = match ($role) { 'admin' => 'admin', 'pegawai_bps' => 'pegawai', 'mitra' => 'mitra', default => '' };
    $roleLabel = ['admin' => 'Administrator', 'pegawai_bps' => 'Pegawai BPS', 'mitra' => 'Mitra BPS'][$role] ?? ($role ?? 'Pengguna');
@endphp
<div class="app">
    @if (session('status'))
        <div class="flash-toast" id="flashToast">
            <span>{{ session('status') }}</span>
            <button type="button" aria-label="Tutup notifikasi" onclick="document.getElementById('flashToast')?.remove()">×</button>
        </div>
    @endif
    @if ($errors->any())
        <div class="flash-toast err" id="flashToast">
            <span>{{ $errors->first() }}</span>
            <button type="button" aria-label="Tutup notifikasi" onclick="document.getElementById('flashToast')?.remove()">×</button>
        </div>
    @endif
    <button type="button" class="sidebar-backdrop" aria-label="Tutup menu" onclick="closeSidebar()"></button>
    <aside class="sidebar" id="appSidebar">
        <div class="brand">
            <span class="logo">
                {{-- Logo Badan Pusat Statistik (BPS) --}}
                <svg width="34" height="34" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Logo BPS">
                    {{-- Elips (cincin) --}}
                    <g transform="rotate(-18 50 50)">
                        <path d="M50 18 C76 18 94 32 94 50" stroke="#ef7f1a" stroke-width="6" stroke-linecap="round" fill="none"/>
                        <path d="M50 82 C24 82 6 68 6 50" stroke="#2ca9e1" stroke-width="6" stroke-linecap="round" fill="none"/>
                    </g>
                    {{-- Tiga blok stilasi --}}
                    <rect x="28" y="24" width="20" height="26" rx="4" transform="skewX(-12)" fill="#2ca9e1"/>
                    <rect x="52" y="20" width="20" height="26" rx="4" transform="skewX(-12)" fill="#ef7f1a"/>
                    <rect x="40" y="50" width="20" height="26" rx="4" transform="skewX(-12)" fill="#6cbe45"/>
                </svg>
            </span>
            <div>
                <div class="b-name">SIMKM</div>
                <div class="b-sub">BPS Kab. Kepulauan Seribu</div>
            </div>
        </div>

        <div class="nav-label">{{ $panelTitle ?? 'Panel' }}</div>
        <nav>
            @yield('menu')
        </nav>

        <form method="POST" action="/logout">
            @csrf
            <button type="submit" class="logout-btn">⎋ Keluar</button>
        </form>
    </aside>

    <main class="main">
        <div class="topbar">
            <div class="topbar-left">
                <button type="button" class="menu-toggle" aria-label="Buka menu" aria-controls="appSidebar" aria-expanded="false" onclick="toggleSidebar()" id="sidebarToggle">
                    <span aria-hidden="true"></span>
                </button>
                <div>
                    <div class="t-title">{{ $pageTitle ?? 'Dashboard' }}</div>
                    <div class="t-sub">Sistem Monitoring Kinerja Mitra</div>
                </div>
            </div>
            <div class="t-actions">
                <button type="button" class="ic-btn" id="themeToggle" title="Mode Gelap/Terang" onclick="toggleTheme()">
                    <svg class="ic-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    <svg class="ic-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>
                @php
                    $notifs = in_array($prefix, ['pegawai', 'mitra'], true) ? auth()->user()->notifications()->latest()->limit(8)->get() : collect();
                    $unread = in_array($prefix, ['pegawai', 'mitra'], true) ? auth()->user()->unreadNotifications()->count() : 0;
                @endphp
                <div class="notif" id="notifWrap">
                    <button type="button" class="ic-btn" title="Notifikasi" onclick="toggleNotif(event)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        @if ($unread > 0)<span class="ic-badge">{{ $unread > 9 ? '9+' : $unread }}</span>@endif
                    </button>
                    <div class="notif-panel" id="notifPanel">
                        <div class="notif-head">
                            <span>Notifikasi</span>
                            @if ($unread > 0)<span class="pill pill-blue">{{ $unread }} baru</span>@endif
                        </div>
                        <div class="notif-list">
                            @forelse ($notifs as $notif)
                                <a href="{{ $notif->data['url'] ?? '#' }}" class="notif-item {{ $notif->read_at ? '' : 'unread' }}">
                                    <span class="notif-ico">🔔</span>
                                    <span class="notif-body">
                                        <b>{{ $notif->data['title'] ?? 'Notifikasi' }}</b>
                                        <span>{{ $notif->data['message'] ?? '' }}</span>
                                        <em>{{ $notif->created_at->diffForHumans() }}</em>
                                    </span>
                                </a>
                            @empty
                                <div class="notif-empty">Belum ada notifikasi.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <a href="/{{ $prefix }}/profile" class="u-chip" title="Profil">
                    <span class="u-meta">
                        <span class="u-name">{{ auth()->user()->name }}</span><br>
                        <span class="u-role">{{ $roleLabel }}</span>
                    </span>
                </a>
            </div>
        </div>

        <div class="content">
            @if (session('status'))<div class="alert alert-ok">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="alert alert-err">{{ $errors->first() }}</div>@endif
            @yield('content')
        </div>
    </main>
</div>
<script>
function toggleTheme(){
    const el = document.documentElement;
    const dark = el.getAttribute('data-theme') === 'dark';
    if (dark){ el.removeAttribute('data-theme'); try{localStorage.setItem('simkm-theme','light');}catch(e){} }
    else { el.setAttribute('data-theme','dark'); try{localStorage.setItem('simkm-theme','dark');}catch(e){} }
}
function toggleNotif(e){
    e.stopPropagation();
    document.getElementById('notifPanel')?.classList.toggle('open');
}
function setSidebar(open){
    document.body.classList.toggle('sidebar-open', open);
    document.getElementById('sidebarToggle')?.setAttribute('aria-expanded', open ? 'true' : 'false');
}
function toggleSidebar(){
    setSidebar(!document.body.classList.contains('sidebar-open'));
}
function closeSidebar(){
    setSidebar(false);
}
document.addEventListener('click', function(ev){
    const wrap = document.getElementById('notifWrap');
    const panel = document.getElementById('notifPanel');
    if (wrap && panel && !wrap.contains(ev.target)) panel.classList.remove('open');
});
document.addEventListener('keydown', function(ev){
    if (ev.key === 'Escape') closeSidebar();
});
document.querySelectorAll('.sidebar a').forEach(function(link){
    link.addEventListener('click', function(){ closeSidebar(); });
});
setTimeout(function(){
    document.getElementById('flashToast')?.remove();
}, 4500);
</script>
@stack('scripts')
</body>
</html>
