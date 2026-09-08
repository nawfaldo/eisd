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
        Schema::create('sppg_city', function (Blueprint $table) {
            $table->id();
            $table->string('city');
            $table->string('province')->nullable();
            // Null where the boundary source has no polygon for the regency:
            // the count still counts, it just cannot be drawn.
            $table->string('region_id')->nullable()->index();
            $table->unsignedInteger('outlets');
            // 2020 census population (UNFPA COD-PS), used to shade the map per head
            // rather than by raw count.
            $table->unsignedInteger('population')->nullable();
            // 'city' everywhere the directory has kabupaten/kota detail; 'province'
            // for Papua, where no public city-level source exists.
            $table->string('level')->default('city');
            // Meta's Relative Wealth Index (Chi et al., PNAS 2022), averaged over the
            // 2.4km grid cells falling inside the region. Independent of government stats.
            $table->decimal('rwi', 6, 4)->nullable();
            $table->date('as_of');
            $table->text('source_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sppg_city');
    }
};
