@extends('panel.layout', ['panelTitle' => 'Mitra BPS', 'pageTitle' => 'Daftar Survei'])

@section('menu')
    @include('panel.mitra.menu')
@endsection

@php
    $survey = $assignment->survey;
    $action = $entry->exists ? '/mitra/entries/'.$entry->id : '/mitra/surveys/'.$survey->id.'/entries';
    $hasPhoto = (bool) $entry->evidence_photo_path;
@endphp

@push('head')
<style>
    .ef-grid{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:16px;align-items:start}
    .ef-photo{display:grid;gap:10px}
    .ef-frame{position:relative;display:grid;place-items:center;aspect-ratio:3/4;border:1.5px dashed var(--line);border-radius:14px;background:var(--soft);overflow:hidden;color:var(--muted);text-align:center;padding:12px;cursor:pointer;transition:border-color .15s ease-out}
    .ef-frame:hover{border-color:var(--brand)}
    .ef-frame input{position:absolute;inset:0;opacity:0;cursor:pointer}
    .ef-frame img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
    .ef-frame svg{width:30px;height:30px;color:var(--brand)}
    .ef-frame b{display:block;margin-top:6px;font-size:13.5px;color:var(--text)}
    .ef-frame small{display:block;font-size:12px;margin-top:2px}
    .ef-actions{position:sticky;bottom:0;z-index:2;display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:12px 16px;box-shadow:0 -4px 16px var(--shadow)}
    .ef-actions p{margin:0;font-size:12px;color:var(--muted);max-width:52ch}
    .ef-actions .btns{display:flex;gap:8px;flex-wrap:wrap}
    .ef-ident{margin:0;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
    .ef-ident div{background:var(--soft);border:1px solid var(--line);border-radius:10px;padding:8px 10px;min-width:0}
    .ef-ident dt{font-size:11.5px;font-weight:600;color:var(--muted)}
    .ef-ident dd{margin:2px 0 0;font-size:13px;font-weight:700;color:var(--text);overflow-wrap:anywhere}
    @media (max-width:640px){.ef-ident{grid-template-columns:repeat(2,minmax(0,1fr))}}
    .fld .err{font-size:12px;color:var(--st-late-ink);font-weight:600}
    [data-theme="dark"] .fld .err{color:#fda4b4}
    @media (max-width:860px){.ef-grid{grid-template-columns:1fr}.ef-frame{aspect-ratio:4/3}}
    @media (max-width:560px){
        .ef-actions{padding:10px 12px;border-radius:14px 14px 0 0;margin:0 -2px}
        .ef-actions p{display:none}
        .ef-actions .btns{width:100%;display:grid;grid-template-columns:auto 1fr 1fr;gap:8px}
    }
</style>
@endpush

@section('content')
<form class="ui" method="POST" action="{{ $action }}" enctype="multipart/form-data" novalidate>
    @csrf
    @if ($entry->exists)
        @method('PUT')
    @endif

    <div class="pg-head">
        <div style="min-width:0">
            <a class="pg-back" href="/mitra/surveys/{{ $survey->id }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                {{ $survey->title }}
            </a>
            <h1>{{ $entry->exists && $entry->no_urut_ruta ? 'Ruta '.$entry->no_urut_ruta : 'Isi ruta baru' }}</h1>
            <p>Simpan sebagai draft kapan saja. Kirim setelah semua isian dan foto bukti lengkap; entri yang sudah dikirim tidak bisa diubah.</p>
        </div>
        @if ($entry->exists)
            <span class="bdg bdg-dot {{ $entry->entry_status === 'draft' ? 'bdg-amber' : 'bdg-gray' }}">{{ $entry->entry_status === 'draft' ? 'Draft' : 'Belum diisi' }}</span>
        @endif
    </div>

    @if ($errors->any())
        <div class="note note-amber" role="alert">
            <span>Belum bisa dikirim: {{ $errors->count() }} isian perlu dilengkapi atau diperbaiki. Periksa kolom bertanda merah.</span>
        </div>
    @endif

    <div class="ef-grid">
        <div class="ui">
            <section class="pnl" aria-labelledby="identityTitle">
                <div class="pnl-h">
                    <div>
                        <h2 id="identityTitle">Identitas ruta</h2>
                        @if ($entry->hasAllocatedIdentity())<p>Ditetapkan admin saat alokasi.</p>@endif
                    </div>
                </div>
                <div class="pnl-b">
                    @if ($entry->hasAllocatedIdentity())
                        <dl class="ef-ident">
                            <div><dt>Kelurahan</dt><dd>{{ $entry->village?->name ?? '–' }}</dd></div>
                            <div><dt>Kecamatan</dt><dd>{{ $entry->district?->name ?? '–' }}</dd></div>
                            <div><dt>SLS</dt><dd>{{ $entry->sls }}</dd></div>
                            <div><dt>No urut ruta</dt><dd>{{ $entry->no_urut_ruta }}</dd></div>
                        </dl>
                    @else
                        <div class="fld-grid">
                            <label class="fld">
                                <span>Kecamatan</span>
                                <select name="district_id" id="districtSelect">
                                    <option value="">Pilih kecamatan</option>
                                    @foreach ($districts as $district)
                                        <option value="{{ $district->id }}" @selected((int) old('district_id', $entry->district_id) === $district->id)>{{ $district->name }}</option>
                                    @endforeach
                                </select>
                                @error('district_id')<span class="err">{{ $message }}</span>@enderror
                            </label>
                            <label class="fld">
                                <span>Desa / kelurahan</span>
                                <select name="village_id" id="villageSelect">
                                    <option value="">Pilih desa/kelurahan</option>
                                    @foreach ($villages as $village)
                                        <option value="{{ $village->id }}" data-district="{{ $village->district_id }}" @selected((int) old('village_id', $entry->village_id) === $village->id)>{{ $village->name }}</option>
                                    @endforeach
                                </select>
                                @error('village_id')<span class="err">{{ $message }}</span>@enderror
                            </label>
                            <label class="fld">
                                <span>SLS</span>
                                <input name="sls" value="{{ old('sls', $entry->sls) }}" placeholder="Contoh: RT 002 RW 001" autocomplete="off">
                                @error('sls')<span class="err">{{ $message }}</span>@enderror
                            </label>
                            <label class="fld">
                                <span>No urut ruta</span>
                                <input name="no_urut_ruta" value="{{ old('no_urut_ruta', $entry->no_urut_ruta) }}" inputmode="numeric" autocomplete="off">
                                @error('no_urut_ruta')<span class="err">{{ $message }}</span>@enderror
                            </label>
                        </div>
                    @endif
                </div>
            </section>

            <section class="pnl" aria-labelledby="variablesTitle">
                <div class="pnl-h">
                    <div>
                        <h2 id="variablesTitle">Isian survei <span class="num-chip">{{ $survey->variables->count() }}</span></h2>
                        <p>Variabel ditentukan admin untuk survei ini.</p>
                    </div>
                </div>
                <div class="pnl-b">
                    @if ($survey->variables->isEmpty())
                        <p style="margin:0;font-size:12.5px;color:var(--muted)">Survei ini hanya memerlukan identitas ruta dan foto bukti.</p>
                    @else
                        <div class="ui" style="gap:14px">
                            @foreach ($survey->variables as $variable)
                                <label class="fld">
                                    <span>{{ $variable->name }} <small>{{ $variable->data_type === 'number' ? 'angka' : 'teks' }}</small></span>
                                    <input name="variables[{{ $variable->id }}]"
                                           value="{{ old('variables.'.$variable->id, $valueMap[$variable->id]->value ?? '') }}"
                                           @if ($variable->data_type === 'number') inputmode="decimal" @endif
                                           placeholder="{{ $variable->example_format ? 'Contoh: '.$variable->example_format : '' }}"
                                           autocomplete="off">
                                    @error('variables.'.$variable->id)<span class="err">{{ $message }}</span>@enderror
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <section class="pnl" aria-labelledby="photoTitle">
            <div class="pnl-h"><h2 id="photoTitle">Foto bukti</h2></div>
            <div class="pnl-b ef-photo">
                <label class="ef-frame" id="photoFrame">
                    <input type="file" name="evidence_photo" id="photoInput" accept="image/*" capture="environment" aria-describedby="photoHint">
                    <img id="photoPreview" src="{{ $hasPhoto ? asset('storage/'.$entry->evidence_photo_path) : '' }}" alt="Pratinjau foto bukti" @unless ($hasPhoto) hidden @endunless>
                    <span id="photoEmpty" @if ($hasPhoto) hidden @endif>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        <b>Ambil atau pilih foto</b>
                        <small>JPG/PNG, maks. 5 MB</small>
                    </span>
                </label>
                <p id="photoHint" style="margin:0;font-size:12px;color:var(--muted)">
                    Foto saat pencacahan, misalnya bersama responden atau kuesioner terisi. Wajib saat mengirim.
                    @if ($hasPhoto) Klik foto untuk menggantinya. @endif
                </p>
                @error('evidence_photo')<span class="err" style="font-size:12px;color:var(--st-late-ink);font-weight:600">{{ $message }}</span>@enderror
            </div>
        </section>
    </div>

    <div class="ef-actions">
        <p>Draft bisa dilanjutkan dari menu Daftar Survei. Setelah dikirim, progres Anda bertambah dan entri terkunci.</p>
        <div class="btns">
            <a class="b b-ghost" href="/mitra/surveys/{{ $survey->id }}">Batal</a>
            <button type="submit" name="action" value="draft" class="b b-soft">Simpan draft</button>
            <button type="submit" name="action" value="submit" class="b b-primary" data-confirm="Kirim entri ini? Setelah dikirim, entri tidak bisa diubah lagi.">Kirim</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    (function () {
        const input = document.getElementById('photoInput');
        input.addEventListener('change', () => {
            const file = input.files[0];
            if (! file) return;
            const preview = document.getElementById('photoPreview');
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
            document.getElementById('photoEmpty').hidden = true;
        });

        // Daftar desa mengikuti kecamatan yang dipilih.
        const district = document.getElementById('districtSelect');
        const village = document.getElementById('villageSelect');
        if (! district || ! village) return;
        const filterVillages = () => {
            [...village.options].forEach((option) => {
                if (! option.value) return;
                const matches = ! district.value || option.dataset.district === district.value;
                option.hidden = ! matches;
                if (! matches && option.selected) village.value = '';
            });
        };
        district.addEventListener('change', filterVillages);
        filterVillages();
    })();
</script>
@endpush
