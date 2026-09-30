@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Form Isian Survei'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@php
    $typeLabels = ['text' => 'Teks', 'number' => 'Angka'];
@endphp

@section('content')
<div class="ui">
    @include('panel.admin.surveys.setup-header', ['survey' => $survey, 'step' => 'variables'])

    <div class="cols cols-side">
        <section class="pnl flush" aria-labelledby="variablesTitle">
            <div class="pnl-h">
                <div>
                    <h2 id="variablesTitle">Variabel isian <span class="num-chip">{{ $survey->variables->count() }}</span></h2>
                    <p>Kolom yang diisi mitra untuk setiap ruta, selain identitas wilayah dan foto bukti.</p>
                </div>
            </div>
            <div class="pnl-b">
                @if ($survey->variables->isEmpty())
                    <div class="empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M4 12h10M4 18h7"/></svg>
                        <b>Belum ada variabel isian</b>
                        <span>Tambahkan dari panel samping. Tanpa variabel, mitra hanya mengisi identitas ruta dan foto bukti.</span>
                    </div>
                @else
                    <div class="tbl-wrap">
                        <table class="tbl stack" style="min-width:620px">
                            <thead><tr><th style="width:46%">Nama variabel</th><th>Tipe</th><th>Contoh pengisian</th><th class="act"><span class="sr-only">Aksi</span></th></tr></thead>
                            <tbody>
                                @foreach ($survey->variables as $variable)
                                    <tr data-view="var-{{ $variable->id }}">
                                        <td style="font-weight:600">{{ $variable->name }}</td>
                                        <td><span class="tag">{{ $typeLabels[$variable->data_type] ?? $variable->data_type }}</span></td>
                                        <td class="muted-cell">{{ $variable->example_format ?: '–' }}</td>
                                        <td class="act">
                                            <button type="button" class="b b-soft b-sm" data-toggle="var-{{ $variable->id }}">Ubah</button>
                                            <button type="submit" form="dv{{ $variable->id }}" class="b b-danger-soft b-sm">Hapus</button>
                                        </td>
                                    </tr>
                                    <tr data-edit="var-{{ $variable->id }}" hidden>
                                        <td><label class="sr-only" for="vn{{ $variable->id }}">Nama variabel</label><input id="vn{{ $variable->id }}" form="ev{{ $variable->id }}" name="name" value="{{ $variable->name }}" required class="cell-input"></td>
                                        <td>
                                            <label class="sr-only" for="vt{{ $variable->id }}">Tipe data</label>
                                            <select id="vt{{ $variable->id }}" form="ev{{ $variable->id }}" name="data_type" required class="cell-input">
                                                @foreach ($typeLabels as $value => $label)
                                                    <option value="{{ $value }}" @selected($variable->data_type === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><label class="sr-only" for="ve{{ $variable->id }}">Contoh pengisian</label><input id="ve{{ $variable->id }}" form="ev{{ $variable->id }}" name="example_format" value="{{ $variable->example_format }}" class="cell-input"></td>
                                        <td class="act">
                                            <button type="submit" form="ev{{ $variable->id }}" class="b b-primary b-sm">Simpan</button>
                                            <button type="button" class="b b-soft b-sm" data-toggle="var-{{ $variable->id }}">Batal</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @foreach ($survey->variables as $variable)
                        <form id="ev{{ $variable->id }}" method="POST" action="/admin/surveys/{{ $survey->id }}/variables/{{ $variable->id }}">@csrf @method('PUT')</form>
                        <form id="dv{{ $variable->id }}" method="POST" action="/admin/surveys/{{ $survey->id }}/variables/{{ $variable->id }}" data-confirm="Hapus variabel ini? Isian mitra untuk variabel ini ikut terhapus.">@csrf @method('DELETE')</form>
                    @endforeach
                @endif
            </div>
            <div class="pnl-f">
                <a class="b b-ghost" href="/admin/surveys/{{ $survey->id }}/edit">Kembali ke informasi</a>
                <a class="b b-primary" href="/admin/surveys/{{ $survey->id }}/assignments">Lanjutkan ke Alokasi Mitra</a>
            </div>
        </section>

        <div class="ui">
            <form class="pnl" method="POST" action="/admin/surveys/{{ $survey->id }}/variables">
                @csrf
                <div class="pnl-h"><h2>Tambah variabel</h2></div>
                <div class="pnl-b ui" style="gap:12px">
                    <label class="fld"><span>Nama variabel</span><input name="name" required placeholder="VSEN26.K BLOK XX R2010.A.(i)"></label>
                    <label class="fld"><span>Tipe data</span>
                        <select name="data_type" required>
                            @foreach ($typeLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="fld"><span>Contoh pengisian <small>(opsional)</small></span><input name="example_format" placeholder="1000000, 10.56, atau sudah"><span class="hint">Tampil sebagai petunjuk di form mitra.</span></label>
                    <button type="submit" class="b b-primary" style="justify-self:start">Tambah variabel</button>
                </div>
            </form>

            <section class="pnl">
                <div class="pnl-h">
                    <div>
                        <h2>Template spreadsheet</h2>
                        <p>Baris 1 judul blok, baris 2 nama kolom, baris 3 contoh entrian, seperti format SUSENAS.</p>
                    </div>
                </div>
                <div class="pnl-b">
                    <a class="b b-soft" href="/admin/surveys/{{ $survey->id }}/variables/template">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11M7 10l5 5 5-5"/><path d="M5 20h14"/></svg>
                        Unduh template
                    </a>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
