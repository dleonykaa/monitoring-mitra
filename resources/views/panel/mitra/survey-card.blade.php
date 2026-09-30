{{-- Kartu satu survei yang ditugaskan. Butuh $item (MitraSurveyHoldings) dan $counts (jumlah entri per status, PAPI). $actionLabel opsional: tombol menuju halaman survei. --}}
@php
    $survey = $item['survey'];
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $daysLeft = (int) today()->diffInDays($survey->end_date, false);
    $isPapi = ! $survey->isCapi();
    $isRunning = $survey->status === 'Berjalan';
    $actionLabel = $actionLabel ?? null;
    $done = $isPapi ? (int) $counts->get('submitted', 0) : (int) $item['progress'];
    $draft = $isPapi ? (int) $counts->get('draft', 0) : 0;
    $target = (int) $item['target'];
    $open = max(0, $target - $done - $draft);
    $share = fn ($part) => $target > 0 ? min(100, $part / $target * 100) : 0;
@endphp
<article class="mt-card">
    <div>
        <h3>{{ $survey->title }}</h3>
        <div class="meta" style="margin-top:6px">
            <span class="tag">{{ $survey->typeLabel() }}</span>
            <span>{{ $survey->start_date->locale('id')->translatedFormat('d M') }} – {{ $survey->end_date->locale('id')->translatedFormat('d M Y') }}</span>
            @if ($isRunning)
                <span class="{{ $daysLeft < 0 ? 'late' : '' }}">{{ $daysLeft < 0 ? 'Lewat '.abs($daysLeft).' hari' : ($daysLeft === 0 ? 'Berakhir hari ini' : 'Sisa '.$daysLeft.' hari') }}</span>
            @else
                <span>Selesai</span>
            @endif
        </div>
    </div>

    @php
        $cardCheckpoint = $isRunning ? $survey->passedCheckpoint() : null;
    @endphp
    @if ($cardCheckpoint && $target > 0 && $share($done) < $cardCheckpoint->target_percentage)
        <p class="late" style="margin:0;font-size:12px;color:var(--st-late-ink);font-weight:600">Di bawah target {{ $cardCheckpoint->target_percentage }}% per {{ $cardCheckpoint->checkpoint_date->locale('id')->translatedFormat('d M') }}</p>
    @endif

    <div class="mt-big">
        <b>{{ number_format($share($done), 1, ',', '.') }}%</b>
        <span>{{ $fmt($done) }} dari {{ $fmt($target) }} {{ $item['unit'] }} selesai</span>
    </div>
    <div class="mt-track" aria-hidden="true">
        <i style="width:{{ $share($done) }}%;background:var(--st-submit)"></i>
        <i style="width:{{ $share($draft) }}%;background:var(--st-draft)"></i>
    </div>

    @if ($isPapi)
        <div class="mt-counts">
            <div><small><i class="mt-sw" style="background:var(--st-open)"></i>Belum diisi</small><b>{{ $fmt($open) }}</b></div>
            <div><small><i class="mt-sw" style="background:var(--st-draft)"></i>Draft</small><b>{{ $fmt($draft) }}</b></div>
            <div><small><i class="mt-sw" style="background:var(--st-submit)"></i>Selesai</small><b>{{ $fmt($done) }}</b></div>
        </div>
        <div class="foot">
            <p>{{ ! $isRunning ? 'Survei sudah ditutup' : ($draft > 0 ? $draft.' draft menunggu dilengkapi' : ($open > 0 ? $open.' ruta belum diisi' : 'Semua ruta sudah terisi')) }}</p>
            @if ($actionLabel)
                <a class="b {{ $isRunning ? 'b-primary' : 'b-soft' }} b-sm" href="/mitra/surveys/{{ $survey->id }}">{{ $actionLabel }}</a>
            @endif
        </div>
    @else
        <div class="foot">
            <p>Pendataan lewat aplikasi FASIH. Progres diperbarui admin dari data FASIH.</p>
        </div>
    @endif
</article>
