@extends('ui.layout')

@section('content')
    <div class="topbar">
        <div>
            <div class="brand">Monitoring Kinerja Mitra BPS</div>
            <div class="sub">UI awal untuk Admin, Pegawai BPS, dan Mitra</div>
        </div>
    </div>

    <div class="grid g3">
        <div class="card">
            <div class="label">Admin</div>
            <p>Kelola akun, role, tim, wilayah, dan monitoring lintas tim.</p>
            <a class="btn" href="/ui/admin">Buka Dashboard</a>
        </div>
        <div class="card">
            <div class="label">Pegawai BPS</div>
            <p>Kelola survei, assignment mitra, dan progres tim.</p>
            <a class="btn" href="/ui/pegawai">Buka Dashboard</a>
        </div>
        <div class="card">
            <div class="label">Mitra (Web Preview)</div>
            <p>Lihat tugas survei dan progres personal.</p>
            <a class="btn" href="/ui/mitra">Buka Dashboard</a>
        </div>
    </div>
@endsection
