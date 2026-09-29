@extends('panel.layout', ['panelTitle' => 'Pegawai BPS', 'pageTitle' => 'Dashboard Tim'])

@section('menu')
    @include('panel.pegawai.menu')
@endsection

@section('content')
@php
    $teamNames = $teams->pluck('name')->join(', ') ?: 'belum dialokasikan';
    $sisaTarget = max(0, $totalTarget - $totalProgress);
    $completedSurveys = $surveyProgress->filter(fn ($s) => $s->total_target > 0 && ($s->progress_sum ?? 0) >= $s->total_target)->count();
    $scopeLabel = $selectedSurvey ? $selectedSurvey->title : 'Semua Survei';
    $surveyChartHeight = min(440, max(210, $surveyProgress->count() * 44));
@endphp

{{-- Greeting --}}
<div class="hero">
    <h2>Hi, {{ auth()->user()->name }}!</h2>
    @if ($selectedSurvey)
        <p>Menampilkan grafik untuk survei <b>{{ $selectedSurvey->title }}</b> — capaian <b>{{ $overallPercentage }}%</b> dari target <b>{{ number_format($totalTarget) }}</b>. {{ $lateAssignments > 0 ? 'Ada '.$lateAssignments.' alokasi mitra yang terlambat.' : 'Alokasi mitra berjalan tepat waktu.' }}</p>
    @else
        <p>Selamat datang di SIMKM. Tim <b>{{ $teamNames }}</b> sedang mengelola <b>{{ $totalSurveys }} survei</b> dengan capaian progres <b>{{ $overallPercentage }}%</b> dari total target. {{ $lateAssignments > 0 ? 'Ada '.$lateAssignments.' alokasi mitra yang perlu perhatian segera.' : 'Seluruh alokasi mitra berjalan tepat waktu.' }}</p>
    @endif
    <div class="h-chips">
        <span class="h-chip">📋 <b>{{ $totalSurveys }}</b> Survei</span>
        <span class="h-chip">🎯 <b>{{ number_format($totalTarget) }}</b> Target</span>
        <span class="h-chip">✅ <b>{{ number_format($totalProgress) }}</b> Masuk</span>
        <span class="h-chip">📈 <b>{{ $overallPercentage }}%</b> Capaian</span>
    </div>
</div>

{{-- Pilih Survei --}}
<div class="card" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
    <div class="card-h" style="margin:0"><span class="dot"></span> Lingkup Data:
        <span class="pill pill-blue" style="margin-left:4px">{{ \Illuminate\Support\Str::limit($scopeLabel, 40) }}</span>
    </div>
    <form method="GET" action="/pegawai/dashboard" style="display:flex;gap:8px;margin:0;align-items:center;flex-wrap:wrap">
        <span class="muted">Pilih Survei:</span>
        <select name="survey_id" onchange="this.form.submit()" style="min-width:260px">
            <option value="">Semua Survei</option>
            @foreach ($teamSurveys as $s)
                <option value="{{ $s->id }}" @selected($selectedSurveyId === $s->id)>{{ $s->title }}</option>
            @endforeach
        </select>
    </form>
</div>

{{-- Stat cards --}}
<div class="grid g4">
    @if ($selectedSurvey)
        <div class="stat st-blue"><div class="s-ico">📋</div><div class="s-label">Status Survei</div><div class="s-val" style="font-size:22px">{{ $selectedSurvey->status }}</div><div class="s-foot">{{ $selectedSurvey->start_date->format('d/m/y') }} – {{ $selectedSurvey->end_date->format('d/m/y') }}</div></div>
    @else
        <div class="stat st-blue"><div class="s-ico">📋</div><div class="s-label">Survei Tim</div><div class="s-val">{{ $totalSurveys }}</div><div class="s-foot">{{ $completedSurveys }} selesai · {{ $totalSurveys - $completedSurveys }} berjalan</div></div>
    @endif
    <div class="stat st-indigo"><div class="s-ico">🎯</div><div class="s-label">Total Target</div><div class="s-val">{{ number_format($totalTarget) }}</div><div class="s-foot">Target seluruh survei</div></div>
    <div class="stat st-green"><div class="s-ico">✅</div><div class="s-label">Progress Masuk</div><div class="s-val">{{ number_format($totalProgress) }}</div><div class="s-foot">{{ $overallPercentage }}% dari target</div></div>
    <div class="stat {{ $lateAssignments > 0 ? 'st-rose' : 'st-cyan' }}"><div class="s-ico">⏰</div><div class="s-label">Alert Keterlambatan</div><div class="s-val">{{ $lateAssignments }}</div><div class="s-foot">{{ $lateAssignments > 0 ? 'Perlu perhatian' : 'Semua tepat waktu' }}</div></div>
