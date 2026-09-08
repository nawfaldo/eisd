@props(['regions', 'legend', 'unit', 'width', 'height'])

@php $hatch = 'nodata-'.Str::slug($unit); @endphp

<svg
    data-region-map
    viewBox="0 0 {{ $width }} {{ $height }}"
    class="mt-6 w-full"
    role="img"
    aria-label="{{ $unit }} by region"
>
    <defs>
        {{-- Absence of data is hatched; a plain fill would read as a measured zero. --}}
        <pattern id="{{ $hatch }}" width="4" height="4" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
            <rect width="4" height="4" fill="#fff" />
            <line x1="0" y1="0" x2="0" y2="4" stroke="#d4d4d4" stroke-width="1.2" />
        </pattern>
    </defs>

    @foreach ($regions as $region)
        @php
            $title = ($region['detail'] ?? null)
                ? $region['name'].' — '.$region['detail']
                : ($region['value'] > 0
                    ? $region['name'].' — '.number_format($region['value']).' '.$unit
                    : $region['name'].' — no data');
        @endphp

        @if ($region['link'])
            <a href="{{ $region['link'] }}">
                <path class="region region-{{ $region['shade'] }} region-link" d="{{ $region['d'] }}"
                      @if ($region['shade'] === 0) style="fill: url(#{{ $hatch }})" @endif>
                    <title>{{ $title }}</title>
                </path>
            </a>
        @else
            <path class="region region-{{ $region['shade'] }}" d="{{ $region['d'] }}"
                  @if ($region['shade'] === 0) style="fill: url(#{{ $hatch }})" @endif>
                <title>{{ $title }}</title>
            </path>
        @endif
    @endforeach
</svg>

<div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2">
    @foreach ($legend as $band)
        <span class="flex items-center gap-1.5">
            <span @class(['h-3 w-6 border border-neutral-400', 'swatch-'.$band['shade'] => $band['shade'] > 0])
                  @if ($band['shade'] === 0) style="background: repeating-linear-gradient(45deg, #fff 0 2px, #d4d4d4 2px 3px)" @endif></span>
            <span class="text-[11px] tabular-nums tracking-tight text-neutral-500">{{ $band['label'] }}</span>
        </span>
    @endforeach
    <span class="text-[11px] uppercase tracking-[0.15em] text-neutral-400">{{ $unit }}</span>
</div>
