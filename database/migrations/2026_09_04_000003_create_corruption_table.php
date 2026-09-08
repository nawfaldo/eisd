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
        Schema::create('corruption', function (Blueprint $table) {
            $table->id();
            $table->text('title');                            // what the case is, as reported
            $table->string('province')->nullable();            // null for cases that are national in scope
            $table->string('region_id')->nullable()->index();  // matches a map region in resources/data/regions.json
            $table->string('regency')->nullable();
            // State loss in rupiah. Null while a case is open and no figure has been
            // put on it — the case still counts, it just cannot be added up.
            $table->unsignedBigInteger('amount')->nullable();
            $table->string('agency')->nullable();              // KPK, Kejaksaan, Polri, BPK
            $table->string('status')->nullable();              // investigation, suspects named, on trial, convicted
            $table->date('reported_on')->nullable();
            $table->string('reported_raw')->nullable();        // original date text, as with poisoning cases
            $table->text('source_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corruption');
    }
};
