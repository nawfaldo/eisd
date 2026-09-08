<?php

namespace Tests\Feature;

use App\Models\Poisoned;
use App\Models\User;
use Database\Seeders\PoisonedSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProvincePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_province_page_is_open_to_anyone(): void
    {
        $this->get('/provinces/indonesia-jawa-tengah')->assertOk();
    }

    public function test_it_lists_every_case_for_the_region(): void
    {
        $this->seed(PoisonedSeeder::class);

        $expected = Poisoned::where('region_id', 'indonesia-jawa-tengah')->get();

        $response = $this->actingAs(User::factory()->create())
            ->get('/provinces/indonesia-jawa-tengah');

        $response->assertOk()
            ->assertSee('Jawa Tengah')
            ->assertSee(number_format((int) $expected->sum('victims')));

        // One table row per case.
        $this->assertSame(
            $expected->count(),
            substr_count($response->getContent(), '<tr class="border-b border-neutral-200'),
        );
    }

    public function test_it_shows_regency_and_a_source_link_per_case(): void
    {
        $this->seed(PoisonedSeeder::class);

        $case = Poisoned::whereNotNull('source_url')->whereNotNull('regency')->first();

        $this->actingAs(User::factory()->create())
            ->get('/provinces/'.$case->region_id)
            ->assertOk()
            ->assertSee($case->regency)
            ->assertSee($case->source_url, escape: false);
    }

    public function test_unknown_regions_404(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/provinces/not-a-region')
            ->assertNotFound();
    }

    public function test_the_home_page_links_the_map_and_the_leaderboard(): void
    {
        $this->seed(PoisonedSeeder::class);

        $response = $this->actingAs(User::factory()->create())->get('/');

        $response->assertOk()
            ->assertSee(route('province', 'indonesia-jawa-tengah'), escape: false)
            ->assertSee('region-link', escape: false);

        // Regions with no cases are not links.
        $response->assertDontSee(route('province', 'papua-new-guinea-morobe'), escape: false);
    }
}
