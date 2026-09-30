<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk · SIMPROCA BPS Kepulauan Seribu</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-bps.svg') }}">
    <script>(function(){try{if(localStorage.getItem('simkm-theme')==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();</script>
    <style>
        :root{
            --page:#ffffff;--text:#0f2747;--muted:#546b89;--line:#b6c5d8;--line-hover:#8aa0bb;--field:#ffffff;--soft:#f1f6fd;
            --brand:#1d4ed8;--brand-hover:#1e40af;--ring:rgba(29,78,216,.22);
            --side:#0b2447;--side-text:#ffffff;--side-muted:#b9cbe6;
            --bps-blue:#0093dd;--bps-green:#68b92e;--bps-orange:#eb891b;
            --err-bg:#fef2f2;--err-line:#fca5a5;--err-text:#991b1b;
        }
        [data-theme="dark"]{
            --page:#0f1829;--text:#e4ebf7;--muted:#9fb1cc;--line:#34445f;--line-hover:#4d6183;--field:#15213a;--soft:#1a2842;
            --brand:#3b82f6;--brand-hover:#60a5fa;--ring:rgba(96,165,250,.28);
            --side:#0c1f3d;--side-muted:#a9bddb;
            --err-bg:#3a1620;--err-line:#7f2336;--err-text:#fda4b4;
        }
        *{box-sizing:border-box}
        html,body{height:100%}
        body{margin:0;font-family:'Segoe UI',system-ui,-apple-system,Arial,sans-serif;color:var(--text);background:var(--page);-webkit-font-smoothing:antialiased}
        .wrap{display:grid;grid-template-columns:minmax(0,5fr) minmax(0,6fr);min-height:100vh}

        /* Panel identitas */
        .side{position:relative;overflow:hidden;background:var(--side);color:var(--side-text);padding:48px 56px;display:flex;flex-direction:column;justify-content:space-between;gap:48px}
        .side > *{position:relative;z-index:1}
        .lockup{display:flex;align-items:center;gap:16px}
        .lockup .mark{flex:none;width:64px;height:64px;border-radius:16px;background:#fff;display:grid;place-items:center}
        .lockup .mark img{width:46px;height:auto;display:block}
        .lockup .org{font-size:14px;font-weight:700;font-style:italic;text-transform:uppercase;letter-spacing:.02em;line-height:1.25}
        .lockup .unit{font-size:12.5px;color:var(--side-muted);margin-top:3px}
        .intro h1{margin:0 0 14px;font-size:30px;line-height:1.15;font-weight:700;letter-spacing:-.01em;text-wrap:balance;max-width:18ch}
        .intro p{margin:0;font-size:14.5px;line-height:1.6;color:var(--side-muted);max-width:40ch}
        .side-foot{display:flex;flex-direction:column;gap:14px;font-size:12px;color:var(--side-muted)}
        .tricolor{display:flex;gap:6px}
        .tricolor span{width:28px;height:4px;border-radius:2px}
        .tricolor span:nth-child(1){background:var(--bps-blue)}
        .tricolor span:nth-child(2){background:var(--bps-green)}
        .tricolor span:nth-child(3){background:var(--bps-orange)}
        .bars{position:absolute;right:0;bottom:0;width:78%;max-width:520px;height:auto;z-index:0;pointer-events:none}

        /* Panel formulir */
        .form-side{position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 32px}
        .theme-btn{position:absolute;top:20px;right:20px;width:40px;height:40px;border-radius:11px;background:var(--soft);border:1px solid transparent;color:var(--text);cursor:pointer;display:grid;place-items:center;transition:background-color .15s,color .15s}
        .theme-btn:hover{color:var(--brand);border-color:var(--line)}
        .theme-btn svg{width:19px;height:19px}
        .ic-sun{display:none}.ic-moon{display:block}
        [data-theme="dark"] .ic-sun{display:block}[data-theme="dark"] .ic-moon{display:none}

        .login{width:100%;max-width:380px}
        .mobile-brand{display:none;align-items:center;gap:12px;margin-bottom:32px}
        .mobile-brand img{width:44px;height:auto}
        .mobile-brand b{display:block;font-size:15px}
        .mobile-brand span{font-size:12px;color:var(--muted)}
        .login h2{margin:0 0 6px;font-size:21px;line-height:1.2;font-weight:700;letter-spacing:-.01em}
        .login .lead{margin:0 0 28px;color:var(--muted);font-size:13.5px;line-height:1.5}

        .alert{display:flex;gap:10px;align-items:flex-start;background:var(--err-bg);color:var(--err-text);border:1px solid var(--err-line);border-radius:10px;padding:11px 13px;margin-bottom:20px;font-size:13.5px;line-height:1.45}
        .alert svg{flex:none;width:18px;height:18px;margin-top:1px}

        .field{margin-bottom:18px}
        .field label{display:block;font-size:13px;font-weight:600;margin-bottom:7px}
        .control{position:relative}
        .control input{width:100%;height:46px;padding:0 14px;border:1px solid var(--line);border-radius:10px;background:var(--field);color:var(--text);font:inherit;font-size:14px;transition:border-color .15s,box-shadow .15s}
        .control input::placeholder{color:var(--muted);opacity:.85}
        .control input:hover{border-color:var(--line-hover)}
        .control input:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 4px var(--ring)}
        .control input[aria-invalid="true"]{border-color:var(--err-text)}
        .control.has-toggle input{padding-right:48px}
        .pw-toggle{position:absolute;top:3px;right:3px;width:40px;height:40px;border:0;border-radius:8px;background:transparent;color:var(--muted);cursor:pointer;display:grid;place-items:center;transition:color .15s,background-color .15s}
        .pw-toggle:hover{color:var(--text);background:var(--soft)}
        .pw-toggle svg{width:19px;height:19px}
        .pw-toggle .ic-eye-off{display:none}
        .pw-toggle[aria-pressed="true"] .ic-eye{display:none}
        .pw-toggle[aria-pressed="true"] .ic-eye-off{display:block}

        .alert.ok{background:#ecfdf5;color:#065f46;border-color:#86efac}
        [data-theme="dark"] .alert.ok{background:#0e3a2a;color:#86efac;border-color:#14532d}
        .forgot{margin-top:16px;font-size:13.5px}
        .forgot summary{cursor:pointer;color:var(--brand, #1d4ed8);font-weight:600;list-style:none;display:inline-block}
        .forgot summary::-webkit-details-marker{display:none}
        .forgot summary:hover{text-decoration:underline}
        .forgot p{margin:10px 0;color:var(--muted);font-size:12.5px;line-height:1.5}
        .forgot-row{display:flex;gap:8px}
        .forgot-row input{flex:1 1 auto;min-width:0;padding:10px 12px;border:1px solid var(--line, #dbe6f5);border-radius:10px;font:inherit;font-size:13.5px;background:transparent;color:inherit}
        .forgot-row button{flex:none;padding:10px 14px;border:0;border-radius:10px;background:var(--brand, #1d4ed8);color:#fff;font:inherit;font-size:13px;font-weight:600;cursor:pointer}
        .remember{display:inline-flex;align-items:center;gap:9px;font-size:13.5px;color:var(--text);cursor:pointer;margin:2px 0 24px;user-select:none}
        .remember input{width:17px;height:17px;margin:0;accent-color:var(--brand);cursor:pointer}

        .btn-login{position:relative;width:100%;height:48px;border:0;border-radius:10px;background:var(--brand);color:#fff;font:inherit;font-weight:600;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px;transition:background-color .15s}
        .btn-login:hover{background:var(--brand-hover)}
        .btn-login:disabled{cursor:progress;opacity:.8}
        .btn-login .spinner{display:none;width:17px;height:17px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite}
        .btn-login[data-loading] .spinner{display:block}
        @keyframes spin{to{transform:rotate(360deg)}}

        .demo{margin-top:32px;padding-top:20px;border-top:1px solid var(--line)}
        .demo p{margin:0 0 10px;font-size:12.5px;color:var(--muted)}
        .demo-list{display:flex;flex-wrap:wrap;gap:8px}
        .demo-list button{height:34px;padding:0 14px;border:1px solid var(--line);border-radius:999px;background:transparent;color:var(--text);font:inherit;font-size:12.5px;font-weight:600;cursor:pointer;transition:border-color .15s,color .15s,background-color .15s}
        .demo-list button:hover{border-color:var(--brand);color:var(--brand);background:var(--soft)}

        .form-foot{display:none;margin-top:40px;font-size:12px;color:var(--muted);text-align:center}

        :focus-visible{outline:2px solid var(--brand);outline-offset:2px}
        .control input:focus-visible{outline:none}

        @media (max-width:960px){
            .wrap{grid-template-columns:1fr}
            .side{display:none}
            .mobile-brand{display:flex}
            .form-foot{display:block}
            .form-side{justify-content:flex-start;padding:72px 20px 32px}
        }
        @media (prefers-reduced-motion:reduce){
            *{transition:none!important}
            .btn-login .spinner{animation-duration:2s}
        }
    </style>
</head>
<body>
<div class="wrap">
    <aside class="side">
        <div class="lockup">
            <span class="mark"><img src="{{ asset('images/logo-bps.svg') }}" alt="Logo Badan Pusat Statistik"></span>
            <div>
                <div class="org">Badan Pusat Statistik</div>
                <div class="unit">Kabupaten Kepulauan Seribu</div>
            </div>
        </div>

        <div class="intro">
            <h1>Sistem Monitoring Progress Pencacahan</h1>
            <p>Pantau pelaksanaan survei, penugasan, dan capaian mitra statistik dalam satu tempat.</p>
        </div>

        <div class="side-foot">
            <div class="tricolor" aria-hidden="true"><span></span><span></span><span></span></div>
            <div>© {{ date('Y') }} BPS Kabupaten Kepulauan Seribu</div>
        </div>

        <svg class="bars" viewBox="0 0 520 300" aria-hidden="true" focusable="false">
            <g fill="#ffffff" fill-opacity=".05">
                <rect x="20" y="210" width="44" height="90" rx="6"/>
                <rect x="84" y="170" width="44" height="130" rx="6"/>
                <rect x="148" y="190" width="44" height="110" rx="6"/>
                <rect x="212" y="120" width="44" height="180" rx="6"/>
                <rect x="276" y="140" width="44" height="160" rx="6"/>
                <rect x="340" y="80" width="44" height="220" rx="6"/>
                <rect x="404" y="40" width="44" height="260" rx="6"/>
                <rect x="468" y="60" width="44" height="240" rx="6"/>
            </g>
            <polyline points="42,196 106,156 170,176 234,106 298,126 362,66 426,26 490,46" fill="none" stroke="#ffffff" stroke-opacity=".14" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
        </svg>
    </aside>

    <main class="form-side">
        <button type="button" class="theme-btn" aria-label="Ganti mode gelap atau terang" title="Mode gelap/terang" onclick="toggleTheme()">
            <svg class="ic-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            <svg class="ic-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>

        <div class="login">
            <div class="mobile-brand">
                <img src="{{ asset('images/logo-bps.svg') }}" alt="Logo Badan Pusat Statistik">
                <div>
                    <b>SIMPROCA</b>
                    <span>BPS Kabupaten Kepulauan Seribu</span>
                </div>
            </div>

            <h2>Masuk ke SIMPROCA</h2>
            <p class="lead">Gunakan email dan kata sandi akun Anda.</p>

            @if ($errors->any())
                <div class="alert" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            @if (session('reset_status'))
                <div class="alert ok" role="status">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/></svg>
                    <span>{{ session('reset_status') }}</span>
                </div>
            @endif

            <form id="login-form" method="POST" action="/login">
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <div class="control">
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="nama@bps.go.id" @if ($errors->any()) aria-invalid="true" @endif>
                    </div>
                </div>
                <div class="field">
                    <label for="password">Kata sandi</label>
                    <div class="control has-toggle">
                        <input id="password" type="password" name="password" required autocomplete="current-password" @if ($errors->any()) aria-invalid="true" @endif>
                        <button type="button" class="pw-toggle" id="pw-toggle" aria-pressed="false" aria-label="Tampilkan kata sandi">
                            <svg class="ic-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="ic-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c6.5 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19M6.6 6.6A18.4 18.4 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M2 2l20 20"/></svg>
                        </button>
                    </div>
                </div>
                <label class="remember"><input type="checkbox" name="remember" value="1"> Ingat saya di perangkat ini</label>
                <button type="submit" class="btn-login" id="btn-login">
                    <span class="spinner" aria-hidden="true"></span>
                    <span class="label">Masuk</span>
                </button>
            </form>

            <details class="forgot" @if ($errors->has('reset_email')) open @endif>
                <summary>Lupa kata sandi?</summary>
                <form method="POST" action="/lupa-kata-sandi">
                    @csrf
                    <p>Masukkan email akun Anda. Permintaan diteruskan ke admin, lalu admin memberi Anda kata sandi baru.</p>
                    <div class="forgot-row">
                        <input type="email" name="reset_email" value="{{ old('reset_email') }}" required placeholder="Email akun Anda" autocomplete="username" aria-label="Email akun">
                        <button type="submit">Minta reset</button>
                    </div>
                </form>
            </details>

            <div class="demo">
                <p>Coba dengan akun demo:</p>
                <div class="demo-list">
                    <button type="button" data-email="admin@bps.go.id">Admin</button>
                    <button type="button" data-email="pegawai@bps.go.id">Pegawai BPS</button>
                    <button type="button" data-email="mitra@bps.go.id">Mitra</button>
                </div>
            </div>

            <div class="form-foot">© {{ date('Y') }} BPS Kabupaten Kepulauan Seribu</div>
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

    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('pw-toggle');
    passwordToggle.addEventListener('click', function () {
        const isVisible = this.getAttribute('aria-pressed') === 'true';
        this.setAttribute('aria-pressed', String(!isVisible));
        this.setAttribute('aria-label', isVisible ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi');
        passwordInput.type = isVisible ? 'password' : 'text';
    });

    document.querySelectorAll('.demo-list button').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('email').value = this.dataset.email;
            passwordInput.value = 'password123';
            document.getElementById('btn-login').focus();
        });
    });

    document.getElementById('login-form').addEventListener('submit', function () {
        const submitButton = document.getElementById('btn-login');
        submitButton.disabled = true;
        submitButton.setAttribute('data-loading', '');
        submitButton.querySelector('.label').textContent = 'Memproses…';
    });

    window.addEventListener('pageshow', function () {
        const submitButton = document.getElementById('btn-login');
        submitButton.disabled = false;
        submitButton.removeAttribute('data-loading');
        submitButton.querySelector('.label').textContent = 'Masuk';
    });
</script>
</body>
</html>
