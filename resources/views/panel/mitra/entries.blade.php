@extends('panel.layout', ['panelTitle' => 'Mitra BPS', 'pageTitle' => 'Data Entri'])

@section('menu')
    @include('panel.mitra.menu')
@endsection

@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $hasFilter = $filters['survey'] !== null || $filters['q'] !== '';
@endphp

@push('head')
<style>
    .me-filters{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end}
    .me-filters .fld{flex:1 1 200px}
    .me-filters .fld.grow{flex:2 1 260px}
    .me-tbl tbody tr[data-href]{cursor:pointer}
</style>
@endpush

@section('content')
<div class="ui">
    <div class="pg-head">
        <div>
            <h1>Data entri</h1>
            <p>Entri yang sudah Anda kirim. Entri terkirim terkunci dan tidak bisa diubah lagi.</p>
        </div>
    </div>

    <section class="pnl flush" aria-labelledby="entriesTitle">
        <div class="pnl-h">
            <h2 id="entriesTitle">Entri terkirim <span class="num-chip">{{ $fmt($entries->total()) }}</span></h2>
        </div>
        <div class="pnl-b" style="padding:0 18px 14px;border-bottom:1px solid var(--line)">
            <form class="me-filters" method="GET" action="/mitra/data-entri" role="search">
                <label class="fld"><span class="hint">Survei</span>
                    <select name="survey">
                        <option value="">Semua survei</option>
                        @foreach ($surveys as $survey)
                            <option value="{{ $survey->id }}" @selected($filters['survey'] === $survey->id)>{{ $survey->title }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="fld grow"><span class="hint">Cari</span>
                    <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Kelurahan, SLS, no ruta" autocomplete="off">
                </label>
                <button type="submit" class="b b-primary">Terapkan</button>
                @if ($hasFilter)<a class="b b-ghost" href="/mitra/data-entri">Reset</a>@endif
            </form>
        </div>
        <div class="pnl-b">
            @if ($entries->isEmpty())
                <div class="empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></svg>
                    <b>{{ $hasFilter ? 'Tidak ada entri yang cocok' : 'Belum ada entri terkirim' }}</b>
                    <span>{{ $hasFilter ? 'Ubah survei atau kata kunci pencarian.' : 'Entri muncul di sini setelah Anda mengirimnya dari Daftar Survei.' }}</span>
                </div>
            @else
                <div class="tbl-wrap">
                    <table class="tbl stack me-tbl" style="min-width:680px">
                        <thead>
                            <tr>
                                <th class="rank">No</th>
                                <th>Survei</th>
                                <th>Wilayah</th>
                                <th>SLS</th>
                                <th class="num">No ruta</th>
                                <th>Dikirim</th>
                                <th class="act"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entries as $entry)
                                <tr data-href="/mitra/data-entri/{{ $entry->id }}">
                                    <td class="rank">{{ $entries->firstItem() + $loop->index }}</td>
                                    <td><b style="font-weight:600">{{ $entry->survey->title }}</b></td>
                                    <td style="font-size:12.5px">{{ $entry->village?->name ?? '–' }}<small style="display:block;color:var(--muted)">{{ $entry->district?->name ?? '' }}</small></td>
                                    <td style="font-size:12.5px">{{ $entry->sls ?: '–' }}</td>
                                    <td class="num">{{ $entry->no_urut_ruta ?: '–' }}</td>
                                    <td class="muted-cell" style="white-space:nowrap">{{ $entry->submitted_at?->locale('id')->translatedFormat('d M Y, H:i') ?? '–' }}</td>
                                    <td class="act"><a class="b b-soft b-sm" href="/mitra/data-entri/{{ $entry->id }}">Detail</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @if ($entries->hasPages())
            <div class="pnl-f">{{ $entries->links('vendor.pagination.custom') }}</div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
// Klik di mana pun pada baris membuka detail entri.
document.querySelectorAll('.me-tbl tbody tr[data-href]').forEach((row) => {
    row.addEventListener('click', (event) => {
        if (!event.target.closest('a')) window.location.href = row.dataset.href;
    });
});
</script>
@endpush
