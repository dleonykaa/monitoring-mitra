@extends('panel.layout', ['panelTitle' => 'Pegawai BPS', 'pageTitle' => 'Variabel Validasi'])

@section('menu')
    @include('panel.pegawai.menu')
@endsection

@section('content')
    @php
        $validationRules = old('validation_rules', $survey->validation_rules ?? []);
        if ($validationRules === [] && filled($survey->validation_formula)) {
            $validationRules = [$survey->validation_formula];
        }
        if (! is_array($validationRules) || $validationRules === []) {
            $validationRules = [''];
        }
    @endphp

    <div class="card" style="display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <div class="card-h"><span class="dot"></span>{{ $survey->title }}</div>
            <div class="muted" style="max-width:720px">
                Tambahkan kolom yang akan diisi mitra. Template spreadsheet berisi baris 1 untuk judul blok,
                baris 2 untuk nama kolom, dan baris 3 untuk contoh entrian seperti format SUSENAS.
            </div>
        </div>
        <a class="btn" href="/pegawai/surveys/{{ $survey->id }}/variables/template" style="padding:7px 10px;font-size:12px;">
            Download Template Spreadsheet
        </a>
    </div>

    <div class="card">
        <div class="card-h"><span class="dot"></span>Tambah Variabel</div>
        <form method="POST" action="/pegawai/surveys/{{ $survey->id }}/variables" style="display:grid;grid-template-columns:minmax(240px,2fr) minmax(140px,1fr) minmax(220px,1.5fr) auto;gap:10px;align-items:end;">
            @csrf
            <label>
                <span class="muted" style="display:block;margin-bottom:6px">Nama variabel</span>
                <input name="name" placeholder="Contoh: VSEN26.K BLOK XX R2010.A.(i)" required style="width:100%">
            </label>
            <label>
                <span class="muted" style="display:block;margin-bottom:6px">Tipe data</span>
                <select name="data_type" required style="width:100%">
                    <option value="text">Teks</option>
                    <option value="number">Angka</option>
                </select>
            </label>
            <label>
                <span class="muted" style="display:block;margin-bottom:6px">Contoh pengisian</span>
                <input name="example_format" placeholder="Contoh: 1000000, 10.56, atau sudah" style="width:100%">
            </label>
            <button type="submit">Tambah Variabel</button>
        </form>
    </div>

    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:15px 16px 8px;display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap">
            <div class="card-h" style="margin:0"><span class="dot"></span>Daftar Variabel ({{ $survey->variables->count() }})</div>
        </div>
        <div style="overflow-x:auto">
            <table class="rich-table">
                <thead>
                    <tr>
                        <th style="width:42%">Nama Variabel</th>
                        <th>Tipe Data</th>
                        <th>Contoh Pengisian</th>
                        <th style="text-align:center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($survey->variables as $variable)
                    <tr>
                        <td><input form="ev{{ $variable->id }}" name="name" value="{{ $variable->name }}" required style="width:100%"></td>
                        <td>
                            <select form="ev{{ $variable->id }}" name="data_type" required style="width:100%">
                                <option value="text" @selected($variable->data_type === 'text')>Teks</option>
                                <option value="number" @selected($variable->data_type === 'number')>Angka</option>
                            </select>
                        </td>
                        <td><input form="ev{{ $variable->id }}" name="example_format" value="{{ $variable->example_format }}" placeholder="-" style="width:100%"></td>
                        <td style="white-space:nowrap;text-align:center">
                            <button form="ev{{ $variable->id }}" type="submit" style="padding:7px 12px">Simpan Perubahan</button>
                            <button form="dv{{ $variable->id }}" type="submit" class="btn-danger" style="padding:7px 12px">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted" style="text-align:center;padding:24px">Belum ada variabel validasi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @foreach ($survey->variables as $variable)
            <form id="ev{{ $variable->id }}" method="POST" action="/pegawai/surveys/{{ $survey->id }}/variables/{{ $variable->id }}">@csrf @method('PUT')</form>
            <form id="dv{{ $variable->id }}" method="POST" action="/pegawai/surveys/{{ $survey->id }}/variables/{{ $variable->id }}" onsubmit="return confirm('Hapus variabel ini?')">@csrf @method('DELETE')</form>
        @endforeach
    </div>

    <div class="card">
        <div class="card-h"><span class="dot"></span>Formula Validasi</div>
        <div class="muted" style="max-width:720px;margin-bottom:10px">
            Tambahkan aturan satu per satu. Semua aturan harus terpenuhi. Jika satu aturan gagal, entri ditandai sebagai <b>anomali</b> dan perlu diperiksa.
            Perbandingan nilai teks bersifat <b>case sensitive</b> (contoh: <code>'sudah'</code> tidak sama dengan <code>'Sudah'</code>).
        </div>
        @if ($survey->variables->isNotEmpty())
            <div class="formula-vars">
                <span class="muted" style="display:block;margin-bottom:6px">Identifier yang dapat dipakai dalam aturan:</span>
                @foreach ($survey->variables as $variable)
                    <code>{{ \App\Services\EntryAnomalyValidator::variableIdentifier($variable->name) }}</code>
                @endforeach
            </div>
        @endif
        <form id="validationRulesForm" method="POST" action="/pegawai/surveys/{{ $survey->id }}/validation-formula" style="margin-top:10px;">
            @csrf
            @method('PUT')
            <div id="validationRules" class="validation-rules">
                @foreach ($validationRules as $index => $rule)
                    <div class="validation-rule">
                        <label>
                            <span class="muted">Aturan {{ $index + 1 }}</span>
                            <input name="validation_rules[]" value="{{ $rule }}" placeholder="Contoh: usia >= 0 atau status == 1" style="width:100%">
                        </label>
                        <button type="button" class="btn-grey remove-validation-rule">Hapus</button>
                    </div>
                @endforeach
            </div>
            <div class="validation-actions">
                <button type="button" id="addValidationRule">+Tambah</button>
            </div>
        </form>
    </div>

    <div class="card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <span class="muted">Setelah variabel dan aturan disimpan, lanjutkan untuk mengatur alokasi mitra.</span>
        <button type="submit" form="validationRulesForm">Simpan dan Lanjutkan</button>
        <a class="btn btn-grey" href="/pegawai/surveys" onclick="if (history.length > 1) { history.back(); return false; }">Kembali</a>
    </div>
