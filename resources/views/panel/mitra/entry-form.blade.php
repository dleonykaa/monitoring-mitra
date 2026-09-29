@extends('panel.layout', ['panelTitle' => 'Mitra BPS', 'pageTitle' => $entry->exists ? 'Edit Entri' : 'Tambah Progress'])

@section('menu')
    @include('panel.mitra.menu')
@endsection

@section('content')
    @php
        $survey = $assignment->survey;
        $action = $entry->exists ? '/mitra/entries/'.$entry->id : '/mitra/surveys/'.$survey->id.'/entries';
    @endphp

    <div class="card">
        <div class="card-h"><span class="dot"></span>{{ $survey->title }}</div>
        <div class="muted">Isi entri progress. Anda bisa menyimpan sebagai draft terlebih dahulu, lalu submit saat data sudah lengkap.</div>
    </div>

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
        @csrf
        @if ($entry->exists)
            @method('PUT')
        @endif

        <div class="card">
            <div class="card-h"><span class="dot"></span> Identitas Entri</div>
            <div class="entry-grid">
                <label>Kecamatan
                    <select name="district_id" style="width:100%">
                        <option value="">Pilih kecamatan</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district->id }}" @selected((int) old('district_id', $entry->district_id) === $district->id)>{{ $district->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Kelurahan / Pulau
                    <select name="village_id" style="width:100%">
                        <option value="">Pilih kelurahan/pulau</option>
                        @foreach ($villages as $village)
                            <option value="{{ $village->id }}" @selected((int) old('village_id', $entry->village_id) === $village->id)>{{ $village->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Kode NKS
                    <input name="kode_nks" value="{{ old('kode_nks', $entry->kode_nks) }}" placeholder="Kode NKS" style="width:100%">
                </label>
                <label>SLS
                    <input name="sls" value="{{ old('sls', $entry->sls) }}" placeholder="SLS" style="width:100%">
                </label>
                <label>No Urut Ruta
                    <input name="no_urut_ruta" value="{{ old('no_urut_ruta', $entry->no_urut_ruta) }}" placeholder="No urut rumah tangga" style="width:100%">
                </label>
                <label>Foto Bukti Kunjungan
                    <input type="file" name="evidence_photo" accept="image/*" style="width:100%">
                    @if ($entry->evidence_photo_path)
                        <span class="muted" style="font-size:11.5px">Foto lama tersimpan. Upload baru jika ingin mengganti.</span>
                    @endif
                </label>
            </div>
        </div>

        <div class="card">
            <div class="card-h"><span class="dot"></span> Variabel Validasi</div>
            <div class="entry-grid">
                @forelse ($survey->variables as $variable)
                    <label>{{ $variable->name }}
                        <input name="variables[{{ $variable->id }}]" value="{{ old('variables.'.$variable->id, $valueMap[$variable->id]->value ?? '') }}" placeholder="Contoh: {{ $variable->example_format ?: '-' }}" style="width:100%">
                        <span class="muted" style="font-size:11.5px">Tipe: {{ $variable->data_type }}. Contoh: {{ $variable->example_format ?: '-' }}</span>
                    </label>
                @empty
                    <div class="muted">Tidak ada variabel validasi.</div>
                @endforelse
            </div>
        </div>

        <div class="card" style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap">
            <a class="btn btn-grey" href="/mitra/surveys/{{ $survey->id }}">Batal</a>
            @if (! $entry->exists || $entry->entry_status === 'draft')
                <button type="submit" name="action" value="draft" class="btn-grey">Simpan Draft</button>
                <button type="submit" name="action" value="submit">Submit Entri</button>
            @else
                <button type="submit" name="action" value="save_changes">Simpan Perubahan</button>
            @endif
        </div>
    </form>
@endsection

@include('panel.mitra.styles')

@push('head')
<style>
    .entry-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 12px;
    }

    .entry-grid label {
        display: grid;
        gap: 6px;
        color: var(--brand-dark);
        font-weight: 700;
        font-size: 13px;
    }
</style>
@endpush
