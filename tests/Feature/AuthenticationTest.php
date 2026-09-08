<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_users_can_authenticate_with_name_and_password(): void
    {
        $user = User::factory()->create(['name' => 'taylor']);

        $response = $this->post('/login', [
            'name' => 'taylor',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_users_cannot_authenticate_with_an_invalid_password(): void
    {
        User::factory()->create(['name' => 'taylor']);

        $this->post('/login', [
            'name' => 'taylor',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('name');

        $this->assertGuest();
    }

    public function test_users_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
