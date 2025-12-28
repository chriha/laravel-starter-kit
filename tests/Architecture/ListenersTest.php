<?php

declare(strict_types=1);

arch('listeners do not use facades')
    ->expect('Illuminate\Support\Facades')
    ->not->toBeUsedIn('App\Listeners');
