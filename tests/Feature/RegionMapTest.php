<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\RegionMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegionMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_region_has_an_id_name_country_and_path(): void
    {
        $map = RegionMap::load();

        $this->assertGreaterThan(0, $map['width']);
        $this->assertGreaterThan(0, $map['height']);
        $this->assertNotEmpty($map['regions']);

        foreach ($map['regions'] as $region) {
            $this->assertNotEmpty($region['id']);
            $this->assertNotEmpty($region['name']);
            $this->assertNotEmpty($region['country']);
            $this->assertStringStartsWith('M', $region['d']);
        }
    }

    public function test_region_ids_are_unique(): void
    {
        $ids = array_column(RegionMap::load()['regions'], 'id');

        $this->assertSame($ids, array_unique($ids));
    }

    public function test_it_covers_indonesia_papua_new_guinea_and_malaysian_borneo(): void
    {
        $countries = array_count_values(array_column(RegionMap::load()['regions'], 'country'));

        $this->assertArrayHasKey('Indonesia', $countries);
        $this->assertArrayHasKey('Papua New Guinea', $countries);
        $this->assertSame(2, $countries['Malaysia']);
    }

    public function test_malaysia_is_limited_to_sabah_and_sarawak(): void
    {
        $malaysian = array_values(array_map(
            fn (array $r) => $r['name'],
            array_filter(RegionMap::load()['regions'], fn (array $r) => $r['country'] === 'Malaysia'),
        ));

        sort($malaysian);

        $this->assertSame(['Sabah', 'Sarawak'], $malaysian);
    }

    public function test_the_home_page_renders_a_path_per_region(): void
    {
        $map = RegionMap::load();

        $response = $this->actingAs(User::factory()->create())->get('/');

        $response->assertOk()
            ->assertSee('data-region-map', escape: false)
            ->assertDontSee('aria-pressed', escape: false)
            ->assertDontSee('tabindex', escape: false);

        // One choropleth: poisoning victims by province.
        $this->assertSame(
            count($map['regions']),
            substr_count($response->getContent(), 'class="region region-'),
        );
        $this->assertSame(1, substr_count($response->getContent(), 'data-region-map'));
    }
}
