{{-- Dashboard bersama: ringkasan seluruh survei. Admin: $canManage = true (tombol kelola + daftar perlu tindakan). Pegawai: lihat saja. Rincian per survei ada di Monitoring. --}}
@extends('panel.layout', ['panelTitle' => $panelTitle, 'pageTitle' => 'Dashboard'])

@section('menu')
    @include($menuView)
@endsection

@include('panel.partials.dashboard-styles')

@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $fmtPct = fn ($n) => number_format((float) $n, 1, ',', '.').'%';
    $tone = fn ($p) => $p >= 90 ? 'good' : ($p >= 70 ? 'ok' : ($p >= 50 ? 'warn' : 'bad'));
    $share = fn ($part, $whole) => $whole > 0 ? round($part / $whole * 100, 2) : 0;
    $shortDate = fn ($date) => $date->locale('id')->translatedFormat('d M Y');

    $now = now('Asia/Jakarta');
    $today = $now->toDateString();
    $hour = $now->hour;
    $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
    $monitoringUrl = $base.'/monitoring/progres';

    $totals = $runningTotals;
    $runningCount = $surveyStatusCounts['Berjalan'];

    // Sisa waktu survei dihitung dari tanggal (bukan jam) agar "hari terakhir" tepat.
    $daysLeft = fn ($survey) => (int) \Illuminate\Support\Carbon::parse($today)->diffInDays(\Illuminate\Support\Carbon::parse($survey->end_date->toDateString()), false);
    $timeline = function ($survey) use ($daysLeft, $shortDate): array {
        return match ($survey->status) {
            'Draft' => ['gray', 'Mulai '.$shortDate($survey->start_date)],
            'Selesai' => ['gray', 'Berakhir '.$shortDate($survey->end_date)],
            default => match (true) {
                $daysLeft($survey) < 0 => ['rose', 'Lewat '.abs($daysLeft($survey)).' hari'],
                $daysLeft($survey) === 0 => ['amber', 'Hari terakhir'],
                $daysLeft($survey) <= 7 => ['amber', 'Sisa '.$daysLeft($survey).' hari'],
                default => ['gray', 'Sisa '.$daysLeft($survey).' hari'],
            },
        };
    };

    $statusOrder = ['Berjalan' => 0, 'Draft' => 1, 'Selesai' => 2];
    $sortedSurveys = $surveys->sortBy([
        fn ($a, $b) => ($statusOrder[$a->status] ?? 3) <=> ($statusOrder[$b->status] ?? 3),
        fn ($a, $b) => $a->end_date <=> $b->end_date,
    ])->values();
    $defaultFilter = $runningCount > 0 ? 'Berjalan' : 'all';

    $dailyTotal = $dailyEntries->sum('count');
    $dailyMax = max(1, (int) $dailyEntries->max('count'));
    $todayCount = (int) ($dailyEntries->first(fn ($day) => $day['date']->toDateString() === $today)['count'] ?? 0);

@endphp

