<?php

namespace Tests\Feature;

use App\Models\Corruption;
use App\Models\SppgCity;
use App\Models\User;
use App\Support\Budget;
use App\Support\RegionMap;
use Database\Seeders\CorruptionSeeder;
use Database\Seeders\SppgCitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_total_is_the_sum_of_the_allocations(): void
    {
        $this->seed(SppgCitySeeder::class);

        $budget = $this->summarise();

        $this->assertSame(
            (float) collect(config('mbg.allocations'))->sum('amount'),
            $budget['total'],
        );
    }

    public function test_the_wealth_bands_split_the_attributable_money(): void
    {
        $this->seed(SppgCitySeeder::class);

        $budget = $this->summarise();
        [$poor, $rich] = [$budget['bands'][0], $budget['bands'][1]];

        // The halves are exhaustive, so they must add back up to the placed total.
        $this->assertEqualsWithDelta($budget['placed'], $poor['amount'] + $rich['amount'], 1);
        $this->assertEqualsWithDelta(1.0, $poor['share'] + $rich['share'], 0.0001);

        // Cities without a wealth score hold real outlets, so not everything is placed.
        $this->assertLessThan($budget['total'], $budget['placed']);
        $this->assertGreaterThan(0.9, $budget['covered']);
    }

    public function test_rich_and_very_rich_are_nested_inside_the_richer_half(): void
    {
        $this->seed(SppgCitySeeder::class);

        [, $richerHalf, $rich, $veryRich] = $this->summarise()['bands'];

        $this->assertTrue($rich['nested'] && $veryRich['nested']);
        $this->assertLessThan($richerHalf['amount'], $rich['amount']);
        $this->assertLessThan($rich['amount'], $veryRich['amount']);
    }

    public function test_corruption_is_the_sum_of_the_recorded_cases(): void
    {
        $this->seed(SppgCitySeeder::class);

        Corruption::create(['title' => 'A', 'amount' => 1_000_000_000]);
        Corruption::create(['title' => 'B', 'amount' => 500_000_000]);
        // No published figure yet, so it must not drag the total down or up.
        Corruption::create(['title' => 'C', 'amount' => null]);

        $budget = $this->summarise((float) Corruption::sum('amount'));

        $this->assertSame(1_500_000_000.0, $budget['corrupted']);
        $this->assertEqualsWithDelta($budget['corrupted'] / $budget['total'], $budget['corruptedShare'], 1e-9);
    }

    public function test_rupiah_uses_a_short_scale(): void
    {
        $this->assertSame('Rp 406 T', Budget::rupiah(406_000_000_000_000));
        $this->assertSame('Rp 1.5 B', Budget::rupiah(1_500_000_000));
        $this->assertSame('Rp 2 M', Budget::rupiah(2_000_000));
        $this->assertSame('Rp 900', Budget::rupiah(900));
    }

    public function test_the_home_page_shows_what_the_programme_costs(): void
    {
        $this->seed(SppgCitySeeder::class);

        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('Allocated')
            ->assertSee('Poorer half')
            ->assertSee('of which very rich (top 10%)')
            ->assertSee('Corrupted')
            ->assertSee(route('corruption'))
            ->assertSee(Budget::rupiah((float) collect(config('mbg.allocations'))->sum('amount')));
    }

    public function test_the_corruption_page_lists_the_cases(): void
    {
        Corruption::create([
            'title' => 'Procurement mark-up', 'amount' => 2_000_000_000,
            'province' => 'Jawa Barat', 'region_id' => 'indonesia-jawa-barat',
            'regency' => 'Kabupaten Bandung', 'agency' => 'KPK', 'status' => 'on trial',
            'reported_on' => '2026-03-04', 'source_url' => 'https://example.test/a',
        ]);
        Corruption::create(['title' => 'Open case', 'amount' => null]);

        $this->actingAs(User::factory()->create())
            ->get(route('corruption'))
            ->assertOk()
            ->assertSee('Procurement mark-up')
            ->assertSee('Rp 2 B')
            ->assertSee('Kabupaten Bandung')
            ->assertSee(route('province', 'indonesia-jawa-barat'))
            ->assertSee('on trial')
            // A case with no published figure is still listed, just not priced.
            ->assertSee('Open case')
            ->assertSee('no rupiah figure has been published', escape: false);
    }

    public function test_every_seeded_case_is_sourced(): void
    {
        $this->seed(CorruptionSeeder::class);

        $this->assertGreaterThan(0, Corruption::count());
        $this->assertSame(0, Corruption::whereNull('source_url')->count());

        // A case may have no published figure, but it must never carry a zero one:
        // that would read as "nothing was taken" rather than "nobody has said".
        $this->assertSame(0, Corruption::where('amount', 0)->count());

        // Region ids have to resolve, or the province link 404s.
        $regions = collect(RegionMap::load()['regions'])->pluck('id');

        Corruption::whereNotNull('region_id')->pluck('region_id')
            ->each(fn ($id) => $this->assertContains($id, $regions));
    }

    public function test_the_corruption_page_is_empty_without_cases(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('corruption'))
            ->assertOk()
            ->assertSee('No reported cases.');
    }

    public function test_the_corruption_page_is_open_to_anyone(): void
    {
        $this->get(route('corruption'))->assertOk();
    }

    /**
     * @return array<string, mixed>
     */
    private function summarise(float $corrupted = 0.0): array
    {
        $rows = SppgCity::query()
            ->whereNotNull('rwi')
            ->selectRaw('city as label, level, rwi, sum(outlets) as outlets')
            ->groupBy('city', 'level', 'rwi')
            ->orderBy('rwi')
            ->get();

        $sorted = $rows->pluck('rwi')->map(fn ($v) => (float) $v)->values();
        $at = fn (float $q) => (float) $sorted[(int) floor($q * $sorted->count())];

        return Budget::summarise(
            $rows,
            $at(0.5),
            $at(0.75),
            $at(0.90),
            (int) SppgCity::sum('outlets'),
            $corrupted,
        );
    }
}
