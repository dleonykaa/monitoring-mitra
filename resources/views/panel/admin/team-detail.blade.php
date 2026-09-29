@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => $team->name])

@section('menu')
    @include('panel.admin.menu')
@endsection

@section('content')
    <div class="card" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
        <div>
            <div class="card-h" style="margin:0"><span class="dot"></span>{{ $team->name }}</div>
            <div class="muted">{{ $team->description ?: 'Tidak ada deskripsi' }}</div>
        </div>
        <a class="btn btn-grey" href="/admin/teams">&larr; Daftar Tim</a>
    </div>

    <div class="card">
        <div class="card-h"><span class="dot"></span>Alokasikan Pegawai</div>
        <form method="POST" action="/admin/teams/{{ $team->id }}/assign" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:10px;">
            @csrf
            <select name="user_id" required style="flex:1 1 220px;min-width:0;">
                @foreach ($pegawaiUsers as $pegawai)
                    <option value="{{ $pegawai->id }}">{{ $pegawai->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-sm">Alokasikan</button>
        </form>
    </div>

    <div class="card">
        <div class="card-h"><span class="dot"></span>Pegawai di Tim Ini ({{ $team->users->count() }})</div>
        <div class="summary-grid" style="margin-top:14px">
            @forelse ($team->users as $pegawai)
                <div class="summary-item">
                    <strong>{{ $pegawai->name }}</strong>
                    <div class="muted">{{ $pegawai->email }}</div>
                    <form method="POST" action="/admin/teams/{{ $team->id }}/users/{{ $pegawai->id }}" style="margin-top:8px;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-sm" style="background:#be123c;">Lepas</button>
                    </form>
                </div>
            @empty
                <div class="muted">Belum ada pegawai di tim ini.</div>
            @endforelse
        </div>
    </div>
@endsection

@push('head')
<style>
    .btn-sm {
        padding: 7px 14px;
        font-size: 13px;
    }
</style>
@endpush
