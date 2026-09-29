@extends('panel.layout', ['panelTitle' => 'Pegawai BPS', 'pageTitle' => 'Detail Update Mitra'])

@section('menu')
    @include('panel.pegawai.menu')
@endsection

@section('content')
    @php
        $mitra = $entry->assignment->mitra;
        $isWarning = $entry->entry_status === 'submitted' && $entry->is_valid === false;
        $statusText = $entry->is_valid === null ? 'Menunggu' : ($entry->is_valid ? 'Valid' : 'Tidak Valid');
        $statusPill = $entry->is_valid === null ? 'pill-amber' : ($entry->is_valid ? 'pill-green' : 'pill-rose');
        $waNumber = $mitra->phone ? preg_replace('/\D+/', '', $mitra->phone) : null;
        if ($waNumber && str_starts_with($waNumber, '0')) {
            $waNumber = '62'.substr($waNumber, 1);
        }
        $waMessage = 'Halo '.$mitra->name.', kami mendeteksi anomali otomatis pada entri #'.($entry->survey_entry_number ?? $entry->id).' (Ruta '.($entry->no_urut_ruta ?? '-').') di survei '.$entry->survey->title.'. Mohon dicek dan diperbaiki kembali. Terima kasih.';
        $anomalousVariableIds = $isWarning ? \App\Services\EntryAnomalyValidator::anomalousVariableIds($entry) : [];
    @endphp

    @if ($isWarning)
        <div class="card warning-card">
            <div>
                <div class="card-h" style="margin-bottom:4px"><span class="dot"></span>Entri Perlu Perhatian</div>
                <div class="muted">{{ $entry->note ?: 'Sistem mendeteksi adanya anomali pada entrian ini. Mohon periksa kembali data yang di input.' }}</div>
            </div>
            @if ($waNumber)
                <a class="btn btn-wa" target="_blank" rel="noopener" href="https://wa.me/{{ $waNumber }}?text={{ urlencode($waMessage) }}">Hubungi Mitra</a>
            @else
                <span class="pill pill-amber">Nomor WA mitra belum terdaftar</span>
            @endif
        </div>
    @endif

    <div class="grid g2">
        <div class="card">
            <div class="card-h"><span class="dot"></span>Informasi Entri</div>
            <table>
                <tr><th>Survei</th><td>{{ $entry->survey->title }}</td></tr>
                <tr><th>Mitra (PPL)</th><td>{{ $entry->ppl ?? $mitra->name }}</td></tr>
                <tr><th>Kecamatan</th><td>{{ $entry->district?->name ?? '-' }}</td></tr>
                <tr><th>Kelurahan/Pulau</th><td>{{ $entry->village?->name ?? '-' }}</td></tr>
                <tr><th>Kode NKS</th><td>{{ $entry->kode_nks ?? '-' }}</td></tr>
                <tr><th>SLS</th><td>{{ $entry->sls ?? '-' }}</td></tr>
                <tr><th>No Urut Ruta</th><td>{{ $entry->no_urut_ruta ?? '-' }}</td></tr>
                <tr><th>Waktu</th><td>{{ $entry->created_at->format('d/m/Y H:i') }}</td></tr>
                <tr><th>Status Validasi</th><td><span class="pill {{ $statusPill }}">{{ $statusText }}</span></td></tr>
            </table>
        </div>

        <div class="card">
            <div class="card-h"><span class="dot"></span>Dokumentasi</div>
            @if ($entry->evidence_photo_path)
                <a href="{{ asset('storage/'.$entry->evidence_photo_path) }}" target="_blank" rel="noopener" class="evidence-preview">
                    <img src="{{ asset('storage/'.$entry->evidence_photo_path) }}" alt="Foto bukti kunjungan">
                    <span>Buka foto ukuran penuh</span>
                </a>
            @else
                <div class="evidence-empty">Tidak ada foto dokumentasi.</div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-h"><span class="dot"></span>Entrian Variabel Validasi</div>
        @if ($anomalousVariableIds !== [])
            <div class="muted" style="margin-bottom:10px">⚠ Variabel bertanda merah diduga jadi penyebab anomali pada entri ini.</div>
        @endif
        <table>
            <tr><th>Variabel</th><th>Tipe</th><th>Nilai</th></tr>
            @forelse ($entry->values as $value)
                @php($isAnomalousVariable = in_array($value->survey_variable_id, $anomalousVariableIds, true))
                <tr class="{{ $isAnomalousVariable ? 'anomalous-variable-row' : '' }}">
                    <td>
                        @if ($isAnomalousVariable)
                            <span title="Diduga penyebab anomali">⚠</span>
                        @endif
                        {{ $value->variable->name }}
                    </td>
                    <td>{{ $value->variable->data_type }}</td>
                    <td>{{ $value->value }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Tidak ada variabel validasi pada entri ini.</td></tr>
            @endforelse
        </table>
    </div>
@endsection

@push('head')
<style>
    .warning-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
        border-color: #fda4af;
        background: #fff1f2;
    }

    .btn-wa {
        background: #25d366;
        color: #fff;
        white-space: nowrap;
        padding: 6px 12px;
        font-size: 13px;
    }

    .content table th {
        font-size: 11px;
    }

    .content table td {
        font-size: 13px;
    }

    .anomalous-variable-row td {
        background: #fff1f2;
        color: #9f1239;
        font-weight: 700;
    }

    .evidence-preview {
        display: grid;
        gap: 10px;
        color: var(--brand);
        font-weight: 800;
        text-decoration: none;
    }

    .evidence-preview img {
        width: 100%;
        max-height: 420px;
        object-fit: cover;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: var(--soft2);
    }

    .evidence-preview span {
        display: inline-flex;
        width: fit-content;
        border-radius: 999px;
        background: var(--soft);
        padding: 7px 11px;
        font-size: 12.5px;
    }

    .evidence-empty {
        display: grid;
        min-height: 220px;
        place-items: center;
        border: 1px dashed var(--line);
        border-radius: 12px;
        background: var(--soft2);
        color: var(--muted);
        font-weight: 700;
        text-align: center;
    }
</style>
@endpush
