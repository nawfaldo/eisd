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
        Schema::create('sppg', function (Blueprint $table) {
            $table->id();
            $table->string('province');
            $table->string('region_id')->index();   // matches a map region in resources/data/regions.json
            $table->unsignedInteger('outlets');     // operational SPPG kitchens
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
        Schema::dropIfExists('sppg');
    }
};
