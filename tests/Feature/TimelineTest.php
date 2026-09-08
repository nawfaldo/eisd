<?php

namespace Tests\Feature;

use App\Models\Poisoned;
use App\Models\User;
use App\Support\Timeline;
use Database\Seeders\PoisonedSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimelineTest extends TestCase
{
    use RefreshDatabase;

    private function cases()
    {
        return Poisoned::whereNotNull('occurred_on')->get(['occurred_on', 'victims']);
    }

    public function test_it_buckets_by_month_and_fills_empty_periods(): void
    {
        $this->seed(PoisonedSeeder::class);

        $timeline = Timeline::build($this->cases(), 'all');

        $this->assertSame('month', $timeline['unit']);
        $this->assertGreaterThan(12, count($timeline['points']));

        // Continuous axis: consecutive months, no gaps.
        $periods = array_column($timeline['points'], 'period');
        $this->assertSame($periods, array_unique($periods));
        $this->assertContains(0, array_column($timeline['points'], 'value'));
    }

    public function test_each_range_picks_a_sensible_bucket(): void
    {
        $this->seed(PoisonedSeeder::class);

        $this->assertSame('day', Timeline::build($this->cases(), 'mtd')['unit']);
        $this->assertSame('day', Timeline::build($this->cases(), 'qtd')['unit']);
        $this->assertSame('week', Timeline::build($this->cases(), 'ytd')['unit']);
        $this->assertSame('week', Timeline::build($this->cases(), '1y')['unit']);
        $this->assertSame('month', Timeline::build($this->cases(), '3y')['unit']);
        $this->assertSame('month', Timeline::build($this->cases(), 'all')['unit']);
    }

    public function test_every_range_produces_a_plottable_series(): void
    {
        $this->seed(PoisonedSeeder::class);

        foreach (array_keys(Timeline::RANGES) as $range) {
            $timeline = Timeline::build($this->cases(), $range);

            $this->assertNotEmpty($timeline['points'], $range);
            $this->assertLessThan(400, count($timeline['points']), $range);
        }
    }

    public function test_narrower_ranges_never_total_more_than_wider_ones(): void
    {
        $this->seed(PoisonedSeeder::class);

        $total = fn (string $r) => array_sum(array_column(Timeline::build($this->cases(), $r)['points'], 'value'));

        $this->assertLessThanOrEqual($total('qtd'), $total('mtd'));
        $this->assertLessThanOrEqual($total('ytd'), $total('qtd'));
        $this->assertLessThanOrEqual($total('1y'), $total('ytd'));
        $this->assertLessThanOrEqual($total('all'), $total('1y'));
    }

    public function test_all_time_accounts_for_every_dated_victim(): void
    {
        $this->seed(PoisonedSeeder::class);

        $plotted = array_sum(array_column(Timeline::build($this->cases(), 'all')['points'], 'value'));

        $this->assertSame((int) Poisoned::whereNotNull('occurred_on')->sum('victims'), $plotted);
    }

    public function test_the_home_page_renders_every_timeframe_up_front(): void
    {
        $this->seed(PoisonedSeeder::class);

        $content = $this->actingAs(User::factory()->create())->get('/')
            ->assertOk()
            ->assertSee('Poisoned over time')
            ->getContent();

        // One pane and one chart per range, so switching never hits the server.
        foreach (array_keys(Timeline::RANGES) as $range) {
            $this->assertStringContainsString('tf-pane-'.$range, $content, $range);
            $this->assertStringContainsString('for="tf-'.$range.'"', $content, $range);
        }

        $this->assertSame(count(Timeline::RANGES), substr_count($content, '<polyline'));

        // Each pane keeps its own aria-label, which still names its bucket size.
        $this->assertStringContainsString('Poisoned per day', $content);
        $this->assertStringContainsString('Poisoned per week', $content);
        $this->assertStringContainsString('Poisoned per month', $content);
    }

    public function test_all_time_is_the_checked_timeframe(): void
    {
        $this->seed(PoisonedSeeder::class);

        $content = $this->actingAs(User::factory()->create())->get('/')->getContent();

        $this->assertSame(1, substr_count($content, 'checked'));
        $this->assertMatchesRegularExpression('/id="tf-all"[^>]*checked/', $content);
    }
}
