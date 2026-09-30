@extends('panel.layout', ['panelTitle' => 'Mitra BPS', 'pageTitle' => 'Dashboard'])

@section('menu')
    @include('panel.mitra.menu')
@endsection

@include('panel.mitra.styles')

@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $papiRunning = $runningItems->filter(fn ($item) => ! $item['survey']->isCapi());
    $papiTarget = (int) $papiRunning->sum('target');
    $papiDone = (int) $papiRunning->sum(fn ($item) => $entryCounts->get($item['survey']->id)?->get('submitted', 0) ?? 0);
    $papiDraft = (int) $papiRunning->sum(fn ($item) => $entryCounts->get($item['survey']->id)?->get('draft', 0) ?? 0);
    $papiPercent = $papiTarget > 0 ? min(100, round($papiDone / $papiTarget * 100, 1)) : 0;
    $firstName = explode(' ', trim(auth()->user()->name))[0];
@endphp

@section('content')
<div class="ui">
    <section class="hero-nv" aria-label="Ringkasan tugas">
        <div class="top">
            <div style="min-width:0">
                <h1>Halo, {{ $firstName }}</h1>
                <div class="meta">
                    <span><b>{{ $runningItems->count() }}</b> survei berjalan</span>
                    <span>·</span>
                    <span><b>{{ $completedItems->count() }}</b> survei selesai</span>
                </div>
            </div>
        </div>


        @if ($papiRunning->isNotEmpty())
            <div class="ledger">
                <div class="big">
                    <div class="lbl">Ruta selesai (PAPI)</div>
                    <div class="val">{{ number_format($papiPercent, 1, ',', '.') }}%</div>
                    <div class="sub"><b>{{ $fmt($papiDone) }}</b> dari {{ $fmt($papiTarget) }} ruta</div>
                </div>
                <div>
                    <div class="track" role="img" aria-label="Selesai {{ $papiDone }}, draft {{ $papiDraft }} dari {{ $papiTarget }} ruta">
                        <i style="width:{{ $papiTarget ? $papiDone / $papiTarget * 100 : 0 }}%;background:var(--st-submit)"></i>
                        <i style="width:{{ $papiTarget ? $papiDraft / $papiTarget * 100 : 0 }}%;background:var(--st-draft)"></i>
                        <i style="width:{{ $papiTarget ? max(0, $papiTarget - $papiDone - $papiDraft) / $papiTarget * 100 : 0 }}%;background:var(--st-open)"></i>
                    </div>
                    <div class="legend" style="grid-template-columns:repeat(3,minmax(0,1fr))">
                        <div><div class="k"><i class="sw submit"></i>Selesai</div><div class="v">{{ $fmt($papiDone) }}</div></div>
                        <div><div class="k"><i class="sw draft"></i>Draft</div><div class="v">{{ $fmt($papiDraft) }}</div></div>
                        <div><div class="k"><i class="sw open"></i>Belum diisi</div><div class="v">{{ $fmt(max(0, $papiTarget - $papiDone - $papiDraft)) }}</div></div>
                    </div>
                </div>
            </div>
        @endif
    </section>

    <section aria-labelledby="runningTitle" class="ui" style="gap:10px">
        <h2 id="runningTitle" style="margin:0;font-size:14.5px;color:var(--brand-dark)">Survei berjalan <span class="num-chip">{{ $runningItems->count() }}</span></h2>
        @if ($runningItems->isEmpty())
            <div class="pnl"><div class="empty"><b>Tidak ada survei berjalan</b><span>Survei yang dialokasikan admin kepada Anda akan muncul di sini.</span></div></div>
        @else
            <div class="mt-cards">
                @foreach ($runningItems as $item)
                    @include('panel.mitra.survey-card', ['item' => $item, 'counts' => $entryCounts->get($item['survey']->id, collect())])
                @endforeach
            </div>
        @endif
    </section>

    @if ($completedItems->isNotEmpty())
        <section class="pnl flush" aria-labelledby="doneTitle">
            <div class="pnl-h"><h2 id="doneTitle">Survei selesai <span class="num-chip">{{ $completedItems->count() }}</span></h2></div>
            <div class="pnl-b">
                <div class="tbl-wrap">
                    <table class="tbl stack" style="min-width:520px">
                        <thead><tr><th>Survei</th><th>Periode</th><th class="num">Hasil</th></tr></thead>
                        <tbody>
                            @foreach ($completedItems as $item)
                                <tr>
                                    <td><b style="font-weight:600">{{ $item['survey']->title }}</b> <span class="tag">{{ $item['survey']->typeLabel() }}</span></td>
                                    <td class="muted-cell" style="white-space:nowrap">{{ $item['survey']->start_date->locale('id')->translatedFormat('d M') }} – {{ $item['survey']->end_date->locale('id')->translatedFormat('d M Y') }}</td>
                                    <td class="num">{{ $fmt($item['progress']) }} / {{ $fmt($item['target']) }} {{ $item['unit'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif
</div>
@endsection
