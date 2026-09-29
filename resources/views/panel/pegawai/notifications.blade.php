@extends('panel.layout', ['panelTitle' => 'Pegawai BPS', 'pageTitle' => 'Notifikasi'])
@section('menu')
    @include('panel.pegawai.menu')
@endsection
@section('content')
<div class="card"><table><tr><th>Waktu</th><th>Judul</th><th>Pesan</th></tr>@foreach($notifications as $notification)<tr><td>{{ $notification->created_at }}</td><td>{{ $notification->data['title'] ?? '-' }}</td><td>{{ $notification->data['message'] ?? '-' }}</td></tr>@endforeach</table>{{ $notifications->links() }}</div>
@endsection
