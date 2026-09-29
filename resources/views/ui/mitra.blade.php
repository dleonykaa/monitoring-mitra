@extends('ui.layout')

@section('content')
    <div class="topbar">
        <div>
            <div class="brand">Dashboard Mitra</div>
            <div class="sub">Daftar tugas survei dan progres personal</div>
        </div>
        <a href="/ui" class="btn alt">Kembali</a>
    </div>

    <div class="grid g4 kpis">
        <div class="card"><div class="label">Survei Berjalan</div><div class="metric">3</div></div>
        <div class="card"><div class="label">Target Total</div><div class="metric">160</div></div>
        <div class="card"><div class="label">Sudah Entri</div><div class="metric">114</div></div>
        <div class="card"><div class="label">Sisa Target</div><div class="metric">46</div></div>
    </div>

    <div class="card">
        <div class="label">Tugas Survei</div>
        <table>
            <tr><th>Survei</th><th>Progress</th><th>Deadline</th><th>Status</th></tr>
            <tr><td>Susenas Juni</td><td>58/70</td><td>2026-06-18</td><td><span class="pill ok">Tepat Waktu</span></td></tr>
            <tr><td>Sakernas Triwulan</td><td>32/50</td><td>2026-06-20</td><td><span class="pill warn">Perlu Aksi</span></td></tr>
            <tr><td>Podes Pulau</td><td>24/40</td><td>2026-06-25</td><td><span class="pill ok">On Track</span></td></tr>
        </table>
    </div>
@endsection
