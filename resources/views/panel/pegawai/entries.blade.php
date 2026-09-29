@extends('panel.layout', ['panelTitle' => 'Pegawai BPS', 'pageTitle' => 'Data Entri'])

@section('menu')
    @include('panel.pegawai.menu')
@endsection

@section('content')
    @php
        $baseParams = array_filter([
            'survey_id' => $selectedSurveyId,
            'modified_from' => $filters['modified_from'] ?? null,
            'modified_to' => $filters['modified_to'] ?? null,
            'ppl_search' => $pplSearch ?: null,
        ], fn ($value) => filled($value));
        $exportQuery = http_build_query($baseParams);
        $allTabQuery = http_build_query($baseParams);
        $invalidTabQuery = http_build_query($baseParams + ['status' => 'invalid']);

        $sortParams = array_filter($baseParams + ['status' => $statusFilter ?: null], fn ($value) => filled($value));
        $sortLink = function (string $key) use ($sortParams, $sortKey, $sortDir) {
            $nextDir = ($sortKey === $key && $sortDir === 'asc') ? 'desc' : 'asc';

            return '/pegawai/entries?'.http_build_query($sortParams + ['sort' => $key, 'direction' => $nextDir]);
        };
        $sortArrow = fn (string $key) => $sortKey === $key ? ($sortDir === 'asc' ? '▲' : '▼') : '';
    @endphp

    <div class="card entries-filter-card">
        <div class="card-h" style="margin-bottom:10px"><span class="dot"></span> Pilih Survei</div>
        <form method="GET" action="/pegawai/entries" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <select name="survey_id" style="min-width:300px;" onchange="this.form.submit()">
                <option value="">Pilih jenis survei</option>
                @foreach ($surveys as $survey)
                    <option value="{{ $survey->id }}" @selected($selectedSurveyId === $survey->id)>{{ $survey->title }}</option>
                @endforeach
            </select>
            <div class="entry-range-filter">
                <span>Pilih Rentang Waktu</span>
                <div>
                    <input type="date" name="modified_from" value="{{ $filters['modified_from'] ?? '' }}" aria-label="Modified dari">
                    <input type="date" name="modified_to" value="{{ $filters['modified_to'] ?? '' }}" aria-label="Modified sampai">
                </div>
            </div>
            <input type="search" name="ppl_search" value="{{ $pplSearch }}" placeholder="Cari PPL" style="min-width:160px" aria-label="Cari PPL">
            <button type="submit">Tampilkan</button>
            @if ($selectedSurvey)
                <a class="btn btn-green" href="/pegawai/entries/export?{{ $exportQuery }}&format=xlsx" style="padding:9px 14px;">Export XLSX</a>
                <a class="btn btn-green" href="/pegawai/entries/export?{{ $exportQuery }}&format=csv" style="padding:9px 14px;">Export CSV</a>
            @endif
        </form>
    </div>

    @if (! $selectedSurvey)
        <div class="card entries-empty-hint">
            <div class="entries-empty-ico">📋</div>
            <div style="font-weight:800;font-size:17px;color:var(--brand-dark)">Pilih jenis survei terlebih dahulu</div>
            <div class="muted" style="max-width:520px;margin:8px auto 0">
                Kolom data entri akan mengikuti variabel validasi pada survei yang dipilih.
            </div>
        </div>
    @else
        @php $vars = $selectedSurvey->variables; @endphp
        <div class="card entries-survey-summary">
            <div>
                <div class="entries-survey-title">{{ $selectedSurvey->title }}</div>
                <div class="muted">{{ $vars->count() }} variabel validasi</div>
            </div>
            <div class="entries-status-tabs" role="tablist" aria-label="Filter status entri">
                <a href="/pegawai/entries?{{ $allTabQuery }}" class="{{ $statusFilter !== 'invalid' ? 'active' : '' }}">
                    Semua <b>{{ $entryCounts['total'] }}</b>
                </a>
                <a href="/pegawai/entries?{{ $invalidTabQuery }}" class="{{ $statusFilter === 'invalid' ? 'active warn' : '' }}">
                    ⚠ Perlu Perhatian <b>{{ $entryCounts['invalid'] }}</b>
                </a>
            </div>
        </div>

        <div class="card entries-table">
            <div class="entries-table-toolbar">
                <div>
                    <strong>Preview entri</strong>
                    <span>Geser tabel untuk melihat seluruh variabel validasi</span>
                </div>
            </div>
            <div class="entries-scroll" tabindex="0" aria-label="Scroll tabel data entri">
                <table class="rich-table">
                    <thead>
                        <tr>
                            <th class="entry-col-anchor">Entri</th>
                            <th>Kode Prov</th>
                            <th>Kode Kab</th>
                            <th><a href="{{ $sortLink('kecamatan') }}" class="sort-link {{ $sortKey === 'kecamatan' ? 'active' : '' }}">Kecamatan {{ $sortArrow('kecamatan') }}</a></th>
                            <th><a href="{{ $sortLink('kelurahan') }}" class="sort-link {{ $sortKey === 'kelurahan' ? 'active' : '' }}">Kelurahan {{ $sortArrow('kelurahan') }}</a></th>
                            <th><a href="{{ $sortLink('kode_nks') }}" class="sort-link {{ $sortKey === 'kode_nks' ? 'active' : '' }}">Kode NKS {{ $sortArrow('kode_nks') }}</a></th>
                            <th><a href="{{ $sortLink('sls') }}" class="sort-link {{ $sortKey === 'sls' ? 'active' : '' }}">SLS {{ $sortArrow('sls') }}</a></th>
                            <th><a href="{{ $sortLink('ppl') }}" class="sort-link {{ $sortKey === 'ppl' ? 'active' : '' }}">PPL {{ $sortArrow('ppl') }}</a></th>
                            <th><a href="{{ $sortLink('no_urut_ruta') }}" class="sort-link {{ $sortKey === 'no_urut_ruta' ? 'active' : '' }}">No Urut Ruta {{ $sortArrow('no_urut_ruta') }}</a></th>
                            <th><a href="{{ $sortLink('submit') }}" class="sort-link {{ $sortKey === 'submit' ? 'active' : '' }}">Submit {{ $sortArrow('submit') }}</a></th>
                            <th><a href="{{ $sortLink('modified') }}" class="sort-link {{ $sortKey === 'modified' ? 'active' : '' }}">Modified {{ $sortArrow('modified') }}</a></th>
                            @foreach ($vars as $v)
                                @php $varSortKey = 'var_'.$v->id; @endphp
                                <th><a href="{{ $sortLink($varSortKey) }}" class="sort-link {{ $sortKey === $varSortKey ? 'active' : '' }}">{{ $v->name }} {{ $sortArrow($varSortKey) }}</a></th>
                            @endforeach
                            <th class="entry-col-aksi" style="text-align:center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($entries as $entry)
                        @php
                            $valid = $entry->is_valid;
                            $isInvalid = $valid === false;
                            $pill = $valid === null ? 'pill-amber' : ($valid ? 'pill-green' : 'pill-rose');
                            $txt = $valid === null ? 'Menunggu' : ($valid ? 'Valid' : 'Tidak Valid');
                            $wasRevised = ! $entry->updated_at->equalTo($entry->created_at);
                            $valueMap = $entry->values->keyBy('survey_variable_id');
                        @endphp
                        <tr class="{{ $isInvalid ? 'entry-row-invalid' : '' }}">
                            <td class="entry-col-anchor">
                                <div class="entry-anchor">
                                    <span class="entry-anchor-id">#{{ $entry->survey_entry_number ?? $entry->id }}</span>
                                    <span class="pill {{ $pill }}">{{ $txt }}</span>
                                </div>
                            </td>
                            <td>31</td>
                            <td>01</td>
                            <td>{{ $entry->district?->name ?? '-' }}</td>
                            <td>{{ $entry->village?->name ?? '-' }}</td>
                            <td>{{ $entry->kode_nks ?? '-' }}</td>
                            <td>{{ $entry->sls ?? '-' }}</td>
                            <td style="font-weight:600">{{ $entry->ppl ?? $entry->assignment->mitra->name }}</td>
                            <td>{{ $entry->no_urut_ruta ?? '-' }}</td>
                            <td class="entry-time-cell">
                                {{ $entry->created_at->format('d M Y') }}<br>
                                <span>{{ $entry->created_at->format('H:i') }}</span>
                            </td>
                            <td class="entry-time-cell">
                                {{ $entry->updated_at->format('d M Y') }}<br>
                                <span>{{ $entry->updated_at->format('H:i') }}</span>
                                @if ($wasRevised)
                                    <span class="pill pill-blue entry-revised-badge">Direvisi</span>
                                @endif
                            </td>
                            @foreach ($vars as $v)
                                <td>{{ $valueMap[$v->id]->value ?? '-' }}</td>
                            @endforeach
                            <td class="entry-col-aksi">
                                <a class="btn btn-grey" href="/pegawai/updates/{{ $entry->id }}" style="padding:6px 11px">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 12 + $vars->count() }}" class="entries-empty-row">
                                @if ($statusFilter === 'invalid')
                                    ✅ Tidak ada entri yang perlu perhatian. Semua entri pada survei ini valid.
                                @else
                                    Belum ada entri pada survei ini.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($entries->hasPages())
            <div class="card entries-pagination-native">{{ $entries->links() }}</div>
        @endif
    @endif
