@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Daftar Mitra'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@php
    $initials = collect(preg_split('/\s+/', trim($mitra->name)))->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
@endphp

@section('content')
<div class="ui">
    <div class="pg-head">
        <div style="min-width:0">
            <a class="pg-back" href="/admin/mitra">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Daftar mitra
            </a>
            <div class="who" style="gap:14px">
                <span class="avatar lg" aria-hidden="true">{{ $initials }}</span>
                <span>
                    <h1>{{ $mitra->name }}</h1>
                    <p style="margin-top:2px">{{ $mitra->email }}{{ $mitra->phone ? ' · '.$mitra->phone : '' }}</p>
                </span>
            </div>
        </div>
        <span class="bdg bdg-dot {{ $mitra->is_active ? 'bdg-green' : 'bdg-gray' }}">{{ $mitra->is_active ? 'Akun aktif' : 'Akun nonaktif' }}</span>
    </div>

    <div class="stats">
        <div><div class="k">Survei berjalan</div><div class="v">{{ $runningItems->count() }}</div></div>
        <div><div class="k">Survei selesai</div><div class="v">{{ $completedItems->count() }}</div></div>
        <div><div class="k">Total survei</div><div class="v">{{ $runningItems->count() + $completedItems->count() }}</div></div>
    </div>

    @include('panel.admin.mitra.survey-table', ['title' => 'Survei berjalan', 'items' => $runningItems, 'emptyText' => 'Mitra ini tidak sedang memegang survei.'])
    @include('panel.admin.mitra.survey-table', ['title' => 'Survei selesai', 'items' => $completedItems, 'emptyText' => 'Belum ada survei yang selesai.'])
</div>
@endsection
