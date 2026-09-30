{{-- Satu baris tabel Monitoring Progres beserta turunannya (kecamatan > desa > SLS, atau mitra > SLS). Butuh $group, $depth, $parentId. --}}
@php
    $rowLevel = $group['level'] ?? $level;
    $rowChildren = $group['children'] ?? collect();
    $rowId = ($parentId === null ? '' : $parentId.'-').$rowIndex;
    $rowIsMitra = $rowLevel === 'mitra';
    $isExpandable = $rowChildren->isNotEmpty();
    $openShare = $share($group['open'], $group['total']);
    $drillUrl = $url($group['drill']);
    $rowTitle = $rowIsMitra ? ($group['pencacah'] ?? $group['context']) : $group['name'];
@endphp
<tr class="fx-row" data-id="{{ $rowId }}" data-depth="{{ $depth }}"
    @if ($parentId !== null) data-parent="{{ $parentId }}" hidden @endif
    @if ($isExpandable) data-expandable @elseif ($depth === 0) data-href="{{ $drillUrl }}" @endif
    data-search="{{ strtolower($group['name'].' '.$group['context'].' '.$group['code'].' '.implode(' ', $group['mitra'])) }}">
    <td class="no">{{ $depth === 0 ? $rowIndex + 1 : '' }}</td>
    <td class="fx-cell-name" style="--d:{{ $depth }}" data-sort="{{ $rowIsMitra ? strtolower($group['pencacah'] ?? '~'.$group['context']) : $group['key'] }}">
        @if ($isExpandable)
            <button type="button" class="fx-tog" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                <span class="fx-name">{{ $rowTitle }}</span>
            </button>
        @else
            <a class="fx-name" href="{{ $drillUrl }}">{{ $rowTitle }}</a>
        @endif
        @if ($rowIsMitra)
            <span class="fx-sub">{{ $group['pencacah'] ? $group['context'] : 'Nama belum tersedia' }}</span>
        @elseif ($isMitraLevel)
            <span class="fx-sub">{{ $group['context'] }}</span>
        @else
            <span class="fx-sub"><code>{{ $group['code'] }}</code></span>
        @endif
    </td>
    @if ($rowIsMitra)
        <td class="num" data-sort="{{ $group['sls_count'] }}">{{ $fmt($group['sls_count']) }}</td>
    @elseif ($isMitraLevel)
        <td class="num" data-sort="0"></td>
    @elseif ($isMitraDetail)
        <td class="fx-mitra" data-sort="{{ strtolower($group['context']) }}">{{ $group['context'] }}</td>
    @elseif ($rowLevel === 'sls')
        <td class="fx-mitra" data-sort="{{ strtolower(implode(', ', $group['mitra'])) }}">{{ implode(', ', $group['mitra']) }}</td>
    @else
        <td data-sort="{{ $group['mitra_count'] }}">{{ $fmt($group['mitra_count']) }} mitra · {{ $fmt($group['sls_count']) }} SLS</td>
    @endif
    <td class="num c-total" data-sort="{{ $group['total'] }}">{{ $fmt($group['total']) }}</td>
    <td class="num c-submit {{ $group['submit'] ? '' : 'zero' }}" data-sort="{{ $group['submit'] }}">{{ $fmt($group['submit']) }}</td>
    <td class="num c-draft {{ $group['draft'] ? '' : 'zero' }}" data-sort="{{ $group['draft'] }}">{{ $fmt($group['draft']) }}</td>
    <td class="num c-open {{ $openShare >= 50 ? 'hot' : '' }} {{ $group['open'] ? '' : 'zero' }}" data-sort="{{ $group['open'] }}">{{ $fmt($group['open']) }}</td>
    @if ($isCapi)<td class="num c-other {{ $group['other'] ? '' : 'zero' }}" data-sort="{{ $group['other'] }}">{{ $fmt($group['other']) }}</td>@endif
    <td class="num" data-sort="{{ $group['percent'] }}">
        <div class="fx-prog">
            <span class="fx-minibar" aria-hidden="true">
                <i style="width:{{ $share($group['submit'], $group['total']) }}%;background:var(--fx-submit)"></i>
                <i style="width:{{ $share($group['draft'], $group['total']) }}%;background:var(--fx-draft)"></i>
                <i style="width:{{ $openShare }}%;background:var(--fx-open)"></i>
            </span>
            <span class="fx-pct {{ $tone($group['percent']) }}">{{ $fmtPct($group['percent']) }}</span>
        </div>
    </td>
    <td class="fx-go" aria-hidden="true">{{ $isExpandable || $depth > 0 ? '' : '›' }}</td>
</tr>
@foreach ($rowChildren as $child)
    @include('panel.partials.monitoring-row', ['group' => $child, 'depth' => $depth + 1, 'parentId' => $rowId, 'rowIndex' => $loop->index])
@endforeach