</div>

{{-- Grafik ringkas --}}
<div class="grid g2">
    <div class="card chart-card">
        <div class="card-h"><span class="dot"></span> Grafik Keseluruhan</div>
        <div class="chart-caption">Komposisi target yang sudah masuk dibanding sisa target.</div>
        <div class="chart-wrap chart-wrap-balanced"><canvas id="chartOverall"></canvas></div>
        <div class="chart-kpis">
            <div class="chart-kpi"><span>Tercapai</span><strong style="color:#1d4ed8">{{ number_format($totalProgress) }}</strong></div>
            <div class="chart-kpi"><span>Sisa Target</span><strong style="color:#64748b">{{ number_format($sisaTarget) }}</strong></div>
            <div class="chart-kpi"><span>Capaian</span><strong style="color:#059669">{{ $overallPercentage }}%</strong></div>
        </div>
    </div>

    <div class="card chart-card">
        <div class="card-h"><span class="dot"></span> Grafik Per Kecamatan</div>
        <div class="chart-caption">Jumlah entri masuk per kecamatan untuk melihat konsentrasi progres wilayah.</div>
        @if ($progressByDistrict->count())
            <div class="chart-wrap chart-wrap-balanced"><canvas id="chartDistrict"></canvas></div>
        @else
            <div class="chart-empty">Belum ada entri per kecamatan.</div>
        @endif
    </div>
</div>

{{-- Progress per survei --}}
@unless ($selectedSurvey)
<div class="card chart-card">
    <div class="card-h"><span class="dot"></span> Progress Survei</div>
    @if ($surveyProgress->count())
        <div class="chart-wrap" style="height:{{ $surveyChartHeight }}px"><canvas id="chartSurvey"></canvas></div>
    @else
        <div class="chart-empty">Belum ada survei untuk tim Anda.</div>
    @endif
</div>
@endunless

{{-- Monitoring per mitra --}}
@if ($selectedSurvey)
<div class="card dashboard-split-card">
    <div class="card-h" style="justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
        <span style="display:flex;align-items:center;gap:8px"><span class="dot"></span> Progress Mitra Survei</span>
        <div class="mitra-monitor-search">
            <input id="mitraProgressSearch" type="search" value="{{ $mitraSearch }}" placeholder="Cari mitra" aria-label="Cari mitra">
        </div>
    </div>

    <div class="mitra-monitor-list">
        @forelse ($mitraMonitoring as $index => $row)
            @php
                $rank = $index + 1;
            @endphp
            <div class="mitra-monitor-row" data-mitra-row data-name="{{ strtolower($row->mitra->name) }}">
                <div class="mitra-rank">#{{ $rank }}</div>
                <div class="mitra-monitor-main">
                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap">
                        <div>
                            <strong>{{ $row->mitra->name }}</strong>
                        </div>
                        <b>{{ $row->progress }}/{{ $row->target }}</b>
                    </div>
                    <div class="bar"><i style="width:{{ $row->percentage }}%"></i></div>
                </div>
                <div class="mitra-monitor-score">
                    <strong>{{ $row->percentage }}%</strong>
                    <span>Progress</span>
                </div>
            </div>
        @empty
            <div class="muted" style="padding:18px;text-align:center">Belum ada mitra pada lingkup data ini.</div>
        @endforelse
        <div id="mitraProgressEmpty" class="muted" style="display:none;padding:18px;text-align:center">Mitra tidak ditemukan pada lingkup data ini.</div>
    </div>

    <div class="mitra-monitor-pager" id="mitraProgressPager" hidden>
        <button type="button" class="btn btn-grey" id="mitraProgressPrev">Sebelumnya</button>
        <span id="mitraProgressPageInfo"></span>
        <button type="button" class="btn" id="mitraProgressNext">Berikutnya</button>
    </div>
