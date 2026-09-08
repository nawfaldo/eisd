<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * National nutrition indicators. These are the numbers that would show whether
 * the programme worked, kept separately from the programme's own figures so a
 * claim about MBG can never be built out of MBG's own reporting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('indicator')->index();          // stunting, wasting, ...
            $table->string('label');                       // how the indicator is named on the page
            $table->string('period');                      // the survey year, as published
            $table->decimal('value', 6, 2);
            $table->string('unit')->default('%');
            $table->string('source');                      // the survey it was published in
            $table->text('source_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outcomes');
    }
};
