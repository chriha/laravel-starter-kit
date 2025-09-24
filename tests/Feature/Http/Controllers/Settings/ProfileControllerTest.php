<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the profile settings page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('settings/Profile')
            ->where('mustVerifyEmail', false)
            ->has('status')
        );
});

it('updates the profile without changing the email', function (): void {
    $user = User::factory()->create([
        'name' => 'Old Name',
    ]);

    $payload = [
        'name' => 'New Name',
        'email' => $user->email,
    ];

    $response = $this->actingAs($user)->patch(route('profile.update'), $payload);

    $response->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe($payload['email'])
        ->and($user->email_verified_at)->not->toBeNull();
});

it('updates the profile and resets verification when email changes', function (): void {
    $user = User::factory()->create([
        'name' => 'Old Name',
    ]);

    $newEmail = 'new-email@example.com';

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Another Name',
        'email' => $newEmail,
    ]);

    $response->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Another Name')
        ->and($user->email)->toBe($newEmail)
        ->and($user->email_verified_at)->toBeNull();
});

it('deletes the user account with the correct password', function (): void {
    $user = User::factory()->create(); // factory sets password to "password"

    $response = $this->actingAs($user)->delete(route('profile.destroy'), [
        'password' => 'password',
    ]);

    $response->assertRedirect('/');

    $this->assertGuest();
    $this->assertModelMissing($user);
});
