<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Actions\BuildMetricGrowthSeries;
use App\Enums\GalleryMetric;
use App\Enums\UserRole;
use App\Filament\Widgets\GalleryGrowthChart;
use App\Filament\Widgets\GalleryStats;
use App\Filament\Widgets\LatestEmails;
use App\Models\Artwork;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_gallery_totals_and_latest_emails(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create();
        Artwork::factory()->count(3)->create(['category_id' => $category->id]);

        ContactMessage::query()->create([
            'sender_name' => 'A Visiting Curator',
            'sender_email' => 'curator@example.com',
            'message' => 'I would love to feature these pieces.',
        ]);

        // Act
        $response = $this->actingAs($admin)->get('/admin');

        // Assert
        $response->assertOk();
        $response->assertSee('Gallery overview');
        $response->assertSee('Artworks growth');
        $response->assertSee('Latest emails');
        $response->assertSee('A Visiting Curator');
    }

    public function test_stat_cards_link_to_their_growth_chart(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        // Act
        $response = $this->actingAs($admin)->get('/admin');

        // Assert
        $response->assertOk();
        $response->assertSee('metric=likes');
        $response->assertSee('metric=comments');
        $response->assertSee('metric=views');
        $response->assertSee('metric=messages');
        $response->assertSee('metric=users');
    }

    public function test_growth_chart_opens_the_clicked_metric(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Livewire::actingAs($admin);
        Livewire::withQueryParams(['metric' => 'likes']);

        // Act & Assert
        Livewire::test(GalleryGrowthChart::class)
            ->assertSee('Likes growth')
            ->assertSee('Last 30 days')
            ->set('filter', 7)
            ->assertSee('Likes growth');
    }

    public function test_growth_series_aggregates_records_by_day(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2026-11-19 12:00:00'));

        $artwork = Artwork::factory()->create([
            'category_id' => Category::factory()->create()->id,
        ]);

        // Two likes yesterday, one today, none two days ago.
        foreach ([1, 1, 0] as $daysAgo) {
            $like = Like::factory()->create([
                'user_id' => User::factory()->create()->id,
                'artwork_id' => $artwork->id,
            ]);

            $like->forceFill(['created_at' => now()->subDays($daysAgo)])->save();
        }

        // Act
        $series = app(BuildMetricGrowthSeries::class)->execute(GalleryMetric::Likes, 3);

        // Assert
        $this->assertSame(['Nov 17', 'Nov 18', 'Nov 19'], $series['labels']);
        $this->assertSame([0, 2, 1], $series['data']);
    }

    public function test_gallery_stats_widget_reports_totals(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create();
        Artwork::factory()->count(2)->create(['category_id' => $category->id]);

        // Act & Assert
        Livewire::actingAs($admin)
            ->test(GalleryStats::class)
            ->assertSee('Artworks')
            ->assertSee('Categories')
            ->assertSee('Likes')
            ->assertSee('Views')
            ->assertSee('Comments');
    }

    public function test_latest_emails_widget_lists_contact_messages(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $message = ContactMessage::query()->create([
            'sender_name' => 'Journalist',
            'sender_email' => 'press@example.com',
            'message' => 'Could we arrange an interview about the collection?',
        ]);

        // Act & Assert
        Livewire::actingAs($admin)
            ->test(LatestEmails::class)
            ->assertCanSeeTableRecords([$message])
            ->assertSee('Journalist')
            ->assertSee('press@example.com');
    }

    public function test_latest_emails_widget_has_an_empty_state(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        // Act & Assert
        Livewire::actingAs($admin)
            ->test(LatestEmails::class)
            ->assertSee('No messages yet');
    }
}
