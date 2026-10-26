<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('artworks', 'title')) {
            Schema::table('artworks', function (Blueprint $table) {
                $table->string('title')->nullable()->after('category_id');
            });
        }
        if (! Schema::hasColumn('artworks', 'description')) {
            Schema::table('artworks', function (Blueprint $table) {
                $table->text('description')->nullable()->after('title');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            $table->dropColumn(['title', 'description']);
        });
    }
};