@section('content')
<div class="db">
    <header class="db-page">
        <div style="min-width:0">
            <p class="db-hello">{{ $greeting }}, <b>{{ auth()->user()->name }}</b> · {{ $now->locale('id')->translatedFormat('l, d F Y') }}</p>
            <h1>Ringkasan survei</h1>
        </div>
    </header>

    {{-- Angka kunci: kartu capaian berlatar navy sebagai sorotan, sisanya kartu putih beraksen navy --}}
    <section class="db-kpis" aria-label="Angka kunci">
        <article class="kpi kpi-navy">
            <div class="kpi-h">
                <h2>Capaian survei berjalan</h2>
                <span class="kpi-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 6-6"/></svg></span>
            </div>
            <div class="kpi-val">{{ $totals['total'] > 0 ? $fmtPct($totals['percent']) : '–' }}</div>
            <div class="kpi-bar" role="img" aria-label="Submit {{ $fmt($totals['submit']) }}, draft {{ $fmt($totals['draft']) }}, open {{ $fmt($totals['open']) }}, dari beban {{ $fmt($totals['total']) }}">
                <i style="width:{{ $share($totals['submit'], $totals['total']) }}%;background:var(--st-submit)"></i>
                <i style="width:{{ $share($totals['draft'], $totals['total']) }}%;background:var(--st-draft)"></i>
                <i style="width:{{ $share($totals['open'], $totals['total']) }}%;background:var(--st-open)"></i>
                <i style="width:{{ $share($totals['other'], $totals['total']) }}%;background:var(--st-other)"></i>
            </div>
            <p class="kpi-foot"><b>{{ $fmt($totals['submit']) }}</b> dari {{ $fmt($totals['total']) }} beban sudah submit</p>
        </article>

        <article class="kpi">
            <div class="kpi-h">
                <h2>Status pendataan</h2>
                <span class="kpi-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
            </div>
            <dl class="kpi-list">
                <div><dt><i class="sw submit"></i>Submit</dt><dd class="ink-submit">{{ $fmt($totals['submit']) }}</dd></div>
                <div><dt><i class="sw draft"></i>Draft</dt><dd class="ink-draft">{{ $fmt($totals['draft']) }}</dd></div>
                <div><dt><i class="sw open"></i>Open</dt><dd>{{ $fmt($totals['open']) }}</dd></div>
                @if ($totals['other'] > 0)
                    <div><dt><i class="sw other"></i>Lainnya</dt><dd>{{ $fmt($totals['other']) }}</dd></div>
                @endif
            </dl>
            <p class="kpi-foot">Gabungan {{ $runningCount }} survei berjalan</p>
        </article>

        <article class="kpi">
            <div class="kpi-h">
                <h2>Entri PAPI harian</h2>
                <span class="kpi-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M9 15l2 2 4-4"/></svg></span>
            </div>
            <div class="kpi-row">
                <div class="kpi-val">{{ $fmt($dailyTotal) }} <small>entri</small></div>
                <ol class="kpi-spark" aria-label="Entri selesai per hari">
                    @foreach ($dailyEntries as $day)
                        <li title="{{ $day['date']->locale('id')->translatedFormat('l, d M') }}: {{ $fmt($day['count']) }} entri" @if ($day['date']->toDateString() === $today) class="today" @endif>
                            <i style="height:{{ $day['count'] > 0 ? max(8, round($day['count'] / $dailyMax * 100)) : 0 }}%"></i>
                            <span aria-hidden="true">{{ $day['date']->locale('id')->translatedFormat('D') }}</span>
                            <span class="sr-only">{{ $day['date']->locale('id')->translatedFormat('l') }}: {{ $fmt($day['count']) }} entri</span>
                        </li>
                    @endforeach
                </ol>
            </div>
            <p class="kpi-foot">Hari ini <b>{{ $fmt($todayCount) }}</b> entri diselesaikan mitra</p>
        </article>

        <article class="kpi">
            <div class="kpi-h">
                <h2>Survei</h2>
                <span class="kpi-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span>
            </div>
            <div class="kpi-val">{{ $runningCount }} <small>berjalan</small></div>
            <p class="kpi-chips">
                @foreach ($surveyTypeCounts as $label => $count)
                    <span>{{ $count }} {{ $label }}</span>
                @endforeach
            </p>
        </article>
    </section>

    {{-- Kiri: capaian per survei + per kecamatan. Kanan: tindak lanjut. --}}
    <div class="db-main">
        <div class="db-side">
            <section class="db-card" aria-labelledby="dbSurveyTitle">
                <div class="db-card-h">
                    <div>
                        <h2 id="dbSurveyTitle"><span class="db-h-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 14l2 2 4-4"/></svg></span>Capaian per survei</h2>
                        <p>Diurutkan dari tenggat terdekat.</p>
                    </div>
                    @if ($sortedSurveys->isNotEmpty())
                        <div class="seg" role="group" aria-label="Filter status survei" id="dbSurveyFilter">
                            @foreach (['all' => 'Semua', 'Berjalan' => 'Berjalan', 'Selesai' => 'Selesai'] as $value => $label)
                                <button type="button" data-filter="{{ $value }}" aria-pressed="{{ $value === $defaultFilter ? 'true' : 'false' }}">
                                    {{ $label }} <small>{{ $value === 'all' ? $sortedSurveys->count() : $surveyStatusCounts[$value] }}</small>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
                @if ($sortedSurveys->isEmpty())
                    <div class="db-card-b">
                        <div class="db-blank">
                            <b>Belum ada survei</b>
                            <span>{{ $canManage ? 'Survei PAPI disiapkan lewat variabel dan alokasi mitra; survei CAPI cukup dengan file FASIH pertama.' : 'Survei muncul di sini setelah admin membuatnya.' }}</span>
                            @if ($canManage)<a class="b b-primary b-sm" href="/admin/surveys/create">Buat survei pertama</a>@endif
                        </div>
                    </div>
                @else
                    <ul class="db-list" id="dbSurveyList">
                        @foreach ($sortedSurveys as $survey)
                            @php
                                [$chipTone, $chipLabel] = $timeline($survey);
                                $hasTarget = $survey->progress_target > 0;
                                $surveyTone = $hasTarget ? $tone($survey->progress_percent) : 'none';
                                $draft = $survey->breakdown['draft'] ?? 0;
                            @endphp
                            <li data-status="{{ $survey->status }}">
                                <div class="db-srow">
                                    <span style="min-width:0">
                                        <span class="db-srow-title">{{ $survey->title }}</span>
                                        <span class="db-srow-sub">
                                            <span class="db-tag">{{ $survey->typeLabel() }}</span>
                                            @if ($survey->status !== 'Berjalan')<span>{{ $survey->status }}</span>@endif
                                            <span class="db-chip {{ $chipTone }}">{{ $chipLabel }}</span>
                                            @if ($hasTarget && $survey->isBehindSchedule((float) $survey->progress_percent))
                                                @if ($lateCheckpoint = $survey->passedCheckpoint())
                                                    <span class="db-chip rose">Di bawah checkpoint {{ $lateCheckpoint->checkpoint_date->locale('id')->translatedFormat('d M') }} (target {{ $lateCheckpoint->target_percentage }}%)</span>
                                                @else
                                                    <span class="db-chip rose" title="Progres {{ $fmtPct($survey->progress_percent) }}, sedangkan {{ $fmtPct($survey->elapsedPercent()) }} waktu survei sudah berjalan">Tertinggal jadwal</span>
                                                @endif
                                            @endif
                                        </span>
                                    </span>
                                    <span class="db-srow-prog">
                                        <span class="db-minibar">
                                            <i class="fill-good" style="width:{{ $hasTarget ? $share($survey->progress_count, $survey->progress_target) : 0 }}%"></i>
                                            @if ($draft > 0)<i class="fill-warn" style="width:{{ $share($draft, $survey->progress_target) }}%"></i>@endif
                                            @if (($survey->breakdown['open'] ?? 0) > 0)<i class="fill-none" style="width:{{ $share($survey->breakdown['open'], $survey->progress_target) }}%"></i>@endif
                                        </span>
                                        <small>{{ $fmt($survey->progress_count) }} / {{ $fmt($survey->progress_target) }}{{ $draft > 0 ? ' · '.$fmt($draft).' draft' : '' }}</small>
                                    </span>
                                    <span class="db-pct t-{{ $surveyTone }}">{{ $hasTarget ? $fmtPct($survey->progress_percent) : '–' }}</span>
                                </div>
                            </li>
                        @endforeach
                        <li id="dbSurveyEmpty" class="db-empty-row" hidden>Tidak ada survei dengan status ini.</li>
                    </ul>
                @endif
            </section>

            <section class="db-card" aria-labelledby="dbDistrictTitle">
                <div class="db-card-h">
                    <div>
                        <h2 id="dbDistrictTitle"><span class="db-h-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg></span>Progres per kecamatan</h2>
                        <p>Gabungan seluruh survei berjalan. Klik kecamatan untuk melihat progres per kelurahan.</p>
                    </div>
                    <a class="db-link" href="{{ $monitoringUrl }}">Buka monitoring ›</a>
                </div>
                @if ($districtSummary->isEmpty())
                    <div class="db-card-b">
                        <div class="db-blank">
                            <b>Belum ada beban berwilayah</b>
                            <span>Angka per kecamatan muncul setelah survei berjalan punya alokasi ruta (PAPI) atau data FASIH (CAPI).</span>
                        </div>
                    </div>
                @else
                    <div class="tbl-wrap">
                        <table class="tbl db-dist">
                            <thead>
                                <tr>
                                    <th scope="col">Kecamatan</th>
                                    <th scope="col" class="num">Beban</th>
                                    <th scope="col" class="num">Submit</th>
                                    <th scope="col" class="num">Draft</th>
                                    <th scope="col" class="num">Open</th>
                                    <th scope="col" class="prog">Progres submit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($districtSummary as $row)
                                    <tr class="db-dist-row">
                                        <th scope="row">
                                            <button type="button" class="db-dist-toggle" aria-expanded="false" data-district="{{ $row['key'] }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                                                <span>{{ $row['name'] }}<small>{{ $row['mitra_count'] }} pencacah · {{ $row['sls_count'] }} SLS</small></span>
                                            </button>
                                        </th>
                                        <td class="num">{{ $fmt($row['total']) }}</td>
                                        <td class="num ink-submit">{{ $fmt($row['submit']) }}</td>
                                        <td class="num ink-draft">{{ $fmt($row['draft']) }}</td>
                                        <td class="num">{{ $fmt($row['open']) }}</td>
                                        <td class="prog">
                                            <span class="db-prog">
                                                <span class="db-minibar">
                                                    <i class="fill-good" style="width:{{ $share($row['submit'], $row['total']) }}%"></i>
                                                    <i class="fill-warn" style="width:{{ $share($row['draft'], $row['total']) }}%"></i>
                                                    <i class="fill-none" style="width:{{ $share($row['open'], $row['total']) }}%"></i>
                                                </span>
                                                <span class="db-pct t-{{ $tone($row['percent']) }}">{{ $fmtPct($row['percent']) }}</span>
                                            </span>
                                        </td>
                                    </tr>
                                    @foreach ($row['villages'] as $village)
                                        <tr class="db-dist-sub" data-parent="{{ $row['key'] }}" hidden>
                                            <th scope="row">{{ $village['name'] }}<small>{{ $village['mitra_count'] }} pencacah · {{ $village['sls_count'] }} SLS</small></th>
                                            <td class="num">{{ $fmt($village['total']) }}</td>
                                            <td class="num ink-submit">{{ $fmt($village['submit']) }}</td>
                                            <td class="num ink-draft">{{ $fmt($village['draft']) }}</td>
                                            <td class="num">{{ $fmt($village['open']) }}</td>
                                            <td class="prog">
                                                <span class="db-prog">
                                                    <span class="db-minibar">
                                                        <i class="fill-good" style="width:{{ $share($village['submit'], $village['total']) }}%"></i>
                                                        <i class="fill-warn" style="width:{{ $share($village['draft'], $village['total']) }}%"></i>
                                                        <i class="fill-none" style="width:{{ $share($village['open'], $village['total']) }}%"></i>
                                                    </span>
                                                    <span class="db-pct t-{{ $tone($village['percent']) }}">{{ $fmtPct($village['percent']) }}</span>
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <div class="db-side">

            <section class="db-card" aria-labelledby="staleTitle">
                <div class="db-card-h">
                    <div>
                        <h2 id="staleTitle"><span class="db-h-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><circle cx="18" cy="16" r="4"/><path d="M18 14.5V16l1 1"/></svg></span>Mitra belum update
                            @if ($staleAssignments->isNotEmpty())<span class="db-count rose">{{ $staleAssignments->count() }}</span>@endif
                        </h2>
                        <p>Alokasi survei berjalan tanpa entri selesai dalam 3 hari{{ $lateAssignments > 0 ? '. '.$fmt($lateAssignments).' alokasi sudah lewat tenggat' : '' }}. {{ $staleAssignments->count() > 5 ? 'Ditampilkan 5 yang paling lama.' : '' }}</p>
                    </div>
                </div>
                @if ($staleAssignments->isEmpty())
                    <div class="db-allclear">
                        <span class="db-alert-ico green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg></span>
                        <span><b>Tidak ada mitra yang tertinggal</b><span>Semua mitra mengirim entri dalam 3 hari terakhir.</span></span>
                    </div>
                @else
                    <ul class="db-people">
                        @foreach ($staleAssignments->take(5) as $assignment)
                            @php
                                $lastUpdate = $assignment->last_entry_at ? \Illuminate\Support\Carbon::parse($assignment->last_entry_at) : null;
                            @endphp
                            <li>
                                <span style="min-width:0">
                                    <strong>{{ $assignment->mitra->name }}</strong>
                                    <span class="sub" title="{{ $assignment->survey->title }}">{{ $assignment->survey->title }} · {{ $fmt($assignment->current_progress) }}/{{ $fmt($assignment->target) }} ruta</span>
                                </span>
                                <span class="db-chip {{ $lastUpdate ? 'amber' : 'rose' }}">{{ $lastUpdate ? (int) $lastUpdate->diffInDays(now()).' hari' : 'Belum pernah' }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if ($staleAssignments->count() > 5)
                        <div class="db-card-f"><span>{{ $fmt($staleAssignments->count() - 5) }} mitra lainnya belum update.</span></div>
                    @endif
                @endif
            </section>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Filter status pada daftar capaian survei.
    const filter = document.getElementById('dbSurveyFilter');
    const list = document.getElementById('dbSurveyList');
    if (filter && list) {
        const rows = [...list.querySelectorAll('li[data-status]')];
        const empty = document.getElementById('dbSurveyEmpty');
        const apply = (value) => {
            let shown = 0;
            rows.forEach((row) => {
                row.hidden = value !== 'all' && row.dataset.status !== value;
                if (!row.hidden) shown++;
            });
            empty.hidden = shown > 0;
            filter.querySelectorAll('button').forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.filter === value)));
        };
        filter.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-filter]');
            if (button) apply(button.dataset.filter);
        });
        apply(filter.querySelector('[aria-pressed="true"]')?.dataset.filter || 'all');
    }

    // Baris kecamatan membuka/menutup rincian kelurahannya.
    document.querySelector('.db-dist')?.addEventListener('click', (event) => {
        const toggle = event.target.closest('.db-dist-row')?.querySelector('.db-dist-toggle');
        if (!toggle) return;
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        toggle.setAttribute('aria-expanded', String(open));
        document.querySelectorAll('.db-dist-sub[data-parent="' + toggle.dataset.district + '"]').forEach((row) => { row.hidden = !open; });
    });

})();
</script>
@endpush
