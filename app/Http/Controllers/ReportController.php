<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Report;
use App\Support\RegionMap;
use App\Support\Uploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    /** Rows shown per page. */
    private const PER_PAGE = 50;

    /**
     * Everything the signed-in user has put forward: their open submissions, and
     * the dataset rows they uploaded, which are reports that were merged.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $submitted = Report::query()
            ->where('user_id', $user->id)
            // A merged report is listed as the dataset row it became, so skip it here
            // rather than showing the same thing twice.
            ->whereNot('status', ReportStatus::Merged)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Report $report) => [
                'dataset' => $report->type->label(),
                'title' => $report->title ?: ($report->regency ?: ($report->province ?: 'National')),
                'detail' => $report->detail(),
                'source_url' => $report->source_url,
                'status' => $report->status,
                'at' => $report->created_at,
            ]);

        // Uploaded rows have already been accepted into a dataset, by definition.
        $merged = Uploads::entries($user->id)->map(fn (array $entry) => [
            'dataset' => $entry['dataset'],
            'title' => $entry['title'],
            'detail' => $entry['detail'],
            'source_url' => $entry['source_url'],
            'status' => ReportStatus::Merged,
            'at' => $entry['uploaded_at'],
        ]);

        $reports = $submitted->concat($merged)->sortByDesc('at')->values();

        $type = array_key_exists((string) $request->query('type'), Uploads::DATASETS)
            ? (string) $request->query('type')
            : null;

        $status = ReportStatus::tryFrom((string) $request->query('status'));

        $filtered = $reports
            ->when($type, fn (Collection $all) => $all->where('dataset', Uploads::DATASETS[$type]))
            ->when($status, fn (Collection $all) => $all->where('status', $status))
            ->values();

        $page = max(1, $request->integer('page', 1));

        return view('reports', [
            'reports' => (new LengthAwarePaginator(
                $filtered->forPage($page, self::PER_PAGE)->values(),
                $filtered->count(),
                self::PER_PAGE,
                $page,
                ['path' => $request->url()],
            ))->appends(array_filter(['type' => $type, 'status' => $status?->value])),
            'counts' => collect(ReportStatus::cases())
                ->mapWithKeys(fn (ReportStatus $case) => [
                    $case->value => $reports->where('status', $case)->count(),
                ]),
            'types' => Uploads::DATASETS,
            'statuses' => ReportStatus::cases(),
            'type' => $type,
            'status' => $status,
            'empty' => $reports->isEmpty(),
        ]);
    }

    public function create(): View
    {
        return view('reports.create', [
            'types' => ReportType::cases(),
            'provinces' => $this->provinces(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', array_column(ReportType::cases(), 'value'))],
            // Only a corruption case can be national; the other two happen somewhere.
            'region_id' => ['required_unless:type,corruption', 'nullable', 'string', 'in:'.$this->provinces()->keys()->implode(',')],
            'regency' => ['nullable', 'string', 'max:255'],
            'title' => ['required_if:type,poisoning,corruption', 'nullable', 'string', 'max:500'],
            'occurred_on' => ['required_if:type,sppg', 'nullable', 'date'],
            'victims' => ['nullable', 'integer', 'min:0'],
            'deaths' => ['nullable', 'integer', 'min:0'],
            'outlets' => ['required_if:type,sppg', 'nullable', 'integer', 'min:0'],
            'amount' => ['nullable', 'integer', 'min:0'],
            'agency' => ['nullable', 'string', 'max:255'],
            'source_url' => ['required', 'url', 'max:2000'],
            'note' => ['nullable', 'string', 'max:2000'],
        ], attributes: [
            'region_id' => 'province',
            'title' => 'name',
            'occurred_on' => 'date',
        ]);

        Report::create([
            ...$validated,
            'province' => $this->provinces()[$validated['region_id'] ?? null] ?? null,
            'user_id' => $request->user()->id,
            // Nothing is accepted on submission; review comes later.
            'status' => ReportStatus::Pending,
        ]);

        return redirect()->route('reports')->with('status', 'Report submitted. It is pending review.');
    }

    /**
     * The provinces a report can be filed against, keyed by map region.
     *
     * @return Collection<string, string>
     */
    private function provinces(): Collection
    {
        return collect(RegionMap::load()['regions'])
            ->where('country', 'Indonesia')
            ->sortBy('name')
            ->mapWithKeys(fn (array $region) => [$region['id'] => $region['name']]);
    }
}
