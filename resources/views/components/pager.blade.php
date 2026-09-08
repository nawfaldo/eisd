@props(['paginator'])

@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();

        // First, last, and the pages either side of where you are; the rest collapse to an ellipsis.
        $pages = collect(range(1, $last))
            ->filter(fn (int $page) => $page === 1 || $page === $last || abs($page - $current) <= 2);
    @endphp

    <nav class="mt-8 flex flex-wrap items-center justify-center gap-1 text-xs tabular-nums">
        @php $previous = 0; @endphp

        @foreach ($pages as $page)
            @if ($page - $previous > 1)
                <span class="px-2 py-1 text-neutral-300">…</span>
            @endif

            @if ($page === $current)
                <span aria-current="page" class="px-2 py-1 font-medium text-black">{{ $page }}</span>
            @else
                <a href="{{ $paginator->url($page) }}" class="px-2 py-1 text-neutral-500 transition hover:text-black">{{ $page }}</a>
            @endif

            @php $previous = $page; @endphp
        @endforeach
    </nav>
@endif

<p class="mt-3 text-center text-[11px] tabular-nums text-neutral-400">
    {{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }} of {{ number_format($paginator->total()) }}
</p>
