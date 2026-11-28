<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FeedIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_indexes_match_filtering_and_stable_sort_order(): void
    {
        // Arrange: the migrations should define indexes on the full ordering keys.
        $this->assertTrue(Schema::hasIndex('artworks', ['created_at', 'id']));
        $this->assertTrue(Schema::hasIndex('comments', ['artwork_id', 'created_at', 'id']));
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        // Act: inspect the SQLite query plans rather than guessing from query count.
        $artworkPlan = DB::select('EXPLAIN QUERY PLAN select * from artworks order by created_at desc, id desc limit 18');
        $commentPlan = DB::select('EXPLAIN QUERY PLAN select * from comments where artwork_id = 1 order by created_at desc, id desc limit 20');

        // Assert: both queries use their intended index without temporary sorting.
        $this->assertStringContainsString('artworks_feed_order', json_encode($artworkPlan));
        $this->assertStringContainsString('comments_feed_order', json_encode($commentPlan));
        $this->assertStringNotContainsString('TEMP B-TREE', json_encode([$artworkPlan, $commentPlan]));
    }
}
