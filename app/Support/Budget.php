<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * What the programme has cost, and where the money lands.
 *
 * There is no per-city spending source, so the national allocation is spread
 * over cities in proportion to their kitchens: an SPPG is the unit the budget
 * actually buys, and each one is funded to serve a comparable number of meals.
 * That makes the wealth split a statement about where kitchens were built,
 * which is the decision the money represents.
 */
class Budget
{
    /**
     * Rupiah scale steps, largest first. The page is in English, so these are
     * English suffixes — they line up one-for-one with triliun, miliar and juta.
     *
     * @var list<array{0: float, 1: string}>
     */
    private const SCALES = [
        [1e12, 'T'],
        [1e9, 'B'],
        [1e6, 'M'],
    ];

    /**
     * @param  Collection<int, object>  $rows  Cities carrying `rwi` and `outlets`.
     * @return array{
     *     total: float,
     *     allocations: list<array{year: int, amount: float, source_url: ?string}>,
     *     bands: list<array{label: string, amount: float, cities: int, outlets: int, share: float, nested: bool}>,
     *     corrupted: float,
     *     corruptedShare: float,
     *     covered: float,
     *     placed: float,
     * }
     */
    public static function summarise(
        Collection $rows,
        float $median,
        float $rich,
        float $veryRich,
        int $allOutlets,
        float $corrupted = 0.0,
    ): array {
        $allocations = collect(config('mbg.allocations', []))
            ->map(fn (array $a, int $year) => [
                'year' => $year,
                'amount' => (float) $a['amount'],
                'source_url' => $a['source_url'] ?? null,
            ])
            ->sortBy('year')
            ->values();

        $total = (float) $allocations->sum('amount');

        // Only cities with a wealth score can be placed on the poor/rich split;
        // the rest of the money is real, it just cannot be attributed.
        $scored = (int) $rows->sum('outlets');
        $covered = $allOutlets > 0 ? $scored / $allOutlets : 0.0;
        $placed = $total * $covered;

        $band = fn (Collection $subset, string $label, bool $nested = false) => [
            'label' => $label,
            'cities' => $subset->count(),
            'outlets' => (int) $subset->sum('outlets'),
            'amount' => $scored > 0 ? $placed * $subset->sum('outlets') / $scored : 0.0,
            'share' => $scored > 0 ? $subset->sum('outlets') / $scored : 0.0,
            'nested' => $nested,
        ];

        $bands = [
            $band($rows->where('rwi', '<', $median), 'Poorer half'),
            $band($rows->where('rwi', '>=', $median), 'Richer half'),
            $band($rows->where('rwi', '>=', $rich), 'of which rich (top 25%)', true),
            $band($rows->where('rwi', '>=', $veryRich), 'of which very rich (top 10%)', true),
        ];

        return [
            'total' => $total,
            'allocations' => $allocations->all(),
            'bands' => $bands,
            'corrupted' => $corrupted,
            'corruptedShare' => $total > 0 ? $corrupted / $total : 0.0,
            'covered' => $covered,
            'placed' => $placed,
        ];
    }

    /**
     * Rupiah at the coarsest scale that still says something, e.g. "Rp 406 T".
     */
    public static function rupiah(float $amount, int $precision = 1): string
    {
        foreach (self::SCALES as [$step, $suffix]) {
            if (abs($amount) >= $step) {
                return 'Rp '.rtrim(rtrim(number_format($amount / $step, $precision), '0'), '.').' '.$suffix;
            }
        }

        return 'Rp '.number_format($amount);
    }
}
