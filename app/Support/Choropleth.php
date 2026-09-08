<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Shades map regions by value.
 *
 * Totals here are heavily skewed — one province can hold a quarter of the national figure —
 * so equal-width bands would collapse most of the country into a single grey. Cuts are taken
 * on quantiles instead: every shade carries a comparable number of provinces.
 */
class Choropleth
{
    /** Grey steps available to regions with a value above zero. */
    public const STEPS = 5;

    /**
     * @param  list<array<string, mixed>>  $regions
     * @param  Collection<string, int>  $byRegion
     * @return array{regions: list<array<string, mixed>>, legend: list<array{shade: int, label: string}>}
     */
    public static function apply(array $regions, Collection $byRegion): array
    {
        $cuts = self::cuts($byRegion);

        foreach ($regions as $i => $region) {
            $value = (int) ($byRegion[$region['id']] ?? 0);

            $regions[$i]['value'] = $value;
            $regions[$i]['shade'] = self::shade($value, $cuts);
        }

        return [
            'regions' => $regions,
            'legend' => self::legend($cuts, (int) $byRegion->max()),
        ];
    }

    /**
     * @param  Collection<string, int>  $byRegion
     * @return list<int>
     */
    private static function cuts(Collection $byRegion): array
    {
        $values = $byRegion->map(fn ($v) => (int) $v)->filter(fn (int $v) => $v > 0)->sort()->values();

        if ($values->isEmpty()) {
            return [];
        }

        $cuts = [];

        for ($i = 1; $i < self::STEPS; $i++) {
            $cuts[] = (int) $values[(int) floor($i * $values->count() / self::STEPS)];
        }

        return array_values(array_unique($cuts));
    }

    /**
     * @param  list<int>  $cuts
     */
    private static function shade(int $value, array $cuts): int
    {
        if ($value <= 0) {
            return 0;
        }

        $shade = 1;

        foreach ($cuts as $cut) {
            if ($value >= $cut) {
                $shade++;
            }
        }

        return $shade;
    }

    /**
     * Swatch labels, so unequal bands are readable rather than misleading.
     *
     * @param  list<int>  $cuts
     * @return list<array{shade: int, label: string}>
     */
    private static function legend(array $cuts, int $max): array
    {
        $legend = [['shade' => 0, 'label' => 'no data']];
        $low = 1;

        foreach ([...$cuts, null] as $i => $cut) {
            $high = $cut === null ? $max : $cut - 1;

            $legend[] = [
                'shade' => $i + 1,
                'label' => $low >= $high ? number_format($low) : number_format($low).'–'.number_format($high),
            ];

            $low = $cut;
        }

        return $legend;
    }
}
