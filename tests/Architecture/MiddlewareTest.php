<?php

declare(strict_types=1);

arch('middleware have handle method')
    ->expect('App\Http\Middleware')
    ->classes()
    ->toHaveMethod('handle');

arch('middlewares do not use facades')
    ->expect('Illuminate\Support\Facades')
    ->not->toBeUsedIn('App\Http\Middleware');
