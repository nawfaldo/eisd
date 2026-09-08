@props(['budget'])

@php
    use App\Support\Budget;

    $bands = $budget['bands'];
    $widest = max(array_column($bands, 'share')) ?: 1;
@endphp

<header class="mt-20 flex flex-wrap items-baseline gap-x-8 gap-y-2">
    <div>
        <div class="text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">Allocated</div>
        <div class="mt-1 text-2xl tabular-nums tracking-tight">{{ Budget::rupiah($budget['total']) }}</div>
    </div>
    <div>
        <div class="text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">Corrupted</div>
        <a href="{{ route('corruption') }}" class="group mt-1 flex items-baseline gap-2">
            <span class="text-2xl tabular-nums tracking-tight">{{ Budget::rupiah($budget['corrupted']) }}</span>
            <span class="text-lg text-neutral-400 transition group-hover:text-black" aria-hidden="true">→</span>
            <span class="sr-only">See the cases</span>
        </a>
    </div>
</header>

<ul class="mt-8 max-w-2xl">
    @foreach ($bands as $band)
        {{-- Nested rows indent the label only, so every bar still starts on the same line. --}}
        <li class="flex items-center gap-3 py-1.5">
            <span @class([
                'w-44 shrink-0 text-[11px] tracking-tight',
                'pl-6 text-neutral-500' => $band['nested'],
            ])>{{ $band['label'] }}</span>
            <span class="h-2.5 flex-1 bg-neutral-100">
                <span @class([
                    'block h-2.5',
                    'bg-black' => ! $band['nested'],
                    'bg-neutral-400' => $band['nested'],
                ]) style="width: {{ max(0.8, $band['share'] / $widest * 100) }}%"></span>
            </span>
            <span class="w-20 shrink-0 text-right text-[11px] tabular-nums">{{ Budget::rupiah($band['amount']) }}</span>
            <span class="w-10 shrink-0 text-right text-[11px] tabular-nums text-neutral-400">
                {{ number_format($band['share'] * 100) }}%
            </span>
        </li>
    @endforeach
</ul>