</div>
@endif

<div class="grid g2 dashboard-mitra-grid">
@if ($checkpointAssignments->count())
    <div class="card checkpoint-alert dashboard-split-card">
        <div class="card-h" style="justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
            <span style="display:flex;align-items:center;gap:8px"><span class="dot"></span> Alert Mitra Tertinggal Checkpoint <span class="pill pill-amber">{{ $checkpointAssignments->count() }}</span></span>
            <div class="mitra-monitor-search">
                <input id="checkpointSearch" type="search" placeholder="Cari mitra" aria-label="Cari mitra tertinggal checkpoint">
            </div>
        </div>
        <p class="checkpoint-note">Capaian tertinggal minimal 10 poin dari checkpoint yang sudah jatuh tempo.</p>
        <div class="checkpoint-list">
            @foreach ($checkpointAssignments as $alert)
                @php
                    $assignment = $alert->assignment;
                @endphp
                <div class="checkpoint-row" data-checkpoint-row data-name="{{ strtolower($assignment->mitra->name) }}">
                    <div>
                        <strong>{{ $assignment->mitra->name }}</strong>
                        <span>{{ \Illuminate\Support\Str::limit($assignment->survey->title, 42) }} · Checkpoint {{ $alert->checkpointNumber }} ({{ $alert->checkpoint->target_percentage }}%)</span>
                    </div>
                    <div class="checkpoint-progress">
                        <div class="bar"><i style="width:{{ $alert->progressPercentage }}%;background:#f59e0b"></i></div>
                        <small>{{ $assignment->current_progress }}/{{ $assignment->target }} · {{ $alert->progressPercentage }}%</small>
                    </div>
                    <span class="pill pill-amber">Kurang {{ $alert->shortfall }} target</span>
                </div>
            @endforeach
            <div id="checkpointEmpty" class="muted" style="display:none;padding:18px;text-align:center">Mitra tidak ditemukan.</div>
        </div>

        <div class="mitra-monitor-pager" id="checkpointPager" hidden>
            <button type="button" class="btn btn-grey" id="checkpointPrev">Sebelumnya</button>
            <span id="checkpointPageInfo"></span>
            <button type="button" class="btn" id="checkpointNext">Berikutnya</button>
        </div>
    </div>
@endif
@if ($staleAssignments->count())
    <div class="card stale-alert dashboard-split-card">
        <div class="card-h" style="justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
            <span style="display:flex;align-items:center;gap:8px"><span class="dot"></span> Alert Mitra Belum Update 3 Hari <span class="pill pill-rose">{{ $staleAssignments->count() }}</span></span>
            <div class="mitra-monitor-search">
                <input id="staleSearch" type="search" placeholder="Cari mitra" aria-label="Cari mitra terlambat">
            </div>
        </div>
        <div class="stale-list">
            @foreach ($staleAssignments as $assignment)
                @php
                    $lastUpdate = $assignment->last_entry_at ? \Illuminate\Support\Carbon::parse($assignment->last_entry_at) : null;
                    $staleDays = $lastUpdate ? (int) $lastUpdate->diffInDays(now()) : null;
                    $pct = $assignment->target > 0 ? min(100, round(($assignment->current_progress / $assignment->target) * 100, 1)) : 0;
                @endphp
                <div class="stale-row" data-stale-row data-name="{{ strtolower($assignment->mitra->name) }}">
                    <div>
                        <strong>{{ $assignment->mitra->name }}</strong>
                        <span>{{ \Illuminate\Support\Str::limit($assignment->survey->title, 42) }}</span>
                    </div>
                    <div class="stale-progress">
                        <div class="bar"><i style="width:{{ $pct }}%;background:#f59e0b"></i></div>
                        <small>{{ $assignment->current_progress }}/{{ $assignment->target }}</small>
                    </div>
                    <span class="pill pill-amber">{{ $lastUpdate ? $staleDays.' hari tidak update' : 'Belum pernah update' }}</span>
                </div>
            @endforeach
            <div id="staleEmpty" class="muted" style="display:none;padding:18px;text-align:center">Mitra tidak ditemukan.</div>
        </div>

        <div class="mitra-monitor-pager" id="stalePager" hidden>
            <button type="button" class="btn btn-grey" id="stalePrev">Sebelumnya</button>
            <span id="stalePageInfo"></span>
            <button type="button" class="btn" id="staleNext">Berikutnya</button>
        </div>
    </div>
