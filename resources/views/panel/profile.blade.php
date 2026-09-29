@extends('panel.layout', ['panelTitle' => $panelTitle, 'pageTitle' => 'Profil'])
@section('menu')
{!! $menuHtml !!}
@endsection
@section('content')
<div class="card" style="max-width:520px;">
<form method="POST" action="/profile/password" style="display:grid;gap:8px;">@csrf
<input type="password" name="current_password" placeholder="Password saat ini" required>
<input type="password" name="password" placeholder="Password baru" required>
<input type="password" name="password_confirmation" placeholder="Konfirmasi password baru" required>
<button type="submit">Ubah Password</button>
</form>
</div>
@endsection
