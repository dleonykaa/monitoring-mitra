@extends('panel.layout', ['panelTitle' => $panelTitle, 'pageTitle' => 'Profil'])

@section('menu')
    @isset($menuView)
        @include($menuView)
    @else
        {!! $menuHtml !!}
    @endisset
@endsection

@php
    $user = auth()->user();
    $roleName = $user->getRoleNames()->first();
    $roleLabel = ['admin' => 'Administrator', 'pegawai_bps' => 'Pegawai BPS', 'mitra' => 'Mitra BPS'][$roleName] ?? ($roleName ?? 'Pengguna');
    $initials = collect(preg_split('/\s+/', trim($user->name)))->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
    $passwordFields = [
        ['name' => 'current_password', 'label' => 'Kata sandi saat ini', 'autocomplete' => 'current-password', 'minlength' => null],
        ['name' => 'password', 'label' => 'Kata sandi baru', 'autocomplete' => 'new-password', 'minlength' => 8],
        ['name' => 'password_confirmation', 'label' => 'Ulangi kata sandi baru', 'autocomplete' => 'new-password', 'minlength' => 8],
    ];
@endphp

@push('head')
<style>
    .pf{max-width:980px}
    .pf-id{display:flex;flex-wrap:wrap;align-items:center;gap:14px 18px}
    .pf-avatar{flex:none;width:64px;height:64px;border-radius:18px;display:grid;place-items:center;background:var(--navy-chip);color:#fff;font-size:22px;font-weight:800;letter-spacing:.02em;box-shadow:inset 0 0 0 1px rgba(255,255,255,.18)}
    .pf-id h1{overflow-wrap:anywhere}
    .pf-id > div{min-width:0;flex:1 1 220px}

    .pf-list{margin:0;display:grid}
    .pf-list > div{position:relative;min-height:34px;padding:12px 0 12px 46px;border-top:1px solid var(--line)}
    .pf-list > div:first-child{border-top:0;padding-top:0}
    .pf-list > div:first-child .pf-ico{top:0}
    .pf-list > div:last-child{padding-bottom:0}
    .pf-ico{position:absolute;left:0;top:12px;width:34px;height:34px;border-radius:10px;display:grid;place-items:center;background:var(--navy-tint);color:var(--navy-tint-fg)}
    .pf-ico svg{width:16px;height:16px}
    .pf-list dt{font-size:12px;color:var(--muted);font-weight:600}
    .pf-list dd{margin:2px 0 0;font-size:13.5px;font-weight:600;color:var(--text);overflow-wrap:anywhere}

    .pf-form{display:grid;gap:14px;max-width:460px}
    .pw{position:relative}
    .pw input{padding-right:44px}
    .pw input[aria-invalid="true"]{border-color:var(--st-late)}
    .pw-eye{all:unset;box-sizing:border-box;position:absolute;right:4px;top:50%;transform:translateY(-50%);width:34px;height:34px;border-radius:8px;display:grid;place-items:center;color:var(--muted);cursor:pointer;transition:background-color .15s ease-out,color .15s ease-out}
    .pw-eye:hover{background:var(--soft);color:var(--text)}
    .pw-eye:focus-visible{outline:2px solid var(--brand);outline-offset:1px}
    .pw-eye svg{width:17px;height:17px}
    .pw-eye .off{display:none}
    .pw-eye[aria-pressed="true"] .on{display:none}
    .pw-eye[aria-pressed="true"] .off{display:block}
    .fld .err{font-size:12px;font-weight:600;color:var(--st-late-ink)}

    .pw-rules{list-style:none;margin:0;padding:0;display:grid;gap:6px;font-size:12.5px;color:var(--muted)}
    .pw-rules li{display:flex;align-items:center;gap:8px;transition:color .15s ease-out}
    .pw-rules svg{flex:none;width:16px;height:16px;padding:3px;border-radius:50%;background:var(--soft);color:transparent;transition:background-color .15s ease-out,color .15s ease-out}
    .pw-rules li.ok{color:var(--st-submit-ink)}
    .pw-rules li.ok svg{background:var(--st-submit);color:#fff}
    @media (prefers-reduced-motion:reduce){.pw-eye,.pw-rules li,.pw-rules svg{transition:none}}
</style>
@endpush

@section('content')
<div class="ui pf">
    <section class="hero-nv" aria-label="Identitas akun">
        <div class="pf-id">
            <span class="pf-avatar" aria-hidden="true">{{ $initials }}</span>
            <div>
                <h1>{{ $user->name }}</h1>
                <div class="meta">
                    <span class="chip">{{ $roleLabel }}</span>
                    <span>{{ $user->email }}</span>
                </div>
            </div>
        </div>
    </section>

    <div class="cols cols-side">
        <form class="pnl" method="POST" action="/profile/password" aria-labelledby="pwTitle">
            @csrf
            <div class="pnl-h">
                <div>
                    <h2 id="pwTitle">Ubah kata sandi</h2>
                    <p>Anda tetap masuk di perangkat ini setelah kata sandi diganti.</p>
                </div>
            </div>
            <div class="pnl-b">
                <div class="pf-form">
                    @foreach ($passwordFields as $field)
                        <label class="fld">
                            <span>{{ $field['label'] }}</span>
                            <span class="pw">
                                <input type="password" name="{{ $field['name'] }}" id="pw_{{ $field['name'] }}" required autocomplete="{{ $field['autocomplete'] }}"
                                    @if ($field['minlength']) minlength="{{ $field['minlength'] }}" @endif
                                    @error($field['name']) aria-invalid="true" aria-describedby="err_{{ $field['name'] }}" @enderror>
                                <button type="button" class="pw-eye" aria-pressed="false" aria-controls="pw_{{ $field['name'] }}" aria-label="Tampilkan {{ mb_strtolower($field['label']) }}">
                                    <svg class="on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.6 5.1A10.9 10.9 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-3 3.9M6.5 6.6A17.300 17.300 0 0 0 2 12s3.600 7 10 7a10.300 10.300 0 0 0 5.200-1.400M9.900 9.900a3 3 0 0 0 4.200 4.200M3 3l18 18"/></svg>
                                </button>
                            </span>
                            @error($field['name'])<span class="err" id="err_{{ $field['name'] }}">{{ $message }}</span>@enderror
                        </label>
                    @endforeach

                    <ul class="pw-rules" aria-label="Syarat kata sandi baru">
                        <li data-rule="length">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                            Minimal 8 karakter
                        </li>
                        <li data-rule="match">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                            Kedua isian kata sandi baru sama
                        </li>
                    </ul>
                </div>
                <div class="form-foot" style="margin-top:18px">
                    <p>Kata sandi lama langsung tidak berlaku.</p>
                    <button type="submit" class="b b-primary">Simpan kata sandi</button>
                </div>
            </div>
        </form>

        <section class="pnl" aria-labelledby="accountTitle">
            <div class="pnl-h">
                <div>
                    <h2 id="accountTitle">Informasi akun</h2>
                    <p>Hubungi admin untuk mengubah nama, email, atau nomor WhatsApp.</p>
                </div>
            </div>
            <div class="pnl-b">
                <dl class="pf-list">
                    <div>
                        <dt><span class="pf-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span>Email</dt>
                        <dd>{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt><span class="pf-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.900v3a2 2 0 0 1-2.200 2 19.800 19.800 0 0 1-8.600-3.100 19.500 19.500 0 0 1-6-6A19.800 19.800 0 0 1 2.100 4.200 2 2 0 0 1 4.100 2h3a2 2 0 0 1 2 1.700c.100.960.360 1.900.700 2.800a2 2 0 0 1-.450 2.100L8.100 9.900a16 16 0 0 0 6 6l1.300-1.300a2 2 0 0 1 2.100-.450c.900.340 1.840.600 2.800.700A2 2 0 0 1 22 16.900z"/></svg></span>No. WhatsApp</dt>
                        <dd>{{ $user->phone ?: 'Belum diisi' }}</dd>
                    </div>
                    <div>
                        <dt><span class="pf-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6v6c0 4.500 3.400 8.300 8 9 4.600-.700 8-4.500 8-9V6z"/><path d="m9 12 2 2 4-4"/></svg></span>Peran</dt>
                        <dd>{{ $roleLabel }}</dd>
                    </div>
                    @if ($user->created_at)
                        <div>
                            <dt><span class="pf-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span>Terdaftar sejak</dt>
                        <dd>{{ $user->created_at->locale('id')->translatedFormat('d F Y') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Tombol mata menampilkan/menyembunyikan isi kolom kata sandi.
    document.querySelectorAll('.pw-eye').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.getAttribute('aria-controls'));
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(show));
        });
    });

    // Daftar syarat kata sandi baru dicentang saat terpenuhi.
    const password = document.getElementById('pw_password');
    const confirmation = document.getElementById('pw_password_confirmation');
    const rules = {
        length: () => password.value.length >= 8,
        match: () => password.value !== '' && password.value === confirmation.value,
    };
    const check = () => Object.entries(rules).forEach(([rule, passes]) => {
        document.querySelector('.pw-rules [data-rule="' + rule + '"]')?.classList.toggle('ok', passes());
    });
    [password, confirmation].forEach((input) => input.addEventListener('input', check));
})();
</script>
@endpush
