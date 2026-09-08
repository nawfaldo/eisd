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
        // A link is written in the post like anything else, so it needs no field of its own.
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->dropColumn('link');
        });

        Schema::table('forum_posts', function (Blueprint $table) {
            $table->dropColumn('link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->text('link')->nullable();
        });

        Schema::table('forum_posts', function (Blueprint $table) {
            $table->text('link')->nullable();
        });
    }
};
