{{-- Ringkasan survei CAPI: dipakai halaman detail survei admin dan pegawai. Butuh $survey (dengan fasihImports) dan $monitoringUrl. --}}
@php
    $latestImport = $survey->fasihImports->first();
    $latestTotals = $latestImport
        ? \App\Models\FasihProgressRow::query()->where('fasih_import_id', $latestImport->id)
            ->selectRaw('sum(total_region) as beban, sum(submit_count) as submitted, count(distinct email) as mitra')
            ->first()
        : null;
    $beban = (int) ($latestTotals->beban ?? 0);
    $submitted = (int) ($latestTotals->submitted ?? 0);
    $percentage = $beban > 0 ? round($submitted / $beban * 100, 1) : 0;
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
@endphp

<div class="stats">
    <div><div class="k">Capaian submit</div><div class="v">{{ number_format($percentage, 1, ',', '.') }}%</div></div>
    <div><div class="k">Total beban</div><div class="v">{{ $fmt($beban) }}<small>dokumen</small></div></div>
    <div><div class="k">Submit</div><div class="v">{{ $fmt($submitted) }}</div></div>
    <div><div class="k">Sisa</div><div class="v">{{ $fmt(max(0, $beban - $submitted)) }}<small>draft & open</small></div></div>
    <div><div class="k">Pencacah</div><div class="v">{{ $fmt($latestTotals->mitra ?? 0) }}</div></div>
</div>

<section class="pnl flush" aria-labelledby="fasihHistoryTitle">
    <div class="pnl-h">
        <div>
            <h2 id="fasihHistoryTitle">Riwayat data FASIH <span class="num-chip">{{ $survey->fasihImports->count() }}</span></h2>
            <p>Angka di atas dihitung dari impor terbaru. Rincian per wilayah dan pencacah ada di Monitoring Progres.</p>
        </div>
        <a class="b b-soft b-sm" href="{{ $monitoringUrl }}">Buka Monitoring Progres</a>
    </div>
    <div class="pnl-b">
        @if ($survey->fasihImports->isEmpty())
            <div class="empty">
                <b>Belum ada data FASIH</b>
                <span>Impor file CSV progres dari halaman Monitoring Progres.</span>
            </div>
        @else
            <div class="tbl-wrap">
                <table class="tbl stack" style="min-width:560px">
                    <thead><tr><th>Diimpor</th><th>File</th><th class="num">Baris</th><th>Oleh</th></tr></thead>
                    <tbody>
                        @foreach ($survey->fasihImports as $import)
                            <tr>
                                <td style="white-space:nowrap">
                                    {{ $import->created_at->locale('id')->translatedFormat('d M Y, H:i') }}
                                    @if ($loop->first) <span class="bdg bdg-blue" style="margin-left:6px">Terbaru</span>@endif
                                </td>
                                <td style="word-break:break-all">{{ $import->file_name }}</td>
                                <td class="num">{{ $fmt($import->row_count) }}</td>
                                <td class="muted-cell">{{ $import->user?->name ?? '–' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
