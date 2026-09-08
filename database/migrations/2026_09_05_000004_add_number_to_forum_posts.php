<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // A post is answered by its number in the thread, and the opening post is
        // No.1, so replies count from 2. Row ids cannot serve: threads and posts are
        // separate sequences, and ">>1" would name both the opening post and the
        // first reply.
        Schema::table('forum_posts', function (Blueprint $table) {
            $table->unsignedInteger('number')->default(0);
        });

        foreach (DB::table('forum_posts')->orderBy('id')->get()->groupBy('thread_id') as $posts) {
            foreach ($posts->values() as $index => $post) {
                DB::table('forum_posts')->where('id', $post->id)->update(['number' => $index + 2]);
            }
        }

        Schema::table('forum_posts', function (Blueprint $table) {
            $table->unique(['thread_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            $table->dropUnique(['thread_id', 'number']);
            $table->dropColumn('number');
        });
    }
};
