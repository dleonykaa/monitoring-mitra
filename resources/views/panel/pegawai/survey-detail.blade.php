@extends('panel.layout', ['panelTitle' => 'Pegawai BPS', 'pageTitle' => 'Detail Survei'])

@section('menu')
    @include('panel.pegawai.menu')
@endsection

@section('content')
    @php
        $progress = $survey->assignments->sum('current_progress');
        $percentage = $survey->total_target > 0 ? min(100, round(($progress / $survey->total_target) * 100, 1)) : 0;
        $sisaTarget = max(0, $survey->total_target - $progress);
    @endphp

    <div class="survey-detail-hero">
        <div>
            <div class="card-h" style="margin:0"><span class="dot"></span>Detail Survei</div>
            <h2>{{ $survey->title }}</h2>
            <p>{{ $survey->description ?: 'Tidak ada deskripsi survei.' }}</p>
        </div>
        <div class="survey-detail-actions">
            <span class="pill {{ $survey->status === 'Selesai' ? 'pill-green' : ($survey->status === 'Draft' ? 'pill-amber' : 'pill-blue') }}">{{ $survey->status }}</span>
            @if ($survey->status !== 'Selesai')
                <a class="btn" href="/pegawai/surveys/{{ $survey->id }}/edit">Edit Survei</a>
            @else
                <span class="pill pill-green">Terkunci</span>
            @endif
        </div>
    </div>

    <div class="grid g4">
        <div class="stat st-blue"><div class="s-label">Total Target</div><div class="s-val">{{ number_format($survey->total_target) }}</div><div class="s-foot">Akumulasi target mitra</div></div>
        <div class="stat st-green"><div class="s-label">Progress Masuk</div><div class="s-val">{{ number_format($progress) }}</div><div class="s-foot">{{ $percentage }}% tercapai</div></div>
        <div class="stat st-indigo"><div class="s-label">Sisa Target</div><div class="s-val">{{ number_format($sisaTarget) }}</div><div class="s-foot">Belum masuk</div></div>
        <div class="stat st-cyan"><div class="s-label">Mitra Dialokasikan</div><div class="s-val">{{ $survey->assignments->count() }}</div><div class="s-foot">{{ $survey->start_date->format('d/m/Y') }} - {{ $survey->end_date->format('d/m/Y') }}</div></div>
    </div>

    <div class="card survey-progress-panel">
        <div class="card-h"><span class="dot"></span>Progress Keseluruhan</div>
        <div class="overall-progress">
            <div>
                <strong>{{ $percentage }}%</strong>
                <span>{{ number_format($progress) }} dari {{ number_format($survey->total_target) }} target</span>
            </div>
            <div class="bar"><i style="width:{{ $percentage }}%"></i></div>
        </div>
    </div>

    <div class="card">
        <div class="card-h"><span class="dot"></span>Progress Seluruh Mitra</div>

        <div class="survey-mitra-list">
            @forelse ($survey->assignments as $assignment)
                @php
                    $mitraPct = $assignment->target > 0 ? min(100, round(($assignment->current_progress / $assignment->target) * 100, 1)) : 0;
                    $done = $assignment->current_progress >= $assignment->target;
                @endphp
                <div class="survey-mitra-row">
                    <div class="survey-mitra-rank">#{{ $loop->iteration }}</div>
                    <div class="survey-mitra-main">
                        <div class="survey-mitra-head">
                            <div>
                                <strong>{{ $assignment->mitra->name }}</strong>
                                <span>{{ $assignment->current_progress }} dari {{ $assignment->target }} target</span>
                            </div>
                            <span class="pill {{ $done ? 'pill-green' : 'pill-blue' }}">{{ $done ? 'Target tercapai' : $mitraPct.'%' }}</span>
                        </div>
                        <div class="bar"><i style="width:{{ $mitraPct }}%"></i></div>
                    </div>
                </div>
            @empty
                <div class="chart-empty">Belum ada alokasi mitra untuk survei ini.</div>
            @endforelse
        </div>
    </div>
@endsection

@push('head')
<style>
    .survey-detail-hero {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        border: 1px solid var(--line);
        border-radius: 16px;
        background: var(--card);
        padding: 18px;
    }

    .survey-detail-hero h2 {
        color: var(--brand-dark);
        font-size: 23px;
        line-height: 1.2;
        margin: 8px 0 6px;
    }

    .survey-detail-hero p {
        color: var(--muted);
        line-height: 1.55;
        margin: 0;
        max-width: 72ch;
    }

    .survey-detail-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .survey-progress-panel {
        display: grid;
        gap: 12px;
    }

    .overall-progress {
        display: grid;
        grid-template-columns: 150px minmax(0, 1fr);
        gap: 18px;
        align-items: center;
    }

    .overall-progress strong {
        color: var(--brand);
        display: block;
        font-size: 34px;
        line-height: 1;
    }

    .overall-progress span {
        color: var(--muted);
        display: block;
        font-size: 12.5px;
        margin-top: 5px;
    }

    .survey-mitra-list {
        display: grid;
        gap: 8px;
    }

    .survey-mitra-row {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 10px;
        align-items: center;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: var(--soft2);
        padding: 10px;
    }

    .survey-mitra-rank {
        color: var(--brand);
        font-size: 13px;
        font-weight: 900;
        text-align: center;
    }

    .survey-mitra-main {
        display: grid;
        gap: 8px;
    }

    .survey-mitra-head {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .survey-mitra-main strong {
        color: var(--brand-dark);
        display: block;
        font-size: 13.5px;
    }

    .survey-mitra-main span {
        color: var(--muted);
        font-size: 11.5px;
    }

    @media (max-width: 760px) {
        .survey-detail-hero,
        .overall-progress {
            grid-template-columns: 1fr;
        }

        .survey-detail-hero {
            display: grid;
        }

        .survey-detail-actions {
            justify-content: flex-start;
        }

        .survey-mitra-row {
            grid-template-columns: 1fr;
        }

        .survey-mitra-rank {
            text-align: left;
        }
    }
</style>
@endpush
