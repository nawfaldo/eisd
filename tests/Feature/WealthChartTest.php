<?php

namespace Tests\Feature;

use App\Models\SppgCity;
use App\Models\User;
use Database\Seeders\SppgCitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WealthChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_wealth_is_attached_to_almost_every_city(): void
    {
        $this->seed(SppgCitySeeder::class);

        $this->assertSame(440, SppgCity::whereNotNull('rwi')->count());
        $this->assertSame(7, SppgCity::whereNull('rwi')->count());

        // RWI is a standardised index; sanity-check the range rather than a magic number.
        $this->assertGreaterThan(-3, (float) SppgCity::min('rwi'));
        $this->assertLessThan(3, (float) SppgCity::max('rwi'));
    }

    public function test_the_wealth_ranking_is_face_valid(): void
    {
        $this->seed(SppgCitySeeder::class);

        $richest = SppgCity::whereNotNull('rwi')->orderByDesc('rwi')->first();
        $poorest = SppgCity::whereNotNull('rwi')->orderBy('rwi')->first();

        $this->assertStringContainsString('Jakarta', $richest->city);
        $this->assertGreaterThan($poorest->rwi, $richest->rwi);
    }

    public function test_the_chart_plots_one_point_per_city(): void
    {
        $this->seed(SppgCitySeeder::class);

        $content = $this->actingAs(User::factory()->create())->get('/')
            ->assertOk()
            ->assertSee('Outlets vs city wealth')
            ->assertSee('Relative Wealth Index')
            ->getContent();

        // Bounded to the wealth chart: the poisoning timeline below it also draws circles.
        $start = strpos($content, 'Outlets vs city wealth');
        $chart = substr($content, $start, strpos($content, 'What counts as rich or poor') - $start);

        $this->assertSame(
            SppgCity::whereNotNull('rwi')->distinct()->count('city'),
            substr_count($chart, '<circle'),
        );
    }

    public function test_it_explains_what_rich_median_and_poor_mean(): void
    {
        $this->seed(SppgCitySeeder::class);

        $rows = SppgCity::whereNotNull('rwi')->orderBy('rwi')->get();
        $median = (float) $rows[intdiv($rows->count(), 2)]->rwi;

        // The whole point of the chart: outlets are not evenly spread by wealth.
        $this->assertGreaterThan(
            (int) $rows->where('rwi', '<', $median)->sum('outlets'),
            (int) $rows->where('rwi', '>=', $median)->sum('outlets'),
        );

        $this->actingAs(User::factory()->create())->get('/')
            ->assertOk()
            ->assertSee('What counts as rich or poor')
            ->assertSee('<details class="mt-4 max-w-2xl', escape: false)
            ->assertSee(number_format($median, 2))
            ->assertSee(number_format((float) $rows->first()->rwi, 2))
            ->assertSee(number_format((float) $rows->last()->rwi, 2))
            ->assertSee($rows->where('rwi', '<', $median)->count().' cities fall to its left')
            ->assertSee('rich (top 25%)')
            ->assertSee('very rich (top 10%)')
            ->assertDontSee('Each point is one city');
    }

    public function test_the_chart_is_skipped_when_there_is_no_data(): void
    {
        $this->actingAs(User::factory()->create())->get('/')
            ->assertOk()
            ->assertDontSee('Outlets vs city wealth');
    }
}
