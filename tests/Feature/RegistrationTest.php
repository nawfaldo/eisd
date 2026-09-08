<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_new_users_can_register_and_default_to_the_normal_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'taylor',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $user = User::firstWhere('name', 'taylor');

        $this->assertSame(UserRole::Normal, $user->role);
        $this->assertFalse($user->isAdmin());
    }

    public function test_names_must_be_unique(): void
    {
        User::factory()->create(['name' => 'taylor']);

        $this->post('/register', [
            'name' => 'taylor',
            'password' => 'password',
        ])->assertSessionHasErrors('name');

        $this->assertGuest();
    }

    public function test_short_passwords_are_allowed(): void
    {
        $this->post('/register', [
            'name' => 'taylor',
            'password' => '1234',
        ])->assertSessionHasNoErrors()->assertRedirect(route('home'));

        $this->assertAuthenticated();
    }

    public function test_password_is_still_required(): void
    {
        $this->post('/register', [
            'name' => 'taylor',
            'password' => '',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }
}
