<?php

namespace Tests\Feature;

use App\Models\Sppg;
use App\Models\User;
use App\Support\RegionMap;
use Database\Seeders\SppgSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SppgTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_matches_the_reported_national_total(): void
    {
        $this->seed(SppgSeeder::class);

        $this->assertSame(38, Sppg::count());
        $this->assertSame(29_991, (int) Sppg::sum('outlets'));
        $this->assertSame(0, Sppg::whereNull('source_url')->count());
    }

    public function test_every_province_maps_to_a_region_on_the_map(): void
    {
        $this->seed(SppgSeeder::class);

        $known = array_column(RegionMap::load()['regions'], 'id');

        $this->assertEmpty(array_diff(Sppg::distinct()->pluck('region_id')->all(), $known));
    }

    public function test_the_province_page_shows_its_outlet_count(): void
    {
        $this->seed(SppgSeeder::class);

        $this->actingAs(User::factory()->create())
            ->get('/provinces/indonesia-jawa-barat')
            ->assertOk()
            ->assertSee('Outlets')
            ->assertSee(number_format(6_721));
    }
}
