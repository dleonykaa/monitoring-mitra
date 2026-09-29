@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Detail Survei'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@section('content')
    <div class="card">
        <div class="card-h"><span class="dot"></span>{{ $survey->title }}</div>
        <div class="summary-grid">
            <div class="summary-item"><div class="muted">Status</div><strong>{{ $survey->status }}</strong></div>
            <div class="summary-item"><div class="muted">Target</div><strong>{{ $survey->total_target }}</strong></div>
            <div class="summary-item"><div class="muted">Entri</div><strong>{{ $survey->entries->count() }}</strong></div>
        </div>
    </div>

    <div class="card" style="padding:0;overflow:hidden">
        <div class="card-h" style="padding:15px 16px 0;margin-bottom:8px"><span class="dot"></span>Progres per Mitra</div>
        <div style="overflow-x:auto">
            <table class="rich-table" style="min-width:480px">
                <tr><th>Mitra</th><th>Target</th><th>Progress</th><th style="text-align:center">Aksi</th></tr>
                @forelse ($survey->assignments as $item)
                    @php($pct = $item->target > 0 ? round(($item->current_progress / $item->target) * 100) : 0)
                    <tr>
                        <td style="white-space:nowrap">{{ $item->mitra->name }}</td>
                        <td>{{ $item->target }}</td>
                        <td style="min-width:160px">
                            <div class="bar"><i style="width:{{ min(100, $pct) }}%"></i></div>
                            <div class="muted">{{ $item->current_progress }} / {{ $item->target }} ({{ $pct }}%)</div>
                        </td>
                        <td style="text-align:center;white-space:nowrap"><a class="btn" href="/admin/monitoring/mitra/{{ $item->mitra->id }}">Profil</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted" style="text-align:center;padding:24px">Belum ada mitra dialokasikan.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
