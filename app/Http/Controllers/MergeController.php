<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MergeController extends Controller
{
    /** Reports shown per page. */
    private const PER_PAGE = 25;

    /**
     * Every report still waiting on a decision, oldest first: the one that has
     * been queued longest is the one to look at next.
     */
    public function index(Request $request): View
    {
        $pending = Report::query()->where('status', ReportStatus::Pending);

        // Both filters only offer what is actually in the queue, so neither can
        // be set to an empty result.
        $types = collect(ReportType::cases())
            ->filter(fn (ReportType $case) => (clone $pending)->where('type', $case)->exists())
            ->values();

        $reporters = User::query()
            ->whereIn('id', (clone $pending)->select('user_id'))
            ->orderBy('name')
            ->pluck('name', 'id');

        $type = $types->firstWhere('value', $request->query('type'));
        $reporter = $reporters->has($request->integer('reporter')) ? $request->integer('reporter') : null;

        return view('merge', [
            'reports' => $pending
                ->with('author')
                ->when($type, fn (Builder $query) => $query->where('type', $type))
                ->when($reporter, fn (Builder $query) => $query->where('user_id', $reporter))
                ->orderBy('created_at')
                ->paginate(self::PER_PAGE)
                ->appends(array_filter(['type' => $type?->value, 'reporter' => $reporter])),
            'types' => $types,
            'reporters' => $reporters,
            'type' => $type,
            'reporter' => $reporter,
        ]);
    }

    public function merge(Report $report): RedirectResponse
    {
        abort_unless($report->isPending(), 409);

        $report->merge();

        return back()->with('status', 'Merged into the '.$report->type->label().' data.');
    }

    public function decline(Report $report): RedirectResponse
    {
        abort_unless($report->isPending(), 409);

        $report->decline();

        return back()->with('status', 'Report declined.');
    }
}
