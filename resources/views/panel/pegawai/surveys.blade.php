@extends('panel.layout', ['panelTitle' => 'Pegawai BPS', 'pageTitle' => 'Survei'])

@section('menu')
    @include('panel.pegawai.menu')
@endsection

@section('content')
    <div class="card" style="display:flex;justify-content:space-between;align-items:center;">
        <div class="card-h" style="margin:0;"><span class="dot"></span>Daftar Survei</div>
        <a class="btn" href="/pegawai/surveys/create" style="padding:9px 12px;">Tambah Survei</a>
    </div>

    <div class="card-h"><span class="dot"></span>Survei Berjalan</div>
    <div class="grid g-fixed">
        @forelse ($runningSurveys as $survey)
            @include('panel.pegawai.survey-card', ['survey' => $survey])
        @empty
            <div class="card muted">Belum ada survei berjalan.</div>
        @endforelse
    </div>

    <div class="card-h" style="margin-top:22px;"><span class="dot"></span>Draft</div>
    <div class="grid g-fixed">
        @forelse ($draftSurveys as $survey)
            @include('panel.pegawai.survey-card', ['survey' => $survey])
        @empty
            <div class="card muted">Belum ada draft survei.</div>
        @endforelse
    </div>

    <div class="card-h" style="margin-top:22px;"><span class="dot"></span>Survei Selesai</div>
    <div class="grid g-fixed">
        @forelse ($completedSurveys as $survey)
            @include('panel.pegawai.survey-card', ['survey' => $survey])
        @empty
            <div class="card muted">Belum ada survei selesai.</div>
        @endforelse
    </div>
@endsection
