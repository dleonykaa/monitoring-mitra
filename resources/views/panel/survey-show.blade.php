{{-- Detail survei bersama. Admin: $canManage = true, $base = '/admin'. Pegawai: lihat saja, $base = '/pegawai'. --}}
@extends('panel.layout', ['panelTitle' => $panelTitle, 'pageTitle' => 'Detail Survei'])

@section('menu')
    @include($menuView)
@endsection

@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $fmtPct = fn ($n) => number_format((float) $n, 1, ',', '.').'%';
    $tone = fn ($p) => $p >= 90 ? 'good' : ($p >= 70 ? 'ok' : ($p >= 50 ? 'warn' : 'bad'));
    $initials = fn ($name) => collect(preg_split('/\s+/', trim($name)))->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
    $shortDate = fn ($date) => $date->locale('id')->translatedFormat('d M Y');

    $isLocked = $survey->status === 'Selesai';
    $isSetupPending = $canManage && $survey->status === 'Draft' && ! $survey->isCapi();
    $canEditSetup = $canManage && $survey->status !== 'Selesai';
    $submittedEntries = (int) $survey->submitted_entries_count;
    $statusClass = ['Berjalan' => 'st-run', 'Draft' => 'st-draft', 'Selesai' => 'st-done'][$survey->status] ?? 'st-run';
    $statusStyle = ['st-run' => 'background:#dbeafe;color:#1e40af', 'st-draft' => 'background:#fef3c7;color:#92400e', 'st-done' => 'background:#dcfce7;color:#166534'][$statusClass];
    $daysLeft = (int) today()->diffInDays($survey->end_date, false);

    $progress = (int) $survey->assignments->sum('current_progress');
    $target = (int) $survey->total_target;
    $percentage = $target > 0 ? min(100, round($progress / $target * 100, 1)) : 0;
@endphp

