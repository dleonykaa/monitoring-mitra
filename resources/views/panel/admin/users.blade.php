@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Manajemen Pengguna'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@php
    $roleTabs = ['all' => 'Semua', 'admin' => 'Admin', 'pegawai_bps' => 'Pegawai BPS', 'mitra' => 'Mitra'];
    $roleLabels = ['admin' => 'Admin', 'pegawai_bps' => 'Pegawai BPS', 'mitra' => 'Mitra'];
    $roleTone = ['admin' => 'bdg-rose', 'pegawai_bps' => 'bdg-blue', 'mitra' => 'bdg-gray'];
    $addLabel = $selectedRole === 'all' ? null : $roleTabs[$selectedRole];
@endphp

@section('content')
<div class="ui">
    <div class="toolbar">
        <nav class="seg" aria-label="Filter peran pengguna">
            @foreach ($roleTabs as $role => $label)
                <a href="/admin/users?{{ http_build_query(array_filter(['role' => $role, 'q' => $search])) }}" @if ($selectedRole === $role) aria-current="page" @endif>
                    {{ $label }} <small>{{ number_format($roleCounts[$role], 0, ',', '.') }}</small>
                </a>
            @endforeach
        </nav>
        <div class="pg-actions" style="width:auto">
            <form class="search" method="GET" action="/admin/users" role="search">
                <input type="hidden" name="role" value="{{ $selectedRole }}">
                <label class="sr-only" for="userSearch">Cari nama atau email</label>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input id="userSearch" type="search" name="q" value="{{ $search }}" placeholder="Cari nama atau email" autocomplete="off">
            </form>
            @if ($addLabel)
                <button type="button" class="b b-primary" onclick="document.getElementById('add-user').showModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Tambah {{ $addLabel }}
                </button>
            @endif
        </div>
    </div>

    <section class="pnl flush" aria-labelledby="usersTitle">
        <div class="pnl-h">
            <div>
                <h2 id="usersTitle">{{ $roleTabs[$selectedRole] === 'Semua' ? 'Semua pengguna' : $roleTabs[$selectedRole] }} <span class="num-chip">{{ number_format($users->total(), 0, ',', '.') }}</span></h2>
                @if ($search !== '')
                    <p>Hasil pencarian "{{ $search }}". <a href="/admin/users?role={{ $selectedRole }}" style="color:var(--brand);font-weight:600">Hapus pencarian</a></p>
                @endif
            </div>
        </div>
        <div class="pnl-b">
            @if ($users->isEmpty())
                <div class="empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
                    <b>{{ $search !== '' ? 'Tidak ada pengguna yang cocok' : 'Belum ada akun di kelompok ini' }}</b>
                    <span>{{ $search !== '' ? 'Coba kata kunci lain atau periksa ejaan email.' : 'Tambahkan akun agar bisa masuk ke SIMPROCA.' }}</span>
                </div>
            @else
                <div class="tbl-wrap">
                    <table class="tbl stack" style="min-width:720px">
                        <thead>
                            <tr><th>Pengguna</th><th>Peran</th><th>No. WhatsApp</th><th>Status</th><th>Terdaftar</th><th class="act"><span class="sr-only">Aksi</span></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                @php
                                    $roleName = $user->roles->first()?->name;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="who">
                                            <span><b>{{ $user->name }}</b><small>{{ $user->email }}</small></span>
                                        </div>
                                    </td>
                                    <td><span class="bdg {{ $roleTone[$roleName] ?? 'bdg-gray' }}">{{ $roleLabels[$roleName] ?? ($roleName ?? '-') }}</span></td>
                                    <td class="muted-cell">{{ $user->phone ?: '–' }}</td>
                                    <td><span class="bdg bdg-dot {{ $user->is_active ? 'bdg-green' : 'bdg-gray' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                    <td class="muted-cell" style="white-space:nowrap">{{ $user->created_at?->locale('id')->translatedFormat('d M Y') }}</td>
                                    <td class="act">
                                        <button type="button" class="b b-soft b-sm" onclick="document.getElementById('edit-user-{{ $user->id }}').showModal()">Ubah</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @if ($users->hasPages())
            <div class="pnl-f" style="display:block">{{ $users->links() }}</div>
        @endif
    </section>
