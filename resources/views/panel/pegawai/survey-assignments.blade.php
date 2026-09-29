@extends('panel.layout', ['panelTitle' => 'Pegawai BPS', 'pageTitle' => 'Alokasi Mitra'])

@section('menu')
    @include('panel.pegawai.menu')
@endsection

@section('content')
    @php $totalTarget = $survey->assignments->sum('target'); @endphp

    <div class="card" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
        <div>
            <div class="card-h" style="margin:0"><span class="dot"></span>{{ $survey->title }}</div>
            <div class="muted">Total target dihitung otomatis dari akumulasi target mitra.</div>
        </div>
        <span class="pill pill-blue" style="font-size:14px">🎯 Total Target: {{ $totalTarget }}</span>
    </div>

    <div class="grid g2">
        <div class="card">
            <div class="card-h"><span class="dot"></span>Tambah Manual</div>
            <form id="manualAssignmentForm" method="POST" action="/pegawai/surveys/{{ $survey->id }}/assignments" style="display:grid;gap:8px;">
                @csrf
                <div class="mitra-picker">
                    <label for="mitraSearch" class="sr-only">Cari mitra</label>
                    <input type="search" id="mitraSearch" placeholder="Cari dan pilih mitra" autocomplete="off" aria-autocomplete="list" aria-controls="mitraResults">
                    <input type="hidden" name="mitra_id" id="mitraId">
                    <div id="mitraResults" class="mitra-results" role="listbox" hidden>
                    @foreach ($mitraUsers as $mitra)
                        <button type="button" class="mitra-result" role="option" data-id="{{ $mitra->id }}" data-name="{{ strtolower($mitra->name) }}" hidden>
                            {{ $mitra->name }}
                        </button>
                    @endforeach
                    </div>
                </div>
                <span id="selectedMitra" class="muted" aria-live="polite">Belum ada mitra dipilih.</span>
                <input type="number" min="1" name="target" placeholder="Target mitra" required>
                <button type="submit">Tambah Alokasi</button>
            </form>
        </div>

        <div class="card">
            <div class="card-h"><span class="dot"></span>Upload Spreadsheet</div>
            <form method="POST" action="/pegawai/surveys/{{ $survey->id }}/assignments/import" enctype="multipart/form-data" style="display:grid;gap:8px;">
                @csrf
                <div class="muted">
                    Kolom (urutan harus sama persis): <b>Kode Prov, Kode Kab, Kecamatan, Kelurahan, Kode NKS, SLS, PPL, No Urut Ruta</b>.
                    Setiap baris menjadi 1 alokasi Ruta untuk mitra yang namanya cocok dengan kolom PPL.
                    <a href="/pegawai/surveys/{{ $survey->id }}/assignments/template">Unduh template kosong</a>.
                </div>
                <div style="display:grid;gap:6px">
                    <label style="display:flex;gap:8px;align-items:flex-start">
                        <input type="radio" name="import_mode" value="append" checked style="margin-top:3px">
                        <span>
                            <b>Tambahkan sebagai alokasi baru</b><br>
                            <span class="muted">Data dari file akan menambah alokasi baru dan memperbarui mitra yang sama jika sudah ada.</span>
                        </span>
                    </label>
                    <label style="display:flex;gap:8px;align-items:flex-start">
                        <input type="radio" name="import_mode" value="replace" style="margin-top:3px">
                        <span>
                            <b>Ganti alokasi yang sudah ada</b><br>
                            <span class="muted">Semua alokasi lama pada survei ini dihapus, lalu diganti dengan isi file.</span>
                        </span>
                    </label>
                </div>
                <input type="file" name="file" accept=".xlsx,.csv" required>
                <button type="submit">Import Alokasi</button>
            </form>
        </div>
    </div>

    <div class="card" style="padding:0;overflow:hidden">
        <div class="card-h" style="padding:15px 16px 0;margin-bottom:8px"><span class="dot"></span>Mitra Teralokasi ({{ $survey->assignments->count() }})</div>
        <div style="overflow-x:auto">
            <table class="rich-table" style="min-width:480px">
                <thead><tr><th style="width:55%">Mitra</th><th>Target</th><th style="text-align:center">Aksi</th></tr></thead>
                <tbody>
                @forelse ($survey->assignments as $assignment)
                    @php $ini = collect(explode(' ', $assignment->mitra->name))->take(2)->map(fn($p)=>mb_substr($p,0,1))->join(''); @endphp
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:9px">
                                <span class="mini-avatar">{{ strtoupper($ini) }}</span>
                                <b style="white-space:nowrap">{{ $assignment->mitra->name }}</b>
                            </div>
                        </td>
                        <td><input form="ea{{ $assignment->id }}" type="number" min="1" name="target" value="{{ $assignment->target }}" required style="width:120px"></td>
                        <td style="white-space:nowrap;text-align:center">
                            <button form="ea{{ $assignment->id }}" type="submit" style="padding:7px 11px">Simpan Perubahan</button>
                            <button form="da{{ $assignment->id }}" type="submit" class="btn-danger" style="padding:7px 11px">🗑️</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted" style="text-align:center;padding:22px">Belum ada alokasi mitra.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @foreach ($survey->assignments as $assignment)
            <form id="ea{{ $assignment->id }}" method="POST" action="/pegawai/surveys/{{ $survey->id }}/assignments/{{ $assignment->id }}">@csrf @method('PUT')</form>
            <form id="da{{ $assignment->id }}" method="POST" action="/pegawai/surveys/{{ $survey->id }}/assignments/{{ $assignment->id }}" onsubmit="return confirm('Hapus alokasi mitra ini?')">@csrf @method('DELETE')</form>
        @endforeach
    </div>

    <div class="card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <span class="muted">Perubahan alokasi tersimpan setiap kali tambah, import, ubah target, atau hapus alokasi dilakukan.</span>
        @if ($survey->status === 'Draft')
            <a class="btn" href="/pegawai/surveys/{{ $survey->id }}/checkpoints">Simpan dan Lanjutkan</a>
        @else
            <a class="btn" href="/pegawai/surveys/{{ $survey->id }}">Simpan Perubahan</a>
        @endif
        <a class="btn btn-grey" href="/pegawai/surveys/{{ $survey->id }}" onclick="if (history.length > 1) { history.back(); return false; }">Kembali ke Detail Survei</a>
    </div>

