<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_authenticated_pages(): void
    {
        $this->get('/profile')->assertRedirect(route('login'));
        $this->get('/reports')->assertRedirect(route('login'));
        $this->get('/merge')->assertRedirect(route('login'));
    }

    public function test_guests_can_read_the_site_and_are_offered_a_sign_in(): void
    {
        // Reading is open; the header offers only what a visitor can act on.
        $this->get('/')
            ->assertOk()
            ->assertSee('Login')
            ->assertDontSee('Profile')
            ->assertDontSee('Reports');
    }

    public function test_authenticated_users_can_view_the_home_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('Chart')
            ->assertSee('Profile');
    }

    public function test_authenticated_users_are_redirected_from_guest_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/login')->assertRedirect('/');
        $this->actingAs($user)->get('/register')->assertRedirect('/');
    }
}
