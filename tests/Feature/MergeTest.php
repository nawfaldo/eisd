<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Corruption;
use App\Models\Poisoned;
use App\Models\Report;
use App\Models\Sppg;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeTest extends TestCase
{
    use RefreshDatabase;

    private function report(User $user, array $attributes = []): Report
    {
        return Report::create([
            'user_id' => $user->id,
            'type' => ReportType::Poisoning,
            'status' => ReportStatus::Pending,
            'province' => 'Aceh',
            'region_id' => 'indonesia-aceh',
            'regency' => 'Aceh Utara',
            'title' => 'SDN 6 Matangkuli',
            'occurred_on' => '2026-09-01',
            'victims' => 12,
            'deaths' => 1,
            'source_url' => 'https://example.com/story',
            ...$attributes,
        ]);
    }

    public function test_only_admins_can_reach_the_merge_page(): void
    {
        $report = $this->report($normal = User::factory()->create());

        $this->get('/merge')->assertRedirect('/login');

        $this->actingAs($normal)->get('/merge')->assertForbidden();
        $this->actingAs($normal)->post(route('merge.merge', $report))->assertForbidden();
        $this->actingAs($normal)->post(route('merge.decline', $report))->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())->get('/merge')->assertOk();

        // Nothing a forbidden request asked for happened.
        $this->assertSame(ReportStatus::Pending, $report->fresh()->status);
    }

    public function test_the_merge_page_is_hidden_from_the_header_for_normal_users(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')
            ->assertOk()
            ->assertDontSee('>Merge<', escape: false);

        $this->actingAs(User::factory()->admin()->create())->get('/profile')
            ->assertOk()
            ->assertSee('>Merge<', escape: false);
    }

    public function test_merging_a_poisoning_report_writes_it_to_the_dataset(): void
    {
        $reporter = User::factory()->create();
        $report = $this->report($reporter);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('merge.merge', $report))
            ->assertRedirect();

        $case = Poisoned::sole();

        // Credited to the reporter, not to the admin who accepted it.
        $this->assertSame($reporter->id, $case->uploaded_by);
        $this->assertSame('SDN 6 Matangkuli', $case->place);
        $this->assertSame('Aceh Utara', $case->regency);
        $this->assertSame(12, $case->victims);
        $this->assertSame(1, $case->deaths);
        $this->assertSame('2026-09-01', $case->occurred_on->toDateString());

        $this->assertSame(ReportStatus::Merged, $report->fresh()->status);
    }

    public function test_merging_an_sppg_report_writes_it_to_the_dataset(): void
    {
        $reporter = User::factory()->create();

        $report = $this->report($reporter, [
            'type' => ReportType::Sppg,
            'title' => null,
            'outlets' => 737,
            'occurred_on' => '2026-06-08',
        ]);

        $this->actingAs(User::factory()->admin()->create())->post(route('merge.merge', $report));

        $row = Sppg::sole();

        $this->assertSame(737, $row->outlets);
        $this->assertSame('2026-06-08', $row->as_of->toDateString());
        $this->assertSame($reporter->id, $row->uploaded_by);
    }

    public function test_merging_a_corruption_report_writes_it_to_the_dataset(): void
    {
        $reporter = User::factory()->create();

        $report = $this->report($reporter, [
            'type' => ReportType::Corruption,
            'province' => null,
            'region_id' => null,
            'regency' => null,
            'title' => 'Kitchen procurement mark-ups',
            'amount' => 1_000_000_000,
            'agency' => 'KPK',
        ]);

        $this->actingAs(User::factory()->admin()->create())->post(route('merge.merge', $report));

        $case = Corruption::sole();

        $this->assertSame('Kitchen procurement mark-ups', $case->title);
        $this->assertSame(1_000_000_000, $case->amount);
        $this->assertSame('KPK', $case->agency);
        $this->assertNull($case->province);
    }

    public function test_declining_a_report_leaves_the_data_untouched(): void
    {
        $report = $this->report(User::factory()->create());

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('merge.decline', $report))
            ->assertRedirect();

        $this->assertSame(ReportStatus::Declined, $report->fresh()->status);
        $this->assertSame(0, Poisoned::count());
    }

    public function test_a_decided_report_cannot_be_decided_twice(): void
    {
        $report = $this->report(User::factory()->create());
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('merge.merge', $report));

        $this->actingAs($admin)->post(route('merge.merge', $report))->assertStatus(409);
        $this->actingAs($admin)->post(route('merge.decline', $report))->assertStatus(409);

        // Still exactly one row, and still merged.
        $this->assertSame(1, Poisoned::count());
        $this->assertSame(ReportStatus::Merged, $report->fresh()->status);
    }

    public function test_the_merge_page_lists_pending_reports_from_every_user_oldest_first(): void
    {
        $one = User::factory()->create(['name' => 'reporter-one']);
        $two = User::factory()->create(['name' => 'reporter-two']);

        $this->report($one, ['title' => 'Older report', 'created_at' => now()->subDay()]);
        $this->report($two, ['title' => 'Newer report']);
        $this->report($two, ['title' => 'Already declined', 'status' => ReportStatus::Declined]);

        $this->actingAs(User::factory()->admin()->create())->get('/merge')
            ->assertOk()
            ->assertSeeInOrder(['Older report', 'Newer report'])
            ->assertSee('reporter-one')
            ->assertSee('reporter-two')
            ->assertDontSee('Already declined')
            ->assertSee('Merge')
            ->assertSee('Decline');
    }

    public function test_a_merged_report_shows_as_merged_on_the_reporters_own_page(): void
    {
        $reporter = User::factory()->create();
        $report = $this->report($reporter);

        $this->actingAs(User::factory()->admin()->create())->post(route('merge.merge', $report));

        // It appears once, as the dataset row it became, not twice.
        $content = $this->actingAs($reporter)->get('/reports')
            ->assertOk()
            ->assertSee('Merged')
            ->getContent();

        $this->assertSame(1, substr_count($content, 'SDN 6 Matangkuli'));
    }

    public function test_the_merge_queue_filters_by_type_and_reporter(): void
    {
        $one = User::factory()->create(['name' => 'reporter-one']);
        $two = User::factory()->create(['name' => 'reporter-two']);

        $this->report($one, ['title' => 'A poisoning case']);
        $this->report($two, [
            'type' => ReportType::Corruption,
            'title' => 'A corruption case',
            'amount' => 1_000_000_000,
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/merge?type=corruption')
            ->assertOk()
            ->assertSee('A corruption case')
            ->assertDontSee('A poisoning case')
            ->assertSee('Clear');

        $this->actingAs($admin)->get('/merge?reporter='.$one->id)
            ->assertOk()
            ->assertSee('A poisoning case')
            ->assertDontSee('A corruption case');

        // The two filters combine, and can rule everything out.
        $this->actingAs($admin)->get('/merge?type=corruption&reporter='.$one->id)
            ->assertOk()
            ->assertSee('No pending reports match this filter');

        // Unknown values are ignored rather than emptying the queue.
        $this->actingAs($admin)->get('/merge?type=nonsense&reporter=9999')
            ->assertOk()
            ->assertSee('A poisoning case')
            ->assertSee('A corruption case');
    }

    public function test_the_merge_filters_only_offer_what_is_queued(): void
    {
        $reporter = User::factory()->create(['name' => 'reporter-one']);
        $decided = User::factory()->create(['name' => 'already-decided']);

        $this->report($reporter, ['title' => 'A poisoning case']);
        $this->report($decided, ['title' => 'Declined case', 'status' => ReportStatus::Declined]);

        $this->actingAs(User::factory()->admin()->create())->get('/merge')
            ->assertOk()
            ->assertSee('reporter-one')
            // Nobody whose only report is already decided, and no type with nothing queued.
            ->assertDontSee('already-decided')
            ->assertSee('Poisoning')
            ->assertDontSee('SPPG province');
    }

    public function test_the_merge_queue_pages_and_keeps_its_filters(): void
    {
        $reporter = User::factory()->create();

        foreach (range(1, 30) as $i) {
            $this->report($reporter, ['title' => "Case {$i}"]);
        }

        $this->actingAs(User::factory()->admin()->create())->get('/merge?type=poisoning')
            ->assertOk()
            ->assertSee('1–25 of 30', escape: false)
            ->assertSee('type=poisoning', escape: false)
            ->assertSee('page=2', escape: false);
    }
}
