<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Institutions handling corruption cases.
 *
 * The `agency` column on `corruption` was already holding more than one body per
 * case ("BPKP / Kejaksaan Agung"), so counting cases per institution by string was
 * wrong. That relation is many-to-many and is now stored as one. The original
 * column stays as the raw wording of the source; the pivot is what gets queried.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('kind')->index();   // police, prosecutor, auditor, other
            $table->timestamps();
        });

        Schema::create('agency_corruption', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('corruption_id')->constrained('corruption')->cascadeOnDelete();
            // Where the body appeared in the source's own wording; 1 is the one it led with.
            $table->unsignedTinyInteger('position')->default(1);
            $table->timestamps();

            $table->unique(['agency_id', 'corruption_id']);
        });

        $this->backfill();
    }

    /** Split every existing `agency` string into the bodies it was naming. */
    private function backfill(): void
    {
        $now = now();

        foreach (DB::table('corruption')->whereNotNull('agency')->get(['id', 'agency']) as $case) {
            foreach ($this->split($case->agency) as $position => $name) {
                $id = DB::table('agencies')->where('name', $name)->value('id')
                    ?? DB::table('agencies')->insertGetId([
                        'name' => $name,
                        'kind' => $this->kind($name),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                DB::table('agency_corruption')->insertOrIgnore([
                    'agency_id' => $id,
                    'corruption_id' => $case->id,
                    'position' => $position + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function split(string $raw): array
    {
        return collect(preg_split('#\s*(?:/|,|\band\b|&)\s*#i', $raw))
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** What sort of body it is, read off the name Indonesian institutions use. */
    private function kind(string $name): string
    {
        return match (true) {
            (bool) preg_match('/^(Pol(da|res|resta|sek|ri)|Bareskrim)\b/i', $name) => 'police',
            (bool) preg_match('/^(Kejaksaan|Kejari|Kejati|KPK)\b/i', $name) => 'prosecutor',
            (bool) preg_match('/^(BPK|BPKP|Inspektorat)\b/i', $name) => 'auditor',
            default => 'other',
        };
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_corruption');
        Schema::dropIfExists('agencies');
    }
};
