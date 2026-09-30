@extends('panel.layout', ['panelTitle' => $panelTitle, 'pageTitle' => 'Data Entri PAPI'])

@section('menu')
    @include($menuView)
@endsection

@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $pageUrl = $base.'/entri-papi';
    $baseParams = array_filter([
        'survey' => $survey?->id,
        'status' => $filters['status'],
        'q' => $filters['q'],
        'from' => $filters['from'],
        'to' => $filters['to'],
    ], fn ($value) => filled($value));
    $url = fn (array $params = [], string $path = '') => $pageUrl.$path.'?'.http_build_query(array_filter([...$baseParams, ...$params], fn ($value) => filled($value)));
    $sortUrl = fn (string $key) => $url(['sort' => $key, 'dir' => $sort === $key && $dir === 'asc' ? 'desc' : 'asc']);
    $sortState = fn (string $key) => $sort === $key ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none';
    $exportUrl = fn (string $format) => $url(['sort' => $sort, 'dir' => $dir, 'format' => $format], '/export');
    $hasFilter = $filters['q'] !== '' || $filters['from'] || $filters['to'];
    $statusTone = ['submitted' => 'bdg-green', 'draft' => 'bdg-amber', 'open' => 'bdg-gray'];
    $variables = $survey?->variables ?? collect();
@endphp

