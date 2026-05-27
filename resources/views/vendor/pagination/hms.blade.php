@if ($paginator->hasPages())
<nav aria-label="{{ __('Pagination Navigation') }}" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem">

    @if ($paginator->firstItem())
    <p style="font-size:.8rem;color:#64748B;margin:0">
        Showing <strong style="color:#1A202C">{{ $paginator->firstItem() }}</strong>
        to <strong style="color:#1A202C">{{ $paginator->lastItem() }}</strong>
        of <strong style="color:#1A202C">{{ $paginator->total() }}</strong> results
    </p>
    @endif

    <div class="hms-pagination" style="margin-top:0">

        @if ($paginator->onFirstPage())
            <span style="opacity:.35;cursor:not-allowed" aria-disabled="true"><i class="bi bi-chevron-left"></i></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}"><i class="bi bi-chevron-left"></i></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span style="border:none;color:#94A3B8;font-size:.8rem">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}"><i class="bi bi-chevron-right"></i></a>
        @else
            <span style="opacity:.35;cursor:not-allowed" aria-disabled="true"><i class="bi bi-chevron-right"></i></span>
        @endif

    </div>
</nav>
@endif
