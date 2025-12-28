<?php

declare(strict_types=1);

arch('only traits in models concerns folder')
    ->expect('App\Models\Concerns')
    ->toBeTraits();

arch('model does not directly interact with http')
    ->expect('App\Models')
    ->not->toUse(['request', 'Illuminate\Http'])
    ->ignoring('App\Models\Concerns');