@endif
</div>

@endsection

@push('scripts')
<script>
const BLUE='#2563eb', SKY='#38bdf8', GREEN='#10b981', AMBER='#f59e0b', GREY='#e2e8f0', GRID='#e8eef8';
Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
Chart.defaults.color = '#516987';

const commonPlugins = {
    tooltip: {
        backgroundColor: '#0b2b55',
        padding: 10,
        cornerRadius: 10,
        titleFont: { weight: '700' },
        bodyFont: { weight: '600' }
    }
};

// 1) Grafik keseluruhan
new Chart(document.getElementById('chartOverall'), {
    type:'doughnut',
    data:{ labels:['Tercapai','Sisa Target'], datasets:[{ data:[{{ $totalProgress }}, {{ $sisaTarget }}], backgroundColor:[BLUE, GREY], borderWidth:0, hoverOffset:4 }] },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        cutout:'72%',
        layout:{ padding:8 },
        plugins:{ ...commonPlugins, legend:{ position:'bottom', labels:{ usePointStyle:true, boxWidth:8, padding:16 } } }
    }
});

// 2) Grafik per kecamatan
@if ($progressByDistrict->count())
new Chart(document.getElementById('chartDistrict'), {
    type:'bar',
    data:{
        labels:@json($progressByDistrict->map(fn($r)=>$r->district?->name ?? 'Tanpa kecamatan')->values()),
        datasets:[{ label:'Total Entri', data:@json($progressByDistrict->pluck('total')->values()),
            backgroundColor:SKY, borderRadius:8, maxBarThickness:34 }]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        layout:{ padding:{ top:8, right:8, bottom:0, left:0 } },
        plugins:{ ...commonPlugins, legend:{ display:false } },
        scales:{
            y:{ beginAtZero:true, ticks:{ precision:0 }, grid:{ color:GRID } },
            x:{ grid:{ display:false }, ticks:{ maxRotation:0, autoSkip:true } }
        }
    }
});
@endif

// 3) Progress per survei (hanya saat "Semua Survei Tim")
@if (! $selectedSurvey && $surveyProgress->count())
new Chart(document.getElementById('chartSurvey'), {
    type:'bar',
    data:{
        labels:@json($surveyProgress->map(fn($s)=>\Illuminate\Support\Str::limit($s->title, 42))->values()),
        datasets:[{ label:'Capaian (%)',
            data:@json($surveyProgress->map(fn($s)=>$s->total_target>0?round(($s->progress_sum??0)/$s->total_target*100,1):0)->values()),
            backgroundColor:BLUE, borderRadius:8, maxBarThickness:24 }]
    },
    options:{
        indexAxis:'y',
        responsive:true,
        maintainAspectRatio:false,
        layout:{ padding:{ top:4, right:18, bottom:0, left:0 } },
        plugins:{
            ...commonPlugins,
            legend:{ display:false },
            tooltip:{ ...commonPlugins.tooltip, callbacks:{ label:(context)=>`${context.parsed.x}% capaian` } }
        },
        scales:{
            x:{ beginAtZero:true, max:100, grid:{ color:GRID }, ticks:{ callback:(value)=>`${value}%` } },
            y:{ grid:{ display:false }, ticks:{ autoSkip:false } }
        }
    }
});
@endif

/**
 * Daftar dengan search + pagination sisi klien, dipakai untuk kartu-kartu monitoring
 * mitra di dashboard ini agar perilakunya konsisten (5 nama per halaman).
 */
