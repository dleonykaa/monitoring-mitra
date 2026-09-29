<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk · SIMKM BPS Kepulauan Seribu</title>
    <script>(function(){try{if(localStorage.getItem('simkm-theme')==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();</script>
    <style>
        :root{--bg:#eef4fb;--card:#fff;--line:#dbe6f5;--text:#0f2747;--muted:#5b7290;--brand:#1d4ed8;--brand2:#3b82f6;--soft:#f1f6fd}
        [data-theme="dark"]{--bg:#0b1220;--card:#141d30;--line:#27344c;--text:#e4ebf7;--muted:#93a5c2;--brand:#3b82f6;--brand2:#60a5fa;--soft:#1b2740}
        *{box-sizing:border-box}
        body{margin:0;font-family:'Segoe UI',system-ui,Arial,sans-serif;color:var(--text);background:var(--bg);min-height:100vh;display:flex}
        .wrap{display:grid;grid-template-columns:1.1fr 1fr;width:100%;min-height:100vh}
        /* Brand panel */
        .side{position:relative;overflow:hidden;background:linear-gradient(150deg,#0b2447 0%,#1d4ed8 55%,#3b82f6 100%);color:#fff;padding:48px;display:flex;flex-direction:column;justify-content:space-between}
        .side::after{content:"";position:absolute;right:-80px;top:-80px;width:320px;height:320px;border-radius:50%;background:rgba(255,255,255,.07)}
        .side::before{content:"";position:absolute;left:-60px;bottom:-90px;width:260px;height:260px;border-radius:50%;background:rgba(255,255,255,.06)}
        .side .top{display:flex;align-items:center;gap:13px;position:relative}
        .side .logo{width:54px;height:54px;border-radius:14px;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 22px rgba(0,0,0,.25)}
        .side .b-name{font-size:26px;font-weight:800;letter-spacing:.5px}
        .side .b-sub{font-size:12px;color:#bcd2f5}
        .side .hero-txt{position:relative}
        .side .hero-txt h1{font-size:32px;line-height:1.2;margin:0 0 12px;font-weight:800}
        .side .foot{font-size:12px;color:#bcd2f5;position:relative}
        /* Form panel */
        .form-side{display:flex;align-items:center;justify-content:center;padding:40px;position:relative}
        .theme-btn{position:absolute;top:22px;right:22px;width:42px;height:42px;border-radius:12px;background:var(--soft);border:1px solid var(--line);color:var(--brand);cursor:pointer;display:flex;align-items:center;justify-content:center}
        .theme-btn:hover{background:var(--brand);color:#fff}
        .theme-btn svg{width:20px;height:20px}
        .ic-sun{display:none}.ic-moon{display:block}
        [data-theme="dark"] .ic-sun{display:block}[data-theme="dark"] .ic-moon{display:none}
        .login-card{width:100%;max-width:400px}
        .login-card h2{margin:0 0 4px;font-size:24px;font-weight:800}
        .login-card .lead{color:var(--muted);font-size:14px;margin-bottom:24px}
        .field{display:grid;gap:7px;margin-bottom:16px}
        .field label{font-size:13px;font-weight:600}
        .field input[type=email],.field input[type=password]{padding:12px 14px;border:1px solid var(--line);border-radius:12px;background:var(--card);color:var(--text);font:inherit;transition:.15s}
        .field input:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 4px rgba(59,130,246,.16)}
        .row-opts{display:flex;justify-content:space-between;align-items:center;font-size:13px;color:var(--muted);margin-bottom:20px}
        .row-opts label{display:flex;align-items:center;gap:7px;cursor:pointer}
        .btn-login{width:100%;padding:13px;border:0;border-radius:12px;background:linear-gradient(90deg,var(--brand),var(--brand2));color:#fff;font-weight:700;font-size:15px;cursor:pointer;transition:.15s;box-shadow:0 8px 20px rgba(29,78,216,.3)}
        .btn-login:hover{filter:brightness(1.07);transform:translateY(-1px)}
        .err{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:11px;padding:11px 13px;margin-bottom:18px;font-size:14px}
        [data-theme="dark"] .err{background:#3a1620;border-color:#7f2336;color:#fda4b4}
        .demo{margin-top:22px;padding:13px 15px;border:1px dashed var(--line);border-radius:12px;font-size:12.5px;color:var(--muted);background:var(--soft)}
        .demo b{color:var(--text)}
        @media(max-width:860px){.wrap{grid-template-columns:1fr}.side{display:none}}
    </style>
</head>
<body>
<div class="wrap">
    <aside class="side">
        <div class="top">
            <span class="logo">
                <svg width="38" height="38" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Logo BPS">
                    <g transform="rotate(-18 50 50)">
                        <path d="M50 18 C76 18 94 32 94 50" stroke="#ef7f1a" stroke-width="6" stroke-linecap="round" fill="none"/>
                        <path d="M50 82 C24 82 6 68 6 50" stroke="#2ca9e1" stroke-width="6" stroke-linecap="round" fill="none"/>
                    </g>
                    <rect x="28" y="24" width="20" height="26" rx="4" transform="skewX(-12)" fill="#2ca9e1"/>
                    <rect x="52" y="20" width="20" height="26" rx="4" transform="skewX(-12)" fill="#ef7f1a"/>
                    <rect x="40" y="50" width="20" height="26" rx="4" transform="skewX(-12)" fill="#6cbe45"/>
                </svg>
            </span>
            <div>
                <div class="b-name">SIMKM</div>
                <div class="b-sub">BPS Kabupaten Kepulauan Seribu</div>
            </div>
        </div>

        <div class="hero-txt">
            <h1>Sistem Monitoring Kinerja Mitra</h1>
        </div>

        <div class="foot">© {{ date('Y') }} Badan Pusat Statistik — Kabupaten Kepulauan Seribu</div>
    </aside>

    <main class="form-side">
        <button type="button" class="theme-btn" title="Mode Gelap/Terang" onclick="toggleTheme()">
            <svg class="ic-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            <svg class="ic-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>

        <div class="login-card">
            <h2>Selamat Datang</h2>
            <div class="lead">Masuk menggunakan akun yang sudah terdaftar.</div>

            @if ($errors->any())
                <div class="err">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="/login">
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="nama@bps.go.id">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required placeholder="••••••••">
                </div>
                <div class="row-opts">
                    <label><input type="checkbox" name="remember" value="1"> Ingat saya</label>
                    <label><input id="show-password" type="checkbox"> Tampilkan sandi</label>
                </div>
                <button type="submit" class="btn-login">Masuk ke SIMKM</button>
            </form>

            <div class="demo">
                <b>Akun demo</b> (password: <b>password123</b>)<br>
                admin@bps.go.id · pegawai@bps.go.id · mitra@bps.go.id
            </div>
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
    document.getElementById('show-password').addEventListener('change', function () {
        document.getElementById('password').type = this.checked ? 'text' : 'password';
    });
</script>
</body>
</html>
