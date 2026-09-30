{{-- Daftar survei bersama. Admin: $canManage = true, $base = '/admin'. Pegawai: lihat saja. --}}
@extends('panel.layout', ['panelTitle' => $panelTitle, 'pageTitle' => 'Survei'])

@section('menu')
    @include($menuView)
@endsection

@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $fmtPct = fn ($n) => number_format((float) $n, 1, ',', '.').'%';
    $tone = fn ($p) => $p >= 90 ? 'good' : ($p >= 70 ? 'ok' : ($p >= 50 ? 'warn' : 'bad'));
    $statusBadge = ['Berjalan' => 'bdg-blue', 'Draft' => 'bdg-amber', 'Selesai' => 'bdg-green'];
    $statusCounts = collect(['Berjalan', 'Draft', 'Selesai'])->mapWithKeys(fn ($status) => [$status => $surveys->where('status', $status)->count()]);
    $defaultStatus = $statusCounts['Berjalan'] > 0 ? 'Berjalan' : 'all';
    $today = today();
@endphp

@section('content')
<div class="ui">
    <div class="pg-head">
        <div>
            <h1>{{ $fmt($surveys->count()) }} survei</h1>
            <p>PAPI diisi mitra lewat form SIMPROCA, sedangkan CAPI dipantau dari data scraping FASIH.</p>
        </div>
        @if ($canManage)
            <div class="pg-actions">
                <a class="b b-primary" href="/admin/surveys/create">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Buat survei
                </a>
            </div>
        @endif
    </div>

    <section class="pnl flush" aria-label="Daftar survei">
        <div class="pnl-h">
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                <div class="seg" role="group" aria-label="Filter status" data-filter="status">
                    @foreach (['all' => 'Semua', 'Berjalan' => 'Berjalan', 'Draft' => 'Draft', 'Selesai' => 'Selesai'] as $value => $label)
                        <button type="button" data-value="{{ $value }}" aria-pressed="{{ $value === $defaultStatus ? 'true' : 'false' }}">{{ $label }} <small>{{ $value === 'all' ? $surveys->count() : $statusCounts[$value] }}</small></button>
                    @endforeach
                </div>
                <div class="seg" role="group" aria-label="Filter metode" data-filter="type">
                    <button type="button" data-value="all" aria-pressed="true">Semua metode</button>
                    @foreach (\App\Models\Survey::TYPES as $value => $label)
                        <button type="button" data-value="{{ $value }}" aria-pressed="false">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            <label class="search">
                <span class="sr-only">Cari judul survei</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" id="surveySearch" placeholder="Cari judul survei" autocomplete="off">
            </label>
        </div>
        <div class="pnl-b">
            @if ($surveys->isEmpty())
                <div class="empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4M12 16h4M8 11h.01M8 16h.01"/></svg>
                    <b>Belum ada survei</b>
                    @if ($canManage)
                        <span>Buat survei PAPI atau CAPI untuk mulai memantau progres mitra.</span>
                        <a class="b b-primary" href="/admin/surveys/create" style="margin-top:6px">Buat survei</a>
                    @else
                        <span>Survei yang dibuat admin akan muncul di sini.</span>
                    @endif
                </div>
            @else
                <div class="tbl-wrap">
                    <table class="tbl stack" id="surveyTable" style="min-width:900px">
                        <thead>
                            <tr><th>Survei</th><th>Periode</th><th>Progres</th><th class="num">Capaian</th><th>Status</th><th class="act"><span class="sr-only">Aksi</span></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($surveys as $survey)
                                @php
                                    $hasTarget = $survey->progress_target > 0;
                                    $rowTone = $hasTarget ? $tone($survey->progress_percent) : 'none';
                                    $daysLeft = (int) $today->diffInDays($survey->end_date, false);
                                    $isLocked = $survey->status === 'Selesai';
                                @endphp
                                <tr data-status="{{ $survey->status }}" data-type="{{ $survey->type }}" data-search="{{ strtolower($survey->title) }}">
                                    <td style="max-width:360px">
                                        <a href="{{ $base }}/surveys/{{ $survey->id }}" style="text-decoration:none;color:inherit">
                                            <b style="display:block;font-weight:600;font-size:13.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $survey->title }}</b>
                                        </a>
                                        <span style="display:flex;gap:8px;align-items:center;margin-top:3px;font-size:12px;color:var(--muted)">
                                            <span class="tag">{{ $survey->typeLabel() }}</span>
                                            {{ $survey->isCapi() ? 'Data FASIH' : $fmt($survey->assignments_count).' mitra' }}
                                        </span>
                                    </td>
                                    <td style="white-space:nowrap;font-size:12.5px">
                                        {{ $survey->start_date->locale('id')->translatedFormat('d M') }} – {{ $survey->end_date->locale('id')->translatedFormat('d M Y') }}
                                        <div style="font-size:12px;color:{{ $survey->status === 'Berjalan' && $daysLeft < 0 ? 'var(--t-rose-fg)' : 'var(--muted)' }}">
                                            @if ($isLocked)
                                                Selesai
                                            @elseif ($daysLeft < 0)
                                                Lewat {{ abs($daysLeft) }} hari
                                            @elseif ($daysLeft === 0)
                                                Berakhir hari ini
                                            @else
                                                Sisa {{ $daysLeft }} hari
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="meter" style="max-width:200px">
                                            <span class="bar2"><i class="fill-good" style="width:{{ $survey->progress_percent }}%"></i></span>
                                            <small>{{ $fmt($survey->progress_count) }} / {{ $fmt($survey->progress_target) }} {{ $survey->isCapi() ? 'dokumen' : 'ruta' }}</small>
                                        </div>
                                    </td>
                                    <td class="num"><span class="pct {{ $rowTone }}">{{ $hasTarget ? $fmtPct($survey->progress_percent) : '–' }}</span></td>
                                    <td><span class="bdg bdg-dot {{ $statusBadge[$survey->status] ?? 'bdg-gray' }}">{{ $survey->status }}</span></td>
                                    <td class="act">
                                        @if ($survey->status === 'Berjalan')
                                            <a class="b b-soft b-sm" href="{{ $base }}/monitoring/progres?survey={{ $survey->id }}">Monitoring</a>
                                        @endif
                                        @if ($canManage && $survey->status === 'Berjalan')
                                            <form method="POST" action="/admin/surveys/{{ $survey->id }}/status" data-confirm="Tandai survei {{ $survey->title }} selesai? Survei akan terkunci dan tidak muncul lagi di Monitoring." style="display:inline">
                                            @csrf
                                            <input type="hidden" name="status" value="Selesai">
                                            <button type="submit" class="b b-soft b-sm">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                                Tandai selesai
                                            </button>
                                            </form>
                                        @endif
                                        @if ($canManage && $survey->status === 'Draft' && ! $survey->isCapi())
                                            <a class="b b-primary b-sm" href="/admin/surveys/{{ $survey->id }}/{{ $survey->total_target > 0 ? 'assignments' : 'variables' }}">Lanjutkan setup</a>
                                        @else
                                            <a class="b b-soft b-sm" href="{{ $base }}/surveys/{{ $survey->id }}">Detail</a>
                                        @endif
                                        @if ($canManage && $survey->status === 'Draft')
                                            <form method="POST" action="/admin/surveys/{{ $survey->id }}" style="display:inline" data-confirm="Hapus survei draft {{ $survey->title }}? Form isian, alokasi mitra, dan checkpoint-nya ikut terhapus dan tidak bisa dikembalikan.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="b b-danger-soft b-sm b-icon" aria-label="Hapus survei {{ $survey->title }}" title="Hapus survei draft">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            <tr id="surveyNoMatch" hidden><td colspan="6"><div class="empty" style="padding:22px">Tidak ada survei yang cocok dengan filter ini.</div></td></tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const table = document.getElementById('surveyTable');
    if (!table) return;
    const rows = [...table.querySelectorAll('tbody tr[data-status]')];
    const search = document.getElementById('surveySearch');
    const state = { status: document.querySelector('[data-filter="status"] [aria-pressed="true"]').dataset.value, type: 'all' };

    const apply = () => {
        const term = search.value.trim().toLowerCase();
        let shown = 0;
        rows.forEach((row) => {
            const match = (state.status === 'all' || row.dataset.status === state.status)
                && (state.type === 'all' || row.dataset.type === state.type)
                && (!term || row.dataset.search.includes(term));
            row.hidden = !match;
            if (match) shown++;
        });
        document.getElementById('surveyNoMatch').hidden = shown > 0;
    };

    document.querySelectorAll('[data-filter]').forEach((group) => {
        group.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-value]');
            if (!button) return;
            state[group.dataset.filter] = button.dataset.value;
            group.querySelectorAll('button').forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
            apply();
        });
    });
    search.addEventListener('input', apply);
    apply();
})();
</script>
@endpush
