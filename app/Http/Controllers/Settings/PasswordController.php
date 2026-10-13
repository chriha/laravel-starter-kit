<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

final class PasswordController
{
    /**
     * Show the user's password settings page.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/Password');
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request, #[CurrentUser] User $user, Hasher $hasher): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user->update([
            'password' => $hasher->make($validated['password']),
        ]);

        return back();
    }
}
