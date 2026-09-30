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
                            <thead><tr><th>Sampai tanggal</th><th class="num">Target</th><th>Status</th><th>Mitra di bawah target</th><th class="act"><span class="sr-only">Aksi</span></th></tr></thead>
                            <tbody>
                                @foreach ($survey->checkpoints as $checkpoint)
                                    @php
                                        $isPassed = $checkpoint->checkpoint_date->toDateString() < $today;
                                        $belowCount = $mitraProgress->filter(fn ($mitra) => $mitra['target'] > 0 && $mitra['percent'] < $checkpoint->target_percentage)->count();
                                    @endphp
                                    <tr>
                                        <td><b style="font-weight:600">{{ $longDate($checkpoint->checkpoint_date) }}</b></td>
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
                <a class="b b-primary" href="/admin/surveys/{{ $survey->id }}">Kembali ke detail survei</a>
            </div>
        </section>

        <form class="pnl" method="POST" action="/admin/surveys/{{ $survey->id }}/checkpoints">
            @csrf
            <div class="pnl-h">
                <div>
                    <h2>Tambah checkpoint</h2>
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
                    @error('target_percentage')<span class="hint" style="color:var(--st-late-ink);font-weight:600">{{ $message }}</span>@enderror
                </label>
                <button type="submit" class="b b-primary" style="justify-self:start">Simpan checkpoint</button>
            </div>
        </form>
    </div>
</div>
@endsection
