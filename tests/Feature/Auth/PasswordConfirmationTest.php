<?php

declare(strict_types=1);

use Database\Factories\UserFactory;

test('confirm password screen can be rendered', function (): void {
    $user = UserFactory::new()->create();

    $response = $this->actingAs($user)->get(route('password.confirm'));

    $response->assertOk();
});

test('password can be confirmed', function (): void {
    $user = UserFactory::new()->create();

    $response = $this->actingAs($user)->post(route('password.confirm.store'), [
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
});

test('password is not confirmed with invalid password', function (): void {
    $user = UserFactory::new()->create();

    $response = $this->actingAs($user)->post(route('password.confirm.store'), [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors();
});
