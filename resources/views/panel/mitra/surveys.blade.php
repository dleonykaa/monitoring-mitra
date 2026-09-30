@extends('panel.layout', ['panelTitle' => 'Mitra BPS', 'pageTitle' => 'Daftar Survei'])

@section('menu')
    @include('panel.mitra.menu')
@endsection

@include('panel.mitra.styles')

@php
    $isRunningTab = $status === 'Berjalan';
@endphp

@section('content')
<div class="ui">
    <div class="pg-head">
        <div>
            <h1>Daftar survei</h1>
            <p>Buka survei PAPI yang berjalan untuk mengisi ruta. Isian bisa disimpan sebagai draft, lalu dikirim setelah isian dan foto lengkap.</p>
        </div>
    </div>

    <div class="toolbar">
        <nav class="seg" aria-label="Status survei">
            @foreach ($statusCounts as $value => $count)
                <a href="/mitra/surveys?status={{ $value }}" @if ($value === $status) aria-current="page" @endif>
                    {{ $value }} <small>{{ $count }}</small>
                </a>
            @endforeach
        </nav>
    </div>

    @if ($items->isEmpty())
        <div class="pnl">
            <div class="empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>
                <b>{{ $isRunningTab ? 'Tidak ada survei berjalan' : 'Belum ada survei selesai' }}</b>
                <span>{{ $isRunningTab ? 'Survei yang dialokasikan admin kepada Anda akan muncul di sini.' : 'Survei yang sudah ditutup admin akan pindah ke sini.' }}</span>
            </div>
        </div>
    @else
        <div class="mt-cards">
            @foreach ($items as $item)
                @include('panel.mitra.survey-card', [
                    'item' => $item,
                    'counts' => $entryCounts->get($item['survey']->id, collect()),
                    'actionLabel' => $isRunningTab ? 'Update progress' : 'Lihat entri',
                ])
            @endforeach
        </div>
    @endif
</div>
@endsection