@section('content')
<div class="ui">
    <section class="hero-nv" aria-label="Ringkasan survei">
        <div class="top">
            <div style="min-width:0;flex:1 1 420px">
                <a class="pg-back" href="{{ $base }}/surveys" style="color:var(--navy-muted)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    Daftar survei
                </a>
                <h1>{{ $survey->title }}</h1>
                <div class="meta">
                    <span class="chip">{{ $survey->typeLabel() }}</span>
                    <span class="bdg" style="{{ $statusStyle }};padding:2px 9px;font-size:11.5px;font-weight:700">{{ $survey->status }}</span>
                    <span>{{ $shortDate($survey->start_date) }} – {{ $shortDate($survey->end_date) }}</span>
                    @unless ($isLocked)
                        <span>·</span>
                        <span>{{ $daysLeft < 0 ? 'Lewat '.abs($daysLeft).' hari' : ($daysLeft === 0 ? 'Berakhir hari ini' : 'Sisa '.$daysLeft.' hari') }}</span>
                    @endunless
                </div>
                @if ($survey->description)
                    <p class="desc">{{ $survey->description }}</p>
                @endif
            </div>
            <div class="pg-actions" style="width:auto">
                @unless ($survey->isCapi())
                    <a class="b b-light" href="{{ $base }}/entri-papi?survey={{ $survey->id }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 5h18M3 12h18M3 19h18"/></svg>
                        Data entri <small style="opacity:.75">{{ $fmt($submittedEntries) }}</small>
                    </a>
                @endunless
                @if ($survey->status === 'Berjalan')
                    <a class="b {{ $survey->isCapi() ? 'b-light' : 'b-outline-light' }}" href="{{ $base }}/monitoring/progres?survey={{ $survey->id }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        Buka monitoring
                    </a>
                @endif
                @if ($canManage && $survey->status === 'Berjalan')
                    <form method="POST" action="/admin/surveys/{{ $survey->id }}/status" data-confirm="Tandai survei {{ $survey->title }} selesai? Survei akan terkunci dan tidak muncul lagi di Monitoring." style="display:inline">
                        @csrf
                        <input type="hidden" name="status" value="Selesai">
                        <button type="submit" class="b b-outline-light">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            Tandai selesai
                        </button>
                    </form>
                @endif
                @if ($canManage)
                    @if ($isLocked)
                        <span class="b b-outline-light" style="cursor:default">Terkunci</span>
                    @else
                        <a class="b b-outline-light" href="/admin/surveys/{{ $survey->id }}/edit">Ubah pengaturan</a>
                    @endif
                @endif
            </div>
        </div>

        @unless ($survey->isCapi())
            <div class="ledger">
                <div class="big">
                    <div class="lbl">Capaian survei</div>
                    <div class="val">{{ $fmtPct($percentage) }}</div>
                    <div class="sub"><b>{{ $fmt($progress) }}</b> dari {{ $fmt($target) }} ruta masuk</div>
                </div>
                <div>
                    <div class="track" role="img" aria-label="Masuk {{ $fmt($progress) }} dari {{ $fmt($target) }}"><i style="width:{{ $percentage }}%;background:var(--st-submit)"></i><i style="width:{{ max(0, 100 - $percentage) }}%;background:var(--st-open)"></i></div>
                    <div class="legend">
                        <div><div class="k"><i class="sw submit"></i>Masuk</div><div class="v">{{ $fmt($progress) }}</div></div>
                        <div><div class="k"><i class="sw open"></i>Sisa target</div><div class="v">{{ $fmt(max(0, $target - $progress)) }}</div></div>
                        <div><div class="k">Mitra</div><div class="v">{{ $survey->assignments->count() }}<small>{{ $survey->variables->count() }} variabel</small></div></div>
                        <div><div class="k">Sisa waktu</div>
                            <div class="v">
                                @if ($isLocked)
                                    –<small>survei selesai</small>
                                @else
                                    {{ max(0, $daysLeft) }}<small>hari · s.d. {{ $survey->end_date->locale('id')->translatedFormat('d M') }}</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endunless
    </section>

    @if ($isSetupPending)
        <div class="note note-amber" role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
            <span style="flex:1">Survei ini masih Draft dan belum terlihat oleh mitra. Susun form isian dan alokasi mitra, lalu jalankan survei dari langkah Alokasi mitra.</span>
            <a class="b b-primary b-sm" href="/admin/surveys/{{ $survey->id }}/{{ $survey->variables->isEmpty() ? 'variables' : 'assignments' }}">Lanjutkan setup</a>
        </div>
    @endif

    @if ($survey->isCapi())
        @include('panel.partials.survey-capi-summary', ['monitoringUrl' => $base.'/monitoring/progres?survey='.$survey->id])
    @else
        <div class="cols cols-side">
            <section class="pnl flush" aria-labelledby="mitraTitle">
                <div class="pnl-h">
                    <div>
                        <h2 id="mitraTitle">Progres mitra <span class="num-chip">{{ $survey->assignments->count() }}</span></h2>
                        <p>Diurutkan dari progres terbanyak.</p>
                    </div>
                    @if ($canEditSetup)
                        <a class="b b-soft b-sm" href="/admin/surveys/{{ $survey->id }}/assignments">Kelola alokasi</a>
                    @endif
                </div>
                <div class="pnl-b">
                    @if ($survey->assignments->isEmpty())
                        <div class="empty">
                            <b>Belum ada mitra dialokasikan</b>
                            <span>Tambahkan mitra dan target ruta di langkah Alokasi mitra.</span>
                        </div>
                    @else
                        <div class="tbl-wrap">
                            <table class="tbl stack" style="min-width:520px">
                                <thead><tr><th class="rank">#</th><th>Mitra</th><th>Progres</th><th class="num">Capaian</th></tr></thead>
                                <tbody>
                                    @foreach ($survey->assignments as $assignment)
                                        @php
                                            $mitraPct = $assignment->target > 0 ? min(100, round($assignment->current_progress / $assignment->target * 100, 1)) : 0;
                                        @endphp
                                        <tr>
                                            <td class="rank">{{ $loop->iteration }}</td>
                                            <td>
                                                <div class="who">
                                                    <span class="avatar" aria-hidden="true">{{ $initials($assignment->mitra->name) }}</span>
                                                    @if ($canManage)
                                                        <a href="/admin/mitra/{{ $assignment->mitra->id }}"><span><b>{{ $assignment->mitra->name }}</b></span></a>
                                                    @else
                                                        <span><b>{{ $assignment->mitra->name }}</b></span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="meter">
                                                    <span class="bar2"><i class="fill-good" style="width:{{ $mitraPct }}%"></i></span>
                                                    <small>{{ $fmt($assignment->current_progress) }} / {{ $fmt($assignment->target) }} ruta</small>
                                                </div>
                                            </td>
                                            <td class="num">
                                                @if ($assignment->current_progress >= $assignment->target)
                                                    <span class="bdg bdg-green">Tuntas</span>
                                                @else
                                                    <span class="pct {{ $tone($mitraPct) }}">{{ $fmtPct($mitraPct) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>

            <div class="ui">
                <section class="pnl" aria-labelledby="variableTitle">
                    <div class="pnl-h">
                        <h2 id="variableTitle">Form isian <span class="num-chip">{{ $survey->variables->count() }}</span></h2>
                        @if ($canEditSetup)<a class="b b-ghost b-sm" href="/admin/surveys/{{ $survey->id }}/variables">Atur</a>@endif
                    </div>
                    <div class="pnl-b">
                        @forelse ($survey->variables->take(8) as $variable)
                            <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;padding:7px 0;{{ $loop->last ? '' : 'border-bottom:1px solid var(--line)' }}">
                                <span style="font-size:13px;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $variable->name }}</span>
                                <span class="tag">{{ $variable->data_type === 'number' ? 'Angka' : 'Teks' }}</span>
                            </div>
                        @empty
                            <p style="margin:0;font-size:12.5px;color:var(--muted)">Mitra hanya mengisi identitas ruta dan foto bukti.</p>
                        @endforelse
                        @if ($survey->variables->count() > 8)
                            <p style="margin:8px 0 0;font-size:12px;color:var(--muted)">+{{ $survey->variables->count() - 8 }} variabel lainnya</p>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    @endif
</div>
@endsection