@push('scripts')
    <script>
    const mitraSearch = document.getElementById('mitraSearch');
    const mitraId = document.getElementById('mitraId');
    const mitraResults = document.getElementById('mitraResults');
    const selectedMitra = document.getElementById('selectedMitra');
    const manualAssignmentForm = document.getElementById('manualAssignmentForm');
    const mitraOptions = Array.from(document.querySelectorAll('.mitra-result'));

    function showMitraResults() {
        const keyword = mitraSearch.value.trim().toLowerCase();
        const matches = mitraOptions.filter((option) => option.dataset.name.includes(keyword)).slice(0, 8);

        mitraOptions.forEach((option) => {
            option.hidden = ! matches.includes(option);
        });

        mitraResults.hidden = keyword === '' || matches.length === 0;
    }

    mitraSearch.addEventListener('input', () => {
        mitraId.value = '';
        selectedMitra.textContent = 'Pilih mitra dari hasil pencarian.';
        showMitraResults();
    });

    mitraResults.addEventListener('click', (event) => {
        const option = event.target.closest('.mitra-result');
        if (! option) {
            return;
        }

        mitraId.value = option.dataset.id;
        mitraSearch.value = option.textContent.trim();
        selectedMitra.textContent = 'Mitra dipilih: ' + mitraSearch.value;
        mitraResults.hidden = true;
    });

    manualAssignmentForm.addEventListener('submit', (event) => {
        if (mitraId.value !== '') {
            return;
        }

        event.preventDefault();
        selectedMitra.textContent = 'Pilih mitra dari hasil pencarian terlebih dahulu.';
        mitraSearch.focus();
        showMitraResults();
    });

    document.addEventListener('click', (event) => {
        if (! event.target.closest('.mitra-picker')) {
            mitraResults.hidden = true;
        }
    });
    </script>
    @endpush

@push('head')
<style>
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    .mitra-picker {
        position: relative;
    }

    .mitra-results {
        position: absolute;
        z-index: 2;
        top: calc(100% + 4px);
        right: 0;
        left: 0;
        display: grid;
        max-height: 220px;
        overflow-y: auto;
        border: 1px solid var(--line);
        border-radius: 10px;
        background: var(--card);
        box-shadow: 0 6px 14px rgba(15, 39, 71, .12);
    }

    .mitra-result {
        border-radius: 0;
        background: var(--card);
        color: var(--text);
        padding: 9px 12px;
        text-align: left;
    }

    .mitra-result:hover {
        background: var(--soft);
        box-shadow: none;
    }

    .mitra-results[hidden] {
        display: none;
    }
</style>
@endpush
@endsection
