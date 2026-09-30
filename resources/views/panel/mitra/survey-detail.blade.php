@extends('panel.layout', ['panelTitle' => 'Mitra BPS', 'pageTitle' => 'Daftar Survei'])

@section('menu')
    @include('panel.mitra.menu')
@endsection

@include('panel.mitra.styles')

@php
    $survey = $assignment->survey;
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $target = (int) $assignment->target;
    $percent = $target > 0 ? min(100, round($statusCounts['submitted'] / $target * 100, 1)) : 0;
    $isRunning = $survey->status === 'Berjalan';
    $groups = [
        'draft' => ['Draft', 'Sudah diisi sebagian, belum dikirim. Masih bisa diubah.'],
        'open' => ['Open', 'Ruta yang dialokasikan admin dan belum diisi.'],
        'submitted' => ['Selesai', 'Sudah dikirim dan terkunci. Rinciannya ada di Data Entri.'],
    ];
    $byStatus = $entries->groupBy('entry_status');
@endphp

@section('content')
<div class="ui">
    <section class="hero-nv" aria-label="Ringkasan survei">
        <div class="top">
            <div style="min-width:0;flex:1 1 380px">
                <a class="pg-back" href="/mitra/surveys{{ $isRunning ? '' : '?status=Selesai' }}" style="color:var(--navy-muted)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    Daftar survei
                </a>
                <h1>{{ $survey->title }}</h1>
                <div class="meta">
                    <span class="chip">PAPI</span>
                    <span>{{ $survey->start_date->locale('id')->translatedFormat('d M') }} – {{ $survey->end_date->locale('id')->translatedFormat('d M Y') }}</span>
                    <span>·</span>
                    <span>{{ $survey->variables->count() }} variabel isian + foto bukti</span>
                </div>
            </div>
            @if ($canAddEntry)
                <a class="b b-light" href="/mitra/surveys/{{ $survey->id }}/entries/create">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Isi ruta baru
                </a>
            @endif
        </div>
        <div class="ledger">
            <div class="big">
                <div class="lbl">Progres Anda</div>
                <div class="val">{{ number_format($percent, 1, ',', '.') }}%</div>
                <div class="sub"><b>{{ $fmt($statusCounts['submitted']) }}</b> dari {{ $fmt($target) }} ruta selesai</div>
            </div>
            <div>
                <div class="track" aria-hidden="true">
                    <i style="width:{{ $target ? $statusCounts['submitted'] / $target * 100 : 0 }}%;background:var(--st-submit)"></i>
                    <i style="width:{{ $target ? $statusCounts['draft'] / $target * 100 : 0 }}%;background:var(--st-draft)"></i>
                    <i style="width:{{ $target ? $statusCounts['open'] / $target * 100 : 0 }}%;background:var(--st-open)"></i>
                </div>
                <div class="legend" style="grid-template-columns:repeat(3,minmax(0,1fr))">
                    <div><div class="k"><i class="sw submit"></i>Selesai</div><div class="v">{{ $fmt($statusCounts['submitted']) }}</div></div>
                    <div><div class="k"><i class="sw draft"></i>Draft</div><div class="v">{{ $fmt($statusCounts['draft']) }}</div></div>
                    <div><div class="k"><i class="sw open"></i>Belum diisi</div><div class="v">{{ $fmt($statusCounts['open']) }}</div></div>
                </div>
            </div>
        </div>
    </section>

    @if ($errors->has('entry'))
        <div class="note note-amber" role="alert">{{ $errors->first('entry') }}</div>
    @endif
    @unless ($isRunning)
        <div class="note note-blue" role="status">Survei ini sudah {{ strtolower($survey->status) }}. Entri tidak bisa diubah lagi.</div>
    @endunless
    @if ($isRunning && $passedCheckpoint && $assignment->isBelow($passedCheckpoint))
        <div class="note note-amber" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
            <span>Capaian Anda <b>{{ number_format($assignment->progressPercent(), 1, ',', '.') }}%</b>, di bawah target <b>{{ $passedCheckpoint->target_percentage }}%</b> per {{ $passedCheckpoint->checkpoint_date->locale('id')->translatedFormat('d M Y') }}. Kurang {{ $fmt(max(1, (int) ceil($target * $passedCheckpoint->target_percentage / 100) - (int) $assignment->current_progress)) }} ruta untuk mencapainya.</span>
        </div>
    @endif
    @if ($isRunning && $nextCheckpoint)
        <div class="note note-blue" role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 22V4a1 1 0 0 1 1-1h11l-2 4 2 4H5"/></svg>
            <span>Target berikutnya: <b>{{ $nextCheckpoint->target_percentage }}%</b> ({{ $fmt((int) ceil($target * $nextCheckpoint->target_percentage / 100)) }} dari {{ $fmt($target) }} ruta) sampai {{ $nextCheckpoint->checkpoint_date->locale('id')->translatedFormat('l, d M Y') }}.</span>
        </div>
    @endif

    @foreach ($groups as $status => [$label, $hint])
        @php($items = $byStatus->get($status, collect()))
        @continue($items->isEmpty() && $status !== 'draft')
        <section class="pnl" aria-labelledby="group-{{ $status }}">
            <div class="pnl-h">
                <div>
                    <h2 id="group-{{ $status }}">{{ $label }} <span class="num-chip">{{ $items->count() }}</span></h2>
                    <p>{{ $hint }}</p>
                </div>
            </div>
            <div class="pnl-b">
                @if ($items->isEmpty())
                    <p style="margin:0;font-size:12.5px;color:var(--muted)">Tidak ada draft. {{ $canAddEntry ? 'Mulai dengan "Isi ruta baru".' : '' }}</p>
                @else
                    <div class="mt-entries">
                        @foreach ($items as $entry)
                            <div class="mt-entry">
                                @if ($entry->evidence_photo_path)
                                    <img src="{{ asset('storage/'.$entry->evidence_photo_path) }}" alt="Foto bukti ruta {{ $entry->no_urut_ruta }}" loading="lazy" onerror="this.onerror=null;this.alt='';this.title='Foto tidak ditemukan';this.classList.add('img-missing');this.src='data:image/gif;base64,R0lGODlhAQABAAAAACw=';">
                                @else
                                    <span class="ph" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></span>
                                @endif
                                <div style="min-width:0">
                                    <b>Ruta {{ $entry->no_urut_ruta ?: 'belum bernomor' }}</b>
                                    <small>
                                        {{ $entry->sls ? 'SLS '.$entry->sls : 'SLS belum diisi' }}
                                        · {{ $entry->village?->name ?? $entry->district?->name ?? 'Wilayah belum diisi' }}
                                        · {{ ($entry->submitted_at ?? $entry->updated_at)->locale('id')->diffForHumans() }}
                                    </small>
                                </div>
                                <div class="side">
                                    @if ($isRunning && $entry->isEditableByMitra())
                                        <a class="b {{ $status === 'draft' ? 'b-primary' : 'b-soft' }} b-sm" href="/mitra/entries/{{ $entry->id }}/edit">{{ $status === 'draft' ? 'Lanjutkan' : 'Mulai isi' }}</a>
                                    @elseif ($status === 'submitted')
                                        <span class="bdg bdg-dot bdg-green">Terkirim</span>
                                        <a class="b b-soft b-sm" href="/mitra/data-entri/{{ $entry->id }}">Lihat</a>
                                    @else
                                        <span class="bdg bdg-gray">Terkunci</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endforeach
</div>
@endsection
