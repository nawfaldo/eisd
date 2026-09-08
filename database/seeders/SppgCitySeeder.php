<?php

namespace Database\Seeders;

use App\Models\SppgCity;
use App\Models\User;
use Illuminate\Database\Seeder;

class SppgCitySeeder extends Seeder
{
    /**
     * Seed operational SPPG counts per kabupaten/kota.
     */
    public function run(): void
    {
        $rows = json_decode(
            file_get_contents(database_path('seeders/data/sppg_city.json')),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        $now = now();
        $uploader = User::query()->where('name', 'admin')->value('id');

        foreach (array_chunk($rows, 200) as $chunk) {
            SppgCity::insert(array_map(fn (array $row) => [
                ...$row,
                'uploaded_by' => $uploader,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));
        }
    }
}
