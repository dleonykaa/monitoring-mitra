@extends('panel.layout', ['panelTitle' => 'Pegawai BPS', 'pageTitle' => 'Daftar Mitra'])

@section('menu')
    @include('panel.pegawai.menu')
@endsection

@section('content')
    <div class="card" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
        <div class="card-h" style="margin:0"><span class="dot"></span> Daftar Mitra ({{ $mitraStats->total() }})</div>
        <form method="GET" action="/pegawai/mitra" style="display:flex;gap:8px;align-items:center;margin:0">
            <input name="search" value="{{ $search }}" placeholder="Cari mitra" style="min-width:240px;">
            <button type="submit">Cari</button>
            @if ($search !== '')
                <a class="btn btn-grey" href="/pegawai/mitra">Reset</a>
            @endif
        </form>
    </div>

    <div class="mitra-list">
        @forelse ($mitraStats as $mitra)
            @php
                $performance = $mitra->performance;
                $timelinessColor = $performance['timeliness_score'] >= 80 ? '#047857' : ($performance['timeliness_score'] >= 60 ? '#b45309' : '#be123c');
            @endphp
            <div class="mitra-row">
                <div class="mitra-person">
                    <div>
                        <div class="mitra-name">{{ $mitra->name }}</div>
                        <div class="muted" style="font-size:11.5px">{{ $mitra->is_active ? 'Aktif' : 'Nonaktif' }} - {{ $performance['total_entries'] }} entri</div>
                    </div>
                </div>

                <div class="mitra-timeliness">
                    <b style="color:{{ $timelinessColor }}">{{ $performance['timeliness_score'] }}%</b>
                    <small>Ketepatan Waktu ({{ $performance['on_time'] }}/{{ $performance['total_assignments'] }})</small>
                </div>

                <a class="btn" href="/pegawai/mitra/{{ $mitra->id }}" style="padding:7px 14px;">Detail</a>
            </div>
        @empty
            <div class="card muted">Mitra tidak ditemukan.</div>
        @endforelse
    </div>

    @if ($mitraStats->hasPages())
        <div class="card">{{ $mitraStats->links() }}</div>
    @endif
@endsection

@push('head')
<style>
    .mitra-list {
        display: grid;
        gap: 10px;
    }

    .mitra-row {
        display: grid;
        grid-template-columns: minmax(200px, 1fr) 200px 72px;
        gap: 14px;
        align-items: center;
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 10px 14px;
        box-shadow: 0 2px 8px var(--shadow);
    }

    .mitra-person {
        display: flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
    }

    .mitra-name {
        font-weight: 700;
        color: var(--brand-dark);
        font-size: 13px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .mitra-timeliness {
        background: var(--soft2);
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: 7px 12px;
        line-height: 1.2;
        text-align: center;
    }

    .mitra-timeliness b {
        display: block;
        font-size: 16px;
    }

    .mitra-timeliness small {
        color: var(--muted);
        font-size: 11px;
    }

    .mitra-row > .btn {
        justify-self: end;
        min-width: 72px;
        text-align: center;
    }

    @media (max-width: 1060px) {
        .mitra-row {
            grid-template-columns: 1fr;
            align-items: stretch;
        }

        .mitra-timeliness {
            text-align: left;
        }
    }
</style>
@endpush
