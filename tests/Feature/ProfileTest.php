<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_profile_page_shows_the_name_role_and_sign_out(): void
    {
        $this->actingAs(User::factory()->admin()->create(['name' => 'taylor']))
            ->get('/profile')
            ->assertOk()
            ->assertSee('taylor')
            ->assertSee('Admin')
            ->assertSee('Sign out');
    }

    public function test_the_profile_page_shows_the_normal_role(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/profile')
            ->assertOk()
            ->assertSee('Normal');
    }

    public function test_the_home_page_has_no_profile_details(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'taylor']))
            ->get('/')
            ->assertOk()
            ->assertDontSee('taylor')
            ->assertDontSee('Sign out');
    }
}
