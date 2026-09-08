<?php

namespace Database\Seeders;

use App\Models\Sppg;
use App\Models\User;
use Illuminate\Database\Seeder;

class SppgSeeder extends Seeder
{
    /**
     * Seed operational SPPG counts per province.
     */
    public function run(): void
    {
        $rows = json_decode(
            file_get_contents(database_path('seeders/data/sppg.json')),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        $now = now();
        $uploader = User::query()->where('name', 'admin')->value('id');

        Sppg::insert(array_map(fn (array $row) => [
            ...$row,
            'uploaded_by' => $uploader,
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows));
    }
}
