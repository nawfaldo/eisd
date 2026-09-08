<?php

namespace Database\Seeders;

use App\Models\Outcome;
use App\Models\User;
use Illuminate\Database\Seeder;

class OutcomeSeeder extends Seeder
{
    /**
     * Seed published national nutrition figures.
     *
     * Every row here predates MBG. That is not an oversight: no national survey
     * covering the programme's own years has been published, so the honest
     * baseline is all there is to show.
     */
    public function run(): void
    {
        $rows = json_decode(
            file_get_contents(database_path('seeders/data/outcomes.json')),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        $now = now();
        $uploader = User::query()->where('name', 'admin')->value('id');

        Outcome::insert(array_map(fn (array $row) => [
            ...$row,
            'uploaded_by' => $uploader,
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows));
    }
}
