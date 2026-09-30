@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Alokasi Mitra'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $initials = fn ($name) => collect(preg_split('/\s+/', trim($name)))->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
    $totalTarget = $survey->assignments->sum('target');
    $isDraft = $survey->status === 'Draft';
@endphp

@section('content')
<div class="ui">
    @include('panel.admin.surveys.setup-header', ['survey' => $survey, 'step' => 'assignments'])

    <div class="cols cols-side">
        <section class="pnl flush" aria-labelledby="assignTitle">
            <div class="pnl-h">
                <div>
                    <h2 id="assignTitle">Mitra teralokasi <span class="num-chip">{{ $survey->assignments->count() }}</span></h2>
                    <p>Total target survei dihitung otomatis dari jumlah target seluruh mitra: <b style="color:var(--text)">{{ $fmt($totalTarget) }} ruta</b>.</p>
                </div>
            </div>
            <div class="pnl-b">
                @if ($survey->assignments->isEmpty())
                    <div class="empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
                        <b>Belum ada alokasi mitra</b>
                        <span>Tambahkan satu per satu atau impor spreadsheet dari panel samping.</span>
                    </div>
                @else
                    <div class="tbl-wrap">
                        <table class="tbl stack" style="min-width:520px">
                            <thead><tr><th>Mitra</th><th>Progres</th><th>Target (ruta)</th><th class="act"><span class="sr-only">Aksi</span></th></tr></thead>
                            <tbody>
                                @foreach ($survey->assignments as $assignment)
                                    <tr>
                                        <td>
                                            <div class="who">
                                                <span class="avatar" aria-hidden="true">{{ $initials($assignment->mitra->name) }}</span>
                                                <span><b>{{ $assignment->mitra->name }}</b></span>
                                            </div>
                                        </td>
                                        <td class="muted-cell" style="white-space:nowrap">{{ $fmt($assignment->current_progress) }} masuk</td>
                                        <td>
                                            <label class="sr-only" for="target-{{ $assignment->id }}">Target {{ $assignment->mitra->name }}</label>
                                            <input id="target-{{ $assignment->id }}" form="ea{{ $assignment->id }}" type="number" min="1" name="target" value="{{ $assignment->target }}" required class="cell-input" style="width:110px">
                                        </td>
                                        <td class="act">
                                            <button form="ea{{ $assignment->id }}" type="submit" class="b b-soft b-sm">Simpan</button>
                                            <button form="da{{ $assignment->id }}" type="submit" class="b b-danger-soft b-sm b-icon" aria-label="Hapus alokasi {{ $assignment->mitra->name }}" title="Hapus alokasi">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @foreach ($survey->assignments as $assignment)
                        <form id="ea{{ $assignment->id }}" method="POST" action="/admin/surveys/{{ $survey->id }}/assignments/{{ $assignment->id }}">@csrf @method('PUT')</form>
                        <form id="da{{ $assignment->id }}" method="POST" action="/admin/surveys/{{ $survey->id }}/assignments/{{ $assignment->id }}" data-confirm="Hapus alokasi {{ $assignment->mitra->name }}?">@csrf @method('DELETE')</form>
                    @endforeach
                @endif
            </div>
            <div class="pnl-f">
                <a class="b b-ghost" href="/admin/surveys/{{ $survey->id }}/variables">Kembali ke form isian</a>
                @if ($isDraft)
                    <form method="POST" action="/admin/surveys/{{ $survey->id }}/finish-setup" data-confirm="Jalankan survei ini? Mitra yang dialokasikan akan langsung bisa mengisi progres.">
                        @csrf
                        <button type="submit" class="b b-primary" @disabled($survey->assignments->isEmpty())>Jalankan survei</button>
                    </form>
                @else
                    <a class="b b-primary" href="/admin/surveys/{{ $survey->id }}">Kembali ke detail survei</a>
                @endif
            </div>
        </section>

        <div class="ui">
            <form class="pnl" id="manualAssignmentForm" method="POST" action="/admin/surveys/{{ $survey->id }}/assignments">
                @csrf
                <div class="pnl-h">
                    <div>
                        <h2>Tambah manual</h2>
                        <p>Isiannya sama dengan kolom template Excel. Nomor urut ruta dibuat otomatis sebanyak target.</p>
                    </div>
                </div>
                <div class="pnl-b ui" style="gap:12px">
                    <div class="fld" style="position:relative">
                        <label for="mitraSearch" class="fld-label">Mitra</label>
                        <input type="search" id="mitraSearch" placeholder="Cari dan pilih mitra" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="mitraResults">
                        <input type="hidden" name="mitra_id" id="mitraId">
                        <div id="mitraResults" class="mitra-results" role="listbox" hidden>
                            @foreach ($mitraUsers as $mitra)
                                <button type="button" class="mitra-result" role="option" data-id="{{ $mitra->id }}" data-name="{{ strtolower($mitra->name) }}" hidden>{{ $mitra->name }}</button>
                            @endforeach
                        </div>
                        <span id="selectedMitra" class="hint" aria-live="polite">Belum ada mitra dipilih.</span>
                    </div>
                    @error('mitra_id')<span class="hint" style="color:var(--st-late-ink);font-weight:600">{{ $message }}</span>@enderror
                    <label class="fld">
                        <span>Kelurahan</span>
                        <select name="village_id" id="villageSelect" required>
                            <option value="">Pilih kelurahan</option>
                            @foreach ($villages as $village)
                                <option value="{{ $village->id }}" @selected((int) old('village_id') === $village->id)>{{ $village->name }} · {{ $village->district?->name }}</option>
                            @endforeach
                        </select>
                        @error('village_id')<span class="hint" style="color:var(--st-late-ink);font-weight:600">{{ $message }}</span>@enderror
                    </label>
                    <label class="fld">
                        <span>SLS</span>
                        <input type="text" name="sls" id="slsInput" list="slsOptions" required maxlength="160" value="{{ old('sls') }}" placeholder="RT 002 RW 001" autocomplete="off">
                        <datalist id="slsOptions"></datalist>
                        @error('sls')<span class="hint" style="color:var(--st-late-ink);font-weight:600">{{ $message }}</span>@enderror
                    </label>
                    <label class="fld">
                        <span>PPL <small>(opsional)</small></span>
                        <input type="text" name="ppl" id="pplInput" maxlength="255" value="{{ old('ppl') }}" placeholder="Nama pencacah lapangan">
                        <span class="hint">Bila kosong, diisi nama mitra.</span>
                    </label>
                    <label class="fld">
                        <span>Target ruta <small>(1–99)</small></span>
                        <input type="number" min="1" max="99" name="target" required value="{{ old('target') }}" placeholder="10">
                        <span class="hint">Diisi 10 berarti 10 ruta dengan no urut berurutan, melanjutkan nomor terakhir di SLS itu.</span>
                        @error('target')<span class="hint" style="color:var(--st-late-ink);font-weight:600">{{ $message }}</span>@enderror
                    </label>
                    <button type="submit" class="b b-primary" style="justify-self:start">Tambah alokasi</button>
                </div>
            </form>

            <form class="pnl" method="POST" action="/admin/surveys/{{ $survey->id }}/assignments/import" enctype="multipart/form-data" id="importForm" @if ($survey->assignments->isNotEmpty()) data-confirm="Impor akan mengganti seluruh alokasi yang ada. Lanjutkan?" @endif>
                @csrf
                <div class="pnl-h">
                    <div>
                        <h2>Impor dari Excel</h2>
                        <p>Satu baris = satu ruta. Mitra dikenali dari <b>email</b> akunnya.</p>
                    </div>
                </div>
                <div class="pnl-b ui" style="gap:12px">
                    <ol class="imp-steps">
                        <li><b>Unduh template</b>, lalu isi lembar <i>Alokasi</i>. <a href="/admin/surveys/{{ $survey->id }}/assignments/template">Unduh template</a></li>
                        <li>Kolom: Kode Prov (31), Kode Kab (01), Kelurahan, Email mitra, SLS, PPL, No Urut Ruta (1–99).</li>
                        <li>Unggah file. Seluruh isi file menjadi alokasi survei ini, dengan status ruta <i>Belum diisi</i>.</li>
                    </ol>
                    @if ($survey->assignments->isNotEmpty())
                        <div class="note note-amber" role="note" style="font-size:12.5px;line-height:1.5">
                            <span>Impor <b>mengganti seluruh alokasi</b> yang ada ({{ $survey->assignments->count() }} mitra, {{ $fmt($totalTarget) }} ruta). Hanya bisa bila belum ada ruta yang diisi mitra.</span>
                        </div>
                    @endif
                    <label class="dropzone" id="importDrop">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required aria-describedby="importHint">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/></svg>
                        <span><b id="importName">Pilih file .xlsx atau .csv</b><small id="importHint">Semua baris dicek dulu; bila ada yang salah, tidak ada data yang disimpan.</small></span>
                    </label>
                    @if (session('import_success'))
                        <div class="note note-green imp-result" role="status">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                            <span><b>Impor berhasil</b>{{ session('import_success') }}</span>
                        </div>
                    @endif
                    @error('file')
                        <div class="note imp-result imp-failed" role="alert">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/></svg>
                            <span><b>Impor gagal</b>{{ $message }}</span>
                        </div>
                    @enderror
                    <button type="submit" class="b b-primary" id="importBtn" style="justify-self:start">
                        <span class="imp-spin" aria-hidden="true" hidden></span>
                        <span id="importBtnLabel">Impor alokasi</span>
                    </button>
                    <p class="imp-wait" id="importWait" role="status" hidden>File sedang dibaca dan dicek baris per baris. Jangan tutup atau muat ulang halaman ini.</p>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('head')
