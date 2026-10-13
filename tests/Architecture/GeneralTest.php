<?php

declare(strict_types=1);

use App\Models\User;

arch()->preset()->php();
arch()->preset()->security();
// casts() must be protected (framework contract).
arch()->preset()->strict()->ignoring(User::class);

arch()->expect('App')
    ->toUseStrictEquality()
    ->toUseStrictTypes()
    ->classes()->toBeFinal();

arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed();

arch('app')
    ->expect(['dd', 'dump', 'dbg', 'var_dump'])
    ->not->toBeUsed();
