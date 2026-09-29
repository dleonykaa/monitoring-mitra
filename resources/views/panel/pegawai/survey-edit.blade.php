@extends('panel.layout', ['panelTitle' => 'Pegawai BPS', 'pageTitle' => 'Edit Survei'])

@section('menu')
    @include('panel.pegawai.menu')
@endsection

@section('content')
    @php
        $progress = $survey->assignments->sum('current_progress');
        $percentage = $survey->total_target > 0 ? min(100, round(($progress / $survey->total_target) * 100, 1)) : 0;
        $isLocked = $survey->status === 'Selesai';
    @endphp

    <div class="card edit-title-card">
        <div>
            <div class="card-h" style="margin:0"><span class="dot"></span>Edit Survei</div>
            <div class="muted">Perubahan survei, variabel, alokasi, hapus, dan penyelesaian paksa dikelola dari halaman ini.</div>
        </div>
        <a class="btn btn-grey" href="/pegawai/surveys/{{ $survey->id }}">Lihat Detail</a>
    </div>

    <div class="grid g4">
        <div class="stat st-blue"><div class="s-label">Status</div><div class="s-val" style="font-size:22px;">{{ $survey->status }}</div></div>
        <div class="stat st-sky"><div class="s-label">Target</div><div class="s-val">{{ $survey->total_target }}</div></div>
        <div class="stat st-green"><div class="s-label">Progress</div><div class="s-val">{{ $progress }}</div></div>
        <div class="stat st-indigo"><div class="s-label">Capaian</div><div class="s-val">{{ $percentage }}%</div></div>
    </div>

    <div class="card">
        <div class="card-h"><span class="dot"></span>{{ $isLocked ? 'Ringkasan Survei' : 'Edit Survei' }}</div>
        @if ($isLocked)
            <div class="locked-summary">
                <div><span>Judul</span><strong>{{ $survey->title }}</strong></div>
                <div><span>Total Target</span><strong>{{ $survey->total_target }}</strong></div>
                <div><span>Tanggal Mulai</span><strong>{{ $survey->start_date->format('d/m/Y') }}</strong></div>
                <div><span>Tanggal Berakhir</span><strong>{{ $survey->end_date->format('d/m/Y') }}</strong></div>
                <div style="grid-column:1/-1"><span>Deskripsi</span><strong>{{ $survey->description ?: '-' }}</strong></div>
            </div>
            <div class="locked-note">Survei sudah selesai, data survei, variabel, dan alokasi tidak dapat diedit.</div>
        @else
            <form method="POST" action="/pegawai/surveys/{{ $survey->id }}" class="survey-edit-form">
                @csrf
                @method('PUT')
                <label>Judul<input name="title" value="{{ $survey->title }}" required style="width:100%;margin-top:4px;"></label>
                <label>Total Target (otomatis)<input value="{{ $survey->total_target }} - akumulasi target mitra" disabled style="width:100%;margin-top:4px;background:#f1f6fd;"></label>
                <label>Tanggal Mulai<input type="date" name="start_date" value="{{ $survey->start_date->toDateString() }}" required style="width:100%;margin-top:4px;"></label>
                <label>Tanggal Berakhir<input type="date" name="end_date" value="{{ $survey->end_date->toDateString() }}" required style="width:100%;margin-top:4px;"></label>
                <label style="grid-column:1/-1;">Deskripsi<textarea name="description" rows="3" style="width:100%;margin-top:4px;">{{ $survey->description }}</textarea></label>
                <button type="submit" style="width:max-content;">Simpan Perubahan</button>
            </form>
        @endif
    </div>

    <div class="grid g2">
        <div class="card">
            <div class="card-h"><span class="dot"></span>Variabel Validasi</div>
            <table>
                <tr><th>Nama</th><th>Tipe</th><th>Contoh</th></tr>
                @if ($survey->variables->isEmpty())
                    <tr><td colspan="3">Tidak ada variabel validasi.</td></tr>
                @endif
                @foreach ($survey->variables as $variable)
                    <tr><td>{{ $variable->name }}</td><td>{{ $variable->data_type }}</td><td>{{ $variable->example_format ?? '-' }}</td></tr>
                @endforeach
            </table>
            @if (! $isLocked)
                <a class="btn" href="/pegawai/surveys/{{ $survey->id }}/variables" style="padding:7px 10px;margin-top:10px;">Kelola Variabel</a>
            @else
                <span class="pill pill-green" style="margin-top:10px">Variabel terkunci</span>
            @endif
        </div>

        <div class="card">
            <div class="card-h"><span class="dot"></span>Progress Per Mitra</div>
            <div class="survey-mitra-list">
                @if ($survey->assignments->isEmpty())
                    <div class="muted" style="padding:14px;text-align:center">Belum ada alokasi.</div>
                @endif
                @foreach ($survey->assignments as $assignment)
                    @php
                        $mitraPct = $assignment->target > 0 ? min(100, round(($assignment->current_progress / $assignment->target) * 100, 1)) : 0;
                        $done = $assignment->current_progress >= $assignment->target;
                    @endphp
                    <div class="survey-mitra-row">
                        <div class="survey-mitra-rank">#{{ $loop->iteration }}</div>
                        <div class="survey-mitra-main">
                            <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap">
                                <div>
                                    <strong>{{ $assignment->mitra->name }}</strong>
                                    <span>{{ $assignment->current_progress }} dari {{ $assignment->target }} target</span>
                                </div>
                                <span class="pill {{ $done ? 'pill-green' : 'pill-blue' }}">{{ $done ? 'Target tercapai' : $mitraPct.'%' }}</span>
                            </div>
                            <div class="bar"><i style="width:{{ $mitraPct }}%"></i></div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if (! $isLocked)
                <a class="btn" href="/pegawai/surveys/{{ $survey->id }}/assignments" style="padding:7px 10px;margin-top:10px;">Kelola Alokasi</a>
            @else
                <span class="pill pill-green" style="margin-top:10px">Alokasi terkunci</span>
            @endif
        </div>

        <div class="card">
            <div class="card-h"><span class="dot"></span>Checkpoint</div>
            <table>
                <tr><th>Tanggal</th><th>Target</th><th>Status</th></tr>
                @forelse ($survey->checkpoints as $checkpoint)
                    @php
                        $isPast = $checkpoint->checkpoint_date->isPast();
                        $onTrack = $percentage >= $checkpoint->target_percentage;
                        $checkpointStatus = ! $isPast ? 'Akan Datang' : ($onTrack ? 'Tercapai' : 'Belum Tercapai');
                        $checkpointClass = ! $isPast ? 'pill-blue' : ($onTrack ? 'pill-green' : 'pill-rose');
                    @endphp
                    <tr>
                        <td>{{ $checkpoint->checkpoint_date->format('d/m/Y') }}</td>
                        <td>{{ $checkpoint->target_percentage }}%</td>
                        <td><span class="pill {{ $checkpointClass }}">{{ $checkpointStatus }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="3">Belum ada checkpoint.</td></tr>
                @endforelse
            </table>
            @if (! $isLocked)
                <a class="btn" href="/pegawai/surveys/{{ $survey->id }}/checkpoints" style="padding:7px 10px;margin-top:10px;">Kelola Checkpoint</a>
            @else
                <span class="pill pill-green" style="margin-top:10px">Checkpoint terkunci</span>
            @endif
        </div>
    </div>

    @if (! $isLocked)
        <div class="card danger-zone">
            <div>
                <div class="card-h" style="margin-bottom:4px"><span class="dot"></span>Aksi Lanjutan</div>
                <div class="muted">Gunakan hanya jika survei perlu dikunci paksa atau dihapus dari sistem.</div>
            </div>
            <div class="danger-actions">
                <form method="POST" action="/pegawai/surveys/{{ $survey->id }}/status" onsubmit="return confirm('Tandai survei ini sebagai selesai? Survei selesai tidak dapat diedit lagi.')">
                    @csrf
                    <input type="hidden" name="status" value="Selesai">
                    <button type="submit" class="btn-warning">Tandai Sudah Selesai</button>
                </form>
                <form method="POST" action="/pegawai/surveys/{{ $survey->id }}" onsubmit="return confirm('Hapus survei ini? Data assignment dan entri terkait ikut terhapus.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">Hapus Survei</button>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('head')
<style>
    .edit-title-card {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }

    .survey-edit-form {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .danger-zone {
        border-color: #fecaca;
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: center;
        flex-wrap: wrap;
    }

    .danger-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }

    .btn-warning {
        background: #f59e0b;
        color: #fff;
    }

    .locked-summary {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .locked-summary div {
        border: 1px solid var(--line);
        border-radius: 12px;
        background: #f8fbff;
        padding: 11px 12px;
    }

    .locked-summary span {
        color: var(--muted);
        display: block;
        font-size: 12px;
        margin-bottom: 4px;
    }

    .locked-summary strong {
        color: var(--brand-dark);
        font-size: 14px;
    }

    .locked-note {
        margin-top: 10px;
        border: 1px solid #bbf7d0;
        border-radius: 12px;
        background: #ecfdf5;
        color: #047857;
        font-weight: 700;
        padding: 10px 12px;
    }

    .survey-mitra-list {
        display: grid;
        gap: 8px;
    }

    .survey-mitra-row {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 10px;
        align-items: center;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: var(--soft2);
        padding: 10px;
    }

    .survey-mitra-rank {
        color: var(--brand);
        font-size: 13px;
        font-weight: 900;
        text-align: center;
    }

    .survey-mitra-main {
        display: grid;
        gap: 8px;
    }

    .survey-mitra-main strong {
        color: var(--brand-dark);
        display: block;
        font-size: 13.5px;
    }

    .survey-mitra-main span {
        color: var(--muted);
        font-size: 11.5px;
    }

    @media (max-width: 760px) {
        .survey-edit-form {
            grid-template-columns: 1fr;
        }

        .survey-edit-form label {
            grid-column: auto !important;
        }

        .danger-actions,
        .danger-actions form,
        .danger-actions button {
            width: 100%;
        }

        .locked-summary {
            grid-template-columns: 1fr;
        }

        .survey-mitra-row {
            grid-template-columns: 1fr;
        }

        .survey-mitra-rank {
            text-align: left;
        }
    }
</style>
@endpush