</div>

@foreach ($users as $user)
    <dialog id="edit-user-{{ $user->id }}" class="dlg" aria-labelledby="edit-user-title-{{ $user->id }}">
        <div class="dlg-h">
            <div>
                <h2 id="edit-user-title-{{ $user->id }}">Ubah pengguna</h2>
                <p>{{ $user->email }}</p>
            </div>
            <button type="button" class="b b-ghost b-icon" aria-label="Tutup" onclick="this.closest('dialog').close()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="edit-form-{{ $user->id }}" method="POST" action="/admin/users/{{ $user->id }}" class="dlg-b fld-grid">
            @csrf
            @method('PUT')
            <label class="fld full"><span>Nama</span><input name="name" value="{{ $user->name }}" required></label>
            <label class="fld full"><span>Email</span><input name="email" type="email" value="{{ $user->email }}" required></label>
            <label class="fld"><span>No. WhatsApp <small>(opsional)</small></span><input name="phone" type="tel" value="{{ $user->phone }}"></label>
            <label class="fld"><span>Peran</span>
                <select name="role" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}" @selected($user->hasRole($role->name))>{{ $roleLabels[$role->name] ?? $role->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="fld"><span>Status</span>
                <select name="is_active" required>
                    <option value="1" @selected($user->is_active)>Aktif</option>
                    <option value="0" @selected(! $user->is_active)>Nonaktif</option>
                </select>
            </label>
            <label class="fld"><span>Kata sandi baru</span><input name="password" type="password" minlength="8" autocomplete="new-password"><span class="hint">Kosongkan bila tidak diubah.</span></label>
        </form>
        <form id="delete-form-{{ $user->id }}" method="POST" action="/admin/users/{{ $user->id }}" data-confirm="Hapus akun {{ $user->name }}? Akun tidak bisa dipakai masuk lagi.">
            @csrf
            @method('DELETE')
        </form>
        <div class="dlg-f">
            <button form="delete-form-{{ $user->id }}" type="submit" class="b b-danger-soft">Hapus pengguna</button>
            <div class="right">
                <button type="button" class="b b-soft" onclick="this.closest('dialog').close()">Batal</button>
                <button form="edit-form-{{ $user->id }}" type="submit" class="b b-primary">Simpan perubahan</button>
            </div>
        </div>
    </dialog>
@endforeach

@if ($addLabel)
    <dialog id="add-user" class="dlg" aria-labelledby="add-user-title">
        <div class="dlg-h">
            <div>
                <h2 id="add-user-title">Tambah {{ $addLabel }}</h2>
                <p>Akun baru langsung aktif dengan peran {{ $addLabel }}.</p>
            </div>
            <button type="button" class="b b-ghost b-icon" aria-label="Tutup" onclick="this.closest('dialog').close()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="add-user-form" method="POST" action="/admin/users" class="dlg-b fld-grid">
            @csrf
            <input name="role" type="hidden" value="{{ $selectedRole }}">
            <label class="fld full"><span>Nama</span><input name="name" value="{{ old('name') }}" required></label>
            <label class="fld full"><span>Email</span><input name="email" type="email" value="{{ old('email') }}" required></label>
            <label class="fld"><span>No. WhatsApp <small>(opsional)</small></span><input name="phone" type="tel" value="{{ old('phone') }}"></label>
            <label class="fld"><span>Kata sandi</span><input name="password" type="password" minlength="8" required autocomplete="new-password"><span class="hint">Minimal 8 karakter.</span></label>
        </form>
        <div class="dlg-f">
            <div class="right">
                <button type="button" class="b b-soft" onclick="this.closest('dialog').close()">Batal</button>
                <button form="add-user-form" type="submit" class="b b-primary">Tambah pengguna</button>
            </div>
        </div>
    </dialog>
@endif
@endsection
