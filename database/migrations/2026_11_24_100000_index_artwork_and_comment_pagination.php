<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artworks', fn (Blueprint $table) => $table->index(['created_at', 'id'], 'artworks_feed_order'));
        Schema::table('comments', fn (Blueprint $table) => $table->index(['artwork_id', 'created_at', 'id'], 'comments_feed_order'));
    }

    public function down(): void
    {
        Schema::table('artworks', fn (Blueprint $table) => $table->dropIndex('artworks_feed_order'));
        Schema::table('comments', fn (Blueprint $table) => $table->dropIndex('comments_feed_order'));
    }
};
