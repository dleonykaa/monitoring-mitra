@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Log Aktivitas'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@php
    $hasFilter = collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty();
    $actionTone = fn ($action) => match (strtok((string) $action, '.')) {
        'admin' => 'bdg-blue',
        'mitra' => 'bdg-green',
        'pegawai' => 'bdg-amber',
        default => 'bdg-gray',
    };
    $initials = fn ($name) => collect(preg_split('/\s+/', trim($name)))->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
@endphp

@section('content')
<div class="ui">
    <section class="pnl flush" aria-labelledby="logsTitle">
        <div class="pnl-h">
            <div>
                <h2 id="logsTitle">Log aktivitas <span class="num-chip">{{ number_format($logs->total(), 0, ',', '.') }}</span></h2>
                <p>{{ $hasFilter ? 'Menampilkan log sesuai filter.' : 'Semua perubahan data oleh admin, pegawai, dan mitra, terbaru di atas.' }}</p>
            </div>
        </div>
        <div class="pnl-b" style="padding:0 18px 14px;border-bottom:1px solid var(--line)">
            <form method="GET" action="/admin/logs" class="fld-row">
                <label class="fld" style="flex:1 1 170px"><span class="hint">Pengguna</span>
                    <select name="user_id">
                        <option value="">Semua pengguna</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="fld" style="flex:1 1 150px"><span class="hint">Aksi</span><input name="action" value="{{ $filters['action'] ?? '' }}" placeholder="mis. survey, fasih"></label>
                <label class="fld" style="flex:0 1 160px"><span class="hint">Tanggal</span><input type="date" name="date" value="{{ $filters['date'] ?? '' }}"></label>
                <button type="submit" class="b b-primary">Terapkan</button>
                @if ($hasFilter)<a class="b b-ghost" href="/admin/logs">Reset</a>@endif
            </form>
        </div>
        <div class="pnl-b">
            @if ($logs->isEmpty())
                <div class="empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l4 2"/></svg>
                    <b>Log tidak ditemukan</b>
                    <span>{{ $hasFilter ? 'Ubah atau reset filter untuk melihat log lain.' : 'Aktivitas akan tercatat saat data mulai diubah.' }}</span>
                </div>
            @else
                <div class="tbl-wrap">
                    <table class="tbl stack" style="min-width:760px">
                        <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Deskripsi</th></tr></thead>
                        <tbody>
                            @foreach ($logs as $log)
                                <tr>
                                    <td style="white-space:nowrap">
                                        <b style="font-weight:600;font-size:13px">{{ $log->created_at->locale('id')->translatedFormat('d M Y') }}</b>
                                        <div class="muted-cell" style="font-size:12px;color:var(--muted)">{{ $log->created_at->format('H:i') }} · {{ $log->created_at->locale('id')->diffForHumans() }}</div>
                                    </td>
                                    <td>
                                        <div class="who">
                                            <span class="avatar" aria-hidden="true" style="width:30px;height:30px;font-size:11.5px">{{ $log->user ? $initials($log->user->name) : 'S' }}</span>
                                            <span><b style="font-size:13px">{{ $log->user?->name ?? 'Sistem' }}</b></span>
                                        </div>
                                    </td>
                                    <td><span class="bdg {{ $actionTone($log->action) }}" style="font-family:ui-monospace,Consolas,monospace;font-size:11.5px">{{ $log->action }}</span></td>
                                    <td style="line-height:1.45">{{ $log->description }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @if ($logs->hasPages())
            <div class="pnl-f" style="display:block">{{ $logs->links() }}</div>
        @endif
    </section>
</div>
@endsection
