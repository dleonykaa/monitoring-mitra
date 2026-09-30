@extends('panel.layout', ['panelTitle' => $panelTitle, 'pageTitle' => 'Detail Entri'])

@section('menu')
    @include($menuView)
@endsection

@php
    $values = $entry->values->pluck('value', 'survey_variable_id');
    $statusTone = ['submitted' => 'bdg-green', 'draft' => 'bdg-amber', 'open' => 'bdg-gray'];
    $when = fn ($date) => $date?->locale('id')->translatedFormat('d M Y, H:i') ?? '–';
@endphp

@push('head')
<style>
    .ed-grid{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:16px;align-items:start}
    .ed-list{margin:0;display:grid;grid-template-columns:minmax(140px,max-content) minmax(0,1fr);gap:0}
    .ed-list dt,.ed-list dd{padding:10px 0;border-bottom:1px solid var(--line);font-size:13px}
    .ed-list dt{color:var(--muted);padding-right:18px}
    .ed-list dd{margin:0;font-weight:600;word-break:break-word}
    .ed-list dt:last-of-type,.ed-list dd:last-of-type{border-bottom:0}
    .ed-photo{display:block;width:100%;aspect-ratio:3/4;object-fit:cover;border-radius:12px;border:1px solid var(--line);background:var(--soft)}
    .ed-nophoto{display:grid;place-items:center;aspect-ratio:3/4;border-radius:12px;border:1.5px dashed var(--line);color:var(--muted);font-size:12.5px}
    @media (max-width:860px){.ed-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="ui">
    <div class="pg-head">
        <div style="min-width:0">
            <a class="pg-back" href="{{ $backUrl ?? $base.'/entri-papi?survey='.$survey->id.'&status='.$entry->entry_status }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                {{ $backLabel ?? 'Data Entri PAPI' }} · {{ $survey->title }}
            </a>
            <h1>Ruta {{ $entry->no_urut_ruta ?: '#'.$entry->id }}</h1>
            <p>Dicacah oleh {{ $entry->assignment->mitra->name }} · {{ $entry->district?->name ?? 'Kecamatan belum diisi' }}{{ $entry->village ? ', '.$entry->village->name : '' }}</p>
        </div>
        <span class="bdg bdg-dot {{ $statusTone[$entry->entry_status] ?? 'bdg-gray' }}">{{ $entry->statusLabel() }}</span>
    </div>

    <div class="ed-grid">
        <div class="ui" style="gap:16px">
            <section class="pnl" aria-labelledby="identityTitle">
                <div class="pnl-h"><h2 id="identityTitle">Identitas ruta</h2></div>
                <div class="pnl-b">
                    <dl class="ed-list">
                        <dt>Kecamatan</dt><dd>{{ $entry->district?->name ?? '–' }}</dd>
                        <dt>Desa/Kelurahan</dt><dd>{{ $entry->village?->name ?? '–' }}</dd>
                        @if ($entry->kode_nks)<dt>Kode NKS</dt><dd>{{ $entry->kode_nks }}</dd>@endif
                        <dt>SLS</dt><dd>{{ $entry->sls ?: '–' }}</dd>
                        <dt>No urut ruta</dt><dd>{{ $entry->no_urut_ruta ?: '–' }}</dd>
                        <dt>Terakhir diubah</dt><dd>{{ $when($entry->updated_at) }}</dd>
                        <dt>Selesai dikirim</dt><dd>{{ $when($entry->submitted_at) }}</dd>
                    </dl>
                </div>
            </section>

            <section class="pnl" aria-labelledby="valuesTitle">
                <div class="pnl-h"><h2 id="valuesTitle">Isian variabel <span class="num-chip">{{ $survey->variables->count() }}</span></h2></div>
                <div class="pnl-b">
                    @if ($survey->variables->isEmpty())
                        <p style="margin:0;font-size:12.5px;color:var(--muted)">Survei ini tidak memiliki variabel isian.</p>
                    @else
                        <dl class="ed-list">
                            @foreach ($survey->variables as $variable)
                                <dt>{{ $variable->name }}</dt>
                                <dd>{{ filled($values->get($variable->id)) ? $values->get($variable->id) : '–' }}</dd>
                            @endforeach
                        </dl>
                    @endif
                </div>
            </section>
        </div>

        <section class="pnl" aria-labelledby="photoTitle">
            <div class="pnl-h"><h2 id="photoTitle">Foto bukti</h2></div>
            <div class="pnl-b">
                @if ($entry->evidence_photo_path)
                    <a href="{{ asset('storage/'.$entry->evidence_photo_path) }}" target="_blank" rel="noopener">
                        <img class="ed-photo" src="{{ asset('storage/'.$entry->evidence_photo_path) }}" alt="Foto bukti pencacahan ruta {{ $entry->no_urut_ruta }}" onerror="this.onerror=null;this.alt='';this.title='Foto tidak ditemukan';this.classList.add('img-missing');this.src='data:image/gif;base64,R0lGODlhAQABAAAAACw=';">
                    </a>
                    <p style="margin:8px 0 0;font-size:12px;color:var(--muted)">Klik foto untuk membuka ukuran penuh.</p>
                @else
                    <div class="ed-nophoto">Belum ada foto</div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
