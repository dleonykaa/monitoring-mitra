{{-- Pegawai penanggung jawab survei (PML). Butuh $survey (dengan pegawai) dan $canEditSetup: admin dapat menugaskan dan mencabut pegawai. --}}
@php
    $availablePegawai = $canEditSetup
        ? \App\Models\User::query()->role('pegawai_bps')->where('is_active', true)
            ->whereNotIn('id', $survey->pegawai->pluck('id'))->orderBy('name')->get(['id', 'name'])
        : collect();
@endphp

<section class="pnl" aria-labelledby="pegawaiTitle">
    <div class="pnl-h">
        <div>
            <h2 id="pegawaiTitle">Pegawai penanggung jawab <span class="num-chip">{{ $survey->pegawai->count() }}</span></h2>
            <p>{{ $survey->isCapi() ? 'Pegawai BPS yang memantau survei ini.' : 'Menerima notifikasi setiap mitra survei ini menyelesaikan entri.' }}</p>
        </div>
    </div>
    <div class="pnl-b">
        @forelse ($survey->pegawai as $pegawai)
            <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;padding:7px 0;{{ $loop->last ? '' : 'border-bottom:1px solid var(--line)' }}">
                <span style="min-width:0">
                    <b style="display:block;font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        {{ $pegawai->name }}
                        @if ($pegawai->is(auth()->user()))<span class="bdg bdg-blue" style="margin-left:4px">Anda</span>@endif
                    </b>
                    <small style="color:var(--muted);font-size:12px">{{ $pegawai->trashed() ? 'Akun dihapus' : ($pegawai->is_active ? $pegawai->email : 'Akun nonaktif') }}</small>
                </span>
                @if ($canEditSetup)
                    <form method="POST" action="/admin/surveys/{{ $survey->id }}/pegawai/{{ $pegawai->id }}" data-confirm="Cabut {{ $pegawai->name }} dari penanggung jawab survei {{ $survey->title }}?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="b b-ghost b-sm" aria-label="Cabut {{ $pegawai->name }}">Cabut</button>
                    </form>
                @endif
            </div>
        @empty
            <p style="margin:0;font-size:12.5px;color:var(--muted)">
                Belum ada pegawai yang ditugaskan.{{ $survey->isCapi() ? '' : ' Notifikasi entri mitra dikirim ke seluruh pegawai.' }}
            </p>
        @endforelse

        @if ($canEditSetup)
            @if ($availablePegawai->isNotEmpty())
                <form method="POST" action="/admin/surveys/{{ $survey->id }}/pegawai" id="pegawaiForm" style="display:flex;gap:8px;align-items:end;margin-top:12px">
                    @csrf
                    <label class="fld" style="flex:1">
                        <span class="sr-only">Pegawai yang ditugaskan</span>
                        <select name="user_id" required>
                            <option value="">Pilih pegawai</option>
                            @foreach ($availablePegawai as $pegawai)
                                <option value="{{ $pegawai->id }}" @selected((int) old('user_id') === $pegawai->id)>{{ $pegawai->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="b b-primary b-sm">Tugaskan</button>
                </form>
            @elseif ($survey->pegawai->isEmpty())
                <p style="margin:10px 0 0;font-size:12px;color:var(--muted)">Belum ada akun pegawai BPS aktif. Tambahkan di Manajemen Pengguna.</p>
            @endif
            @error('user_id')
                <p style="margin:8px 0 0;font-size:12px;color:var(--t-rose-fg)">{{ $message }}</p>
            @enderror
        @endif
    </div>
</section>
