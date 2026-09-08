<x-app title="Reports" width="max-w-3xl">
    <header class="flex flex-wrap items-center gap-x-8 gap-y-4">
        <h1 class="text-2xl tracking-tight">Reports</h1>

        <form method="GET" action="{{ route('reports') }}" class="flex flex-wrap items-center gap-2">
            <select name="type" onchange="this.form.submit()"
                    class="cursor-pointer border border-neutral-300 bg-white px-2 py-1 text-xs tracking-tight transition hover:border-black focus:border-black focus:outline-none">
                <option value="">All types</option>
                @foreach ($types as $key => $label)
                    <option value="{{ $key }}" @selected($key === $type)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="status" onchange="this.form.submit()"
                    class="cursor-pointer border border-neutral-300 bg-white px-2 py-1 text-xs tracking-tight transition hover:border-black focus:border-black focus:outline-none">
                <option value="">All statuses</option>
                @foreach ($statuses as $case)
                    <option value="{{ $case->value }}" @selected($case === $status)>{{ $case->label() }}</option>
                @endforeach
            </select>

            {{-- Without JavaScript the selects cannot submit themselves. --}}
            <noscript>
                <button type="submit" class="border border-neutral-300 px-2 py-1 text-xs tracking-tight transition hover:border-black">
                    Filter
                </button>
            </noscript>

            @if ($type || $status)
                <a href="{{ route('reports') }}" class="text-xs text-neutral-500 underline underline-offset-4 transition hover:text-black hover:no-underline">
                    Clear
                </a>
            @endif
        </form>

        <a href="{{ route('reports.create') }}"
           class="ml-auto border border-black bg-black px-3 py-1.5 text-xs font-medium tracking-tight text-white transition hover:bg-white hover:text-black">
            Add report
        </a>
    </header>

    @if ($empty)
        <p class="mt-12 text-sm text-neutral-500">
            You have not reported anything yet.
            <a href="{{ route('reports.create') }}" class="underline underline-offset-4 hover:no-underline">Add one</a>.
        </p>
    @else
        <div class="mt-6 flex gap-8">
            @foreach ($counts as $state => $count)
                <div>
                    <div class="text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">{{ $state }}</div>
                    <div class="mt-1 text-xl tabular-nums tracking-tight">{{ number_format($count) }}</div>
                </div>
            @endforeach
        </div>

        @if ($reports->isEmpty())
            <p class="mt-12 text-sm text-neutral-500">No reports match this filter.</p>
        @else
        <div class="mt-10 overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-black text-[11px] uppercase tracking-[0.15em] whitespace-nowrap text-neutral-500">
                        <th class="w-28 py-2 pr-4 font-medium">Type</th>
                        <th class="py-2 pr-4 font-medium">Report</th>
                        <th class="w-24 py-2 pr-4 font-medium">Status</th>
                        <th class="w-32 py-2 font-medium">Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        <tr class="border-b border-neutral-200 align-top">
                            <td class="py-2.5 pr-4 whitespace-nowrap text-neutral-500">{{ $report['dataset'] }}</td>
                            <td class="py-2.5 pr-4">
                                {{ $report['title'] }}

                                @if ($report['source_url'])
                                    <a href="{{ $report['source_url'] }}" target="_blank" rel="noopener noreferrer"
                                       class="text-neutral-400 underline underline-offset-4 hover:text-black hover:no-underline">source</a>
                                @endif

                                @if ($report['detail'])
                                    <div class="mt-0.5 text-neutral-500">{{ $report['detail'] }}</div>
                                @endif
                            </td>
                            <td class="py-2.5 pr-4">
                                <span @class([
                                    'whitespace-nowrap border px-1.5 py-0.5 text-[11px]',
                                    'border-black bg-black text-white' => $report['status'] === \App\Enums\ReportStatus::Merged,
                                    'border-neutral-300 text-neutral-500' => $report['status'] === \App\Enums\ReportStatus::Pending,
                                    'border-neutral-300 text-neutral-400 line-through' => $report['status'] === \App\Enums\ReportStatus::Declined,
                                ])>{{ $report['status']->label() }}</span>
                            </td>
                            <td class="py-2.5 tabular-nums whitespace-nowrap text-neutral-600">
                                {{ $report['at']->format('j M Y, H:i') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <x-pager :paginator="$reports" />
        @endif
    @endif
</x-app>
