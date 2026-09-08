<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'admin',
            'password' => '1234',
        ]);

        $this->call([
            PoisonedSeeder::class,
            CorruptionSeeder::class,
            SppgSeeder::class,
            SppgCitySeeder::class,
            OutcomeSeeder::class,
        ]);
    }
}
