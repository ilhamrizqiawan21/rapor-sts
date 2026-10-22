@if ($paginator->hasPages())
    <span>Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data</span>
    <nav class="pgn" role="navigation" aria-label="Navigasi halaman">
        @if ($paginator->onFirstPage())
            <span class="pgn-i pgn-off" aria-disabled="true">&laquo; Sebelumnya</span>
        @else
            <a class="pgn-i" href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo; Sebelumnya</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pgn-i pgn-off">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pgn-i pgn-cur" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pgn-i" href="{{ $url }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="pgn-i" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya &raquo;</a>
        @else
            <span class="pgn-i pgn-off" aria-disabled="true">Berikutnya &raquo;</span>
        @endif
    </nav>
@endif
