<?php

namespace Tests\Feature;

use App\Models\Poisoned;
use App\Models\User;
use App\Support\RegionMap;
use Database\Seeders\PoisonedSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoisonedTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_loads_cases_with_victims_and_sources(): void
    {
        $this->seed(PoisonedSeeder::class);

        $this->assertGreaterThan(400, Poisoned::count());
        $this->assertGreaterThan(30_000, (int) Poisoned::sum('victims'));

        // Every case must be attributable: a province, a map region and a news link.
        $this->assertSame(0, Poisoned::whereNull('province')->count());
        $this->assertSame(0, Poisoned::whereNull('region_id')->count());
        $this->assertLessThan(10, Poisoned::whereNull('source_url')->count());
    }

    public function test_every_case_region_exists_on_the_map(): void
    {
        $this->seed(PoisonedSeeder::class);

        $known = array_column(RegionMap::load()['regions'], 'id');
        $used = Poisoned::query()->distinct()->pluck('region_id')->all();

        $this->assertNotEmpty($used);
        $this->assertEmpty(array_diff($used, $known));
    }

    public function test_source_urls_are_links(): void
    {
        $this->seed(PoisonedSeeder::class);

        foreach (Poisoned::whereNotNull('source_url')->pluck('source_url') as $url) {
            $this->assertStringStartsWith('http', $url);
        }
    }

    public function test_the_home_page_shades_the_map_and_ranks_provinces(): void
    {
        $this->seed(PoisonedSeeder::class);

        $response = $this->actingAs(User::factory()->create())->get('/');

        $response->assertOk()
            ->assertSee('region-5', escape: false)   // the worst province is filled black
            ->assertSee('region-0', escape: false)   // regions with no cases stay white
            ->assertSee('Poisoned by province')
            ->assertSee('Jawa Tengah');

        // Leaderboard is ordered by victims, descending. Scope to the list itself:
        // province names also appear in the map's <title> tags earlier in the page.
        preg_match('/<ol\b.*?<\/ol>/s', $response->getContent(), $m);
        $list = $m[0];

        $ranked = Poisoned::query()
            ->selectRaw('province, sum(victims) as victims')
            ->groupBy('province')->orderByDesc('victims')->pluck('province')->all();

        $positions = array_map(
            fn (string $p) => strpos($list, '>'.$p.'</span>'),
            array_slice($ranked, 0, 8),
        );

        $this->assertNotContains(false, $positions);

        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions);
    }

    public function test_the_leaderboard_is_collapsed_by_default(): void
    {
        $this->seed(PoisonedSeeder::class);

        $content = $this->actingAs(User::factory()->create())->get('/')->getContent();

        $this->assertStringContainsString('<details class="mt-14">', $content);
        $this->assertStringNotContainsString('<details open', $content);
        $this->assertStringContainsString('Poisoned by province', $content);
    }

    public function test_the_map_is_blank_when_there_are_no_cases(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/');

        $response->assertOk()->assertDontSee('region-5', escape: false);
    }
}
