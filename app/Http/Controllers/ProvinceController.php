<?php

namespace App\Http\Controllers;

use App\Models\Poisoned;
use App\Models\Sppg;
use App\Support\RegionMap;
use Illuminate\View\View;

class ProvinceController extends Controller
{
    public function __invoke(string $region): View
    {
        $map = RegionMap::load();

        $found = collect($map['regions'])->firstWhere('id', $region);

        abort_if($found === null, 404);

        $cases = Poisoned::query()
            ->where('region_id', $region)
            ->orderByRaw('occurred_on is null, occurred_on desc')
            ->get();

        return view('province', [
            'region' => $found,
            'outlets' => (int) Sppg::where('region_id', $region)->sum('outlets'),
            'viewBox' => $this->viewBox($found['d']),
            'cases' => $cases,
            'victims' => (int) $cases->sum('victims'),
            'deaths' => (int) $cases->sum('deaths'),
            'provinces' => $cases->pluck('province')->unique()->sort()->values(),
        ]);
    }

    /**
     * Tight viewBox around a single region's path, so the outline fills its frame.
     */
    private function viewBox(string $d): string
    {
        preg_match_all('/(-?[\d.]+),(-?[\d.]+)/', $d, $m);

        $xs = array_map('floatval', $m[1]);
        $ys = array_map('floatval', $m[2]);

        $pad = 4;
        $x = min($xs) - $pad;
        $y = min($ys) - $pad;

        return sprintf('%s %s %s %s', $x, $y, max($xs) - $x + $pad, max($ys) - $y + $pad);
    }
}
