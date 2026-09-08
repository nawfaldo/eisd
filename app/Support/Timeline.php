<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Victims bucketed over time, with empty periods filled in so the x axis stays continuous.
 */
class Timeline
{
    public const RANGES = [
        'mtd' => 'MTD',
        'qtd' => 'QTD',
        'ytd' => 'YTD',
        '1y' => '1Y',
        '3y' => '3Y',
        'all' => 'All',
    ];

    /** Bucket size per range, chosen so each chart lands in the 20–120 point band. */
    private const UNITS = [
        'mtd' => 'day',
        'qtd' => 'day',
        'ytd' => 'week',
        '1y' => 'week',
        '3y' => 'month',
        'all' => 'month',
    ];

    /**
     * @param  Collection<int, object>  $cases
     * @return array{points: list<array{label: string, value: int, period: string}>, max: int, unit: string, total: int}
     */
    public static function build(Collection $cases, string $range): array
    {
        $unit = self::UNITS[$range] ?? 'month';
        $end = CarbonImmutable::now();

        $start = match ($range) {
            'mtd' => $end->startOfMonth(),
            'qtd' => $end->startOfQuarter(),
            'ytd' => $end->startOfYear(),
            '1y' => $end->subYear(),
            '3y' => $end->subYears(3),
            default => CarbonImmutable::parse($cases->min('occurred_on') ?? $end),
        };

        $start = self::floor($start, $unit);
        $totals = [];

        foreach ($cases as $case) {
            $at = CarbonImmutable::parse($case->occurred_on);

            if ($at->lt($start) || $at->gt($end)) {
                continue;
            }

            $key = self::floor($at, $unit)->format('Y-m-d');
            $totals[$key] = ($totals[$key] ?? 0) + (int) $case->victims;
        }

        $points = [];

        for ($at = $start; $at->lte($end); $at = self::next($at, $unit)) {
            $key = $at->format('Y-m-d');

            $points[] = [
                'period' => $key,
                'label' => $unit === 'month' ? $at->format('M Y') : $at->format('j M'),
                'value' => $totals[$key] ?? 0,
            ];
        }

        $values = array_column($points, 'value');

        return [
            'points' => $points,
            'max' => max($values ?: [0]),
            'total' => array_sum($values),
            'unit' => $unit,
        ];
    }

    private static function floor(CarbonImmutable $at, string $unit): CarbonImmutable
    {
        return match ($unit) {
            'day' => $at->startOfDay(),
            'week' => $at->startOfWeek(),
            default => $at->startOfMonth(),
        };
    }

    private static function next(CarbonImmutable $at, string $unit): CarbonImmutable
    {
        return match ($unit) {
            'day' => $at->addDay(),
            'week' => $at->addWeek(),
            default => $at->addMonth(),
        };
    }
}
