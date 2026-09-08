<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeded_admin_can_sign_in(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Auth::attempt(['name' => 'admin', 'password' => '1234']));
        $this->assertTrue(Auth::user()->isAdmin());
    }
}
