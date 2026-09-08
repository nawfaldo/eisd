@php use App\Support\Budget; @endphp

<x-app title="Corruption" width="max-w-5xl">
    <a href="{{ route('home') }}" class="text-xs text-neutral-500 underline underline-offset-4 transition hover:text-black hover:no-underline">
        ← Home
    </a>

    <header class="mt-6 flex flex-wrap items-end gap-x-10 gap-y-4">
        <h1 class="text-2xl tracking-tight">Corruption</h1>

        <div class="flex gap-8">
            <div>
                <div class="text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">Published loss</div>
                <div class="mt-1 text-xl tabular-nums tracking-tight">{{ Budget::rupiah($stolen) }}</div>
            </div>
            <div>
                <div class="text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">Cases</div>
                <div class="mt-1 text-xl tabular-nums tracking-tight">{{ number_format($cases->count()) }}</div>
            </div>
        </div>
    </header>

    @if ($agency)
        <p class="mt-8 flex flex-wrap items-center gap-3 text-xs text-neutral-600">
            <span>Cases handled by <span class="text-black">{{ $agency->name }}</span></span>
            <a href="{{ route('corruption') }}" class="text-neutral-500 underline underline-offset-4 hover:text-black hover:no-underline">
                clear
            </a>
        </p>
    @endif

    @if ($agencies->isNotEmpty())
        <x-leaderboard :rows="$agencies" :max="max($agencies->max('value'), 1)"
                       heading="Cases by institution" />
    @endif

    @if ($cases->isEmpty())
        <p class="mt-12 text-sm text-neutral-500">No reported cases.</p>
    @else
        <div class="mt-12 overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-black text-[11px] uppercase tracking-[0.15em] text-neutral-500">
                        <th class="w-28 py-2 pr-4 font-medium">Date</th>
                        <th class="py-2 pr-4 font-medium">Case</th>
                        <th class="w-40 py-2 pr-4 font-medium">Where</th>
                        <th class="w-24 py-2 pr-4 font-medium">Status</th>
                        <th class="w-28 py-2 pr-4 text-right font-medium">Loss</th>
                        <th class="w-16 py-2 font-medium">Source</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cases as $case)
                        <tr class="border-b border-neutral-200 align-top">
                            <td class="py-2.5 pr-4 tabular-nums whitespace-nowrap">
                                {{ $case->reported_on?->format('j M Y') ?? $case->reported_raw ?? '—' }}
                            </td>
                            <td class="py-2.5 pr-4">
                                {{ $case->title }}
                                @foreach ($case->agencies as $handler)
                                    <a href="{{ route('corruption', ['agency' => $handler->name]) }}"
                                       class="text-neutral-400 underline-offset-4 hover:text-black hover:underline">·
                                        {{ $handler->name }}</a>
                                @endforeach
                            </td>
                            <td class="py-2.5 pr-4 text-neutral-600">
                                @if ($case->link)
                                    <a href="{{ $case->link }}" class="underline underline-offset-4 hover:no-underline">
                                        {{ $case->regency ?? $case->province }}
                                    </a>
                                @else
                                    {{ $case->regency ?? $case->province ?? 'National' }}
                                @endif
                            </td>
                            <td class="py-2.5 pr-4 text-neutral-600">{{ $case->status ?? '—' }}</td>
                            <td class="py-2.5 pr-4 text-right tabular-nums">
                                {{ $case->hasAmount() ? Budget::rupiah($case->amount) : '—' }}
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

        @if ($unpriced > 0)
            <p class="mt-6 text-[11px] text-neutral-400">
                “—” means no rupiah figure has been published for that case yet.
            </p>
        @endif
    @endif
</x-app>
