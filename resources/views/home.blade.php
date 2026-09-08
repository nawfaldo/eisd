<x-app title="Chart" width="max-w-5xl">
    @php
        $best = $coverage['rows']->last();
        $worst = $coverage['rows']->first();
    @endphp
    <section class="max-w-2xl">
        <h1 class="text-xl leading-snug tracking-tight">
            Does every kota get the same public service, or only the richer ones?
        </h1>
        @if ($coverage['rows']->isNotEmpty())
        <p class="mt-3 text-xs leading-relaxed text-neutral-500">
            MBG runs {{ number_format($coverage['rows']->sum('outlets')) }} kitchens across
            {{ number_format($coverage['rows']->count()) }} regencies, but not evenly.
            {{ $best->label }} has {{ number_format($best->value, 1) }} per 100,000 residents.
            {{ $worst->label }} has {{ number_format($worst->value, 1) }}, which is
            {{ number_format($best->value / max($worst->value, 0.1)) }} times thinner for a
            programme meant to reach both. Everything below measures that gap against how
            wealthy each city already is, and what it costs where the service is thinnest.
        </p>
        @endif
    </section>

    <header class="mt-20 flex flex-wrap items-baseline gap-x-8 gap-y-2">
        <div>
            <div class="text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">Outlets</div>
            <div class="mt-1 text-2xl tabular-nums tracking-tight">{{ number_format($totalOutlets) }}</div>
        </div>
        <div>
            <div class="text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">Per 100k</div>
            <div class="mt-1 text-2xl tabular-nums tracking-tight">{{ number_format($coverage['national'], 1) }}</div>
        </div>
        <div>
            <div class="text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">Median kota</div>
            <div class="mt-1 text-2xl tabular-nums tracking-tight">{{ number_format($coverage['median'], 1) }}</div>
        </div>
    </header>

    @if ($underserved->isNotEmpty())
        <x-leaderboard :rows="$underserved" :max="max($underserved->max('value'), 0.1)"
                       heading="Least-served kota — kitchens per 100,000 residents"
                       :precision="1" open />
    @endif

    @if (! empty($wealth['points']))
    <h2 class="mt-16 text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">
        Outlets vs city wealth
    </h2>

    <x-wealth-chart :points="$wealth['points']" :markers="$wealth['markers']" />

    <details class="mt-4 max-w-2xl text-[11px] leading-relaxed text-neutral-500">
        <summary class="flex w-fit cursor-pointer items-center gap-2 font-medium uppercase tracking-[0.15em] text-neutral-400 transition hover:text-black">
            <svg class="chevron size-2.5" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path d="M3 1 L7 5 L3 9" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            What counts as rich or poor
        </summary>

        <p class="mt-3">
            The horizontal axis is Meta&rsquo;s Relative Wealth Index, averaged over the 2.4&nbsp;km grid
            cells inside each city. It estimates <em>household living standards</em> — ownership of things
            like electricity, a phone, a fridge, a vehicle, and the materials a home is built from —
            by training on household surveys and predicting from satellite imagery and connectivity data.
            It deliberately measures what families have, not what a district produces, and is built
            independently of government statistics.
        </p>
        <p class="mt-2">
            It is <em>relative</em>: it ranks places within Indonesia, so a value carries no currency
            meaning on its own. We use it in preference to GDP per capita because mining districts
            distort output figures — Mimika and Teluk Bintuni rank among Indonesia&rsquo;s richest by
            GDP per head while their households rank among the poorest.
        </p>
        <p class="mt-2">
            Cities here span {{ number_format($wealth['min'], 2) }} to {{ number_format($wealth['max'], 2) }}.
            The three dashed lines are rank cut-offs, not official thresholds — there is no official
            definition of a rich city, so each is simply a percentile of these
            {{ count($wealth['points']) }} cities:
        </p>
        <ul class="mt-2 space-y-1">
            <li>
                <span class="text-black">Median</span> — the middle city, at
                {{ number_format($wealth['median'], 2) }}.
                {{ $wealth['poorCities'] }} cities fall to its left, {{ $wealth['richCities'] }} to its right.
            </li>
            <li>
                <span class="text-black">Rich</span> — the top quarter, at
                {{ number_format($wealth['rich'], 2) }} and above ({{ $wealth['richCount'] }} cities).
            </li>
            <li>
                <span class="text-black">Very rich</span> — the top tenth, at
                {{ number_format($wealth['veryRich'], 2) }} and above ({{ $wealth['veryRichCount'] }} cities).
            </li>
        </ul>
        <p class="mt-2">
            The splits are even in the number of cities, not in population, so the richer bands also hold
            more people.
        </p>
        <p class="mt-2">
            Outlet counts use a square-root scale. Hollow points are Papua, shown whole-province.
        </p>
    </details>
    @endif

    <x-spend :budget="$budget" />

    <header class="mt-20 flex flex-wrap items-baseline gap-x-8 gap-y-2">
        <div>
            <div class="text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">Poisoned</div>
            <div class="mt-1 text-2xl tabular-nums tracking-tight">{{ number_format($totalVictims) }}</div>
        </div>
        <div>
            <div class="text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">Cases</div>
            <div class="mt-1 text-2xl tabular-nums tracking-tight">{{ number_format($totalCases) }}</div>
        </div>
        <p class="ml-auto max-w-xs text-xs leading-relaxed text-neutral-500">
            What the service costs the communities it reaches, by province.
        </p>
    </header>

    <x-density-map :regions="$poisoned['regions']" :legend="$poisoned['legend']"
                   unit="poisoned" :width="$width" :height="$height" />

    <x-leaderboard :rows="$poisonedBoard" :max="max((int) $poisonedBoard->max('value'), 1)"
                   heading="Poisoned by province" />

    <div class="tf mt-14">
        @foreach ($ranges as $key => $label)
            <input type="radio" name="timeframe" id="tf-{{ $key }}" class="tf-radio"
                   @checked($key === $defaultRange)>
        @endforeach

        <div class="tf-nav flex flex-wrap items-baseline justify-between gap-4">
            <h2 class="text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">
                Poisoned over time
            </h2>

            <nav class="flex items-center gap-1">
                @foreach ($ranges as $key => $label)
                    <label for="tf-{{ $key }}"
                           class="tf-label tf-label-{{ $key }} cursor-pointer px-2 py-1 text-[11px] font-medium uppercase tracking-[0.1em] text-neutral-500 transition hover:text-black">
                        {{ $label }}
                    </label>
                @endforeach
            </nav>
        </div>

        <div class="tf-panes">
            @foreach ($timelines as $key => $timeline)
                <div class="tf-pane tf-pane-{{ $key }}">
                    <x-chart :timeline="$timeline" />
                </div>
            @endforeach
        </div>
    </div>
    @if ($stunting->isNotEmpty())
    @php $peak = $stunting->max('value'); @endphp
    <header class="mt-20 flex flex-wrap items-baseline gap-x-8 gap-y-2">
        <div>
            <div class="text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">
                Has stunting become less?
            </div>
            <div class="mt-1 text-2xl tracking-tight">Falling, but not yet because of MBG</div>
        </div>
    </header>

    <ul class="mt-8 max-w-2xl">
        @foreach ($stunting as $row)
            <li class="flex items-center gap-3 py-1.5">
                <span class="w-12 shrink-0 text-[11px] tabular-nums tracking-tight">{{ $row->period }}</span>
                <span class="h-2.5 flex-1 bg-neutral-100">
                    <span class="block h-2.5 bg-black" style="width: {{ $row->value / $peak * 100 }}%"></span>
                </span>
                <span class="w-14 shrink-0 text-right text-[11px] tabular-nums">{{ number_format($row->value, 1) }}{{ $row->unit }}</span>
                <span class="hidden w-40 shrink-0 truncate text-[11px] text-neutral-400 sm:block">{{ $row->source }}</span>
            </li>
        @endforeach
        <li class="flex items-center gap-3 border-t border-dashed border-neutral-300 py-1.5 pt-3">
            <span class="w-12 shrink-0 text-[11px] tabular-nums tracking-tight text-neutral-400">{{ \App\Models\Outcome::STARTED }}</span>
            <span class="flex-1 text-[11px] text-neutral-400">MBG begins &mdash; no published measurement since</span>
        </li>
    </ul>
    @endif
</x-app>
