<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_gets_token_without_password(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Новый Гость',
            'email' => 'guest@resto.test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.email', 'guest@resto.test')
            ->assertJsonPath('user.role', 'client')
            ->assertJsonMissingPath('user.password');

        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseHas('users', ['email' => 'guest@resto.test', 'role' => 'client']);
    }

    public function test_register_validates_email_and_password_confirmation(): void
    {
        User::factory()->create(['email' => 'busy@resto.test']);

        $this->postJson('/api/register', [
            'name' => 'Гость',
            'email' => 'busy@resto.test',
            'password' => 'secret123',
            'password_confirmation' => 'other',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        User::factory()->create(['email' => 'client@resto.test']);

        $this->postJson('/api/login', [
            'email' => 'client@resto.test',
            'password' => 'wrong-password',
        ])->assertStatus(401)
            ->assertJsonPath('message', 'Неверный email или пароль');
    }

    public function test_request_without_token_returns_401(): void
    {
        $this->getJson('/api/user')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Требуется авторизация');
    }

    public function test_token_stops_working_after_logout(): void
    {
        User::factory()->create(['email' => 'client@resto.test']);

        $token = $this->postJson('/api/login', [
            'email' => 'client@resto.test',
            'password' => 'password',
        ])->assertOk()->json('token');

        $this->withToken($token)->getJson('/api/user')->assertOk();
        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
