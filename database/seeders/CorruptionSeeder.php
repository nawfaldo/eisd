<?php

namespace Database\Seeders;

use App\Models\Corruption;
use App\Models\User;
use Illuminate\Database\Seeder;

class CorruptionSeeder extends Seeder
{
    /**
     * Seed reported MBG corruption cases.
     */
    public function run(): void
    {
        $cases = json_decode(
            file_get_contents(database_path('seeders/data/corruption.json')),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        $now = now();
        $uploader = User::query()->where('name', 'admin')->value('id');

        foreach (array_chunk($cases, 200) as $chunk) {
            Corruption::insert(array_map(fn (array $case) => [
                ...$case,
                'uploaded_by' => $uploader,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));
        }
    }
}
