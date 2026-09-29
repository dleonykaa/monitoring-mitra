@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Profil Mitra'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@section('content')
    <div class="card">
        <div class="card-h"><span class="dot"></span>{{ $mitra->name }}</div>
        <div class="muted">{{ $mitra->email }}</div>
    </div>

    <div class="card" style="padding:0;overflow:hidden">
        <div class="card-h" style="padding:15px 16px 0;margin-bottom:8px"><span class="dot"></span>Riwayat Penugasan</div>
        <div style="overflow-x:auto">
            <table class="rich-table" style="min-width:480px">
                <tr><th>Survei</th><th>Target</th><th>Progress</th><th>Ketepatan</th></tr>
                @forelse ($mitra->assignments as $a)
                    <tr>
                        <td>{{ $a->survey->title }}</td>
                        <td>{{ $a->target }}</td>
                        <td>{{ $a->current_progress }}</td>
                        <td style="white-space:nowrap">
                            @php($late = $a->survey->end_date < now() && $a->current_progress < $a->target)
                            <span class="pill {{ $late ? 'pill-rose' : 'pill-green' }}">{{ $late ? 'Terlambat' : 'Tepat Waktu' }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted" style="text-align:center;padding:24px">Belum ada penugasan.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