function setupPagedList({ rowSelector, searchInputId, emptyId, pagerId, prevId, nextId, pageInfoId, pageSize }) {
    const rows = Array.from(document.querySelectorAll(rowSelector));
    const searchInput = document.getElementById(searchInputId);
    const empty = document.getElementById(emptyId);
    const pager = document.getElementById(pagerId);
    const prevButton = document.getElementById(prevId);
    const nextButton = document.getElementById(nextId);
    const pageInfo = document.getElementById(pageInfoId);
    let currentPage = 1;

    function render() {
        const keyword = (searchInput?.value || '').trim().toLowerCase();
        const filteredRows = rows.filter((row) => keyword === '' || row.dataset.name.includes(keyword));
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));

        currentPage = Math.min(currentPage, totalPages);
        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;

        rows.forEach((row) => {
            row.hidden = true;
        });

        filteredRows.slice(start, end).forEach((row) => {
            row.hidden = false;
        });

        if (empty) {
            empty.style.display = filteredRows.length === 0 ? 'block' : 'none';
        }

        if (pager && pageInfo && prevButton && nextButton) {
            pager.hidden = filteredRows.length <= pageSize;
            pageInfo.textContent = `Halaman ${currentPage} / ${totalPages}`;
            prevButton.disabled = currentPage <= 1;
            nextButton.disabled = currentPage >= totalPages;
        }
    }

    searchInput?.addEventListener('input', () => {
        currentPage = 1;
        render();
    });
    prevButton?.addEventListener('click', () => {
        currentPage = Math.max(1, currentPage - 1);
        render();
    });
    nextButton?.addEventListener('click', () => {
        currentPage += 1;
        render();
    });

    render();
}

setupPagedList({
    rowSelector: '[data-mitra-row]',
    searchInputId: 'mitraProgressSearch',
    emptyId: 'mitraProgressEmpty',
    pagerId: 'mitraProgressPager',
    prevId: 'mitraProgressPrev',
    nextId: 'mitraProgressNext',
    pageInfoId: 'mitraProgressPageInfo',
    pageSize: 5,
});

setupPagedList({
    rowSelector: '[data-checkpoint-row]',
    searchInputId: 'checkpointSearch',
    emptyId: 'checkpointEmpty',
    pagerId: 'checkpointPager',
    prevId: 'checkpointPrev',
    nextId: 'checkpointNext',
    pageInfoId: 'checkpointPageInfo',
    pageSize: 5,
});

setupPagedList({
    rowSelector: '[data-stale-row]',
    searchInputId: 'staleSearch',
    emptyId: 'staleEmpty',
    pagerId: 'stalePager',
    prevId: 'stalePrev',
    nextId: 'staleNext',
    pageInfoId: 'stalePageInfo',
    pageSize: 5,
});
</script>
@endpush

