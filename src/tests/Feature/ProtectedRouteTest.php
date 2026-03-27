<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('allows authenticated user to access profile', function (): void {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', $user->email);
});

it('logs out authenticated user', function (): void {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/auth/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logout successful.');
});
