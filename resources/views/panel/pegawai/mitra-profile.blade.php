@extends('panel.layout', ['panelTitle' => 'Pegawai BPS', 'pageTitle' => 'Detail Mitra'])

@section('menu')
    @include('panel.pegawai.menu')
@endsection

@section('content')
    @php
        $total = $mitra->assignments->count();
        $lateCount = $mitra->assignments->filter(fn ($item) => $item->is_late)->count();
        $onTimeCount = max(0, $total - $lateCount);
        $rate = $total > 0 ? round(($onTimeCount / $total) * 100, 1) : 0;
        $inisial = collect(explode(' ', $mitra->name))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->join('');
    @endphp
    <div class="card">
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:12px;flex-wrap:wrap">
            <span style="width:54px;height:54px;border-radius:14px;background:#1d4ed8;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:20px;flex:0 0 auto">{{ strtoupper($inisial) }}</span>
            <div>
                <h3 style="margin:0;font-size:19px">{{ $mitra->name }}</h3>
                <div class="muted">{{ $mitra->email }} - Mitra Statistik</div>
            </div>
            <a class="btn btn-grey" href="/pegawai/mitra" style="margin-left:auto">Kembali</a>
        </div>

        <div class="summary-grid" style="grid-template-columns:repeat(4,1fr)">
            <div class="summary-item"><div class="muted">Jumlah Survei</div><strong>{{ $total }}</strong></div>
            <div class="summary-item"><div class="muted">Tepat Waktu</div><strong style="color:#047857">{{ $onTimeCount }}</strong></div>
            <div class="summary-item"><div class="muted">Terlambat</div><strong style="color:#be123c">{{ $lateCount }}</strong></div>
            <div class="summary-item"><div class="muted">% Ketepatan</div><strong style="color:#1d4ed8">{{ $rate }}%</strong></div>
        </div>
    </div>

    @php
        $runningAssignments = $mitra->assignments->filter(fn ($assignment) => $assignment->survey->status !== 'Selesai');
        $completedAssignments = $mitra->assignments->filter(fn ($assignment) => $assignment->survey->status === 'Selesai');
    @endphp

    @foreach ([['title' => 'Survei Berjalan', 'items' => $runningAssignments], ['title' => 'Survei Selesai', 'items' => $completedAssignments]] as $group)
        <div class="card" style="padding:14px 16px">
            <div class="card-h" style="margin-bottom:10px"><span class="dot"></span>{{ $group['title'] }} ({{ $group['items']->count() }})</div>
            <div class="survey-history-list">
                @forelse ($group['items'] as $assignment)
                    @php
                        $late = $assignment->is_late;
                        $progress = $assignment->target > 0 ? min(100, round(($assignment->current_progress / $assignment->target) * 100, 1)) : 0;
                    @endphp
                    <div class="survey-history-row">
                        <div class="survey-title">
                            <strong>{{ $assignment->survey->title }}</strong>
                            <span>{{ $assignment->survey->start_date->format('d/m/Y') }} - {{ $assignment->survey->end_date->format('d/m/Y') }}</span>
                        </div>
                        <div class="survey-mini"><small>Target</small><b>{{ $assignment->target }}</b></div>
                        <div class="survey-mini"><small>Progress</small><b>{{ $assignment->current_progress }}</b></div>
                        <div class="survey-progress">
                            <div class="bar"><i style="width: {{ $progress }}%;"></i></div>
                            <small>{{ $progress }}%</small>
                        </div>
                        <span class="pill {{ $late ? 'pill-rose' : 'pill-green' }}">{{ $late ? 'Terlambat' : 'Tepat Waktu' }}</span>
                    </div>
                @empty
                    <div class="muted" style="padding:8px 0">Tidak ada {{ strtolower($group['title']) }}.</div>
                @endforelse
            </div>
        </div>
    @endforeach
@endsection

@push('head')
<style>
    .survey-history-list {
        display: grid;
        gap: 8px;
    }

    .survey-history-row {
        display: grid;
        grid-template-columns: minmax(260px, 1.7fr) 82px 92px minmax(180px, 1fr) 100px;
        gap: 12px;
        align-items: center;
        padding: 10px 12px;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: var(--soft2);
    }

    .survey-title {
        display: grid;
        gap: 3px;
        min-width: 0;
    }

    .survey-title strong {
        color: var(--brand-dark);
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .survey-title span,
    .survey-progress small,
    .survey-mini small {
        color: var(--muted);
        font-size: 11.5px;
    }

    .survey-mini {
        border: 1px solid var(--line);
        border-radius: 10px;
        background: var(--card);
        padding: 7px 10px;
        line-height: 1.1;
    }

    .survey-mini b {
        display: block;
        color: var(--brand-dark);
        font-size: 15px;
    }

    .survey-progress {
        display: grid;
        grid-template-columns: 1fr 42px;
        gap: 8px;
        align-items: center;
    }

    .survey-history-row > .pill {
        justify-self: center;
        min-width: 92px;
        text-align: center;
    }

    @media (max-width: 980px) {
        .survey-history-row {
            grid-template-columns: 1fr;
        }

        .survey-history-row > .pill {
            justify-self: start;
        }
    }
</style>
@endpush
