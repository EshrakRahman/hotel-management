<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

beforeEach(function () {
    Role::create(['name' => 'customer']);
});

describe('auth check', function () {
    test('registers a new user', function () {

        /** @var TestCase $this */
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
        /** @var TestCase $this */
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
        /** @var TestCase $this */
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
        /** @var TestCase $this */
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'phone',  'roles', 'created_at']]);
    });

    test('logs out authenticated user', function () {
        /** @var TestCase $this */
        Sanctum::actingAs(User::factory()->create());

        /** @var TestCase $this */
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);
    });

    test('updates user profile details successfully', function () {
        /** @var TestCase $this */
        $user = User::factory()->create([
            'name' => 'Original Name',
            'phone' => '123456789',
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/profile', [
            'name' => 'Updated Name',
            'phone' => '987654321',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.phone', '987654321');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'phone' => '987654321',
        ]);
    });

    test('validates user profile inputs', function () {
        /** @var TestCase $this */
        Sanctum::actingAs(User::factory()->create());

        $response = $this->putJson('/api/auth/profile', [
            'name' => '', // blank name
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    test('updates user password successfully', function () {
        /** @var TestCase $this */
        $user = User::factory()->create([
            'password' => Hash::make('old_password'),
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/password', [
            'current_password' => 'old_password',
            'password' => 'new_password_123',
            'password_confirmation' => 'new_password_123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Password updated successfully.']);

        $user->refresh();
        expect(Hash::check('new_password_123', $user->password))->toBeTrue();
    });

    test('fails to update user password with invalid current password', function () {
        /** @var TestCase $this */
        $user = User::factory()->create([
            'password' => Hash::make('old_password'),
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/password', [
            'current_password' => 'wrong_password',
            'password' => 'new_password_123',
            'password_confirmation' => 'new_password_123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    });
});
