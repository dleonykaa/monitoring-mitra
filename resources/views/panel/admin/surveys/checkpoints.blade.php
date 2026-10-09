@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Checkpoint Survei'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $longDate = fn ($date) => $date->locale('id')->translatedFormat('l, d M Y');
    $today = now('Asia/Jakarta')->toDateString();
    $mitraCount = $mitraProgress->count();
    $unit = $survey->isCapi() ? 'dokumen' : 'ruta';
    // Urutan checkpoint per tanggal, dipakai form untuk membatasi target agar selalu naik.
    $checkpointData = $survey->checkpoints->values()->map(fn ($checkpoint, $index) => [
        'number' => $index + 1,
        'date' => $checkpoint->checkpoint_date->toDateString(),
        'target' => (int) $checkpoint->target_percentage,
    ]);
@endphp

@section('content')
<div class="ui">
    @include('panel.admin.surveys.setup-header', ['survey' => $survey, 'step' => 'checkpoints'])

    <div class="cols cols-side">
        <section class="pnl flush" aria-labelledby="checkpointTitle">
            <div class="pnl-h">
                <div>
                    <h2 id="checkpointTitle">Target bertahap <span class="num-chip">{{ $survey->checkpoints->count() }}</span></h2>
                    <p>Setiap checkpoint menetapkan persentase target yang harus dicapai tiap mitra sampai tanggal itu. Sehari setelah tanggalnya lewat, mitra yang capaiannya masih kurang otomatis mendapat notifikasi.</p>
                </div>
            </div>
            <div class="pnl-b">
                @if ($survey->checkpoints->isEmpty())
                    <div class="empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 22V4a1 1 0 0 1 1-1h11l-2 4 2 4H5"/></svg>
                        <b>Belum ada checkpoint</b>
                        <span>Tanpa checkpoint, survei dinilai dari porsi waktu yang sudah berjalan. Tambahkan target bertahap dari panel samping.</span>
                    </div>
                @else
                    <div class="tbl-wrap">
                        <table class="tbl stack" style="min-width:560px">
                            <thead><tr><th>Checkpoint</th><th>Sampai tanggal</th><th class="num">Target</th><th>Status</th><th>Mitra di bawah target</th><th class="act"><span class="sr-only">Aksi</span></th></tr></thead>
                            <tbody>
                                @foreach ($survey->checkpoints as $checkpoint)
                                    @php
                                        $isPassed = $checkpoint->checkpoint_date->toDateString() < $today;
                                        $belowCount = $mitraProgress->filter(fn ($mitra) => $mitra['target'] > 0 && $mitra['percent'] < $checkpoint->target_percentage)->count();
                                    @endphp
                                    <tr>
                                        <td><b>Checkpoint {{ $loop->iteration }}</b></td>
                                        <td>{{ $longDate($checkpoint->checkpoint_date) }}</td>
                                        <td class="num"><b>{{ $checkpoint->target_percentage }}%</b></td>
                                        <td>
                                            @if ($isPassed)
                                                <span class="bdg bdg-dot bdg-gray">Sudah lewat</span>
                                            @elseif ($checkpoint->checkpoint_date->toDateString() === $today)
                                                <span class="bdg bdg-dot bdg-amber">Hari ini</span>
                                            @else
                                                <span class="bdg bdg-dot bdg-blue">Mendatang</span>
                                            @endif
                                        </td>
                                        <td class="muted-cell">
                                            @if ($mitraCount === 0)
                                                {{ $survey->isCapi() ? 'Belum ada data FASIH' : 'Belum ada alokasi mitra' }}
                                            @else
                                                <b style="color:var(--text)">{{ $fmt($belowCount) }}</b> dari {{ $fmt($mitraCount) }} mitra{{ $isPassed ? '' : ' (saat ini)' }}
                                                @if ($checkpoint->notified_at)<small style="display:block">Notifikasi terkirim {{ $checkpoint->notified_at->locale('id')->translatedFormat('d M Y') }}</small>@endif
                                            @endif
                                        </td>
                                        <td class="act">
                                            <form method="POST" action="/admin/surveys/{{ $survey->id }}/checkpoints/{{ $checkpoint->id }}" data-confirm="Hapus checkpoint {{ $checkpoint->checkpoint_date->locale('id')->translatedFormat('d M Y') }}?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="b b-danger-soft b-sm b-icon" aria-label="Hapus checkpoint {{ $checkpoint->checkpoint_date->locale('id')->translatedFormat('d M Y') }}" title="Hapus checkpoint">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="pnl-f">
                @if ($survey->isCapi())
                    <span>Capaian mitra CAPI dihitung dari data FASIH terbaru.</span>
                @else
                    <a class="b b-ghost" href="/admin/surveys/{{ $survey->id }}/assignments">Kembali ke alokasi mitra</a>
                @endif
                @if ($survey->status === 'Draft' && ! $survey->isCapi())
                    {{-- Langkah terakhir setup PAPI: checkpoint opsional, lalu survei dijalankan. --}}
                    <form method="POST" action="/admin/surveys/{{ $survey->id }}/finish-setup" data-confirm="Jalankan survei ini? Mitra yang dialokasikan akan langsung bisa mengisi progres.">
                        @csrf
                        <button type="submit" class="b b-primary" @disabled(! $survey->assignments()->exists())>Jalankan survei</button>
                    </form>
                @else
                    <a class="b b-primary" href="/admin/surveys/{{ $survey->id }}">Kembali ke detail survei</a>
                @endif
            </div>
        </section>

        <form class="pnl" method="POST" action="/admin/surveys/{{ $survey->id }}/checkpoints" id="checkpointForm"
              data-checkpoints="{{ json_encode($checkpointData) }}">
            @csrf
            <div class="pnl-h">
                <div>
                    <h2>Tambah checkpoint {{ $survey->checkpoints->count() + 1 }}</h2>
                    <p>Periode survei {{ $survey->start_date->locale('id')->translatedFormat('d M') }} – {{ $survey->end_date->locale('id')->translatedFormat('d M Y') }}.</p>
                </div>
            </div>
            <div class="pnl-b ui" style="gap:12px">
                <label class="fld">
                    <span>Sampai tanggal</span>
                    <input type="date" name="checkpoint_date" required value="{{ old('checkpoint_date') }}" min="{{ $survey->start_date->toDateString() }}" max="{{ $survey->end_date->toDateString() }}">
                    @error('checkpoint_date')<span class="hint" style="color:var(--st-late-ink);font-weight:600">{{ $message }}</span>@enderror
                </label>
                <label class="fld">
                    <span>Target capaian <small>(%)</small></span>
                    <input type="number" name="target_percentage" required min="1" max="100" value="{{ old('target_percentage') }}" placeholder="50">
                    <span class="hint">Persentase {{ $unit }} selesai dari target tiap mitra. Tanggal yang sama akan diperbarui.</span>
                    <span class="hint" id="checkpointRange" @if ($survey->checkpoints->isEmpty()) hidden @endif>
                        @if ($survey->checkpoints->isNotEmpty())
                            Harus lebih dari {{ $survey->checkpoints->last()->target_percentage }}% (target checkpoint {{ $survey->checkpoints->count() }}).
                        @endif
                    </span>
                    @error('target_percentage')<span class="hint" style="color:var(--st-late-ink);font-weight:600">{{ $message }}</span>@enderror
                </label>
                <button type="submit" class="b b-primary" style="justify-self:start">Simpan checkpoint</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Target checkpoint harus naik mengikuti urutan tanggal: batas target disesuaikan dengan tanggal yang dipilih.
    const form = document.getElementById('checkpointForm');
    const checkpoints = JSON.parse(form.dataset.checkpoints || '[]');
    const date = form.querySelector('input[name="checkpoint_date"]');
    const target = form.querySelector('input[name="target_percentage"]');
    const range = document.getElementById('checkpointRange');
    const update = function () {
        const chosen = date.value;
        const others = checkpoints.filter((checkpoint) => checkpoint.date !== chosen);
        const previous = chosen ? others.filter((checkpoint) => checkpoint.date < chosen).pop() : others[others.length - 1];
        const next = chosen ? others.find((checkpoint) => checkpoint.date > chosen) : null;
        target.min = previous ? previous.target + 1 : 1;
        target.max = next ? next.target - 1 : 100;
        const parts = [];
        if (previous) parts.push('lebih dari ' + previous.target + '% (target checkpoint ' + previous.number + ')');
        if (next) parts.push('kurang dari ' + next.target + '% (target checkpoint ' + next.number + ')');
        range.hidden = parts.length === 0;
        range.textContent = parts.length ? 'Harus ' + parts.join(' dan ') + '.' : '';
    };
    date.addEventListener('change', update);
    update();
})();
</script>
@endpush
