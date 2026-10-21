@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Buat Survei'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@php
    $selectedType = old('type', \App\Models\Survey::TYPE_PAPI);
    $oldCheckpoints = collect(old('checkpoints', []))->values();
    // Kesalahan pada checkpoint ditampilkan di langkah checkpoint, bukan di langkah informasi.
    $openCheckpointStep = $selectedType === 'capi' && collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'checkpoints'));
@endphp

@section('content')
<div class="ui">
    <div class="pg-head">
        <div>
            <a class="pg-back" href="/admin/surveys">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Daftar survei
            </a>
            <h1>Buat survei baru</h1>
            <p>Pilih metode pendataan dulu, karena langkah setup PAPI dan CAPI berbeda.</p>
        </div>
    </div>

    <div class="cols cols-side">
        <form method="POST" action="/admin/surveys" enctype="multipart/form-data" class="pnl" id="surveyForm">
            @csrf
            <div class="pnl-b ui" style="padding-top:18px;gap:20px">
                <div class="ui" id="infoStep" style="gap:20px" @if ($openCheckpointStep) hidden @endif>
                    <fieldset style="border:0;margin:0;padding:0;display:grid;gap:8px">
                        <legend class="fld-label" style="margin-bottom:8px;padding:0">Metode pendataan</legend>
                        <div class="cols cols-2" style="gap:10px">
                            <label class="choice">
                                <input type="radio" name="type" value="papi" @checked($selectedType === 'papi')>
                                <span><b>PAPI</b><span>Mitra mengisi form monitoring di SIMPROCA, lengkap dengan foto bukti.</span></span>
                            </label>
                            <label class="choice">
                                <input type="radio" name="type" value="capi" @checked($selectedType === 'capi')>
                                <span><b>CAPI</b><span>Pendataan di FASIH. Progres diambil dari file scraping yang Anda unggah.</span></span>
                            </label>
                        </div>
                    </fieldset>

                    <div class="fld-grid">
                        <label class="fld full"><span>Judul survei</span>
                            <input name="title" value="{{ old('title') }}" required placeholder="Masukkan judul survei">
                        </label>
                        <label class="fld"><span>Tanggal mulai</span>
                            <input type="date" name="start_date" id="startDate" value="{{ old('start_date') }}" required>
                        </label>
                        <label class="fld"><span>Tanggal berakhir</span>
                            <input type="date" name="end_date" id="endDate" value="{{ old('end_date') }}" required>
                        </label>
                        <label class="fld full"><span>Deskripsi <small>(opsional)</small></span>
                            <textarea name="description" rows="3" placeholder="Tujuan survei, cakupan wilayah, atau catatan untuk pegawai">{{ old('description') }}</textarea>
                        </label>
                    </div>

                    <div class="fld" id="capiSection" @if ($selectedType !== 'capi') hidden @endif>
                        <span>Data scraping FASIH pertama</span>
                        <label class="dropzone" id="capiDrop">
                            <input type="file" name="file" id="capiFile" accept=".csv,text/csv" aria-describedby="capiHint">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/></svg>
                            <span><b id="capiFileName">Pilih atau seret file CSV ke sini</b><small>Ekspor progres FASIH, maksimal 10 MB</small></span>
                        </label>
                        <span class="hint" id="capiHint">Kolom yang dibaca: <code class="k">email</code>, <code class="k">regionCode</code>, <code class="k">totalRegion</code>, <code class="k">statusBreakdown</code>, dan <code class="k">namaPetugas</code>. Alokasi mitra dan beban per SLS diambil dari file ini.</span>
                    </div>
                </div>

                <div class="ui" id="checkpointStep" style="gap:14px" @unless ($openCheckpointStep) hidden @endunless>
                    <div>
                        <h2 class="fld-label" style="margin:0;font-size:14.5px;color:var(--brand-dark)">Checkpoint <small>(opsional)</small></h2>
                        <p class="hint" style="margin:4px 0 0;font-size:12px;color:var(--muted);line-height:1.5">
                            Target persentase dokumen yang harus diselesaikan tiap mitra sampai tanggal tertentu. Sehari setelah tanggalnya lewat, mitra yang belum mencapai target mendapat notifikasi.
                            <span id="checkpointPeriod"></span>
                        </p>
                    </div>

                    <div class="ui" id="checkpointRows" style="gap:10px"></div>

                    <div class="empty" id="checkpointEmpty" style="padding:18px">
                        <b>Belum ada checkpoint</b>
                        <span>Boleh dikosongkan. Tanpa checkpoint, capaian dinilai dari porsi waktu survei yang sudah berjalan, dan checkpoint bisa ditambahkan nanti dari detail survei.</span>
                    </div>

                    <button type="button" class="b b-soft" id="addCheckpoint" style="justify-self:start">+ Tambah checkpoint</button>
                </div>

                <template id="checkpointTemplate">
                    <div class="fld-row" data-checkpoint>
                        <label class="fld"><span data-label>Checkpoint</span>
                            <input type="date" data-field="date" required>
                        </label>
                        <label class="fld"><span>Target capaian <small>(%)</small></span>
                            <input type="number" data-field="target" required min="1" max="100" placeholder="50">
                        </label>
                        <button type="button" class="b b-danger-soft b-icon" data-remove aria-label="Hapus checkpoint" title="Hapus checkpoint">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                        </button>
                    </div>
                </template>

                <div class="form-foot">
                    <p id="typeHint"></p>
                    <div class="pg-actions" style="width:auto">
                        <a class="b b-soft" href="/admin/surveys" id="cancelBtn">Batal</a>
                        <button type="button" class="b b-soft" id="backBtn" hidden>Kembali</button>
                        <button type="button" class="b b-primary" id="nextBtn" hidden>Lanjut ke checkpoint</button>
                        <button type="submit" class="b b-primary" id="submitBtn">{{ $selectedType === 'capi' ? 'Simpan dan impor data' : 'Simpan dan lanjut ke form' }}</button>
                    </div>
                </div>
            </div>
        </form>

        <aside class="pnl" aria-labelledby="flowTitle">
            <div class="pnl-h"><h2 id="flowTitle">Langkah pembuatan</h2></div>
            <div class="pnl-b">
                <ol class="steps vert" data-flow="papi" @if ($selectedType === 'capi') hidden @endif>
                    <li><span class="cur"><span class="n">1</span><span class="lbl">Informasi survei<small>Formulir di samping</small></span></span></li>
                    <li><a><span class="n">2</span><span class="lbl">Form isian<small>Variabel yang diisi mitra</small></span></a></li>
                    <li><a><span class="n">3</span><span class="lbl">Alokasi mitra<small>Target ruta per mitra, lalu jalankan survei</small></span></a></li>
                </ol>
                <ol class="steps vert" data-flow="capi" @if ($selectedType !== 'capi') hidden @endif>
                    <li><a data-step-item="info"><span class="n">1</span><span class="lbl">Informasi & file FASIH<small>Data scraping pertama</small></span></a></li>
                    <li><a data-step-item="checkpoint"><span class="n">2</span><span class="lbl">Checkpoint<small>Opsional, target bertahap per mitra</small></span></a></li>
                    <li><a><span class="n">3</span><span class="lbl">Monitoring progres<small>Survei langsung berjalan</small></span></a></li>
                </ol>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('surveyForm');
    const infoStep = document.getElementById('infoStep');
    const checkpointStep = document.getElementById('checkpointStep');
    const capiSection = document.getElementById('capiSection');
    const capiFile = document.getElementById('capiFile');
    const capiDrop = document.getElementById('capiDrop');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const rows = document.getElementById('checkpointRows');
    const template = document.getElementById('checkpointTemplate');
    const nextBtn = document.getElementById('nextBtn');
    const backBtn = document.getElementById('backBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const submitBtn = document.getElementById('submitBtn');
    const today = @json(now()->toDateString());
    const oldCheckpoints = @json($oldCheckpoints);
    let step = @json($openCheckpointStep ? 'checkpoint' : 'info');

    const isCapi = () => form.querySelector('input[name=type]:checked')?.value === 'capi';
    const formatDate = (value) => new Date(value + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });

    function render() {
        const capi = isCapi();
        if (!capi) step = 'info';
        const onCheckpoint = step === 'checkpoint';

        infoStep.hidden = onCheckpoint;
        checkpointStep.hidden = !onCheckpoint;
        capiSection.hidden = !capi;
        capiFile.required = capi;
        // Survei CAPI biasanya sudah berjalan di FASIH, jadi tanggal mulai boleh di masa lalu.
        startDate.min = capi ? '' : today;

        cancelBtn.hidden = onCheckpoint;
        backBtn.hidden = !onCheckpoint;
        nextBtn.hidden = !capi || onCheckpoint;
        submitBtn.hidden = capi && !onCheckpoint;
        submitBtn.textContent = capi ? 'Simpan dan impor data' : 'Simpan dan lanjut ke form';
        document.getElementById('typeHint').textContent = !capi
            ? 'Survei PAPI disimpan sebagai Draft sampai setup form dan alokasi selesai.'
            : (onCheckpoint
                ? 'Survei CAPI langsung berjalan setelah data FASIH berhasil diimpor.'
                : 'Berikutnya: checkpoint (opsional), lalu simpan dan impor data.');

        document.querySelector('[data-flow="papi"]').hidden = capi;
        document.querySelector('[data-flow="capi"]').hidden = !capi;
        document.querySelector('[data-step-item="info"]').className = onCheckpoint ? 'done' : 'cur';
        document.querySelector('[data-step-item="checkpoint"]').className = onCheckpoint ? 'cur' : '';

        // Input checkpoint hanya ikut terkirim untuk survei CAPI.
        rows.querySelectorAll('input').forEach((input) => { input.disabled = !capi; });
        syncCheckpoints();
    }

    // Penomoran, batas tanggal sesuai periode survei, dan tampilan kosong mengikuti isi daftar.
    function syncCheckpoints() {
        const items = [...rows.querySelectorAll('[data-checkpoint]')];
        items.forEach((row, index) => {
            row.querySelector('[data-label]').textContent = 'Checkpoint ' + (index + 1) + ', sampai tanggal';
            row.querySelector('[data-field="date"]').name = 'checkpoints[' + index + '][date]';
            row.querySelector('[data-field="target"]').name = 'checkpoints[' + index + '][target]';
            row.querySelector('[data-field="date"]').min = startDate.value;
            row.querySelector('[data-field="date"]').max = endDate.value;
        });
        document.getElementById('checkpointEmpty').hidden = items.length > 0;
        document.getElementById('addCheckpoint').hidden = items.length >= 10;
        document.getElementById('checkpointPeriod').textContent = startDate.value && endDate.value
            ? 'Periode survei ' + formatDate(startDate.value) + ' – ' + formatDate(endDate.value) + '.'
            : '';
    }

    function addCheckpoint(values) {
        const row = template.content.firstElementChild.cloneNode(true);
        row.querySelector('[data-field="date"]').value = values?.date || '';
        row.querySelector('[data-field="target"]').value = values?.target || '';
        row.querySelector('[data-remove]').addEventListener('click', () => { row.remove(); syncCheckpoints(); });
        rows.appendChild(row);
        syncCheckpoints();
        return row;
    }

    // Target harus naik mengikuti urutan tanggal, sama seperti di halaman checkpoint.
    function checkTargetsIncrease() {
        const items = [...rows.querySelectorAll('[data-checkpoint]')].map((row) => ({
            date: row.querySelector('[data-field="date"]').value,
            target: row.querySelector('[data-field="target"]'),
        }));
        items.forEach((item) => item.target.setCustomValidity(''));
        const sorted = items.filter((item) => item.date && item.target.value).sort((a, b) => a.date.localeCompare(b.date));
        for (let i = 1; i < sorted.length; i++) {
            if (Number(sorted[i].target.value) <= Number(sorted[i - 1].target.value)) {
                sorted[i].target.setCustomValidity('Target harus lebih dari ' + sorted[i - 1].target.value + '% (target checkpoint tanggal sebelumnya).');
                return false;
            }
        }
        return true;
    }

    // Isian langkah pertama dicek sebelum pindah langkah; file CSV juga hilang setelah validasi server gagal.
    function infoStepIsValid() {
        const invalid = [...infoStep.querySelectorAll('input, textarea')].find((field) => !field.checkValidity());
        if (!invalid) return true;
        step = 'info';
        render();
        invalid.reportValidity();
        return false;
    }

    function goToCheckpointStep() {
        if (!infoStepIsValid()) return;
        step = 'checkpoint';
        render();
        checkpointStep.scrollIntoView({ block: 'nearest' });
    }

    nextBtn.addEventListener('click', goToCheckpointStep);
    backBtn.addEventListener('click', () => { step = 'info'; render(); });
    // Fokus menunggu isian tanggal baris baru selesai diubah ke dd/mm/yyyy oleh skrip layout.
    document.getElementById('addCheckpoint').addEventListener('click', () => {
        const row = addCheckpoint();
        setTimeout(() => row.querySelector('input').focus());
    });
    rows.addEventListener('input', checkTargetsIncrease);

    form.querySelectorAll('input[name=type]').forEach((radio) => radio.addEventListener('change', render));
    startDate.addEventListener('change', () => {
        endDate.min = startDate.value;
        if (endDate.value && endDate.value < startDate.value) endDate.value = startDate.value;
        syncCheckpoints();
    });
    endDate.addEventListener('change', syncCheckpoints);
    capiFile.addEventListener('change', () => {
        document.getElementById('capiFileName').textContent = capiFile.files[0]?.name || 'Pilih atau seret file CSV ke sini';
    });
    ['dragenter', 'dragover'].forEach((type) => capiDrop.addEventListener(type, () => capiDrop.classList.add('is-over')));
    ['dragleave', 'drop'].forEach((type) => capiDrop.addEventListener(type, () => capiDrop.classList.remove('is-over')));
    // Dicek saat tombol diklik (juga lewat Enter), sebelum validasi bawaan browser menolak isian di langkah yang tersembunyi.
    submitBtn.addEventListener('click', (event) => {
        if (!isCapi()) return;
        if (step === 'info') {
            event.preventDefault();
            goToCheckpointStep();
        } else if (!infoStepIsValid()) {
            event.preventDefault();
        } else {
            checkTargetsIncrease();
        }
    });
    form.addEventListener('submit', () => {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Menyimpan…';
    });

    oldCheckpoints.forEach((checkpoint) => addCheckpoint(checkpoint));
    render();
})();
</script>
@endpush
