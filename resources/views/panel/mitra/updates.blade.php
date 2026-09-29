@extends('panel.layout', ['panelTitle' => 'Mitra BPS', 'pageTitle' => 'Riwayat Update'])

@section('menu')
    @include('panel.mitra.menu')
@endsection

@section('content')
    <div class="card">
        <div class="card-h"><span class="dot"></span> Riwayat Update Progress</div>
        <div class="muted">Menampilkan entri yang sudah disubmit sebagai progress resmi.</div>
    </div>

    <div class="card" style="padding:0;overflow:hidden">
        @include('panel.mitra.entries-table', ['entries' => $entries, 'compact' => true])
    </div>

    <div class="card">{{ $entries->links() }}</div>
@endsection

@include('panel.mitra.styles')
