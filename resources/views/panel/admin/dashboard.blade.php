@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Dashboard Admin'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@section('content')
@php
    $pct = $totalUsers > 0 ? round($totalActiveUsers / $totalUsers * 100) : 0;
    $inactive = max(0, $totalUsers - $totalActiveUsers);
@endphp

<div class="hero">
    <h2>Hi, {{ auth()->user()->name }}! 👋</h2>
    <p>Selamat datang di panel administrasi SIMKM. Saat ini terdapat <b>{{ $totalUsers }} pengguna</b> ({{ $totalActiveUsers }} aktif), <b>{{ $totalSurveys }} survei</b>, dan <b>{{ number_format($totalActivities) }} aktivitas</b> tercatat dalam sistem.</p>
    <div class="h-chips">
        <span class="h-chip">👥 <b>{{ $totalUsers }}</b> Pengguna</span>
        <span class="h-chip">✅ <b>{{ $totalActiveUsers }}</b> Aktif</span>
        <span class="h-chip">📋 <b>{{ $totalSurveys }}</b> Survei</span>
        <span class="h-chip">📈 <b>{{ number_format($totalActivities) }}</b> Aktivitas</span>
    </div>
</div>

<div class="grid g4">
    <div class="stat st-blue"><div class="s-ico">👥</div><div class="s-label">Total Pengguna</div><div class="s-val">{{ $totalUsers }}</div><div class="s-foot">Seluruh akun terdaftar</div></div>
    <div class="stat st-sky"><div class="s-ico">📋</div><div class="s-label">Total Survei</div><div class="s-val">{{ $totalSurveys }}</div><div class="s-foot">Survei dalam sistem</div></div>
    <div class="stat st-indigo"><div class="s-ico">✅</div><div class="s-label">Pengguna Aktif</div><div class="s-val">{{ $totalActiveUsers }}</div><div class="s-foot">{{ $pct }}% dari total</div></div>
    <div class="stat st-cyan"><div class="s-ico">📈</div><div class="s-label">Aktivitas Tercatat</div><div class="s-val">{{ number_format($totalActivities) }}</div><div class="s-foot">Log aktivitas sistem</div></div>
</div>

<div class="grid g2">
    <div class="card">
        <div class="card-h"><span class="dot"></span> Status Pengguna</div>
        <div class="chart-wrap" style="height:230px"><canvas id="chartUsers"></canvas></div>
        <div style="display:flex;gap:8px;margin-top:12px;justify-content:center;flex-wrap:wrap">
            <span class="pill pill-blue">Aktif: {{ $totalActiveUsers }}</span>
            <span class="pill pill-amber">Nonaktif: {{ $inactive }}</span>
        </div>
    </div>
    <div class="card">
        <div class="card-h"><span class="dot"></span> Ringkasan Sistem</div>
        <div class="chart-wrap" style="height:230px"><canvas id="chartOverview"></canvas></div>
    </div>
</div>

<div class="card">
    <div class="card-h"><span class="dot"></span> Akses Cepat</div>
    <div class="grid g3" style="margin-bottom:0">
        <a href="/admin/users" class="stat st-navy" style="text-decoration:none"><div class="s-label">Kelola Pengguna</div><div class="s-foot" style="margin-top:6px">Tambah & atur akun →</div></a>
        <a href="/admin/teams" class="stat st-blue" style="text-decoration:none"><div class="s-label">Tim Kerja</div><div class="s-foot" style="margin-top:6px">Atur tim survei →</div></a>
        <a href="/admin/logs" class="stat st-sky" style="text-decoration:none"><div class="s-label">Log Aktivitas</div><div class="s-foot" style="margin-top:6px">Pantau aktivitas →</div></a>
    </div>
</div>
@endsection

@push('scripts')
<script>
Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
Chart.defaults.color = '#5b7290';

new Chart(document.getElementById('chartUsers'), {
    type:'doughnut',
    data:{ labels:['Aktif','Nonaktif'], datasets:[{ data:[{{ $totalActiveUsers }}, {{ $inactive }}], backgroundColor:['#1d4ed8','#f59e0b'], borderWidth:0 }] },
    options:{ responsive:true, maintainAspectRatio:false, cutout:'68%', plugins:{ legend:{ position:'bottom' } } }
});

new Chart(document.getElementById('chartOverview'), {
    type:'bar',
    data:{ labels:['Pengguna','Aktif','Survei','Aktivitas'],
        datasets:[{ label:'Jumlah', data:[{{ $totalUsers }}, {{ $totalActiveUsers }}, {{ $totalSurveys }}, {{ $totalActivities }}],
            backgroundColor:['#1d4ed8','#10b981','#38bdf8','#6366f1'], borderRadius:6, maxBarThickness:48 }] },
    options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } },
        scales:{ y:{ beginAtZero:true, ticks:{ precision:0 }, grid:{ color:'#eef2f9' } }, x:{ grid:{ display:false } } } }
});
</script>
@endpush
