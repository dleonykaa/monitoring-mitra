{{-- Tabel survei yang dipegang mitra. Butuh $title, $items (dari MitraSurveyHoldings), dan $emptyText. --}}
@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $statusTone = ['Berjalan' => 'bdg-blue', 'Draft' => 'bdg-amber', 'Selesai' => 'bdg-green'];
    $tableId = \Illuminate\Support\Str::slug($title);
@endphp
<section class="pnl flush" aria-labelledby="{{ $tableId }}">
    <div class="pnl-h"><h2 id="{{ $tableId }}">{{ $title }} <span class="num-chip">{{ $items->count() }}</span></h2></div>
    <div class="pnl-b">
        @if ($items->isEmpty())
            <div class="empty"><b>{{ $emptyText }}</b></div>
        @else
            <div class="tbl-wrap">
                <table class="tbl stack" style="min-width:640px">
                    <thead><tr><th>Survei</th><th>Periode</th><th>Status</th><th>Progres</th></tr></thead>
                    <tbody>
                        @foreach ($items as $item)
                            @php($survey = $item['survey'])
                            <tr>
                                <td style="max-width:320px">
                                    <a href="/admin/surveys/{{ $survey->id }}" style="color:inherit;text-decoration:none">
                                        <b style="display:block;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $survey->title }}</b>
                                    </a>
                                    <span class="tag">{{ $survey->typeLabel() }}</span>
                                </td>
                                <td style="white-space:nowrap;font-size:12.5px">{{ $survey->start_date->locale('id')->translatedFormat('d M') }} – {{ $survey->end_date->locale('id')->translatedFormat('d M Y') }}</td>
                                <td><span class="bdg {{ $statusTone[$survey->status] ?? 'bdg-gray' }}">{{ $survey->status }}</span></td>
                                <td>
                                    <div class="meter" style="max-width:200px">
                                        <span class="bar2"><i class="fill-good" style="width:{{ $item['percent'] }}%"></i></span>
                                        <small>{{ $fmt($item['progress']) }} / {{ $fmt($item['target']) }} {{ $item['unit'] }}</small>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
