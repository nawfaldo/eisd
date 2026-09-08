<?php

namespace Tests\Feature;

use App\Models\SppgCity;
use App\Models\User;
use App\Support\RegionMap;
use Database\Seeders\SppgCitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SppgCityTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_matches_the_scraped_directory_total(): void
    {
        $this->seed(SppgCitySeeder::class);

        $this->assertSame(447, SppgCity::count());
        $this->assertSame(24_467, (int) SppgCity::sum('outlets'));
        $this->assertSame(0, SppgCity::whereNull('source_url')->count());
    }

    public function test_mappable_rows_point_at_a_real_city_region(): void
    {
        $this->seed(SppgCitySeeder::class);

        $known = array_column(RegionMap::cities()['regions'], 'id');
        $used = SppgCity::whereNotNull('region_id')->pluck('region_id')->all();

        $this->assertSame(440, count($used));
        $this->assertEmpty(array_diff($used, $known));
    }

    public function test_each_region_is_claimed_by_at_most_one_city(): void
    {
        $this->seed(SppgCitySeeder::class);

        $used = SppgCity::whereNotNull('region_id')->pluck('region_id')->all();

        $this->assertSame(count($used), count(array_unique($used)));
    }

    public function test_unmappable_rows_still_carry_their_count(): void
    {
        $this->seed(SppgCitySeeder::class);

        $unmapped = SppgCity::whereNull('region_id')->get();

        $this->assertCount(7, $unmapped);
        $this->assertSame(345, (int) $unmapped->sum('outlets'));
    }

    public function test_the_city_map_uses_the_same_frame_as_the_province_map(): void
    {
        $provinces = RegionMap::load();
        $cities = RegionMap::cities();

        $this->assertSame($provinces['width'], $cities['width']);
        $this->assertSame($provinces['height'], $cities['height']);
        $this->assertGreaterThan(450, count($cities['regions']));
    }

    public function test_papua_falls_back_to_province_level(): void
    {
        $this->seed(SppgCitySeeder::class);

        $papua = SppgCity::where('level', 'province')->get();

        // Six Papua provinces, carrying the official BGN figures.
        $this->assertCount(6, $papua);
        $this->assertSame(275, (int) $papua->sum('outlets'));
        $this->assertSame(0, $papua->whereNull('region_id')->count());

        // No city-level row survives for Papua, so nothing is double counted.
        $this->assertSame(0, SppgCity::where('level', 'city')
            ->where('province', 'like', 'Papua%')->count());

        // Those province outlines are on the city map, their regencies are not.
        $ids = array_column(RegionMap::cities()['regions'], 'id');
        $this->assertContains('indonesia-papua-tengah', $ids);
        $this->assertNotContains('papua-tengah-nabire', $ids);
    }

    public function test_every_mapped_city_has_a_population(): void
    {
        $this->seed(SppgCitySeeder::class);

        $mapped = SppgCity::whereNotNull('region_id')->get();

        $this->assertSame(0, $mapped->whereNull('population')->count());
        $this->assertGreaterThan(200_000_000, (int) $mapped->sum('population'));
    }

    public function test_the_home_page_shows_the_outlet_total_without_a_map_or_leaderboard(): void
    {
        $this->seed(SppgCitySeeder::class);

        $this->actingAs(User::factory()->create())->get('/')
            ->assertOk()
            ->assertSee(number_format(24_467))
            ->assertDontSee('Outlets by city')
            ->assertDontSee('outlets per 100k people');
    }
}
