<?php

declare(strict_types=1);

use Database\Factories\UserFactory;

test('registration screen can be rendered', function (): void {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function (): void {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registration validates input', function (array $overrides, string $field): void {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        ...$overrides,
    ])->assertSessionHasErrors($field);

    $this->assertGuest();
})->with([
    'missing name' => [['name' => ''], 'name'],
    'invalid email' => [['email' => 'not-an-email'], 'email'],
    'unconfirmed password' => [['password_confirmation' => 'different'], 'password'],
    'missing password' => [['password' => '', 'password_confirmation' => ''], 'password'],
]);

test('registration rejects a duplicate email', function (): void {
    UserFactory::new()->create(['email' => 'test@example.com']);

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('email');
});
