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
        if (Schema::hasColumn('users', 'name') && ! Schema::hasColumn('users', 'display_name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('name', 'display_name');
            });
        }

        if (Schema::hasColumn('users', 'password') && ! Schema::hasColumn('users', 'password_hash')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('password', 'password_hash');
            });
        }

        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['user', 'admin', 'super_admin'])
                    ->default('user')
                    ->after('password_hash');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }

        if (Schema::hasColumn('users', 'password_hash') && ! Schema::hasColumn('users', 'password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('password_hash', 'password');
            });
        }

        if (Schema::hasColumn('users', 'display_name') && ! Schema::hasColumn('users', 'name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('display_name', 'name');
            });
        }
    }
};
