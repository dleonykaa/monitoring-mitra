{{-- Header halaman pengaturan survei + stepper. Butuh $survey dan $step (info|variables|assignments|checkpoints). --}}
@php
    $isDraft = $survey->status === 'Draft';
    $statusBadge = ['Berjalan' => 'bdg-blue', 'Draft' => 'bdg-amber', 'Selesai' => 'bdg-green'][$survey->status] ?? 'bdg-gray';
    $setupSteps = [
        'info' => ['Informasi', 'Judul & periode', true, '/admin/surveys/'.$survey->id.'/edit'],
        'variables' => ['Form isian', $survey->variables()->count().' variabel', $survey->variables()->exists(), '/admin/surveys/'.$survey->id.'/variables'],
        'assignments' => ['Alokasi mitra', $survey->assignments()->count().' mitra', $survey->assignments()->exists(), '/admin/surveys/'.$survey->id.'/assignments'],
        'checkpoints' => ['Checkpoint', $survey->checkpoints()->count().' target · opsional', $survey->checkpoints()->exists(), '/admin/surveys/'.$survey->id.'/checkpoints'],
    ];
    $stepNumber = array_search($step, array_keys($setupSteps), true) + 1;
@endphp

<div class="pg-head">
    <div style="min-width:0">
        <a class="pg-back" href="/admin/surveys/{{ $survey->id }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            Detail survei
        </a>
        <h1>{{ $survey->title }}</h1>
        <p style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
            <span class="tag">{{ $survey->typeLabel() }}</span>
            <span class="bdg bdg-dot {{ $statusBadge }}">{{ $survey->status }}</span>
            <span>{{ $survey->start_date->locale('id')->translatedFormat('d M Y') }} – {{ $survey->end_date->locale('id')->translatedFormat('d M Y') }}</span>
            @if ($isDraft && ! $survey->isCapi())
                <span>· Langkah {{ $stepNumber }} dari {{ count($setupSteps) }}</span>
            @endif
        </p>
    </div>
</div>

@unless ($survey->isCapi())
    <nav aria-label="Langkah pengaturan survei">
        <ol class="steps">
            @foreach ($setupSteps as $key => [$label, $detail, $isDone, $href])
                <li>
                    @if ($key === $step)
                        <span class="cur" aria-current="step">
                            <span class="n">{{ $loop->iteration }}</span>
                            <span class="lbl">{{ $label }}<small>{{ $detail }}</small></span>
                        </span>
                    @else
                        <a href="{{ $href }}" class="{{ $isDone ? 'done' : '' }}">
                            <span class="n">
                                @if ($isDone)
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    <span class="sr-only">Selesai:</span>
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </span>
                            <span class="lbl">{{ $label }}<small>{{ $detail }}</small></span>
                        </a>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endunless
