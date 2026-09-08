<x-app title="Changelog" width="max-w-3xl">
    <header class="flex flex-wrap items-center gap-x-8 gap-y-4">
        <h1 class="text-2xl tracking-tight">Changelog</h1>

        <form method="GET" action="{{ route('changelog') }}" class="flex flex-wrap items-center gap-2">
            <select name="dataset" onchange="this.form.submit()"
                    class="cursor-pointer border border-neutral-300 bg-white px-2 py-1 text-xs tracking-tight transition hover:border-black focus:border-black focus:outline-none">
                <option value="">All data</option>
                @foreach ($datasets as $key => $label)
                    <option value="{{ $key }}" @selected($key === $dataset)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="date" onchange="this.form.submit()"
                    class="cursor-pointer border border-neutral-300 bg-white px-2 py-1 text-xs tracking-tight transition hover:border-black focus:border-black focus:outline-none">
                <option value="">All dates</option>
                @foreach ($dates as $available)
                    <option value="{{ $available }}" @selected($available === $date)>
                        {{ \Illuminate\Support\Carbon::parse($available)->format('j M Y') }}
                    </option>
                @endforeach
            </select>

            {{-- Without JavaScript the selects cannot submit themselves. --}}
            <noscript>
                <button type="submit" class="border border-neutral-300 px-2 py-1 text-xs tracking-tight transition hover:border-black">
                    Filter
                </button>
            </noscript>

            @if ($dataset || $date)
                <a href="{{ route('changelog') }}" class="text-xs text-neutral-500 underline underline-offset-4 transition hover:text-black hover:no-underline">
                    Clear
                </a>
            @endif
        </form>
    </header>

    @if ($entries->isEmpty())
        <p class="mt-12 text-sm text-neutral-500">
            {{ $dataset || $date ? 'No uploads match this filter.' : 'Nothing has been uploaded yet.' }}
        </p>
    @else
        <div class="mt-12 overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-black text-[11px] uppercase tracking-[0.15em] whitespace-nowrap text-neutral-500">
                        <th class="w-28 py-2 pr-4 font-medium">Data</th>
                        <th class="py-2 pr-4 font-medium">Record</th>
                        <th class="w-28 py-2 pr-4 font-medium">Uploaded by</th>
                        <th class="w-36 py-2 font-medium">Uploaded on</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entries as $entry)
                        <tr class="border-b border-neutral-200 align-top">
                            <td class="py-2.5 pr-4 whitespace-nowrap text-neutral-500">{{ $entry['dataset'] }}</td>
                            <td class="py-2.5 pr-4">
                                @if ($entry['link'])
                                    <a href="{{ $entry['link'] }}" class="underline underline-offset-4 hover:no-underline">
                                        {{ $entry['title'] }}
                                    </a>
                                @else
                                    {{ $entry['title'] }}
                                @endif

                                @if ($entry['source_url'])
                                    <a href="{{ $entry['source_url'] }}" target="_blank" rel="noopener noreferrer"
                                       class="text-neutral-400 underline underline-offset-4 hover:text-black hover:no-underline">source</a>
                                @endif

                                @if ($entry['detail'])
                                    <div class="mt-0.5 text-neutral-500">{{ $entry['detail'] }}</div>
                                @endif
                            </td>
                            <td class="py-2.5 pr-4">{{ $entry['uploader'] ?? '—' }}</td>
                            <td class="py-2.5 tabular-nums whitespace-nowrap text-neutral-600">
                                {{ $entry['uploaded_at']->format('j M Y, H:i') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <x-pager :paginator="$entries" />

    @endif
</x-app>
