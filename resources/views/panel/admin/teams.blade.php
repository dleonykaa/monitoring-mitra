@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Tim Kerja'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@section('content')
    <div class="card">
        <div class="card-h"><span class="dot"></span>Tambah Tim Kerja</div>
        <form method="POST" action="/admin/teams" style="display:flex;flex-wrap:wrap;gap:8px;">
            @csrf
            <input name="name" placeholder="Nama Tim" required style="flex:1 1 160px;min-width:0;">
            <input name="description" placeholder="Deskripsi" style="flex:2 1 220px;min-width:0;">
            <button type="submit" style="flex:0 0 auto;">Tambah Tim</button>
        </form>
    </div>

    <div class="team-grid">
        @foreach ($teams as $team)
            <a class="team-card" href="/admin/teams/{{ $team->id }}">
                <div class="team-card-name"><span class="dot"></span>{{ $team->name }}</div>
                <div class="muted team-card-desc">{{ $team->description ?: 'Tidak ada deskripsi' }}</div>
                <div class="team-card-count">{{ $team->users_count }} pegawai</div>
            </a>
        @endforeach
    </div>
@endsection

@push('head')
<style>
    .team-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 12px;
    }

    .team-card {
        display: block;
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 14px 16px;
        box-shadow: 0 2px 8px var(--shadow);
        text-decoration: none;
        color: inherit;
        transition: .15s;
    }

    .team-card:hover {
        border-color: var(--brand);
        box-shadow: 0 4px 12px var(--shadow);
    }

    .team-card-name {
        font-weight: 700;
        color: var(--brand-dark);
        font-size: 14px;
    }

    .team-card-desc {
        font-size: 12px;
        margin-top: 6px;
        min-height: 16px;
    }

    .team-card-count {
        margin-top: 12px;
        font-size: 12px;
        font-weight: 600;
        color: var(--brand);
    }
</style>
@endpush
