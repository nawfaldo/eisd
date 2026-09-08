<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Replies count from 1: ">>1" is the first reply, and the opening post is
        // the thread itself rather than a numbered post in it.
        DB::table('forum_posts')->decrement('number');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('forum_posts')->increment('number');
    }
};
