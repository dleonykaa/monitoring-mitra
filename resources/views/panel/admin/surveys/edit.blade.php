@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Pengaturan Survei'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@php
    $isLocked = $survey->status === 'Selesai';
    $shortDate = fn ($date) => $date->locale('id')->translatedFormat('d M Y');
@endphp

@section('content')
<div class="ui">
    @include('panel.admin.surveys.setup-header', ['survey' => $survey, 'step' => 'info'])

    <div class="cols cols-side">
        <section class="pnl" aria-labelledby="infoTitle">
            <div class="pnl-h">
                <div>
                    <h2 id="infoTitle">Informasi survei</h2>
                    <p>{{ $isLocked ? 'Survei sudah selesai sehingga pengaturannya terkunci.' : 'Perubahan judul dan periode langsung terlihat oleh pegawai dan mitra.' }}</p>
                </div>
            </div>
            <div class="pnl-b">
                @if ($isLocked)
                    <dl class="fld-grid" style="margin:0">
                        <div class="fld full"><dt class="hint">Judul</dt><dd style="margin:0;font-weight:600">{{ $survey->title }}</dd></div>
                        <div class="fld"><dt class="hint">Tanggal mulai</dt><dd style="margin:0;font-weight:600">{{ $shortDate($survey->start_date) }}</dd></div>
                        <div class="fld"><dt class="hint">Tanggal berakhir</dt><dd style="margin:0;font-weight:600">{{ $shortDate($survey->end_date) }}</dd></div>
                        <div class="fld full"><dt class="hint">Deskripsi</dt><dd style="margin:0">{{ $survey->description ?: '–' }}</dd></div>
                    </dl>
                @else
                    <form method="POST" action="/admin/surveys/{{ $survey->id }}" class="fld-grid">
                        @csrf
                        @method('PUT')
                        <label class="fld full"><span>Judul survei</span><input name="title" value="{{ old('title', $survey->title) }}" required></label>
                        <label class="fld"><span>Tanggal mulai</span><input type="date" name="start_date" value="{{ old('start_date', $survey->start_date->toDateString()) }}" required></label>
                        <label class="fld"><span>Tanggal berakhir</span><input type="date" name="end_date" value="{{ old('end_date', $survey->end_date->toDateString()) }}" required></label>
                        <label class="fld full"><span>Deskripsi <small>(opsional)</small></span><textarea name="description" rows="3">{{ old('description', $survey->description) }}</textarea></label>
                        <label class="fld full"><span>Total target</span>
                            <input value="{{ number_format((int) $survey->total_target, 0, ',', '.') }}" disabled>
                            <span class="hint">Dihitung otomatis dari {{ $survey->isCapi() ? 'total beban pada data FASIH terbaru' : 'jumlah target seluruh mitra di langkah Alokasi' }}.</span>
                        </label>
                        <div class="form-foot full">
                            <p>{{ $survey->isCapi() ? 'Data FASIH baru diimpor dari halaman Monitoring Progres.' : 'Lanjutkan ke Form isian setelah menyimpan.' }}</p>
                            <div class="pg-actions" style="width:auto">
                                @unless ($survey->isCapi())
                                    <a class="b b-soft" href="/admin/surveys/{{ $survey->id }}/variables">Ke form isian</a>
                                @endunless
                                <button type="submit" class="b b-primary">Simpan perubahan</button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </section>

        <div class="ui">
            @if ($survey->isCapi())
                <section class="pnl">
                    <div class="pnl-h"><h2>Data FASIH</h2></div>
                    <div class="pnl-b" style="display:grid;gap:10px">
                        <p style="margin:0;font-size:12.5px;color:var(--muted);line-height:1.5">{{ $survey->fasihImports->count() }} kali impor. Terakhir {{ $survey->fasihImports->first()?->created_at->locale('id')->diffForHumans() ?? 'belum ada' }}.</p>
                        <a class="b b-soft" href="/admin/monitoring/progres?survey={{ $survey->id }}" style="justify-self:start">Impor data terbaru</a>
                    </div>
                </section>
            @endif

            @if ($isLocked)
                <div class="note note-green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                    <span>Survei selesai. Form isian dan alokasi tidak dapat diubah lagi.</span>
                </div>
            @else
                <section class="pnl pnl-danger" aria-labelledby="dangerTitle">
                    <div class="pnl-h">
                        <div>
                            <h2 id="dangerTitle">Tindakan lanjutan</h2>
                            <p>Keduanya tidak bisa dibatalkan.</p>
                        </div>
                    </div>
                    <div class="pnl-b" style="display:grid;gap:14px">
                        <form method="POST" action="/admin/surveys/{{ $survey->id }}/status" data-confirm="Tandai survei ini sebagai selesai? Survei selesai tidak dapat diubah lagi." style="display:grid;gap:6px">
                            @csrf
                            <input type="hidden" name="status" value="Selesai">
                            <button type="submit" class="b b-soft" style="justify-self:start">Tandai selesai</button>
                            <span class="hint" style="font-size:12px;color:var(--muted)">Mengunci survei walaupun target belum tercapai.</span>
                        </form>
                        <form method="POST" action="/admin/surveys/{{ $survey->id }}" data-confirm="Hapus survei ini? Alokasi dan entri mitra ikut terhapus." style="display:grid;gap:6px;padding-top:14px;border-top:1px solid var(--line)">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="b b-danger" style="justify-self:start">Hapus survei</button>
                            <span class="hint" style="font-size:12px;color:var(--muted)">Menghapus survei beserta alokasi dan entri mitra.</span>
                        </form>
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>
@endsection