@endsection

@push('head')
<style>
    .entries-empty-hint {
        text-align: center;
        padding: 46px 20px;
    }

    .entries-empty-ico {
        font-size: 30px;
        margin-bottom: 6px;
    }

    .entries-survey-summary {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        width: 100%;
        padding: 13px 16px;
        margin-bottom: 12px;
    }

    .entries-survey-title {
        font-weight: 800;
        font-size: 16px;
        color: var(--brand-dark);
    }

    .entries-status-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .entries-status-tabs a {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 10px;
        background: var(--soft);
        border: 1px solid var(--line);
        color: var(--muted);
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: .15s;
    }

    .entries-status-tabs a:hover {
        background: var(--row-hover);
        color: var(--brand-dark);
    }

    .entries-status-tabs a b {
        font-weight: 800;
    }

    .entries-status-tabs a.active {
        background: var(--brand);
        border-color: var(--brand);
        color: #fff;
    }

    .entries-status-tabs a.active.warn {
        background: #be123c;
        border-color: #be123c;
    }

    .entries-filter-card {
        width: 100%;
        padding: 12px 16px;
        margin-bottom: 12px;
    }

    .entries-filter-card .card-h {
        margin-bottom: 8px !important;
    }

    .entry-range-filter {
        display: grid;
        gap: 4px;
        min-width: 340px;
        margin: 0;
    }

    .entry-range-filter span {
        color: var(--muted);
        font-size: 11.5px;
        font-weight: 800;
        line-height: 1;
    }

    .entry-range-filter div {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .entry-range-filter input {
        height: 42px;
        min-width: 0;
        padding: 8px 10px;
        font-size: 12.5px;
    }

    .entries-table {
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        padding: 0;
        min-width: 0;
        margin-left: 0;
        margin-right: 0;
    }

    .entries-table-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 14px;
        border-bottom: 1px solid var(--line);
        background: var(--card);
    }

    .entries-table-toolbar strong {
        display: block;
        color: var(--brand-dark);
        font-size: 13.5px;
        line-height: 1.2;
    }

    .entries-table-toolbar span {
        display: block;
        color: var(--muted);
        font-size: 11.5px;
        margin-top: 2px;
    }

    .entries-scroll {
        display: block;
        width: 100%;
        max-width: 100%;
        height: clamp(460px, calc(100vh - 330px), 680px);
        overflow: auto;
        overscroll-behavior: contain;
        scrollbar-gutter: stable both-edges;
    }

    .entries-scroll::-webkit-scrollbar {
        width: 12px;
        height: 12px;
    }

    .entries-scroll::-webkit-scrollbar-track {
        background: var(--soft2);
        border: 1px solid var(--line);
    }

    .entries-scroll::-webkit-scrollbar-thumb {
        background: #9db8e6;
        border: 3px solid var(--soft2);
        border-radius: 999px;
    }

    .entries-scroll:focus {
        outline: 2px solid rgba(37, 99, 235, .35);
        outline-offset: -2px;
    }

    .entries-table .rich-table {
        width: max-content;
        min-width: 100%;
        table-layout: auto;
    }

    .entries-table .rich-table th {
        position: sticky;
        top: 0;
        z-index: 4;
        padding: 6px 12px;
        font-size: 11px;
        line-height: 1.25;
        vertical-align: middle;
        white-space: nowrap;
    }

    .entries-table .rich-table th a.sort-link {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: inherit;
        text-decoration: none;
        cursor: pointer;
    }

    .entries-table .rich-table th a.sort-link:hover {
        color: var(--brand);
    }

    .entries-table .rich-table th a.sort-link.active {
        color: var(--brand);
        font-weight: 800;
    }

    .entries-table .rich-table td {
        padding: 7px 12px;
        font-size: 12.5px;
        line-height: 1.2;
        white-space: nowrap;
    }

    .entries-table .pill {
        font-size: 11px;
        padding: 3px 8px;
    }

    .entries-table button,
    .entries-table .btn {
        font-size: 12px;
        padding: 5px 9px !important;
        border-radius: 8px;
    }

    .entries-table .rich-table th:nth-child(4),
    .entries-table .rich-table td:nth-child(4),
    .entries-table .rich-table th:nth-child(5),
    .entries-table .rich-table td:nth-child(5),
    .entries-table .rich-table th:nth-child(8),
    .entries-table .rich-table td:nth-child(8) {
        min-width: 140px;
    }

    .entries-table .entry-time-cell {
        min-width: 116px;
        color: var(--brand-dark);
        font-weight: 600;
    }

    .entries-table .entry-time-cell span {
        color: var(--muted);
        font-size: 11.5px;
        font-weight: 500;
    }

    .entry-revised-badge {
        display: block;
        width: fit-content;
        margin-top: 3px;
        font-size: 9.5px;
        padding: 1px 6px;
    }

    /* Kolom identitas + status dibekukan di kiri agar tetap terlihat saat menggeser
       tabel melewati banyak kolom variabel validasi. */
    .entries-table .rich-table th.entry-col-anchor,
    .entries-table .rich-table td.entry-col-anchor {
        position: sticky;
        left: 0;
        z-index: 3;
        min-width: 128px;
        background: var(--card);
        box-shadow: 8px 0 14px rgba(15, 39, 71, .06);
    }

    .entries-table .rich-table th.entry-col-anchor {
        background: var(--soft);
        z-index: 5;
    }

    .entry-anchor {
        display: flex;
        flex-direction: column;
        gap: 4px;
        align-items: flex-start;
    }

    .entry-anchor-id {
        font-weight: 700;
        color: var(--brand-dark);
    }

    .entries-table .rich-table th.entry-col-aksi,
    .entries-table .rich-table td.entry-col-aksi {
        min-width: 100px;
        text-align: center;
        white-space: nowrap;
        position: sticky;
        right: 0;
        z-index: 3;
        background: var(--card);
        box-shadow: -8px 0 14px rgba(15, 39, 71, .06);
    }

    .entries-table .rich-table th.entry-col-aksi {
        background: var(--soft);
        z-index: 5;
    }

    /* Baris dengan anomali otomatis diberi rona merah muda agar mudah dipantau sekilas. */
    .entries-table .rich-table tbody tr.entry-row-invalid td {
        background: #fff1f2;
    }

    .entries-table .rich-table tbody tr.entry-row-invalid td.entry-col-anchor,
    .entries-table .rich-table tbody tr.entry-row-invalid td.entry-col-aksi {
        background: #fff1f2;
    }

    .entries-table .rich-table tbody tr:hover td.entry-col-anchor,
    .entries-table .rich-table tbody tr:hover td.entry-col-aksi {
        background: var(--row-hover);
    }

    .entries-table .rich-table tbody tr.entry-row-invalid:hover td {
        background: #ffe1e4;
    }

    .entries-empty-row {
        text-align: center;
        padding: 30px 16px;
        color: var(--muted);
        font-weight: 600;
        white-space: normal;
    }

    .entries-pagination-native {
        overflow: hidden;
        padding: 12px 14px;
    }

    @media (max-width: 880px) {
        .entry-range-filter {
            width: 100%;
            min-width: 0;
        }

        .entries-survey-summary {
            flex-direction: column;
            align-items: stretch;
        }

        .entries-status-tabs a {
            flex: 1;
            justify-content: center;
        }

        .entries-scroll {
            height: 72vh;
            max-height: 72vh;
            min-height: 320px;
            margin-inline: -1px;
        }
    }

    @media (max-width: 560px) {
        .entry-range-filter div {
            grid-template-columns: 1fr;
        }

        .entries-status-tabs {
            flex-direction: column;
        }

        .entries-status-tabs a {
            justify-content: space-between;
        }
    }
</style>
@endpush
