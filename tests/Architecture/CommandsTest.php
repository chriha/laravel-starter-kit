<?php

declare(strict_types=1);

arch('commands have Command suffix')
    ->expect('App\Console\Commands')
    ->toHaveSuffix('Command');

arch('commands do not use facades')
    ->expect('Illuminate\Support\Facades')
    ->not->toBeUsedIn('App\Http\Controllers');
