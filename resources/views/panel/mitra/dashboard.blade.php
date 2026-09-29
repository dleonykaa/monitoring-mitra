@extends('panel.layout', ['panelTitle' => 'Mitra BPS', 'pageTitle' => 'Dashboard Mitra'])

@section('menu')
    @include('panel.mitra.menu')
@endsection

@section('content')
    @php
        $overall = $totalTarget > 0 ? round(($totalProgress / $totalTarget) * 100, 1) : 0;
        $remaining = max(0, $totalTarget - $totalProgress);
    @endphp

    <div class="hero">
        <h2>Hi, {{ auth()->user()->name }}!</h2>
        <p>Berikut daftar survei yang dialokasikan kepada Anda. Fokus utama: selesaikan target, submit entri, dan pantau status tepat waktu.</p>
        <div class="h-chips">
            <span class="h-chip"><b>{{ $runningAssignments->count() }}</b> Survei berjalan</span>
            <span class="h-chip"><b>{{ $completedAssignments->count() }}</b> Survei selesai</span>
            <span class="h-chip"><b>{{ $overall }}%</b> Total progress</span>
        </div>
    </div>

    <div class="grid g4">
        <div class="stat st-blue"><div class="s-label">Target Total</div><div class="s-val">{{ $totalTarget }}</div><div class="s-foot">Seluruh alokasi Anda</div></div>
        <div class="stat st-green"><div class="s-label">Progress Masuk</div><div class="s-val">{{ $totalProgress }}</div><div class="s-foot">{{ $overall }}% dari target</div></div>
        <div class="stat st-amber"><div class="s-label">Sisa Target</div><div class="s-val">{{ $remaining }}</div><div class="s-foot">Perlu dituntaskan</div></div>
        <div class="stat st-indigo"><div class="s-label">Draft / Perlu Perbaikan</div><div class="s-val">{{ $draftEntries }}/{{ $pendingEntries }}</div><div class="s-foot">Draft dan entri tidak valid otomatis</div></div>
    </div>

    <div class="card">
        <div class="card-h"><span class="dot"></span> Survei Berjalan</div>
        <div class="mitra-survey-list">
            @forelse ($runningAssignments as $assignment)
                @include('panel.mitra.survey-row', ['assignment' => $assignment])
            @empty
                <div class="muted">Tidak ada survei berjalan.</div>
            @endforelse
        </div>
    </div>
@endsection

@include('panel.mitra.styles')
