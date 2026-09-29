@extends('panel.layout', ['panelTitle' => 'Pegawai BPS', 'pageTitle' => 'Riwayat Update'])

@section('menu')
    @include('panel.pegawai.menu')
@endsection

@section('content')
    <div class="card">
        <form method="GET" action="/pegawai/updates" class="updates-filter">
            <select name="mitra_id">
                <option value="">Semua Mitra</option>
                @foreach ($mitraUsers as $mitra)
                    <option value="{{ $mitra->id }}" @selected(($filters['mitra_id'] ?? '') == $mitra->id)>{{ $mitra->name }}</option>
                @endforeach
            </select>
            <select name="district_id">
                <option value="">Semua Kecamatan</option>
                @foreach ($districts as $district)
                    <option value="{{ $district->id }}" @selected(($filters['district_id'] ?? '') == $district->id)>{{ $district->name }}</option>
                @endforeach
            </select>
            <div class="updates-range-filter">
                <span>Pilih Rentang Waktu</span>
                <div>
                    <input type="date" name="modified_from" value="{{ $filters['modified_from'] ?? '' }}" aria-label="Modified dari">
                    <input type="date" name="modified_to" value="{{ $filters['modified_to'] ?? '' }}" aria-label="Modified sampai">
                </div>
            </div>
            <button type="submit">Filter</button>
        </form>
    </div>

    <div class="card updates-table">
        <div class="card-h updates-table-title"><span class="dot"></span> Riwayat Update Survei ({{ $entries->total() }})</div>
        <div class="updates-scroll" tabindex="0" aria-label="Scroll tabel riwayat update">
            <table class="rich-table">
                <thead>
                    <tr>
                        <th>Mitra</th>
                        <th>Survei</th>
                        <th>No Urut Ruta</th>
                        <th>Wilayah</th>
                        <th>Dibuat</th>
                        <th>Dimodifikasi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($entries as $entry)
                    <tr>
                        <td>
                            <div style="line-height:1.2">
                                <div style="font-weight:700">{{ $entry->assignment->mitra->name }}</div>
                                <div class="muted" style="font-size:11.5px">{{ $entry->assignment->mitra->email }}</div>
                            </div>
                        </td>
                        <td>{{ \Illuminate\Support\Str::limit($entry->survey->title, 38) }}</td>
                        <td>{{ $entry->no_urut_ruta ?? '-' }}</td>
                        <td>
                            <div style="line-height:1.2">
                                <div>{{ $entry->district?->name ?? '-' }}</div>
                                <div class="muted" style="font-size:11.5px">{{ $entry->village?->name ?? '-' }}</div>
                            </div>
                        </td>
                        <td><span class="muted">{{ $entry->created_at->format('d M Y') }}</span><br><span style="font-size:12px">{{ $entry->created_at->format('H:i') }}</span></td>
                        <td><span class="muted">{{ $entry->updated_at->format('d M Y') }}</span><br><span style="font-size:12px">{{ $entry->updated_at->format('H:i') }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted" style="text-align:center;padding:26px">Belum ada update sesuai filter.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($entries->hasPages())
        <div class="card updates-pagination">
            @if ($entries->onFirstPage())
                <span class="page-btn disabled">Sebelumnya</span>
            @else
                <a class="page-btn" href="{{ $entries->previousPageUrl() }}">Sebelumnya</a>
            @endif

            <div class="page-numbers">
                @foreach ($entries->getUrlRange(1, $entries->lastPage()) as $page => $url)
                    @if ($page === $entries->currentPage())
                        <span class="page-btn active">{{ $page }}</span>
                    @else
                        <a class="page-btn" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            </div>

            @if ($entries->hasMorePages())
                <a class="page-btn" href="{{ $entries->nextPageUrl() }}">Berikutnya</a>
            @else
                <span class="page-btn disabled">Berikutnya</span>
            @endif
        </div>
    @endif
@endsection

@push('head')
<style>
    .updates-filter {
        display: grid;
        grid-template-columns: minmax(180px, 1fr) minmax(180px, 1fr) minmax(340px, 1.8fr) auto;
        gap: 8px;
        align-items: end;
    }

    .updates-range-filter {
        display: grid;
        gap: 4px;
    }

    .updates-range-filter span {
        color: var(--muted);
        font-size: 11.5px;
        font-weight: 800;
        line-height: 1;
    }

    .updates-range-filter div {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .updates-range-filter input {
        min-width: 0;
        height: 42px;
        padding: 8px 10px;
        font-size: 12.5px;
    }

    .updates-table {
        max-width: 100%;
        overflow: hidden;
        padding: 0;
        min-width: 0;
    }

    .updates-table-title {
        margin: 0;
        padding: 14px 16px;
        border-bottom: 1px solid var(--line);
    }

    .updates-scroll {
        width: 100%;
        max-width: 100%;
        height: calc(100vh - 305px);
        min-height: 340px;
        max-height: 620px;
        overflow: auto;
        overscroll-behavior: contain;
        scrollbar-gutter: stable both-edges;
    }

    .updates-scroll::-webkit-scrollbar {
        width: 12px;
        height: 12px;
    }

    .updates-scroll::-webkit-scrollbar-track {
        background: var(--soft2);
        border: 1px solid var(--line);
    }

    .updates-scroll::-webkit-scrollbar-thumb {
        background: #9db8e6;
        border: 3px solid var(--soft2);
        border-radius: 999px;
    }

    .updates-table .rich-table {
        min-width: 1120px;
    }

    .updates-table .rich-table th {
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .updates-table .rich-table th,
    .updates-table .rich-table td {
        padding: 9px 14px;
    }

    .updates-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        flex-wrap: wrap;
        padding: 8px 12px;
        margin-bottom: 0;
    }

    .page-numbers {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        min-height: 30px;
        padding: 6px 10px;
        border: 1px solid var(--line);
        border-radius: 9px;
        background: var(--soft2);
        color: var(--brand-dark);
        font-size: 12.5px;
        font-weight: 700;
        text-decoration: none;
    }

    .page-btn.active {
        background: var(--brand);
        border-color: var(--brand);
        color: #fff;
    }

    .page-btn.disabled {
        color: var(--muted);
        cursor: not-allowed;
        opacity: .65;
    }

    @media (max-width: 820px) {
        .updates-filter {
            grid-template-columns: 1fr;
        }

        .updates-scroll {
            height: 72vh;
            max-height: 72vh;
        }
    }

    @media (max-width: 560px) {
        .updates-filter {
            grid-template-columns: 1fr;
        }

        .updates-range-filter div {
            grid-template-columns: 1fr;
        }

        .updates-pagination {
            justify-content: center;
        }

        .page-numbers {
            order: 3;
            width: 100%;
        }
    }
</style>
@endpush
