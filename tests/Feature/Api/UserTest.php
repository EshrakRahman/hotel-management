<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'customer']);
});


describe('auth check', function () {
    test('registers a new user', function () {

        /** @var Tests\TestCase $this */
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type']);
    });

    test('log in with valid credentials', function () {
        /** @var Tests\TestCase $this */
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $login->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type']);
    });

    test('returns 422 for invalid credentials', function () {
        /** @var Tests\TestCase $this */
        $login = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $login->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('returns authenticated user', function () {
        // This simulates "acting as" an authenticated user
        Sanctum::actingAs(User::factory()->create());
        /** @var Tests\TestCase $this */
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'phone',  'roles', 'created_at']]);
    });

    test('logs out authenticated user', function () {
        /** @var Tests\TestCase $this */
        Sanctum::actingAs(User::factory()->create());

        /** @var Tests\TestCase $this */
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);
    });
});
