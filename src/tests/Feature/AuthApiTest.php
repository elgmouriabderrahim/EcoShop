<?php

use App\Models\User;

it('registers a user and returns token', function (): void {
    $response = $this->postJson('/api/auth/register', [
        'full_name' => 'Alice Green',
        'email' => 'alice@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response
        ->assertCreated()
        ->assertJsonStructure([
            'message',
            'token',
            'user' => ['id', 'full_name', 'email', 'role'],
        ]);

    expect(User::query()->where('email', 'alice@example.com')->exists())->toBeTrue();
});

it('logs in an existing user and returns token', function (): void {
    User::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/api/auth/login', [
        'email' => 'john@example.com',
        'password' => 'password123',
    ])
        ->assertOk()
        ->assertJsonStructure(['message', 'token', 'user']);
});

it('rejects unauthenticated access to protected profile route', function (): void {
    $this->getJson('/api/auth/me')->assertUnauthorized();
});
