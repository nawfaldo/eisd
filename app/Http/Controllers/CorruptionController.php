<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Corruption;
use App\Support\RegionMap;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CorruptionController extends Controller
{
    public function __invoke(Request $request): View
    {
        // Filtering by institution goes through the pivot, so a case handled by
        // several bodies is found under each of them.
        $agency = Agency::firstWhere('name', $request->query('agency'));

        // Biggest published loss first; cases with no figure yet sit at the end
        // rather than being ranked as if they were worth nothing.
        $cases = Corruption::query()
            ->with('agencies')
            ->when($agency, fn ($q) => $q->whereRelation('agencies', 'agencies.id', $agency->id))
            ->orderByRaw('amount is null, amount desc')
            ->orderByDesc('reported_on')
            ->get();

        $regions = collect(RegionMap::load()['regions'])->pluck('id')->flip();

        $cases->each(fn ($case) => $case->link = $case->region_id && $regions->has($case->region_id)
            ? route('province', $case->region_id)
            : null);

        return view('corruption', [
            'cases' => $cases,
            'stolen' => (int) $cases->sum('amount'),
            'unpriced' => $cases->whereNull('amount')->count(),
            'agency' => $agency,
            'agencies' => Agency::withCount('cases')
                // has(), not having(): withCount is a subquery, which SQLite will
                // not let a HAVING clause reach.
                ->has('cases')
                ->orderByDesc('cases_count')
                ->orderBy('name')
                ->get()
                ->each(function ($a) {
                    $a->label = $a->name;
                    $a->value = $a->cases_count;
                    $a->link = route('corruption', ['agency' => $a->name]);
                }),
        ]);
    }
}
