@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Log Aktivitas'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@section('content')
    <div class="card">
        <form method="GET" action="/admin/logs" style="display:flex;flex-wrap:wrap;gap:8px;">
            <select name="user_id" style="flex:1 1 160px;min-width:0;">
                <option value="">Semua user</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
            <input name="action" value="{{ $filters['action'] ?? '' }}" placeholder="Cari aksi" style="flex:1 1 160px;min-width:0;">
            <input type="date" name="date" value="{{ $filters['date'] ?? '' }}" style="flex:1 1 160px;min-width:0;">
            <button type="submit" style="flex:0 0 auto;">Filter</button>
        </form>
    </div>

    <div class="card" style="padding:0;overflow:hidden">
        <div style="overflow-x:auto">
            <table class="rich-table" style="min-width:640px">
                <tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Deskripsi</th></tr>
                @forelse ($logs as $log)
                    <tr>
                        <td style="white-space:nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td style="white-space:nowrap">{{ $log->user?->name ?? '-' }}</td>
                        <td style="white-space:nowrap">{{ $log->action }}</td>
                        <td>{{ $log->description }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted" style="text-align:center;padding:24px">Log tidak ditemukan.</td></tr>
                @endforelse
            </table>
        </div>
        <div style="padding:12px 14px">{{ $logs->links() }}</div>
    </div>
@endsection
