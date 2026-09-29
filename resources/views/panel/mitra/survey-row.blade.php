@php
    $progress = $assignment->target > 0 ? min(100, round(($assignment->current_progress / $assignment->target) * 100, 1)) : 0;
    $late = $assignment->survey->end_date->isPast() && $assignment->current_progress < $assignment->target;
    $canAddProgress = $assignment->survey->status !== 'Selesai' && $assignment->current_progress < $assignment->target;
@endphp
<div class="mitra-survey-row">
    <div class="mitra-survey-title">
        <strong>{{ $assignment->survey->title }}</strong>
        <span>{{ $assignment->survey->start_date->format('d/m/Y') }} - {{ $assignment->survey->end_date->format('d/m/Y') }}</span>
    </div>
    <div class="survey-mini"><small>Target</small><b>{{ $assignment->target }}</b></div>
    <div class="survey-mini"><small>Progress</small><b>{{ $assignment->current_progress }}</b></div>
    <div class="survey-progress">
        <div class="bar"><i style="width:{{ $progress }}%"></i></div>
        <small>{{ $progress }}%</small>
    </div>
    <span class="pill {{ $late ? 'pill-rose' : 'pill-green' }}">{{ $late ? 'Terlambat' : 'Tepat Waktu' }}</span>
    <div class="survey-action">
        @if ($canAddProgress)
            <a class="btn" href="/mitra/surveys/{{ $assignment->survey_id }}/entries/create">Tambah Progress</a>
        @else
            <span class="muted">Selesai</span>
        @endif
    </div>
</div>
