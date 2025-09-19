<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->security();

// arch()->preset()->strict();
arch()->expect('App')
    ->toUseStrictEquality()
    ->toUseStrictTypes()
    ->classes()->toBeFinal();

arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed();
