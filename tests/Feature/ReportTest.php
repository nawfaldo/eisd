<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Poisoned;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_header_links_to_reports(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/profile')
            ->assertOk()
            ->assertSee('Reports');
    }

    public function test_the_reports_page_requires_a_signed_in_user(): void
    {
        $this->get('/reports')->assertRedirect('/login');
        $this->get('/reports/create')->assertRedirect('/login');
        $this->post('/reports')->assertRedirect('/login');
    }

    public function test_a_submitted_report_starts_pending(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/reports', [
            'type' => 'poisoning',
            'region_id' => 'indonesia-aceh',
            'title' => 'SDN 6 Matangkuli',
            'regency' => 'Aceh Utara',
            'occurred_on' => '2026-09-01',
            'victims' => 12,
            'deaths' => 1,
            'source_url' => 'https://example.com/story',
        ])->assertRedirect('/reports');

        $report = Report::sole();

        $this->assertSame(ReportStatus::Pending, $report->status);
        $this->assertSame(ReportType::Poisoning, $report->type);
        $this->assertSame($user->id, $report->user_id);
        // The province name is taken from the map, not typed by the reporter.
        $this->assertSame('Aceh', $report->province);
        $this->assertSame(12, $report->victims);

        $this->actingAs($user)->get('/reports')
            ->assertOk()
            ->assertSee('SDN 6 Matangkuli')
            ->assertSee('Pending');
    }

    public function test_a_report_needs_a_type_and_a_checkable_source(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/reports', ['type' => 'poisoning', 'region_id' => 'indonesia-aceh', 'title' => 'A school'])
            ->assertSessionHasErrors('source_url');

        $this->actingAs($user)
            ->post('/reports', ['type' => 'nonsense', 'source_url' => 'https://example.com'])
            ->assertSessionHasErrors('type');

        // A poisoning case happens somewhere; only corruption can be national.
        $this->actingAs($user)
            ->post('/reports', ['type' => 'poisoning', 'title' => 'A school', 'source_url' => 'https://example.com'])
            ->assertSessionHasErrors('region_id');

        $this->actingAs($user)
            ->post('/reports', ['type' => 'sppg', 'region_id' => 'indonesia-aceh', 'source_url' => 'https://example.com'])
            ->assertSessionHasErrors('outlets');

        $this->assertSame(0, Report::count());
    }

    public function test_a_national_corruption_report_needs_no_province(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/reports', [
            'type' => 'corruption',
            'title' => 'Kitchen procurement mark-ups',
            'agency' => 'KPK',
            'amount' => 1_000_000_000,
            'source_url' => 'https://example.com/case',
        ])->assertRedirect('/reports');

        $this->assertNull(Report::sole()->province);
    }

    public function test_uploaded_rows_show_as_merged_reports_by_their_uploader(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'admin']);
        $other = User::factory()->create();

        Poisoned::insert([
            ['uploaded_by' => $admin->id, 'province' => 'Aceh', 'region_id' => 'indonesia-aceh', 'place' => 'SDN 6 Matangkuli', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($admin)->get('/reports')
            ->assertOk()
            ->assertSee('SDN 6 Matangkuli')
            ->assertSee('Merged');

        // Another user sees their own reports, not the admin's uploads.
        $this->actingAs($other)->get('/reports')
            ->assertOk()
            ->assertDontSee('SDN 6 Matangkuli');
    }

    public function test_a_users_reports_are_private_to_them(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();

        Report::create([
            'user_id' => $theirs->id,
            'type' => ReportType::Corruption,
            'status' => ReportStatus::Pending,
            'title' => 'Someone elses case',
            'source_url' => 'https://example.com/case',
        ]);

        $this->actingAs($mine)->get('/reports')
            ->assertOk()
            ->assertDontSee('Someone elses case')
            ->assertSee('You have not reported anything yet');
    }

    public function test_the_reports_page_offers_the_add_report_form(): void
    {
        $this->actingAs(User::factory()->create())->get('/reports')
            ->assertOk()
            ->assertSee('Add report')
            ->assertSee(route('reports.create'), escape: false);

        $this->actingAs(User::factory()->create())->get('/reports/create')
            ->assertOk()
            ->assertSee('Poisoning')
            ->assertSee('SPPG')
            ->assertSee('Corruption')
            ->assertSee('Aceh')
            ->assertSee('Submit report');
    }

    public function test_the_report_list_filters_by_type_and_status(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'admin']);

        Poisoned::insert([
            ['uploaded_by' => $admin->id, 'province' => 'Aceh', 'region_id' => 'indonesia-aceh', 'place' => 'A merged school', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Report::create([
            'user_id' => $admin->id,
            'type' => ReportType::Corruption,
            'status' => ReportStatus::Pending,
            'title' => 'A pending case',
            'source_url' => 'https://example.com/case',
        ]);

        $this->actingAs($admin)->get('/reports?type=corruption')
            ->assertOk()
            ->assertSee('A pending case')
            ->assertDontSee('A merged school')
            ->assertSee('Clear');

        $this->actingAs($admin)->get('/reports?status=merged')
            ->assertOk()
            ->assertSee('A merged school')
            ->assertDontSee('A pending case');

        // The two filters combine, and can rule everything out.
        $this->actingAs($admin)->get('/reports?type=corruption&status=merged')
            ->assertOk()
            ->assertSee('No reports match this filter');

        // Unknown values are ignored rather than emptying the list.
        $this->actingAs($admin)->get('/reports?type=nonsense&status=nonsense')
            ->assertOk()
            ->assertSee('A pending case')
            ->assertSee('A merged school');
    }

    public function test_the_report_list_pages_and_keeps_its_filters(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'admin']);

        Poisoned::insert(array_map(fn (int $i) => [
            'uploaded_by' => $admin->id,
            'province' => "Province {$i}",
            'region_id' => "region-{$i}",
            'created_at' => now(),
            'updated_at' => now(),
        ], range(1, 60)));

        $this->actingAs($admin)->get('/reports')
            ->assertOk()
            ->assertSee('1–50 of 60', escape: false)
            ->assertSee('reports?page=2', escape: false);

        $this->actingAs($admin)->get('/reports?page=2')
            ->assertOk()
            ->assertSee('51–60 of 60', escape: false);

        $this->actingAs($admin)->get('/reports?status=merged')
            ->assertOk()
            ->assertSee('status=merged', escape: false)
            ->assertSee('page=2', escape: false);
    }
}
