@props(['rows', 'max', 'heading', 'precision' => 0, 'open' => false])

<details class="mt-14"{{ $open ? ' open' : '' }}>
    <summary class="flex w-fit cursor-pointer items-center gap-2 text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500 transition hover:text-black">
        <svg class="chevron size-2.5" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
            <path d="M3 1 L7 5 L3 9" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        {{ $heading }}
    </summary>

    <ol class="mt-5 columns-1 gap-x-10 sm:columns-2 lg:columns-3">
        @foreach ($rows as $row)
            <li class="break-inside-avoid">
                @php $href = $row->link ?? null; @endphp

                <{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" @endif class="group flex items-center gap-2 py-1">
                    <span class="w-5 shrink-0 text-right text-[11px] tabular-nums text-neutral-400">{{ $loop->iteration }}</span>
                    <span @class([
                        'w-28 shrink-0 truncate text-[11px] tracking-tight',
                        'group-hover:underline group-hover:underline-offset-4' => $href,
                    ]) title="{{ $row->label }}">{{ $row->label }}</span>
                    <span class="h-2.5 flex-1 bg-neutral-100">
                        <span class="block h-2.5 bg-black" style="width: {{ max(0.8, $row->value / $max * 100) }}%"></span>
                    </span>
                    <span class="w-12 shrink-0 text-right text-[11px] tabular-nums">{{ number_format($row->value, $precision) }}</span>
                </{{ $href ? 'a' : 'div' }}>
            </li>
        @endforeach
    </ol>
</details>
