@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Manajemen Pengguna'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@section('content')
    @php
        $roleTabs = [
            'all' => 'Semua',
            'pegawai_bps' => 'Pegawai BPS',
            'mitra' => 'Mitra',
        ];
    @endphp

    <div class="user-toolbar">
        <nav class="user-role-tabs" aria-label="Filter peran pengguna">
            @foreach ($roleTabs as $role => $label)
                <a href="/admin/users?role={{ $role }}" @class(['active' => $selectedRole === $role])>{{ $label }}</a>
            @endforeach
        </nav>
        @if ($selectedRole !== 'all')
            <button type="button" class="user-add-button" onclick="document.getElementById('add-user').showModal()">Tambah {{ $roleTabs[$selectedRole] }}</button>
        @endif
    </div>

    <div class="card user-table-card">
        <div class="user-table-scroll">
            <table class="rich-table">
                <tr><th>Pengguna</th><th>Peran</th><th>Status</th><th style="text-align:center">Aksi</th></tr>
                @foreach ($users as $user)
                    <tr>
                        <td>
                            <strong>{{ $user->name }}</strong>
                            <div class="muted user-email">{{ $user->email }}</div>
                        </td>
                        <td>{{ ucwords(str_replace('_', ' ', $user->roles->first()?->name ?? '-')) }}</td>
                        <td>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td style="white-space:nowrap;text-align:center">
                            <button type="button" onclick="document.getElementById('edit-user-{{ $user->id }}').showModal()">Edit</button>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>

        @foreach ($users as $user)
            <dialog id="edit-user-{{ $user->id }}" class="user-edit-dialog">
                <div class="user-edit-heading">
                    <div>
                        <div class="card-h">Edit pengguna</div>
                        <div class="muted">{{ $user->email }}</div>
                    </div>
                    <button type="button" class="dialog-close" aria-label="Tutup" onclick="document.getElementById('edit-user-{{ $user->id }}').close()">×</button>
                </div>
                <form id="edit-form-{{ $user->id }}" method="POST" action="/admin/users/{{ $user->id }}" class="user-edit-form">
                    @csrf
                    @method('PUT')
                    <label>Nama<input name="name" value="{{ $user->name }}" required></label>
                    <label>Email<input name="email" type="email" value="{{ $user->email }}" required></label>
                    <label>No. WhatsApp<input name="phone" type="tel" value="{{ $user->phone }}" placeholder="Opsional"></label>
                    <label>Peran
                        <select name="role" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}" @selected($user->hasRole($role->name))>{{ ucwords(str_replace('_', ' ', $role->name)) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Status
                        <select name="is_active" required>
                            <option value="1" @selected($user->is_active)>Aktif</option>
                            <option value="0" @selected(! $user->is_active)>Nonaktif</option>
                        </select>
                    </label>
                    <label>Kata sandi baru<input name="password" type="password" placeholder="Kosongkan bila tidak diubah"></label>
                </form>
                <form id="delete-form-{{ $user->id }}" method="POST" action="/admin/users/{{ $user->id }}" onsubmit="return confirm('Hapus user ini?')">
                    @csrf
                    @method('DELETE')
                </form>
                <div class="user-edit-actions">
                    <button form="delete-form-{{ $user->id }}" type="submit" class="btn-danger">Hapus pengguna</button>
                    <button form="edit-form-{{ $user->id }}" type="submit">Simpan perubahan</button>
                </div>
            </dialog>
        @endforeach

        <div class="user-pagination">{{ $users->links() }}</div>
    </div>

    @if ($selectedRole !== 'all')
        <dialog id="add-user" class="user-edit-dialog user-add-dialog">
            <div class="user-edit-heading">
                <div>
                    <div class="card-h">Tambah {{ $roleTabs[$selectedRole] }}</div>
                    <div class="muted">Akun baru akan masuk ke kelompok ini.</div>
                </div>
                <button type="button" class="dialog-close" aria-label="Tutup" onclick="document.getElementById('add-user').close()">×</button>
            </div>
            <form id="add-user-form" method="POST" action="/admin/users" class="user-edit-form user-add-form">
                @csrf
                <input name="role" type="hidden" value="{{ $selectedRole }}">
                <label>Nama<input name="name" required></label>
                <label>Email<input name="email" type="email" required></label>
                <label>Kata sandi<input name="password" type="password" required></label>
            </form>
            <div class="user-edit-actions">
                <button form="add-user-form" type="submit">Tambah pengguna</button>
            </div>
        </dialog>
    @endif
@endsection

@push('head')
<style>
    .user-toolbar {
        align-items: center;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .user-role-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .user-role-tabs a {
        border-radius: 8px;
        color: var(--muted);
        font-size: 13px;
        font-weight: 600;
        padding: 7px 10px;
        text-decoration: none;
    }

    .user-role-tabs a:hover {
        background: var(--row-hover);
        color: var(--brand);
    }

    .user-role-tabs a.active {
        background: var(--brand);
        color: #fff;
    }

    .user-add-button {
        border-radius: 8px;
        font-size: 13px;
        padding: 7px 10px;
        white-space: nowrap;
    }

    .user-table-card {
        padding: 0;
        overflow: hidden;
    }

    .user-table-scroll {
        overflow-x: auto;
    }

    .user-table-scroll table {
        min-width: 620px;
    }

    .user-table-scroll td,
    .user-table-scroll th {
        white-space: nowrap;
    }

    .user-pagination {
        padding: 12px 14px;
    }

    .user-email {
        font-size: 12px;
        margin-top: 3px;
    }

    .user-edit-dialog {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 8px 32px rgba(15, 39, 71, .24);
        color: var(--text);
        max-width: min(520px, calc(100vw - 32px));
        padding: 20px;
        width: 100%;
    }

    .user-edit-dialog::backdrop {
        background: rgba(15, 39, 71, .42);
    }

    .user-edit-heading {
        align-items: flex-start;
        display: flex;
        justify-content: space-between;
        margin-bottom: 16px;
    }

    .user-edit-heading .card-h {
        margin: 0 0 3px;
    }

    .dialog-close {
        background: transparent;
        box-shadow: none;
        color: var(--muted);
        font-size: 24px;
        line-height: 1;
        padding: 0 4px;
    }

    .dialog-close:hover {
        box-shadow: none;
        color: var(--text);
    }

    .user-edit-form {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .user-edit-form label {
        color: var(--muted);
        display: grid;
        font-size: 12px;
        gap: 5px;
    }

    .user-edit-form label:first-child,
    .user-edit-form label:nth-child(2) {
        grid-column: 1 / -1;
    }

    .user-edit-form input,
    .user-edit-form select {
        color: var(--text);
        min-width: 0;
        width: 100%;
    }

    .user-edit-actions {
        display: flex;
        gap: 8px;
        grid-column: 1 / -1;
        justify-content: flex-end;
        margin-top: 4px;
    }

    .user-edit-actions button {
        border-radius: 8px;
        font-size: 13px;
        padding: 7px 10px;
    }

    .user-add-form label {
        grid-column: 1 / -1;
    }

    @media (max-width: 560px) {
        .user-toolbar {
            align-items: flex-start;
            flex-direction: column;
        }

        .user-edit-dialog {
            padding: 16px;
        }

        .user-edit-form {
            grid-template-columns: 1fr;
        }

        .user-edit-form label:first-child,
        .user-edit-form label:nth-child(2),
        .user-add-form label {
            grid-column: auto;
        }
    }

</style>
@endpush
