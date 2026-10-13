<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ProfileController
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request, #[CurrentUser] User $user): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->forceFill(['email_verified_at' => null]);
        }

        $user->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request, #[CurrentUser] User $user, #[Auth('web')] StatefulGuard $guard): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $guard->logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
