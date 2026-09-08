<x-app :title="$region['name']" width="max-w-5xl">
    <a href="{{ route('home') }}" class="text-xs text-neutral-500 underline underline-offset-4 transition hover:text-black hover:no-underline">
        ← All provinces
    </a>

    <header class="mt-6 flex flex-wrap items-end gap-x-10 gap-y-4">
        <svg viewBox="{{ $viewBox }}" class="h-20 w-28 shrink-0" role="presentation">
            <path d="{{ $region['d'] }}" fill="#000" />
        </svg>

        <div>
            <h1 class="text-2xl tracking-tight">{{ $region['name'] }}</h1>
            @if ($provinces->count() > 1)
                <p class="mt-1 text-xs text-neutral-500">Includes {{ $provinces->join(', ', ' and ') }}</p>
            @endif
        </div>

        <div class="flex gap-8">
            <div>
                <div class="text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">Poisoned</div>
                <div class="mt-1 text-xl tabular-nums tracking-tight">{{ number_format($victims) }}</div>
            </div>
            <div>
                <div class="text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">Cases</div>
                <div class="mt-1 text-xl tabular-nums tracking-tight">{{ number_format($cases->count()) }}</div>
            </div>
            @if ($outlets > 0)
                <div>
                    <div class="text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">Outlets</div>
                    <div class="mt-1 text-xl tabular-nums tracking-tight">{{ number_format($outlets) }}</div>
                </div>
            @endif
            @if ($deaths > 0)
                <div>
                    <div class="text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">Deaths</div>
                    <div class="mt-1 text-xl tabular-nums tracking-tight">{{ number_format($deaths) }}</div>
                </div>
            @endif
        </div>
    </header>

    @if ($cases->isEmpty())
        <p class="mt-12 text-sm text-neutral-500">No reported cases.</p>
    @else
        <div class="mt-12 overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-black text-[11px] uppercase tracking-[0.15em] text-neutral-500">
                        <th class="w-28 py-2 pr-4 font-medium">Date</th>
                        <th class="w-44 py-2 pr-4 font-medium">Regency</th>
                        <th class="py-2 pr-4 font-medium">Place</th>
                        <th class="w-20 py-2 pr-4 text-right font-medium">Poisoned</th>
                        <th class="w-16 py-2 font-medium">Source</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cases as $case)
                        <tr class="border-b border-neutral-200 align-top">
                            <td class="py-2.5 pr-4 tabular-nums whitespace-nowrap">
                                {{ $case->occurred_on?->format('j M Y') ?? $case->occurred_raw ?? '—' }}
                            </td>
                            <td class="py-2.5 pr-4">{{ $case->regency ?? '—' }}</td>
                            <td class="py-2.5 pr-4 text-neutral-600">{{ $case->place ?? '—' }}</td>
                            <td class="py-2.5 pr-4 text-right tabular-nums">
                                {{ $case->victims !== null ? number_format($case->victims) : '—' }}
                            </td>
                            <td class="py-2.5">
                                @if ($case->source_url)
                                    <a href="{{ $case->source_url }}" target="_blank" rel="noopener noreferrer"
                                       class="underline underline-offset-4 hover:no-underline">link</a>
                                @else
                                    <span class="text-neutral-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="mt-6 text-[11px] text-neutral-400">
            “—” means the source reported the case without a precise count.
        </p>
    @endif
</x-app>
