@php
    $progress = $survey->total_target > 0 ? min(100, round(($survey->entries_count / $survey->total_target) * 100, 1)) : 0;
    $statusPill = match ($survey->status) {
        'Selesai' => 'pill-green',
        'Draft' => 'pill-amber',
        default => 'pill-blue',
    };
@endphp

<div class="entity-card">
    <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
        <div style="min-width:0;">
            <div class="entity-title" style="font-weight:800;font-size:16px;">{{ $survey->title }}</div>
            <div class="muted entity-team">{{ $survey->team->name }}</div>
        </div>
        <span class="pill {{ $statusPill }}" style="flex:0 0 auto;">{{ $survey->status }}</span>
    </div>

    <div class="summary-grid">
        <div class="summary-item"><div class="muted">Target</div><strong>{{ $survey->total_target }}</strong></div>
        <div class="summary-item"><div class="muted">Progress</div><strong>{{ $survey->entries_count }}</strong></div>
        <div class="summary-item"><div class="muted">Capaian</div><strong>{{ $progress }}%</strong></div>
    </div>

    <div class="entity-footer">
        <div class="bar"><i style="width: {{ $progress }}%;"></i></div>
        <div class="muted" style="margin-top:8px;">{{ $survey->start_date->format('d/m/Y') }} - {{ $survey->end_date->format('d/m/Y') }}</div>
    </div>

    <div class="entity-overlay">
        <a class="btn" href="/pegawai/surveys/{{ $survey->id }}">Detail Survei</a>
        @if ($survey->status === 'Selesai')
            <span class="pill pill-green">Terkunci</span>
        @else
            <a class="btn btn-grey" href="/pegawai/surveys/{{ $survey->id }}/edit">Edit Survei</a>
        @endif
    </div>
</div>
