@extends('ui.layout')

@section('content')
    <div class="topbar">
        <div>
            <div class="brand">Dashboard Pegawai BPS</div>
            <div class="sub">Progres survei tim, assignment mitra, dan update lapangan terbaru</div>
        </div>
        <a href="/ui" class="btn alt">Kembali</a>
    </div>

    <div class="grid g4 kpis">
        <div class="card"><div class="label">Survei Berjalan</div><div class="metric">7</div></div>
        <div class="card"><div class="label">Total Target</div><div class="metric">4,200</div></div>
        <div class="card"><div class="label">Entri Masuk</div><div class="metric">2,980</div></div>
        <div class="card"><div class="label">Capaian</div><div class="metric">71%</div></div>
    </div>

    <div class="grid g3">
        <div class="card">
            <div class="label">Progres Per Survei</div>
            <p>Susenas Juni</p><div class="bar"><span style="width: 78%"></span></div>
            <p>Sakernas Triwulan</p><div class="bar"><span style="width: 64%"></span></div>
            <p>Podes Pulau</p><div class="bar"><span style="width: 52%"></span></div>
        </div>
        <div class="card">
            <div class="label">Per Kecamatan</div>
            <p>Kepulauan Seribu Utara</p><div class="bar"><span style="width: 76%"></span></div>
            <p>Kepulauan Seribu Selatan</p><div class="bar"><span style="width: 66%"></span></div>
        </div>
        <div class="card">
            <div class="label">Aksi</div>
            <div class="links">
                <a class="btn" href="#">Buat Survei</a>
                <a class="btn" href="#">Import Assignment</a>
                <a class="btn" href="#">Export Entri</a>
            </div>
        </div>
    </div>
@endsection
