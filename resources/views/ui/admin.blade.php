@extends('ui.layout')

@section('content')
    <div class="topbar">
        <div>
            <div class="brand">Dashboard Admin</div>
            <div class="sub">Manajemen pengguna, role, tim, wilayah, dan audit aktivitas</div>
        </div>
        <a href="/ui" class="btn alt">Kembali</a>
    </div>

    <div class="grid g4 kpis">
        <div class="card"><div class="label">Total Pengguna</div><div class="metric">128</div></div>
        <div class="card"><div class="label">Mitra Aktif</div><div class="metric">84</div></div>
        <div class="card"><div class="label">Tim Kerja</div><div class="metric">12</div></div>
        <div class="card"><div class="label">Log Hari Ini</div><div class="metric">356</div></div>
    </div>

    <div class="grid g3">
        <div class="card">
            <div class="label">Aksi Cepat</div>
            <div class="links">
                <a class="btn" href="#">Tambah User</a>
                <a class="btn" href="#">Atur Role</a>
                <a class="btn" href="#">Kelola Wilayah</a>
            </div>
        </div>
        <div class="card">
            <div class="label">Distribusi Role</div>
            <p>Admin 6, Pegawai 38, Mitra 84</p>
            <div class="bar"><span style="width: 66%"></span></div>
        </div>
        <div class="card">
            <div class="label">Status Akun</div>
            <p>Aktif 118, Nonaktif 10</p>
            <div class="bar"><span style="width: 92%"></span></div>
        </div>
    </div>
@endsection
