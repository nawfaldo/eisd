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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');                            // poisoning, sppg, corruption
            $table->string('status')->default('pending')->index();
            $table->string('province')->nullable();            // null only for national corruption cases
            $table->string('region_id')->nullable()->index();  // matches a map region in resources/data/regions.json
            $table->string('regency')->nullable();
            // The school for a poisoning case, the case itself for corruption.
            $table->text('title')->nullable();
            // One date per report: when it happened, when it was reported, or what the count is as of.
            $table->date('occurred_on')->nullable();
            $table->unsignedInteger('victims')->nullable();
            $table->unsignedInteger('deaths')->nullable();
            $table->unsignedInteger('outlets')->nullable();
            $table->unsignedBigInteger('amount')->nullable();  // state loss in rupiah
            $table->string('agency')->nullable();
            $table->text('source_url');                        // a report without a source cannot be checked
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