<style>
    .imp-steps{margin:0;padding-left:18px;display:grid;gap:6px;font-size:12.5px;color:var(--muted);line-height:1.5}
    .imp-steps b{color:var(--text)}
    .imp-steps a{color:var(--brand);font-weight:600}
    .imp-spin{width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;animation:impSpin .7s linear infinite}
    .imp-wait{margin:0;font-size:12px;color:var(--muted);line-height:1.5}
    .imp-result{font-size:12.5px;line-height:1.5}
    .imp-result b{display:block;font-size:13.5px}
    .imp-failed{background:var(--t-rose-bg);color:var(--t-rose-fg)}
    #importForm{scroll-margin-top:80px}
    #importBtn[aria-busy="true"]{opacity:.85;cursor:progress}
    @keyframes impSpin{to{transform:rotate(360deg)}}
    @media (prefers-reduced-motion:reduce){.imp-spin{animation-duration:2s}}
</style>
<style>
    .mitra-results{position:absolute;z-index:5;top:72px;right:0;left:0;display:grid;max-height:240px;overflow-y:auto;border:1px solid var(--line);border-radius:10px;background:var(--card);box-shadow:0 12px 28px rgba(15,39,71,.16);padding:4px}
    .mitra-result{all:unset;box-sizing:border-box;padding:9px 12px;border-radius:7px;font-size:13px;color:var(--text);cursor:pointer}
    .mitra-result:hover,.mitra-result:focus-visible{background:var(--row-hover);color:var(--brand)}
