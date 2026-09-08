@props(['points', 'markers'])

@php
    $w = 1000; $h = 340; $padL = 52; $padR = 14; $padT = 16; $padB = 42;
    $plotW = $w - $padL - $padR;
    $plotH = $h - $padT - $padB;

    $xs = array_column($points, 'rwi');
    $xMin = floor(min($xs) * 10) / 10;
    $xMax = ceil(max($xs) * 10) / 10;
    $yMax = max(array_column($points, 'outlets'));

    // Outlets are heavily skewed (1 to 707), so a square-root scale keeps the
    // crowded low end readable while still starting at a true zero.
    $sx = fn (float $v) => $padL + ($v - $xMin) / max($xMax - $xMin, 0.001) * $plotW;
    $sy = fn (float $v) => $padT + $plotH - sqrt(max($v, 0)) / sqrt($yMax) * $plotH;

    $yTicks = [0, 25, 100, 250, 500, 700];
    $xTicks = [];
    for ($t = $xMin; $t <= $xMax + 0.001; $t += 0.5) { $xTicks[] = round($t, 2); }
@endphp

<svg viewBox="0 0 {{ $w }} {{ $h }}" class="mt-5 w-full" role="img"
     aria-label="Outlets against relative wealth, one point per city">
    @foreach ($yTicks as $tick)
        @php $y = round($sy($tick), 1); @endphp
        <line x1="{{ $padL }}" y1="{{ $y }}" x2="{{ $w - $padR }}" y2="{{ $y }}"
              stroke="{{ $tick === 0 ? '#000' : '#ededed' }}" stroke-width="1" vector-effect="non-scaling-stroke" />
        <text x="{{ $padL - 8 }}" y="{{ $y + 3 }}" text-anchor="end" font-size="9" fill="#a3a3a3">{{ $tick }}</text>
    @endforeach

    @foreach ($xTicks as $tick)
        <text x="{{ round($sx($tick), 1) }}" y="{{ $h - 22 }}" text-anchor="middle" font-size="9" fill="#a3a3a3">
            {{ number_format($tick, 1) }}
        </text>
    @endforeach

    {{-- Wealth cut-offs. Labels are stacked so they do not collide when lines sit close together. --}}
    @foreach ($markers as $marker)
        @php $mx = round($sx($marker['value']), 1); @endphp
        <line x1="{{ $mx }}" y1="{{ $padT }}" x2="{{ $mx }}" y2="{{ $padT + $plotH }}"
              stroke="#000" stroke-width="1" stroke-dasharray="3 3" vector-effect="non-scaling-stroke" />
        <text x="{{ $mx + 5 }}" y="{{ $padT + 9 + $marker['row'] * 12 }}" font-size="9" fill="#737373">
            {{ $marker['label'] }}
        </text>
    @endforeach

    @foreach ($points as $p)
        <circle cx="{{ round($sx($p['rwi']), 1) }}" cy="{{ round($sy($p['outlets']), 1) }}" r="3"
                fill="{{ $p['level'] === 'province' ? 'none' : '#000' }}"
                fill-opacity="{{ $p['level'] === 'province' ? 1 : 0.4 }}"
                stroke="#000" stroke-width="{{ $p['level'] === 'province' ? 1.2 : 0 }}">
            <title>{{ $p['label'] }} — {{ number_format($p['outlets']) }} outlets, wealth {{ number_format($p['rwi'], 2) }}</title>
        </circle>
    @endforeach

    <text x="{{ $padL }}" y="{{ $h - 6 }}" font-size="9" fill="#a3a3a3">← poorer</text>
    <text x="{{ $w - $padR }}" y="{{ $h - 6 }}" text-anchor="end" font-size="9" fill="#a3a3a3">richer →</text>
    <text x="{{ $padL + $plotW / 2 }}" y="{{ $h - 6 }}" text-anchor="middle" font-size="9" fill="#a3a3a3">
        relative wealth index
    </text>
</svg>
