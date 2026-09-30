@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Buat Survei'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@php
    $selectedType = old('type', \App\Models\Survey::TYPE_PAPI);
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

                <div class="form-foot">
                    <p id="typeHint">{{ $selectedType === 'capi' ? 'Survei CAPI langsung berjalan setelah data FASIH berhasil diimpor.' : 'Survei PAPI disimpan sebagai Draft sampai setup form dan alokasi selesai.' }}</p>
                    <div class="pg-actions" style="width:auto">
                        <a class="b b-soft" href="/admin/surveys">Batal</a>
                        <button type="submit" class="b b-primary" id="submitBtn">{{ $selectedType === 'capi' ? 'Simpan dan impor data' : 'Simpan dan lanjut ke form' }}</button>
                    </div>
                </div>
            </div>
        </form>

        <aside class="pnl" aria-labelledby="flowTitle">
            <div class="pnl-h"><h2 id="flowTitle">Setelah disimpan</h2></div>
            <div class="pnl-b">
                <ol class="steps vert" data-flow="papi" @if ($selectedType === 'capi') hidden @endif>
                    <li><span class="cur"><span class="n">1</span><span class="lbl">Informasi survei<small>Formulir di samping</small></span></span></li>
                    <li><a><span class="n">2</span><span class="lbl">Form isian<small>Variabel yang diisi mitra</small></span></a></li>
                    <li><a><span class="n">3</span><span class="lbl">Alokasi mitra<small>Target ruta per mitra, lalu jalankan survei</small></span></a></li>
                </ol>
                <ol class="steps vert" data-flow="capi" @if ($selectedType !== 'capi') hidden @endif>
                    <li><span class="cur"><span class="n">1</span><span class="lbl">Informasi & file FASIH<small>Formulir di samping</small></span></span></li>
                    <li><a><span class="n">2</span><span class="lbl">Monitoring progres<small>Survei langsung berjalan</small></span></a></li>
                    <li><a><span class="n">3</span><span class="lbl">Impor data berkala<small>Dari halaman Monitoring Progres</small></span></a></li>
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
    const capiSection = document.getElementById('capiSection');
    const capiFile = document.getElementById('capiFile');
    const capiDrop = document.getElementById('capiDrop');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const today = @json(now()->toDateString());

    function applyType() {
        const isCapi = form.querySelector('input[name=type]:checked')?.value === 'capi';
        capiSection.hidden = !isCapi;
        capiFile.required = isCapi;
        // Survei CAPI biasanya sudah berjalan di FASIH, jadi tanggal mulai boleh di masa lalu.
        startDate.min = isCapi ? '' : today;
        document.getElementById('typeHint').textContent = isCapi
            ? 'Survei CAPI langsung berjalan setelah data FASIH berhasil diimpor.'
            : 'Survei PAPI disimpan sebagai Draft sampai setup form dan alokasi selesai.';
        document.getElementById('submitBtn').textContent = isCapi ? 'Simpan dan impor data' : 'Simpan dan lanjut ke form';
        document.querySelector('[data-flow="papi"]').hidden = isCapi;
        document.querySelector('[data-flow="capi"]').hidden = !isCapi;
    }

    form.querySelectorAll('input[name=type]').forEach((radio) => radio.addEventListener('change', applyType));
    startDate.addEventListener('change', () => {
        endDate.min = startDate.value;
        if (endDate.value && endDate.value < startDate.value) endDate.value = startDate.value;
    });
    capiFile.addEventListener('change', () => {
        document.getElementById('capiFileName').textContent = capiFile.files[0]?.name || 'Pilih atau seret file CSV ke sini';
    });
    ['dragenter', 'dragover'].forEach((type) => capiDrop.addEventListener(type, () => capiDrop.classList.add('is-over')));
    ['dragleave', 'drop'].forEach((type) => capiDrop.addEventListener(type, () => capiDrop.classList.remove('is-over')));
    form.addEventListener('submit', () => {
        const button = document.getElementById('submitBtn');
        button.disabled = true;
        button.textContent = 'Menyimpan…';
    });
    applyType();
})();
</script>
@endpush
