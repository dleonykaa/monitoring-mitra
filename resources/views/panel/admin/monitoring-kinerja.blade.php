@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Kinerja Mitra'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@section('content')
    <div class="card">
        <div class="card-h"><span class="dot"></span>Peringkat Kinerja Mitra</div>
        <a class="btn" href="/admin/monitoring/kinerja/export">Export Excel</a>
    </div>

    <div class="card" style="padding:0;overflow:hidden">
        <div style="overflow-x:auto">
            <table class="rich-table" style="min-width:720px">
                <tr><th>Mitra</th><th>Survei</th><th>Progress</th><th>Target</th><th>Persen</th><th>Kategori</th><th style="text-align:center">Aksi</th></tr>
                @forelse ($rankings as $r)
                    @php($pct = $r->target > 0 ? round(($r->current_progress / $r->target) * 100, 2) : 0)
                    <tr>
                        <td style="white-space:nowrap">{{ $r->mitra->name }}</td>
                        <td>{{ $r->survey->title }}</td>
                        <td>{{ $r->current_progress }}</td>
                        <td>{{ $r->target }}</td>
                        <td>{{ $pct }}%</td>
                        <td style="white-space:nowrap">
                            <span class="pill {{ $pct >= 80 ? 'pill-green' : ($pct >= 60 ? 'pill-amber' : 'pill-rose') }}">{{ $pct >= 80 ? 'Baik' : ($pct >= 60 ? 'Cukup' : 'Buruk') }}</span>
                        </td>
                        <td style="text-align:center;white-space:nowrap"><a class="btn" href="/admin/monitoring/mitra/{{ $r->mitra->id }}">Profil</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted" style="text-align:center;padding:24px">Belum ada alokasi mitra.</td></tr>
                @endforelse
            </table>
        </div>
    </div>

    @if ($rankings->hasPages())
        <div class="card">{{ $rankings->links() }}</div>
    @endif
@endsection