@push('head')
<style>
    .chart-card {
        display: grid;
        align-content: start;
        gap: 10px;
    }

    .chart-caption {
        color: var(--muted);
        font-size: 12.5px;
        line-height: 1.45;
        margin-top: -4px;
    }

    .chart-wrap-balanced {
        height: 282px;
    }

    .chart-kpis {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
        gap: 8px;
        margin-top: 2px;
    }

    .chart-kpi {
        border: 1px solid var(--line);
        border-radius: 12px;
        background: var(--soft2);
        padding: 9px 10px;
        text-align: center;
    }

    .chart-kpi span {
        display: block;
        color: var(--muted);
        font-size: 11.5px;
        line-height: 1.2;
    }

    .chart-kpi strong {
        display: block;
        color: var(--brand-dark);
        font-size: 16px;
        line-height: 1.2;
        margin-top: 3px;
    }

    .chart-empty {
        min-height: 180px;
        display: grid;
        place-items: center;
        border: 1px dashed var(--line);
        border-radius: 14px;
        color: var(--muted);
        background: var(--soft2);
        text-align: center;
        padding: 18px;
    }

    .dashboard-split-card {
        container-type: inline-size;
    }

    /* Baris yang di-hidden oleh pagination JS harus benar-benar hilang; tanpa ini,
       display:grid pada .mitra-monitor-row/.checkpoint-row/.stale-row mengalahkan
       default [hidden] browser sehingga baris tetap tampak walau atribut hidden ada. */
    .mitra-monitor-row[hidden],
    .checkpoint-row[hidden],
    .stale-row[hidden] {
        display: none !important;
    }

    .stale-alert {
        border-color: #fecaca;
        background: #fff7f7;
    }

    .checkpoint-alert {
        border-color: #fde68a;
        background: #fffbeb;
    }

    .checkpoint-note {
        color: #92400e;
        font-size: 11.5px;
        line-height: 1.45;
        margin: 8px 0 0;
    }

    .checkpoint-list {
        display: grid;
        gap: 7px;
        margin-top: 12px;
    }

    .checkpoint-row {
        display: grid;
        grid-template-columns: minmax(120px, 1.3fr) minmax(110px, 1fr) 100px;
        gap: 8px;
        align-items: center;
        padding: 7px 9px;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: var(--card);
    }

    .checkpoint-row strong,
    .checkpoint-row span {
        display: block;
    }

    .checkpoint-row strong {
        color: var(--brand-dark);
        font-size: 13.5px;
    }

    .checkpoint-row span,
    .checkpoint-progress small {
        color: var(--muted);
        font-size: 11.5px;
    }

    .checkpoint-progress {
        display: grid;
        grid-template-columns: 1fr;
        gap: 5px;
    }

    .stale-list {
        display: grid;
        gap: 7px;
        margin-top: 12px;
    }

    .stale-row {
        display: grid;
        grid-template-columns: minmax(120px, 1.3fr) minmax(110px, 1fr) 100px;
        gap: 8px;
        align-items: center;
        padding: 7px 9px;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: var(--card);
    }

    .stale-row strong,
    .stale-row span {
        display: block;
    }

    .stale-row strong {
        color: var(--brand-dark);
        font-size: 13.5px;
    }

    .stale-row span,
    .stale-progress small {
        color: var(--muted);
        font-size: 11.5px;
    }

    .stale-progress {
        display: grid;
        grid-template-columns: 1fr 52px;
        gap: 8px;
        align-items: center;
    }

    .mitra-monitor-search {
        display: flex;
        gap: 8px;
        margin: 0;
        align-items: center;
        flex-wrap: wrap;
    }

    .mitra-monitor-search input {
        min-width: 140px;
        flex: 1;
    }

    .mitra-monitor-list {
        display: grid;
        gap: 8px;
    }

    .mitra-monitor-row {
        display: grid;
        grid-template-columns: 26px minmax(140px, 1fr) 70px;
        gap: 8px;
        align-items: center;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: var(--soft2);
        padding: 7px 9px;
    }

    .mitra-rank {
        color: var(--brand);
        font-weight: 900;
        font-size: 13px;
        text-align: center;
    }

    .mitra-monitor-main {
        display: grid;
        gap: 5px;
    }

    .mitra-monitor-main strong {
        display: block;
        color: var(--brand-dark);
        font-size: 13.5px;
    }

    .mitra-monitor-main .bar {
        height: 6px;
    }

    .mitra-monitor-main span,
    .mitra-monitor-score span {
        color: var(--muted);
        font-size: 11px;
    }

    .mitra-monitor-score {
        border: 1px solid var(--line);
        border-radius: 10px;
        background: var(--card);
        padding: 5px 8px;
        text-align: center;
    }

    .mitra-monitor-score strong {
        display: block;
        color: var(--brand);
        font-size: 15px;
        line-height: 1.2;
    }

    .mitra-monitor-pager {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid var(--line);
    }

    .mitra-monitor-pager[hidden] {
        display: none !important;
    }

    .mitra-monitor-pager span {
        color: var(--muted);
        font-size: 12.5px;
        font-weight: 800;
    }

    .mitra-monitor-pager button:disabled {
        cursor: not-allowed;
        opacity: .55;
        box-shadow: none;
        filter: none;
    }

    @media (max-width: 900px) {
        .chart-wrap-balanced {
            height: 240px;
        }
    }

    /* Kedua kartu ini bisa tampil sebelahan (setengah lebar) atau ditumpuk penuh,
       jadi breakpoint-nya mengikuti lebar kartu itu sendiri, bukan lebar layar. */
    @container (max-width: 380px) {
        .checkpoint-row,
        .stale-row {
            grid-template-columns: 1fr;
        }
    }

    @container (max-width: 260px) {
        .mitra-monitor-row {
            grid-template-columns: 1fr;
            align-items: start;
        }

        .mitra-rank,
        .mitra-monitor-score {
            text-align: left;
        }
    }

    @container (max-width: 300px) {
        .mitra-monitor-search,
        .mitra-monitor-search input {
            width: 100%;
        }
    }
</style>
@endpush
