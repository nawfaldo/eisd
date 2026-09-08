@props(['timeline'])

@php
    $w = 1000; $h = 260; $padL = 56; $padR = 10; $padT = 14; $padB = 26;
    $plotW = $w - $padL - $padR;
    $plotH = $h - $padT - $padB;
    $points = $timeline['points'];
    $n = count($points);
    $magnitude = 10 ** max(0, strlen((string) max($timeline['max'], 1)) - 2);
    $ceiling = max($magnitude, (int) ceil($timeline['max'] / $magnitude) * $magnitude);
    $stepX = $n > 1 ? $plotW / ($n - 1) : 0;
    $baseline = $padT + $plotH;

    $xy = [];
    foreach ($points as $i => $point) {
        $xy[] = [
            round($padL + $i * $stepX, 1),
            round($baseline - ($ceiling > 0 ? $point['value'] / $ceiling * $plotH : 0), 1),
        ];
    }

    $line = implode(' ', array_map(fn ($p) => $p[0].','.$p[1], $xy));
    $area = $n > 1
        ? 'M'.$xy[0][0].','.$baseline.' L'.implode(' L', array_map(fn ($p) => $p[0].','.$p[1], $xy)).' L'.end($xy)[0].','.$baseline.' Z'
        : '';
    $labelEvery = (int) ceil(max($n, 1) / 8);
@endphp

<svg viewBox="0 0 {{ $w }} {{ $h }}" class="mt-5 w-full" role="img"
     aria-label="Poisoned per {{ $timeline['unit'] }} over the selected timeframe">
    @foreach ([0, 0.25, 0.5, 0.75, 1] as $tick)
        @php $y = round($baseline - $tick * $plotH, 1); @endphp
        <line x1="{{ $padL }}" y1="{{ $y }}" x2="{{ $w - $padR }}" y2="{{ $y }}"
              stroke="{{ $tick === 0 ? '#000' : '#ededed' }}" stroke-width="1" vector-effect="non-scaling-stroke" />
        <text x="{{ $padL - 8 }}" y="{{ $y + 3 }}" text-anchor="end" font-size="9" fill="#a3a3a3">
            {{ number_format($ceiling * $tick) }}
        </text>
    @endforeach

    @if ($area)
        <path d="{{ $area }}" fill="#000" fill-opacity="0.07" />
    @endif

    <polyline points="{{ $line }}" fill="none" stroke="#000" stroke-width="1.6"
              stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />

    @if ($n === 1)
        <circle cx="{{ $xy[0][0] }}" cy="{{ $xy[0][1] }}" r="2.5" fill="#000" />
    @endif

    @foreach ($points as $i => $point)
        <rect x="{{ round($padL + $i * $stepX - $stepX / 2, 1) }}" y="{{ $padT }}"
              width="{{ round(max($stepX, 1), 1) }}" height="{{ $plotH }}" fill="transparent">
            <title>{{ $point['label'] }} — {{ number_format($point['value']) }} poisoned</title>
        </rect>

        @if ($i % $labelEvery === 0)
            <text x="{{ round($padL + $i * $stepX, 1) }}" y="{{ $h - 8 }}" text-anchor="middle"
                  font-size="9" fill="#a3a3a3">{{ $point['label'] }}</text>
        @endif
    @endforeach
</svg>

