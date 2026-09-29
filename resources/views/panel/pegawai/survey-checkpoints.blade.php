@extends('panel.layout', ['panelTitle' => 'Pegawai BPS', 'pageTitle' => 'Checkpoint Survei'])

@section('menu')
    @include('panel.pegawai.menu')
@endsection

@section('content')
    @php
        $isLocked = $survey->status === 'Selesai';
        $totalProgress = $survey->assignments->sum('current_progress');
        $overallPercentage = $survey->total_target > 0 ? round(($totalProgress / $survey->total_target) * 100, 1) : 0;
    @endphp

    <div class="card" style="display:flex;justify-content:space-between;gap:14px;align-items:center;flex-wrap:wrap;">
        <div>
            <div class="card-h" style="margin:0"><span class="dot"></span>{{ $survey->title }}</div>
            <div class="muted" style="max-width:720px">
                Tentukan target capaian per tanggal untuk memantau apakah progres mitra sesuai jadwal.
            </div>
        </div>
        <span class="pill pill-blue" style="font-size:14px">Capaian saat ini: {{ $overallPercentage }}%</span>
    </div>

    @unless ($isLocked)
        <div class="card">
            <div class="card-h"><span class="dot"></span>Tambah Checkpoint</div>
            <form method="POST" action="/pegawai/surveys/{{ $survey->id }}/checkpoints" style="display:flex;flex-wrap:wrap;gap:10px;align-items:end;">
                @csrf
                <label style="flex:1 1 200px;min-width:0;">
                    <span class="muted" style="display:block;margin-bottom:6px">Tanggal Checkpoint</span>
                    <input type="date" name="checkpoint_date" required min="{{ $survey->start_date->toDateString() }}" max="{{ $survey->end_date->toDateString() }}" style="width:100%">
                </label>
                <label style="flex:1 1 160px;min-width:0;">
                    <span class="muted" style="display:block;margin-bottom:6px">Target Capaian (%)</span>
                    <input type="number" name="target_percentage" min="1" max="100" placeholder="Contoh: 20" required style="width:100%">
                </label>
                <button type="submit" style="flex:0 0 auto;">Tambah Checkpoint</button>
            </form>
        </div>
    @endunless

    <div class="card" style="padding:0;overflow:hidden">
        <div class="card-h" style="padding:15px 16px 0;margin-bottom:8px"><span class="dot"></span>Daftar Checkpoint ({{ $survey->checkpoints->count() }})</div>
        <div style="overflow-x:auto">
            <table class="rich-table" style="min-width:560px">
                <thead>
                    <tr>
                        <th>Checkpoint</th>
                        <th>Tanggal</th>
                        <th>Target Capaian</th>
                        <th>Status</th>
                        @unless ($isLocked)
                            <th style="text-align:center">Aksi</th>
                        @endunless
                    </tr>
                </thead>
                <tbody>
                @forelse ($survey->checkpoints as $index => $checkpoint)
                    @php
                        $isPast = $checkpoint->checkpoint_date->isPast();
                        $onTrack = $overallPercentage >= $checkpoint->target_percentage;
                        $statusText = ! $isPast ? 'Akan Datang' : ($onTrack ? 'Tercapai' : 'Belum Tercapai');
                        $statusClass = ! $isPast ? 'pill-blue' : ($onTrack ? 'pill-green' : 'pill-rose');
                    @endphp
                    <tr>
                        <td style="white-space:nowrap;font-weight:700">Checkpoint {{ $index + 1 }}</td>
                        <td style="white-space:nowrap">
                            @unless ($isLocked)
                                <input form="ec{{ $checkpoint->id }}" type="date" name="checkpoint_date" value="{{ $checkpoint->checkpoint_date->toDateString() }}" min="{{ $survey->start_date->toDateString() }}" max="{{ $survey->end_date->toDateString() }}" required style="width:100%">
                            @else
                                {{ $checkpoint->checkpoint_date->format('d/m/Y') }}
                            @endunless
                        </td>
                        <td>
                            @unless ($isLocked)
                                <input form="ec{{ $checkpoint->id }}" type="number" name="target_percentage" value="{{ $checkpoint->target_percentage }}" min="1" max="100" required style="width:90px">%
                            @else
                                {{ $checkpoint->target_percentage }}%
                            @endunless
                        </td>
                        <td style="white-space:nowrap"><span class="pill {{ $statusClass }}">{{ $statusText }}</span></td>
                        @unless ($isLocked)
                            <td style="white-space:nowrap;text-align:center">
                                <button form="ec{{ $checkpoint->id }}" type="submit" style="padding:7px 12px">Simpan</button>
                                <button form="dc{{ $checkpoint->id }}" type="submit" class="btn-danger" style="padding:7px 12px">Hapus</button>
                            </td>
                        @endunless
                    </tr>
                @empty
                    <tr><td colspan="{{ $isLocked ? 4 : 5 }}" class="muted" style="text-align:center;padding:24px">Belum ada checkpoint.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @unless ($isLocked)
            @foreach ($survey->checkpoints as $checkpoint)
                <form id="ec{{ $checkpoint->id }}" method="POST" action="/pegawai/surveys/{{ $survey->id }}/checkpoints/{{ $checkpoint->id }}">@csrf @method('PUT')</form>
                <form id="dc{{ $checkpoint->id }}" method="POST" action="/pegawai/surveys/{{ $survey->id }}/checkpoints/{{ $checkpoint->id }}" onsubmit="return confirm('Hapus checkpoint ini?')">@csrf @method('DELETE')</form>
            @endforeach
        @endunless
    </div>

    <div class="card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <span class="muted">Checkpoint bersifat opsional, hanya untuk membantu memantau jadwal capaian mitra.</span>
        @if ($survey->status === 'Draft')
            <form method="POST" action="/pegawai/surveys/{{ $survey->id }}/finish-setup" style="margin:0">
                @csrf
                <button type="submit">🚀 Jalankan Survei</button>
            </form>
        @else
            <a class="btn" href="/pegawai/surveys/{{ $survey->id }}" onclick="if (history.length > 1) { history.back(); return false; }">Kembali ke Detail Survei</a>
        @endif
        <a class="btn btn-grey" href="/pegawai/surveys/{{ $survey->id }}/assignments" onclick="if (history.length > 1) { history.back(); return false; }">Kembali ke Alokasi Mitra</a>
    </div>
@endsection
