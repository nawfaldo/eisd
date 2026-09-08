<?php

namespace Tests\Feature;

use App\Models\Corruption;
use App\Models\Poisoned;
use App\Models\Sppg;
use App\Models\SppgCity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangelogTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_changelog_page_lists_every_record_with_its_uploader(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'admin']);

        Poisoned::insert([
            ['uploaded_by' => $admin->id, 'province' => 'Aceh', 'region_id' => 'indonesia-aceh', 'regency' => 'Aceh Utara', 'place' => 'SDN 6 Matangkuli', 'victims' => 3, 'deaths' => 0, 'occurred_on' => '2025-09-29', 'created_at' => now(), 'updated_at' => now()],
            ['uploaded_by' => $admin->id, 'province' => 'Bali', 'region_id' => 'indonesia-bali', 'regency' => null, 'place' => 'SDN 1 Denpasar', 'victims' => 12, 'deaths' => 1, 'occurred_on' => '2026-01-15', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Corruption::insert([
            ['uploaded_by' => $admin->id, 'title' => 'Kitchen procurement mark-ups', 'agency' => 'KPK', 'status' => 'on trial', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Sppg::insert([
            ['uploaded_by' => $admin->id, 'province' => 'Aceh', 'region_id' => 'indonesia-aceh', 'outlets' => 737, 'as_of' => '2026-06-08', 'created_at' => now(), 'updated_at' => now()],
        ]);

        SppgCity::insert([
            ['uploaded_by' => $admin->id, 'city' => 'Aceh Barat', 'province' => 'Aceh', 'outlets' => 16, 'population' => 209498, 'as_of' => '2026-06-09', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Each record gets its own line, described well enough to tell it from its neighbours.
        $this->actingAs($admin)
            ->get('/changelog')
            ->assertOk()
            ->assertSee('SDN 6 Matangkuli')
            ->assertSee('Aceh Utara · Aceh · 3 poisoned · 29 Sep 2025', escape: false)
            ->assertSee('SDN 1 Denpasar')
            ->assertSee('12 poisoned · 1 died', escape: false)
            ->assertSee('Kitchen procurement mark-ups')
            ->assertSee('National · KPK · on trial', escape: false)
            ->assertSee('737 outlets · as of 8 Jun 2026', escape: false)
            ->assertSee('Aceh Barat')
            ->assertSee('16 outlets · pop 209,498', escape: false)
            ->assertSee('admin')
            ->assertSee(now()->format('j M Y'));
    }

    public function test_the_changelog_page_does_not_group_records_or_show_totals(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'admin']);

        Poisoned::insert(array_map(fn (int $i) => [
            'uploaded_by' => $admin->id,
            'province' => "Province {$i}",
            'region_id' => "region-{$i}",
            'created_at' => now(),
            'updated_at' => now(),
        ], range(1, 5)));

        $content = $this->actingAs($admin)->get('/changelog')
            ->assertOk()
            ->assertDontSee('Every batch of data')
            ->assertDontSee('Records')
            ->getContent();

        // Five rows, not one line saying "Poisoning cases — 5".
        $this->assertSame(5, substr_count($content, 'Poisoning</td>'));
    }

    public function test_the_changelog_page_paginates_long_histories(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'admin']);

        Poisoned::insert(array_map(fn (int $i) => [
            'uploaded_by' => $admin->id,
            'province' => "Province {$i}",
            'region_id' => "region-{$i}",
            'created_at' => now(),
            'updated_at' => now(),
        ], range(1, 60)));

        $this->actingAs($admin)->get('/changelog')
            ->assertOk()
            ->assertSee('1–50 of 60', escape: false);

        $this->actingAs($admin)->get('/changelog?page=2')
            ->assertOk()
            ->assertSee('51–60 of 60', escape: false);
    }

    public function test_the_changelog_page_is_open_to_anyone(): void
    {
        $this->get('/changelog')->assertOk();
    }

    public function test_the_header_links_to_the_changelog(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/profile')
            ->assertOk()
            ->assertSee('Changelog');
    }

    public function test_the_changelog_page_filters_by_data_type(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'admin']);

        Poisoned::insert([
            ['uploaded_by' => $admin->id, 'province' => 'Aceh', 'region_id' => 'indonesia-aceh', 'place' => 'SDN 6 Matangkuli', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Corruption::insert([
            ['uploaded_by' => $admin->id, 'title' => 'Kitchen procurement mark-ups', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($admin)->get('/changelog?dataset=corruption')
            ->assertOk()
            ->assertSee('Kitchen procurement mark-ups')
            ->assertDontSee('SDN 6 Matangkuli')
            ->assertSee('Clear');

        // An unknown data type is ignored rather than emptying the table.
        $this->actingAs($admin)->get('/changelog?dataset=nonsense')
            ->assertOk()
            ->assertSee('Kitchen procurement mark-ups')
            ->assertSee('SDN 6 Matangkuli');
    }

    public function test_the_changelog_page_filters_by_upload_date(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'admin']);

        Poisoned::insert([
            ['uploaded_by' => $admin->id, 'province' => 'Aceh', 'region_id' => 'indonesia-aceh', 'place' => 'Uploaded today', 'created_at' => now(), 'updated_at' => now()],
            ['uploaded_by' => $admin->id, 'province' => 'Bali', 'region_id' => 'indonesia-bali', 'place' => 'Uploaded last week', 'created_at' => now()->subWeek(), 'updated_at' => now()->subWeek()],
        ]);

        $this->actingAs($admin)->get('/changelog?date='.now()->toDateString())
            ->assertOk()
            ->assertSee('Uploaded today')
            ->assertDontSee('Uploaded last week');

        // Both upload days are offered as options.
        $this->actingAs($admin)->get('/changelog')
            ->assertOk()
            ->assertSee(now()->format('j M Y'))
            ->assertSee(now()->subWeek()->format('j M Y'));
    }

    public function test_the_pager_numbers_the_pages(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'admin']);

        Poisoned::insert(array_map(fn (int $i) => [
            'uploaded_by' => $admin->id,
            'province' => "Province {$i}",
            'region_id' => "region-{$i}",
            'created_at' => now(),
            'updated_at' => now(),
        ], range(1, 160)));

        // Four pages: the one you are on is plain text, the others are links.
        $content = $this->actingAs($admin)->get('/changelog?page=2')
            ->assertOk()
            ->assertSee('changelog?page=3', escape: false)
            ->assertSee('changelog?page=4', escape: false)
            ->assertDontSee('Newer')
            ->assertDontSee('Older')
            ->getContent();

        $this->assertStringContainsString('aria-current="page" class="px-2 py-1 font-medium text-black">2<', $content);
        $this->assertStringNotContainsString('changelog?page=2"', $content);
    }

    public function test_the_pager_keeps_the_active_filters(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'admin']);

        Poisoned::insert(array_map(fn (int $i) => [
            'uploaded_by' => $admin->id,
            'province' => "Province {$i}",
            'region_id' => "region-{$i}",
            'created_at' => now(),
            'updated_at' => now(),
        ], range(1, 60)));

        $this->actingAs($admin)->get('/changelog?dataset=poisoning')
            ->assertOk()
            ->assertSee('dataset=poisoning', escape: false)
            ->assertSee('page=2', escape: false);
    }
}
