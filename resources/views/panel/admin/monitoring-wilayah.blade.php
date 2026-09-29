@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Peta Wilayah'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@section('content')
    @php($maxEntries = max(1, $districtProgress->max('entries_count') ?? 1))
    <div class="card">
        <div class="card-h"><span class="dot"></span>Sebaran Entri per Kecamatan</div>
        <table class="rich-table">
            <tr><th>Kecamatan</th><th>Total Entri</th><th>Proporsi</th></tr>
            @forelse ($districtProgress as $d)
                <tr>
                    <td>{{ $d->name }}</td>
                    <td>{{ $d->entries_count }}</td>
                    <td><div class="bar"><i style="width:{{ round(($d->entries_count / $maxEntries) * 100) }}%"></i></div></td>
                </tr>
            @empty
                <tr><td colspan="3">Belum ada data wilayah.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
