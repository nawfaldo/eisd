<x-app title="Merge" width="max-w-3xl">
    <header class="flex flex-wrap items-center gap-x-8 gap-y-4">
        <h1 class="text-2xl tracking-tight">Merge</h1>

        @if ($types->isNotEmpty())
            <form method="GET" action="{{ route('merge') }}" class="flex flex-wrap items-center gap-2">
                <select name="type" onchange="this.form.submit()"
                        class="cursor-pointer border border-neutral-300 bg-white px-2 py-1 text-xs tracking-tight transition hover:border-black focus:border-black focus:outline-none">
                    <option value="">All types</option>
                    @foreach ($types as $case)
                        <option value="{{ $case->value }}" @selected($case === $type)>{{ $case->label() }}</option>
                    @endforeach
                </select>

                <select name="reporter" onchange="this.form.submit()"
                        class="cursor-pointer border border-neutral-300 bg-white px-2 py-1 text-xs tracking-tight transition hover:border-black focus:border-black focus:outline-none">
                    <option value="">All reporters</option>
                    @foreach ($reporters as $id => $name)
                        <option value="{{ $id }}" @selected($id === $reporter)>{{ $name }}</option>
                    @endforeach
                </select>

                {{-- Without JavaScript the selects cannot submit themselves. --}}
                <noscript>
                    <button type="submit" class="border border-neutral-300 px-2 py-1 text-xs tracking-tight transition hover:border-black">
                        Filter
                    </button>
                </noscript>

                @if ($type || $reporter)
                    <a href="{{ route('merge') }}" class="text-xs text-neutral-500 underline underline-offset-4 transition hover:text-black hover:no-underline">
                        Clear
                    </a>
                @endif
            </form>
        @endif
    </header>

    @if ($reports->isEmpty())
        <p class="mt-12 text-sm text-neutral-500">
            {{ $type || $reporter ? 'No pending reports match this filter.' : 'Nothing is waiting for review.' }}
        </p>
    @else
        <ul class="mt-10 border-t border-black">
            @foreach ($reports as $report)
                <li class="flex flex-wrap items-start gap-x-6 gap-y-3 border-b border-neutral-200 py-4">
                    <div class="min-w-0 flex-1 text-xs">
                        <div class="text-[11px] uppercase tracking-[0.15em] text-neutral-400">
                            {{ $report->type->label() }}
                        </div>

                        <div class="mt-1.5">
                            {{ $report->title ?: ($report->regency ?: ($report->province ?: 'National')) }}

                            <a href="{{ $report->source_url }}" target="_blank" rel="noopener noreferrer"
                               class="text-neutral-400 underline underline-offset-4 hover:text-black hover:no-underline">source</a>
                        </div>

                        <div class="mt-0.5 text-neutral-500">{{ $report->detail() }}</div>

                        @if ($report->note)
                            <p class="mt-2 border-l-2 border-neutral-200 pl-3 text-neutral-500">{{ $report->note }}</p>
                        @endif

                        <div class="mt-2 text-[11px] text-neutral-400">
                            {{ $report->author->name }} · {{ $report->created_at->format('j M Y, H:i') }}
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('merge.merge', $report) }}">
                            @csrf
                            <button type="submit"
                                    class="border border-black bg-black px-3 py-1.5 text-xs font-medium tracking-tight text-white transition hover:bg-white hover:text-black">
                                Merge
                            </button>
                        </form>

                        <form method="POST" action="{{ route('merge.decline', $report) }}">
                            @csrf
                            <button type="submit"
                                    class="border border-neutral-300 px-3 py-1.5 text-xs tracking-tight text-neutral-500 transition hover:border-black hover:text-black">
                                Decline
                            </button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>

        <x-pager :paginator="$reports" />
    @endif
</x-app>
