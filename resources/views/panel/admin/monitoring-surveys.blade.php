@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Monitoring Survei'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@section('content')
    <div class="card" style="padding:0;overflow:hidden">
        <div class="card-h" style="padding:15px 16px 0;margin-bottom:8px"><span class="dot"></span>Seluruh Survei</div>
        <div style="overflow-x:auto">
            <table class="rich-table" style="min-width:560px">
                <tr><th>Judul</th><th>Status</th><th>Entri</th><th>Mitra</th><th style="text-align:center">Aksi</th></tr>
                @forelse ($surveys as $survey)
                    <tr>
                        <td>{{ $survey->title }}</td>
                        <td style="white-space:nowrap">
                            <span class="pill {{ $survey->status === 'Berjalan' ? 'pill-amber' : ($survey->status === 'Selesai' ? 'pill-green' : 'pill-blue') }}">{{ $survey->status }}</span>
                        </td>
                        <td>{{ $survey->entries_count }}</td>
                        <td>{{ $survey->assignments_count }}</td>
                        <td style="text-align:center;white-space:nowrap"><a class="btn" href="/admin/monitoring/surveys/{{ $survey->id }}">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted" style="text-align:center;padding:24px">Belum ada survei.</td></tr>
                @endforelse
            </table>
        </div>
        <div style="padding:12px 14px">{{ $surveys->links() }}</div>
    </div>
@endsection
