@php
    $firstItem = $paginator->firstItem() ?? 0;
    $lastItem = $paginator->lastItem() ?? 0;
    $totalItems = method_exists($paginator, 'total') ? $paginator->total() : $lastItem;
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();

    $startPage = max(2, $currentPage - 1);
    $endPage = min($lastPage - 1, $currentPage + 1);
@endphp

<nav class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-sm-between gap-2">
    <div>
        <p class="small text-muted mb-0 pagination-summary">
            {!! __('Showing') !!}
            <span class="fw-semibold">{{ $firstItem }}</span>
            {!! __('to') !!}
            <span class="fw-semibold">{{ $lastItem }}</span>
            {!! __('of') !!}
            <span class="fw-semibold">{{ $totalItems }}</span>
            {!! __('results') !!}
        </p>
    </div>

    <div>
        <ul class="pagination mb-0">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled pagination-arrow pagination-arrow-prev" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="page-link" aria-hidden="true">&lt;</span>
                </li>
            @else
                <li class="page-item pagination-arrow pagination-arrow-prev">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lt;</a>
                </li>
            @endif

            {{-- Compact Pagination Elements --}}
            @if ($lastPage > 1)
                {{-- First page --}}
                @if ($currentPage === 1)
                    <li class="page-item active" aria-current="page"><span class="page-link">1</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginator->url(1) }}">1</a></li>
                @endif

                {{-- Left ellipsis --}}
                @if ($startPage > 2)
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">...</span></li>
                @endif

                {{-- Middle pages around current page --}}
                @for ($page = $startPage; $page <= $endPage; $page++)
                    @if ($page === $currentPage)
                        <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a></li>
                    @endif
                @endfor

                {{-- Right ellipsis --}}
                @if ($endPage < $lastPage - 1)
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">...</span></li>
                @endif

                {{-- Last page --}}
                @if ($currentPage === $lastPage)
                    <li class="page-item active" aria-current="page"><span class="page-link">{{ $lastPage }}</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginator->url($lastPage) }}">{{ $lastPage }}</a></li>
                @endif
            @else
                <li class="page-item active" aria-current="page"><span class="page-link">1</span></li>
            @endif

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item pagination-arrow pagination-arrow-next">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&gt;</a>
                </li>
            @else
                <li class="page-item disabled pagination-arrow pagination-arrow-next" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="page-link" aria-hidden="true">&gt;</span>
                </li>
            @endif
        </ul>
    </div>
</nav>
