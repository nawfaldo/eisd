<x-app title="Add report" width="max-w-xl">
    <a href="{{ route('reports') }}" class="text-xs text-neutral-500 underline underline-offset-4 transition hover:text-black hover:no-underline">
        ← Reports
    </a>

    <h1 class="mt-6 text-2xl tracking-tight">Add report</h1>
    <p class="mt-2 text-xs leading-relaxed text-neutral-500">
        Report something for one of the datasets. Every report needs a source that can be checked,
        and arrives pending review.
    </p>

    @if ($errors->any())
        <p class="mt-6 border-l-2 border-black pl-3 text-xs text-neutral-600">
            Some fields need attention.
        </p>
    @endif

    <form method="POST" action="{{ route('reports.store') }}" class="rt mt-8">
        @csrf

        @foreach ($types as $type)
            <input type="radio" name="type" id="rt-{{ $type->value }}" value="{{ $type->value }}" class="rt-radio"
                   @checked($type->value === old('type', 'poisoning'))>
        @endforeach

        <div class="rt-nav">
            <div class="text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">Type</div>

            <div class="mt-2 flex flex-wrap gap-1">
                @foreach ($types as $type)
                    <label for="rt-{{ $type->value }}"
                           class="rt-label rt-label-{{ $type->value }} cursor-pointer border border-neutral-300 px-3 py-1.5 text-xs tracking-tight transition hover:border-black">
                        {{ $type->label() }}
                    </label>
                @endforeach
            </div>

            <div class="rt-panes mt-2 text-[11px] text-neutral-400">
                @foreach ($types as $type)
                    <p class="rt-pane rt-pane-{{ $type->value }}">{{ $type->describes() }}.</p>
                @endforeach
            </div>
        </div>

        <div class="mt-8 space-y-5">
            <div>
                <label for="region_id" class="block text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">
                    Province
                </label>

                <select id="region_id" name="region_id"
                        @class([
                            'mt-2 block w-full border bg-white px-3 py-2.5 text-sm text-black outline-none transition focus:border-black focus:ring-1 focus:ring-black',
                            'border-black ring-1 ring-black' => $errors->has('region_id'),
                            'border-neutral-300' => ! $errors->has('region_id'),
                        ])>
                    <option value="">National / not tied to a province</option>
                    @foreach ($provinces as $id => $name)
                        <option value="{{ $id }}" @selected($id === old('region_id'))>{{ $name }}</option>
                    @endforeach
                </select>

                @error('region_id')
                    <p class="mt-2 text-xs text-black">{{ $message }}</p>
                @enderror
            </div>

            <x-field name="title" label="Name" placeholder="School, city, or what the case is" />
            <x-field name="regency" label="Regency (optional)" />
            <x-field name="occurred_on" label="Date" type="date" />

            {{-- Only the counts differ by type; everything above is asked of every report. --}}
            <div class="rt-panes">
                <div class="rt-pane rt-pane-poisoning space-y-5">
                    <x-field name="victims" label="Poisoned" type="number" min="0" placeholder="Leave blank if uncounted" />
                    <x-field name="deaths" label="Deaths" type="number" min="0" />
                </div>

                <div class="rt-pane rt-pane-sppg space-y-5">
                    <x-field name="outlets" label="Outlets" type="number" min="0" />
                </div>

                <div class="rt-pane rt-pane-corruption space-y-5">
                    <x-field name="amount" label="State loss (rupiah)" type="number" min="0" placeholder="Leave blank if unpublished" />
                    <x-field name="agency" label="Agency" placeholder="KPK, Kejaksaan, Polri, BPK" />
                </div>
            </div>

            <x-field name="source_url" label="Source" type="url" placeholder="https://…" required />

            <div>
                <label for="note" class="block text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">
                    Note (optional)
                </label>

                <textarea id="note" name="note" rows="3"
                          @class([
                              'mt-2 block w-full border bg-white px-3 py-2.5 text-sm text-black outline-none transition placeholder:text-neutral-400 focus:border-black focus:ring-1 focus:ring-black',
                              'border-black ring-1 ring-black' => $errors->has('note'),
                              'border-neutral-300' => ! $errors->has('note'),
                          ])>{{ old('note') }}</textarea>

                @error('note')
                    <p class="mt-2 text-xs text-black">{{ $message }}</p>
                @enderror
            </div>

            <x-button>Submit report</x-button>
        </div>
    </form>
</x-app>
