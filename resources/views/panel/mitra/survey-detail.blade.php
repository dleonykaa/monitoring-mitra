@extends('panel.layout', ['panelTitle' => 'Mitra BPS', 'pageTitle' => 'Detail Survei'])

@section('menu')
    @include('panel.mitra.menu')
@endsection

@section('content')
    @php
        $survey = $assignment->survey;
        $progress = $assignment->target > 0 ? min(100, round(($assignment->current_progress / $assignment->target) * 100, 1)) : 0;
        $late = $survey->end_date->isPast() && $assignment->current_progress < $assignment->target;
        $isRunning = $survey->status !== 'Selesai';
    @endphp

    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
            <div>
                <div class="card-h" style="margin-bottom:4px"><span class="dot"></span>{{ $survey->title }}</div>
                <div class="muted">{{ $survey->description ?: 'Tidak ada deskripsi.' }}</div>
                <div class="muted" style="margin-top:4px">{{ $survey->start_date->format('d/m/Y') }} - {{ $survey->end_date->format('d/m/Y') }}</div>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
                <span class="pill {{ $late ? 'pill-rose' : 'pill-green' }}">{{ $late ? 'Terlambat' : 'Tepat Waktu' }}</span>
                @if ($isRunning && $assignment->current_progress < $assignment->target)
                    <a class="btn" href="/mitra/surveys/{{ $survey->id }}/entries/create">Tambah Progress</a>
                @endif
            </div>
        </div>
        <div class="summary-grid" style="grid-template-columns:repeat(3,1fr);margin-top:14px">
            <div class="summary-item"><div class="muted">Target</div><strong>{{ $assignment->target }}</strong></div>
            <div class="summary-item"><div class="muted">Progress</div><strong>{{ $assignment->current_progress }}</strong></div>
            <div class="summary-item"><div class="muted">Persentase</div><strong>{{ $progress }}%</strong></div>
        </div>
        <div style="margin-top:12px"><div class="bar"><i style="width:{{ $progress }}%"></i></div></div>
    </div>

    <div class="card">
        <div class="card-h"><span class="dot"></span> Variabel Validasi</div>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
            @forelse ($survey->variables as $variable)
                <span class="pill pill-blue">{{ $variable->name }} ({{ $variable->data_type }})</span>
            @empty
                <span class="muted">Survei ini tidak memiliki variabel validasi.</span>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="card-h"><span class="dot"></span> Entri Terbaru</div>
        @include('panel.mitra.entries-table', ['entries' => $recentEntries, 'compact' => true])
    </div>
@endsection

@include('panel.mitra.styles')
