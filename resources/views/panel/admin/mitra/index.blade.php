@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Daftar Mitra'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
@endphp

@section('content')
<div class="ui">
    <section class="pnl flush" aria-labelledby="mitraTitle">
        <div class="pnl-h">
            <div>
                <h2 id="mitraTitle">Daftar mitra <span class="num-chip">{{ $fmt($mitraUsers->total()) }}</span></h2>
                <p>
                    @if ($search !== '')
                        Hasil pencarian "{{ $search }}". <a href="/admin/mitra" style="color:var(--brand);font-weight:600">Hapus pencarian</a>
                    @else
                        Klik mitra untuk melihat survei yang sedang dan sudah dipegang.
                    @endif
                </p>
            </div>
            <form class="search" method="GET" action="/admin/mitra" role="search">
                <label class="sr-only" for="mitraSearch">Cari nama, email, atau nomor</label>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input id="mitraSearch" type="search" name="q" value="{{ $search }}" placeholder="Cari nama, email, atau nomor" autocomplete="off">
            </form>
        </div>
        <div class="pnl-b">
            @if ($mitraUsers->isEmpty())
                <div class="empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    <b>{{ $search !== '' ? 'Tidak ada mitra yang cocok' : 'Belum ada akun mitra' }}</b>
                    <span>{{ $search !== '' ? 'Coba kata kunci lain.' : 'Tambahkan akun mitra dari Manajemen Pengguna.' }}</span>
                </div>
            @else
                <div class="tbl-wrap">
                    <table class="tbl stack" style="min-width:720px">
                        <thead>
                            <tr><th>Mitra</th><th>No. WhatsApp</th><th class="num">Survei berjalan</th><th class="num">Survei selesai</th><th>Akun</th><th class="act"><span class="sr-only">Aksi</span></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($mitraUsers as $mitra)
                                @php
                                    $items = $holdings->get($mitra->id, collect());
                                    $running = $items->filter(fn ($item) => $item['survey']->status !== 'Selesai')->count();
                                    $completed = $items->count() - $running;
                                @endphp
                                <tr data-href="/admin/mitra/{{ $mitra->id }}" style="cursor:pointer">
                                    <td>
                                        <a class="who" href="/admin/mitra/{{ $mitra->id }}" style="color:inherit;text-decoration:none">
                                            <span><b>{{ $mitra->name }}</b><small>{{ $mitra->email }}</small></span>
                                        </a>
                                    </td>
                                    <td class="muted-cell">{{ $mitra->phone ?: '–' }}</td>
                                    <td class="num">{{ $running ?: '–' }}</td>
                                    <td class="num">{{ $completed ?: '–' }}</td>
                                    <td><span class="bdg bdg-dot {{ $mitra->is_active ? 'bdg-green' : 'bdg-gray' }}">{{ $mitra->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                    <td class="act"><a class="b b-soft b-sm" href="/admin/mitra/{{ $mitra->id }}">Lihat survei</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @if ($mitraUsers->hasPages())
            <div class="pnl-f" style="display:block">{{ $mitraUsers->links() }}</div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('tr[data-href]').forEach((row) => row.addEventListener('click', (event) => {
        if (! event.target.closest('a, button')) window.location.href = row.dataset.href;
    }));
</script>
@endpush
