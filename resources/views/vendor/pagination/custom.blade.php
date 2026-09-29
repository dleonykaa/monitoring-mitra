@if ($paginator->hasPages())
    <nav class="simkm-pagination" role="navigation" aria-label="Navigasi halaman">
        <div class="simkm-pagination-info">
            Menampilkan {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data
        </div>
        <ul class="simkm-pagination-list">
            @if ($paginator->onFirstPage())
                <li><span class="simkm-page-link disabled" aria-disabled="true">&laquo; Pertama</span></li>
                <li><span class="simkm-page-link disabled" aria-disabled="true">&lsaquo; Sebelumnya</span></li>
            @else
                <li><a class="simkm-page-link" href="{{ $paginator->url(1) }}" aria-label="Ke halaman pertama">&laquo; Pertama</a></li>
                <li><a class="simkm-page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">&lsaquo; Sebelumnya</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="simkm-page-link dots" aria-disabled="true">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span class="simkm-page-link active" aria-current="page">{{ $page }}</span></li>
                        @else
                            <li><a class="simkm-page-link" href="{{ $url }}" aria-label="Ke halaman {{ $page }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a class="simkm-page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya &rsaquo;</a></li>
                <li><a class="simkm-page-link" href="{{ $paginator->url($paginator->lastPage()) }}" aria-label="Ke halaman terakhir">Terakhir &raquo;</a></li>
            @else
                <li><span class="simkm-page-link disabled" aria-disabled="true">Berikutnya &rsaquo;</span></li>
                <li><span class="simkm-page-link disabled" aria-disabled="true">Terakhir &raquo;</span></li>
            @endif
        </ul>
    </nav>
@endif
