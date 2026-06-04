<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EmailVerificationPromptController
{
    /**
     * Show the email verification prompt page.
     */
    public function __invoke(Request $request, #[CurrentUser] User $user): RedirectResponse|Response
    {
        return $user->hasVerifiedEmail()
            ? redirect()->intended(route('dashboard', absolute: false))
            : Inertia::render('auth/VerifyEmail', ['status' => $request->session()->get('status')]);
    }
}
