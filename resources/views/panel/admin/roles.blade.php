@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Role & Hak Akses'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@section('content')
    <div class="grid g2">
        @foreach ($roles as $role)
            <div class="card">
                <div class="card-h"><span class="dot"></span>{{ ucwords(str_replace('_', ' ', $role->name)) }}</div>
                <form method="POST" action="/admin/roles/{{ $role->id }}">
                    @csrf
                    @method('PUT')
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:8px;">
                        @foreach ($permissions as $permission)
                            <label style="display:flex;gap:8px;align-items:center;background:#f7faff;border:1px solid var(--line);border-radius:8px;padding:8px;">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked($role->hasPermissionTo($permission->name))>
                                {{ ucfirst(str_replace('-', ' ', $permission->name)) }}
                            </label>
                        @endforeach
                    </div>
                    <button type="submit" style="margin-top:12px;">Simpan Hak Akses</button>
                </form>
            </div>
        @endforeach
    </div>
@endsection
