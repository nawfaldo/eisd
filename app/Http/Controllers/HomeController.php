<?php

namespace App\Http\Controllers;

use App\Models\Corruption;
use App\Models\Outcome;
use App\Models\Poisoned;
use App\Models\SppgCity;
use App\Support\Budget;
use App\Support\Choropleth;
use App\Support\RegionMap;
use App\Support\Timeline;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $map = RegionMap::load();

        $victimsByRegion = Poisoned::query()
            ->selectRaw('region_id, sum(victims) as total')
            ->groupBy('region_id')
            ->pluck('total', 'region_id');

        $casesByRegion = Poisoned::query()
            ->selectRaw('region_id, count(*) as total')
            ->groupBy('region_id')
            ->pluck('total', 'region_id');

        $poisoned = Choropleth::apply($this->linked($map['regions'], $casesByRegion->keys()->flip()), $victimsByRegion);

        $wealth = $this->wealth();

        $coverage = $this->coverage();

        // Every timeframe is rendered up front so switching is a CSS toggle, not a page load.
        $dated = Poisoned::whereNotNull('occurred_on')->get(['occurred_on', 'victims']);

        return view('home', [
            'width' => $map['width'],
            'height' => $map['height'],
            'poisoned' => $poisoned,
            'poisonedBoard' => Poisoned::query()
                ->selectRaw('province as label, region_id, sum(victims) as value')
                ->groupBy('province', 'region_id')->orderByDesc('value')->get()
                ->each(fn ($r) => $r->link = route('province', $r->region_id)),
            'timelines' => collect(Timeline::RANGES)->map(fn (string $l, string $k) => Timeline::build($dated, $k)),
            'ranges' => Timeline::RANGES,
            'defaultRange' => 'all',
            'totalVictims' => (int) Poisoned::sum('victims'),
            'totalCases' => Poisoned::count(),
            'totalOutlets' => (int) SppgCity::sum('outlets'),
            'coverage' => $coverage,
            'stunting' => Outcome::where('indicator', 'stunting')->orderBy('period')->get(),
            'underserved' => $coverage['rows']->take(30),
            'wealth' => $wealth,
            'budget' => $this->budget($wealth),
        ]);
    }

    /**
     * How thinly the programme covers each kabupaten/kota: kitchens per 100,000
     * residents, thinnest first. This is the equity question the map cannot show —
     * a big city with many kitchens can still serve fewer of its people than a
     * small one — so coverage is measured per resident, not per city.
     *
     * @return array{rows: Collection<int, object>, median: float, national: float}
     */
    private function coverage(): array
    {
        $rows = SppgCity::query()
            ->where('level', 'city')
            ->where('population', '>', 0)
            ->selectRaw('city as label, province, population, sum(outlets) as outlets')
            ->groupBy('city', 'province', 'population')
            ->get()
            ->each(fn ($r) => $r->value = round($r->outlets / $r->population * 100_000, 1))
            ->sortBy('value')
            ->values();

        if ($rows->isEmpty()) {
            return ['rows' => $rows, 'median' => 0.0, 'national' => 0.0];
        }

        $population = (int) $rows->sum('population');

        return [
            'rows' => $rows,
            'median' => (float) $rows[(int) floor($rows->count() / 2)]->value,
            'national' => $population > 0
                ? round($rows->sum('outlets') / $population * 100_000, 1)
                : 0.0,
        ];
    }

    /**
     * One row per city that has a wealth score, poorest first.
     *
     * @return Collection<int, object>
     */
    private function wealthRows(): Collection
    {
        return SppgCity::query()
            ->whereNotNull('rwi')
            ->selectRaw('city as label, level, rwi, sum(outlets) as outlets')
            ->groupBy('city', 'level', 'rwi')
            ->orderBy('rwi')
            ->get();
    }

    /**
     * What the programme has cost, split over the same wealth cut-offs the
     * chart above draws, so the two sections cannot disagree.
     *
     * @param  array<string, mixed>  $wealth
     * @return array<string, mixed>
     */
    private function budget(array $wealth): array
    {
        return Budget::summarise(
            $this->wealthRows(),
            (float) $wealth['median'],
            (float) $wealth['rich'],
            (float) $wealth['veryRich'],
            (int) SppgCity::sum('outlets'),
            (float) Corruption::sum('amount'),
        );
    }

    /**
     * Outlets against relative wealth, one point per city, plus the split that
     * answers whether the poorer half of the country is served.
     *
     * @return array{points: list<array<string, mixed>>, median: float, poorCities: int, poorOutlets: int, richCities: int, richOutlets: int}
     */
    private function wealth(): array
    {
        $rows = $this->wealthRows();

        if ($rows->isEmpty()) {
            return [
                'points' => [], 'median' => 0.0, 'rich' => 0.0, 'veryRich' => 0.0, 'markers' => [],
                'richCount' => 0, 'veryRichCount' => 0, 'min' => 0.0, 'max' => 0.0,
                'poorCities' => 0, 'poorOutlets' => 0, 'richCities' => 0, 'richOutlets' => 0,
            ];
        }

        $sorted = $rows->pluck('rwi')->map(fn ($v) => (float) $v)->values();
        $at = fn (float $q) => (float) $sorted[(int) floor($q * $sorted->count())];

        $median = $at(0.5);
        $rich = $at(0.75);
        $veryRich = $at(0.90);

        $poorHalf = $rows->where('rwi', '<', $median);
        $richHalf = $rows->where('rwi', '>=', $median);

        return [
            'points' => $rows->map(fn ($r) => [
                'label' => $r->level === 'province' ? $r->label.' (prov.)' : $r->label,
                'level' => $r->level,
                'rwi' => (float) $r->rwi,
                'outlets' => (int) $r->outlets,
            ])->all(),
            'median' => $median,
            'rich' => $rich,
            'veryRich' => $veryRich,
            'markers' => [
                ['value' => $median, 'label' => 'median', 'row' => 0],
                ['value' => $rich, 'label' => 'rich (top 25%)', 'row' => 1],
                ['value' => $veryRich, 'label' => 'very rich (top 10%)', 'row' => 2],
            ],
            'richCount' => $rows->where('rwi', '>=', $rich)->count(),
            'veryRichCount' => $rows->where('rwi', '>=', $veryRich)->count(),
            'min' => (float) $rows->first()->rwi,
            'max' => (float) $rows->last()->rwi,
            'poorCities' => $poorHalf->count(),
            'poorOutlets' => (int) $poorHalf->sum('outlets'),
            'richCities' => $richHalf->count(),
            'richOutlets' => (int) $richHalf->sum('outlets'),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $regions
     * @return list<array<string, mixed>>
     */
    private function linked(array $regions, Collection $linkable): array
    {
        foreach ($regions as $i => $region) {
            $regions[$i]['link'] = $linkable->has($region['id'])
                ? route('province', $region['id'])
                : null;
        }

        return $regions;
    }

    /**
     * @return Collection<int, object>
     */
    private function board(Builder $query): Collection
    {
        return $query->groupBy('province', 'region_id')->orderByDesc('value')->get();
    }
}