</style>
@endpush

@push('scripts')
<script>
(function () {
    const mitraSearch = document.getElementById('mitraSearch');
    const mitraId = document.getElementById('mitraId');
    const mitraResults = document.getElementById('mitraResults');
    const selectedMitra = document.getElementById('selectedMitra');
    const mitraOptions = [...document.querySelectorAll('.mitra-result')];

    function showMitraResults() {
        const keyword = mitraSearch.value.trim().toLowerCase();
        const matches = mitraOptions.filter((option) => option.dataset.name.includes(keyword)).slice(0, 8);
        mitraOptions.forEach((option) => { option.hidden = !matches.includes(option); });
        mitraResults.hidden = keyword === '' || matches.length === 0;
        mitraSearch.setAttribute('aria-expanded', String(!mitraResults.hidden));
    }

    mitraSearch.addEventListener('input', () => {
        mitraId.value = '';
        selectedMitra.textContent = 'Pilih mitra dari hasil pencarian.';
        showMitraResults();
    });

    mitraResults.addEventListener('click', (event) => {
        const option = event.target.closest('.mitra-result');
        if (!option) return;
        mitraId.value = option.dataset.id;
        mitraSearch.value = option.textContent.trim();
        selectedMitra.textContent = 'Mitra dipilih: ' + mitraSearch.value;
        const ppl = document.getElementById('pplInput');
        if (ppl && (ppl.value === '' || ppl.dataset.auto === '1')) { ppl.value = mitraSearch.value; ppl.dataset.auto = '1'; }
        mitraResults.hidden = true;
        mitraSearch.setAttribute('aria-expanded', 'false');
    });

    document.getElementById('manualAssignmentForm').addEventListener('submit', (event) => {
        if (mitraId.value !== '') return;
        event.preventDefault();
        selectedMitra.textContent = 'Pilih mitra dari hasil pencarian terlebih dahulu.';
        mitraSearch.focus();
        showMitraResults();
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('#manualAssignmentForm .fld')) mitraResults.hidden = true;
    });

    // Saran SLS mengikuti kelurahan yang dipilih (dari master SLS); SLS di luar master tetap boleh diketik.
    const slsByVillage = @json($slsByVillage);
    const villageSelect = document.getElementById('villageSelect');
    const slsOptions = document.getElementById('slsOptions');
    const fillSlsOptions = () => {
        slsOptions.replaceChildren(...(slsByVillage[villageSelect.value] || []).map((name) => {
            const option = document.createElement('option');
            option.value = name;

            return option;
        }));
    };
    villageSelect.addEventListener('change', fillSlsOptions);
    fillSlsOptions();
    document.getElementById('pplInput').addEventListener('input', (event) => { event.target.dataset.auto = '0'; });

    // Saat impor dikirim: tombol dikunci dan menampilkan animasi sampai halaman berganti.
    const importForm = document.getElementById('importForm');
    importForm.addEventListener('submit', () => {
        const button = document.getElementById('importBtn');
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.querySelector('.imp-spin').hidden = false;
        document.getElementById('importBtnLabel').textContent = 'Mengimpor…';
        document.getElementById('importWait').hidden = false;
    });
    // Kembali lewat tombol Back browser: tombol dipulihkan.
    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) return;
        const button = document.getElementById('importBtn');
        button.disabled = false;
        button.removeAttribute('aria-busy');
        button.querySelector('.imp-spin').hidden = true;
        document.getElementById('importBtnLabel').textContent = 'Impor alokasi';
        document.getElementById('importWait').hidden = true;
    });

    const drop = document.getElementById('importDrop');
    const file = drop.querySelector('input[type=file]');
    file.addEventListener('change', () => {
        document.getElementById('importName').textContent = file.files[0]?.name || 'Pilih file .xlsx atau .csv';
    });
    ['dragenter', 'dragover'].forEach((type) => drop.addEventListener(type, () => drop.classList.add('is-over')));
    ['dragleave', 'drop'].forEach((type) => drop.addEventListener(type, () => drop.classList.remove('is-over')));
})();
</script>
@endpush