@push('head')
<style>
    .pe-picker{display:flex;flex-wrap:wrap;align-items:center;gap:8px 12px;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:10px 14px;box-shadow:0 2px 8px var(--shadow)}
    .pe-picker label{display:inline-flex;align-items:center;gap:7px;font-size:12px;font-weight:600;color:var(--muted);white-space:nowrap}
    .pe-picker label svg{width:16px;height:16px;color:var(--brand)}
    .pe-picker .sel{position:relative;flex:1 1 280px;min-width:0;max-width:520px}
    .pe-picker select{appearance:none;-webkit-appearance:none;width:100%;min-height:38px;padding:7px 34px 7px 12px;border:1px solid var(--line);border-radius:10px;background:var(--soft);color:var(--text);font-size:13.5px;font-weight:600;cursor:pointer}
    .pe-picker .sel svg{position:absolute;right:10px;top:50%;width:16px;height:16px;transform:translateY(-50%);color:var(--muted);pointer-events:none}
    .pe-filters{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end}
    .pe-filters .fld{flex:0 1 160px}
    .pe-filters .fld.grow{flex:1 1 240px}
    .pe-table th .sort-link{color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:4px}
    .pe-table th .sort-link::after{content:"↕";opacity:.35;font-size:10px}
    .pe-table th[aria-sort="ascending"] .sort-link,.pe-table th[aria-sort="descending"] .sort-link{color:var(--brand)}
    .pe-table th[aria-sort="ascending"] .sort-link::after{content:"↑";opacity:1}
    .pe-table th[aria-sort="descending"] .sort-link::after{content:"↓";opacity:1}
    .pe-table th.var{background:#eef4ff}
    [data-theme="dark"] .pe-table th.var{background:#1b2d4f}
    .pe-table td.var{max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .pe-table tbody tr{cursor:pointer}
    .pe-export{display:flex;gap:8px;flex-wrap:wrap}
</style>
@endpush

@section('content')
<div class="ui">
    <div class="pg-head">
        <div>
            <h1>Data Entri PAPI</h1>
            <p>Isian mitra untuk survei PAPI: identitas ruta, variabel form isian, dan foto bukti pencacahan.</p>
        </div>
        @if ($survey)
            <div class="pe-export">
                <a class="b b-green" href="{{ $exportUrl('xlsx') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11M7 10l5 5 5-5"/><path d="M5 20h14"/></svg>
                    Unduh Excel
                </a>
                <a class="b b-soft" href="{{ $exportUrl('csv') }}">Unduh CSV</a>
            </div>
        @endif
    </div>

    <form class="pe-picker" method="GET" action="{{ $pageUrl }}" aria-label="Pilih survei PAPI">
        <label for="peSurvey">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 3H2l8 9.46V19l4 2v-8.54z"/></svg>
            Pilih survei
        </label>
        <div class="sel">
            <select id="peSurvey" name="survey" onchange="this.form.submit()" @disabled($surveys->isEmpty())>
                @forelse ($surveys as $item)
                    <option value="{{ $item->id }}" @selected($survey?->is($item))>{{ $item->title }} · {{ $item->status }}</option>
                @empty
                    <option>Belum ada survei PAPI</option>
                @endforelse
            </select>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </div>
        <noscript><button type="submit" class="b b-soft b-sm">Tampilkan</button></noscript>
    </form>

    @if (! $survey)
        <div class="pnl">
            <div class="empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></svg>
                <b>Belum ada survei PAPI</b>
                <span>Data entri muncul setelah admin membuat survei PAPI dan mitra mulai mengisi.</span>
            </div>
        </div>
    @else
        <div class="toolbar">
            <nav class="seg" aria-label="Status entri">
                @foreach ($statusTabs as $key => $label)
                    <a href="{{ $url(['status' => $key]) }}" @if ($filters['status'] === $key) aria-current="page" @endif>
                        {{ $label }} <small>{{ $fmt($statusCounts->get($key, 0)) }}</small>
                    </a>
                @endforeach
            </nav>
        </div>

        <section class="pnl flush" aria-labelledby="entriesTitle">
            <div class="pnl-h">
                <div>
                    <h2 id="entriesTitle">{{ $survey->title }} <span class="num-chip">{{ $fmt($entries->total()) }}</span></h2>
                    <p>{{ $variables->count() }} variabel isian</p>
                </div>
            </div>
            <div class="pnl-b" style="padding:0 18px 14px;border-bottom:1px solid var(--line)">
                <form class="pe-filters" method="GET" action="{{ $pageUrl }}" role="search">
                    <input type="hidden" name="survey" value="{{ $survey->id }}">
                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <input type="hidden" name="dir" value="{{ $dir }}">
                    <label class="fld grow"><span class="hint">Cari</span>
                        <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Nama/email mitra, kelurahan, SLS, no ruta" autocomplete="off">
                    </label>
                    <label class="fld"><span class="hint">Diperbarui dari</span><input type="date" name="from" value="{{ $filters['from'] }}"></label>
                    <label class="fld"><span class="hint">Sampai</span><input type="date" name="to" value="{{ $filters['to'] }}"></label>
                    <button type="submit" class="b b-primary">Terapkan</button>
                    @if ($hasFilter)<a class="b b-ghost" href="{{ $pageUrl }}?{{ http_build_query(['survey' => $survey->id, 'status' => $filters['status']]) }}">Reset</a>@endif
                </form>
                @error('to')<p class="hint" style="margin:8px 0 0;color:var(--st-late-ink);font-weight:600">{{ $message }}</p>@enderror
            </div>
            <div class="pnl-b">
                @if ($entries->isEmpty())
                    <div class="empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></svg>
                        <b>{{ $hasFilter ? 'Tidak ada entri yang cocok' : 'Belum ada entri '.strtolower($statusTabs[$filters['status']]) }}</b>
                        <span>{{ $hasFilter ? 'Ubah kata kunci atau rentang tanggal.' : 'Entri muncul di sini setelah mitra mengisi progres.' }}</span>
                    </div>
                @else
                    <div class="tbl-wrap">
                        <table class="tbl pe-table" style="min-width:{{ 980 + $variables->count() * 150 }}px">
                            <thead>
                                <tr>
                                    <th class="rank">No</th>
                                    <th aria-sort="{{ $sortState('mitra') }}"><a class="sort-link" href="{{ $sortUrl('mitra') }}">Mitra</a></th>
                                    <th aria-sort="{{ $sortState('kelurahan') }}"><a class="sort-link" href="{{ $sortUrl('kelurahan') }}">Wilayah</a></th>
                                    <th aria-sort="{{ $sortState('sls') }}"><a class="sort-link" href="{{ $sortUrl('sls') }}">SLS</a></th>
                                    <th aria-sort="{{ $sortState('ruta') }}" class="num"><a class="sort-link" href="{{ $sortUrl('ruta') }}">No ruta</a></th>
                                    @foreach ($variables as $variable)
                                        <th class="var" aria-sort="{{ $sortState('var_'.$variable->id) }}" title="{{ $variable->name }}">
                                            <a class="sort-link" href="{{ $sortUrl('var_'.$variable->id) }}">{{ \Illuminate\Support\Str::limit($variable->name, 28) }}</a>
                                        </th>
                                    @endforeach
                                    <th aria-sort="{{ $sortState('status') }}"><a class="sort-link" href="{{ $sortUrl('status') }}">Status</a></th>
                                    <th aria-sort="{{ $sortState('submitted') }}"><a class="sort-link" href="{{ $sortUrl('submitted') }}">Dikirim</a></th>
                                    <th aria-sort="{{ $sortState('updated') }}"><a class="sort-link" href="{{ $sortUrl('updated') }}">Diperbarui</a></th>
                                    <th class="act"><span class="sr-only">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($entries as $entry)
                                    @php($values = $entry->values->pluck('value', 'survey_variable_id'))
                                    <tr data-href="{{ $pageUrl }}/{{ $entry->id }}">
                                        <td class="rank">{{ $entries->firstItem() + $loop->index }}</td>
                                        <td>
                                            <a class="who" href="{{ $pageUrl }}/{{ $entry->id }}" style="color:inherit;text-decoration:none">
                                                <span><b>{{ $entry->assignment->mitra->name }}</b><small>{{ $entry->assignment->mitra->email }}</small></span>
                                            </a>
                                        </td>
                                        <td style="font-size:12.5px;white-space:nowrap">{{ $entry->village?->name ?? '–' }}<small style="display:block;color:var(--muted)">{{ $entry->district?->name ?? '' }}</small></td>
                                        <td style="font-size:12.5px;white-space:nowrap">{{ $entry->sls ?: '–' }}</td>
                                        <td class="num">{{ $entry->no_urut_ruta ?: '–' }}</td>
                                        @foreach ($variables as $variable)
                                            @php($value = $values->get($variable->id))
                                            <td class="var {{ $variable->data_type === 'number' ? 'num' : '' }}" title="{{ $value }}">{{ filled($value) ? $value : '–' }}</td>
                                        @endforeach
                                        <td><span class="bdg bdg-dot {{ $statusTone[$entry->entry_status] ?? 'bdg-gray' }}">{{ $entry->statusLabel() }}</span></td>
                                        <td class="muted-cell" style="white-space:nowrap">{{ $entry->submitted_at?->translatedFormat('d M Y, H:i') ?? '–' }}</td>
                                        <td class="muted-cell" style="white-space:nowrap">{{ $entry->updated_at?->translatedFormat('d M Y, H:i') }}</td>
                                        <td class="act"><a class="b b-soft b-sm" href="{{ $pageUrl }}/{{ $entry->id }}">Detail</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            @if ($entries->hasPages())
                <div class="pnl-f" style="display:block">{{ $entries->links() }}</div>
            @endif
        </section>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.pe-table tr[data-href]').forEach((row) => row.addEventListener('click', (event) => {
        if (! event.target.closest('a, button')) window.location.href = row.dataset.href;
    }));
</script>
@endpush
