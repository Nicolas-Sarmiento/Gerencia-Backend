<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ─── POST /api/register ───

    public function test_register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/register', [
            'firstName' => 'Juan',
            'lastName' => 'Pérez',
            'email' => 'juan@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['access_token', 'token_type', 'user']);

        $this->assertDatabaseHas('users', ['email' => 'juan@test.com']);
    }

    public function test_register_fails_with_invalid_data(): void
    {
        $response = $this->postJson('/api/register', [
            'firstName' => 'Juan',
            // falta lastName, email, password
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lastName', 'email', 'password']);
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        User::create([
            'userId' => Str::uuid()->toString(),
            'firstName' => 'Existente',
            'lastName' => 'User',
            'email' => 'duplicado@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/register', [
            'firstName' => 'Otro',
            'lastName' => 'User',
            'email' => 'duplicado@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // ─── POST /api/login ───

    public function test_login_returns_token_with_valid_credentials(): void
    {
        User::create([
            'userId' => Str::uuid()->toString(),
            'firstName' => 'Juan',
            'lastName' => 'Pérez',
            'email' => 'juan@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'juan@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type', 'user']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::create([
            'userId' => Str::uuid()->toString(),
            'firstName' => 'Juan',
            'lastName' => 'Pérez',
            'email' => 'juan@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'juan@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Credenciales incorrectas']);
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'noexiste@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    // ─── POST /api/logout ───

    public function test_logout_revokes_token(): void
    {
        $user = User::create([
            'userId' => Str::uuid()->toString(),
            'firstName' => 'Juan',
            'lastName' => 'Pérez',
            'email' => 'juan@test.com',
            'password' => bcrypt('password123'),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Sesión cerrada correctamente']);
    }

    public function test_logout_fails_without_auth(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    // ─── GET /api/user ───

    public function test_get_user_returns_authenticated_user(): void
    {
        $user = User::create([
            'userId' => Str::uuid()->toString(),
            'firstName' => 'Juan',
            'lastName' => 'Pérez',
            'email' => 'juan@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->actingAs($user)->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonFragment(['email' => 'juan@test.com']);
    }

    public function test_get_user_fails_without_auth(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    // ─── GET /api/login (unauthenticated) ───

    public function test_unauthenticated_route_returns_401_message(): void
    {
        $response = $this->getJson('/api/login');

        $response->assertStatus(401)
            ->assertJsonFragment(['message' => 'No estás autenticado. Debes iniciar sesión o enviar el Token Bearer.']);
    }
}
