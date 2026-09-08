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
        Schema::create('poisoned', function (Blueprint $table) {
            $table->id();
            $table->string('province');                       // province as reported
            $table->string('region_id')->index();             // matches a map region in resources/data/regions.json
            $table->string('regency')->nullable();
            $table->text('place')->nullable();                // school(s) involved
            $table->date('occurred_on')->nullable();
            $table->string('occurred_raw')->nullable();       // original date text ("Awal Februari 2026")
            $table->unsignedInteger('victims')->nullable();   // poisoned kids; null when the source says "puluhan"/"ratusan"
            $table->unsignedInteger('deaths')->default(0);
            $table->text('source_url')->nullable();           // news link for the case
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poisoned');
    }
};