@endsection

@push('head')
<style>
    .validation-rules {
        display: grid;
        gap: 8px;
    }

    .validation-rule {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 8px;
        align-items: end;
    }

    .validation-rule label {
        display: grid;
        gap: 6px;
    }

    .validation-rule .btn-grey {
        padding: 8px 11px;
        font-size: 12px;
    }

    .validation-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 10px;
    }

    .formula-vars code {
        display: inline-block;
        background: var(--soft);
        border: 1px solid var(--line);
        border-radius: 6px;
        padding: 3px 7px;
        margin: 0 6px 6px 0;
        font-size: 12px;
        color: var(--brand-dark);
    }

    @media (max-width: 960px) {
        form[action*="/variables"] {
            grid-template-columns: 1fr !important;
        }

        .validation-rule {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@push('scripts')
<template id="validationRuleTemplate">
    <div class="validation-rule">
        <label>
            <span class="muted"></span>
            <input name="validation_rules[]" placeholder="Contoh: usia >= 0 atau status == 1" style="width:100%">
        </label>
        <button type="button" class="btn-grey remove-validation-rule">Hapus</button>
    </div>
</template>
<script>
    const validationRules = document.getElementById('validationRules');
    const validationRuleTemplate = document.getElementById('validationRuleTemplate');

    function updateValidationRuleLabels() {
        validationRules.querySelectorAll('.validation-rule').forEach((rule, index) => {
            rule.querySelector('.muted').textContent = 'Aturan ' + (index + 1);
        });
    }

    document.getElementById('addValidationRule').addEventListener('click', () => {
        validationRules.append(validationRuleTemplate.content.cloneNode(true));
        updateValidationRuleLabels();
        validationRules.querySelector('.validation-rule:last-child input').focus();
    });

    validationRules.addEventListener('click', (event) => {
        if (! event.target.classList.contains('remove-validation-rule')) {
            return;
        }

        const rules = validationRules.querySelectorAll('.validation-rule');
        if (rules.length === 1) {
            rules[0].querySelector('input').value = '';

            return;
        }

        event.target.closest('.validation-rule').remove();
        updateValidationRuleLabels();
    });
</script>
@endpush
