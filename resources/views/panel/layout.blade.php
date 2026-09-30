<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($pageTitle ?? 'Dashboard') }} · SIMPROCA BPS Kepulauan Seribu</title>
    <script>(function(){try{if(localStorage.getItem('simkm-theme')==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();</script>
    <style>
        :root{--bg:#eef4fb;--card:#fff;--line:#dbe6f5;--text:#0f2747;--muted:#5b7290;--brand:#1d4ed8;--brand2:#3b82f6;--brand-dark:#0b2447;
            --soft:#f1f6fd;--soft2:#f4f8fe;--topbar:rgba(255,255,255,.85);--bar-bg:#e4edfa;--row-even:#fafcff;--row-hover:#eff5ff;--shadow:rgba(15,39,71,.04)}
        /* ===== Dark mode ===== */
        [data-theme="dark"]{--bg:#0b1220;--card:#141d30;--line:#27344c;--text:#e4ebf7;--muted:#93a5c2;--brand:#3b82f6;--brand2:#60a5fa;--brand-dark:#cbd9f0;
            --soft:#1b2740;--soft2:#1b2740;--topbar:rgba(17,25,42,.88);--bar-bg:#243049;--row-even:#172136;--row-hover:#1d2944;--shadow:rgba(0,0,0,.35)}
        [data-theme="dark"] input,[data-theme="dark"] select,[data-theme="dark"] textarea{background:#1b2740;color:var(--text)}
        [data-theme="dark"] .notif-item.unread{background:#16223a}
        [data-theme="dark"] .notif-item.unread .notif-ico{background:#1e3a5f}
        [data-theme="dark"] .u-chip:hover{background:#1f2d49}
        [data-theme="dark"] .notif-item{border-bottom-color:var(--line)}
        [data-theme="dark"] .pill-blue{background:#1e3a5f;color:#93c5fd}
        .ic-sun{display:none}.ic-moon{display:block}
        [data-theme="dark"] .ic-sun{display:block}[data-theme="dark"] .ic-moon{display:none}
        [data-theme="dark"] .alert-err{background:#3a1620;border-color:#7f2336;color:#fda4b4}
        *{box-sizing:border-box}
        html,body{max-width:100%;overflow-x:hidden}
        body{margin:0;font-size:14px;line-height:1.45;font-family:'Segoe UI',system-ui,Arial,sans-serif;background:var(--bg);color:var(--text)}
        a{color:inherit}
        .app{display:block;min-height:100vh;width:100%;max-width:100vw;overflow-x:hidden}

        /* ---------- Sidebar ---------- */
        .sidebar{background:#0b2447;color:#cfe0fa;padding:18px 16px;display:flex;flex-direction:column;position:fixed;inset:0 auto 0 0;width:248px;height:100vh;z-index:30}
        .sidebar-backdrop{display:none}
        .brand{display:flex;align-items:center;gap:11px;padding:6px 6px 16px;margin-bottom:8px;border-bottom:1px solid rgba(255,255,255,.12)}
        .brand .logo{flex:0 0 auto;width:42px;height:42px;border-radius:10px;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,.2)}
        .brand .b-name{font-size:17px;font-weight:800;letter-spacing:.5px;color:#fff;line-height:1}
        .brand .b-sub{font-size:10.5px;color:#9db8e6;margin-top:3px;line-height:1.2}
        .nav-label{font-size:10.5px;text-transform:uppercase;letter-spacing:1px;color:#7d97c9;margin:14px 8px 6px}
        .sidebar nav{flex:1;overflow-y:auto}
        .sidebar a{display:flex;align-items:center;gap:9px;color:#cfe0fa;text-decoration:none;padding:10px 12px;border-radius:9px;margin-bottom:4px;font-size:13px;font-weight:500;transition:.15s}
        .sidebar a:hover{background:rgba(255,255,255,.08);color:#fff}
        .sidebar a.active{background:#2563eb;color:#fff;box-shadow:0 4px 12px rgba(37,99,235,.45)}
        .sidebar a::before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor;opacity:.5;flex:0 0 auto}
        .sidebar a.active::before{opacity:1}
        .sidebar a:has(> svg)::before{display:none}
        .sidebar a > svg{width:18px;height:18px;flex:0 0 auto;opacity:.8}
        .sidebar a:hover > svg,.sidebar a.active > svg{opacity:1}
        .sidebar a:focus-visible{outline:2px solid #93c5fd;outline-offset:-2px}
        .logout-btn{width:100%;margin-top:10px;background:rgba(255,255,255,.10);color:#fff;border:1px solid rgba(255,255,255,.18);padding:10px;border-radius:9px;cursor:pointer;font-weight:600;font-size:12.5px}
        .logout-btn:hover{background:rgba(239,68,68,.85);border-color:transparent}

        /* ---------- Main ---------- */
        .main{padding:0;min-width:0;margin-left:248px;width:calc(100% - 248px);max-width:calc(100% - 248px);overflow-x:hidden}
        .topbar{position:sticky;top:0;z-index:20;background:var(--topbar);backdrop-filter:blur(8px);border-bottom:1px solid var(--line);padding:13px 26px;display:flex;justify-content:space-between;align-items:center}
        .topbar-left{display:flex;align-items:center;gap:12px;min-width:0}
        .topbar .t-title{font-weight:800;font-size:15px;color:var(--brand-dark)}
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
        .notif-head-r{display:flex;align-items:center;gap:8px}
        .notif-head-r form{margin:0}
        .notif-clear{background:transparent;color:var(--muted);border:0;padding:3px 6px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer}
        .notif-clear:hover{background:var(--row-hover);color:#dc2626;filter:none;box-shadow:none}
        .notif-list{max-height:360px;overflow-y:auto}
        .notif-item{display:flex;gap:10px;padding:12px 15px;text-decoration:none;color:var(--text);border-bottom:1px solid #eef2f9;transition:.12s}
        .notif-item:hover{background:var(--row-hover)}
        .notif-item.unread{background:#eff5ff}
        .notif-item.unread .notif-ico{background:#dbeafe}
        .notif-ico{flex:0 0 auto;width:34px;height:34px;border-radius:10px;background:var(--soft);display:flex;align-items:center;justify-content:center;font-size:14.5px}
        .notif-body{display:flex;flex-direction:column;gap:2px;font-size:12.5px;line-height:1.3}
        .notif-body b{font-size:12.5px;color:var(--brand-dark)}
        .notif-body em{font-style:normal;font-size:11px;color:var(--muted);margin-top:2px}
        .notif-empty{padding:26px 15px;text-align:center;color:var(--muted);font-size:12.5px}
        .u-chip{display:flex;align-items:center;gap:9px;padding:5px 10px 5px 5px;border-radius:11px;background:var(--soft);border:1px solid var(--line);text-decoration:none;color:var(--text);cursor:pointer;text-align:left;font-weight:400}
        .u-chip:hover{background:#e3eefc;filter:none;box-shadow:none}
        .u-chip .avatar{width:30px;height:30px;border-radius:9px;background:var(--brand);color:#fff;font-size:11.5px}
        .u-caret{width:14px;height:14px;color:var(--muted);transition:transform .16s}
        .u-chip[aria-expanded="true"] .u-caret{transform:rotate(180deg)}
        /* profile dropdown */
        .u-menu{position:relative}
        .u-panel{position:absolute;right:0;top:48px;min-width:190px;padding:6px;background:var(--card);border:1px solid var(--line);border-radius:12px;box-shadow:0 18px 40px rgba(15,39,71,.18);opacity:0;visibility:hidden;transform:translateY(-8px);transition:.16s;z-index:40}
        .u-panel.open{opacity:1;visibility:visible;transform:translateY(0)}
        .u-panel form{margin:0}
        .u-item{display:flex;align-items:center;gap:10px;width:100%;padding:9px 10px;border:0;border-radius:8px;background:transparent;color:var(--text);font-size:13px;font-weight:600;text-decoration:none;text-align:left;cursor:pointer}
        .u-item:hover{background:var(--row-hover);filter:none;box-shadow:none}
        .u-item svg{width:16px;height:16px;flex:0 0 auto;color:var(--muted)}
        .u-item.danger,.u-item.danger svg{color:#dc2626}
        .u-item.danger:hover{background:rgba(239,68,68,.12)}
        .u-sep{height:1px;margin:5px 2px;background:var(--line)}
        .u-meta{line-height:1.15}.u-name{font-size:12.5px;font-weight:700;color:var(--text)}.u-role{font-size:11px;color:var(--muted)}
        .content{padding:22px 26px;min-width:0;width:100%;max-width:100%;overflow-x:hidden}

        /* Animasi putar kecil di dalam tombol yang sedang memproses. */
        .b-spin{flex:none;width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;animation:bSpin .7s linear infinite}
        .b[aria-busy]{cursor:progress;opacity:.9}
        @keyframes bSpin{to{transform:rotate(360deg)}}
        @media (prefers-reduced-motion:reduce){.b-spin{animation-duration:2s}}

        /* Isian tanggal berformat dd/mm/yyyy (lihat skrip di bawah); kalender bawaan browser tetap dipakai lewat tombolnya. */
        .date-dmy{position:relative;display:block;min-width:0}
        .date-dmy > input[type="text"]{width:100%;padding-right:40px}
        .date-dmy > input[type="date"]{position:absolute;right:0;bottom:0;width:1px;height:1px;padding:0;border:0;opacity:0;pointer-events:none}
        .date-dmy-btn{all:unset;box-sizing:border-box;position:absolute;right:4px;top:50%;transform:translateY(-50%);width:32px;height:32px;border-radius:8px;display:grid;place-items:center;color:var(--muted);cursor:pointer}
        .date-dmy-btn:hover{background:var(--soft);color:var(--text)}
        .date-dmy-btn:focus-visible{outline:2px solid var(--brand);outline-offset:1px}
        .date-dmy-btn svg{width:16px;height:16px}

        /* ---------- Cards / grids ---------- */
        .grid{display:grid;gap:14px;margin-bottom:14px;min-width:0}
        .grid > *{min-width:0}

        /* progress bar */
        .pill{display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600}
        .pill-blue{background:#dbeafe;color:#1d4ed8}

        table{width:100%;border-collapse:collapse}
        th{background:var(--soft);color:#1d4ed8;font-size:12px;text-transform:uppercase;letter-spacing:.4px}
        th,td{border-bottom:1px solid var(--line);padding:9px 10px;text-align:left;font-size:13.5px}
        tbody tr:hover{background:var(--row-hover)}
        input,select,button,textarea{padding:9px 12px;border:1px solid var(--line);border-radius:10px;font:inherit;background:#fff}
        button{background:var(--brand);color:#fff;border:0;cursor:pointer;text-decoration:none;display:inline-block;border-radius:10px;padding:9px 14px;font-weight:600;transition:.15s}
        button:hover{filter:brightness(1.07);box-shadow:0 4px 12px rgba(29,78,216,.25)}
        form.inline{display:inline}
        .alert{border-radius:10px;padding:12px 14px;margin-bottom:14px;font-size:13.5px}
        .alert-err{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
        .flash-toast{position:fixed;right:22px;top:82px;z-index:90;min-width:min(360px,calc(100vw - 32px));border-radius:14px;padding:13px 15px;background:#ecfdf5;border:1px solid #86efac;color:#065f46;box-shadow:0 16px 36px rgba(15,39,71,.18);font-weight:700;display:flex;gap:10px;align-items:flex-start;animation:toastIn .18s ease-out}
        .flash-toast.err{background:#fff1f2;border-color:#fda4af;color:#9f1239}
        .flash-toast button{margin-left:auto;background:transparent!important;color:inherit;border:0;padding:0;box-shadow:none;font-size:16px;line-height:1;cursor:pointer}
        @keyframes toastIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}

        /* ---------- Pagination (dipakai di semua tabel berhalaman) ---------- */
        .simkm-pagination{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between}
        .simkm-pagination-info{color:var(--muted);font-size:12px}
        .simkm-pagination-list{display:flex;flex-wrap:wrap;gap:6px;list-style:none;margin:0;padding:0}
        .simkm-page-link{display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;padding:0 10px;border-radius:9px;border:1px solid var(--line);background:var(--card);color:var(--brand-dark);font-size:12.5px;font-weight:600;text-decoration:none;transition:.15s;cursor:pointer}
        a.simkm-page-link:hover{background:var(--row-hover);border-color:var(--brand2)}
        .simkm-page-link.active{background:var(--brand);border-color:var(--brand);color:#fff}
        .simkm-page-link.disabled{color:var(--muted);cursor:not-allowed;opacity:.55}
        .simkm-page-link.dots{border-color:transparent;background:transparent;cursor:default}
        @media(max-width:560px){.simkm-pagination{flex-direction:column;align-items:flex-start}}

        @media(max-width:880px){
            body.sidebar-open{overflow:hidden}
            .app{display:block;min-height:100vh}
            .main{margin-left:0;width:100%;max-width:100%}
            .sidebar{position:fixed;inset:0 auto 0 0;width:min(82vw,280px);height:100dvh;z-index:60;transform:translateX(-105%);transition:transform .2s ease-out,box-shadow .2s ease-out;box-shadow:none}
            body.sidebar-open .sidebar{transform:translateX(0);box-shadow:18px 0 42px rgba(15,39,71,.34)}
            .sidebar-backdrop{display:block;position:fixed;inset:0;background:rgba(15,39,71,.46);border:0;border-radius:0;padding:0;opacity:0;visibility:hidden;transition:.18s ease-out;z-index:50}
            .sidebar-backdrop:hover{filter:none;box-shadow:none}
            body.sidebar-open .sidebar-backdrop{opacity:1;visibility:visible}
            .menu-toggle{display:inline-flex;flex:0 0 auto}
            .topbar{padding:12px 16px;gap:12px}
            .topbar-left > div{min-width:0}
            .topbar .t-title{font-size:14.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:38vw}
            .topbar .t-sub{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:42vw}
            .content{padding:16px}
        }
        @media(max-width:640px){
            .topbar{align-items:flex-start}
            .t-actions{gap:7px}
            .ic-btn{width:38px;height:38px}
            .u-chip{padding:4px;border-radius:10px}
            .u-chip .u-meta,.u-chip .u-caret{display:none}
            .notif-panel{position:fixed;left:14px;right:14px;top:66px;width:auto}
            .flash-toast{left:14px;right:14px;top:72px;min-width:0}
        }
    </style>
    @include('panel.partials.ui')
    @stack('head')
</head>
<body>
@php
    $role = auth()->user()?->getRoleNames()->first();
    $prefix = match ($role) { 'admin' => 'admin', 'pegawai_bps' => 'pegawai', 'mitra' => 'mitra', default => '' };
    $roleLabel = ['admin' => 'Administrator', 'pegawai_bps' => 'Pegawai BPS', 'mitra' => 'Mitra BPS'][$role] ?? ($role ?? 'Pengguna');
    $userInitials = collect(explode(' ', trim((string) auth()->user()?->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: 'U';
@endphp
<div class="app">
    @if (session('status'))
        <div class="flash-toast" id="flashToast" role="status">
            <span>{{ session('status') }}</span>
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
                <div class="b-name">SIMPROCA</div>
                <div class="b-sub">BPS Kab. Kepulauan Seribu</div>
            </div>
        </div>

        <div class="nav-label">Menu</div>
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
                    <div class="t-sub">Sistem Monitoring Progress Pencacahan</div>
                </div>
            </div>
            <div class="t-actions">
                <button type="button" class="ic-btn" id="themeToggle" title="Mode Gelap/Terang" onclick="toggleTheme()">
                    <svg class="ic-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    <svg class="ic-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>
                @php
                    $notifs = auth()->user()->notifications()->latest()->limit(8)->get();
                    $unread = auth()->user()->unreadNotifications()->count();
                    $notifUrl = function ($notif) use ($prefix): string {
                        $data = $notif->data;
                        if (in_array($prefix, ['admin', 'pegawai'], true)) {
                            return isset($data['entry_id']) ? '/'.$prefix.'/entri-papi/'.$data['entry_id'] : '#';
                        }

                        return $prefix === 'mitra' ? (isset($data['survey_id']) ? '/mitra/surveys/'.$data['survey_id'] : '/mitra/dashboard') : '#';
                    };
                @endphp
                <div class="notif" id="notifWrap">
                    <button type="button" class="ic-btn" title="Notifikasi" onclick="toggleNotif(event)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        @if ($unread > 0)<span class="ic-badge" id="notifBadge">{{ $unread > 9 ? '9+' : $unread }}</span>@endif
                    </button>
                    <div class="notif-panel" id="notifPanel">
                        <div class="notif-head">
                            <span>Notifikasi</span>
                            <span class="notif-head-r">
                                @if ($unread > 0)<span class="pill pill-blue">{{ $unread }} baru</span>@endif
                                @if ($notifs->isNotEmpty())
                                    <form method="POST" action="/notifications">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="notif-clear">Bersihkan</button>
                                    </form>
                                @endif
                            </span>
                        </div>
                        <div class="notif-list">
                            @forelse ($notifs as $notif)
                                <a href="{{ $notifUrl($notif) }}" class="notif-item {{ $notif->read_at ? '' : 'unread' }}">
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
                <div class="u-menu" id="userWrap">
                    <button type="button" class="u-chip" id="userToggle" title="Profil" aria-haspopup="true" aria-expanded="false" aria-controls="userPanel" onclick="toggleUserMenu(event)">
                        <span class="avatar" aria-hidden="true">{{ $userInitials }}</span>
                        <span class="u-meta">
                            <span class="u-name">{{ auth()->user()->name }}</span><br>
                            <span class="u-role">{{ $roleLabel }}</span>
                        </span>
                        <svg class="u-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="u-panel" id="userPanel" role="menu">
                        <a href="/{{ $prefix }}/profile" class="u-item" role="menuitem">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Edit Profil
                        </a>
                        <div class="u-sep" role="separator"></div>
                        <form method="POST" action="/logout">
                            @csrf
                            <button type="submit" class="u-item danger" role="menuitem">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            @if ($errors->any())<div class="alert alert-err" role="alert">{{ $errors->first() }}</div>@endif
            @yield('content')
        </div>
    </main>
</div>
{{-- Dialog konfirmasi bersama untuk form/tombol ber-atribut data-confirm. --}}
<dialog class="dlg" id="confirmDialog" aria-labelledby="confirmTitle" style="max-width:min(440px,calc(100vw - 32px))">
    <div class="dlg-h"><h2 id="confirmTitle">Konfirmasi</h2></div>
    <div class="dlg-b"><p id="confirmMessage" style="margin:0;font-size:13.5px;line-height:1.55;color:var(--text)"></p></div>
    <div class="dlg-f">
        <div class="right">
            <button type="button" class="b b-soft" id="confirmCancel">Batal</button>
            <button type="button" class="b b-primary" id="confirmOk">Ya, lanjutkan</button>
        </div>
    </div>
</dialog>
<script>
// Konfirmasi ditampilkan sebagai dialog di dalam halaman, bukan confirm() bawaan browser,
// karena sebagian browser/pratinjau memblokir confirm() sehingga tombolnya terasa tidak berfungsi.
(function(){
    const dialog = document.getElementById('confirmDialog');
    const okButton = document.getElementById('confirmOk');
    let pending = null;
    document.addEventListener('submit', function(event){
        const form = event.target;
        const submitter = event.submitter;
        const message = (submitter && submitter.dataset.confirm) || form.dataset.confirm;
        if (!message) return;
        if (form.dataset.confirmed === '1') { delete form.dataset.confirmed; return; }
        event.preventDefault();
        event.stopImmediatePropagation();
        pending = {form: form, submitter: submitter};
        document.getElementById('confirmMessage').textContent = message;
        setBusy(false);
        const destructive = /^hapus/i.test(message);
        okButton.className = 'b ' + (destructive ? 'b-danger' : 'b-primary');
        okButton.textContent = destructive ? 'Ya, hapus' : 'Ya, lanjutkan';
        dialog.showModal();
        document.getElementById('confirmCancel').focus();
    }, true);
    document.getElementById('confirmCancel').addEventListener('click', function(){ dialog.close(); });
    dialog.addEventListener('close', function(){ pending = null; });
    const setBusy = function(busy){
        okButton.disabled = busy;
        document.getElementById('confirmCancel').disabled = busy;
        okButton.toggleAttribute('aria-busy', busy);
        if (busy) okButton.innerHTML = '<span class="b-spin" aria-hidden="true"></span>Memproses…';
    };
    okButton.addEventListener('click', function(){
        const current = pending;
        if (!current) { dialog.close(); return; }
        // Isian form yang belum valid ditunjukkan dulu; dialog tidak perlu menunggu.
        if (!current.form.checkValidity()) { dialog.close(); current.form.reportValidity(); return; }
        // Dialog tetap terbuka dengan animasi sampai halaman berganti, supaya jelas aksinya sedang diproses.
        setBusy(true);
        current.form.dataset.confirmed = '1';
        if (current.submitter && current.submitter.isConnected) current.form.requestSubmit(current.submitter);
        else current.form.requestSubmit();
    });
    // Tombol Esc tidak menutup dialog saat sedang memproses.
    dialog.addEventListener('cancel', function(event){ if (okButton.disabled) event.preventDefault(); });
    // Kembali lewat tombol Back browser: dialog yang tertinggal ditutup.
    window.addEventListener('pageshow', function(event){ if (event.persisted) { setBusy(false); if (dialog.open) dialog.close(); } });
})();
function toggleTheme(){
    const el = document.documentElement;
    const dark = el.getAttribute('data-theme') === 'dark';
    if (dark){ el.removeAttribute('data-theme'); try{localStorage.setItem('simkm-theme','light');}catch(e){} }
    else { el.setAttribute('data-theme','dark'); try{localStorage.setItem('simkm-theme','dark');}catch(e){} }
}
function setUserMenu(open){
    document.getElementById('userPanel')?.classList.toggle('open', open);
    document.getElementById('userToggle')?.setAttribute('aria-expanded', open ? 'true' : 'false');
}
function toggleNotif(e){
    e.stopPropagation();
    setUserMenu(false);
    document.getElementById('notifPanel')?.classList.toggle('open');
    // Membuka panel menandai semua notifikasi sudah dibaca; sorotan "baru" tetap sampai halaman dimuat ulang.
    const badge = document.getElementById('notifBadge');
    if (badge) {
        badge.remove();
        fetch('/notifications/read', {method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}}).catch(function(){});
    }
}
function toggleUserMenu(e){
    e.stopPropagation();
    document.getElementById('notifPanel')?.classList.remove('open');
    setUserMenu(!document.getElementById('userPanel')?.classList.contains('open'));
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
    if (!document.getElementById('userWrap')?.contains(ev.target)) setUserMenu(false);
});
document.addEventListener('keydown', function(ev){
    if (ev.key === 'Escape'){ closeSidebar(); setUserMenu(false); }
});
document.querySelectorAll('.sidebar a').forEach(function(link){
    link.addEventListener('click', function(){ closeSidebar(); });
});
setTimeout(function(){
    document.getElementById('flashToast')?.remove();
}, 4500);
// Baris/blok dengan data-toggle="x" berganti antara tampilan [data-view="x"] dan form edit [data-edit="x"].
document.addEventListener('click', function(ev){
    const button = ev.target.closest('[data-toggle]');
    const editing = button && document.querySelector('[data-edit="' + button.dataset.toggle + '"]');
    if (!editing) return;
    const opening = editing.hidden;
    editing.hidden = !opening;
    document.querySelectorAll('[data-view="' + button.dataset.toggle + '"]').forEach(function(el){ el.hidden = opening; });
    if (opening) editing.querySelector('input,select')?.focus();
});

// Isian tanggal: browser menampilkan <input type="date"> mengikuti bahasa perangkat (sering mm/dd/yyyy).
// Agar selalu dd/mm/yyyy, isian yang terlihat adalah teks; input tanggal aslinya tetap menyimpan nilai
// yyyy-mm-dd yang dikirim ke server dan dipakai untuk membuka kalender.
document.querySelectorAll('input[type="date"]').forEach(function(native){
    const wrap = document.createElement('span');
    wrap.className = 'date-dmy';
    const text = document.createElement('input');
    text.type = 'text';
    text.inputMode = 'numeric';
    text.placeholder = 'dd/mm/yyyy';
    text.maxLength = 10;
    text.autocomplete = 'off';
    text.required = native.required;
    if (native.getAttribute('aria-describedby')) text.setAttribute('aria-describedby', native.getAttribute('aria-describedby'));
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'date-dmy-btn';
    button.setAttribute('aria-label', 'Buka kalender');
    button.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>';

    native.parentNode.insertBefore(wrap, native);
    wrap.append(text, native, button);
    native.required = false;
    native.tabIndex = -1;
    native.setAttribute('aria-hidden', 'true');

    const toDmy = function(iso){ const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso || ''); return m ? m[3] + '/' + m[2] + '/' + m[1] : ''; };
    const toIso = function(dmy){
        const m = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec(dmy.trim());
        if (!m) return null;
        const day = +m[1], month = +m[2], year = +m[3];
        const date = new Date(year, month - 1, day);
        if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) return null;
        return m[3] + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
    };
    const showNative = function(){ text.value = toDmy(native.value); text.setCustomValidity(''); };
    const validate = function(){
        if (text.value.trim() === '') { text.setCustomValidity(''); return; }
        const iso = toIso(text.value);
        if (!iso) { text.setCustomValidity('Isi tanggal dengan format dd/mm/yyyy.'); return; }
        if (native.min && iso < native.min) { text.setCustomValidity('Tanggal paling awal ' + toDmy(native.min) + '.'); return; }
        if (native.max && iso > native.max) { text.setCustomValidity('Tanggal paling akhir ' + toDmy(native.max) + '.'); return; }
        text.setCustomValidity('');
    };
    const commit = function(){
        const iso = text.value.trim() === '' ? '' : toIso(text.value);
        validate();
        if (iso === null || iso === native.value) return;
        native.value = text.validationMessage === '' ? iso : '';
        native.dispatchEvent(new Event('input', {bubbles: true}));
        native.dispatchEvent(new Event('change', {bubbles: true}));
    };

    text.addEventListener('input', function(){
        // Garis miring disisipkan otomatis saat mengetik angka.
        // Tidak berlaku bila hari/bulan diketik satu digit lalu "/" (mis. 5/1/2026).
        const parts = text.value.split('/');
        const typedShort = parts.slice(0, -1).some(function(part){ return part.length < 2; }) || text.value.endsWith('/');
        if (!typedShort) {
            const digits = text.value.replace(/\D/g, '').slice(0, 8);
            text.value = [digits.slice(0, 2), digits.slice(2, 4), digits.slice(4)].filter(Boolean).join('/');
        }
        if (text.value.length === 10 || text.value === '') commit(); else text.setCustomValidity('Isi tanggal dengan format dd/mm/yyyy.');
    });
    text.addEventListener('blur', commit);
    native.addEventListener('change', function(){ if (document.activeElement !== text) showNative(); });
    button.addEventListener('click', function(){
        if (typeof native.showPicker === 'function') { try { native.showPicker(); return; } catch (error) {} }
        text.focus();
    });
    // Skrip halaman bisa mengubah nilai/batas tanggal lain di form yang sama; tampilannya disamakan lagi.
    (native.form || document).addEventListener('change', function(){ if (document.activeElement !== text) showNative(); validate(); });
    native.form?.addEventListener('click', function(event){ if (event.target.closest('[type="submit"]')) validate(); }, true);
    showNative();
});

// Tabel .stack tampil sebagai kartu di layar HP; setiap sel diberi label dari judul kolomnya.
document.querySelectorAll('table.stack').forEach(function(table){
    const labels = Array.from(table.querySelectorAll('thead th')).map(function(th){ return th.textContent.replace(/[↕↑↓]/g, '').trim(); });
    table.querySelectorAll('tbody tr').forEach(function(row){
        Array.from(row.children).forEach(function(cell, index){
            if (!cell.hasAttribute('data-label')) cell.setAttribute('data-label', cell.colSpan > 1 ? '' : (labels[index] || ''));
        });
    });
});
</script>
@stack('scripts')
</body>
</html>
