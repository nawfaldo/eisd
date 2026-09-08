<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\RegionMap;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ChangelogController extends Controller
{
    /** Rows shown per page; the four datasets together run to a few hundred entries. */
    private const PER_PAGE = 50;

    public function __invoke(Request $request): View
    {
        $names = User::query()->pluck('name', 'id');
        $regions = collect(RegionMap::load()['regions'])->pluck('id')->flip();

        $entries = Uploads::entries()->map(fn (array $entry) => [
            ...$entry,
            // Null uploader means the row predates attribution, not that nobody put it there.
            'uploader' => $names[$entry['uploaded_by']] ?? null,
            'link' => $entry['region_id'] && $regions->has($entry['region_id'])
                ? route('province', $entry['region_id'])
                : null,
        ]);

        // The date filter offers the days that actually have uploads, so it can never
        // be set to an empty result.
        $dates = $entries->map(fn (array $entry) => $entry['uploaded_at']->toDateString())
            ->unique()->sortDesc()->values();

        $dataset = array_key_exists((string) $request->query('dataset'), Uploads::DATASETS)
            ? (string) $request->query('dataset')
            : null;

        $date = $dates->contains($request->query('date')) ? (string) $request->query('date') : null;

        $filtered = $entries
            ->when($dataset, fn (Collection $all) => $all->where('dataset', Uploads::DATASETS[$dataset]))
            ->when($date, fn (Collection $all) => $all->filter(
                fn (array $entry) => $entry['uploaded_at']->toDateString() === $date,
            ))
            ->values();

        $page = max(1, $request->integer('page', 1));

        return view('changelog', [
            'entries' => (new LengthAwarePaginator(
                $filtered->forPage($page, self::PER_PAGE)->values(),
                $filtered->count(),
                self::PER_PAGE,
                $page,
                ['path' => $request->url()],
            ))->appends(array_filter(['dataset' => $dataset, 'date' => $date])),
            'datasets' => Uploads::DATASETS,
            'dates' => $dates,
            'dataset' => $dataset,
            'date' => $date,
        ]);
    }
}
